<?php

require_once __DIR__ . "/war_dao.php";

class war_data {

    public $tables;
    public $dao;
    public $wpdb;
    public $prefix;

    public function __construct(){
        $this->create_db_connection();
        $this->dao = new war_dao( $this->wpdb, $this->prefix );
    }

    public function data_create_table( &$model = false, &$args = array() ){
        if(! $model ) return new WP_Error( 501, 'Data Model Not Found' );
        if( $this->dao->dao_model_exisits( $model ) ) return true;

        return $this->dao->dao_create_table( $model, $args );
    }

    /**
     * create_item
     *
     * @param $model
     * @param $data
     * @param $atts
     * @return Query Success | WP_Error | False
     */
    public function create_item( $model = null, $data = array(), $args = array() ){
        $check_table = $this->data_create_table( $model, $args );
        if( is_wp_error( $check_table ) ) return $check_table;

        $params = array();
        foreach( $data->params as $key => $val ){
            if( isset( $args[ $key ] ) ) $params[ $key ] = $val;
        }

        $new_params = $this->dao->dao_default_add_values( $data->current_user->id );
        $new_params = array_merge( $params, $new_params );
        $new_params = $this->dao->dao_numberfy( $new_params );

        $new_item = $this->dao->dao_wpdb_insert( $model, $new_params );
        if( is_wp_error( $new_item ) ) return $new_item;

        $result = $this->dao->dao_wpdb_select_row( $model, $new_item );
        return $result;
    }

    /**
     * data_model_get_one
     *
     * @param $model
     * @param $id
     * @return Query Success | WP_Error
     */
    public function data_model_get_one( $model = false, $data = false, $assoc = false ){
        if( $assoc && ! is_array( $assoc ) ) $assoc = [ $assoc ];

        $self = $this->isolate_self( $data );
        $item = $this->dao->dao_wpdb_select_row( $model, $data, $self );

        if( is_wp_error( $item ) ) return $item;

        if( $assoc ) return $this->data_model_get_assocs( $model, $assoc, $item, $self );
        return $item;
    }

    public function data_model_get_assocs( $model = false, $assoc = false, $item = false, $self = false ){
        if( $assoc === false || empty( $assoc ) ) return $item;
        if( ! $item ) return false;

        $add_to = array();
        foreach( $assoc as $a ){

            $add = false;

            if( in_array( $a, array_keys( $item ) ) ){ // Means this is the MANY item

                $add = $this->dao->dao_wpdb_select_row( $a, $item[ $a ], $self );

            }else{ // Means this is the One item

                $where = (object) ['this' => $model, 'equals' => $item[ "id" ] ];
                $add = $this->dao->dao_wpdb_select_all( $a, $where, $self );

            }

            if( is_wp_error( $add ) ) return $add;

            $add_to[ $a ] = $add;
        }

        $item = array_merge( $item, $add_to );
        return $item;
    }

    /**
     * get_items
     *
     * @param $model
     * @return Query Success | WP_Error
     */
    public function get_items( &$model, &$data ){
        $self = $this->isolate_self( $data );
        $where = ( is_int( $self ) ) ? (object) ['this' => 'user', 'equals' => $self ] : false;

        return $this->dao->dao_wpdb_select_all( $model, $where );
    }

    /**
     * data_model_update_one
     *
     * @param $model
     * @param $id
     * @param $params
     * @return Query Success | WP_Error
     */
    public function data_model_update_one( $model, $id, $params, $self = false ){
        $params[ 'updated_on' ] = date( 'Y-m-d H:i:s' );
        $result = $this->dao->dao_wpdb_update_item(
            $model,
            $data->params,
            $this->where_self(
                $data->current_user,
                $data->war_config->user_roles
            )
        );
        return $result;
    }

    /**
     * data_model_delete_one
     *
     * @param $model
     * @param $id
     * @return Query Success | WP_Error
     */
    public function data_model_delete_one( $model, $id, $self = false ){
        $deleted = $this->dao->dao_wpdb_delete_item( $model, $id, $self );
        if( $deleted[ 0 ] === 1) return [ 'success', $model, $id ];
    }

    private function where_self( $cu, $roles ){
        return ( $cu->role === end( $roles ) ) ? (object) ['this' => 'user', 'equals' => $cu->id ] : false;
    }

    private function isolate_self( $data ){
        return ( $data->current_user->role === end( $data->war_config->user_roles ) ) ? $data->current_user->id : false;
    }

    private function create_db_connection(){
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->prefix = $wpdb->prefix;
    }
}
