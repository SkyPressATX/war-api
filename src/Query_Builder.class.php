<?php

namespace War_Api\Data;

use War_Api\Data\Query_Search as Query_Search;
use War_Api\Helpers\Global_Helpers as Global_Helpers;

class Query_Builder {

	private $query_search;
	private $query;
	private $help;
	private $data;

	public function __construct(){
		$this->query_search = new Query_Search;
		$this->help = new Global_Helpers;
	}

	public function select( $query_map = array() ){
		if( empty( $query_map ) ) throw new \Exception( get_class() . ': Improper Query Map Provided for Select Method' );
		$query_map = (object)$query_map;
		if( ! property_exists( $query_map, 'table' ) )
			throw new \Exception( get_class() . ': No Table Provided for Select Method' );

		$query = 'SELECT ';

		if( is_array( $query_map->table ) ){
			$table = array_values( $query_map->table )[0];
			$as = array_keys( $query_map->table )[0];
		}
		if( is_string( $query_map->table ) ){
			$as = $query_map->table;
			$table = $query_map->table;
		}

		if( property_exists( $query_map, 'select' ) ){
			if( empty( $query_map->select ) ) $query_map->select = $as . '.*';
			if( is_array( $query_map->select ) ){
				array_walk( $query_map->select, function( &$select, $i, $as ){
					if( is_array( $select ) ){
						array_walk( $select, function( &$s, $k, $as ){
							$s = ( is_string( $as ) ) ? $as . '.' . $s : $s;
						}, $i );
						$select = implode( ', ', $select );
					}else{
						$select = $as . '.' . $select;
					}
				}, $as );
			}
		}

		$query .= ( is_array( $query_map->select ) ) ? implode( ', ', $query_map->select ) : $query_map->select;
		$query .= ' FROM ' . $table . ' AS `' . $as . '`';

		if( property_exists( $query_map, 'join' ) ){
			array_walk( $query_map->join, function( &$join, $i ){
				$join = (object)$join;
				if( ! property_exists( $join, 'on' ) ) throw new \Exception( get_class() . ': No Join "On" provided in Select Method' );
				if( ! property_exists( $join, 'type' ) ) throw new \Exception( get_class() . ': No Join "Type" provided in Select Method' );
				if( property_exists( $join, 'query' ) ){
					if( ! property_exists( $join, 'as' ) ) throw new \Exception( get_class() . ': No "As" provided for Join Map in Select Method' );
					$j = '( ' . $this->select( $join->query ) . ' ) AS `' . $join->as . '`';
				}
				if( property_exists( $join, 'table' ) ){
					if( ! is_array( $join->table ) ) throw new \Exception( get_clasS() . ': Table provided in Join Map but is Not an Array in Select Method' );
					$j = array_values( $join->table )[0] . ' AS `' . array_keys( $join->table )[0] . '`';
				}

				if( ! isset( $j ) ) throw new \Exception( get_class() . ': No Query or Table provided to Join in Select Method' );
				if( is_string( $join->on ) ) $join->on = [ $join->on ];
				$join = strtoupper( $join->type ) . ' JOIN ' . $j . ' ON( ' . implode( ' AND ', $join->on ) . ' )';
			});
			$query .= ' ' . implode( ' ', $query_map->join );
		}

		if( property_exists( $query_map, 'where' ) && ! empty( $query_map->where ) )
			$query .= ' WHERE '  . implode( ' AND ', $query_map->where );

		if( property_exists( $query_map, 'group' ) )
			$query .= ( is_array( $query_map->group ) ) ? ' GROUP BY ' . implode( ', ', $query_map->group ) : ' GROUP BY ' . $query_map->group;

		if( property_exists( $query_map, 'order' ) )
			$query .= ( is_array( $query_map->order ) ) ? ' ORDER BY ' . implode( ', ', $query_map->order ) : ' ORDER BY ' . $query_map->order;

		if( property_exists( $query_map, 'limit' ) )  $query  .= ' LIMIT '    . $query_map->limit;
		if( property_exists( $query_map, 'offset' ) ) $query  .= ' OFFSET '   . $query_map->offset;

		return $query;
	}

