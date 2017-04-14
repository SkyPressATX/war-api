# WAR Framework #

*"Let the front-end front and the back-end back!"*

The WAR Framework de-couples the front and back ends of WordPress.

### What does WAR stand for? ###
* W - WordPress
* A - AngularJS
* R - Rest API

### But Why? ###
With the newly introduced WP Rest API, we are creating the ability to turn WordPress into an MVC Developers Framework. The WAR Framework To Do List:

- [x] Easy Creation of Custom Endpoints
- [x] Full Configuration Capabilities
- [x] Easy User Management and User Role Capabilities
- [x] Security Best Practices
- [x] Front-End Routing / Templating
- [ ] Easy Creation of Data Modules (with full CRUD capabilities) -- In Development

All of this as the "boilerplate" code that you don't have to write. The WAR Framework is split up between two sections: The WAR API Plugin and The WAR Parent Theme.

## The WAR API Plugin ##

### Installation ###
_**NOTE: If you have not used the WAR Framework before, it is highly recommended that the WAR API Plugin only be installed on fresh, vanilla instance of WordPress.**_

Just like any normal WordPress plugin, you can install the WAR API Plugin by downloading the `.zip` file from this github repo, and then use the *Upload Plugin* feature within WordPress. The WAR API Plugin also handles it's own updates directly from within WordPress as well. This means you don't have to keep coming back to this repo and repeating the initial install process anytime updates are released.

### Using the Plugin ###
* Upon Activation the WAR API Plugin will automatically configure Pretty Permalinks (`/posts/%postname%/`). This is to ensure easier use of the WordPress Rest API, as well as provide a seamless interaction with the WAR Parent Theme.
* By Default the WAR API Plugin ships with the following Custom Endpoints enabled within the `/war/v1` namespace.
 * `/data-model-setup`
 * `/jwt-token`
 * `/menu`
 * `/theme-options`
 * `/site-options`
 * `/login`
 * `/logout`
 * `/register`
 * `/run-app-config`
 * `/homepage`

To Configure and Extend the WAR API Plugin you need to use your own custom Plugin. Below is an Example Plugin for reference:

```php
<?php
/*
Plugin Name: SkyPress API
Description:  SkyPress Extension of WAR API
Version: 0.1
Author: BMO
License: MIT
*/

// Use this Hook to ensure all of the plugins have loaded before continuing
add_action('plugins_loaded', function(){

    //Make sure the war_api class exists first
    if( class_exists( 'war_api' ) ):

		class skypress_api extends war_api {

			/*
			 * Modify the WAR Framework Configuration
			 */
			public function skypress_config(){
				$this->war_custom_config([
					'permalink' => '/%postname%/',
					'api_name' => 'skypress',
					'version' => 1,
					'default_endpoints' => [
						'jwt_token' => false,
						'register' => false,
						'get_home_page' => false,
					]
				]);
			} // END skypress_config

		} // END Class

		// Instantiate the Class
		$skypress_api = new skypress_api;
		// Add our modification to the war_config_extend Hook
		add_action( 'war_config_extend', [ $skypress_api, 'skypress_config' ] );

	endif; // END class_exists Check

}); // END plugins_loaded Hook
```
* By using the built in `war_custom_config()` method, as opposed to tapping directly into the `war_api_config` Filter Hook, you only have to specify the changes you wish to make to the default WAR Framework Configuration.
* The Full Default WAR Framework Configuration is as follows:

