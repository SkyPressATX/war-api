<?php

namespace War_Api\Data;

class Query_Builder {

	private $model;
	private $params;

	public function __construct( $model = array(), $params = array() ){
		$this->model = $model;
		$this->params = $params;
	}

	public function get_all(){

	}

	public function get_item(){

	}

	public function create_item(){

	}

	public function update_item(){

	}

	public function delete_item(){

	}
	
}
