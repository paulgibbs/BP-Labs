<?php
/**
 * @package BP_Labs
 * @subpackage Administration
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) )
	exit;

/**
 * List table class for the Activity Akismet admin page.
 *
 * @since 1.2
 */
class BPLabs_Akismet_List_Table extends WP_List_Table {
	/**
	 * Constructor
	 *
	 * @since 1.2
	 */
	function __construct() {
		parent::__construct( array(
			'ajax'     => false,
			'plural'   => 'spammed_activities',
			'singular' => 'spammed_activity',
		) );
	}

	/**
	 * Handle the XXXXXX column, and rollover actions.
	 *
	 * @param array $item A singular item (one full row)
	 * @return Column title
	 * @see WP_List_Table::single_row_columns()
	 * @since 1.2
	 */
	function column_type( $item ){
		$actions = array(
			'edit' => sprintf( '<a href="?page=%s&action=edit&SOMEPARAMETER=%d">Edit</a>', esc_attr( $_REQUEST['page'] ), (int) $item['id'] )
		);

		$title = sprintf( '%s <span style="color:silver">(id:%d)</span>%s', esc_html( $item['type'] ), (int) $item['id'], $this->row_actions( $actions ) );
		return $title;
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
		$cb = sprintf( '<input type="checkbox" name="%s[]" value="%s" />', esc_attr( $this->_args['singular'] ), (int) $item['id'] );
		return $cb;
	}

	/**
	 * Get the table's columns' titles.
	 *
	 * @see WP_List_Table::single_row_columns()
	 * @return array Key/value pairs ('slug' => 'title')
	 * @since 1.2
	 */
	function get_columns(){
		$columns = array(
		 'cb'    => '<input type="checkbox" />',

			/* translators: Column header - name of activity items */
		 'type' => __( 'typetype', 'bpl' )
		);

		return $columns;
	}

	/**
	 * Get the sortable columns
	 *
	 * This returns an array where the key is the column that needs to be sortable, and the value is db column to 
	 * sort by. The value is a column name from the database, not the list table. Actual sorting is handled in prepare_items().
	 * 
	 * @return array Key/value pairs ('slugs' => array( 'data_values', bool ))
	 * @see BPLabs_Akismet_List_Table::prepare_items()
	 * @since 1.2
	 */
	 function get_sortable_columns() {
		$sortable_columns = array(
			'type' => array( 'typetypetype', true )  // true means already sorted
		);

		return $sortable_columns;
	}

	/**
	 * Get bulk actions
	 *
	 * @return array Key/value pairs ('slug' => 'title')
	 * @since 1.2
	 */
		function get_bulk_actions() {
			$actions = array(
				/* translators: Column header - name of activity items */
				'delete' => __( 'Delete', 'bpl' )
			);

			return $actions;
		}

	/**
	 * Prepare data for display
	 *
	 * @since 1.2
	 */
	function prepare_items() {
		$current_page = $this->get_pagenum();
		$per_page     = 20;

		// Modify activity queries
		remove_filter( 'bp_activity_get_user_join_filter', array( 'BPLabs_Akismet', 'filter_sql' ), 10, 6 );
		remove_filter( 'bp_activity_total_activities_sql', array( 'BPLabs_Akismet', 'filter_sql_count' ), 10, 3 );
		add_filter( 'bp_activity_get_user_join_filter', array( 'BPLabs_Akismet', 'filter_sql_for_spam' ), 10, 6 );
		add_filter( 'bp_activity_total_activities_sql', array( 'BPLabs_Akismet', 'filter_sql_count_for_spam' ), 10, 3 );

		$this->_column_headers = array( $this->get_columns(), array(), $this->get_sortable_columns() );
		$data = bp_activity_get( array(
			'display_comments' => 'threaded',
			'page'             => $current_page,
			'per_page'         => $per_page,
			'show_hidden'      => true
		) );

		$total_items = (int) $data['total'];
		$data        = $data['activities'];

		$new_data = array();
		foreach ( $data as $d )
			$new_data[] = (array) $d;

		$data = $new_data;

		/*
[0]=> string(2) "id"
[1]=> string(7) "user_id"
[2]=> string(9) "component"
[3]=> string(4) "type"
[4]=> string(6) "action"
[5]=> string(7) "content"
[6]=> string(12) "primary_link"
[7]=> string(7) "item_id"
[8]=> string(17) "secondary_item_id"
[9]=> string(13) "date_recorded"
[10]=> string(13) "hide_sitewide"
[11]=> string(9) "mptt_left"
[12]=> string(10) "mptt_right"
[13]=> string(10) "user_email"
[14]=> string(13) "user_nicename"
[15]=> string(10) "user_login"
[16]=> string(12) "display_name"
[17]=> string(13) "user_fullname"
		*/

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
	$table = new BPLabs_Akismet_List_Table();
	$table->prepare_items();
	?>

	<div class="wrap">
		<div id="icon-users" class="icon32"><br /></div>
		<h2>List Table Test</h2>

		<div style="background:#ECECEC;border:1px solid #CCC;padding:0 10px;margin-top:5px;border-radius:5px;-moz-border-radius:5px;-webkit-border-radius:5px;">
			<p>Additional class details are available on the <a href="http://codex.wordpress.org/Class_Reference/WP_List_Table" target="_blank" style="text-decoration:none;">WordPress Codex</a>.</p>
		</div>

		<form id="movies-filter" method="get">
		<input type="hidden" name="page" value="<?php echo esc_attr( (int) $_REQUEST['page'] ); ?>" />
		<?php $table->display(); ?>
		</form>
	</div>

	<?php
}

bplabs_akismet_table();
?>