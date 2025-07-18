<?php
/**
 * All admin facing functions
 */
namespace WpPluginHub\Run_Manager\App;
use WpPluginHub\Plugin\Base;
use WpPluginHub\Plugin\Metabox;

/**
 * if accessed directly, exit.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @package Plugin
 * @subpackage Admin
 * @author Codexpert <hi@codexpert.io>
 */
class Admin extends Base {

	public $plugin;

	/**
	 * Constructor function
	 */
	public function __construct( $plugin ) {
		$this->plugin	= $plugin;
		$this->slug		= $this->plugin['TextDomain'];
		$this->name		= $this->plugin['Name'];
		$this->server	= $this->plugin['server'];
		$this->version	= $this->plugin['Version'];
	}

	/**
	 * Internationalization
	 */
	public function i18n() {
		load_plugin_textdomain( 'run-manager', false, RUN_MANAGER_DIR . '/languages/' );
	}

	/**
	 * Installer. Runs once when the plugin in activated.
	 *
	 * @since 1.0
	 */
	public function install() {

		if( ! get_option( 'run-manager_version' ) ){
			update_option( 'run-manager_version', $this->version );
		}
		
		if( ! get_option( 'run-manager_install_time' ) ){
			update_option( 'run-manager_install_time', time() );
		}
	}

	/**
	 * Enqueue JavaScripts and stylesheets
	 */
	public function enqueue_scripts() {
		$min = defined( 'RUN_MANAGER_DEBUG' ) && RUN_MANAGER_DEBUG ? '' : '.min';
		
		wp_enqueue_style( $this->slug, plugins_url( "/assets/css/admin{$min}.css", RUN_MANAGER ), '', time(), 'all' );
		wp_enqueue_style( $this->slug . 'toastr', 'https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.css', '', 0.1, 'all' );

		wp_enqueue_script( $this->slug, plugins_url( "/assets/js/admin{$min}.js", RUN_MANAGER ), [ 'jquery' ], time(), true );
		wp_enqueue_script( $this->slug . 'toastr', 'https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js', [ 'jquery' ], time(), true );

		$localized = [
			'ajaxurl'	=> admin_url( 'admin-ajax.php' ),
			'_wpnonce'	=> wp_create_nonce(),
		];
		wp_localize_script( $this->slug, 'RUN_MANAGER', apply_filters( "{$this->slug}-localized", $localized ) );
	}

	public function footer_text( $text ) {
		if( get_current_screen()->parent_base != $this->slug ) return $text;

		return sprintf( __( 'Built with %1$s by the folks at <a href="%2$s" target="_blank">Mahbub</a>.' ), '&hearts;', 'https://techwithmahbub.com/' );
	}

	public function modal() {
		echo '
		<div id="run-manager-modal" style="display: none">
			<img id="run-manager-modal-loader" src="' . esc_attr( RUN_MANAGER_ASSET . '/img/loader.gif' ) . '" />
		</div>';
	}
}