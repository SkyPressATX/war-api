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
	private $db_info;
	private $query_builder;
	private $help;
	private $safe_table = [];

	private static $instance = null;

	private function __construct( $db_info = false ){
		// Set passed $db connection or default to WP DB connection
		$this->db_info = ( ! $db_info || empty( $db_info ) || ! is_array( $db_info ) ) ? $this->get_wp_db_info() : $db_info;
		// Create MySQL Connection
		$this->db = $this->connect_to_mysql();

		// Setup other War Classes
		$this->query_builder = new Query_Builder;
		$this->help = new Global_Helpers;
	}

	//Initialize the War_DB Class
	public static function init( $db_info = false ){
		if( self::$instance === null ) self::$instance = new War_DB( $db_info );
		return self::$instance;
	}

	//Create a new instance of War_DB, even if one exists
	public function new_db( $db_info = false ){
		return new War_DB( $db_info );
	}

	// Check if a table already exists
	public function table_check( $table = false, $throw = true ){
		if( ! $table ) return $table;
		if( is_array( $table ) ) $table = array_values( $table )[0];
		if( in_array( $table, $this->safe_table ) ) return true;

		$query = 'SHOW TABLES LIKE "' . $table . '"';
		$check = $this->db_call( $query )->fetch_row()[0];
		if( $check === NULL && $throw ) throw new \Exception( get_class() . ': Table ' . $table . ' doesn\'t exist from Table Check Method' );
		if( $check === NULL && ! $throw ) return false;

		$this->safe_table[] = $table;
		return true;
	}

	public function select_one( $query_map = array() ){
		if( empty( $query_map ) ) throw new \Exception( get_class() . ': Missing a Proper Query Map for Select One Method' );
		$query_map = (object)$query_map;
		$query = $this->query_builder->select( $query_map );
		return $this->db_call( $query )->fetch_row()[0];
	}

	public function select_row( $query_map = array() ){
		if( empty( $query_map ) ) throw new \Exception( get_class() . ': Missing a Proper Query Map for Select Row Method' );
		$query_map = (object)$query_map;
		//Check My Table
		$this->table_check( $query_map->table );
		//Build the Query String
		$query = $this->query_builder->select( $query_map );
		//Return One Row from the results
		return $this->db_call( $query )->fetch_row();
	}

	public function select_all( $query_map = array(), $table_check = true ){
		if( empty( $query_map ) ) throw new \Exception( get_class() . ': Missing a Proper Query Map for Select All Method' );
		$query_map = (object)$query_map;
		//Check My Table
		if( $table_check ) $this->table_check( $query_map->table );
		//Build me a Select Query!
		$query = $this->query_builder->select( $query_map );
		//Call initial results
		$db_call = $this->db_call( $query );
		//Return all the results
		$result = $db_call->fetch_all( MYSQLI_ASSOC );
		$db_call->free();
		return $this->help->numberfy( $result );
		return $result;
	}

	public function select_query( $query_map = array() ){
		if( empty( $query_map ) ) throw new \Exception( get_class() . ': Missing a Proper Query Map for Select Query Method' );
		$query_map = (object)$query_map;
		$query = $this->query_builder->select( $query_map );
		return $this->db_call( $query )->fetch_all( MYSQLI_ASSOC );
	}

	public function insert( $query_map = array() ){
		if( empty( $query_map ) ) throw new \Exception( get_class() . ': Missing a Proper Query Map for Insert Method' );
		$query_map = (object)$query_map;
		//Check My Table
		$this->table_check( $query_map->table );
		if( ! property_exists( $query_map, 'update' ) || ! is_bool( $query_map->update ) ) $query_map->update = false;
		// Get the right query
		if( property_exists( $query_map, 'data' ) )  $query = $this->query_builder->insert_from_data( $query_map );
		if( property_exists( $query_map, 'query' ) ) $query = $this->query_builder->insert_from_query( $query_map );

		$result = $this->db_call( $query );
		if( ! $result ) return $result;
		return $this->db->affected_rows;
	}

	public function update( $query_map = array() ){
		if( empty( $query_map ) ) throw new \Exception( get_class() . ': Missing a Proper Query Map for Update Method' );
		$query_map = (object)$query_map;
		//Check My Table
		$this->table_check( $query_map->table );

		if( property_exists( $query_map, 'data' ) ) $query  = $this->query_builder->update_from_data( $query_map );
		if( property_exists( $query_map, 'query' ) ) $query = $this->query_builder->update_from_query( $query_map );

		$result = $this->db_call( $query );
		if( ! $result ) return $result;
		return $this->db->affected_rows;
	}

	public function delete( $query_map = array() ){
		if( empty( $query_map ) ) throw new \Exception( get_class() . ': Missing a Proper Query Map for Delete Method' );
		$query_map = (object)$query_map;
		//Check My Table
		$this->table_check( $query_map->table );

		$query = $this->query_builder->delete( $query_map );

		$result = $this->db_call( $query );
		if( ! $result ) return $result;
		return $this->db->affected_rows;
	}

	public function alter_table( $query_map = array() ){
		if( empty( $query_map ) ) throw new \Exception( get_class() . ': Missing a Proper Query Map for Alter Table Method' );
		$query_map = (object)$query_map;
		//Check My Table
		$this->table_check( $query_map->table );

		$query = $this->query_builder->alter( $query_map );

		$result = $this->db_call( $query );
		if( ! $result ) return $result;
		return $this->db->affected_rows;
	}

	public function create_table( $query_map = array() ){
		if( empty( $query_map ) ) throw new \Exception( get_class() . ': Missing a Proper Query Map for Create Table Method' );
		$query_map = (object)$query_map;
		//Check My Table
		$check = $this->table_check( $query_map->table, false ); // Don't throw an exception
		if( $check ) return $check;

		$query = $this->query_builder->create_table( $query_map );

		$result = $this->db_call( $query );
		if( ! $result ) return $result;
		return $this->db->affected_rows;
	}

	public function query( $query_string = false ){
		if( ! $query_string || ! is_string( $query_string ) ) throw new \Exception( get_class() . ': Missing Query String for Query Method' );
		return $this->db_call( $query_string );
	}

	//Simple Call to mysql. The Query should already be safe to run by now
	// Also, no need to process the results, just send it back (unless it errored )
	private function db_call( $query = false ){
		if( ! $query ) throw new \Exception( get_class() . ': No Query Provided for DB Call Method' );
		if( ! is_string( $query ) ) throw new \Exception( get_class() . ': Query Provided is Not A String for DB Call Method' );
		// echo "$query\n\n";
		$result = $this->db->query( $query );
		if( ! $result ) throw new \Exception( get_class() . ': ' . $this->db->error . ' QUERY: ' . $query );
		return $result;
	}

	//Get creds from wp_config.php, return default connection to wp db
	private function get_wp_db_info(){
		$db_user     = ( DB_USER !== NULL )     ? DB_USER     : NULL;
		$db_password = ( DB_PASSWORD !== NULL ) ? DB_PASSWORD : NULL;
		$db_host 	 = ( DB_HOST !== NULL )     ? DB_HOST     : NULL;
		$db_name     = ( DB_NAME !== NULL )     ? DB_NAME     : NULL;
		//Use WordPress $table_prefix as a last resort
		global $table_prefix;

		return [
			'db_user'      => $db_user,
			'db_password'  => $db_password,
			'db_name' 	   => $db_name,
			'db_host' 	   => $db_host,
			'table_prefix' => $table_prefix
		];
	}

	private function connect_to_mysql(){
		if( ! property_exists( $this, 'db_info' ) ) throw new \Exception( get_class() . ': No DB Information Provided for Connect To MySQL Method' );

		$db_info = (object)$this->db_info;
		if( ! property_exists( $db_info, 'db_port' ) ) $db_info->db_port = 33306; //Default mysql port

		$db = mysqli_init();
		$db->options( MYSQLI_OPT_INT_AND_FLOAT_NATIVE, 1 );
		if( property_exists( $db_info, 'ssl' ) && is_array( $db_info->ssl ) )
			$db->ssl_set( $db_info->ssl[0], $db_info->ssl[1], $db_info->ssl[2], $db_info->ssl[3], $db_info->ssl[4] );

		$db->real_connect(
			$db_info->db_host,
			$db_info->db_user,
			$db_info->db_password,
			$db_info->db_name,
			$db_info->db_port
		);

		if( $db->connect_errno ){
			mysqli_close( $db );
			throw new \Exception( get_class() . ': DB Call Method Failed to connect to Mysql: (' . $db->connect_errno . ') ' . $db->connect_error );
		}
		return $db;
	}

} // END War_DB Class
