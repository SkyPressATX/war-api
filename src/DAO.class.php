<?php

namespace War_Api\Data;

class DAO {

	private $db;

	public function __construct( $db = array() ){
		if( empty( $db ) ){
			global $wpdb;
			$this->db = $wpdb;
		}
		$this->db = $wpdb;
	}
}
