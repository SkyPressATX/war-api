<?php

namespace WarApi\WarConfig;

class DefaultConfig {

	public $default;

	public function __construct(){
		$this->get_default_config();
	}

	private function get_default_config(){
		$this->default = [
            'api_name' => 'war',
            'api_prefix' => 'wp-json',
            'admin_toolbar' => false,
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
            'user_roles' => [],
            'version' => 1,
            'permalink' => '/posts/%postname%/',
            'category_base' => 'category'
        ];
	}
}
