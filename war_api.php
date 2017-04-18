<?php
/*
Plugin Name: WAR API
Description:  WAR API
Version: 0.1.4
Author: BMO
License: MIT
*/

/*
 * Require API Files
 * Really need to get to some namespacing and autoloading
 */
require_once 'lib/helpers/war_api_abstract.php'; //At the top for a reason
require_once 'lib/config/war_config.php';
require_once 'lib/data/war_dao.php';
require_once 'lib/data/war_data.php';
require_once 'lib/endpoints/war_defaults.php';
require_once 'lib/endpoints/new_custom_endpoint.php';
require_once 'lib/endpoints/new_custom_model.php';
require_once 'lib/endpoints/war_get_options.php';
require_once 'lib/endpoints/war_menu.php';
require_once 'lib/endpoints/war_set_configs.php';
require_once 'lib/endpoints/war_user_actions.php';
require_once 'lib/helpers/war_api_updater.php';
require_once 'lib/helpers/war_arg_helper.php';
require_once 'lib/helpers/global_helpers.php';
require_once 'lib/security/war_security.php';
require_once 'lib/security/war_jwt.php';
require_once 'lib/vendor/autoload.php';

class war_init {

    public $help;
    public $is_rest = false;
    public $war_config_object;
    public $war_config = array();
    public $war_endpoints = array();
    public $war_models = array();

    public function init(){
        $this->help = new war_global_helpers; // Setup the $help object
        $this->set_config(); // Set the war_config Property
        if( $this->is_rest ){
            $war_defaults_object = new war_defaults;
            $war_defaults_object->register_default_endpoints( $this->war_config );

            add_action( 'rest_api_init', [ new war_security, 'security_is_user_logged_in' ] );
            add_action( 'rest_api_init', [ $this, 'war_api_init' ] );
        }

        if( ! $this->is_rest ) add_filter( 'request', [ $this, 'war_handle_non_pages' ] );
    }

    public function war_api_init(){
        do_action( 'war_api_extend' ); // plugins that extend war_api shouldn't run anything. Rather, set when things should run
        do_action( 'war_custom_endpoints', $this->war_config );
        do_action( 'war_custom_models', $this->war_config );
    }

    public function war_auto_setup(){
        if( empty( $this->war_config_object ) ) $this->war_config_object = new war_config;
        if( empty( $this->war_config ) ) $this->war_config = $this->war_config_object->set_config();

        $this->war_config_object->config_set_permalink( $this->war_config );
        // $this->war_config_object->config_set_category_base( $this->war_config );
    }

    public function war_handle_non_pages( $request ){
        if( isset( $request[ 'error' ] ) && intval( $request[ 'error' ] ) == 404 ) return [];
        return $request;
    }

    private function set_config(){
        do_action( 'war_config_extend' ); // plugins that extend war_api shouldn't run anything. Rather, set when things should run
        if( empty( $this->war_config_object ) ) $this->war_config_object = new war_config;
        if( empty( $this->war_config ) ) $this->war_config = $this->war_config_object->set_config();

        $this->is_rest = $this->help->is_rest_request( $this->war_config["api_prefix"] );
        $this->war_config["is_rest"] = $this->is_rest;
        $this->war_config_object->run_dynamic_config( $this->war_config );
    }

}

/***** Make it Happen *****/
$war_init = new war_init;
add_action('plugins_loaded', [ $war_init, 'init'], 9999);
register_activation_hook( __FILE__, [ $war_init, 'war_auto_setup' ] );

/** Updater Class only needs to be available in wp-admin **/
if( is_admin() ) new war_api_updater( __FILE__, 'SkyPressATX', 'war-api' );
