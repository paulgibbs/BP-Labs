<?php
/**
 * @package BP_Labs
 * @subpackage Mentions_Like
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) )
	exit;

/**
 * Implements the Like experiment.
 *
 * @since 1.3
 */
class BPLabs_Like extends BPLabs_Beaker {
	/**
	 * Enqueue CSS
	 *
	 * @since 1.3
	 */
	public function enqueue_style() {
		return;
		$dir = WP_PLUGIN_URL . '/bp-labs/beakers/css/like';

		if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG )
			wp_enqueue_style( 'bplabs-like', "{$dir}.dev.css", array(), '1.3' );
		else
			wp_enqueue_style( 'bplabs-like', "{$dir}.css", array(), '1.3' );
	}

	/**
	 * Register hooks and filters. The true starting point.
	 *
	 * @since 1.0
	 */
	protected function register_actions() {
	}
}
new BPLabs_Like();
?>