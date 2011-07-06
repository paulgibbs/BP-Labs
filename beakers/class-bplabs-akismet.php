<?php
/**
 * @package BP_Labs
 * @subpackage Akismet
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) )
	exit;

/**
 * Implements Activity Akismet
 *
 * @since 1.0
 */
class BPLabs_Akismet extends BPLabs_Beaker {
	/**
	 *
	 * @since 1.0
	 */
	protected function register_actions() {
	}

	/**
	 *
	 * @since 1.0
	 */
	function enqueue_style() {
		$dir = WP_PLUGIN_URL . '/bp-labs/beakers/css/quickadmin';

		if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG )
			wp_enqueue_style( 'bplabs-quickadmin', "{$dir}.dev.css", array(), '1.0' );
		else
			wp_enqueue_style( 'bplabs-quickadmin', "{$dir}.css", array(), '1.0' );
	}
}
new BPLabs_Akismet();
?>