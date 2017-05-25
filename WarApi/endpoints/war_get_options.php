<?php

class war_get_options {

    public function war_site_options( $data ) {

        $result = [
            'siteOptions' => [
                'currentUser' => (isset($data->current_user)) ? $data->current_user->email : false,
                'currentUserRole' => (isset($data->current_user)) ? $data->current_user->role : false,
                'currentUserCaps' => (isset($data->current_user)) ? $data->current_user->caps : false,
                'siteName' => get_bloginfo('name'),
                'siteDescription' => get_bloginfo('description'),
                'siteUrl' => get_bloginfo('url')
            ],
            'themeOptions' => [
                'siteTitle' => ''
            ],
            'adminOptions' => apply_filters( 'war_admin_options', [] )
        ];
        $theme_options = get_option( 'WAR_THEME_OPTIONS' );
        if( $theme_options ) $result[ 'themeOptions' ] = json_decode( $theme_options );

        // if( ! empty( $result[ 'adminOptions' ] ) ) array_walk( $result[ 'adminOptions' ], function( &$a ){
        //     $a[ 'child' ] = true;
        // });

        $result[ 'adminOptions' ][] = [
            'title' => 'Theme Options',
            'role' => 'administrator',
            'uri' => 'theme-options'
        ];
        $result[ 'adminOptions' ][] = [
            'title' => 'WP Admin',
            'role' => 'administrator',
            'uri' => 'wp-admin'
        ];

        return $result;
    }

    public function war_theme_options( $data ) {
        $blog_id = (is_multisite()) ? get_current_blog_id() : false;
        // $args = $this->help->war_get_request_args( $request );
        $args = $data->params;
        $option = 'WAR_THEME_OPTIONS';
        $result = array();
        if($blog_id){
            $result[ 'siteName' ] = ( isset( $args->siteName ) ) ? update_blog_option( $blog_id, 'blogname', $args->siteName ) : false;
        }else{
            $result[ 'siteName' ] = ( isset( $args->siteName ) ) ? update_option( 'blogname', $args->siteName ) : false;
        }
        $result[ 'themeOptions' ] = ( $args->json ) ? update_option( $option, $args->json ) : false;
        return $result;
    }

}
