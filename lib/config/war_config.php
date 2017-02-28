<?php

class war_config {

    public $flush = false;
    public $war_config;
    public $help;

    /**
     * Set Config
     * This function applies the war_api_config filters
     *
     * @return Array (Either the Default Config Values or the Modified Config Values)
     */
    public function set_config(){
        $new_config = apply_filters( 'war_api_config', $this->get_default_config() );
        $new_config[ 'namespace' ] = $new_config[ 'api_name' ] . '/v' . $new_config[ 'version' ];

        return $new_config;
    }

    /**
     * Run Dynamic Config
     * This function will pull out the Dynamic Config values and them properly implement them within WordPress
     *
     * @param $war_config ARRAY - Calculated war_config Array
     * This function does not return anything
     */
    public function run_dynamic_config( $war_config ){
        $this->war_config = $war_config; //Save to class property
        $this->help = new war_global_helpers;

        add_action( 'init', [ $this, 'config_admin_toolbar' ] ); // Run config_admin_toolbar
        add_action( 'init', [ $this, 'config_set_user_roles' ] ); // Run config_set_user_roles

        if( $this->war_config[ 'api_prefix' ] !== $this->help->get_old_api_prefix() ){
            add_filter( 'rest_url_prefix', [ $this, 'config_set_api_prefix' ] );
            add_action( 'init', [ $this, 'rewrite_flush' ] );
        }

        if( $this->war_config["is_rest"] === false ) // If not a Rest API Request
            add_action( 'wp', [ $this, 'config_localize' ] ); // Localize the warObject

        do_action( 'war_dynamic_config', $this->war_config ); // Run custom dynamic configs

        if( isset( $this->war_config[ 'war_jwt_expire' ] ) ) add_filter( 'war_jwt_expire', function( $e ){
            return $this->war_config[ 'war_jwt_expire' ];
        });
    }

    /**
     * Run Static Config
     * Static Config are adjustments that usually shouldn't change. Because of this we need to manually hit an endpoint to run them
     *
     * @param $war_config ARRAY - Calculated war_config Array
     * @return True | WP_Error Object
     */
    public function run_static_config( $war_config ){
        $this->war_config = $war_config;
        /** Run through the list **/
        $result = [
            'set_default_role' => $this->config_set_default_role( $this->war_config->user_roles ),
            'set_permalink' => $this->config_set_permalink( $this->war_config->permalink ),
            'set_category_base' => $this->config_set_category_base( $this->war_config->category_base )
        ];

        return $result;
    }

    public function config_set_api_prefix(){
        return $this->war_config[ 'api_prefix' ];
    }

    private function config_set_default_role( $user_roles ){
        update_option( 'default_role', end( $user_roles ) );
        return true;
    }

    public function config_set_permalink( $war_config = [] ){
        if( ! empty( $war_config ) ) $this->war_config = $war_config;
        if( empty( $this->war_config ) ) return new WP_Error( 'empty_war_config', 'The War Config Array is empty', [ 'status' => 405 ] );
        if( get_option( 'permalink_structure' ) !== $this->war_config[ 'permalink' ] ){
            global $wp_rewrite;
            $wp_rewrite->set_permalink_structure( $this->war_config[ 'permalink' ] );
            $this->rewrite_flush();
            return true;
        }
        return true;
    }

    public function config_set_category_base( $war_config = [] ){
        if( ! empty( $war_config ) ) $this->war_config = $war_config;
        if( empty( $this->war_config ) ) return new WP_Error( 'empty_war_config', 'The War Config Array is empty', [ 'status' => 405 ] );
        if( get_option( 'category_base' ) !== $this->war_config[ 'category_base' ] ){
            global $wp_rewrite;
            $wp_rewrite->set_category_base( $this->war_config[ 'category_base' ] );
            $this->rewrite_flush();
            return true;
        }
        return true;
    }

    public function rewrite_flush(){
        flush_rewrite_rules();
    }

