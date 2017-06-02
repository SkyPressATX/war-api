<?php

namespace War_Api\Data;

use War_Api\Data\Query_Builder as Query;
use War_Api\Security\Role_Check as Role_Check;
use War_Api\Helpers\Global_Helpers as Global_Helpers;

class DAO {

	private $db;
	private $model;
	private $params;
	private $qb;

	public function __construct( $wpdb = array(), $model = array(), $params = array() ){
		if( empty( $wpdb ) ) global $wpdb;

		$this->db = $wpdb;
		$this->model = $model;
		$this->params = $params;
		$this->qb = new Query;
	}

	public function read_items( $table = false, $params = false ){
		/** First check if the table exists, create it if not **/
		$table_check = $this->create_table();
		if( is_wp_error( $table_check ) || ! $table_check ) throw new Exception( 'Error Creating Table: ' . $this->db->prefix . $this->model->name );

		$help = new Global_Helpers;
		if(! $table ) $table = $this->db->prefix . $this->model->name;
		if( ! $params ) $params = $this->params;
		$read_query = $this->qb->read_items_query( $table, $params );
		$call = $this->db->get_results( $read_query, OBJECT );
		if( is_wp_error( $call ) ) throw new \Exception( $call->get_error_message() );
		return $help->numberfy( $call );
	}

	public function create_item(){
		/** First check if the table exists, create it if not **/
		$table_check = $this->create_table();
		if( is_wp_error( $table_check ) || ! $table_check ) throw new Exception( 'Error Creating Table: ' . $this->db->prefix . $this->model->name );

		return $this->insert_item();
	}

	public function read_item( $table = false, $id = false, $assoc_check = true ){
		$help = new Global_Helpers;
		if(! $table ) $table = $this->db->prefix . $this->model->name;
		if( ! $id ) $id = $this->params->id;
		$query = $this->qb->read_item_query( $table, $id );
		$item = $this->db->get_row( $query, OBJECT );
		if( $assoc_check && isset( $this->model->assoc ) ){
			array_walk( $this->model->assoc, function( $assoc, $model ) use( $item ){
				$assoc = (object)$assoc;

				if( ! isset( $assoc->assoc ) || ! isset( $assoc->bind ) ) return;
				$bind = $assoc->bind;
				if( ! isset( $item->$bind ) ) return;

				if( $assoc->assoc === 'many' ) $item->$model = $this->read_items( $this->db->prefix . $model, array( $this->model->name => $item->$bind ) );
				if( $assoc->assoc === 'one' ) $item->$model = $this->read_item( $this->db->prefix . $model, $item->$model, false );

			});
		}
		return (object)$help->numberfy( $item );
	}

	public function update_item(){
		$table = $this->db->prefix . $this->model->name;
		$id = absint( trim( $this->params->id ) );
		unset( $this->params->id );
		$this->check_table_columns( $table );
		return $this->db->update( $this->db->prefix . $this->model->name, (array)$this->params, [ 'id' => $id ] );
	}

	public function delete_item(){
		if( ! isset( $this->params->id ) ) throw new \Exceptions( 'No ID Provided' );
		return $this->db->delete( $this->db->prefix . $this->model->name, [ 'id' => absint( trim( $this->params->id ) ) ] );
	}

	private function create_table(){
		$create_table_query = $this->qb->create_table_query( $this->db->prefix . $this->model->name, $this->model->params );
		return $this->db->query( esc_sql( $create_table_query ) );
	}

	private function insert_item(){
		$insert_data = $this->qb->insert_data( $this->model->params, $this->params );
		return $this->db->insert( $this->db->prefix . $this->model->name, $insert_data );
	}

	private function check_table_columns( $table ){
		$default_col = [ 'id', 'created_on', 'updated_on', 'user' ];
		$model_col = array_merge( $default_col, array_keys( (array)$this->model->params ) );
		$t_q = 'SELECT `COLUMN_NAME` FROM INFORMATION_SCHEMA.COLUMNS WHERE `TABLE_NAME` = "' . $table . '"';
		$table_col = $this->db->get_col( $t_q );
		$remove = array_values( array_diff( $table_col, $model_col ) );
		$add = array_values( array_diff( $model_col, $table_col ) );

		if( ! empty( $remove ) ) $remove_query = $this->qb->drop_col_query( $table, $remove );
		if( ! empty( $add ) ){
			foreach( $add as $c ){
				if( ! isset( $this->model->params[ $c ] ) ) continue;
				if( ! is_array( $this->model->params[ $c ] ) ) $this->model->params[ $c ] = [ 'type' => $this->model->params[ $c ] ];
				$add_col[ $c ] = $this->model->params[ $c ];
			}

			if( isset( $add_col ) ) $add_query = $this->qb->add_col_query( $table, $add_col );
		}

		if( isset( $remove_query ) ) $r_call = $this->db->query( esc_sql( $remove_query ) );
		if( isset( $add_query ) ) $a_call = $this->db->query( esc_sql( $add_query ) );
	}

}
