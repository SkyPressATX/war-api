<?php

namespace War_Api\Data;

use War_Api\Helpers\Global_Helpers as Global_Helpers;

class Query_Search {

	private $params;
	private $help;
	private $search;

	public function __construct( $params = array() ){
		$this->params = $params;
		$this->help = new Global_Helpers;
		$this->search = (object)[];
	}

	public function get_query_search(){
		if( isset( $this->params->filter ) ) $this->parse_filters();
		// if( isset( $this->params->group ) ) $this->parse_group();
		if( isset( $this->params->order ) ) $this->parse_order();
		if( isset( $this->params->limit ) ) $this->parse_limit();
		if( isset( $this->params->page ) ) $this->parse_page();
		return $this->search;
	}

	/**
	 * $filter syntax col:comp:val
	 **/
	private function parse_filters(){
		array_walk( $this->params->filter, function( $filter ){
			$this->search->filters[] = $this->build_filter_query( trim( $filter ) );
		});
	}

	/**
	 * $group Array( $string, $string, $string )
	 **/
	private function parse_group(){
		// sanitize $group values
		array_walk( $this->params->group, function( $group ){
			$this->search->group[] = '`' . (string)preg_replace( '/[^a-z0-9_]/', '', trim( $group ) ) . '`';
		});
	}

	/**
	 * $order syntax col:direction
	 **/
	private function parse_order(){
		array_walk( $this->params->order, function( $order ){
			$this->search->order[] = $this->build_order_query( trim( $order ) );
		});
	}

	/**
	 * $limit Integer Default 10
	 **/
	private function parse_limit(){
		$this->search->limit = absint( trim( $this->params->limit ) );
	}

	/**
	 * $page Integer
	 * $offset Integer ( $page * $limit )
	 **/
	private function parse_page(){
		$this->search->offset = absint( ( ( trim( $this->params->page ) - 1 ) * trim( $this->params->limit  ) ) );
	}

	private function build_filter_query( $filter ){
		$f = explode( ':', strtolower( $filter ) );
		array_walk( $f, function( &$x ){ $x = (string)( trim( $x ); });
		switch( trim( $f[1] ) ){
			case 'like':
				return '`' . $f[0] . '` LIKE("%' . preg_replace( '/[^a-z0-9]/', '', $f[2] ) . '%")';
				break;
			case 'nlike':
				return '`' . $f[0] . '` NOT LIKE("%' . preg_replace( '/[^a-z0-9]/', '', $f[2] ) . '%")';
				break;
			case 'gt':
				return '`' . $f[0] . '` > ' . $this->help->quote_it( $f[2] );
				break;
			case 'lt':
				return '`' . $f[0] . '` < ' . $this->help->quote_it( $f[2] );
				break;
			case 'gte':
				return '`' . $f[0] . '` >= ' . $this->help->quote_it( $f[2] );
				break;
			case 'lte':
				return '`' . $f[0] . '` <= ' . $this->help->quote_it( $f[2] );
				break;
			case 'eq':
				return '`' . $f[0] . '` = ' . $this->help->quote_it( $f[2] );
				break;
			case 'ne':
				return '`' . $f[0] . '` != ' . $this->help->quote_it( $f[2] );

		}
	}

	private function build_order_query( $order ){
		$o = explode( ':', strtolower( $order ) );
		$order_by = trim( $o[0] );
		if( sizeof( $o ) > 1 ){
			$d = strtoupper( trim( $o[1] ) );
			if( in_array( $d, [ 'ASC', 'DESC' ] ) ) $direction = $d;
		}
		return ( isset( $direction ) ) ? $order_by . ' ' . $direction : $order_by;
	}

}
