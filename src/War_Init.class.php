<?php

namespace War_Api;

use War_Api\War_Endpoint as War_Endpoint;
use War_Api\War_Model as War_Model;
use War_Api\Security\War_User as War_User;
use War_Api\War_Auto_Config as War_Auto_Config;
use War_Api\Helpers\Global_Helpers as Global_Helpers;

/**
 * WAR Initialization Class.
 *
 * This class is responsible for initializing all the necessary functionality of the WAR API.
 *
 * @since 0.0.1
 **/
class War_Init {

	public $war_config;
	public $war_endpoints;
	public $war_models;
	public $current_user;
	public $help;
	public $auto_config;

	public function __construct( $config = array(), $endpoints = array(), $models = array() ){
		$this->war_config = (object)$config;
		$this->create_namespace();
		$this->war_endpoints = $endpoints;
		$this->war_models = $models;
	}

	/**
	 * The Main Init Function.
	 *
	 * This function handles the proper Hook and Filters the WAR API uses.
	 *
	 * @since 0.0.1
	 * @access public
	 **/
	public function init(){
		$this->help = new Global_Helpers;
		$this->auto_config = new War_Auto_Config( $this->war_config );
		$this->get_current_user();
		$this->add_filters();
		$this->add_actions();
		add_filter( 'request', [ $this, 'handle_missing_requests' ] ); // Important for the AngularJS aspect of the WAR Framework
		// register_activation_hook( __FILE__, [ $this, 'auto_setup' ] );
	}

	private function get_current_user(){
		$war_user = new War_User;
		$this->current_user = $war_user->get_user();
	}

	private function add_filters(){
		if( $this->war_config->api_prefix != $this->help->get_old_rest_api_prefix() ){
			add_filter( 'rest_url_prefix', [ $this->auto_config, 'set_api_prefix' ] );
			add_action( 'init', [ $this->help, 'rewrite_flush' ] );
		}

		add_filter( 'request', [ $this, 'handle_missing_requests' ] );
	}

	private function add_actions(){
		add_action( 'init', [ $this->auto_config, 'manage_admin_toolbar' ] ); // Run config_admin_toolbar
		add_action( 'init', [ $this->auto_config, 'set_user_roles' ] ); // Run config_set_user_roles
		add_action( 'rest_api_init', [ $this, 'register_endpoints' ] );
		add_action( 'rest_api_init', [ $this, 'register_models' ] );
		add_action( 'wp_enqueue_scripts', [ $this->auto_config, 'war_localize' ] ); // Localize the warObject
	}

	public function register_endpoints(){
		if( empty( $this->war_endpoints ) ) return;
		array_walk( $this->war_endpoints, function( $end, $slug ){
			if( empty( $end ) ) return;
			$e = new War_Endpoint( $end, $this->war_config, $this->current_user );
			$e->register();
		});
	}

	public function register_models(){
		if( empty( $this->war_models ) ) return;
		array_walk( $this->war_models, function( $model ){
			if( empty( $model ) ) return;
			$m = new War_Model( $model, $this->war_config, $this->current_user );
			$m->register();
		});
	}

	private function create_namespace(){
		$this->war_config->namespace = $this->war_config->api_name . '/v' . $this->war_config->version;
	}

	/**
	 * Auto Setup upon Plugin Activation.
	 *
	 * This method is called when the WAR API Plugin is activated
	 *
	 * @since 0.1.0
 	 * @access public
	 **/
	public function auto_setup(){

	}

	/**
	 * Handle Missing Requests 'request' Filter Function
	 *
	 * When the site is loaded from a specific URL, WordPress tries to handle that request.
	 * Because of the routing in AngularJS, the URL isn't always going to be registered with WordPress.
	 * This function prevents arbitrary 404's from being thrown when AngularJS knows how to handle the request, but WordPress does not.
	 *
	 * @since 0.1.0
	 * @access public
	 *
	 * @link https://codex.wordpress.org/Plugin_API/Filter_Reference/request
	 *
	 * @param Array $request Request Array provided by the Request Filter.
	 * @return Array If Error is a 404 return empty array, else return original $request.
	 **/
	public function handle_missing_requests( $request ){
        if( isset( $request[ 'error' ] ) && intval( $request[ 'error' ] ) == 404 ) return [];
        return $request;
	}

}
