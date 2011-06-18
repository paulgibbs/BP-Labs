<?php
/**
 * Plugin Name: BP Labs
 * Plugin URI: http://buddypress.org/community/groups/bp-labs/
 * Description: BP Labs contains unofficial and experimental BuddyPress features for testing and feedback. Cake, and grief counselling, will be available at the conclusion of the plugin.
 * Version: 1.0
 * Author: Paul Gibbs
 * Author URI: http://byotos.com
 * Network: true
 * Domain Path: /languages/
 * Text Domain: bpl
 * License: GPL3
 */

/**
 * BP Labs contains unofficial and experimental BuddyPress features for testing and feedback. Cake, and grief counselling, will be available at the conclusion of the plugin.
 *
 * "BP Labs"
 * Copyright (C) 2011 Paul Gibbs
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 3 as published by
 * the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see http://www.gnu.org/licenses/.
 *
 * @package BP_Labs
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) )
	exit;

/**
 * Welcome to the main BP Labs class. Anything and everything happens in here, come on in!
 *
 * @since 1.0
 */
class BPLabs {
	/**
	 * Creates an instance of the BPLabs class, and loads i18n.
	 *
	 * Thanks to the Jetpack plugin for the idea with this function.
	 *
	 * @return BPLabs object
	 * @since 1.0
	 * @static
	 */
	static function &init() {
		static $instance = false;

		if ( !$instance ) {
			load_plugin_textdomain( 'bpl', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
			$instance = new BPLabs;
		}

		return $instance;
	}

	/**
	 * Constructor.
	 *
	 * @since 1.0
	 */
	function __construct() {
		$this->_includes();
	}

	/**
	 * Include beakers for science.
	 *
	 * @since 1.0
	 */
	protected function _includes() {
		require_once( dirname( __FILE__ ) . '/beakers/class-bplabs-beaker.php' );

		if ( bp_is_active( 'activity' ) )
			require_once( dirname( __FILE__ ) . '/beakers/class-bplabs-autosuggest.php' );

		if ( bp_is_active( 'groups' ) )
			require_once( dirname( __FILE__ ) . '/beakers/class-bplabs-quickadmin.php' );

		//if ( bp_is_active( 'activity' ) )
		//	require_once( dirname( __FILE__ ) . '/beakers/class-bplabs-swish.php' );
		// I bet you want to know what Swish is going to do.

		do_action( 'bplabs_includes' );
	}
}
add_action( 'bp_include', array( 'BPLabs', 'init' ) );

// The cake is a lie.
?>