    /**
     * Config Localize
     * Create the warObject for the Parent Theme
     *
     * This function does not return anything
     */
    public function config_localize(){
        wp_register_script('war_site_details', null);
        $war_object = array(
            'warPath' => get_template_directory_uri(),
            'childPath' => get_stylesheet_directory_uri(),
            'nonce' => wp_create_nonce('wp_rest'),
            'permalink' => preg_replace('/\%.+\%/',':slug', get_option( 'permalink_structure' ) ),
            'category_base' => preg_replace('/\%.+\%/',':slug', get_option( 'category_base' ) ),
            'api_prefix' => $this->war_config['api_prefix'],
            'api_namespace' => $this->war_config['namespace']
        );
        $war_object = apply_filters( 'war_object', $war_object );
        wp_localize_script('war_site_details','warObject',$war_object);
        wp_enqueue_script('war_site_details');
    }

    /**
     * Config Admin Toolbar
     * Default Show or Hide of Admin Toolbar when browsing Site
     *
     * This function does not return anything
     */
    public function config_admin_toolbar(){
        if( ! is_bool( $this->war_config['admin_toolbar'] ) ) $this->war_config['admin_toolbar'] = false;
        show_admin_bar( $this->war_config['admin_toolbar'] );
    }

    /**
     * Config Set User Roles
     * Adjust the available WordPress User Roles based on war_config user_roles property
     *
     * This function does not return anything
     */
    public function config_set_user_roles(){
        global $wp_roles;

        /** Retreive Previous Custom Roles **/
        $old_roles = get_option( 'war_custom_user_roles' );
        /** Remove any old custom roles that are no longer declared. Must perform this step before exiting **/
        foreach( $old_roles as $or ){
            if( ! in_array( $or, $this->war_config[ 'user_roles' ] ) ){
                $wp_roles->remove_role( $or );
                $remove_role = true;
            }
        }

        if( empty( $this->war_config[ 'user_roles' ] ) || ! isset( $remove_role ) ) return; //Now we can leave knowing that things are cleaned up

        /***** Define needed variables *****/
        $custom_roles = array_reverse( $this->war_config['user_roles'] ); // Start at the end first
        $avail_roles = array_keys( $wp_roles->get_names() );

        /** Temp solution to reset all default roles **/
        // require_once ABSPATH . '/wp-admin/includes/schema.php';
        // if( function_exists( 'populate_roles' ) ) populate_roles();

        /***** Process our custom user roles and groups *****/
        $processed_roles = array();
        $role_size = sizeof( $custom_roles );
        for( $i=0; $i<$role_size; $i++ ){
            $role = $custom_roles[$i];
            $rs = array_slice( $custom_roles, 0, ($i+1) );
            $name = ucfirst($role);
            $caps = $this->help->format_caps( $role, $rs );
            $processed_roles[$role] = (object) ['name' => $name, 'capabilities' => $caps];
        }

        /***** Add our custom roles to $wp_roles *****/
        foreach($processed_roles as $role => $val ){
            $new_role = $wp_roles->add_role( $role, $val->name, $val->capabilities );
            if( $new_role === null ){ //Looks like that role already exists
                $existing_role = $wp_roles->get_role( $role );
                foreach( array_keys( $val->capabilities) as $c ){
                    if(! in_array($c, array_keys( $existing_role->capabilities ) ) ) $wp_roles->add_cap( $role, $c );
                }
            }
        }

        /** Save the New Roles and Groups **/
        $new_roles = update_option( 'war_custom_user_roles', $this->war_config['user_roles'] );
        /** Make sure the Default Role is set **/
        $this->config_set_default_role( $this->war_config[ 'user_roles' ] );
        return;
    }

    /**
     * Get Default Config
     *
     * @return Array - Default Config Values
     */
    private function get_default_config(){
        return [
            'api_name' => 'war',
            'api_prefix' => 'wp-json',
            'admin_toolbar' => false,
            'blog_id' => get_current_blog_id(),
            'default_endpoints' => [
                'build_tables' => true,
                'set_config' => true,
                'menu' => true,
                'site_options' => true,
                'theme_options' => true,
                'jwt_token' => true,
                'login' => true,
                'logout' => true,
                'register' => true,
                'get_home_page' => true
            ],
            'is_multisite' => is_multisite(),
            'user_roles' => [],
            'version' => 1,
            'permalink' => '/posts/%postname%/',
            'category_base' => 'category'
        ];
    }
}
