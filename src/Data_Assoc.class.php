<?php

namespace War_Api\Data;

class Data_Assoc {

	private $model;
	private $assoc_map;
	private $war_config;
	private $params;
	private $item;

	public function __construct( $war_config = array(), $assoc_map = array(), $params = array(), $model = array() ){
		$this->war_config = $war_config;
		$this->assoc_map = $assoc_map;
		$this->params = $params;
		$this->model = $model;
	}

	public function get_assoc_data( $item = array() ){
		if( empty( $item ) || sizeof( $item ) <= 0 ) return $item;
		if( empty( $this->assoc_map ) || sizeof( $this->assoc_map ) <= 0 ) return $item;

		$this->item = $item;

		array_walk( $this->assoc_map, function( $assoc, $model ){
			$assoc = (object)$assoc;

			if( ! isset( $assoc->assoc ) || ! isset( $assoc->bind ) ) return; //Improper assoc model
			$bind = $assoc->bind;

			if( $assoc->assoc === 'many' ) $this->get_many_items( $assoc, $bind, $model );
			if( $assoc->assoc === 'one' ) $this->get_one_item( $assoc, $bind, $model );

			return;
		});

		return $this->item;
	}

	private function get_many_items( $assoc = array(), $bind = 'id', $model = false ){
		if( ! $model ) return;

		//Check if sideSearch has been turned into a filter
		if( ! empty( $this->params && isset( $this->params->filter ) ) ){
			$this->params->filter = (array)$this->params->filter;
			array_walk( $this->params->filter, function( &$f ) use( $model ){
				$f_array = explode( ':', $f );
				if( sizeof( $f_array ) === 3 ){
					$f = implode( ':', $f_array );
					return;
				}
				if( sizeof( $f_array ) === 4 && $f_array[0] === $model ){
					unset( $f_array[0] );
					$f = implode( ':', $f_array );
					return;
				}
				if( sizeof( $f_array ) !== 3 || sizeof( $f_array ) !== 4 || $f_array[0] !== $model ) $f = false;
			});

			$this->params->filter = array_filter( $this->params->filter, function( $v ){ return ( $v ); } );

		}

		if( empty( $this->params ) || sizeof( $this->params ) <= 0 ) $this->params = (object)[ 'filter' => [] ];

		$this->params->filter[] = $this->model->name . ':eq:' . $this->item->$bind;
		$this->item->$model = $this->local_call( $model, $this->params );
	}

	private function get_one_item( $assoc = array(), $bind = 'id', $model = false ){
		if( ! $model ) return;
		if( ! isset( $this->item->$model ) || empty( $this->item->$model ) ) return;
		$this->params = (object)[ 'filter' => [] ];

		$this->params->filter[] = $bind . ':eq:' . $this->item->$model;
	 	$result = $this->local_call( $model, $this->params );
		$this->item->$model = $result[0];
	}

	private function local_call( $end = false, $params = array() ){
		if( ! $end ) return;
		$full_end = '/' . $this->war_config->namespace . '/' . $end;
		$req = new \WP_REST_Request( 'GET', $full_end );
		foreach( $params as $k => $v ){
			if( ! empty( $v ) ) $req->set_param( $k, $v );
		}
		$req->set_param( 'sideLoad', false );

		$result = rest_do_request( $req );
		return $result->get_data();
	}
} // END Class
