<?php

namespace War_Api\Data;

use War_Api\Data\Query_Search as Query_Search;
use War_Api\Data\Query_Builder as Query_Builder;
use War_Api\Data\War_DB as War_DB;
use War_Api\Helpers\Global_Helpers as Global_Helpers;

class Query_Assoc {

	private $query_search;
	private $query_builder;
	private $assoc_maps;
	private $params;
	private $base_table;
	private $table_prefix;
	private $side_search;
	private $war_db;
	private $help;

	public function __construct( $assoc_maps = array(), $params = array(), $table_prefix = '' ){
		$this->query_search  = new Query_Search;
		$this->query_builder = new Query_Builder;
		$this->help          = new Global_Helpers;

		$this->assoc_maps   = $assoc_maps;
		$this->params       = $params;
		$this->table_prefix = $table_prefix;
	}

	public function get_query_maps(){
		if( empty( $this->assoc_maps ) ) return array(); //Dont throw an error, just return a blank array
		//Set up some private variables

		if( property_exists( $this->params, 'sideSearch' ) )
			$this->side_search = $this->query_search->parse_filters_with_model( $this->params->sideSearch, $this->table_prefix );

		//Lets shorten this list if we can
		if( property_exists( $this->params, 'sideLoad' ) && ! is_bool( $this->params->sideLoad ) ){
			$side = $this->params->sideLoad;
			$this->assoc_maps = [ $side => $this->assoc_maps[ $side ] ];
		}

		array_walk( $this->assoc_maps, function( &$assoc, $model ){
			$assoc[ 'table' ] = $this->table_prefix . $model;
			$assoc = [
				'map'   => $assoc,
				'query' => $this->build_query_map( $model, $assoc )
			];
		});

		return $this->assoc_maps;
	} // END get_side_search_filters Method

	public function get_side_search_filter( $assoc = array(), $model = false, $table = false ){
		if( empty( $assoc ) || ! isset( $assoc[ 'query' ] ) || ! isset( $assoc[ 'map' ] ) || ! $table || ! $model ) return false;

		if( ! isset( $this->war_db ) ) $this->war_db = War_DB::init();
		//Remove limit from this version of the query
		if( isset( $assoc[ 'query' ][ 'limit' ] ) ) unset( $assoc[ 'query' ][ 'limit' ] );

		$result = [
			'data'   => [],
			'filter' => null
		];

		//If $assoc type is one or many
		if( $assoc[ 'map' ][ 'assoc' ] == 'one' || $assoc[ 'map' ][ 'assoc' ] == 'many' ){
			$assoc[ 'query' ][ 'select' ] = [ $assoc[ 'map' ][ 'match' ] ];
			$result[ 'filter' ] = $table . '.' . $assoc[ 'map' ][ 'bind' ] . ' IN ( ' . $this->query_builder->select( $assoc[ 'query' ] ) . ' )';
		}

		//If $assoc type is mm
		if( $assoc[ 'map' ][ 'assoc' ] == 'mm' ){
			//Get the list of items to match
			$result[ 'data' ] = $this->war_db->select_all( $assoc[ 'query' ], false );
			//Build a new where statement for the main query
			// $where_list = $this->help->flatten_cs_list( array_column( $result[ 'data' ], $assoc[ 'map' ][ 'match' ] ) );
			$this->where_list = [];
			array_walk( array_column( $result[ 'data' ], $assoc[ 'map' ][ 'match' ] ), function( $x ){
				$x = explode( ',', $x );
				foreach( $x as $y ){
					$this->where_list[] = ( is_numeric( $y ) ) ? (float)$y : (string)$y;
				}
			});
			$result[ 'filter' ] = $table . '.' . $assoc[ 'map' ][ 'bind' ] . ' IN (' . implode( ',', array_filter( $this->where_list ) ) . ')';
		}

		return $result;
	}

	public function append_assoc_data( $assoc_map = array(), $data = array() ){
		if( empty( $assoc_map ) || empty( $data ) ) return $data;
		if( ! isset( $this->war_db ) ) $this->war_db = War_DB::init();
		$this->data = $data;

		array_walk( $assoc_map, function( $assoc, $model ){
			$assoc = (object)$assoc;
			if( ! property_exists( $assoc, 'query' ) ) return;
				$assoc->query = (object)$assoc->query;
				$assoc->map   = (object)$assoc->map;

			if( ! property_exists( $assoc, 'data' ) ){
				//Make sure we have a "where" property
				if( ! property_exists( $assoc->query, 'where' ) ) $assoc->query->where = array();
				// Determine what dat from teh $data array we should be using
				$match = array_column( $this->data, $assoc->map->bind );
				if( empty( $match ) ) return; // Leave if nothing is found

				if( $assoc->map->assoc == 'mm' ){
					$assoc->query->where[] = 'CONCAT( ",", ' . $model . '.' . $assoc->map->match . ', "," ) REGEXP ",(' . implode( '|', $match ) . '),"';
				}else{
					array_walk( $match, function( &$i){
						$i = $this->help->quote_it( $i );
					});
					$assoc->query->where[] = $model  . '.' . $assoc->map->match . ' IN (' . implode( ',', $match ) . ')';
				}

				// print_r( $assoc->query );
				$assoc->data = $this->war_db->select_all( $assoc->query, false );
				// print_r( $assoc->data );
			}

			if( empty( $assoc->data ) ) return;

			foreach( $this->data as &$d ){
				$this->map = $assoc->map;
				$this->search = $d[ $this->map->bind ];
				if( empty( $this->search ) ) continue;

				$assoc_items = array_filter( $assoc->data, function( $i ){
					if( $this->map->assoc == 'mm' ) return ( in_array( $this->search, explode( ',', $i[ $this->map->match ] ) ) );
					return ( $i[ $this->map->match ] === $this->search );
				});
				if( empty( $assoc_items ) ) return;

				if( $assoc->map->assoc == 'one' )
					$d[ $assoc->map->bind ] = array_values( $assoc_items )[0];
				else
					$d[ $model ] = array_values( $assoc_items );
			}

		});

		return $this->data;
	}


	private function build_query_map( $model = false, $assoc = array() ){
		if( empty( $assoc ) || ! $model ) return array();
		$table = ( isset( $assoc[ 'prefix' ] ) ) ? $assoc[ 'prefix' ] . $model : $this->table_prefix . $model;
		if( isset( $assoc[ 'db_name' ] ) ) $table = $assoc[ 'db_name' ] . '.' . $table;
		$query = [
			'select' => [],
			'table'   => [ $model => $table ],
			'where'  => ( isset( $this->side_search[ $model ] ) ) ? $this->side_search[ $model ] : []
		];

		if( property_exists( $this->params, 'sideLimit' ) && $this->params->sideLimit )  $query['limit']  = $this->query_search->parse_limit( $this->params->sideLimit );
		if( property_exists( $this->params, 'sideOrder' ) )  $query['order']  = $this->query_search->parse_order( $this->params->sideOrder, $model );

		return $query;
	} // END build_query_map Method


} // END Data Assoc Class
