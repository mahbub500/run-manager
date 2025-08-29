<?php
/**
 * Plugin Name: Run Manager
 * Description: This plugin is for managing running activities.
 * Plugin URI: https://techwithmahbub.com/
 * Author: Mahbub
 * Author URI: https://techwithmahbub.com/
 * Version: 2.0.0
 * Text Domain: run-manager
 * Domain Path: /languages
 */


namespace WpPluginHub\Run_Manager;
use WpPluginHub\Plugin\Notice;

/**
 * if accessed directly, exit.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main class for the plugin
 * @package Plugin
 * @author Codexpert <hi@codexpert.io>
 */
final class Plugin {
	
	/**
	 * Plugin instance
	 * 
	 * @access private
	 * 
	 * @var Plugin
	 */
	private static $_instance;

	/**
	 * The constructor method
	 * 
	 * @access private
	 * 
	 * @since 0.9
	 */
	private function __construct() {
		/**
		 * Includes required files
		 */
		$this->include();

		/**
		 * Defines contants
		 */
		$this->define();

		/**
		 * Runs actual hooks
		 */
		$this->hook();
	}

	/**
	 * Includes files
	 * 
	 * @access private
	 * 
	 * @uses composer
	 * @uses psr-4
	 */
	private function include() {
		require_once( dirname( __FILE__ ) . '/vendor/autoload.php' );
	}

	/**
	 * Define variables and constants
	 * 
	 * @access private
	 * 
	 * @uses get_plugin_data
	 * @uses plugin_basename
	 */
	private function define() {

		/**
		 * Define some constants
		 * 
		 * @since 0.9
		 */
		define( 'RUN_MANAGER', __FILE__ );
		define( 'RUN_MANAGER_DIR', dirname( RUN_MANAGER ) );
		define( 'RUN_MANAGER_ASSET', plugins_url( 'assets', RUN_MANAGER ) );
		define( 'RUN_MANAGER_DEBUG', apply_filters( 'run-manager_debug', true ) );

		/**
		 * The plugin data
		 * 
		 * @since 0.9
		 * @var $plugin
		 */
		$this->plugin					= get_plugin_data( RUN_MANAGER );
		$this->plugin['basename']		= plugin_basename( RUN_MANAGER );
		$this->plugin['file']			= RUN_MANAGER;
		$this->plugin['server']			= apply_filters( 'run-manager_server', 'https://codexpert.io/dashboard' );
		$this->plugin['min_php']		= '5.6';
		$this->plugin['min_wp']			= '4.0';
		$this->plugin['icon']			= RUN_MANAGER_ASSET . '/img/icon.png';
		// $this->plugin['depends']		= [ 'woocommerce/woocommerce.php' => 'WooCommerce' ];
		
	}

	/**
	 * Hooks
	 * 
	 * @access private
	 * 
	 * Executes main plugin features
	 *
	 * To add an action, use $instance->action()
	 * To apply a filter, use $instance->filter()
	 * To register a shortcode, use $instance->register()
	 * To add a hook for logged in users, use $instance->priv()
	 * To add a hook for non-logged in users, use $instance->nopriv()
	 * 
	 * @return void
	 */
	private function hook() {

		if( is_admin() ) :

			/**
			 * Admin facing hooks
			 */
			$admin = new App\Admin( $this->plugin );
			$admin->activate( 'install' );
			$admin->action( 'admin_footer', 'modal' );
			$admin->action( 'plugins_loaded', 'i18n' );
			$admin->action( 'admin_enqueue_scripts', 'enqueue_scripts' );
			$admin->action( 'admin_footer_text', 'footer_text' );
			

			/**
			 * Settings related hooks
			 */
			$settings = new App\Settings( $this->plugin );
			$settings->action( 'plugins_loaded', 'init_menu' );

			/**
			 * Renders different notices
			 * 
			 * @package Codexpert\Plugin
			 * 
			 * @author Codexpert <hi@codexpert.io>
			 */
			$notice = new Notice( $this->plugin );

		else : // ! is_admin() ?

			/**
			 * Front facing hooks
			 */
			$front = new App\Front( $this->plugin );
			$front->action( 'wp_head', 'head' );
			$front->action( 'wp_footer', 'modal' );
			$front->action( 'wp_enqueue_scripts', 'enqueue_scripts' );
			$front->action( 'woocommerce_my_account_my_orders_actions', 'download_certificate', 10, 2 );
			
			$front->action( 'woocommerce_order_status_processing', 'send_confirmation_sms', 10, 1 );
// 			$front->filter( 'woocommerce_email_subject_new_order', 'custom_new_order_email_subject', 10, 2 );
			$front->filter( 'woocommerce_add_cart_item_data', 'custom_save_fields_to_cart', 10, 3 );
			$front->action( 'woocommerce_checkout_create_order_line_item', 'custom_save_to_order_items', 20, 4 );
			$front->action( 'woocommerce_order_details_after_order_table', 'show_tshirt_size_after_order_table', 20 );
			$front->action( 'woocommerce_checkout_fields', 'hide_checkout_field_if_product_in_cart', 20 );
			$front->action( 'woocommerce_checkout_update_order_meta', 'add_tracking_meta', 20 );



			/**
			 * Shortcode related hooks
			 */
			$shortcode = new App\Shortcode( $this->plugin );
			$shortcode->register( 'my_shortcode', 'my_shortcode' );
			$shortcode->register( 'race_data_table', 'display_race_data_table' );
			$shortcode->register( 'verify_bib', 'verify_bib_shortcode' );

		endif;

		/**
		 * Cron facing hooks
		 */
		$cron = new App\Cron( $this->plugin );
		$cron->activate( 'install' );
		$cron->deactivate( 'uninstall' );
		

		

		/**
		 * Common hooks
		 *
		 * Executes on both the admin area and front area
		 */
		$common = new App\Common( $this->plugin );
		$common->action( 'run_manager_delete_old_certificates', 'delete_old_certificates' );

		/**
		 * AJAX related hooks
		 */
		$ajax = new App\AJAX( $this->plugin );
		$ajax->all( 'export_woocommerce_orders', 'woocommerce_orders_export' );
		$ajax->all( 'import_woocommerce_orders', 'import_excel_to_orders' );
		$ajax->priv( 'create_certificate', 'download_certificate' );
		$ajax->priv( 'upload_race_data', 'upload_race_data_callback' );
		$ajax->priv( 'generate-munual-certificate', 'generate_certificate' );
		$ajax->priv( 'verify_bib_action', 'verify_bib_action_callback' );
		$ajax->priv( 'generate_tshirt_size', 'generate_tshirt_size' );
		$ajax->all( 'clear_cart', 'custom_clear_cart' );
		$ajax->priv( 'save_notify_data', 'save_notify_data' );
		
		$ajax->priv( 'get_all_product_sales_count', 'product_sales_count' );

	}

	/**
	 * Cloning is forbidden.
	 * 
	 * @access public
	 */
	public function __clone() { }

	/**
	 * Unserializing instances of this class is forbidden.
	 * 
	 * @access public
	 */
	public function __wakeup() { }

	/**
	 * Instantiate the plugin
	 * 
	 * @access public
	 * 
	 * @return $_instance
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}
}

Plugin::instance();