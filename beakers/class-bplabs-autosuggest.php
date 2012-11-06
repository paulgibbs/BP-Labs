<?php
/**
 * @package BP_Labs
 * @subpackage Mentions_Autosuggest
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Implements the @message autosuggest experiment.
 *
 * @since 1.0
 */
class BPLabs_Autosuggest extends BPLabs_Beaker {
	/**
	 * Enqueue javascript
	 *
	 * @since 1.0
	 */
	public function enqueue_script() {
		$dir = WP_PLUGIN_URL . '/bp-labs/beakers/js/jquery.mentions';

		if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG )
			wp_enqueue_script( 'bplabs-autosuggest-js', "{$dir}.dev.js", array( 'jquery' ), '1.2' );
		else
			wp_enqueue_script( 'bplabs-autosuggest-js', "{$dir}.js", array( 'jquery' ), '1.2' );

		wp_localize_script( 'bplabs-autosuggest-js', 'BPMentions', array(
			'error1'     => __( 'Sorry, an error occurred.', 'bpl' ),
			'error2'     => _x( 'Please try again.', 'an error occurred', 'bpl' ),
			'searching'  => _x( 'Searching...', 'started a search', 'bpl' )
		) );

		// This bit of javascript is what you could add directly to your theme

		$dir = WP_PLUGIN_URL . '/bp-labs/beakers/js/autosuggest';

		if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG )
			wp_enqueue_script( 'bplabs-autosuggest-theme-js', "{$dir}.dev.js", array( 'bplabs-autosuggest-js' ), '1.0' );
		else
			wp_enqueue_script( 'bplabs-autosuggest-theme-js', "{$dir}.js", array( 'bplabs-autosuggest-js' ), '1.0' );
	}

	/**
	 * Enqueue CSS
	 *
	 * @since 1.0
	 */
	public function enqueue_style() {
		$dir = WP_PLUGIN_URL . '/bp-labs/beakers/css/jquery.mentions';

		if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG )
			wp_enqueue_style( 'bplabs-autosuggest', "{$dir}.dev.css", array(), '1.0' );
		else
			wp_enqueue_style( 'bplabs-autosuggest', "{$dir}.css", array(), '1.0' );
	}

	/**
	 * Add the AJAX callback for the mention_autosuggest() method.
	 *
	 * @since 1.0
	 */
	protected function register_actions() {
		add_action( 'wp_ajax_activity_mention_autosuggest', array( 'BPLabs_Autosuggest', 'mention_autosuggest' ) );
	}

	/**
	 * AJAX receiver for the @mention autosuggest jQuery library. Performs a search on the string provided and returns matches.
	 *
	 * @global object $bp BuddyPress global settings
	 * @return mixed Either HTML or JSON. If error, "-1" for missing parameters, "0" for no matches.
	 * @see bp-labs/beakers/js/jquery.mentions.dev.js
	 * @since 1.0
	 */
	 public function mention_autosuggest() {
		global $bp;

		if ( empty( $_POST['limit'] ) || empty( $_POST['search'] ) )
			exit( '-1' );

		// Sanitise input
		$search_query = implode( '', (array) preg_replace( array( '|^https?://|i', '|\*|', '|@|' ), '', $_POST['search'] ) );
		if ( empty( $search_query ) )
			exit( '-1' );

		$args = array(
			'count_total' => false,
			'number'      => (int) $_POST['limit'],
			'search'      => "{$search_query}*"
		);

		if ( !empty( $bp->loggedin_user->id ) )
			$args['exclude'] = array( $bp->loggedin_user->id );

		if ( bp_is_username_compatibility_mode() ) {
			$args['fields']  = array( 'ID', 'user_login' );
			$args['orderby'] = 'login';

		}	else {
			$args['fields']  = array( 'ID', 'user_nicename' );
			$args['orderby'] = 'nicename';
		}

		$args = apply_filters( 'bpl_mention_autosuggest_args', $args );

		// Search users
		$user_search_results = get_users( $args );
		if ( empty( $user_search_results ) ) {

			// Return JSON
			if ( !empty( $_POST['format'] ) && 'json' == $_POST['format'] ) {
				exit( json_encode( false ) );

			// Return HTML
			} else {
				printf( '<li class="section error"><p><span>%s</span> %s</p></li>', _x( 'No matches found.', 'no search results', 'bpl' ), _x( 'Please check your spelling.', 'no search results', 'bpl' ) );
				exit();
			}
		}

		// If logged in, get user's friends
		$friend_ids = array();
		if ( !empty( $bp->loggedin_user->id ) && bp_is_active( 'friends' ) )
			$friend_ids = friends_get_friend_user_ids( $bp->loggedin_user->id );

		$search_results = array( 'friends' => array(), 'others' => array() );

		// Build results
		foreach ( (array) $user_search_results as $user ) {
			$result         = new stdClass;
			$result->avatar = bp_core_fetch_avatar( array( 'item_id' => $user->ID, 'width' => 30, 'height' => 30, 'type' => 'thumb', 'alt' => __( 'Profile picture of %s', 'bpl' ) ) );
			$result->name   = bp_core_get_user_displayname( $user->ID );

			if ( bp_is_username_compatibility_mode() )
			 	$result->id = $user->user_login;
			else
			 	$result->id = $user->user_nicename;

			if ( in_array( $user->ID, (array) $friend_ids ) )
				$search_results['friends'][] = $result;
			else
				$search_results['others'][]  = $result;
		}

		apply_filters_ref_array( 'bpl_mention_autosuggest', array( &$search_results, $args ) );

		// Return JSON
		if ( !empty( $_POST['format'] ) && 'json' == $_POST['format'] ) {
			exit( json_encode( $search_results ) );

		// Return HTML
		} else {
			$html = array();

			foreach ( $search_results as $section => $items ) {
				if ( empty( $items ) )
					continue;

				// Friends and other users
				if ( 'friends' == $section || 'others' == $section ) {
					if ( 'friends' == $section ) {
						$html[] = sprintf( '<li class="section friends"><p>%s</p></li>', __( 'Your friends', 'bpl' ) );

					} elseif ( 'others' == $section ) {
						if ( !empty( $search_results['friends'] ) )
							$html[] = sprintf( '<li class="section other"><p>%s</p></li>', sprintf( __( 'Other people on %s', 'bpl' ), get_bloginfo( 'name', 'display' ) ) );
						else
							$html[] = sprintf( '<li class="section other"><p>%s</p></li>', sprintf( __( 'People on %s', 'bpl' ), get_bloginfo( 'name', 'display' ) ) );
					}

					foreach ( $items as $item )
						$html[] = sprintf( '<li class=%s><p>%s</p></li>', esc_attr( $item->id ), $item->avatar . esc_html( $item->name ) );

				// For third-party extensions
				} else {
					$custom_section = apply_filters( 'bpl_mention_autosuggest_custom_section', false, $section, $items );

					if ( !empty( $custom_section ) )
						$html = array_merge( $html, (array) $custom_section );
				}
			}

			// Safety net
			if ( empty( $html ) )
				$html[] = sprintf( '<li class="section error"><p><span>%s</span> %s</p></li>', _x( 'No matches found.', 'no search results', 'bpl' ), _x( 'Please check your spelling.', 'no search results', 'bpl' ) );

			exit( apply_filters( 'bpl_mention_autosuggest_html', implode( PHP_EOL, $html ), $html ) );
		}
	}
}
new BPLabs_Autosuggest();
?>