<?php

namespace War_Api;

use War_Api\Defaults\War_Default_Congif as Default_Config;

class War_Config {

	private $config;

	public function __construct(){
		$this->config = $this->set_default_config();
	}

	public function get_config(){
		return $this->config;
	}

	private function set_default_config(){
		return [
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
