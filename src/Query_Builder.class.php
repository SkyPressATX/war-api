<?php

namespace War_Api\Data;

use War_Api\Data\Query_Search as Query_Search;
use War_Api\Helpers\Global_Helpers as Global_Helpers;
use War_Api\Security\War_User as War_User;

class Query_Builder {

	private $help;
	private $current_user;

	public function __construct( $current_user = [] ){
		$this->current_user = $current_user;
		if( empty( $this->current_user ) ) $this->get_current_user();
		$this->help = new Global_Helpers;
	}

	/**
	 * $table should already be properly prefixed
	 **/
	public function create_table_query( $table, $params ){
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

		return 'CREATE TABLE IF NOT EXISTS ' . esc_sql( $table ) . ' (' . implode( ', ', $values ) . ')';
	}

	public function add_col_query( $table, $col ){
		$q = 'ALTER TABLE ' . esc_sql( $table );

		foreach( $col as $k => $c ){
			$x[] = ' ADD `' . esc_sql( $k ) . '` ' . $this->help->sql_data_type( $c[ 'type' ] );
		}

		$q .= implode( ',', $x );
		return $q;
	}

	public function drop_col_query( $table, $col ){
		return 'ALTER TABLE ' . esc_sql( $table ) . ' DROP ' . implode( ', DROP ', $col );
	}

	public function insert_data( $model_params, $requested_params ){
		$model_params = (array)$model_params;
		$requested_params = (array)$requested_params;

		// Strip invalid requested_params
		foreach( $requested_params as $key => $val ){
			if( ! isset( $model_params[ $key ] ) ) unset( $requested_params[ $key ] );
		}

		$requested_params[ 'user' ] = $this->current_user->id;

		return $requested_params;
	}

	public function read_items_query( $table = false, $params = array() ){
		if( !$table ) throw new \Exception( 'Missing Table' );
		$q = 'SELECT * FROM ' . esc_sql( $table );

		$search = new Query_Search( $params );
		$query_search = $search->get_query_search();

		if( isset( $query_search->filters ) ) $q .= ' WHERE ' . implode( ' AND ', $query_search->filters );
		if( isset( $query_search->order ) )   $q .= ' ORDER BY ' . implode( ', ', $query_search->order );
		if( isset( $query_search->limit ) )   $q .= ' LIMIT ' . $query_search->limit;
		if( isset( $query_search->offset ) )  $q .= ' OFFSET ' . $query_search->offset;

		return $q;
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

	// /**
    //  * dao_default_add_values
    //  *
    //  * @return Array
    //  */
    // private function set_default_params(){
    //     return array(
    //         'user' => (int)get_current_user_id()
    //     );
    // }

	private function get_current_user(){
		$wu = new War_User;
		$this->current_user = $wu->get_user();
	}

}
