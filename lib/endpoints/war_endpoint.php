<?php



class war_endpoint {

    public $war_config;
	public $namespace;
	public $endpoint;

    public function __construct( $war_config, $endpoint ){
        $this->war_config = $war_config;
        $this->namespace = $this->war_config->namespace;
        $this->endpoint = (object) $endpoint;
    }

    public function endpoint_add(){
        register_rest_route( $this->namespace, $this->endpoint->uri, array(
            array(
                'methods' => ( isset($this->endpoint->options->method) ) ? $this->endpoint->options->method : 'GET',
                'callback' => $this->endpoint->cb,
                'permission_callback' => array($this,'endpoint_role_check'),
                'args' => $this->endpoint->options->args
            )
        ) );
    }

    public function war_endpoint_callback( $request ){
        $result = call_user_func_array( $this->endpoint->cb, [ $request ] );
        return $result;
    }

    public function endpoint_role_check( \WP_REST_Request $request ){
        $level = $this->endpoint->options->access;
        require_once __DIR__ . '/../security/war_security.php';
        $sec = new war_security;
        return $sec->security_role_check( $level, $request );
    }
}
