<?php

namespace War_Api\Data;

use War_Api\Data\Query_Search as Query_Search;

class Query_Builder {

	private $query_search;
	private $query;

	public function __construct(){
		$this->query_search = new Query_Search;
	}

	public function select( $query_map = array() ){
		if( empty( $query_map ) ) throw new \Exception( 'Improper Query Map Provided' );
		$query_map = (object)$query_map;
		$this->query = 'SELECT ';

		if( property_exists( $query_map, 'select' ) ){
			if( empty( $query_map->select ) ) $query_map->select = $query_map->table . '.*';
			if( is_array( $query_map->select ) ){
				array_walk( $query_map->select, function( &$select, $i, $table ){
					$select = $table . '.' . $select;
				}, $query_map->table );
			}
		}

		$this->query .= ( is_array( $query_map->select ) ) ? implode( ', ', $query_map->select ) : $query_map->select;
		$this->query .= ' FROM ' . $query_map->table;

		if( property_exists( $query_map, 'where' ) && ! empty( $query_map->where ) )
			$this->query .= ' WHERE '  . implode( ' AND ', $query_map->where );
		if( property_exists( $query_map, 'group' ) ) $this->query .= ' GROUP BY ' . $query_map->group;
		if( property_exists( $query_map, 'limit' ) )  $this->query .= ' LIMIT '  . $query_map->limit;
		if( property_exists( $query_map, 'offset' ) ) $this->query .= ' OFFSET ' . $query_map->offset;

		return $this->query;
	}

	/**
	 * $table should already be properly prefixed
	 **/
	public function create_table_query( $model = false, $params = array() ){
		if( ! $model ) throw new \Exception( 'Missing Model Name' );
		$primary_keys = array();
		$foreign_keys = array();
		$values = $this->default_table_columns();
		foreach( $params as $param => $val ){
			if( is_string( $val ) ) $val = [ 'type' => esc_sql( $val ) ];
			$val = (object)$val;
			$type = array( '`' . esc_sql( $param ) . '`' ); //Start building an array we can implode later
			if( isset( $val->type ) ) $type[] = $this->help->sql_data_type( $val->type );
			if( isset( $val->unique ) && $val->unique ) $primary_keys[] = esc_sql( $param ); // If this is required, set it as a primary key
			if( in_array( $param, [ 'id', 'created_on', 'updated_on', 'user' ] ) ) continue; // Lets not duplicate things
			$values[] = implode( ' ', $type ); //Add the type array as a string to the Values array
		}

		if( ! empty( $primary_keys ) ) $values[] = 'PRIMARY KEY(' . implode( ',', $primary_keys ) . ')';
		$values[] = 'KEY (`id`)';

		return 'CREATE TABLE IF NOT EXISTS ' . esc_sql( $this->prefix . $model ) . ' (' . implode( ', ', $values ) . ')';
	}

	public function add_col_query( $model = false, $col ){
		if( ! $model ) throw new \Exception( 'Missing Model Name' );
		$q = 'ALTER TABLE ' . esc_sql( $this->prefix . $model );

		foreach( $col as $k => $c ){
			$x[] = ' ADD `' . esc_sql( $k ) . '` ' . $this->help->sql_data_type( $c[ 'type' ] );
		}

		$q .= implode( ',', $x );
		return $q;
	}

	public function drop_col_query( $model = false, $col ){
		if( ! $model ) throw new \Exception( 'Missing Model Name' );
		return 'ALTER TABLE ' . esc_sql( $this->prefix . $model ) . ' DROP ' . implode( ', DROP ', $col );
	}

	// public function insert_ignore( $query_map = array() ){
	// 	if( empty( $query_map ) ) throw new \Exception( 'Improper Query Map Provided' );
	// 	$query_map = (object)$query_map;
	//
	// 	$query = 'INSERT IGNORE';
	// }

	public function insert_data( $model_params, $requested_params ){
		$model_params = (array)$model_params;
		$requested_params = (array)$requested_params;

		// Strip invalid requested_params
		foreach( $requested_params as $key => $val ){
			if( ! isset( $model_params[ $key ] ) ) unset( $requested_params[ $key ] );
			if( is_array( $val ) ) $val = implode( ',', $val );
		}

		$requested_params[ 'user' ] = $this->current_user->id;

		return $requested_params;
	}

	/**
	 * dao_default_create_values
	 *
	 * @return Array
	 */
	private function default_table_columns(){
		return array(
			'`id` MEDIUMINT NOT NULL AUTO_INCREMENT',
			'`created_on` datetime DEFAULT CURRENT_TIMESTAMP',
			'`updated_on` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
			'`user` MEDIUMINT NOT NULL'
		);
	}

} // END Query_Builder Class
