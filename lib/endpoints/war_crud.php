<?php

require_once __DIR__ . "/../responses/war_response.php";

class war_crud {

	public $war_config;
	public $namespace;
	public $model;
	public $data;
	public $response;
	public $access_levels;
	public $self;

	public function __construct( $war_config, $model ){
		// parent::__construct();
		$this->war_config = $war_config;
		$this->namespace = $this->war_config->namespace;
		$this->model = $model;
		// $this->self = ( sizeof( $this->current_user->roles ) === 1 ) ? $this->current_user->ID : false;
		$this->response = new war_response;
		$this->access_levels = $this->security_get_access_levels( $this->war_config, $this->model );
	}

	/**
	 * @inheritdoc
	 */
	public function crud_add_routes() {
		register_rest_route( $this->namespace, '/' . $this->model->uri, [
				[
					'methods'         => \WP_REST_Server::READABLE,
					'callback'        => [ $this, 'crud_get_items' ],
					'permission_callback' => [ $this, 'crud_read_permissions_check' ],
					// 'args'            => $this->crud_request_args($mod->base)
				],
				[
					'methods'         => \WP_REST_Server::CREATABLE,
					'callback'        => [ $this, 'crud_create_item' ],
					'permission_callback' => [ $this, 'crud_create_permissions_check' ],
					'args'            => $this->model->options->args
				],
			]
		);
		register_rest_route( $this->namespace, '/' . $this->model->uri . '/(?P<id>\d+)', [
				[
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => [ $this, 'crud_get_item' ],
					'permission_callback' => [ $this, 'crud_read_permissions_check' ],
					// 'args'            => $this->crud_request_args($mod->base)
				],
				[
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => [ $this, 'crud_update_item' ],
					'permission_callback' => [ $this, 'crud_update_permissions_check' ],
					// 'args'            => $this->crud_request_args($mod->base)
				],
				[
					'methods'             => \WP_REST_Server::DELETABLE,
					'callback'            => [ $this, 'crud_delete_item' ],
					'permission_callback' => [ $this, 'crud_delete_permissions_check' ],
					// 'args'            => $this->crud_request_args($mod->base)
				],
			]
		);

	}

	/**
	 * @inheritdoc
	 */
	public function crud_create_permissions_check( \WP_REST_Request $request ){
		$this->crud_security_object();
		
		$level = $this->access_levels->create;
		return ($this->security->security_role_check($level,$request));
	}

	/**
	 * Check if a given request has access to get a specific item
	 *
	 * @param \WP_REST_Request $request Full data about the request.
	 * @return \WP_Error|bool
	 */
	public function crud_read_permissions_check( \WP_REST_Request $request ) {
		$this->crud_security_object();

		$level = $this->access_levels->read;
		return ($this->security->security_role_check($level,$request));
	}

	/**
	 * Check if a given request has access to update a specific item
	 *
	 * @param \WP_REST_Request $request Full data about the request.
	 * @return \WP_Error|bool
	 */
	public function crud_update_permissions_check( \WP_REST_Request $request ) {
		$this->crud_security_object();

		$level = $this->access_levels->update;
		return ($this->security->security_role_check($level,$request));
	}
	/**
	 * Check if a given request has access to delete a specific item
	 *
	 * @param \WP_REST_Request $request Full data about the request.
	 * @return \WP_Error|bool
	 */
	public function crud_delete_permissions_check( \WP_REST_Request $request ) {
		$this->crud_security_object();

		$level = $this->access_levels->delete;
		return ($this->security->security_role_check($level,$request));
	}

	/**
	 * Create one item from the collection
	 *
	 * @param \WP_REST_Request $request Full data about the request.
	 * @return response|error
	 */
	public function crud_create_item( \WP_REST_Request $request ) {
		$this->crud_data_object();
		$params = $request->get_params();
		$args = $this->model->options->args;
		// apply_filters( 'war_pre_data_' . $this->model->uri, $params, $args );
		$created = $this->data->data_model_add( $this->model->uri, $params, $args, $this->current_user->ID );
		return $this->response->response_prepare($created);
	}

	/**
	 * Get a collection of items
	 *
	 * @param \WP_REST_Request $request Full data about the request.
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function crud_get_items( \WP_REST_Request $request ) {
		$this->crud_security_object();
		$this->crud_data_object();

		$this->self = $this->security_get_current_user_id();
		$result = $this->data->data_model_get_all( $this->model->uri, $this->self );
		foreach($result as $i => &$res){
			$res = apply_filters( 'war_pre_return_' . $this->model->uri, $res );
		}
		return $this->response->response_prepare( $result );
	}

	/**
	 * Get one item from the collection
	 *
	 * @param \WP_REST_Request $request Full data about the request.
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function crud_get_item( \WP_REST_Request $request ) {
		$this->crud_security_object();
		$this->crud_data_object();

		$this->self = $this->security_get_current_user_id();
		$params = (object) $request->get_params();
		$assoc = ( isset($this->model->options->assoc) ) ? $this->model->options->assoc : false;
		$item = $this->data->data_model_get_one( $this->model->uri, $params->id, $assoc, $this->self );
		$result = apply_filters( 'war_pre_return_' . $this->model->uri, $item );
		return $this->response->response_prepare( $result );
	}

	/**
	 * Update one item from the collection
	 *
	 * @param \WP_REST_Request $request Full data about the request.
	 * @return response|error
	 */
	public function crud_update_item( \WP_REST_Request $request ) {
		$this->crud_security_object();
		$this->crud_data_object();

		$this->self = $this->security_get_current_user_id();
		$id = $request->get_url_params();
		$id = (int) $id["id"];
		$params = $request->get_body_params();
		$result = $this->data->data_model_update_one( $this->model->uri, $id, $params, $this->self );
		return $this->response->response_prepare($result);
	}

	/**
	 * Delete one item from the collection
	 *
	 * @param \WP_REST_Request $request Full data about the request.
	 * @return response|error
	 */
	public function crud_delete_item( \WP_REST_Request $request ) {
		$this->crud_security_object();
		$this->crud_data_object();

		$this->self = $this->security->security_get_current_user_id();
		$id = $request->get_url_params();
		$id = (int) $id["id"];
		$result = $this->data->data_model_delete_one( $this->model->uri, $id, $this->self );
		return $this->response->response_prepare($result);
	}

	/**
	 * Create Data Object if it doesn't exists
	 */
	private function crud_data_object(){
		require_once __DIR__ . "/../data/war_data.php";
		if(! isset( $this->data ) ) $this->data = new war_data;
	}

	private function crud_security_object(){
		require_once __DIR__ . "/../security/war_security.php";
		if(! isset( $this->security ) ) $this->security = new war_security;
	}
}
