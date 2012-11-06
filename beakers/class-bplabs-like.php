<?php
/**
 * @package BP_Labs
 * @subpackage Mentions_Like
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
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
		add_filter( 'bp_get_activity_content_body', array( 'BPLabs_Like', 'activity_content' ), 2 );
	}

	/**
	 * Register the activity stream actions for Likes
	 *
	 * @global object $bp BuddyPress global settings
	 * @since 1.3
	 */
	public function register_activity_actions() {
		global $bp;
		bp_activity_set_action( $bp->activity->id, 'bpl_like', __( 'Liked a Post', 'bpl' ) );
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

		// Is the Activity component active?
		if ( ! $post_id || ! $user_id || ! bp_is_active( 'activity' ) )
			return false;

		// Find the Like activity
		$activity_id = BPLabs_Like::has_post_been_liked( $post_id );
		if ( empty( $activity_id ) )
			return false;

		// Check if the creator of the original Like activity is the current user
		$activity = bp_activity_get( array(
				'filter' => array(
					'action'     => 'bpl_like',  // Action to filter on
					'object'     => 'bpl_like',  // Type to filter on
					'primary_id' => $post_id,    // Post ID to filter on
				),
				'max'    => 1,
				'spam'   => 'all',
			) );

		if ( $user_id == $activity['activities'][0]->user_id )
			return $activity_id;

		// Has the specified user liked this post?
		$likes = (array) bp_activity_get_meta( $activity_id, 'bpl_likes' );
		if ( in_array( $user_id, $likes ) )
			return $activity_id;

		// If we're down here, the user hasn't Liked this post.
		return false;
	}

	/**
	 * Get the Likes total for the specified activity ID.
	 *
	 * Assumes that has_post_been_liked() has been succesful prior to invocation.
	 *
	 * @global int $activity_id Optional; if not set, assumes we're in the Activity loop.
	 * @return int Likes total
	 * @since 1.3
	 */
	public static function get_likes_total( $activity_id = 0 ) {
		if ( empty( $activity_id ) )
			$activity_id = bp_get_activity_id();

		$likes = (array) bp_activity_get_meta( $activity_id, 'bpl_likes' );
		return count( $likes );
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

		if ( ! $activity_id )
			$activity_id = BPLabs_Like::has_post_been_liked();

		// Get the Like count
		if ( $activity_id ) {
			$likes_count = BPLabs_Like::get_likes_total( $activity_id );
			$title       = '<span class="ab-icon"></span><span class="ab-label">' . sprintf( _n( '%s Like', '%s Likes', $likes_count, 'bpl' ), number_format_i18n( $likes_count ) ) . '</span>';
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
			$likes = (array) bp_activity_get_meta( $activity_id, 'bpl_likes' );
			if ( false == $likes )
				$likes = array();

			$likes[] = bp_loggedin_user_id();
			$likes   = array_unique( $likes );

			bp_activity_update_meta( $activity_id, 'bpl_likes', $likes );

			// Get Likes total
			$likes_count = count( $likes );

		// Add new Like
		} else {
			$activity_id = bp_activity_add( array(
				'component' => 'bpl_like',
				'content'   => ' ',  // To fool bp_activity_has_content()
				'item_id'   => $post_id,
				'type'      => 'bpl_like',
				'user_id'   => bp_loggedin_user_id(),
			) );

			$likes_count = 1;
		}

		// Send Likes total
		exit( sprintf( _n( '%s Like', '%s Likes', $likes_count, 'bpl' ), number_format_i18n( $likes_count ) ) );
	}

	/**
	 * Filter the activity stream item markup for Likes.
	 *
	 * @global unknown $activities_template
	 * @return string
	 * @since 1.3
	 */
	public function activity_content( $content ) {
		global $activities_template;

		// Only handle Like activity items.
		if ( 'bpl_like' != bp_get_activity_object_name() || 'bpl_like' != bp_get_activity_type() )
			return $content;

		// Get the post
		// @todo handle a missing post better
		$post = get_post( bp_get_activity_item_id() );
		if ( is_null( $post ) )
			return $content;

		// Get number of Likes that this post has
		$extra_people = BPLabs_Like::get_likes_total();

		if ( $extra_people > 1 ) {
			$extra_content  = '<p>';

			$likes = (array) bp_activity_get_meta( $activities_template->activity->id, 'bpl_likes' );
			foreach ( $likes as $user_id ) {
				if ( empty( $user_id ) )
					continue;

				// Skip past activity user ID
				if ( $user_id == $activities_template->activity->user_id )
					continue;

				$extra_content .= bp_core_fetch_avatar( array( 'item_id' => $user_id, 'type' => 'thumb', 'width' => 20, 'height' => 20, ) );
			}

			$extra_content .='</p>';
			$extra_people   = sprintf( _n( 'and %s other person', 'and %s other people', $extra_people - 1, 'bpl' ), number_format_i18n( $extra_people - 1 ) );

		} else {
			$extra_content = $extra_people = '';
		}

		// Build the content
		$content  = '<p>' . sprintf( __( '<a href="%1$s">%2$s</a> %3$s liked the article, <a href="%4$s">%5$s</a>.', 'bpl' ), esc_attr( esc_url( bp_get_activity_user_link() ) ),
			$activities_template->activity->display_name,
			$extra_people,
			esc_attr( get_permalink( $post->ID ) ),
			apply_filters( 'the_title', $post->post_title, $post->ID ) )
			. '</p>';
		$content .= $extra_content;

		// Don't truncate the activity content
		remove_filter( 'bp_get_activity_content_body', 'bp_activity_truncate_entry', 5 );

		return $content;
	}
}
new BPLabs_Like();
?>