<?php
/**
 * @package BP_Labs
 * @subpackage Administration
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) )
	exit;

/**
 * List table class for the Activity Stream Spam admin page.
 *
 * @since 1.2
 */
class BPLabs_Akismet_List_Table extends WP_List_Table {
	/**
	 * Constructor
	 *
	 * @global $bp BuddyPress global settings
	 * @since 1.2
	 */
	function __construct() {
		global $bp;

		parent::__construct( array(
			'ajax'     => false,
			'plural'   => 'spammed_activities',
			'singular' => 'spammed_activity',
		) );

		remove_filter( 'bp_get_activity_content_body', 'bp_activity_truncate_entry', 5 );
		remove_filter( 'bp_get_activity_content_body', array( &$this, 'autoembed' ), 8 );
		remove_filter( 'bp_get_activity_content_body', array( &$bp->embed, 'run_shortcode' ), 7 );
	}

	/**
	 * Handle the display_name column
	 *
	 * @param array $item A singular item (one full row)
	 * @return Column title
	 * @see WP_List_Table::single_row_columns()
	 * @since 1.2
	 */
	function column_display_name( $item ) {
		return '<strong>' . get_avatar( $item['user_id'], '32' ) . ' ' . bp_core_get_userlink( $item['user_id'] ) . '</strong>';
	}

	/**
	 * Handle the content column, and rollover actions.
	 *
	 * @param array $item A singular item (one full row)
	 * @return Column title
	 * @see WP_List_Table::single_row_columns()
	 * @since 1.2
	 */
	function column_content( $item ) {
		$notspam_url = wp_nonce_url( add_query_arg( array( 'action' => 'notspam', 'activity_id' => $item['id'] ) ), 'bpl_akismet_ham_' . $item['id'] );

		$actions = array(
			'permalink' => sprintf( '<a href="%s">%s</a>', bp_activity_get_permalink( $item['id'], (object) $item ), __( 'Permalink', 'bpl' ) ),
			'notspam'   => sprintf( '<a href="%s">%s</a>', esc_attr( $notspam_url ), __( 'Not Spam', 'bpl' ) ),
		);

		$body    = apply_filters_ref_array( 'bp_get_activity_content_body', array( $item['content'] ) );
		$content = esc_html( strip_tags( $body ) ) . ' ' . $this->row_actions( $actions );

		return $content;
	}

	/**
	 * Handle the checkbox column
	 *
	 * @param array $item A singular item (one full row)
	 * @return string
	 * @see WP_List_Table::single_row_columns()
	 * @since 1.2
	 */
	function column_cb( $item ) {
		$cb = sprintf( '<input type="checkbox" name="activity_id[]" value="%d" />', (int) $item['id'] );
		return $cb;
	}

	/**
	 * Handle the date_recorded column
	 *
	 * @param array $item A singular item (one full row)
	 * @return Activity item date
	 * @see WP_List_Table::single_row_columns()
	 * @since 1.2
	 */
	function column_date( $item ) {
		return bp_core_time_since( strtotime( $item['date_recorded'] ) );
	}

	/**
	 * Get the table's columns' titles.
	 *
	 * @see WP_List_Table::single_row_columns()
	 * @return array Key/value pairs ('slug' => 'title')
	 * @since 1.2
	 */
	function get_columns() {
		$columns = array(
		 'cb'             => '<input name type="checkbox" />',
		 'display_name'   => __( 'Author', 'bpl' ),
		 'content'        => __( 'Message', 'bpl' ),
		 'date'           => __( 'Date', 'bpl' ),
		);

		return $columns;
	}

	/**
	 * Get bulk actions
	 *
	 * @return array Key/value pairs ('slug' => 'title')
	 * @since 1.2
	 */
	/*function get_bulk_actions() {
		$actions = array(
			// translators: Column header - name of activity items
			'notspam' => __( 'Not Spam', 'bpl' )
		);

		return $actions;
	}*/

	/**
	 * Prepare data for display
	 *
	 * @since 1.2
	 */
	function prepare_items() {
		$current_page = $this->get_pagenum();
		$per_page     = 20;

		// Modify activity queries
		add_filter( 'bp_activity_get_user_join_filter', array( 'BPLabs_Akismet', 'filter_sql_for_spam' ), 10, 6 );
		add_filter( 'bp_activity_total_activities_sql', array( 'BPLabs_Akismet', 'filter_sql_count_for_spam' ), 10, 3 );

		$this->_column_headers = array( $this->get_columns(), array(), $this->get_sortable_columns() );

		$data = bp_activity_get( array(
			'display_comments' => 'threaded',
			'page'             => $current_page,
			'per_page'         => $per_page,
			'show_hidden'      => true
		) );

		// Restore activity queries
		remove_filter( 'bp_activity_get_user_join_filter', array( 'BPLabs_Akismet', 'filter_sql_for_spam' ), 10, 6 );
		remove_filter( 'bp_activity_total_activities_sql', array( 'BPLabs_Akismet', 'filter_sql_count_for_spam' ), 10, 3 );

		$total_items = (int) $data['total'];
		$data        = $data['activities'];

		$new_data = array();
		foreach ( $data as $d )
			$new_data[] = (array) $d;

		$data = $new_data;

		// Handle pagination
		$data        = array_slice( $data, ( ( $current_page -1 ) * $per_page ), $per_page );
		$this->items = $data;

		$this->set_pagination_args( array(
			'per_page'    => $per_page,
			'total_items' => $total_items,
			'total_pages' => ceil( $total_items / $per_page )
		) );
	}
}

function bplabs_akismet_table() {
	$updated = false;

	if ( !empty( $_GET['action'] ) && !empty( $_GET['activity_id'] ) && 'notspam' == $_GET['action'] ) {
		$id = (int) $_GET['activity_id'];
		check_admin_referer( 'bpl_akismet_ham_' . $id );

		$activity = bp_activity_get_specific( array( 'activity_ids' => $id, 'show_hidden' => true ) );
		if ( !empty( $activity['activities'][0] ) ) {
			BPLabs_Akismet::mark_as_ham( $activity['activities'][0] );
			$updated = true;
		}
	}/* else {
			die(var_dump($_POST));
	}*/

	$table = new BPLabs_Akismet_List_Table();
	$table->prepare_items();
	?>

	<?php if ( $updated ) : ?>
		<div id="message" class="updated below-h2">
			<p><?php _e( 'The status of that activity item has been successfully updated (not spam).' ); ?></p>
		</div>
	<?php endif; ?>

	<form id="bpl-akismet-filter" method="post" action="<?php echo esc_attr( add_query_arg( array() ) ); ?>">
		<input type="hidden" name="page" value="<?php echo esc_attr( $_REQUEST['page'] ); ?>" />
		<?php $table->display(); ?>
	</form>

	<?php
}

bplabs_akismet_table();
?>