<?php
/**
 * The Beaker class is a simple container for enqueuing CSS, Javascript and hooking into actions.
 *
 * @package BP_Labs
 * @subpackage Beaker
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) )
	exit;

/**
 * Each component of BP Labs needs to extend and implement this beaker class.
 *
 * I haven't built pieces of plugin functionality in this pattern before. This, too, is an experiment!
 *
 * @since 1.0
 */
abstract class BPLabs_Beaker {
	/**
	 * Constructor.
	 *
	 * @since 1.0
	 */
	function __construct() {
		add_action( 'init', array( 'BPLabs', 'enqueue_script' ) );
		add_action( 'init', array( 'BPLabs', 'enqueue_style' ) );
		add_action( 'init', array( 'BPLabs', 'register_actions' ) );
	}

	/**
	 * Enqueue your javascript here
	 *
	 * @since 1.0
	 */
	function enqueue_script() {
	}

	/**
	 * Enqueue your CSS here
	 *
	 * @since 1.0
	 */
	function enqueue_style() {
	}

	/**
	 * Hook 
	 *
	 * @since 1.0
	 */
	function register_actions() {
	}
}
?>