<?php

class war_dao_delete extends war_data_store implements war_dao_interface {

	public $query;

	public function get_query(){
		return $this->query;
	}

	public function build_query(){
		$this->query = $this->store;
	}

}
