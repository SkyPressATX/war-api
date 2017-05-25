<?php

abstract class war_api {

    public $help;
    public $custom_config;

    public function __construct(){
        $this->help = new war_global_helpers; // Setup the $help object
    }

    public function war_custom_config( $custom_config ){
        if( ! is_array( $custom_config ) ) return false;
        $this->custom_config = $custom_config;
        add_filter( 'war_api_config', function( $wc ){
            if( isset( $this->custom_config[ 'default_endpoints' ] ) )
                $this->custom_config[ 'default_endpoints' ] = array_merge( $wc[ 'default_endpoints' ], $this->custom_config[ 'default_endpoints' ]);
            return array_merge( $wc, $this->custom_config );
        });
    }

    public function war_add_endpoint( $endpoint = [] ){
        if( empty( $endpoint ) ) return;

        $stored = $this->war_new_store( $endpoint );

        add_action( 'war_custom_endpoints', function( $config ) use( $stored ){
            $end = new war_new_endpoint( $stored );
            $end->register_endpoint( $config );
        }, 10, 1 );

    }

    public function war_add_model( $model = [] ){
        if( empty( $model ) ) return;

        $stored = $this->war_new_store( $model );

        add_action( 'war_custom_models', function( $config ) use( $stored ){
            $data_model = new war_new_model( $stored );
            $data_model->register_model( $config );
            add_filter( 'war_data_models', function( $models ) use( $stored ){
                $models[] = $stored;
                return $models;
            });
        }, 10, 1);
    }

    public function war_new_store( $x = [] ){
        if( empty( $x ) ) return $x;
        if( is_object( $x ) ) $x = (array)$x;
        $store = new war_data_store;
        array_walk( $x, function( $val, $key ) use( $store ){
            $store->$key = $val;
        });
        return $store;
    }

    /**
    * Need to refactor this to use the newly existing REST API Internal Call Functionality
    * Similar to the get_home_request default endpoint
    **/

    public function war_local_call( $class = false, $cb = false, $data = array() ){

        // $avail_endpoints = apply_filters( 'war_list_custom_endpoint', [] );

        $class_found = array_filter( $avail_endpoints, function( $end ) use ( $class, $cb ){
            return ( $end['options'] && isset( $end['options']->class ) && $end['options']->class === $class && $end['options']->call === $cb );
        } );

        if( empty( $class_found ) ) return new WP_Error(501, 'Endpoint Method Not Found');

        $class_args = reset( $class_found )['options']->args;

        foreach( $class_args as $arg => $arr ){
            if( ! in_array( $arg, array_keys( get_object_vars( $data->params ) ) ) && isset( $arr['default'] ) ) $data->params->$arg = $arr['default'];
        }

        return call_user_func( [ new $class, $cb ], $data );
    }
}
