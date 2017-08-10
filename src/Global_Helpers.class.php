<?php

namespace War_Api\Helpers;

class Global_Helpers {

	public function get_old_rest_api_prefix(){
		$rewrite_rules = get_option( 'rewrite_rules' );
        $old_prefix = NULL;
        if( ! is_array( $rewrite_rules ) ) return $old_prefix;
        foreach( $rewrite_rules as $rule => $dest ){
            if( $dest === 'index.php?rest_route=/' ){
				$old_prefix = preg_replace( '/[^a-z-]*/', '', $rule );
				break;
			}
        }
        return $old_prefix;
	}

	public function war_encrypt( $string ){
        $iv_size = mcrypt_get_iv_size( MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CFB );
        $iv = mcrypt_create_iv( $iv_size, MCRYPT_RAND) ;
        $key = hash( 'sha256', SECURE_AUTH_KEY, true );
        $enc = mcrypt_encrypt( MCRYPT_RIJNDAEL_256, $key, $string, MCRYPT_MODE_CFB, $iv);
        $com = $iv . $enc;
        return base64_encode( $com );
    }

    public function war_decrypt( $string ){
        if(! $string) return true;
        $iv_size = mcrypt_get_iv_size( MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CFB );
        $debase = base64_decode( $string );
        $iv = substr( $debase, 0, $iv_size );
        $val = substr( $debase, $iv_size );
        $dc = mcrypt_decrypt( MCRYPT_RIJNDAEL_256, hash( 'sha256', SECURE_AUTH_KEY, true ), $val, MCRYPT_MODE_CFB, $iv );
        return ( $dc ) ? $dc : $val;
    }

	public function rewrite_flush(){
		flush_rewrite_rules( false );
    }

	public function quote_it( $x ){
		if( is_numeric( $x ) ) return $x;
		return '"' . $x . '"';
	}

    public function numberfy( $array ){
        $array = (array) $array;
        foreach( $array as &$row ){
            if( is_array( $row ) || is_object( $row ) ){
                $row = $this->numberfy( $row );
            }else{
				if( is_numeric( $row ) ) $row = (float) $row;
			}
        }
        return $array;
    }

	public function flatten_cs_list( $array = '' ){
		if( ! is_array( $array ) && ! strpos( $array, ',' ) ){
			$item = ( is_numeric( $array ) ) ? [ (int)$array ] : [ (string)$array ];
			if( sizeof( $item ) > 0 ) return $item;
		}

		if( ! is_array( $array ) && strpos( $array, ',' ) ) $array = explode( ',', $array );

		$result = [];
		foreach( $array as $value ){
			$result = array_merge( $result, $this->flatten_cs_list( $value ) );
		}
		return $result;
	}

	public function sql_data_type( $type = 'string' ){
		switch ( $type ){
			case 'string':
				return 'VARCHAR(150)';
				break;
			case 'email':
				return 'VARCHAR(150)';
				break;
			case 'text':
				return 'LONGTEXT';
				break;
			case 'integer':
				return 'BIGINT';
				break;
			case 'float':
				return 'FLOAT(10,5)';
				break;
			case 'date':
				return 'DATETIME';
				break;
			case 'bool':
				return 'TINYINT';
				break;
			case 'array':
				return 'VARCHAR(550)';
				break;
			default:
				return 'VARCHAR(150)';
				break;
		}
	}

	public function get_table_name( $db = array(), $model = array(), $war_config = array() ){
		if( empty( $db ) || empty( $model ) || empty( $war_config ) ) return false;

		$table = $db->prefix;
		$table .= ( isset( $model->table_prefix ) ) ? $model->table_prefix : $war_config->api_name . '_';
		$table .= $model->name;
		return $table;
	}
}
