<?php

namespace War_Api\Data;

class Query_Select {

	private $select_array;
	private $select;
	private $table;
	private $query = array();
	private $sql_actions = array( 'sum', 'count', 'avg' );

	public function parse_select( $select_array = array(), $table = NULL ){
		if( empty( $select_array ) ) return $select_array;
		$this->select_array = $select_array;
		$this->table = $table;
		// $this->query = array( array(), $this->table => array() );

		array_walk( $this->select_array, function( $select ){
			$select = $this->build_select_query( $select );

			if( is_string( $select ) ){
				$this->query[ $this->table ][] = $select;
				return;
			}

			if( is_array( $select ) ) $this->query[] = $select;
			return;
			// if( is_array( $select ) )  $this->query[0] = array_merge( $select, $this->query[0] );
		});


		$this->query = array_filter( $this->query, function( $x ){ return ( ! empty( $x ) ); });
		return $this->query;
	}

	public function build_select_query( &$select ){
		$san_regex = '/[^a-z0-9_\.@]/';
		$select = explode( ':', strtolower( $select ) );
		array_walk( $select, function( &$x ){ $x = (string) esc_sql( trim( $x ) ); });

		if( sizeof( $select ) <= 1 ) return $select[0];

		if( ! in_array( $select[0], $this->sql_actions ) ) throw new \Exception( 'Provided SQL Method does not exists' );

		$q = strtoupper( $select[0] ) . '( ' . $select[1] . ' )';
		if( isset( $select[2] ) ) $q .= ' AS "' . $select[2] . '"';
		return [ $q ];

	}

}
