<?php

if( class_exists( 'war_api' ) ): //Make sure the war_api class exists first
    class war_defaults extends war_api {

        public function register_default_endpoints( $war_config ){

            $defaults = [
                'build_tables' => [
                    'uri' => '/data-model-setup',
                    'access' => 'administrator',
                    'callback' => [ $this, 'war_build_tables' ]
                ],
                'jwt_token' => [
                    'uri' => '/jwt-token',
                    'access' => true,
                    'callback' => [ $this, 'war_jwt_create' ]
                ],
                'menu' => [
                    'uri' => '/menu',
                    'access' => false,
                    'callback' => [ $this, 'war_get_menu' ]
                ],
                'theme_options' => [
                    'uri' => '/theme-options',
                    'access' => false,
                    'method' => 'POST',
                    'callback' => [ $this, 'war_theme_options' ]
                ],
                'site_options' => [
                    'uri' => '/site-options',
                    'access' => false,
                    'callback' => [ $this, 'war_site_options' ]
                ],
                'login' => [
                    'uri' => '/login',
                    'access' => null,
                    'method' => 'POST',
                    'params'=>[
                        'username' => [ 'type' => 'string', 'required' => true ],
                        'password' => [ 'type' => 'string', 'required' => true ]
                    ],
                    'callback' => [ $this, 'war_login' ]
                ],
                'logout' => [
                    'uri' => '/logout',
                    'access' => true,
                    'callback' => [ $this, 'war_logout' ]
                ],
                'register' => [
                    'uri' => '/register',
                    'access' => null,
                    'method' => 'POST',
                    'params' => [
                        'email' => [ 'type' => 'email', 'required' => true ],
                        'password' => [ 'type' => 'string', 'required' => true ],
                        'role' => [
                            'type' => 'string',
                            'options' => ( isset( $war_config[ 'user_roles' ] ) ) ? $war_config[ 'user_roles' ] : [],
                            'default' => end( $war_config['user_roles'] )
                        ]
                    ],
                    'callback' => [ $this, 'war_signup' ]
                ],
                'set_config' => [
                    'uri' => '/run-app-config',
                    'access' => 'administrator',
                    'callback' => [ $this, 'war_app_config' ]
                ],
                'get_home_page' => [
                    'uri' => '/home',
                    'access' => false,
                    'callback' => [ $this, 'war_get_home' ]
                ]
            ];

            $allowed_endpoints = array_filter( $war_config[ 'default_endpoints' ] );

            array_walk( $defaults, function($end, $key ) use( $allowed_endpoints ){
                if( isset( $allowed_endpoints[ $key ] ) )
                    $this->war_add_endpoint( $end );
            });

        }

        public function war_get_home( $data ){
            $home_id = get_option('page_on_front');
            if( intval( $home_id ) === 0 || is_wp_error( $home_id ) ) return new WP_Error( 'no_home_page', 'No Home Page was Set' );
            $home_req = new WP_Rest_Request( 'GET', '/wp/v2/pages' );
            // $home_req->set_param( 'id', $home_id );
            $home_req->set_query_params([
                'id' => $home_id,
                '_embed' => '1'
            ]);
            $home_res = rest_do_request( $home_req );
            if( is_wp_error( $home_res ) ) return $home_res;
            return $home_res->data[0]; // There should only be one
        }

        public function war_login( $data ){
            $class = $this->war_get_wua_class();
            return $class->war_login( $data );
        }

        public function war_logout( $data ){
            $class = $this->war_get_wua_class();
            return $class->war_logout( $data );
        }

        public function war_signup( $data ){
            $class = $this->war_get_wua_class();
            return $class->war_signup( $data );
        }

        public function war_jwt_create( $data ){
            $class = $this->war_get_wua_class();
            return $class->war_jwt_create( $data );
        }

        public function war_site_options( $data ) {
            $class = $this->war_get_wconf_class();
            return $class->war_site_options( $data );
        }

        public function war_theme_options( $data ) {
            $class = $this->war_get_wconf_class();
            return $class->war_theme_options( $data );
        }

        public function war_get_menu( $data ) {
            $class = $this->war_require_and_get_class( 'war_menu', '/war_menu.php' );
            return $class->war_get_menu();
        }

        public function war_app_config( $data ){
            $class = $this->war_require_and_get_class( 'war_set_configs', '/war_set_configs.php' );
            return $class->war_app_config( $data );
        }

        public function war_build_tables( $data ){
            $class = $this->war_require_and_get_class( 'war_set_configs', '/war_set_configs.php' );
            return $class->war_build_tables( $data );
        }

        private function war_get_wconf_class(){
            return new war_get_options;
        }

        private function war_get_wua_class(){
            return new war_user_actions;
        }

        private function war_require_and_get_class( $c = false, $f = false ){
            if($c === false) return false;
            return new $c;
        }

    }

endif;