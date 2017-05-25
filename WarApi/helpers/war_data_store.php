<?php

if( ! defined( 'WPINC' ) ) wp_die();

class war_data_store {

	private $store;

	public function __construct(){
		$this->store = (object)[];
	}

	public function __set( $name, $value ){
		$this->store->$name = $value;
	}

	public function __get( $name ){
		if( array_key_exists( $name, $this->store ) ) return $this->store->$name;
		return null;
	}

	public function __isset( $name ){
		return isset( $this->store->$name );
	}

	public function __unset( $name ){
		unset( $this->store->$name );
	}

	public function get_data(){
		return $this->store;
	}

}
