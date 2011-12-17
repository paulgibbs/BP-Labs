<?php
/**
 * @package BP_Labs
 * @subpackage Mentions_Like
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) )
	exit;

/**
 * Implements the Like experiment. Requires BP 1.6+, WP 3.3+, and the Activity component active.
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
		// Check if to show the Like button for the current screen
		if ( ! BPLabs_Like::is_like_enabled() )
			return;

		$dir = WP_PLUGIN_URL . '/bp-labs/beakers/css/like';
		if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG )
			$dir .= '.dev';

		wp_enqueue_style( 'bplabs-like', "{$dir}.css", array(), '1.3' );
	}

	/**
	 * Enqueue javascript
	 *
	 * @since 1.3
	 */
	public function enqueue_script() {
		// Check if to show the Like button for the current screen
		if ( ! BPLabs_Like::is_like_enabled() )
			return;

		$dir = WP_PLUGIN_URL . '/bp-labs/beakers/js/like';
		if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG )
			$dir .= '.dev';

		wp_enqueue_script( 'bplabs-like', "{$dir}.js", array( 'jquery' ), '1.3', true );
	}

	/**
	 * Register hooks and filters.
	 *
	 * @since 1.3
	 */
	protected function register_actions() {
		// For some reason, this experiment's scripts only work hooked into the 'wp' action.
		remove_action( 'init', array( $this, 'enqueue_script' ) );
		remove_action( 'init', array( $this, 'enqueue_style' ) );
		add_action( 'wp', array( $this, 'enqueue_style' ) );
		add_action( 'wp', array( $this, 'enqueue_script' ) );

		// Add Toolbar support
		add_action( 'admin_bar_menu', array( 'BPLabs_Like', 'toolbar' ), 75 );

		// AJAX handler
		add_action( 'wp_ajax_bpl-like', array( 'BPLabs_Like', 'ajax_reciever' ) );
	}

	/**
	 * Is the Like button enabled for the current screen?
	 *
	 * For now, only works with blog posts
	 *
	 * @return bool True if to enable the like button for the current screen
	 * @since 1.3
	 */
	public static function is_like_enabled() {
		if ( bp_is_blog_page() && is_single() || defined( 'DOING_AJAX' ) && DOING_AJAX )
			return true;

		return false;
	}

	/**
	 * Add Toolbar support
	 *
	 * @global unknown $wp_admin_bar
	 * @since 1.3
	 */
	public function toolbar() {
		global $wp_admin_bar;

		// Check if to show the Like button for the current screen
		if ( ! BPLabs_Like::is_like_enabled() )
			return;

		// No-js fallback
 		$nonce    = wp_create_nonce( 'bpl-like-' . get_the_ID() );
		$like_url = remove_query_arg( array( 'like', '_wpnonce' ), $_SERVER['REQUEST_URI'] );
		$like_url = add_query_arg( array( 'like' => get_the_ID(), '_wpnonce' => $nonce ), $like_url );

		// Add the top-level Group Admin button
		$wp_admin_bar->add_menu( array(
			'href'  => $like_url,
			'id'    => 'bpl-likebutton',
			'meta'  => array(
				'title' => _x( 'Like this Post', 'toolbar like button title', 'bpl' ),
			),
			'title' => '<span class="ab-icon"></span><span class="ab-label">' . _x( 'Like', 'toolbar like button label', 'bpl' ) . '</span>',
		) );
	}

	/**
	 * AJAX receiver for Likes.
	 *
	 * @return string '-1' if something is missing. '0' if post doesn't exist. Or 'n', where n = new/existing Like ID
	 * @since 1.3
	 */
	public function ajax_reciever() {
		$post_id = ! empty( $_POST['like'] ) ? (int) $_POST['like'] : 0;

		// $post_id is required
		if ( empty( $post_id ) )
			die( '-1' );

		// Check nonce
		check_ajax_referer( 'bpl-like-' . $post_id );

		// Check that BuddyPress' Activity component is active
		if ( ! bp_is_active( 'activity' ) )
			die( '-1' );

		// Check post exists
		$post = get_post( $post_id );
		if ( is_null( $post ) )
			die( '0' );

		// @todo: Check if user is allowed to Like items
		// if ( ! current_user_can( 'bpl_like', $post_id ) )
		//	die( '-1' );

		// Does a Like for this item exist?
		$activity = bp_activity_get( array(
			'filter' => array(
				'object'       => 'bpl_like',  // Type to filter on
				'action'       => 'like',      // Action to filter on
				'primary_id'   => $post_id,    // Post ID to filter on
			),
			'max'    => 1,
			'spam'   => 'all',
		) );

		// Update existing Like meta
		if ( ! empty( $activity['activities'] ) ) {
			$activity = $activity['activities'][0];



		// Add new Like
		} else {
		}

		// Send response
		exit( 000000 ); // put new like ID here
	}
}
new BPLabs_Like();
?>