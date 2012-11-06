<?php
/**
 * @package BP_Labs
 * @subpackage Administration
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Admin option stuff.
 *
 * @since 1.1
 */
class BPLabs_Admin {
	/**
	 * Constructor.
	 *
	 * @since 1.1
	 */
	public function __construct() {
		add_action( bp_core_admin_hook(), array( $this, 'setup_menu' ) );
	}

	/**
	 * Sets up the admin menu
	 *
	 * @since 1.1
	 */
	public function setup_menu() {
		add_action( 'load-settings_page_bplabs', array( $this, 'init' ) );
		add_options_page( __( 'BP Labs', 'bpl' ), __( 'BP Labs', 'bpl' ), 'manage_options', 'bplabs', array( $this, 'admin_page' ) );
	}

	/**
	 * Initialise common elements for all pages of the admin screen.
	 * Add metaboxes and contextual help to admin screen.
	 * Add social media button javascript to page footer.
	 *
	 * @since 1.1
	 */
	public function init() {
		if ( ! empty( $_GET['tab'] ) ) {
			if ( 'support' == $_GET['tab'] )
				$tab = 'support';

		}	else {
			$tab = 'settings';
		}

		add_screen_option( 'layout_columns', array( 'default' => 2, 'max' => 2 ) );

		// Support tab
		if ( 'support' == $tab )
			add_meta_box( 'bpl-helpushelpyou', __( 'Help Me Help You', 'bpl' ), array( $this, 'helpushelpyou'), 'settings_page_bplabs', 'side', 'high' );
		else
			add_meta_box( 'bpl-likethis', __( 'Love BP Labs?', 'bpl' ), array( $this, 'like_this_plugin' ), 'settings_page_bplabs', 'side', 'default' );

		// All tabs
		add_meta_box( 'bpl-paypal', __( 'Give Kudos', 'bpl' ), array( $this, '_paypal' ), 'settings_page_bplabs', 'side', 'default' );
		add_meta_box( 'bpl-latest', __( 'Latest News', 'bpl' ), array( $this, 'metabox_latest_news' ), 'settings_page_bplabs', 'side', 'default' );

		wp_enqueue_script( 'postbox' );
		wp_enqueue_script( 'dashboard' );
		?>

			<script type="text/javascript" src="https://apis.google.com/js/plusone.js">
			  {parsetags: 'explicit'}
			</script>
			<script type="text/javascript">gapi.plusone.go();</script>

		<?php
	}

