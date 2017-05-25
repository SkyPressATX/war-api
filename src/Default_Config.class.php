<?php

namespace War_Api\Defaults;

class Default_Config {

	public $default_config;

	public function __construct(){
		$this->default_config = $this->set_default_config();
	}

	private function set_default_config(){
		return [
            'api_name' => 'war',
            'api_prefix' => 'wp-json',
            'admin_toolbar' => false,
            'user_roles' => [],
            'version' => 1,
            'permalink' => '/posts/%postname%/',
            'category_base' => 'category'
        ];
	}

}
