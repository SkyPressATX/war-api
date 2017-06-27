<?php
/*
Plugin Name: WAR API
Description:  WAR API
Version: 0.1.9.3
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
        define( 'WAR_API_INIT', true );
        $this->config[ 'nonce' ] = wp_create_nonce('wp_rest'); //Add this here so that it can't be edited
        $war_init = new War_Init( $this->config, $this->endpoints, $this->models );
        $war_init->init();
    }

} // END War_Api class

// register_activation_hook( __FILE__, [ $war_init, 'war_auto_setup' ] );
//
// /** Updater Class only needs to be available in wp-admin **/
// if( is_admin() ) new war_api_updater( __FILE__, 'SkyPressATX', 'war-api' );


add_action( 'plugins_loaded', function(){
    if( defined( 'WAR_API_INIT' ) ) return;
    /** Initialize the WAR API if no custom plugin has done so already **/
    $war_api = new War_Api;
    $war_api->init();
}, 99 );
