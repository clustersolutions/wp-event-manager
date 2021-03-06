<?php
/*
Plugin Name: WP Event Manager

Plugin URI: https://www.wp-eventmanager.com/

Description: Manage event listings from the WordPress admin panel, and allow users to post events directly to your site.

Author: WP Event Manager

Author URI: https://www.wp-eventmanager.com

Text Domain: wp-event-manager

Domain Path: /languages

Version: 2.4

Since: 1.0

Requires WordPress Version at least: 4.1

Copyright: 2017 WP Event Manager

License: GNU General Public License v3.0

License URI: http://www.gnu.org/licenses/gpl-3.0.html

*/

// Exit if accessed directly

if ( ! defined( 'ABSPATH' ) ) {
	
	exit;
}

/**
 * WP_Event_Manager class.
 */

class WP_Event_Manager {

	/**
	 * Constructor - get the plugin hooked in and ready
	 */

	public function __construct() 
	{
		// Define constants

		define( 'EVENT_MANAGER_VERSION', '2.4' );
		define( 'EVENT_MANAGER_PLUGIN_DIR', untrailingslashit( plugin_dir_path( __FILE__ ) ) );
		define( 'EVENT_MANAGER_PLUGIN_URL', untrailingslashit( plugins_url( basename( plugin_dir_path( __FILE__ ) ), basename( __FILE__ ) ) ) );

		//Core		
		include( 'core/wp-event-manager-install.php' );
		include( 'core/wp-event-manager-post-types.php' );
		include( 'core/wp-event-manager-ajax.php' );
		include( 'core/wp-event-manager-api.php' );
		include( 'core/wp-event-manager-geocode.php' );
		include( 'core/wp-event-manager-filters.php' );
		include( 'core/wp-event-manager-cache-helper.php' );		

		//shortcodes
		include( 'shortcodes/wp-event-manager-shortcodes.php' );

		//forms
		include( 'forms/wp-event-manager-forms.php' );	

		if ( is_admin() ) {

			include( 'admin/wp-event-manager-admin.php' );

		}
		// Load language files
		require_once( 'core/wp-event-manager-wpml.php' );
		require_once( 'core/wp-event-manager-polylang.php' );
		
		
		require_once(   'core/all-in-one-seo-pack.php' );
		require_once(   'core/jetpack.php' );
		require_once(   'core/wp-event-manager-yoast.php' );
		
		// Init classes

		$this->forms      = new WP_Event_Manager_Forms();

		$this->post_types = new WP_Event_Manager_Post_Types();

		// Activation - works with symlinks

		register_activation_hook( basename( dirname( __FILE__ ) ) . '/' . basename( __FILE__ ), array( $this, 'activate' ) );

		// Switch theme

		add_action( 'after_switch_theme', array( 'WP_Event_Manager_Ajax', 'add_endpoint' ), 10 );

		add_action( 'after_switch_theme', array( $this->post_types, 'register_post_types' ), 11 );

		add_action( 'after_switch_theme', 'flush_rewrite_rules', 15 );

		// Actions

		add_action( 'after_setup_theme', array( $this, 'load_plugin_textdomain' ) );

		add_action( 'after_setup_theme', array( $this, 'include_template_functions' ), 11 );

		add_action( 'widgets_init', array( $this, 'widgets_init' ) );

		add_action( 'wp_enqueue_scripts', array( $this, 'frontend_scripts' ) );

		add_action( 'admin_init', array( $this, 'updater' ) );
		add_action( 'wp_logout', array( $this, 'cleanup_event_posting_cookies' ) );
		
		// Defaults for core actions
		add_action( 'event_manager_notify_new_user', 'wp_event_manager_notify_new_user', 10, 2 );
		
		// Schedule cron events
		self::check_schedule_crons();
	}

	/**
	 * Called on plugin activation
	 */

	public function activate() {

		WP_Event_Manager_Ajax::add_endpoint();

		$this->post_types->register_post_types();

		WP_Event_Manager_Install::install();
		
		//show notice after activating plugin
		update_option('event_manager_rating_showcase_admin_notices_dismiss','0');
		
		flush_rewrite_rules();
	}

	/**
	 * Handle Updates
	 */

	public function updater() {

		if ( version_compare( EVENT_MANAGER_VERSION, get_option( 'wp_event_manager_version' ), '>' ) ) {

			WP_Event_Manager_Install::install();

			flush_rewrite_rules();
		}
	}

	/**
	 * Localisation
	 */