	/**
	 * Outputs admin page HTML
	 *
	 * @global int $screen_layout_columns Number of columns shown on this admin page
	 * @since 1.1
	 */
	public function admin_page() {
		global $screen_layout_columns;

		if ( ! empty( $_GET['tab'] ) ) {
			if ( 'support' == $_GET['tab'] )
				$tab = 'support';

		}	else {
			$tab = 'settings';
		}

		$updated  = $this->maybe_save();
		$url      = network_admin_url( 'admin.php?page=bplabs' );
		$settings = BPLabs::get_settings();
	?>

		<style type="text/css">
		#bpl-helpushelpyou ul {
			list-style: disc;
			padding-left: 2em;
		}
		#bpl-likethis #___plusone_0,
		#bpl-likethis .fb {
			max-width: 49% !important;
			width: 49% !important;
		}
		#bpl-likethis .fb {
			height: 20px;
		}
		#bpl-paypal .inside {
			text-align: center;
		}
		.bpl_autosuggest,
		.bpl_quickadmin {
			margin-right: 2em;
		}
		</style>

		<div class="wrap">
			<?php screen_icon( 'options-general' ); ?>

			<h2 class="nav-tab-wrapper">
				<a href="<?php echo esc_attr( $url ); ?>"                       class="nav-tab <?php if ( 'settings' == $tab )  : ?>nav-tab-active<?php endif; ?>"><?php _e( 'BP Labs', 'bpl' );     ?></a>
				<a href="<?php echo esc_attr( $url . '&amp;tab=support' ); ?>"  class="nav-tab <?php if ( 'support'  == $tab  ) : ?>nav-tab-active<?php endif; ?>"><?php _e( 'Get Support', 'bpl' ); ?></a>
			</h2>

			<div id="poststuff" class="metabox-holder<?php echo 2 == $screen_layout_columns ? ' has-right-sidebar' : ''; ?>">
				<div id="side-info-column" class="inner-sidebar">
					<?php do_meta_boxes( 'settings_page_bplabs', 'side', $settings ); ?>
				</div>

				<div id="post-body" class="has-sidebar">
					<div id="post-body-content" class="has-sidebar-content">
						<?php
						if ( 'support' == $tab )
							$this->admin_page_support();
						else
							$this->admin_page_settings( $settings, $updated );
						?>
					</div><!-- #post-body-content -->
				</div><!-- #post-body -->

			</div><!-- #poststuff -->
		</div><!-- .wrap -->

	<?php
	}

	/**
	 * Support tab content for the admin page
	 *
	 * @since 1.1
	 */
	protected function admin_page_support() {
	?>

		<p><?php printf( __( "All of BP Labs' experiments are in <a href='%s'>beta</a>, and come with no guarantees. They work best with the latest versions of WordPress and BuddyPress.", 'bpl' ), 'http://en.wikipedia.org/wiki/Software_release_life_cycle#Beta' ); ?></p>
		<p><?php printf( __( 'If you have problems with this plugin or find a bug, please contact me by leaving a message on the <a href="%s">support forums</a>.', 'bpl' ), 'http://buddypress.org/community/groups/bp-labs/' ); ?></p>

	<?php
	}

	/**
	 * Main tab's content for the admin page
	 *
	 * @param array $settings Plugin settings (from DB)
	 * @param bool $updated Have settings been updated on the previous page submission?
	 * @since 1.1
	 */
	protected function admin_page_settings( $settings, $updated ) {
	?>
		<?php if ( $updated ) : ?>
			<div id="message" class="updated below-h2"><p><?php _e( 'Your preferences have been updated.', 'bpl' ); ?></p></div>
		<?php endif; ?>

		<form method="post" action="admin.php?page=bplabs" id="bpl-labs-form">
			<?php wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false ); ?>
			<?php wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false ); ?>
			<?php wp_nonce_field( 'bpl-admin', 'bpl-admin-nonce', false ); ?>

			<p><?php _e( 'BP Labs contains unofficial BuddyPress experiments which I am making available for testing, feedback, and to give people new shiny toys for their websites.', 'bpl' ); ?></p>

			<h4><?php _e( '@mentions autosuggest', 'bpl' ); ?></h4>
			<p><?php _e( '@mentions autosuggest requires the Activity component, and extends its @messaging feature to help you find the short name of a user. It is integrated into comments, the "What\'s New" activity status box, Private Messaging (body) and bbPress forums. To trigger the autosuggest, type an @ followed by at least one other letter.', 'bpl' ); ?></p>
			<label><?php _e( 'On', 'bpl' ); ?> <input type="radio" name="bpl_autosuggest" class="bpl_autosuggest" value="on" <?php checked( $settings['autosuggest'] ); ?>/></label>
			<label><?php _e( 'Off', 'bpl' ); ?> <input type="radio" name="bpl_autosuggest" class="bpl_autosuggest" value="off" <?php checked( $settings['autosuggest'], false ); ?>/></label>

			<h4><?php _e( 'Quick Admin', 'bpl' ); ?></h4>
			<p><?php _e( 'Quick Admin requires Groups, and affects the group directory. Designed to help speed up accessing admin screens for each group, hovering over each group in the directory will reveal links to the admin screens for that group.', 'bpl' ); ?></p>
			<label><?php _e( 'On', 'bpl' ); ?> <input type="radio" name="bpl_quickadmin" class="bpl_quickadmin" value="on" <?php checked( $settings['quickadmin'] ); ?>/></label>
			<label><?php _e( 'Off', 'bpl' ); ?> <input type="radio" name="bpl_quickadmin" class="bpl_quickadmin" value="off" <?php checked( $settings['quickadmin'], false ); ?>/></label>

			<h4><?php _e( 'Like Button', 'bpl' ); ?></h4>
			<p><?php _e( 'Adds a context-sensitive Like Button to the WordPress Toolbar. Requires WordPress 3.3+, BuddyPress 1.6+ and its Activity component.', 'bpl' ); ?></p>
			<label><?php _e( 'On', 'bpl' ); ?> <input type="radio" name="bpl_like" class="bpl_like" value="on" <?php checked( $settings['like'] ); ?>/></label>
			<label><?php _e( 'Off', 'bpl' ); ?> <input type="radio" name="bpl_like" class="bpl_like" value="off" <?php checked( $settings['like'], false ); ?>/></label>

			<p><input type="submit" class="button-primary" value="<?php _e( 'Update Settings', 'bpl' ); ?>" /></p>
		</form>

	<?php
	}

	/**
	 * Check for and handle form submission.
	 *
	 * @return bool Have settings been updated?
	 * @since 1.1
	 * @static
	 */
	protected static function maybe_save() {
		$settings = $existing_settings = BPLabs::get_settings();
		$updated  = false;

		if ( !empty( $_POST['bpl_autosuggest'] ) ) {
			if ( 'on' == $_POST['bpl_autosuggest'] )
				$settings['autosuggest'] = true;
			else
				$settings['autosuggest'] = false;
		}

		if ( !empty( $_POST['bpl_quickadmin'] ) ) {
			if ( 'on' == $_POST['bpl_quickadmin'] )
				$settings['quickadmin'] = true;
			else
				$settings['quickadmin'] = false;
		}

		if ( !empty( $_POST['bpl_like'] ) ) {
			if ( 'on' == $_POST['bpl_like'] )
				$settings['like'] = true;
			else
				$settings['like'] = false;
		}

		if ( $settings != $existing_settings ) {
			check_admin_referer( 'bpl-admin', 'bpl-admin-nonce' );
			update_site_option( 'bplabs', $settings );
			$updated = true;
		}

		return $updated;
	}

	/**
	 * Latest news metabox
	 *
	 * @param array $settings Plugin settings (from DB)
	 * @since 1.1
	 */
	public function metabox_latest_news( $settings) {
		$rss = fetch_feed( 'http://feeds.feedburner.com/BYOTOS' );
		if ( !is_wp_error( $rss ) ) {
			$content = '<ul>';
			$items = $rss->get_items( 0, $rss->get_item_quantity( 3 ) );

			foreach ( $items as $item )
				$content .= '<li><p><a href="' . esc_url( $item->get_permalink(), null, 'display' ) . '">' . apply_filters( 'bpl_metabox_latest_news', stripslashes( $item->get_title() ) ) . '</a></p></li>';

			echo $content;

		} else {
			echo '<ul><li class="rss">' . __( 'No news found at the moment', 'bpl' ) . '</li></ul>';
		}
	}

	/**
	 * "Help Me Help You" metabox
	 *
	 * @global wpdb $wpdb WordPress database object
	 * @global string $wp_version WordPress version number
	 * @global WP_Rewrite $wp_rewrite WordPress Rewrite object for creating pretty URLs
	 * @global object $wp_rewrite
	 * @param array $settings Plugin settings (from DB)
	 * @since 1.1
	 */
	public function helpushelpyou( $settings ) {
		global $wpdb, $wp_rewrite, $wp_version;

		$active_plugins = array();
		$all_plugins    = apply_filters( 'all_plugins', get_plugins() );

		foreach ( $all_plugins as $filename => $plugin ) {
			if ( 'BP Labs' != $plugin['Name'] && 'BuddyPress' != $plugin['Name'] && is_plugin_active( $filename ) )
				$active_plugins[] = $plugin['Name'] . ': ' . $plugin['Version'];
		}
		natcasesort( $active_plugins );

		if ( !$active_plugins )
			$active_plugins[] = __( 'No other plugins are active', 'bpl' );

		if ( is_multisite() ) {
			if ( is_subdomain_install() )
				$is_multisite = __( 'subdomain', 'bpl' );
			else
				$is_multisite = __( 'subdirectory', 'bpl' );

		} else {
			$is_multisite = __( 'no', 'bpl' );
		}

		if ( 1 == constant( 'BP_ROOT_BLOG' ) )
			$is_bp_root_blog = __( 'standard', 'bpl' );
		else
			$is_bp_root_blog = __( 'non-standard', 'bpl' );

		$is_bp_default_child_theme = __( 'no', 'bpl' );

	  if ( empty( $wp_rewrite->permalink_structure ) )
			$custom_permalinks = __( 'default', 'bpl' );
		else
			if ( strpos( $wp_rewrite->permalink_structure, 'index.php' ) )
				$custom_permalinks = __( 'almost', 'bpl' );
			else
				$custom_permalinks = __( 'custom', 'bpl' );
	?>

		<p><?php _e( "If you have trouble, a little information about your site goes a long way.", 'bpl' ); ?></p>

		<h4><?php _e( 'Versions', 'bpl' ); ?></h4>
		<ul>
			<li><?php printf( __( 'BP Labs: %s', 'bpl' ), BP_LABS_VERSION ); ?></li>
			<li><?php printf( __( 'BP_ROOT_BLOG: %s', 'bpl' ), $is_bp_root_blog ); ?></li>
			<li><?php printf( __( 'BuddyPress: %s', 'bpl' ), BP_VERSION ); ?></li>
			<li><?php printf( __( 'MySQL: %s', 'bpl' ), $wpdb->db_version() ); ?></li>
			<li><?php printf( __( 'Permalinks: %s', 'bpl' ), $custom_permalinks ); ?></li>
			<li><?php printf( __( 'PHP: %s', 'bpl' ), phpversion() ); ?></li>
			<li><?php printf( __( 'WordPress: %s', 'bpl' ), $wp_version ); ?></li>
			<li><?php printf( __( 'WordPress multisite: %s', 'bpl' ), $is_multisite ); ?></li>
		</ul>

		<h4><?php _e( 'Active Plugins', 'bpl' ); ?></h4>
		<ul>
			<?php foreach ( $active_plugins as $plugin ) : ?>
				<li><?php echo esc_html( $plugin ); ?></li>
			<?php endforeach; ?>
		</ul>

	<?php
	}

	/**
	 * Social media sharing metabox
	 *
	 * @since 1.1
	 * @param array $settings Plugin settings (from DB)
	 */
	public function like_this_plugin( $settings ) {
	?>

		<p><?php _e( 'Why not do any or all of the following:', 'bpl' ) ?></p>
		<ul>
			<li><p><a href="http://wordpress.org/extend/plugins/bp-labs/"><?php _e( 'Give it a five star rating on WordPress.org.', 'bpl' ) ?></a></p></li>
			<li><p><a href="http://buddypress.org/community/groups/bp-labs/reviews/"><?php _e( 'Write a review on BuddyPress.org.', 'bpl' ) ?></a></p></li>
			<li><p><a href="https://www.paypal.com/cgi-bin/webscr?cmd=_donations&amp;business=P3K7Z7NHWZ5CL&amp;lc=GB&amp;item_name=B%2eY%2eO%2eT%2eO%2eS%20%2d%20BuddyPress%20plugins&amp;currency_code=GBP&amp;bn=PP%2dDonationsBF%3abtn_donate_LG%2egif%3aNonHosted"><?php _e( 'Fund more experiments.', 'bpl' ) ?></a></p></li>
			<li>
				<g:plusone size="medium" href="http://wordpress.org/extend/plugins/bp-labs/"></g:plusone>
				<iframe class="fb" allowTransparency="true" frameborder="0" scrolling="no" src="http://www.facebook.com/plugins/like.php?href=http://wordpress.org/extend/plugins/bp-labs/&amp;send=false&amp;layout=button_count&amp;width=90&amp;show_faces=false&amp;action=recommend&amp;colorscheme=light&amp;font=arial"></iframe>
			</li>
		</ul>

	<?php
	}

	/**
	 * Paypal donate button metabox
	 *
	 * @since 1.1
	 * @param array $settings Plugin settings (from DB)
	 */ 
	function _paypal( $settings ) {
	?>

		<form action="https://www.paypal.com/cgi-bin/webscr" method="post">
			<input type="hidden" name="cmd" value="_s-xclick">
			<input type="hidden" name="encrypted" value="-----BEGIN PKCS7-----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-----END PKCS7-----">
			<input type="image" src="https://www.paypalobjects.com/en_US/GB/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="<?php esc_attr_e( 'PayPal', 'bpl' ); ?>">
			<img alt="" border="0" src="https://www.paypalobjects.com/en_GB/i/scr/pixel.gif" width="1" height="1" />
		</form>

	<?php
	}
}
new BPLabs_Admin();

// And some filters.
add_filter( 'bpl_metabox_latest_news', 'wp_kses_data', 1 );  // From an external source
add_filter( 'bpl_metabox_latest_news', 'wptexturize'     );
add_filter( 'bpl_metabox_latest_news', 'convert_chars'   );
?>