	public function insert_from_data( $query_map = array() ){
		if( empty( $query_map ) ) throw new \Exception( get_class() . ': Improper Query Map Provided for Insert From Data Method' );
		$query_map = (object)$query_map;
		if( ! property_exists( $query_map, 'table' ) ) throw new \Exception( get_class() . ': No Table Provided for Insert From Data Method' );
		if( ! property_exists( $query_map, 'data' ) )  throw new \Exception( get_class() . ': No Data Provided for Insert From Data Method' );

		if( is_array( $query_map->table ) )
			$query_map->table = $table = array_values( $query_map->table )[0] . ' AS `' . array_keys( $query_map->table )[0] . '`';

		$this->query = ( $query_map->update ) ? 'INSERT INTO ' . $query_map->table : 'INSERT IGNORE INTO ' . $query_map->table;

		$this->data = [];
		$this->keys = [];
		if( $query_map->update ) $this->update = [];

		$query_map->data = (array)$query_map->data;
		array_walk( $query_map->data, function( &$data, $k ){
			//If this isn't an array of arrays (IE: multi-item inserts) then lets quote it and leave
			if( is_string( $data ) || is_numeric( $data ) ){
				$data = $this->help->quote_it( $data );
				return;
			}

			//If this IS an array of arrays, we've got to loop through it again
			$data = (array)$data;
			$this->keys = array_unique( array_merge( $this->keys, array_keys( $data ) ) );

			array_walk( $data, function( &$d, $k ){ $d = $this->help->quote_it( esc_sql( $d ) ); });
			$this->data[] = '(' . implode( ',', $data ) . ')';
		});

		if( empty( $this->keys ) || empty( $this->data ) ){
			$this->keys = array_keys( $query_map->data );
			$this->data = [ '(' . implode( ',', array_values( $query_map->data ) ) . ')' ];
		}

		// $this->keys = array_unique( $this->keys );
		array_walk( $this->keys, function( &$k ){ $k = '`' . $k .'`'; });

		$this->query .= ' (' . implode( ',', array_unique( $this->keys ) ) . ')';
		$this->query .= ' VALUES ' . implode( ',', $this->data );

		if( $query_map->update ){
			array_walk( $this->keys, function( $k ){ $this->update[] = $k . ' = VALUES(' . $k . ')'; });
			$this->query .= ' ON DUPLICATE KEY UPDATE ' . implode( ', ', $this->update );
		}

		return $this->query;
	}

	public function insert_from_query( $query_map = array() ){
		if( empty( $query_map ) ) throw new \Exception( get_class() . ': Improper Query Map Provided for Insert From Query Method' );
		$query_map = (object)$query_map;
		if( ! property_exists( $query_map, 'table' ) )   throw new \Exception( get_class() . ': No Table Provided for Insert From Query Method' );
		if( ! property_exists( $query_map, 'keys' ) ) throw new \Exception( get_class() . ': No Keys Array Provided for Insert From Query Method' );
		if( ! property_exists( $query_map, 'query' ) )   throw new \Exception( get_class() . ': No Select Query Map Provided for Insert From Query Method' );

		$query_map->query = $this->select( $query_map->query );

		array_walk( $query_map->keys, function( &$k ){ $c = '`' . $k . '`'; });

		if( is_array( $query_map->table ) ) $query_map->table = $table = array_values( $query_map->table )[0] . ' AS `' . array_keys( $query_map->table )[0] . '`';

		$this->query = ( $query_map->update ) ? 'INSERT INTO ' . $query_map->table : 'INSERT IGNORE INTO ' . $query_map->table;
		$this->query .= ' ( ' . implode( ', ', $query_map->keys ) . ' )';
		$this->query .= ' ' . $query_map->query;

		if( $query_map->update ){
			$this->update = [];
			array_walk( $query_map->keys, function( $k ){ $this->update[] = $k . ' = VALUES( ' . $k . ' )'; });
			$this->query .= ' ON DUPLICATE KEY UPDATE ' . implode( ', ', $this->update );
		}
		return $this->query;
	}