```php
/**
 * Get Default Config
 *
 * @return Array - Default Config Values
 */
private function get_default_config(){
	return [
		'api_name' => 'war', // API Namespace
		'api_prefix' => 'wp-json', // API Prefix ( https://yoursite.com/<api_prefix>/<api_name>/<version>/endpoint/uri )
		'admin_toolbar' => false, // Show or Hide the Admin Tool bar when browsing the site while logged in
		'blog_id' => get_current_blog_id(), // Useful for Multi-Site configurations
		'default_endpoints' => [ // Control which default Endpoints get registered
			'build_tables' => true, // Used to adjust the table structure of Data Models after changes have been made
			'set_config' => true, // Used to manually the run static config method in /lib/config/war_config.php
			'menu' => true, // Endpoint to return a JSON string of the current Menu Structure
			'site_options' => true, // Return JSON string of current configured WAR API Options
			'theme_options' => true, // Return JSON string of current saved Theme Options
			'jwt_token' => true, // Allow JSON Web Token Authentication and Management
			'login' => true, // Allow ability to login to the site via the WAR API
			'logout' => true, // Allow ability to logout of the site via the WAR API
			'register' => true, // Allow ability to Register a new user via the WAR API
			'get_home_page' => true // Return the page ID that has been set as the "Home Page"
		],
		'is_multisite' => is_multisite(), // Is this a Multi-Site instance
		'user_roles' => [], // List of Custom User Roles
		'version' => 1, // API Version
		'permalink' => '/posts/%postname%/', // Define the desired permalink structure
		'category_base' => 'category' // Define the desired Category Base
	];
}
```

**Lets Look at what we've changed**
* `'permalink' => '/%postname%/'` Set the Permalink Structure
* `'api_name' => 'skypress'` Set the API Namespace to `skypress` instead of `war`
* `'version' => 1` Use our own API Version
* `'default_endpoints' => []` Don't enable the listed Default Endpoints
 * `'jwt_token' => false`
 * `'register' => false`
 * `'get_home_page' => false`

### Create Custom Endpoint ###
Custom Endpoints are the best way to define and control all of the logic and information that will be returned.

* `$this->war_add_endpoint( $uri = 'string', $options = 'array()', $call_back = [ $this, 'cb_string' ] )` is the primary method used for Registering a Custom Endpoint.
* This method requires 3 parameters:
 * $uri -> The Endpoint Uri *https://yoursite.com/<api_prefix>/<api_namespace>/<version><$uri>*
  * ***Note: If the $uri does not start with a backslash ( / ), then one will automatically be added***
 * $options -> Array of Options you would like your Endpoint to adhere to. *This can be blank, though it typically isn't*:
  * `'access' => 'string' | array | true | false | null` Access refers to which group of users (if any) has access to this Endpoint:
   * 'string' can be one of the custom 'user_roles' provided the WAR API Configuration or an available user capability
   * Array is used if you have custom User Roles and Groups configured. `[ 'group_name' => 'user_role' ]`
   * `true` Indicates that this endpoint is accessible to any authenticated user.
   * `false` (default access value) Indicates that this endpoint is not available for direct access, however can be called from within the Site by un-authenticated users. `false` Endpoints require a valid WP Nonce to be passed. This is primarily for endpoints such as the default "menu" endpoint.
   * `null` Indicates that this endpoint is completely open to the public. No form of authentication is needed.
  * `args` -> Array of URL Parameters you would like the Endpoint to accept. ` 'args' => [ 'url_param_name' => [] ]` The main properties to declare for each arg is:
   * `'type' => 'string' | 'integer' | 'date' | 'array' | 'bool' | 'object' | 'enum' | 'email'` Validate URL params based on one of these conditions
    * Array is a comma separated string of values *?room_numbers=1,2,3,4*
   * `'required' => true | false` Require any URL Param in order to validate the request
   * `'default' => <some_value>` Set the default value of this URL Param should the request not include one *Note: Default values are set before "Required" is checked, thus there will always a value and require is not needed*
   * `'options' => array()` Array of acceptable values.
    * `'pets' => [ 'type' => 'string', 'default' => 'Cherry', 'options' => [ 'Niko', 'Cherry' ] ]` This URL could only accept the URL Param `pets` with a value of either `Cherry`, or `Niko`. *(https://yoursite.com/<api_prefix>/<api_namespace>/<version><$uri>?pets=Chompers This Request would fail)*
    * If `type` equals `array` then any combination of the URL Param value should match the options declared:
     * `'humans' => [ 'type' => 'array', 'required' => true, 'options' => [ 'Sally', 'Sarah', 'Mark', 'Jack' ] ]` The URL Param `humans` could contain the value *?humans=Sally,Sarah* or *?humans=Mark,Sally,Jack* or just *?humans=Mark*. However *?humans=Mary,Mark,Jack,Sarah* would fail to validate
  *      













$this->war_add_endpoint( $end[ 'uri' ], $end[ 'options' ], $end[ 'cb' ] );
