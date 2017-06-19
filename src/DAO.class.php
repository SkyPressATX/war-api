<?php

namespace War_Api\Data;

use War_Api\Data\Query_Builder as Query;
use War_Api\Security\Role_Check as Role_Check;
use War_Api\Helpers\Global_Helpers as Global_Helpers;
use War_Api\Data\Data_Assoc as Data_Assoc;

class DAO {

	private $db;
	private $request;
	private $model;
	private $params;
	private $qb;
	private $war_config;
	private $table;

	public function __construct( $wpdb = array(), $model = array(), $request = array(), $war_config = array() ){
		if( empty( $wpdb ) ) global $wpdb;

		$this->db = $wpdb;
		$this->request = $request;
		$this->model = $model;
		$this->params = $this->request->params;
		$this->war_config = $war_config;
		$this->isolate = $this->determine_isolation();
		$this->qb = new Query( $this->isolate );
		$this->table = $this->get_table_name();
	}

	public function read_items(){
		/** First check if the table exists, create it if not **/
		$table_check = $this->create_table();
		if( is_wp_error( $table_check ) || ! $table_check ) throw new Exception( 'Error Creating Table: ' . $this->table );

		$this->add_current_user_to_filter();

		$help = new Global_Helpers;
		$read_query = $this->qb->read_items_query( $this->table, $this->params );
		$call = $this->db->get_results( $read_query );
		if( is_wp_error( $call ) ) throw new \Exception( $call->get_error_message() );
		if( isset( $this->model->assoc ) && ( $this->params->sideLoad || isset( $this->params->sideSearch ) ) ){
			array_walk( $call, function( &$item ){
				$params = (object)[];
				$params->filter = ( $this->params->sideSearch ) ? $this->params->sideSearch : [];
				$da = new Data_Assoc( $this->war_config, $this->model->assoc, $params, $this->model );
				$item = $da->get_assoc_data( $item );
			});
		}
		return $help->numberfy( $call );
	}

	public function create_item(){
		/** First check if the table exists, create it if not **/
		$table_check = $this->create_table();
		if( is_wp_error( $table_check ) || ! $table_check ) throw new Exception( 'Error Creating Table: ' . $this->table );

		return $this->insert_item();
	}

	// public function read_item( $table = false, $find = false, $search = 'id' ){
	public function read_item(){
		$help = new Global_Helpers;
		// if(! $table ) $table = $this->db->prefix . $this->model->name;
		// if( ! $find ) $find = $this->params->id;
		$query = $this->qb->read_item_query( $this->table, $this->params->id, 'id' );
		$item = $this->db->get_row( $query );
		if( isset( $this->model->assoc ) && ( $this->params->sideLoad || isset( $this->params->sideSearch ) ) ){
			$params = (object)[];
			$params->filter = ( $this->params->sideSearch ) ? $this->params->sideSearch : [];
			$da = new Data_Assoc( $this->war_config, $this->model->assoc, $params, $this->model );
			$item = $da->get_assoc_data( $item );
		}
		return (object)$help->numberfy( $item );
	}

	public function update_item(){
		$id = absint( trim( $this->params->id ) );
		unset( $this->params->id );
		$this->check_table_columns();
		$this->unset_empty_values();
		return $this->db->update( $this->table, $this->params, [ 'id' => $id ] );
	}

	public function delete_item(){
		if( ! isset( $this->params->id ) ) throw new \Exceptions( 'No ID Provided' );
		return $this->db->delete( $this->table, [ 'id' => absint( trim( $this->params->id ) ) ] );
	}

	private function create_table(){
		$create_table_query = $this->qb->create_table_query( $this->table, $this->model->params );
		return $this->db->query( $create_table_query );
	}

	private function insert_item(){
		$this->unset_empty_values();
		$insert_data = $this->qb->insert_data( $this->model->params, $this->params );
		return $this->db->insert( $this->table, $insert_data );
	}

	private function check_table_columns(){
		$default_col = [ 'id', 'created_on', 'updated_on', 'user' ];
		$model_col = array_merge( $default_col, array_keys( (array)$this->model->params ) );
		$t_q = 'SELECT `COLUMN_NAME` FROM INFORMATION_SCHEMA.COLUMNS WHERE `TABLE_NAME` = "' . esc_sql( $this->table ) . '"';
		$table_col = $this->db->get_col( $t_q );
		$remove = array_values( array_diff( $table_col, $model_col ) );
		$add = array_values( array_diff( $model_col, $table_col ) );

		if( ! empty( $remove ) ) $remove_query = $this->qb->drop_col_query( $this->table, $remove );
		if( ! empty( $add ) ){
			foreach( $add as $c ){
				if( ! isset( $this->model->params[ $c ] ) ) continue;
				if( ! is_array( $this->model->params[ $c ] ) ) $this->model->params[ $c ] = [ 'type' => $this->model->params[ $c ] ];
				$add_col[ $c ] = $this->model->params[ $c ];
			}

			if( isset( $add_col ) ) $add_query = $this->qb->add_col_query( $this->table, $add_col );
		}

		if( isset( $remove_query ) ) $r_call = $this->db->query( $remove_query );
		if( isset( $add_query ) ) $a_call = $this->db->query( $add_query );
	}

	private function unset_empty_values(){
		$params = (array)$this->params;
		array_walk( $params, function( &$p, $k ){
			if( is_bool( $p ) && $p === false ) $p = (int)0;
			if( is_string( $p ) && empty( $p ) ) unset( $this->params->$k );
		});
		$this->params = $params;
	}

	private function determine_isolation(){
		$isolate = $this->war_config->isolate_user_data;
		if( isset( $this->model->isolate_user_data ) ) $isolate = $this->model->isolate_user_data;
		return $isolate;
	}

	private function add_current_user_to_filter(){
		if( ! $this->isolate ) return;
		if( ! isset( $this->request->current_user ) ) return;

		if( empty( $this->params ) ) $this->params = (object)[ 'filter' => [] ];
		$this->params->filter[] =  'user:eq:' . $this->request->current_user->id;
	}

	private function get_table_name(){
		$table = $this->db->prefix;
		$table .= ( isset( $this->model->table_prefix ) ) ? $this->model->table_prefix : $this->war_config->api_name . '_';
		$table .= $this->model->name;
		return $table;
	}
}
