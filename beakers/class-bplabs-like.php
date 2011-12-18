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

		// Activity integration
		add_action( 'bp_register_activity_actions', array( 'BPLabs_Like', 'register_activity_actions' ) );
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
		if ( ( bp_is_blog_page() && is_single() || defined( 'DOING_AJAX' ) && DOING_AJAX ) && is_user_logged_in() )
			return true;

		return false;
	}

	/**
	 * Has the specified post already been liked by any user?
	 *
	 * @param int $post_id Optional; if not set, get ID from Post template loop.
	 * @return mixed False if post has NOT been liked, otherwise returns the existing Like's activity ID
	 * @since 1.3
	 */
	public static function has_post_been_liked( $post_id = 0 ) {
		// If this is not set, get ID from Post template loop.
		if ( ! $post_id )
			$post_id = get_the_ID();

		// Double-check post is set, and that the Activity component is active
		if ( ! $post_id || ! bp_is_active( 'activity' ) )
			return false;

		$activity = bp_activity_get( array(
			'filter' => array(
				'action'     => 'bpl_like',  // Action to filter on
				'object'     => 'bpl_like',  // Type to filter on
				'primary_id' => $post_id,    // Post ID to filter on
			),
			'max'    => 1,
			'spam'   => 'all',
		) );

		$activity_id = false;

		if ( ! empty( $activity['activities'] ) )
			$activity_id = $activity['activities'][0]->id;

		return $activity_id;
	}

	/**
	 * Has the specified user already liked the specified post?
	 *
	 * @param int $post_id Optional; if not set, get ID from Post template loop.
	 * @param int $user_id Optional; if not set, get current logged in user's ID.
	 * @return mixed False if post has NOT been liked, otherwise returns the Like's activity ID
	 * @since 1.3
	 */
	public static function has_user_liked_post( $post_id = 0, $user_id = 0 ) {
		// If this is not set, get ID from Post template loop.
		if ( ! $post_id )
			$post_id = get_the_ID();

		// If this is not set, get ID from current logged in user
		if ( ! $user_id && is_user_logged_in() )
			$user_id = bp_loggedin_user_id();

		// Double-check both IDs are set, and that the Activity component is active
		if ( ! $post_id || ! $user_id || ! bp_is_active( 'activity' ) )
			return false;

		$activity = bp_activity_get( array(
			'filter' => array(
				'action'     => 'bpl_like',  // Action to filter on
				'object'     => 'bpl_like',  // Type to filter on
				'primary_id' => $post_id,    // Post ID to filter on
			),
			'max'    => 1,
			'spam'   => 'all',
		) );

		if ( ! empty( $activity['activities'] ) )
			$activity = $activity['activities'][0];
		else
			return false;

		// Check if the creator of the original Like activity is the current user
		if ( $user_id == $activity->user_id )
			return $activity->id;

		// Loop through the meta, and compare recorded user IDs
		$metas = (array) bp_activity_get_meta( $activity->id, 'bpl_like' );
		foreach ( $metas as $meta ) {

			// User has Liked this post, but didn't create the original Like.
			if ( $user_id == $meta[0] )
				return $activity->id;
		}

		// If we're down here, the user hasn't Liked this post... yet?
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

		// Set defaults for add_menu()
		$class = '';
		$title = '<span class="ab-icon"></span><span class="ab-label">' . _x( 'Like', 'toolbar like button label', 'bpl' ) . '</span>';

		// Has the user has already liked this post?
		$activity_id = BPLabs_Like::has_user_liked_post();
		if ( $activity_id )
			$class = 'liked';

		// Get the Like count
		$activity_id = BPLabs_Like::has_post_been_liked();
		if ( $activity_id ) {
			$meta  = (array) bp_activity_get_meta( $activity_id, 'bpl_like' );

			if ( 'liked' == $class )
				$title = '<span class="ab-icon"></span><span class="ab-label">' . number_format_i18n( count( $meta ) ) . '</span>';
			else
				$title = '<span class="ab-icon"></span><span class="ab-label">' .sprintf( _x( '%s likes', 'toolbar like button alternative label', 'bpl' ), number_format_i18n( count( $meta ) ) ) . '</span>';
		}

		// Add the top-level Group Admin button
		$wp_admin_bar->add_menu( array(
			'href'  => $like_url,
			'id'    => 'bpl-likebutton',
			'meta'  => array(
				'class' => $class,
				'title' => _x( 'Like this Post', 'toolbar like button title', 'bpl' ),
			),
			'title' => $title,
		) );
	}

	/**
	 * AJAX receiver for Likes.
	 *
	 * @return string '-1' if something is missing. '0' if post doesn't exist. Or 'n', where n = number of Likes for the $post_id
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

		// Check user is logged in
		if ( ! is_user_logged_in() )
			die( '-1' );

		// Check post exists
		$post = get_post( $post_id );
		if ( is_null( $post ) )
			die( '0' );

		// @todo: Check if user is allowed to Like items
		// if ( ! current_user_can( 'bpl_like', $post_id ) )
		//	die( '-1' );

		// Does a Like for this item exist? (for any user)
		$activity_id = BPLabs_Like::has_post_been_liked( $post_id );

		// Like already exists
		if ( $activity_id ) {
			// Add new Like to meta
			$meta   = (array) bp_activity_get_meta( $activity_id, 'bpl_like' );
			$meta[] = array( bp_loggedin_user_id(), bp_core_current_time() );

			// Update meta
			bp_activity_update_meta( $activity_id, 'bpl_like', $meta );

			// Get Likes total
			$likes_count = count( $meta );

		// Add new Like
		} else {
			$activity_id = bp_activity_add( array(
				'component' => 'bpl_like',
				'item_id'   => $post_id,
				'type'      => 'bpl_like',
				'user_id'   => bp_loggedin_user_id(),
			) );

			$likes_count = 1;
		}

		// Send Likes total back
		exit( (string) number_format_i18n( $likes_count ) );
	}

	/**
	 * Register the activity stream actions for Likes
	 *
	 * @global object $bp BuddyPress global settings
	 * @since 1.3
	 */
	function register_activity_actions() {
		global $bp;
		bp_activity_set_action( $bp->activity->id, 'bpl_like', __( 'Liked a Post', 'bpl' ) );
	}
}
new BPLabs_Like();
?>