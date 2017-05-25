<?php

class war_dao_create extends war_data_store implements war_dao_interface {

	public $query;
	public $filters;

	public function __construct( $store ){
		parent::__construct();

	}
	public function get_query(){
		return $this->build_query();
	}

	public function build_query(){

	}

	/**
     * dao_model_exisits
     *
     * @param $model
     * @return Bool
     */
    private function dao_model_exisits( $model ){
        $query = 'SHOW TABLES LIKE "' . $this->prefix.$model . '"';
        $table = $this->dao_wpdb_custom_query( $query )[0];
        return ( $table != 0 );
    }

	/**
	 * dao_create_table
	 *
	 * @param $name
	 * @param $args
	 * @return Query Success | Query Fail
	 */
	private function dao_create_table( $name, $args ){
		$primary_keys = array( '`user`' );
		$foreign_keys = array();
		$values = $this->dao_default_create_values();
		foreach( $args as $arg => $val ){
			$val = (object) $val;
			$type = array( '`' . $arg . '`' ); //Start building an array we can implode later
			if( isset( $val->type ) ) $type[] = $this->dao_data_type( $val->type );
			if( isset( $val->unique ) && $val->unique ) $primary_keys[] = $arg; // If this is required, set it as a primary key
			if( isset( $val->type ) && $val->type === 'assoc'){
				$foreign_keys[] = 'INDEX (`' . $arg . '`)';
			}
			$values[] = implode( ' ', $type ); //Add the type array as a string to the Values array
		}
		foreach( $foreign_keys as $fk ){
			$values[] = $fk;
		}

		$values[] = 'PRIMARY KEY(' . implode( ',', $primary_keys ) . ')';
		$values[] = 'KEY (`id`)';

		$query = 'CREATE TABLE IF NOT EXISTS '. $this->prefix . $name . ' (' . implode( ', ', $values ) . ')';
	}

	/**
     * dao_default_create_values
     *
     * @return Array
     */
    private function dao_default_create_values(){
        return array(
            '`id` MEDIUMINT NOT NULL AUTO_INCREMENT',
            '`created_on` DATETIME NOT NULL',
            '`updated_on` DATETIME NOT NULL',
            '`user` MEDIUMINT NOT NULL'
        );
    }

	/**
     * dao_data_type
     *
     * @param $type
     * @return String
     */
    private function dao_data_type( $type = 'string' ){
        switch ( $type ){
            case 'string':
                return 'VARCHAR(50)';
                break;
            case 'text':
                return 'LONGTEXT';
                break;
            case 'integer':
                return 'BIGINT';
                break;
            case 'model':
                return 'MEDIUMINT';
                break;
            case 'assoc':
                return 'MEDIUMINT';
                break;
            case 'date':
                return 'DATETIME';
                break;
        }
    }
}
