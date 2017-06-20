<?php

namespace War_Api\Defaults;

use War_Api\Defaults\War_Menu as War_Menu;
use War_Api\Security\War_JWT as War_JWT;

/**
 * Default Endpoints Class
 *
 * This Class holds the CallBack functions for each Default Endpoint.
 *
 **/
class Default_Endpoints {

	public function war_get_home( $data ){
		$home_id = get_option('page_on_front');
		if( intval( $home_id ) === 0 || is_wp_error( $home_id ) ) return new \Exception( 'No Home Page was Set' );
		$home_req = new WP_Rest_Request( 'GET', '/wp/v2/pages' );
		// $home_req->set_param( 'id', $home_id );
		$home_req->set_query_params([
			'id' => $home_id,
			'_embed' => '1'
		]);
		$home_res = rest_do_request( $home_req );
		if( is_wp_error( $home_res ) ) return $home_res;
		return $home_res->data[0]; // There should only be one
	}

	public function war_login( $data ){
		$auth = wp_authenticate($data->params->username, $data->params->password);

        if( is_wp_error( $auth ) ) return $auth;

        // wp_set_auth_cookie( $auth->ID );
        // wp_set_current_user( $auth->ID );

        // return $auth;
        $war_jwt = new War_JWT( $data->war_config->war_jwt_expire, $auth->ID );
        $jwt = $war_jwt->jwt_key_create();
        return ['jwt' => $jwt ];
	}

	public function war_logout( $data ){
		wp_logout();
        return true;
	}

	// public function war_signup( $data ){
	// 	$data = $this->war_get_request_args( $request );
	//
    //     if( email_exists( $data->email ) ) return $this->response_prepare( new WP_Error( 409, 'Email Already Taken' ) );
	//
    //     $user_id = wp_create_user( $data->email, $data->password, $data->email );
	//
    //     // Set the nickname
    //     wp_update_user(
    //         array(
    //             'ID'          =>    $user_id,
    //             'nickname'    =>    $data->email
    //         )
    //     );
	//
    //     // Set the role
    //     $user = new WP_User( $user_id );
    //     $user->set_role( 'user' );
	//
    //     return $this->response_prepare( $user );
	// }

	// public function war_jwt_create( $data ){
	// 	$class = $this->war_get_wua_class();
	// 	return $class->war_jwt_create( $data );
	// }

	public function war_get_site_options( $data ){
		return json_decode( get_option( $data->params->option, [] ) );
	}

	public function war_save_site_options( $data ){
		$option = $data->params->option;
		unset( $data->params->option );
		$option_data = json_encode( $data->params );
		return update_option( $option, $option_data, false );

	}

	// public function war_site_options( $data ) {
	// 	$result = [
    //         'siteOptions' => [
    //             'currentUser' => (isset($data->current_user)) ? $data->current_user->email : false,
    //             'currentUserRole' => (isset($data->current_user)) ? $data->current_user->role : false,
    //             'currentUserCaps' => (isset($data->current_user)) ? $data->current_user->caps : false,
    //             'siteName' => get_bloginfo('name'),
    //             'siteDescription' => get_bloginfo('description'),
    //             'siteUrl' => get_bloginfo('url')
    //         ],
    //         'themeOptions' => [
    //             'siteTitle' => ''
    //         ],
    //         'adminOptions' => apply_filters( 'war_admin_options', [] )
    //     ];
    //     $theme_options = get_option( 'WAR_THEME_OPTIONS' );
    //     if( $theme_options ) $result[ 'themeOptions' ] = json_decode( $theme_options );
	//
    //     // if( ! empty( $result[ 'adminOptions' ] ) ) array_walk( $result[ 'adminOptions' ], function( &$a ){
    //     //     $a[ 'child' ] = true;
    //     // });
	//
    //     $result[ 'adminOptions' ][] = [
    //         'title' => 'Theme Options',
    //         'role' => 'administrator',
    //         'uri' => 'theme-options'
    //     ];
    //     $result[ 'adminOptions' ][] = [
    //         'title' => 'WP Admin',
    //         'role' => 'administrator',
    //         'uri' => 'wp-admin'
    //     ];
	//
    //     return $result;
	// }

	// public function war_theme_options( $data ) {
	// 	$blog_id = (is_multisite()) ? get_current_blog_id() : false;
    //     // $args = $this->help->war_get_request_args( $request );
    //     $args = $data->params;
    //     $option = 'WAR_THEME_OPTIONS';
    //     $result = array();
    //     if($blog_id){
    //         $result[ 'siteName' ] = ( isset( $args->siteName ) ) ? update_blog_option( $blog_id, 'blogname', $args->siteName ) : false;
    //     }else{
    //         $result[ 'siteName' ] = ( isset( $args->siteName ) ) ? update_option( 'blogname', $args->siteName ) : false;
    //     }
    //     $result[ 'themeOptions' ] = ( $args->json ) ? update_option( $option, $args->json ) : false;
    //     return $result;
	// }

	public function war_get_menu( $data ) {
		$class = new War_Menu;
		return $class->war_get_menu();
	}
}
