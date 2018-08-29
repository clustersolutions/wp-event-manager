<?php/** Main Admin functions class which responsible for the entire amdin functionality and scripts loaded and files.**/if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly/** * WP_Event_Manager_Admin class. */class WP_Event_Manager_Admin {	/**	 * __construct function.	 *	 * @access public	 * @return void	 */	public function __construct() {		include_once( 'wp-event-manager-cpt.php' );		include_once( 'wp-event-manager-settings.php' );		include_once( 'wp-event-manager-writepanels.php' );		include_once( 'wp-event-manager-setup.php' );				include_once( 'wp-event-manager-field-editor.php' );		$this->settings_page = new WP_Event_Manager_Settings();		add_action( 'admin_menu', array( $this, 'admin_menu' ), 12 );		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );				add_action( 'admin_notices', array( $this,'rating_showcase_admin_notice') );				add_action( 'admin_init', array( $this, 'admin_init' ) );	}	/**	 * admin_enqueue_scripts function.	 *	 * @access public	 * @return void	 */	public function admin_enqueue_scripts() {		global $wp_scripts;		$screen = get_current_screen();			//main frontend, bootstrap & bootstrap calendar style 			wp_register_style( 'bootstrap-main-css', EVENT_MANAGER_PLUGIN_URL . '/assets/js/bootstrap/css/bootstrap.less');			wp_register_style( 'bootstrap-datepicker-css', EVENT_MANAGER_PLUGIN_URL.'/assets/js/jquery-timepicker/bootstrap-datepicker.css');		wp_register_style( 'jquery-timepicker-css', EVENT_MANAGER_PLUGIN_URL.'/assets/js/jquery-timepicker/jquery.timepicker.css');		if (!wp_style_is( 'bootstrap.min.css', 'enqueued' ) && get_option('event_manager_enqueue_boostrap_backend',true) == 1) 		{		    wp_enqueue_style( 'bootstrap-main-css');		}				if (!wp_style_is( 'jquery.timepicker.css', 'enqueued' )) 		{		    wp_enqueue_style( 'jquery-timepicker-css');		}		if (!wp_style_is( 'bootstrap-datepicker.css', 'enqueued' )) 		{		    wp_enqueue_style( 'bootstrap-datepicker-css');		}		wp_enqueue_style( 'event_manager_admin_css', EVENT_MANAGER_PLUGIN_URL . '/assets/css/backend.min.css' );			//bootstrap, moment and bootstrap calendar js					wp_register_script( 'bootstrap-main-js', EVENT_MANAGER_PLUGIN_URL . '/assets/js/bootstrap/js/bootstrap.min.js', array('jquery'), EVENT_MANAGER_VERSION, true);		wp_register_script( 'jquery-timepicker-js', EVENT_MANAGER_PLUGIN_URL . '/assets/js/jquery-timepicker/jquery.timepicker.min.js',array('jquery'), EVENT_MANAGER_VERSION, true);		wp_register_script( 'bootstrap-datepicker-js', EVENT_MANAGER_PLUGIN_URL . '/assets/js/jquery-timepicker/bootstrap-datepicker.js',array('jquery-timepicker-js'), EVENT_MANAGER_VERSION, true);		if (!wp_script_is( 'bootstrap.min.js', 'enqueued' ) && get_option('event_manager_enqueue_boostrap_backend',true) == 1) 		{		    wp_enqueue_script( 'bootstrap-main-js');		}						if (!wp_script_is( 'bootstrap-datepicker.js', 'enqueued' )) 		{		   wp_enqueue_script( 'bootstrap-datepicker-js');			}				if ( in_array( $screen->id, apply_filters( 'event_manager_admin_screen_ids', array( 'edit-event_listing', 'event_listing', 'event_listing_page_event-manager-settings', 'event_listing_page_event-manager-addons' ) ) ) ) 		{			$jquery_version = isset( $wp_scripts->registered['jquery-ui-core']->ver ) ? $wp_scripts->registered['jquery-ui-core']->ver : '1.9.2';						wp_enqueue_style( 'jquery-ui-style', '//code.jquery.com/ui/' . $jquery_version . '/themes/smoothness/jquery-ui.css', array(), $jquery_version );						wp_register_script( 'jquery-tiptip', EVENT_MANAGER_PLUGIN_URL. '/assets/js/jquery-tiptip/jquery.tipTip.min.js', array( 'jquery' ), EVENT_MANAGER_VERSION, true );				wp_enqueue_script( 'event_manager_admin_js', EVENT_MANAGER_PLUGIN_URL. '/assets/js/admin.min.js', array( 'jquery', 'jquery-tiptip','bootstrap-datepicker-js'), EVENT_MANAGER_VERSION, true );		}					wp_register_script( 'wp-event-manager-admin-settings', EVENT_MANAGER_PLUGIN_URL. '/assets/js/admin-settings.min.js', array( 'jquery' ), EVENT_MANAGER_VERSION, true );	}	/**	 * admin_menu function.	 *	 * @access public	 * @return void	 */	public function admin_menu() {		add_submenu_page( 'edit.php?post_type=event_listing', __( 'Settings', 'wp-event-manager' ), __( 'Settings', 'wp-event-manager' ), 'manage_options', 'event-manager-settings', array( $this->settings_page, 'output' ) );		if ( apply_filters( 'event_manager_show_addons_page', true ) )			add_submenu_page(  'edit.php?post_type=event_listing', __( 'WP Event Manager Add-ons', 'wp-event-manager' ),  __( 'Add-ons', 'wp-event-manager' ) , 'manage_options', 'event-manager-addons', array( $this, 'addons_page' ) );	}	/**	 * Output addons page	 */	public function addons_page() {		$addons = include( 'wp-event-manager-addons.php' );		$addons->output();	}		/**	 * Show showcase admin notice	 */	public function rating_showcase_admin_notice(){	    		$showcase = get_option('event_manager_rating_showcase_admin_notices_dismiss', 0);				if(! $showcase == true )		{    	 ?>        <div class="notice wp-event-manager-notice">		    <div class="wp-event-manager-notice-logo"><span></span></div>		    <div class="wp-event-manager-notice-message wp-wp-event-manager-fresh"><?php _e( 'We\'ve noticed you\'ve been using <strong>WP Event Manager</strong> for some time now. we hope you love it! We\'d be thrilled if you could <strong><a href="https://wordpress.org/support/plugin/wp-event-manager/reviews/" target="_blank">give us a 5 stars rating on WordPress.org!</a></strong> Don\'t forget to submit your site to <strong><a href="https://wp-eventmanager.com/showcase/" target="_blank">our showcase</a></strong> and generate more traffic from our site.', 'wp-event-manager' ); ?></div>		    <div class="wp-event-manager-notice-cta">		        <a href="https://wp-eventmanager.com/plugins/" target="_blank" class="wp-event-manager-notice-act button-primary"><?php _e('Try Great Add-Ons','wp-event-manager');?></a>		        <button class="wp-event-manager-notice-dismiss wp-event-manager-dismiss-welcome"><a href="<?php echo esc_url( add_query_arg( 'event-manager-main-admin-dismiss' ,'1' ) ) ?>"><?php _e('Dismiss','wp-event-manager');?></a></span></button>			</div>		</div>        <?php		}	  	}	  			/**		 * Ran on WP admin_init hook		 */		public function admin_init() {		    if( ! empty( $_GET[ 'event-manager-main-admin-dismiss']) ){			    update_option('event_manager_rating_showcase_admin_notices_dismiss', 1);			}					}}new WP_Event_Manager_Admin();