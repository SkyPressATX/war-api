<?php

namespace War_Api;

/**
 * These methods should run, well, automatically; at the right time
 **/
class War_Auto_Config {

	private $war_config;

	public function __construct( $config = array() ){
		$this->war_config = $config;
	}

	public function manage_admin_toolbar(){
		if( ! is_bool( $this->war_config->admin_toolbar ) ) $this->war_config->admin_toolbar = false;
        show_admin_bar( $this->war_config->admin_toolbar );
	}

	public function set_user_roles(){
		return true;
	}

	public function war_localize(){
		wp_register_script('war_site_details', null);
        $war_object = array(
            'warPath' => get_template_directory_uri(),
            'childPath' => get_stylesheet_directory_uri(),
            'nonce' => wp_create_nonce('wp_rest'),
            'permalink' => preg_replace('/\%.+\%/',':slug', get_option( 'permalink_structure' ) ),
            'category_base' => preg_replace('/\%.+\%/',':slug', get_option( 'category_base' ) ),
			'api_prefix' => rest_get_url_prefix(),
            'api_namespace' => $this->war_config->namespace
        );
        $war_object = apply_filters( 'war_object', $war_object );
        wp_localize_script('war_site_details','warObject',$war_object);
        wp_enqueue_script('war_site_details');
	}

	public function set_api_prefix( $prefix ){
        return $this->war_config->api_prefix;
    }
}
