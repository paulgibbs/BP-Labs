<?php
/**
 * @package BP_Labs
 * @subpackage Akismet
 *
 * Credit to WordPress core's Akismet plugin for its implementation example.
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) )
	exit;

/**
 * Implements Activity Stream Spam
 *
 * @since 1.2
 */
class BPLabs_Akismet extends BPLabs_Beaker {
	protected $last_spam_id = 0;

	/**
	 * Constructor.
	 *
	 * @since 1.2
	 */
	public function __construct() {
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
	public function enqueue_script() {
		$dir = WP_PLUGIN_URL . '/bp-labs/beakers/js/akismet';

		if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG )
			wp_enqueue_script( 'bplabs-akismet-js', "{$dir}.dev.js", array( 'jquery' ), '1.0', true );
		else
			wp_enqueue_script( 'bplabs-akismet-js', "{$dir}.js", array( 'jquery' ), '1.0', true );
	}

	/**
	 * Hook Akismet into the activity stream
	 *
	 * @since 1.2
	 */
	protected function register_actions() {
		add_action( 'bp_after_activity_post_form', array( $this, 'add_activity_stream_nonce' ) );
		add_action( 'bp_activity_entry_comments',  array( $this, 'add_activity_stream_nonce' ) );

		// Check for spam
		add_action( 'bp_activity_after_save', array( $this, 'check_activity' ), 1, 1 );

		// Tidy up member's latest (activity) update
		add_action( 'bp_activity_posted_update', array( $this, 'check_member_activity_update' ), 1, 3 );

		// Modify activity queries
		add_filter( 'bp_activity_get_user_join_filter', array( 'BPLabs_Akismet', 'filter_sql' ), 10, 6 );
		add_filter( 'bp_activity_total_activities_sql', array( 'BPLabs_Akismet', 'filter_sql_count' ), 10, 3 );
	}

