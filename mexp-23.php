<?php

/**
 * Plugin Name: MEXP 23
 * Depends: Media Explorer
 * Plugin URI: https://github.com/soderlind/mexp-23
 * Description: 23 video extension for the Media Explorer.
 * Version: 0.1.2
 * Author: PerS
 * Author URI: http://soderlind.no/
 * Text Domain: mexp-23
 * Domain Path: /languages
 * License: GPL v2 or later
 * Requires at least: 3.6
 * Tested up to: 3.9.2
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 */

/**
 * Class that acts as plugin bootstrapper.
 *
 * @author Per Soderlind
 */

defined( 'ABSPATH' ) or die();

class MEXP_23 {

	/**
	 * Plugin version.
	 */
	const VERSION = '0.1.2';

	/**
	 * Constructor.
	 *
	 * - Defines constants used in this plugin.
	 * - Autoloader registration.
	 * - Loads the translation used in this plugin.
	 * - Loads 23 service.
	 *
	 * @since 0.1.0
	 * @access public
	 * @return void
	 */
	public function __construct() {

		$this->define_constants();

		// Autloader registration.
		spl_autoload_register( array( $this, 'loader' ) );

		$this->i18n();
		$this->add_oembed_provider();
		// Loads the 23 service.
		add_filter( 'mexp_services', array( $this, 'load_23_service' ) );



	}

	/**
	 * Autoloader for this plugin. The convention for a class to be loaded:
	 *
	 * - Prefixed with this class name and '_'
	 * - Filename in lowercase without the prefix separated by '-'.
	 *
	 * @since 0.1.2
	 * @access public
	 * @param string $classname Class name
	 * @return void
	 */
	public function loader( $classname ) {
		if ( false === strpos( $classname, __CLASS__ . '_' ) )
			return;

		$classname = str_replace( __CLASS__ . '_', '', $classname );
		$filename  = str_replace( '_', '-', strtolower( $classname ) );

		require_once MEXP_23_INCLUDES_DIR . $filename . '.php';
	}

	/**
	 * Define constants used by the plugin.
	 *
	 * @since 0.1.0
	 * @access public
	 * @return void
	 */
	public function define_constants() {
		define( 'MEXP_23_DIR', trailingslashit( plugin_dir_path( __FILE__ ) ) );

		define( 'MEXP_23_INCLUDES_DIR', MEXP_23_DIR . trailingslashit( 'includes' ) );

		define( 'MEXP_23_URL', trailingslashit( plugin_dir_url( __FILE__ ) ) );
	}

	/**
	 * Loads the translation files.
	 *
	 * @since 0.1.0
	 * @access public
	 * @return void
	 */
	public function i18n() {
		load_plugin_textdomain( 'mexp-23', false, 'mexp-23/languages' );
	}


	public function add_oembed_provider() {
		$options = get_option( 'v23_options' );
		if (isset($options['apiurl'])) {
			wp_oembed_add_provider( sprintf("%s/*",$options['apiurl']), sprintf("%s/oembed",$options['apiurl']) );
		}
	}

	/**
	 * Loads 23 service.
	 *
	 * @since 0.1.0
	 * @access public
	 * @filter mexp_services
	 * @param @param array $services Associative array of Media Explorer services to load; key is a string, value is a MEXP_Template object.
	 * @return array $services Associative array of Media Explorer services to load; key is a string, value is a MEXP_Template object.
	 */
	public function load_23_service( array $services ) {
		$services[ MEXP_23_Service::NAME ] = new MEXP_23_Service;
		return $services;
	}
}

add_action( 'plugins_loaded', function() {
	$GLOBALS['mexp_23'] = new MEXP_23();
} );


require_once('settings/admin.php');
