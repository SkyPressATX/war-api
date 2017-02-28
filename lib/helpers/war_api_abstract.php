<?php

abstract class war_api {

    public $help;

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

    public function war_add_endpoint( $uri = false, $op = false, $cb = false ){
        $new_object = new war_new_endpoint; // Setup the new_endpoint object
        $new_data = $new_object->new_endpoint( $uri, $op, $cb );
        if( ! $new_data || ! is_array( $new_data ) || empty( $new_data ) ) return; //Silent fail
        add_action( 'war_custom_endpoints', function( $config ) use ( $new_object, $new_data ){
            /***** Register the Endpoint via register_rest_route *****/
            $new_object->register_endpoint( $new_data, $config );
        }, 10, 1);

        add_filter( 'war_list_custom_endpoint', function( $list ) use ( $new_data ){
            $list[] = $new_data;
            return $list;
        });
    }

    public function war_add_model( $name = false, $op = false, $pre_data = null, $pre_return = null ){
        $new_object = new war_new_model; // Setup the new_model object
        $new_data = $new_object->new_model( $name, $op, $pre_data, $pre_return );
        add_action( 'war_custom_models', function( $config ) use ( $new_object, $new_data ){
            /***** Register the Endpoint via register_rest_route *****/
            $new_object->register_model( $new_data, $config );
        }, 10, 1);

        add_filter( 'war_list_custom_models', function( $list ) use ( $new_data ){
            $list[] = $new_data;
            return $list;
        });
    }

    public function war_local_call( $class = false, $cb = false, $data = array() ){

        $avail_endpoints = apply_filters( 'war_list_custom_endpoint', [] );

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
