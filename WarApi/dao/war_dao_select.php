<?php

class war_dao_select extends war_data_store implements war_dao_interface {

	public $query;
	public $filters;

	public function get_query(){
		return $this->build_query();
	}

	public function build_query(){
		$this->query[] = 'SELECT';
		$this->parse_requested_items();
		$this->parse_requested_table();
		$this->parse_requested_filters();
		$this->parse_requested_group();
		$this->parse_requested_order();
		$this->parse_requested_limit();
		return $this->implode_query();
	}

	private function parse_requested_items(){
		if(! isset( $this->store->select ) ) $this->store->select = [ '*' ];
		if( is_object( $this->store->select ) ) $this->store->select = (array)$this->store->select;
		if( ! is_array( $this->store->select ) ) $this->store->select = [ $this->store->select ];
		$this->query[] = implode(', ', $this->store->select );
	}
	private function parse_requested_table(){
		$this->query[] = 'FROM ' . $this->store->table . ' AS "' . $this->store->table . '"';
	}
	private function parse_requested_filters(){
		if( ! isset( $this->store->filters ) ) return;
		if( is_object( $this->store->filters ) ) $this->store->filters = (array)$this->store->filters;
		array_walk( $this->store->filters, function( $v, $k ){
			$this->filters[] = $k . ' = ' . $v;
		});
		$this->query[] = 'WHERE ' . implode( ' AND ', $this->filters );
	}
	private function parse_requested_group(){
		if( ! isset( $this->store->group_by ) ) return;
		if( is_object( $this->store->group_by ) ) $this->store->group_by = (array)$this->store->group_by;
		if( ! is_array( $this->store->group_by ) ) $this->store->group_by = [ $this->store->group_by ];
		$this->query[] = 'GROUP BY ' . implode( ', ', $this->store->group_by );
	}
	private function parse_requested_order(){
		if( ! isset( $this->store->order_by ) ) return;
		if( is_object( $this->store->order_by ) ) $this->store->order_by = (array)$this->store->order_by;
		if( ! is_array( $this->store->order_by ) ) $this->store->order_by = [ $this->store->order_by ];
		$this->query[] = 'ORDER BY ' . implode( ', ', $this->store->order_by );
	}
	private function parse_requested_limit(){
		if( ! isset( $this->store->limit ) ) $this->store->limit = 10;
		if( $this->store->limit === false || ! is_numeric( $this->store->limit ) ) return;
		$this->query[] = 'LIMIT ' . $this->store->limit;
	}
	private function implode_query(){
		return implode( ' ', $this->query );
	}
}
