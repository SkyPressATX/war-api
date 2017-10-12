<?php
namespace War_Api\Security;

class War_User_Roles {

	private $old_roles;
	private $new_roles;
	private $wp_roles;

	public function update_roles( $new_roles = array() ){

		$this->new_roles = array_reverse( $new_roles );
		$this->old_roles = $this->get_old_roles();

		// If nothing changed return true
		if( sizeof( $this->new_roles ) === sizeof( $this->old_roles ) ) return true;

		try {
			// Get Global $wp_roles
			$this->wp_roles = wp_roles();
			// Remove the Old Roles
			$this->remove_old_roles();
			// Add the New Roles
			$this->add_new_roles();
			// Save the New Roles
			$this->save_new_roles();
		} catch( \Exception $e ){
			throw $e;
		}
	}

	/**
	 * Get the Old Roles from WP_Options
	 *
	 * @return Array | Old Roles
	 **/
	private function get_old_roles(){
		return json_decode( get_option( 'war_old_user_roles', array() ) );
	}

	/**
	 * Remove the Old Roles from the currently set User Roles
	 *
	 **/
	private function remove_old_roles(){
		// If empty return true
		if( empty( $this->old_roles ) ) return true;
		/**
		 * Loop through and remove the old roles
		 * We are dropping them all, even if they are in the New Roles list
		 * So that we can keep our capabilities up to date
		 **/
		array_walk( $this->old_roles, function( $role ){
			if( $role !== 'administrator' ) $this->wp_roles->remove_role( $role );
		});
	}

	/**
	 * Add the New Roles to our WordPress Roles
	 *
	 **/
	private function add_new_roles(){
		if( empty( $this->new_roles ) ) return true;
		if( ! in_array( 'administrator', $this->new_roles ) ) $this->new_roles[] = 'administrator'; // Ensure Max level of access
		$processed_roles = $this->process_roles( $this->new_roles );
		array_walk( $processed_roles, function( $val, $role ){
			$this->wp_roles->add_role( $role, $val->name, $val->capabilities );
		});
	}

	/**
	 * Save the New Roles in WP_Options so that they become the Old Roles on the next check
	 *
	 **/
	private function save_new_roles(){
		update_option( 'war_old_user_roles', json_encode( $this->new_roles ), false );
	}

	/**
	 * Format an array to include the Role Name and Capabilities
	 *
	 * @return Array | Role Names and Capabilities
	 *
	 **/
	private function process_roles( $roles = array() ){
		if( empty( $roles ) ) throw new \Exception( 'No Roles provided to Process' );
		$processed_roles = array();
		$role_size = sizeof($roles);
		// Capabilities cascade from Left To Right
		for( $i=0; $i<$role_size; $i++ ){
			$role = $roles[$i];
			$rs = array_slice( $roles, 0, ($i+1) );
			$name = ucfirst($role);
			$caps = $this->format_caps( $role, $rs );
			$processed_roles[$role] = (object) ['name' => $name, 'capabilities' => $caps];
		}

		return $processed_roles;

	}

	/**
	 * Format Capabilities
	 *
	 * @return Array | Formatted Capabilities
	 *
	 **/

	private function format_caps( $role = NULL, $caps = array() ){
		if( $role === NULL || empty( $caps ) ) throw new \Exception( 'No Role or Capabilities Provided to Format' );

		$result = array();
		foreach( $caps as $cap ){
			if( ! is_array( $cap ) ) $cap = [ $cap ];
			if( $role && in_array( $role, $cap ) ) $result[ $role ] = true;
			else foreach( $cap as $c ){
				$result[$c] = true;
			}
		}

		if( ! in_array( 'read', array_keys( $result ) ) ) $result[ 'read' ] = true;

		return $result;
	}

}