	public function update_from_data( $query_map = array() ){
		if( empty( $query_map ) ) throw new \Exception( get_class() . ': Improper Query Map Provided for Update From Data Method' );
		$query_map = (object)$query_map;
		if( ! property_exists( $query_map, 'table' ) ) throw new \Exception( get_class() . ': No Table Provided for Update From Data Method' );

		if( is_array( $query_map->table ) ) $query_map->table = $table = array_values( $query_map->table )[0] . ' AS `' . array_keys( $query_map->table )[0] . '`';

		$query_map->data = (array)$query_map->data;

		$query = 'UPDATE ' . $query_map->table;

		array_walk( $query_map->data, function( &$d, $key ){
            $modified_val = $this->help->quote_it( esc_sql( $d ) );
            if ( is_numeric( $modified_val ) ) {
                $d = '`' . $key . '` = ' . $modified_val;
            } else {
                $d = '`' . $key . '` = "' . $modified_val . '"';
            }

		});
		$query .= ' SET ' . implode( ', ', $query_map->data );

		if( property_exists( $query_map, 'where' ) )
			$query .= ' WHERE ' . implode( ' AND ', $query_map->where );

		return $query;
	}

	public function update_from_query( $query_map = array() ){
		// Example Update From Query Map
		// $map = [
		// 	'table'   => 'pet_table',
		// 	'set' => [ 'owner' => 'owner' ],
		// 	'on'    => [
		// 		[ 'owner' => 'owner' ]
		// 	],
		// 	'query'   => [
		// 		'select' => [ 'owner' ],
		// 		'table'  => 'owner_table',
		// 		'where'  => [ 'age >= 30' ]
		// 	],
		//  'where'   => [
		//  	'owner = owner'
		//	]
		// ];


		if( empty( $query_map ) ) throw new \Exception( get_class() . ': Improper Query Map Provided for Update From Query Method' );
		$query_map = (object)$query_map;
		if( ! property_exists( $query_map, 'table' ) ) throw new \Exception( get_class() . ': No Table Provided for Update From Query Method' );
		if( ! property_exists( $query_map, 'set' ) ) throw new \Exception( get_class() . ': No Set Array Provided for Update From Query Method' );
		if( ! property_exists( $query_map, 'query' ) )   throw new \Exception( get_class() . ': No Select Query Map Provided for Update From Query Method' );
		if( ! property_exists( $query_map, 'on' ) )   throw new \Exception( get_class() . ': No Join Map Provided for Update From Query Method' );

		$query_map->query = $this->select( $query_map->query );

		if( is_array( $query_map->table ) ) $query_map->table = $table = array_values( $query_map->table )[0] . ' AS `' . array_keys( $query_map->table )[0] . '`';

		$this->query = 'UPDATE ' . $query_map->table . ' AS a INNER JOIN( ' . $query_map->query .' ) AS b';

		array_walk( $query_map->on, function( &$b, $a ){ $b = 'a.' . $a . ' = b.' . $b; });
		array_walk( $query_map->set, function( &$b, $a ){ $b = 'a.' . $a . ' = b.' . $b; });

		$this->query .= ' ON( ' . implode( ', AND ', $query_map->on ) . ' )';
		$this->query .= ' SET ' . implode( ', ', $query_map->set );

		return $this->query;
	}

	public function delete( $query_map = array() ){
		if( empty( $query_map ) ) throw new \Exception( get_class() . ': Improper Query Map Provided for Delete Method' );
		$query_map = (object)$query_map;
		if( ! property_exists( $query_map, 'table' ) ) throw new \Exception( get_class() . ': No Table Provided for Delete Method' );


		$query  = 'DELETE FROM ' . $query_map->table;
		$query .= ' WHERE ' . implode( ' AND ', $query_map->where );

		return $query;
	}

	public function alter( $query_map = array() ){
		if( empty( $query_map ) ) throw new \Exception( get_class() . ': Improper Query Map Provided for Alter Method' );
		$query_map = (object)$query_map;

		$query = 'ALTER TABLE ' . $query_map->table . ' ' . implode( ', ', $query_map->data );
		return $query;
	}

	public function create_table( $query_map = array() ){
		if( empty( $query_map ) ) throw new \Exception( get_class() . ': Improper Query Map Provided for Create TAble Method' );
		$query_map = (object)$query_map;

		$query  = 'CREATE TABLE IF NOT EXISTS ' . $query_map->table;
		$query .= ' (' . implode( ', ', $query_map->data );
		if( property_exists( $query_map, 'primary' ) && ! empty( $query_map->primary ) )
			$query .= ', PRIMARY KEY(' . implode( ',', $query_map->primary ) . ')';
		if( property_exists( $query_map, 'keys' ) && ! empty( $query_map->keys ) )
			$query .= ', KEY(' . implode( ',', $query_map->keys ) . ')';
		$query .= ' )';

		return $query;
	}

} // END Query_Builder Class
