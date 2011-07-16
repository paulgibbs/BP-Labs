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
 * @since 1.2
 */
class BPLabs_Akismet extends BPLabs_Beaker {
	/**
	 * Constructor.
	 *
	 * @since 1.2
	 */
	function __construct() {
		if ( is_admin() || !defined( 'AKISMET_VERSION' ) )
			return;

		$key = get_option( 'wordpress_api_key' );
		if ( empty( $key ) && !defined( 'WPCOM_API_KEY' ) )
			return;

		add_action( 'init', array( $this, 'enqueue_script' ) );
		add_action( 'init', array( $this, 'enqueue_style' ) );

		$this->register_actions();
	}

	/**
	 * Enqueue javascript
	 *
	 * @since 1.2
	 */
	function enqueue_script() {
		$dir = WP_PLUGIN_URL . '/bp-labs/beakers/js/akismet';

		if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG )
			wp_enqueue_script( 'bplabs-akismet-js', "{$dir}.dev.js", array( 'jquery' ), '1.0', true );
		else
			wp_enqueue_script( 'bplabs-akismet-js', "{$dir}.js", array( 'jquery' ), '1.0', true );
	}

	/**
	 * Register Akismet into the activity stream
	 *
	 * @since 1.2
	 */
	protected function register_actions() {
		add_action( 'bp_after_activity_post_form', array( $this, 'add_activity_stream_nonce' ) );
		add_action( 'bp_activity_entry_comments',  array( $this, 'add_activity_stream_nonce' ) );

		// Check for spam
		add_action( 'bp_activity_after_save', array( $this, 'check_activity' ), 1, 1 );

		// Register filters to modify activity queries
		add_action( 'bp_activity_get_user_join_filter', array( $this, 'filter_sql' ), 10, 5 );
		add_action( 'bp_activity_total_activities_sql', array( $this, 'filter_sql_count' ), 10, 3 );
	}

	/**
	 * Contact Akismet to check if this is spam or ham
	 *
	 * Credit to bbPress for some of the layout ideas in this function
	 *
	 * @global string $akismet_api_host
	 * @global string $akismet_api_port
	 * @param array $activity_data Packet of information to submit to Akismet
	 * @param string $check check|submit
	 * @param string $spam spam|ham
	 * @since 1.2
	 */
	private function _ask_akismet( $activity_data, $check = 'check', $spam = 'spam' ) {
		global $akismet_api_host, $akismet_api_port;

		$query_string = $path = $response = '';

		$activity_data['blog']         = get_option( 'home' );
		$activity_data['blog_charset'] = get_option( 'blog_charset' );
		$activity_data['blog_lang']    = get_locale();
		$activity_data['referrer']     = $_SERVER['HTTP_REFERER'];
		$activity_data['user_agent']   = $_SERVER['HTTP_USER_AGENT'];
		$activity_data['user_ip']      = $_SERVER['REMOTE_ADDR'];

		// Akismet test mode
		if ( akismet_test_mode() )
			$activity_data['is_test'] = 'true';

		// Loop through _POST args and rekey strings
		foreach ( $_POST as $key => $value )
			if ( is_string( $value ) )
				$activity_data['POST_' . $key] = $value;

		// Keys to ignore
		$ignore = array( 'HTTP_COOKIE', 'HTTP_COOKIE2', 'PHP_AUTH_PW' );

		// Loop through _SERVER args and remove whitelisted keys
		foreach ( $_SERVER as $key => $value ) {

			// Key should not be ignored
			if ( !in_array( $key, $ignore ) && is_string( $value ) ) {
				$activity_data[$key] = $value;

			// Key should be ignored
			} else {
				$activity_data[$key] = '';
			}
		}

		foreach ( $activity_data as $key => $data )
			$query_string .= $key . '=' . urlencode( stripslashes( $data ) ) . '&';

		if ( 'check' == $check )
			$path = '/1.1/comment-check';
		elseif ( 'submit' == $check )
			$path = '/1.1/submit-' . $spam;

		// Send to Akismet
		$response = akismet_http_post( $query_string, $akismet_api_host, $path, $akismet_api_port );
		$activity_data['bpla_result'] = $response[1];

		return $activity_data;
	}

	/**
	 * Check if the activity item is spam or ham
	 *
	 * @global object $bp BuddyPress global settings
	 * @param BP_Activity_Activity $activity The activity item to check
	 * @return BP_Activity_Activity
	 * @see http://akismet.com/development/api/
	 * @since 1.2
	 * @todo Spam counter?
	 * @todo Auto-delete old spam?
	 */
	protected function check_activity( $activity ) {
		global $akismet_api_host, $akismet_api_port, $bp;

		$userdata = get_userdata( $activity->user_id );

		$activity_data                          = array();
		$activity_data['akismet_comment_nonce'] = 'inactive';
		$activity_data['comment_author']        = $userdata->display_name;
		$activity_data['comment_author_email']  = $userdata->user_email;
		$activity_data['comment_author_url']    = $userdata->user_url;
		$activity_data['comment_content']       = $activity->content;
		$activity_data['comment_type']          = $activity->type;
		$activity_data['permalink']             = bp_activity_get_permalink( $activity->id, $activity );
		$activity_data['user_ID']               = $userdata->ID;
		$activity_data['user_role']             = akismet_get_user_roles( $userdata->ID );

		if ( !empty( $_POST['_bpla_as_nonce'] ) )
			$activity_data['akismet_comment_nonce'] = wp_verify_nonce( $_POST['_bpla_as_nonce'], "_bpla_as_nonce_{$userdata->ID}" ) ? 'passed' : 'failed';		
		elseif ( !empty( $_POST['_bpla_as_nonce_' . $activity->id] ) )
			$activity_data['akismet_comment_nonce'] = wp_verify_nonce( $_POST["_bpla_as_nonce_{$activity->id}"], "_bpla_as_nonce_{$userdata->ID}_{$activity->id}" ) ? 'passed' : 'failed';

		// Spin the wheel; spam or ham?
		$activity_data = $this->_ask_akismet( $activity_data );

		// Spam!
		if ( 'true' == $activity_data['bpla_result'] ) {
			do_action( 'bpla_akismet_spam_caught', $activity, $activity_data );
			$this->mark_as_spam( $activity );
		}

		return apply_filters( 'bpla_akismet_check_activity', $activity );
	}

	/**
	 * Mark activity item as spam
	 *
	 * @param BP_Activity_Activity $activity
	 * @since 1.2
	 * @todo Remove from activity stream?
	 */
	protected function mark_as_spam( $activity ) {
		bp_activity_update_meta( $activity->id, 'bpla-spam', true ) {

		do_action( 'bpla_akismet_mark_as_spam', $activity );
	}

	protected function mark_as_ham( $activity ) {
	}

	/**
	 * Modify activity component queries to not return spam items, unless you're an administrator (count SQL)
	 *
	 * @global $bp BuddyPress global settings
	 * @param string $sql Original SQL
	 * @param string $where_sql SQL "Where" part
	 * @param string $sort SQL "Sort" part
	 * @return string New SQL
	 * @see BPLabs_Akismet::register_actions
	 * @since 1.2
	 */
	function filter_sql_count( $sql, $where_sql, $sort ) {
		global $bp, $wpdb;

		$sql = $wpdb->prepare( "SELECT count(a.id) FROM {$bp->activity->table_name} a, {$bp->activity->table_name_meta} m {$where_sql} AND m.activity_id = a.id AND m.meta_key != %s ORDER BY a.date_recorded {$sort}", 'bpla-spam' );
		return apply_filters( 'bpla_akismet_filter_sql_count', $sql );
	}

	/**
	 * Modify activity component queries to not return spam items, unless you're an administrator
	 *
	 * @global $bp BuddyPress global settings
	 * @param string $sql Original SQL
	 * @param string $where_sql SQL "Where" part
	 * @param string $sort SQL "Sort" part
	 * @return string New SQL
	 * @see BPLabs_Akismet::register_actions
	 * @since 1.2
	 */
	function filter_sql( $sql, $where_sql, $sort ) {
		global $bp, $wpdb;

		/*
		if ( $per_page && $page ) {
			$pag_sql = $wpdb->prepare( "LIMIT %d, %d", intval( ( $page - 1 ) * $per_page ), intval( $per_page ) );
			$activities = $wpdb->get_results( apply_filters( 'bp_activity_get_user_join_filter', $wpdb->prepare( "{$select_sql} {$from_sql} {$where_sql} ORDER BY a.date_recorded {$sort} {$pag_sql}", $select_sql, $from_sql, $where_sql, $sort, $pag_sql ) ) );
		} else {
			$activities = $wpdb->get_results( apply_filters( 'bp_activity_get_user_join_filter', $wpdb->prepare( "{$select_sql} {$from_sql} {$where_sql} ORDER BY a.date_recorded {$sort}", $select_sql, $from_sql, $where_sql, $sort ) ) );
		}
		*/

		$sql = '';
		return apply_filters( 'bpla_akismet_filter_sql', $sql );
	}

	//apply_filters( 'bp_activity_get_user_join_filter', 
	//  $wpdb->prepare( "{$select_sql} {$from_sql} {$where_sql} ORDER BY a.date_recorded {$sort}",
	//  $select_sql, $from_sql, $where_sql, $sort ) )

	/**
	 * Adds a nonce to the member profile status form, and to the reply form of each activity stream item.
	 * This is used by Akismet to help detect spam activity.
	 *
	 * @global object $bp BuddyPress global settings
	 * @see http://plugins.trac.wordpress.org/ticket/1232
	 * @since 1.2
	 */
	function add_activity_stream_nonce() {
		global $bp;

		$form_id = '_bpla_as_nonce'; 
		$value   = '_bpla_as_nonce_' . $bp->loggedin_user->id;

		if ( 'bp_activity_entry_comments' == current_filter() ) {
			$form_id .= '_' . bp_get_activity_id();
			$value   .= '_' . bp_get_activity_id();
		}

		wp_nonce_field( $value, $form_id, false );
	}
}
new BPLabs_Akismet();
?>