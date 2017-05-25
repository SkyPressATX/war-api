<?php

namespace War_Api\Security;

class Role_Check {

	private $required_role;
	private $current_user;
	private $war_config;

	public function __construct( $role = false, $current_user = array(), $war_config = array() ){
		$this->required_role = $role;
		$this->current_user = $current_user;
		if( ! empty( $war_config ) ) $this->war_config = $war_config;
	}

	public function has_access(){
		if( $this->required_role === NULL ) return true; //Open access for all

		if( $this->required_role === false && $this->current_user->auth === 'NONCE' ) return true; //Access only via the Front End

		if( ! $this->current_user ) return false; //Should be logged in at this point

		if( $this->required_role === true ) return true; // If logged in, then give access

		return ( in_array( $this->required_role, $this->current_user->caps ) );
	}

	/**
	 * Different because it uses the full array of user roles
	 **/
	public function model_has_access( $access_type = 'read' ){
		$access_levels = $this->model_access_levels();
		$this->required_role = $access_levels[ $access_type ];
		return $this->has_access();
	}

	private function model_access_levels(){
		if( is_string( $this->required_role ) ){ // Set all Perm Levels to String Value
			return (object) [
				'create' => $this->required_role,
				'read' => $this->required_role,
				'update' => $this->required_role,
				'delete' => $this->required_role,
			];
		}
		$user_roles = array_reverse( (array) $this->war_config->user_roles );
		$defaults = array(
			'create' => $user_roles[1],
			'read' => $user_roles[0],
			'update' => $user_roles[1],
			'delete' => $user_roles[1]
		);
		$access_levels = array_merge( $defaults, $this->required_role );
		return $access_levels;
	}

} // END Role_Check
