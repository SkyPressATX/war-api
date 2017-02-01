<?php
/*
Plugin Name: WAR API
Description:  WAR API
Version: 0.1.2
Author: BMO
License: MIT
*/

/*
 * Require API Files
 */
require_once 'lib/config/war_config.php';
require_once 'lib/helpers/war_api_abstract.php';
require_once 'lib/endpoints/war_defaults.php';
require_once 'lib/helpers/war_api_updater.php';

class war_api_init {

    public $help;
    public $is_rest = false;
    public $war_config_object;
    public $war_config = array();
    public $war_endpoints = array();
    public $war_models = array();

    public function __construct(){
        $this->help = new war_global_helpers; // Setup the $help object
    }

    public function init(){
        $this->set_config(); // Set the war_config Property
        if( $this->is_rest ){
            /** OFF WITH THEIR HEADS! **/
            $headless_action_hooks = [
                'setup_theme',
                'after_setup_theme',
                'wp_register_sidebar_widget',
                // 'template_redirect',
                // 'get_header',
                // 'wp_enqueue_scripts',
                // 'wp_head',
                // 'wp_print_styles',
                // 'wp_print_scripts',
                // 'get_footer',
                // 'wp_footer',
                // 'wp_print_footer_scripts'
            ];
            foreach( $headless_action_hooks as $hook ){
                remove_all_actions( $hook );
            }

            $this->register_defaults();
            $this->register_extended();
        }
    }

    public function war_api_init(){
        do_action( 'war_custom_endpoints', $this->war_config );
        do_action( 'war_custom_models', $this->war_config );
    }

    public function war_auto_setup(){
        if( empty( $this->war_config_object ) ) $this->war_config_object = new war_config;
        if( empty( $this->war_config ) ) $this->war_config = $this->war_config_object->set_config();

        $this->war_config_object->config_set_permalink( $this->war_config );
        $this->war_config_object->config_set_category_base( $this->war_config );
    }

    private function set_config(){
        do_action( 'war_config_extend' ); // plugins that extend war_api shouldn't run anything. Rather, set when things should run
        if( empty( $this->war_config_object ) ) $this->war_config_object = new war_config;
        if( empty( $this->war_config ) ) $this->war_config = $this->war_config_object->set_config();

        $this->is_rest = $this->help->is_rest_request( $this->war_config["api_prefix"] );
        $this->war_config["is_rest"] = $this->is_rest;
        $this->war_config_object->run_dynamic_config( $this->war_config );
    }

    private function register_defaults(){
        $war_defaults_object = new war_defaults;
        $war_defaults_object->register_default_endpoints( $this->war_config );
    }

    private function register_extended(){
        do_action( 'war_api_extend' ); // plugins that extend war_api shouldn't run anything. Rather, set when things should run
        add_action('rest_api_init', [ $this, 'war_api_init' ]);
    }

}

/***** Make it Happen *****/
$war_api_init = new war_api_init;
add_action('plugins_loaded', [ $war_api_init, 'init'], 9999);
register_activation_hook( __FILE__, [ $war_api_init, 'war_auto_setup' ] );

/** Updater Class only needs to be available in wp-admin **/
if( is_admin() ) new war_api_updater( __FILE__, 'SkyPressATX', 'war-api' );
