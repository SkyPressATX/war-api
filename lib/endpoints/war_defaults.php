<?php

if( class_exists( 'war_api' ) ): //Make sure the war_api class exists first
    class war_defaults extends war_api {

        public function register_default_endpoints( $war_config ){

            $defaults = [
                'build_tables' => [
                    'uri' => '/data-model-setup',
                    'options' => [
                        'access' => 'administrator'
                    ],
                    'cb' => [ $this, 'war_build_tables' ]
                ],
                'jwt_token' => [
                    'uri' => '/jwt-token',
                    'options' => [
                        'access' => true
                    ],
                    'cb' => [ $this, 'war_jwt_create' ]
                ],
                'menu' => [
                    'uri' => '/menu',
                    'options' => [
                        'access' => false
                    ],
                    'cb' => [ $this, 'war_get_menu' ]
                ],
                'theme_options' => [
                    'uri' => '/theme-options',
                    'options' => [
                        'access' => false,
                        'method' => 'POST'
                    ],
                    'cb' => [ $this, 'war_theme_options' ]
                ],
                'site_options' => [
                    'uri' => '/site-options',
                    'options' => [
                        'access' => false
                    ],
                    'cb' => [ $this, 'war_site_options' ]
                ],
                'login' => [
                    'uri' => '/login',
                    'options' => [
                        'access' => null,
                        'method' => 'POST',
                        'args'=>[
                            'username' => [ 'type' => 'string', 'required' => true ],
                            'password' => [ 'type' => 'string', 'required' => true ]
                        ]
                    ],
                    'cb' => [ $this, 'war_login' ]
                ],
                'logout' => [
                    'uri' => '/logout',
                    'options' => [
                        'access' => true
                    ],
                    'cb' => [ $this, 'war_logout' ]
                ],
                'register' => [
                    'uri' => '/register',
                    'options' => [
                        'access' => null,
                        'method' => 'POST',
                        'args' => [
                            'email' => [ 'type' => 'email', 'required' => true ],
                            'password' => [ 'type' => 'string', 'required' => true ],
                            'role' => [
                                'type' => 'string',
                                'options' => ( isset( $war_config[ 'user_roles' ] ) ) ? $war_config[ 'user_roles' ] : [],
                                'default' => end( $war_config['user_roles'] )
                            ]
                        ]
                    ],
                    'cb' => [ $this, 'war_signup' ]
                ],
                'set_config' => [
                    'uri' => '/run-app-config',
                    'options' => [
                        'access' => 'administrator',
                    ],
                    'cb' => [ $this, 'war_app_config' ]
                ],
                'get_home_page' => [
                    'uri' => '/homepage',
                    'options' => [
                        'access' => null
                    ],
                    'cb' => [ $this, 'war_get_home_page' ]
                ]
            ];

            $allowed_endpoints = array_filter( $war_config[ 'default_endpoints' ] );

            array_walk( $defaults, function($end, $key, $ae){
                if( isset( $ae[ $key ] ) )
                    $this->war_add_endpoint( $end[ 'uri' ], $end[ 'options' ], $end[ 'cb' ] );
            }, $allowed_endpoints);

        }

        public function war_login( $data ){
            $class = $this->war_get_wua_class();
            return $class->war_login( $data );
        }

        public function war_logout( $data ){
            $class = $this->war_get_wua_class();
            return $class->war_logout( $data );
        }

        public function war_signup( $data ){
            $class = $this->war_get_wua_class();
            return $class->war_signup( $data );
        }

        public function war_jwt_create( $data ){
            $class = $this->war_get_wua_class();
            return $class->war_jwt_create( $data );
        }

        public function war_site_options( $data ) {
            $class = $this->war_get_wconf_class();
            return $class->war_site_options( $data );
        }

        public function war_theme_options( $data ) {
            $class = $this->war_get_wconf_class();
            return $class->war_theme_options( $data );
        }

        public function war_get_menu( $data ) {
            $class = $this->war_require_and_get_class( 'war_menu', '/war_menu.php' );
            return $class->war_get_menu();
        }

        public function war_app_config( $data ){
            $class = $this->war_require_and_get_class( 'war_set_configs', '/war_set_configs.php' );
            return $class->war_app_config( $data );
        }

        public function war_build_tables( $data ){
            $class = $this->war_require_and_get_class( 'war_set_configs', '/war_set_configs.php' );
            return $class->war_build_tables( $data );
        }

        public function war_get_home_page( $data ){
            return get_option( 'page_on_front' );
        }

        private function war_get_wconf_class(){
            require_once __DIR__ . "/war_get_options.php";
            return new war_get_options;
        }

        private function war_get_wua_class(){
            require_once __DIR__ . "/war_user_actions.php";
            return new war_user_actions;
        }

        private function war_require_and_get_class( $c = false, $f = false ){
            if($c === false) return false;
            require_once __DIR__ . $f;
            return new $c;
        }

    }

    // Add this extension to the war_api_register hook
    // add_action('war_api_extend', [new war_defaults, 'register_default_endpoints'] );

endif;
