<?php
/*
Plugin Name: WAR API
Description:  WAR API
Version: 0.2.0
Author: BMO
License: MIT
*/

spl_autoload_register( 'war_autoloader' );
function war_autoloader( $class ){
    $src_dir = __DIR__ . '/src/';
    $file_array = explode( '\\', $class );
    $file = end( $file_array ) . ".class.php";
    if( file_exists( $src_dir . $file ) ) require_once $src_dir . $file;
}

require_once 'vendor/autoload.php'; // Pull in Composer Libraries

use War_Api\War_Init as War_Init;
use War_Api\War_Defaults as War_Defaults;

/**
 * War_Api Class to manage construction of New API
 **/

class War_Api {

    private $config;
    private $endpoints;
    private $models;

    public function __construct(){
        $war_default = new War_Defaults;
        $this->config = $war_default->config;
        $this->endpoints = $war_default->endpoints;
        $this->models = $war_default->models;
    }

    public function add_config( $slug = false, $val = false ){
        if( is_array( $slug ) )
            $this->config = array_merge( $this->config, $slug );
        else
            $this->config[ $slug ] = $val;
    }

    public function add_endpoints( $slug = false, $endpoint = array() ){
        if( is_array( $slug ) )
            $this->endpoints = array_merge( $this->endpoints, $slug );
        else
            $this->endpoints[ $slug ] = $val;
    }

    public function add_models( $slug = false, $model = array() ){
        if( is_array( $slug ) )
            $this->models = array_merge( $this->models, $slug );
        else
            $this->models[ $slug ] = $val;
    }

    public function init(){
        $war_init = new War_Init( $this->config, $this->endpoints, $this->models );
        add_action( 'init', [ $war_init, 'init' ] );
    }

} // END War_Api class

// $war_api = new War_Api;
// add_action( 'plugins_loaded', [ $war_api, 'run' ], 9999 );

// if( is_admin() ) new war_api_updater( __FILE__, 'SkyPressATX', 'war-api' );

/**
 * war_api class
 * This class should handle the WordPress Specific Needs
 **/

//
// class war_init {
//
//     public $help;
//     public $is_rest = false;
//     public $war_config_object;
//     public $war_config = array();
//     public $war_endpoints = array();
//     public $war_models = array();
//
//     public function init(){
//         $this->help = new war_global_helpers; // Setup the $help object
//         $this->set_config(); // Set the war_config Property
//         if( $this->is_rest ){
//             $war_defaults_object = new war_defaults;
//             $war_defaults_object->register_default_endpoints( $this->war_config );
//
//             add_action( 'rest_api_init', [ new war_security, 'security_is_user_logged_in' ] );
//             add_action( 'rest_api_init', [ $this, 'war_api_init' ] );
//         }
//
//         if( ! $this->is_rest ) add_filter( 'request', [ $this, 'war_handle_non_pages' ] );
//     }
//
//     public function war_api_init(){
//         do_action( 'war_api_extend' ); // plugins that extend war_api shouldn't run anything. Rather, set when things should run
//         do_action( 'war_custom_endpoints', $this->war_config );
//         do_action( 'war_custom_models', $this->war_config );
//     }
//
//     public function war_auto_setup(){
//         if( empty( $this->war_config_object ) ) $this->war_config_object = new war_config;
//         if( empty( $this->war_config ) ) $this->war_config = $this->war_config_object->set_config();
//
//         $this->war_config_object->config_set_permalink( $this->war_config );
//         // $this->war_config_object->config_set_category_base( $this->war_config );
//     }
//
//     public function war_handle_non_pages( $request ){
//         if( isset( $request[ 'error' ] ) && intval( $request[ 'error' ] ) == 404 ) return [];
//         return $request;
//     }
//
//     private function set_config(){
//         do_action( 'war_config_extend' ); // plugins that extend war_api shouldn't run anything. Rather, set when things should run
//         if( empty( $this->war_config_object ) ) $this->war_config_object = new war_config;
//         if( empty( $this->war_config ) ) $this->war_config = $this->war_config_object->set_config();
//
//         $this->is_rest = $this->help->is_rest_request( $this->war_config["api_prefix"] );
//         $this->war_config["is_rest"] = $this->is_rest;
//         $this->war_config_object->run_dynamic_config( $this->war_config );
//     }
//
// }
//
// /***** Make it Happen *****/
// $war_init = new war_init;
// add_action('plugins_loaded', [ $war_init, 'init'], 9999);
// register_activation_hook( __FILE__, [ $war_init, 'war_auto_setup' ] );
//
// /** Updater Class only needs to be available in wp-admin **/
// if( is_admin() ) new war_api_updater( __FILE__, 'SkyPressATX', 'war-api' );
