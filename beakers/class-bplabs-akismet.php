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
		add_action( 'bp_activity_before_save', array( $this, 'check_activity_item' ), 1, 1 );

		//TODO: do 'update_post_meta' stuff
	}

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

	/**
	 * Contact Akismet to check if this is spam or ham
	 *
	 * Credit to bbPress' Akismet implementation for most of this function
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

		return $item_data;
	}

	/**
	 * todo
	 *
	 * @global object $bp BuddyPress global settings
	 * @param BP_Activity_Activity $activity The activity item to check
	 * @see http://akismet.com/development/api/
	 * @since 1.2
	 * @todo Spam counter?
	 * @todo Auto-delete old spam?
	 */
	protected function check_activity_item( $activity ) {
		global $akismet_api_host, $akismet_api_port, $bp;

		$userdata = get_userdata( $activity->user_id );

		$activity_data                          = array();
		$activity_data['akismet_comment_nonce'] = 'inactive';
		$activity_data['comment_author']        = $userdata->display_name;
		$activity_data['comment_author_email']  = $userdata->user_email;
		$activity_data['comment_author_url']    = $userdata->user_url;
		$activity_data['comment_content']       = $item->content;
		$activity_data['comment_type']          = $item->type;
		$activity_data['permalink']             = get activity item permalink();	
		$activity_data['user_ID']               = $userdata->ID;
		$activity_data['user_role']             = akismet_get_user_roles( $userdata->ID );

		if ( !empty( $_POST['_bpla_as_nonce'] ) )
			$activity_data['akismet_comment_nonce'] = wp_verify_nonce( $_POST['_bpla_as_nonce'], "_bpla_as_nonce_{$userdata->ID}" ) ? 'passed' : 'failed';		
		elseif ( !empty( $_POST['_bpla_as_nonce_' . $activity->id] ) )
			$activity_data['akismet_comment_nonce'] = wp_verify_nonce( $_POST["_bpla_as_nonce_{$activity->id}"], "_bpla_as_nonce_{$userdata->ID}_{$activity->id}" ) ? 'passed' : 'failed';

		// Spin the wheel; spam or ham?
		$activity_data = $this->ask_akismet( $activity_data );

		// Spam
		if ( 'true' == $activity_data['bpla_result'] ) {
			do_action( 'bpla_akismet_spam_caught', $activity, $activity_data );
			//$post_data['post_status'] = nasty spam status;
		}

		$this->last_activity_item = $activity;
		return $activity;
	}
}
new BPLabs_Akismet();
?>