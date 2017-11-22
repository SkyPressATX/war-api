<?php

namespace War_Api\Helpers;

class Param_Helper {

	private $war_config;

	public function __construct( $war_config = array() ){
		$this->war_config = $war_config;
	}

	public function get_read_records_params(){
		$params = [
			'select' => 'array',
			'filter' => 'array',
			'group' => 'array',
			'order' => 'array',
			'limit' => [
				'type' => 'integer',
				'default' => ( isset( $this->war_config->limit ) ) ? $this->war_config->limit : 10,
				'sanitize_callback' => $this->sanitize_limit()
			],
			'page' => [
				'type' => 'integer',
				'default' => 1
			],
			'sideLoad' => [
				'type' => 'string',
				'default' => false,
				'sanitize_callback' => $this->sanitize_side_load()
			],
			'sideSearch' => [
				'type' => 'array',
				'sanitize_callback' => $this->sanitize_side_search()
			],
			'_info' => [
				'type' => 'bool',
				'default' => true
			],
			'_map' => [
				'type' => 'bool',
				'default' => 'true'
			]
		];

		if( property_exists( $this->war_config, 'sideLimit' ) && $this->war_config->sideLimit !== false )
			$params[ 'sideLimit' ] = [
				'type' => 'integer',
				'default' => $this->war_config->sideLimit
			];

		return $params;
	}

	public function get_read_record_params(){
		$params = [
			'select' => 'array',
			'sideLoad' => [
				'type' => 'string',
				'default' => true,
				'sanitize_callback' => $this->sanitize_side_load()
			],
			'sideSearch' => [
				'type' => 'array',
				'sanitize_callback' => $this->sanitize_side_search()
			],
			'_info' => [
				'type' => 'bool'
			],
			'_map' => [
				'type' => 'bool'
			]
		];

		if( property_exists( $this->war_config, 'sideLimit' ) && $this->war_config->sideLimit !== false )
			$params[ 'sideLimit' ] = [
				'type' => 'integer',
				'default' => $this->war_config->sideLimit
			];

		return $params;
	}

	public function get_request_args( $request = [] ){
        if( empty( $request ) ) return new \Exception( 'No Request Object Provided' );
        $data = (object) array();
        $params = ( is_object($request) ) ? $request->get_params() : $request;
		$data->params = (object)array_filter( $params, function( $k ){
			return ( ! is_numeric( $k ) );
		}, ARRAY_FILTER_USE_KEY );
        return $data;
    }

    public function process_args( $args = array() ){
        array_walk( $args, function( &$att ){
            if( ! is_array( $att ) ) $att = [ 'type' => $att ];
        } );
        return $this->request_args( $args );
    }

    private function request_args( $args = array() ){
        // $x = array();
        foreach($args as $key => $val){
            $o = ( isset($val["options"]) ) ? $val["options"] : false;
            $x[$key] = array(
                'required' => false,
                'validate_callback' => $this->validate_param($val["type"], $o),
                'sanitize_callback' => $this->sanitize_global()
            );

            if($val["type"] == 'bool'){
                $x[$key]['default'] = false;
                $x[$key]['sanitize_callback'] = $this->sanitize_bool();
            }

            if( $val[ 'type' ] == 'array' ){
                $x[ $key ][ 'sanitize_callback' ] = $this->sanitize_array();
            }

			if( $val[ 'type' ] == 'json' ){
				$x[ $key ][ 'sanitize_callback' ] = $this->sanitize_json();
			}

            $x[ $key ] = array_merge( $x[ $key ], $val );
        }
        return $x;
    }

    public function validate_param( $p = 'string', $x = false ){

        if( is_array( $x ) || $p == 'enum' ) return function( $a ) use ( $p, $x ){
            if( $x === false ) return $x;
            if( $p == 'array' && is_string( $a ) && preg_match( '/^[^,]+,?/', $a ) )
                $a = explode( ',', $a );

            if( is_array( $a ) ) $res = array_filter( $a, function( $v ) use( $x ){
                return ( ! in_array( $v, $x ) );
            } );
            if( ! isset( $res ) ) $res = ( in_array( $a, $x ) );

            if( is_array( $res ) ) return empty( $res );
            return $res;
        };

        switch ($p){
            case 'string':
                return function($a){ return ( is_string( $a ) || is_bool( $a ) ); };
                break;
            case 'text':
                return function($a){ return ( is_string( $a ) ); };
                break;
            case 'integer':
                return function($a){ return is_numeric( $a ); };
                break;
            case 'date':
                return function($a){
                    $match = preg_match('/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/',$a);
                    return ($match === 1) ? true : false;
                };
                break;
			case 'date_time':
				return function($a){
					$match = preg_match('/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])\s[0-9]{2}:[0-9]{2}:[0-9]{2}$/',$a);
					return ($match === 1) ? true : false;
				};
			break;
            case 'array':
                return function($a){ return ( is_array($a) || ( is_string( $a ) && preg_match( '/^[^,]+,?/', $a ) ) ); }; //If is string, then it needs to be a CSL
                break;
            case 'object':
                return function($a){ return is_object($a); };
                break;
            case 'bool':
                return function($a){ return ( is_bool($a) || $a = preg_match( '/^1|0|true|false$/', $a ) ); };
                break;
            case 'email':
                return function($a){ $email = filter_var($a, FILTER_SANITIZE_EMAIL); return (filter_var($email, FILTER_VALIDATE_EMAIL) !== false); };
                break;
			case 'json':
				return function($a){
					if( is_array( $a ) ) $a = json_encode( $a );
					$a = json_decode( $a );
					return ( json_last_error() == JSON_ERROR_NONE );
				};
				break;
        }
        return function($a){ return true; };
    }

    public function sanitize_global(){
        return function( $a ){
			$a = trim($a);
            return ( is_numeric( $a ) ) ? floatval( $a ) : (string)$a;
        };
    }

    public function sanitize_bool(){
        return function($a){ if($a === true || $a == 'true' || $a == 1){ return true; }else{ return false; } };
    }

	public function sanitize_limit(){
		return function( $l ){ return ( (int)$l > 100 ) ? 100 : (int)$l; };
	}

	public function sanitize_side_load(){
		return function( $a ){
			if( is_bool( $a ) ) return $a;
			if( $a == 'true' || $a === (int)1 ) return true;
			if( $a == 'false' || $a === (int)0 ) return false;
			return (string)$a;
		};
	}

	public function sanitize_json(){
		return function( $a ){
			return $a;
		};
	}

    public function sanitize_array(){
        return function( $a ){
            if( is_string( $a ) ) $a = explode( ',', $a );
            array_walk( $a, function( &$v ){
                $v = trim( $v );
                $v = ( is_numeric( $v ) ) ? floatval( $v ) : (string) $v;
            } );
            return $a;
        };
    }

	public function sanitize_side_search(){
		return function( $a ){
			if( is_string( $a ) ) $a = explode( ',', $a );
			array_walk( $a, function( &$v ){
				$v  = trim( $v );
				$v = explode( ':', $v );
				array_walk( $v, function( &$x ){ $x = ( is_numeric( $x ) ) ? floatval( $x ) : (string)$x; } );
				$v = implode( ':', $v );
			} );
			return $a;
		};
	}

	public function parse_url_id_param( $url_id_param = array() ){
		if( empty( $url_id_param ) ) throw new \Exception( 'No URL ID Param Set' );
		if( sizeof( $url_id_param ) !== 2 ) throw new \Exception( 'URL ID Param not properly configured' );
		return '(?P<' . $url_id_param[0] . '>' . $url_id_param[1] . ')';
	}
}