	public function load_plugin_textdomain() {

		$domain = 'wp-event-manager';       

        	$locale = apply_filters('plugin_locale', get_locale(), $domain);

		load_textdomain( $domain, WP_LANG_DIR . "/wp-event-manager/".$domain."-" .$locale. ".mo" );

		load_plugin_textdomain($domain, false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	/**
	 * Load functions
	 */

	public function include_template_functions() {

		include( 'wp-event-manager-functions.php' );

		include( 'wp-event-manager-template.php' );
	}

	/**
	 * Widgets init
	 */

	public function widgets_init() {

		include_once( 'widgets/wp-event-manager-widgets.php' );
	}

	/**
	 * Register and enqueue scripts and css
	 */

	public function frontend_scripts() 
	{
		$ajax_url         = WP_Event_Manager_Ajax::get_endpoint();
		$ajax_filter_deps = array( 'jquery', 'jquery-deserialize' );

		//jQuery Chosen - vendor
		if ( apply_filters( 'event_manager_chosen_enabled', true ) ) {

			wp_register_script( 'chosen', EVENT_MANAGER_PLUGIN_URL . '/assets/js/jquery-chosen/chosen.jquery.min.js', array( 'jquery' ), '1.1.0', true );
			wp_register_script( 'wp-event-manager-term-multiselect', EVENT_MANAGER_PLUGIN_URL . '/assets/js/term-multiselect.min.js', array( 'jquery', 'chosen' ), EVENT_MANAGER_VERSION, true );
			wp_register_script( 'wp-event-manager-multiselect', EVENT_MANAGER_PLUGIN_URL . '/assets/js/multiselect.min.js', array( 'jquery', 'chosen' ), EVENT_MANAGER_VERSION, true );
			wp_enqueue_style( 'chosen', EVENT_MANAGER_PLUGIN_URL . '/assets/css/chosen.css' );

			$ajax_filter_deps[] = 'chosen';
		}
	
		//file upload - vendor
		if ( apply_filters( 'event_manager_ajax_file_upload_enabled', true ) ) {

			wp_register_script( 'jquery-iframe-transport', EVENT_MANAGER_PLUGIN_URL . '/assets/js/jquery-fileupload/jquery.iframe-transport.js', array( 'jquery' ), '1.8.3', true );
			wp_register_script( 'jquery-fileupload', EVENT_MANAGER_PLUGIN_URL . '/assets/js/jquery-fileupload/jquery.fileupload.js', array( 'jquery', 'jquery-iframe-transport', 'jquery-ui-widget' ), '5.42.3', true );
			wp_register_script( 'wp-event-manager-ajax-file-upload', EVENT_MANAGER_PLUGIN_URL . '/assets/js/ajax-file-upload.min.js', array( 'jquery', 'jquery-fileupload' ), EVENT_MANAGER_VERSION, true );

			ob_start();
			get_event_manager_template( 'form-fields/uploaded-file-html.php', array( 'name' => '', 'value' => '', 'extension' => 'jpg' ) );
			$js_field_html_img = ob_get_clean();

			ob_start();
			get_event_manager_template( 'form-fields/uploaded-file-html.php', array( 'name' => '', 'value' => '', 'extension' => 'zip' ) );
			$js_field_html = ob_get_clean();

			wp_localize_script( 'wp-event-manager-ajax-file-upload', 'event_manager_ajax_file_upload', array(
				'ajax_url'               => $ajax_url,
				'js_field_html_img'      => esc_js( str_replace( "\n", "", $js_field_html_img ) ),
				'js_field_html'          => esc_js( str_replace( "\n", "", $js_field_html ) ),
				'i18n_invalid_file_type' => __( 'Invalid file type. Accepted types:', 'wp-event-manager' )
			) );
		}

		//jQuery Deserialize - vendor
		wp_register_script( 'jquery-deserialize', EVENT_MANAGER_PLUGIN_URL . '/assets/js/jquery-deserialize/jquery.deserialize.js', array( 'jquery' ), '1.2.1', true );						
	
		//main frontend, bootstrap & bootstrap calendar style 	
		wp_register_style( 'bootstrap-main-css', EVENT_MANAGER_PLUGIN_URL . '/assets/js/bootstrap/css/bootstrap.min.css');	
		wp_register_style( 'bootstrap-datepicker-css', EVENT_MANAGER_PLUGIN_URL.'/assets/js/jquery-timepicker/bootstrap-datepicker.css');
		wp_register_style( 'jquery-timepicker-css', EVENT_MANAGER_PLUGIN_URL.'/assets/js/jquery-timepicker/jquery.timepicker.css');

		if (!wp_style_is( 'bootstrap.min.css', 'enqueued' )  && get_option('event_manager_enqueue_boostrap_frontend',true) == 1) 
		{
		    wp_enqueue_style( 'bootstrap-main-css');
		}
		
		if (!wp_style_is( 'jquery.timepicker.css', 'enqueued' )) 
		{
		    wp_enqueue_style( 'jquery-timepicker-css');
		}

		if (!wp_style_is( 'bootstrap-datepicker.css', 'enqueued' )) 
		{
		    wp_enqueue_style( 'bootstrap-datepicker-css');
		}
		wp_enqueue_style( 'wp-event-manager-frontend', EVENT_MANAGER_PLUGIN_URL . '/assets/css/frontend.min.css');	

		//bootstrap, moment and bootstrap calendar js	
		wp_register_script( 'bootstrap-main-js', EVENT_MANAGER_PLUGIN_URL . '/assets/js/bootstrap/js/bootstrap.min.js', array('jquery'), EVENT_MANAGER_VERSION, true);
		wp_register_script( 'jquery-timepicker-js', EVENT_MANAGER_PLUGIN_URL . '/assets/js/jquery-timepicker/jquery.timepicker.min.js',array('jquery'), EVENT_MANAGER_VERSION, true);
		wp_register_script( 'bootstrap-datepicker-js', EVENT_MANAGER_PLUGIN_URL . '/assets/js/jquery-timepicker/bootstrap-datepicker.js',array('jquery-timepicker-js'), EVENT_MANAGER_VERSION, true);

		if (!wp_script_is( 'bootstrap.min.js', 'enqueued' )  && get_option('event_manager_enqueue_boostrap_frontend',true) == 1) 
		{
		    wp_enqueue_script( 'bootstrap-main-js');
		}		
	
		if (!wp_script_is( 'bootstrap-datepicker.js', 'enqueued' )) 
		{
		   wp_enqueue_script( 'bootstrap-datepicker-js');	
		}
				
		//common js
		wp_register_script('wp-event-manager-common', EVENT_MANAGER_PLUGIN_URL . '/assets/js/common.min.js', array('jquery'), EVENT_MANAGER_VERSION, true);	
		wp_enqueue_script('wp-event-manager-common'); 		

		//event submission forms and validation js
		wp_register_script( 'wp-event-manager-event-submission', EVENT_MANAGER_PLUGIN_URL . '/assets/js/event-submission.min.js', array('jquery','bootstrap-datepicker-js') , EVENT_MANAGER_VERSION, true );
        wp_register_script( 'wp-event-manager-content-event-listing', EVENT_MANAGER_PLUGIN_URL . '/assets/js/content-event-listing.min.js', array('jquery','wp-event-manager-common'), EVENT_MANAGER_VERSION, true );					

		//ajax filters js
		wp_register_script( 'wp-event-manager-ajax-filters', EVENT_MANAGER_PLUGIN_URL . '/assets/js/event-ajax-filters.min.js', $ajax_filter_deps, EVENT_MANAGER_VERSION, true );
		wp_localize_script( 'wp-event-manager-ajax-filters', 'event_manager_ajax_filters', array(
			'ajax_url'                => $ajax_url,
			'is_rtl'                  => is_rtl() ? 1 : 0,
			'lang'                    => defined( 'ICL_LANGUAGE_CODE' ) ? ICL_LANGUAGE_CODE : '', // WPML workaround until this is standardized
			'i18n_load_prev_listings' => __( 'Load previous listings', 'wp-event-manager' )

		) );

		//dashboard
		wp_register_script( 'bootstrap-confirmation-js', EVENT_MANAGER_PLUGIN_URL . '/assets/js/bootstrap/bootstrap-confirmation.min.js', array('jquery','bootstrap-main-js'), EVENT_MANAGER_VERSION, true );			
		wp_register_script( 'wp-event-manager-event-dashboard', EVENT_MANAGER_PLUGIN_URL . '/assets/js/event-dashboard.min.js', array( 'jquery','bootstrap-confirmation-js' ), EVENT_MANAGER_VERSION, true );	
		wp_localize_script( 'wp-event-manager-event-dashboard', 'event_manager_event_dashboard', array(

			'i18n_btnOkLabel' => __( 'Delete', 'wp-event-manager' ),

			'i18n_btnCancelLabel' => __( 'Cancel', 'wp-event-manager' ),

			'i18n_confirm_delete' => __( 'Are you sure you want to delete this event?', 'wp-event-manager' )

		) );
		
		//registration
	    wp_register_script( 'wp-event-manager-event-registration', EVENT_MANAGER_PLUGIN_URL . '/assets/js/event-registration.min.js', array( 'jquery' ), EVENT_MANAGER_VERSION, true );

	}
	/**
	 	 * Cleanup job posting cookies.
	 	 */
	public function cleanup_event_posting_cookies() {
			if ( isset( $_COOKIE['wp-event-manager-submitting-event-id'] ) ) {
					setcookie( 'wp-event-manager-submitting-event-id', '', 0, COOKIEPATH, COOKIE_DOMAIN, false );
			}
			if ( isset( $_COOKIE['wp-event-manager-submitting-event-key'] ) ) {
					setcookie( 'wp-event-manager-submitting-event-key', '', 0, COOKIEPATH, COOKIE_DOMAIN, false );
			}
	}
	
	/**
	 * Check cron status
	 *
	 **/
	public function check_schedule_crons(){
		if ( ! wp_next_scheduled( 'event_manager_check_for_expired_events' ) ) {
			wp_schedule_event( time(), 'hourly', 'event_manager_check_for_expired_events' );
		}
		if ( ! wp_next_scheduled( 'event_manager_delete_old_previews' ) ) {
			wp_schedule_event( time(), 'daily', 'event_manager_delete_old_previews' );
		}
		if ( ! wp_next_scheduled( 'event_manager_clear_expired_transients' ) ) {
			wp_schedule_event( time(), 'twicedaily', 'event_manager_clear_expired_transients' );
		}
	}			
}
$GLOBALS['event_manager'] = new WP_Event_Manager();