	/**
	 * Contact Akismet to check if this is spam or ham
	 *
	 * Credit to bbPress for the idea of splitting this part of the method out of check_activity(),
	 * and to the WordPress core Akismet plugin for alot of the code.
	 *
	 * @global string $akismet_api_host
	 * @global string $akismet_api_port
	 * @param array $activity_data Packet of information to submit to Akismet
	 * @param string $check check|submit
	 * @param string $spam spam|ham
	 * @since 1.2
	 */
	private function ask_akismet( $activity_data, $check = 'check', $spam = 'spam' ) {
		global $akismet_api_host, $akismet_api_port;

		$query_string = $path = $response = '';

		$activity_data['blog']         = get_option( 'home' );
		$activity_data['blog_charset'] = get_option( 'blog_charset' );
		$activity_data['blog_lang']    = get_locale();
		$activity_data['referrer']     = $_SERVER['HTTP_REFERER'];
		$activity_data['user_agent']   = $_SERVER['HTTP_USER_AGENT'];
		$activity_data['user_ip']      = $_SERVER['REMOTE_ADDR'];

		if ( akismet_test_mode() )
			$activity_data['is_test'] = 'true';

		// Loop through _POST args and rekey strings
		foreach ( $_POST as $key => $value )
			if ( is_string( $value ) && 'cookie' != $key )
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
	 * @global unknown $akismet_api_host
	 * @global unknown $akismet_api_port
	 * @global object $bp BuddyPress global settings
	 * @param BP_Activity_Activity $activity The activity item to check
	 * @see http://akismet.com/development/api/
	 * @since 1.2
	 * @todo Spam counter?
	 * @todo Auto-delete old spam?
	 */
	public function check_activity( $activity ) {
		global $akismet_api_host, $akismet_api_port, $bp;

		$this->last_spam_id = 0;
		$userdata           = get_userdata( $activity->user_id );

		$activity_data                          = array();
		$activity_data['akismet_comment_nonce'] = 'inactive';
		$activity_data['comment_author']        = $userdata->display_name;  // 'viagra-test-123'
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

		// Check with Akismet to see if this is spam
		$activity_data = $this->ask_akismet( $activity_data );

		// Spam
		if ( 'true' == $activity_data['bpla_result'] ) {
			$this->mark_as_spam( $activity );

			do_action_ref_array( 'bpla_akismet_spam_caught', array( &$activity, $activity_data ) );
		}
	}

	/**
	 * Check if the member's latest (activity) update to see if it's the item that was been marked as spam.
	 *
	 * This can't be done in BPLabs_Akismet::check_activity() due to DTheme's AJAX implementation; see bp_dtheme_post_update().
	 *
	 * @param string $content
	 * @param int $user_id
	 * @param int $activity_id
	 * @since 1.2
	 */
	public function check_member_activity_update( $content, $user_id, $activity_id ) {
		if ( !$this->last_spam_id || $activity_id != $this->last_spam_id )
			return;

		bp_delete_user_meta( $user_id, 'bp_latest_update' );
	}

	/**
	 * Mark activity item as spam
	 *
	 * @param BP_Activity_Activity $activity
	 * @since 1.2
	 * @todo Remove from activity stream?
	 */
	public function mark_as_spam( $activity ) {
		$this->last_spam_id = $activity->id;

		bp_activity_update_meta( $activity->id, 'bpla_spam', true );
		bp_delete_user_meta( $activity->user_id, 'bp_latest_update' );

		do_action( 'bpla_akismet_mark_as_spam', $activity );
	}

	/**
	 * Mark activity item as ham
	 *
	 * @param BP_Activity_Activity $activity
	 * @since 1.2
	 * @todo Add to activity stream?
	 */
	public function mark_as_ham( $activity ) {
		bp_activity_delete_meta( $activity->id, 'bpla_spam' );

		do_action( 'bpla_akismet_mark_as_ham', $activity );
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
	public function filter_sql_count( $sql, $where_sql, $sort ) {
		global $bp, $wpdb;

		if ( !empty( $bp->loggedin_user->is_super_admin ) && $bp->loggedin_user->is_super_admin )
			return $sql;

		$sql = $wpdb->prepare( "SELECT count(a.id) FROM {$bp->activity->table_name} a LEFT JOIN {$bp->activity->table_name_meta} m ON m.activity_id = a.id {$where_sql} AND (m.meta_key != %s OR m.meta_key IS NULL) ORDER BY a.date_recorded {$sort}", 'bpla_spam' );
		return apply_filters( 'bpla_akismet_filter_sql_count', $sql );
	}

	/**
	 * Modify activity component queries to not return spam items, unless you're an administrator
	 *
	 * @global $bp BuddyPress global settings
	 * @param string $sql Original SQL
	 * @param string $select_sql
	 * @param string $from_sql
	 * @param string $where_sql
	 * @param string $sort
	 * @param string $pag_sql
	 * @return string New SQL
	 * @see BPLabs_Akismet::register_actions
	 * @since 1.2
	 */
	public function filter_sql( $sql, $select_sql, $from_sql, $where_sql, $sort, $pag_sql='' ) {
		global $bp, $wpdb;

		if ( !empty( $bp->loggedin_user->is_super_admin ) && $bp->loggedin_user->is_super_admin )
			return $sql;

		$from_sql  .= " LEFT JOIN {$bp->activity->table_name_meta} m ON m.activity_id = a.id";
		$where_sql .= $wpdb->prepare( " AND (m.meta_key != %s OR m.meta_key IS NULL)", 'bpla_spam' );

		if ( !empty( $pag_sql ) )
			$sql = $wpdb->prepare( "{$select_sql} {$from_sql} {$where_sql} ORDER BY a.date_recorded {$sort} {$pag_sql}" );
		else
			$sql = $wpdb->prepare( "{$select_sql} {$from_sql} {$where_sql} ORDER BY a.date_recorded {$sort}" );

		return apply_filters( 'bpla_akismet_filter_sql', $sql );
	}

	/**
	 * Modify activity component queries to return spam items
	 *
	 * @global $bp BuddyPress global settings
	 * @param string $sql Original SQL
	 * @param string $where_sql SQL "Where" part
	 * @param string $sort SQL "Sort" part
	 * @return string New SQL
	 * @since 1.2
	 */
	public function filter_sql_count_for_spam( $sql, $where_sql, $sort ) {
		global $bp, $wpdb;

		$sql = $wpdb->prepare( "SELECT count(a.id) FROM {$bp->activity->table_name} a LEFT JOIN {$bp->activity->table_name_meta} m ON m.activity_id = a.id {$where_sql} AND m.meta_key = %s ORDER BY a.date_recorded {$sort}", 'bpla_spam' );
		return apply_filters( 'bpla_akismet_filter_sql_count_for_spam', $sql );
	}

	/**
	 * Modify activity component queries to return spam items
	 *
	 * @global $bp BuddyPress global settings
	 * @param string $sql Original SQL
	 * @param string $select_sql
	 * @param string $from_sql
	 * @param string $where_sql
	 * @param string $sort
	 * @param string $pag_sql
	 * @return string New SQL
	 * @since 1.2
	 */
	public function filter_sql_for_spam( $sql, $select_sql, $from_sql, $where_sql, $sort, $pag_sql='' ) {
		global $bp, $wpdb;

		$from_sql  .= " LEFT JOIN {$bp->activity->table_name_meta} m ON m.activity_id = a.id";
		$where_sql .= $wpdb->prepare( " AND m.meta_key = %s", 'bpla_spam' );

		if ( !empty( $pag_sql ) )
			$sql = $wpdb->prepare( "{$select_sql} {$from_sql} {$where_sql} ORDER BY a.date_recorded {$sort} {$pag_sql}" );
		else
			$sql = $wpdb->prepare( "{$select_sql} {$from_sql} {$where_sql} ORDER BY a.date_recorded {$sort}" );

		return apply_filters( 'bpla_akismet_filter_sql_for_spam', $sql );
	}

	/**
	 * Adds a nonce to the member profile status form, and to the reply form of each activity stream item.
	 * This is used by Akismet to help detect spam activity.
	 *
	 * @global object $bp BuddyPress global settings
	 * @see http://plugins.trac.wordpress.org/ticket/1232
	 * @since 1.2
	 * @todo Replace $bp access with wrapper function in BP 1.5
	 */
	public function add_activity_stream_nonce() {
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