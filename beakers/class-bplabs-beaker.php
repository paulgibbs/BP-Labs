<?php
/**
 * The Beaker class is a simple container for enqueuing CSS, Javascript and hooking into actions.
 *
 * @package BP_Labs
 * @subpackage Beaker
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Each component of BP Labs needs to extend and implement this beaker class.
 *
 * I haven't built pieces of plugin functionality in this pattern before. This, too, is an experiment; for science!
 *
 * @since 1.0
 */
abstract class BPLabs_Beaker {
	/**
	 * Constructor.
	 *
	 * @since 1.0
	 */
	public function __construct() {
		if ( is_admin() && ( ! defined( 'DOING_AJAX' ) || DOING_AJAX === false ) )
			return;

		add_action( 'init', array( $this, 'enqueue_script' ) );
		add_action( 'init', array( $this, 'enqueue_style' ) );

		$this->register_actions();
	}

	/**
	 * Enqueue your javascript here
	 *
	 * @since 1.0
	 */
	public function enqueue_script() {
	}

	/**
	 * Enqueue your CSS here
	 *
	 * @since 1.0
	 */
	public function enqueue_style() {
	}

	/**
	 * Hook into actions here. The true starting point for a new experiment.
	 *
	 * @since 1.0
	 */
	protected function register_actions() {
	}
}
?>