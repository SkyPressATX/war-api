# WAR Framework #
The WAR Framework de-couples the front and back ends of WordPress.
*"Let the front-end front and the back-end back!"*

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
		'api_name' => 'war',
		'api_prefix' => 'wp-json',
		'admin_toolbar' => false,
		'blog_id' => get_current_blog_id(),
		'default_endpoints' => [
			'build_tables' => true,
			'set_config' => true,
			'menu' => true,
			'site_options' => true,
			'theme_options' => true,
			'jwt_token' => true,
			'login' => true,
			'logout' => true,
			'register' => true,
			'get_home_page' => true
		],
		'is_multisite' => is_multisite(),
		'user_roles' => [],
		'version' => 1,
		'permalink' => '/posts/%postname%/',
		'category_base' => 'category'
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
