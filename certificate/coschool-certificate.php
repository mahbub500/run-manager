<?php
/**
 * Plugin Name: CoSchool - Certificate Builder
 * Description: Drag & Drop certificate builder for CoSchool with ready-made templates included.
 * Plugin URI: https://pluggable.io/coschool/add-ons/certificate-builder
 * Author: Pluggable
 * Author URI: https://pluggable.io
 * Version: 0.9
 * Text Domain: coschool-certificate
 * Domain Path: /languages
 */

namespace Codexpert\CoSchool\Certificate;
use Codexpert\Plugin\Notice;
use Codexpert\Plugin\License;
use Codexpert\Plugin\Deactivator;

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
		define( 'COSCHOOL_CERTIFICATE', __FILE__ );
		define( 'COSCHOOL_CERTIFICATE_DIR', dirname( COSCHOOL_CERTIFICATE ) );
		define( 'COSCHOOL_CERTIFICATE_ASSET', plugins_url( 'assets', COSCHOOL_CERTIFICATE ) );
		define( 'COSCHOOL_CERTIFICATE_DEBUG', apply_filters( 'coschool-certificate_debug', true ) );

		/**
		 * The plugin data
		 * 
		 * @since 0.9
		 * @var $plugin
		 */
		$this->plugin					= get_plugin_data( COSCHOOL_CERTIFICATE );
		$this->plugin['basename']		= plugin_basename( COSCHOOL_CERTIFICATE );
		$this->plugin['file']			= COSCHOOL_CERTIFICATE;
		$this->plugin['server']			= apply_filters( 'coschool-certificate_server', 'https://my.pluggable.io' );
		$this->plugin['min_php']		= '5.6';
		$this->plugin['min_wp']			= '4.0';
		$this->plugin['doc_id']			= 1960;
		$this->plugin['icon']			= COSCHOOL_CERTIFICATE_ASSET . '/img/icon.png';
		$this->plugin['depends']		= [ 'coschool/coschool.php' => __( 'CoSchool', 'coschool' )  ];
		
		/**
		 * Pro version info
		 * 
		 * Applicable if this plugin has a pro version
		 */
		$this->plugin['item_id']		= 37;
		$this->plugin['beta']			= true;
		$this->plugin['updatable']		= true;
		$this->plugin['license_page']	= admin_url( 'admin.php?page=coschool-add-ons' );
		$this->plugin['license']		= new License( $this->plugin );

		/**
		 * Set a global variable
		 * 
		 * @global $coschool_certificate
		 */
		global $coschool_certificate;
		$coschool_certificate = $this->plugin;
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
			$admin = new Admin( $this->plugin );
			$admin->activate( 'install' );
			$admin->action( 'admin_enqueue_scripts', 'enqueue_scripts' );
			$admin->filter( "plugin_action_links_{$this->plugin['basename']}", 'action_links' );
			$admin->action( 'save_post', 'update_cache', 10, 3 );
			$admin->action( 'add_meta_boxes', 'config', 11 );
			$admin->action( 'admin_menu', 'add_menu', 11 );
			$admin->action( 'submenu_file', 'highlight_menu' );
			$admin->action( 'coschool_course_metabox', 'certificate_settings' );
			$admin->filter( 'post_updated_messages', 'certificate_updated_message' );
			$admin->filter( 'bulk_post_updated_messages', 'bulk_certificate_updated_message', 10, 2 );



			/**
			 * Settings related hooks
			 */
			$settings = new Settings( $this->plugin );
			$settings->filter( 'coschool_settings', 'settings_fields' );

			/**
			 * Renders different notices
			 * 
			 * @package Codexpert\Plugin
			 * 
			 * @author Codexpert <hi@codexpert.io>
			 */
			$notice = new Notice( $this->plugin );

			/**
			 * Shows a popup window asking why a user is deactivating the plugin
			 * 
			 * @package Codexpert\Plugin
			 * 
			 * @author Codexpert <hi@codexpert.io>
			 */
			$deactivator = new Deactivator( $this->plugin );

		else : // !is_admin() ?

			/**
			 * Front facing hooks
			 */
			$front = new Front( $this->plugin );
			$front->action( 'wp_head', 'head' );
			$front->action( 'wp_enqueue_scripts', 'enqueue_scripts' );


		endif;

		/**
		 * Common hooks
		 *
		 * Executes on both the admin area and front area
		 */
		$common = new Common( $this->plugin );
		$common->action( 'init', 'register_cpt', 11 );
		$common->action( 'manage_certificate_posts_columns', 'add_table_columns' );
		$common->action( 'manage_certificate_posts_custom_column', 'add_column_content', 10, 2 );
		$common->action( 'wp_ajax_get-certificate', 'get_certificate' );

		/**
		 * AJAX related hooks
		 */
		$ajax = new AJAX( $this->plugin );
		$ajax->priv( 'save-certificate', 'save' );
		$ajax->priv( 'download-certificate', 'download' );
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