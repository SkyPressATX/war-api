<?php

namespace War_Api\Data;

use War_Api\Data\Query_Search as Query_Search;
use War_Api\Helpers\Global_Helpers as Global_Helpers;
use War_Api\Data\Query_Assoc as Query_Assoc;
use War_Api\Data\Query_Builder as Query_Builder;
use War_Api\Data\War_DB as War_DB;

class DAO {

	/**
	 * Start with Other Classes
	 **/
	private $query_search;
	private $query_builder;
	private $query_assoc;
	private $help;
	private $db = false;

	private $request;
	private $model;
	private $params;
	private $war_config;
	private $table_prefix;
	private $query_map;

	public function __construct( $db_info = array(), $model = array(), $request = array(), $war_config = array() ){
		try {
			if( ! empty( $db_info ) ) $this->db = $this->mysqli_connection( $db_info );
			$this->war_db = War_DB::init( $this->db );
		}catch( \Exception $e ){
			throw new \Exception( $e->getMessage() );
		}

		$this->model = (object)$model;
		$this->request = $request;
		$this->params = $this->request->params;
		$this->war_config = $war_config;
		$this->isolate = $this->determine_isolation();
		$this->table_prefix = $this->get_table_prefix( $db_info );

		$this->query_search = new Query_Search;
		$this->help = new Global_Helpers;
		$this->query_assoc = new Query_Assoc( $this->model->assoc, $this->params, $this->table_prefix );

	}

	/**
	 * Read All Items from DB
	 *
	 * Get Items, Associated Items, and Info Results
	 **/
	public function read_all(){
		try {
			$this->add_current_user_to_filter();
			$table = $this->table_prefix . $this->model->name;
			$this->query_map = [];

			if( property_exists( $this->model, 'assoc' ) && ! empty( $this->model->assoc ) )
				$this->query_map[ 'assoc' ] = $this->query_assoc->get_query_maps();

			//Build war_db select_all() params
			$this->query_map[ 'query' ] = [
				'select' => ( property_exists( $this->params, 'select' ) ) ? $this->params->select : [],
				'table'   => $table,
				'where'  => $this->query_search->parse_filters( $this->params->filter, $table ),
				'limit'  => $this->query_search->parse_limit( $this->params->limit ),
				'order'  => $this->query_search->parse_order( $this->params->order, $table ),
				'offset' => $this->query_search->parse_page( $this->params->page, $this->params->limit )
			];

			// Lets look for any assoc queries that have a where statement. Pull that data first
			foreach( $this->query_map[ 'assoc' ] as $model => &$assoc ){
				if( ! empty( $assoc[ 'query' ][ 'where' ] ) ){
					$assoc_where = $this->query_assoc->get_side_search_filter( $assoc, $model, $table );
					if( $assoc_where ){
						if( ! empty( $assoc_where[ 'data' ] ) ) $assoc[ 'data' ] = $assoc_where[ 'data' ];
						$this->query_map[ 'query' ][ 'where' ][] = $assoc_where[ 'filter' ];

					}
				}
			}

			$total = $this->query_map[ 'query' ];
			$total[ 'select' ] = 'COUNT(' . $table . '.id)';
			unset( $total[ 'limit' ] );
			unset( $total[ 'offset' ] );

			// print_r( $this->query_map );
			$data = $this->war_db->select_all( $this->query_map[ 'query' ] );

			//Append our Associated Data
			if( property_exists( $this->params, 'sideSearch' ) || $this->params->sideLoad !== false && ! empty( $this->query_map[ 'assoc' ] ) )
				$data = $this->query_assoc->append_assoc_data( $this->query_map[ 'assoc' ], $data );

			if( ! $this->params->_info ) return $data;

			$result = [
				'data' => $data,
				'_info' => [
					'total' => $this->war_db->select_one( $total )
				],
				'_pages' => [
					'current_page' => ( property_exists( $this->params, 'page' ) ) ? $this->params->page : NULL
				]
			];

			if( $result[ '_info' ][ 'total' ] > 0 ){
				$result[ '_info' ][ 'count' ]  = ( $result[ '_info' ][ 'total' ]  < $this->params->limit ) ? $result[ '_info' ][ 'total' ]  : $this->params->limit;
				$result[ '_pages' ][ 'total_pages' ]  = ( (integer)ceil( $result[ '_info' ][ 'total' ] / $this->params->limit ) );
				$result[ '_pages' ][ 'next_page' ] = ( ($this->params->page + 1) <= $result[ '_pages' ][ 'total_pages' ]  ) ? ($this->params->page + 1) : $result[ '_pages' ][ 'total_pages' ];
			}

			return $result;

		} catch( \Exception $e ){
			throw new \Exception( $e->getMessage() );
		}

	} // END Read Items Method

	public function read_one(){
		try {
			$this->params->_info = false;
			$this->params->filter = [ 'id:eq:' . $this->params->id ];
			$this->params->limit = 1;
			$this->params->page = 1;
			unset( $this->params->id );

			$item = $this->read_all();

			return $item[0];
		} catch( \Exception $e ){
			throw new \Exception( $e->getMessage() );
		}
	}

	public function insert_one(){
		try {
			$this->unset_empty_values();
			$insert_map = [
				'table' => $this->table_prefix . $this->model->name
			];
			return true;
			// $insert_query = $this->qb->insert_data( $this->model->params, $this->params );
			// return $this->db->insert( $this->table, $insert_data );
		} catch( \Exception $e ){
			throw new \Exception( $e->getMessage() );
		}
	}

	public function update_one(){
		try {
			return true;
		} catch( \Exception $e ){
			throw new \Exception( $e->getMessage() );
		}
	}

	public function delete_one(){
		try {
			return true;
		} catch( \Exception $e ){
			throw new \Exception( $e->getMessage() );
		}
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
		if( property_exists( $this->model, 'isolate_user_data' ) ) $isolate = $this->model->isolate_user_data;
		return $isolate;
	}

	private function add_current_user_to_filter(){
		if( ! $this->isolate ) return;

		if( empty( $this->params ) ) $this->params = [ 'filter' => [] ];
		$this->params->filter[] =  'user:eq:' . $this->request->current_user->id;
	}

	private function get_table_prefix( $db_info = array() ){
		//Use $db_info[ 'table_prefix' ] if set
		if( ! empty( $db_info ) && isset( $db_info[ 'table_prefix' ] ) ) return $db_info[ 'table_prefix' ];
		//Use $this->war_config->table_prefix if set
		if( property_exists( $this->war_config, 'table_prefix' ) ) return $this->war_config->table_prefix;
		//Use WordPress $table_prefix as a last resort
		global $table_prefix;
		return $table_prefix;
	}

	// Connect to mysql using provided credentials
	private function mysqli_connection( $db_info = array() ){
		$db_info = (object)$db_info;
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

		return $db;

	}
}
