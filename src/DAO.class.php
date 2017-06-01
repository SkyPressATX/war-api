<?php

namespace War_Api\Data;

use War_Api\Data\Query_Builder as Query;
use War_Api\Security\Role_Check as Role_Check;

class DAO {

	private $db;
	private $model;
	private $params;
	private $qb;

	public function __construct( $wpdb = array(), $model = array(), $params = array() ){
		if( empty( $db ) ) global $wpdb;

		$this->db = $wpdb;
		$this->model = $model;
		$this->params = $params;
		$this->qb = new Query;
	}

	public function read_items(){
		$read_query = $this->qb->read_items_query( $this->db->prefix . $this->model->name, $this->params );
		$call = $this->db->get_results( $read_query, OBJECT );
		if( is_wp_error( $call ) ) throw new Exception( 'read-items', $call->get_error_message(), [ 'status' => $e->get_status() ] );
		return $call;
	}

	public function create_item(){
		/** First check if the table exists, create it if not **/
		$table_check = $this->create_table();
		if( is_wp_error( $table_check ) || ! $table_check ) throw new Exception( 'create-item', 'Error Creating Table: ' . $this->db->prefix . $this->model->name );

		return $this->insert_item();
	}

	public function read_item(){
		$query = $this->qb->read_item_query( $this->db->prefix . $this->model->name, $this->params->id );
		return $this->db->get_row( $query, OBJECT );
	}

	public function update_item(){

	}

	public function delete_item(){

	}

	private function create_table(){
		$create_table_query = $this->qb->create_table_query( $this->db->prefix . $this->model->name, $this->model->params );
		return $this->db->query( $create_table_query );
	}

	private function insert_item(){
		$insert_data = $this->qb->insert_data( $this->model->params, $this->params );
		return $this->db->insert( $this->db->prefix . $this->model->name, $insert_data );
	}

}
