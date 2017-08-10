<?php

namespace War_Api\Data;

use War_Api\Data\Query_Builder as Query_Builder;
use War_Api\Helpers\Global_Helpers as Global_Helpers;

/**
* Example Query Map
* $war_db_map = [
*	 'query' => [
*		 'select' => [],
*		 'table'   => 'table',
*		 'join'   => [
*			 [
*				 'query' => [],
*				 'on'    => '',
*				 'as'    => ''
*			 ]
*		 ],
*		 'where'  => [],
*        'group'  => [],
*		 'order'  => [],
*		 'limit'  => 10,
*		 'offset' => 0,
*		 'query'  => NULL //To Be filled in By War_DB
*	 ],
*	 'assoc' => [
*		 [
*			 'map'   => [
*				 'assoc' => '',
*				 'bind'  => '',
*				 'match' => '',
*				 'table' => ''
*			 ],
*			 'query' => []
*		 ]
*	 ]
* ];
**/

class War_DB {

	private $db;
	private $query_builder;
	private $help;
	private $safe_table = [];

	private static $instance = null;

	private function __construct( $db = false ){
		// Set passed $db connection or default to WP DB connection
		if( ! $db )
			$this->db = $this->get_wp_db();
		else
			$this->db = $db;
		// Check the $db connection, throw an error if bad
		$this->check_db();
		// Setup other War Classes
		$this->query_builder = new Query_Builder;
		$this->help = new Global_Helpers;
	}

	//Initialize the War_DB Class
	public static function init( $db = false ){
		if( self::$instance === null ) self::$instance = new War_DB( $db );
		return self::$instance;
	}

	//Create a new instance of War_DB, even if one exists
	public function new_db( $db = false ){
		$war_db = new War_DB();
		return $war_db->init( $db );
	}

	// Check if a table already exists
	public function table_check( $table = false ){
		if( ! $table ) return $table;
		if( in_array( $table, $this->safe_table ) ) return true;

		$query = 'SHOW TABLES LIKE "' . $table . '"';
		$check = $this->db_call( $query )->fetch_row()[0];
		if( $check !== NULL ) $this->safe_table[] = $table;

		if( $check === NULL ) throw new \Exception( "Table $table doesn't exist" );
		return true;
	}

	public function create_table( $query_map = array() ){
		if( empty( $query_map ) ) throw new \Exception( 'Missing a Proper Query Map' );
		return true;
	}

	public function select_one( $query_map = array() ){
		if( empty( $query_map ) ) throw new \Exception( 'Missing a Proper Query Map' );
		$query_map = (object)$query_map;
		$query = $this->query_builder->select( $query_map );
		return $this->db_call( $query )->fetch_row()[0];
	}

	public function select_row( $query_map = array() ){
		if( empty( $query_map ) ) throw new \Exception( 'Missing a Proper Query Map' );
		$query_map = (object)$query_map;
		//Check My Table
		$this->table_check( $query_map->table );
		//Build the Query String
		$query = $this->query_builder->select( $query_map );
		//Return One Row from the results
		return $this->db_call( $query )->fetch_row();
	}

	public function select_all( $query_map = array() ){
		if( empty( $query_map ) ) throw new \Exception( 'Missing a Proper Query Map' );
		$query_map = (object)$query_map;
		//Check My Table
		$this->table_check( $query_map->table );
		//Build me a Select Query!
		$query = $this->query_builder->select( $query_map );
		//Call initial results
		$db_call = $this->db_call( $query );
		//Return all the results
		$result = $db_call->fetch_all( MYSQLI_ASSOC );
		$db_call->free();
		return $result;
	}

	public function insert_row( $query_map = array() ){
		if( empty( $query_map ) ) throw new \Exception( 'Missing a Proper Query Map' );
		$query_map = (object)$query_map;
		//Check My Table
		$this->table_check( $query_map->table );
		return true;
	}

	public function insert_all( $query_map = array() ){
		if( empty( $query_map ) ) throw new \Exception( 'Missing a Proper Query Map' );
		return true;
	}

	public function update_row( $query_map = array() ){
		if( empty( $query_map ) ) throw new \Exception( 'Missing a Proper Query Map' );
		return true;
	}

	public function update_all( $query_map = array() ){
		if( empty( $query_map ) ) throw new \Exception( 'Missing a Proper Query Map' );
		return true;
	}

	public function delete_row( $query_map = array() ){
		if( empty( $query_map ) ) throw new \Exception( 'Missing a Proper Query Map' );
		return true;
	}

	public function delete_all( $query_map = array() ){
		if( empty( $query_map ) ) throw new \Exception( 'Missing a Proper Query Map' );
		return true;
	}

	//Simple Call to mysql. The Query should already be safe to run by now
	// Also, no need to process the results, just send it back (unless it errored )
	private function db_call( $query = false ){
		if( ! $query ) throw new \Exception( 'No Query Generated' );
		// echo "$query\n\n";
		$result = $this->db->query( $query );
		if( ! $result ) throw new \Exception( $this->db->error );
		return $result;
	}

	//Get creds from wp_config.php, return default connection to wp db
	private function get_wp_db(){
		$db_user     = ( DB_USER !== NULL )     ? DB_USER     : NULL;
		$db_password = ( DB_PASSWORD !== NULL ) ? DB_PASSWORD : NULL;
		$db_host 	 = ( DB_HOST !== NULL )     ? DB_HOST     : NULL;
		$db_name     = ( DB_NAME !== NULL )     ? DB_NAME     : NULL;

		return new \mysqli( $db_host, $db_user, $db_password, $db_name );
	}

	//Check if we have a proper mysqli connection
	private function check_db(){
		if( ! property_exists( $this->db, 'host_info' ) ) throw new \Exception( 'Improper mysqli Connection' );
		if( $this->db->connect_errno ){
			mysqli_close( $this->db );
			throw new \Exception( 'Failed to connect to Mysql: (' . $this->db->connect_errno . ') ' . $this->db->connect_error );
		}
		return true;
	}

} // END War_DB Class
