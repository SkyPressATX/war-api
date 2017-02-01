<?php

// require_once "war_response.php";

class war_dao {

    public $prefix;
    public $wpdb;

    /**
     * Construct function
     */
    public function __construct( &$wpdb, &$prefix ){
        $this->wpdb = $wpdb;
        $this->prefix = $prefix;
    }

    public function dao_get_tables(){
        $q = 'SHOW TABLES';
        $result = $this->dao_wpdb_custom_results( $q, 'OBJECT_K' );
        return array_keys( $result );
    }

    /**
     * dao_create_table
     *
     * @param $name
     * @param $args
     * @return Query Success | Query Fail
     */
    public function dao_create_table( $name, $args ){
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

        $created = $this->dao_wpdb_custom_query( $query );
        if( is_wp_error( $created ) ) return $created;
        return true;
    }

    /**
     * dao_wpdb_custom_query
     *
     * @param QUERY STRING $query
     * @return Query Success | Query Fail
     */
    public function dao_wpdb_custom_query( $q ){
        $result = $this->wpdb->query( $q );
        return $this->dao_wpdb_success_check( $result );
    }

    public function dao_wpdb_custom_results( $q, $type = 'OBJECT' ){
        $result = $this->wpdb->get_results( $q, $type );
        return $this->dao_wpdb_success_check( $result );
    }

    /**
     * dao_wpdb_prepare_query
     *
     * @param QUERY STRING $query
     * @param Attributes $atts
     * @return Query Success | Query Fail
     */
    public function dao_wpdb_prepare_query( $query, $atts ){
        $prep = $this->wpdb->prepare( $query, $atts );
        $result = $this->wpdb->query( $prep );
        return $this->dao_wpdb_success_check( $result );
    }

    /**
     * dao_wpdb_insert
     *
     * @param $model
     * @param $data
     * @return Query Success | Query Fail
     */
    public function dao_wpdb_insert( $model, $data ){
        $result = $this->dao_wpdb_success_check( $this->wpdb->insert( $this->prefix . $model, $data ) );
        if( is_wp_error( $result ) ) return $result;
        return $this->wpdb->insert_id;
    }

    /**
     * dao_wpdb_select_row
     *
     * @param $model string
     * @param $id integer
     * @param $assoc array
     * @return Query Success | WP_Error
     */
    public function dao_wpdb_select_row( $model = false, $data = false, $self = false ){

        if( ! $model || ! $data ) return $this->dao_wpdb_success_check( false );

        $q = 'SELECT * FROM ' . $this->prefix . $model . ' WHERE id =  ' . $data->params->id;

        if( is_int( $self ) ) $q .= ' AND user = ' . $self;

        return $this->dao_wpdb_success_check( $this->wpdb->get_row( $q, ARRAY_A ) );
    }

    /**
     * dao_wpdb_select_all
     *
     * @param $model
     * @param $order_by
     * @param $direction
     * @param $where object
     * @return Query Success | WP_Error
     */
    public function dao_wpdb_select_all( $model, $where = false, $self = false, $order_by = 'created_on', $direction = 'DESC' ){
        $query = 'SELECT * FROM '.$this->prefix.$model;

        if( $where ) $query .= ' WHERE '. $where->this .' = '. $where->equals;
        if( is_int( $self ) ) $q .= ' AND user = ' . $self;

        $query .= ' ORDER BY ' . $order_by . ' ' . $direction;

        $result = $this->wpdb->get_results( $query );
        return $this->dao_wpdb_success_check( $result );
    }

    /**
     * dao_wpdb_update_item
     *
     * @param $model
     * @param $id
     * @param $data
     * @return Query Success | WP_Error
     */
    public function dao_wpdb_update_item( $model = false, $id = false, $params = array() ){
        if( ! $model || ! $id ) return false;
        $where = [ 'id' => $params->id ];
        $result = $this->wpdb->update( $this->prefix . $model, $params, $where );
        return $this->dao_wpdb_success_check( $result );
    }

    /**
     * dao_wpdb_delete_item
     *
     * @param $model
     * @param $id
     * @return Query Success | WP_Error
     */
    public function dao_wpdb_delete_item( $model, $id ){
        $where = [ 'id' => $id ];
        $result = $this->wpdb->delete( $this->prefix.$model, $where );
        return $this->dao_wpdb_success_check( $result );
    }

    /**
     * dao_wpdb_success_check
     *
     * @param $result
     * @return Query Result | WP_Error
     */
    public function dao_wpdb_success_check( $result ){
        if( ! isset( $result ) || $result === false ){
            $err = new WP_Error( 500, __( $this->wpdb->last_error ) );
            return $err;
        }

        return $this->dao_numberfy( $result );
    }

    /**
     * dao_default_add_values
     *
     * @return Array
     */
    public function dao_default_add_values( $user_id  = 0 ){
        $date = date( 'Y-m-d H:i:s' );
        return array(
            'created_on' => $date,
            'updated_on' => $date,
            'user' => $user_id
        );
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
     * dao_mysql_prepare_type
     *
     * @param $att
     * @return String
     */
    private function dao_mysql_prepare_type( $att ){
        if( is_int( $att ) ) return '%d';
        if( is_string( $att ) ) return '%s';
        if( is_float( $att ) ) return '%f';
    }

    /**
     * dao_model_exisits
     *
     * @param $model
     * @return Bool
     */
    public function dao_model_exisits( $model ){
        $query = 'SHOW TABLES LIKE "' . $this->prefix.$model . '"';
        $table = $this->dao_wpdb_custom_query( $query )[0];
        return ( $table != 0 );
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

    /**
     * dao_numberfy
     *
     * @param $array
     * @return Array
     */
    public function dao_numberfy( $array ){
        $array = (array) $array;
        foreach( $array as &$row ){
            if( is_array( $row ) || is_object( $row ) ){
                $row = $this->dao_numberfy( $row );
            }else{
                if( is_numeric( $row ) ) $row = (int) $row;
            }
        }
        return $array;
    }

}
