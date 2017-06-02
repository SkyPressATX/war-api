<?php

namespace War_Api\Data;

use War_Api\Data\Query_Search as Query_Search;
use War_Api\Helpers\Global_Helpers as Global_Helpers;

class Query_Builder {

	private $help;

	public function __construct(){
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
			if( is_string( $val ) ) $val = [ 'type' => $val ];
			$val = (object)$val;
			$type = array( '`' . $param . '`' ); //Start building an array we can implode later
			if( isset( $val->type ) ) $type[] = $this->help->sql_data_type( $val->type );
			if( isset( $val->unique ) && $val->unique ) $primary_keys[] = $param; // If this is required, set it as a primary key
			// if( isset( $val->type ) && $val->type === 'assoc'){
			// 	$foreign_keys[] = 'INDEX (`' . $arg . '`)';
			// }
			if( in_array( $param, [ 'id', 'created_on', 'updated_on', 'user' ] ) ) continue; // Lets not duplicate things
			$values[] = implode( ' ', $type ); //Add the type array as a string to the Values array
		}
		foreach( $foreign_keys as $fk ){
			$values[] = $fk;
		}

		if( ! empty( $primary_keys ) ) $values[] = 'PRIMARY KEY(' . implode( ',', $primary_keys ) . ')';
		$values[] = 'KEY (`id`)';

		return 'CREATE TABLE IF NOT EXISTS '. $table . ' (' . implode( ', ', $values ) . ')';
	}

	public function add_col_query( $table, $col ){
		$q = 'ALTER TABLE ' . $table;

		foreach( $col as $k => $c ){
			$x[] = ' ADD `' . $k . '` ' . $this->help->sql_data_type( $c[ 'type' ] );
		}

		$q .= implode( ',', $x );
		return $q;
	}

	public function drop_col_query( $table, $col ){
		return esc_sql( 'ALTER TABLE ' . $table . ' DROP ' . implode( ', DROP ', $col ) );
	}

	public function insert_data( $model_params, $requested_params ){
		$model_params = (array)$model_params;
		$requested_params = (array)$requested_params;

		// Strip invalid requested_params
		foreach( $requested_params as $key => $val ){
			if( ! isset( $model_params[ $key ] ) ) unset( $requested_params[ $key ] );
		}

		$new_params = $this->set_default_params();
		$new_params = array_merge( $requested_params, $new_params );
		$new_params = $this->help->numberfy( $new_params );
		return $new_params;

	}

	public function read_items_query( $table = false, $params = array() ){
		if( !$table ) throw new \Exception( 'Missing Table' );
		$q = 'SELECT * FROM ' . $table;

		$search = new Query_Search( $params );
		$query_search = $search->get_query_search();

		if( isset( $query_search->filters ) ) $q .= ' WHERE ' . implode( ' AND ', $query_search->filters );
		if( isset( $query_search->order ) )   $q .= ' ORDER BY ' . implode( ', ', $query_search->order );
		if( isset( $query_search->limit ) )   $q .= ' LIMIT ' . $query_search->limit;
		if( isset( $query_search->offset ) )  $q .= ' OFFSET ' . $query_search->offset;

		echo "$q\n\n";

		return $q;
	}

	public function read_item_query( $table = false, $id = false ){
		if( !$table ) throw new \Exception( 'Missing Table' );
		if( !$id ) throw new \Exception( 'Missing Item ID' );
		return 'SELECT * FROM ' . $table . ' WHERE `id` = ' . $this->help->quote_it( $id );
	}

	public function update_item_query( $table = false, $params = false ){
		if( ! $table ) throw new \Exception( 'Missing Table' );
		if( ! $params ) throw new \Exception( 'Missing Update Params' );

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

	/**
     * dao_default_add_values
     *
     * @return Array
     */
    private function set_default_params(){
        return array(
            'user' => get_current_user_id()
        );
    }


}
