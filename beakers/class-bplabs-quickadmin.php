<?php
/**
 * @package BP_Labs
 * @subpackage Quickadmin
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Implements the group Quick Admin experiment.
 *
 * @since 1.0
 */
class BPLabs_Quickadmin extends BPLabs_Beaker {
	/**
	 * Hook Quickadmin into the group directory.
	 *
	 * @since 1.0
	 */
	protected function register_actions() {
		add_action( 'bp_directory_groups_item', array( 'BPLabs_Quickadmin', 'make_links' ) );
	}

	/**
	 * Enqueue CSS. You could add this directly to your theme.
	 *
	 * @since 1.0
	 */
	public function enqueue_style() {
		$dir = WP_PLUGIN_URL . '/bp-labs/beakers/css/quickadmin';

		if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG )
			wp_enqueue_style( 'bplabs-quickadmin', "{$dir}.dev.css", array(), '1.0' );
		else
			wp_enqueue_style( 'bplabs-quickadmin', "{$dir}.css", array(), '1.0' );
	}

	/**
	 * Output the quickadmin links
	 *
	 * @global $bp BuddyPress global settings
	 * @since 1.0
	 * @todo Make this work for more than just super admins.
	 */
	public function make_links() {
		global $bp;

		if ( empty( $bp->loggedin_user->id ) || !$bp->loggedin_user->is_super_admin )
			return;

		$url = bp_get_group_admin_permalink();
		$items = array(
			sprintf( '<a href="%1$s">%2$s</a>', $url . '/edit-details',   __( 'Edit', 'bpl' ) ),
			sprintf( '<a href="%1$s">%2$s</a>', $url . '/group-settings', __( 'Settings', 'bpl' ) ),
			sprintf( '<a href="%1$s">%2$s</a>', $url . '/group-avatar',   __( 'Avatar', 'bpl' ) ),
			sprintf( '<a href="%1$s">%2$s</a>', $url . '/manage-members', __( 'Members', 'bpl' ) ),
			sprintf( '<a href="%1$s">%2$s</a>', $url . '/delete-group',   __( 'Delete', 'bpl' ) )
		);

		echo apply_filters( 'bplabs_make_links', '<div class="bpl-quickadmin"><span>' . implode( '</span> | <span>', $items ) . '</span></div>', $items );
	}
}
new BPLabs_Quickadmin();
?>