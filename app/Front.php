<?php
/**
 * All public facing functions
 */
namespace WpPluginHub\Run_Manager\App;
use WpPluginHub\Plugin\Base;
use WpPluginHub\Run_Manager\Helper;
/**
 * if accessed directly, exit.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @package Plugin
 * @subpackage Front
 * @author Codexpert <hi@codexpert.io>
 */
class Front extends Base {

	public $plugin;

	/**
	 * Constructor function
	 */
	public function __construct( $plugin ) {
		$this->plugin	= $plugin;
		$this->slug		= $this->plugin['TextDomain'];
		$this->name		= $this->plugin['Name'];
		$this->version	= $this->plugin['Version'];
	}

	public function head() {
		// return;
			 	$args = [
				    'status'        => ['wc-completed', 'wc-processing'], // Order statuses to filter
				    'limit'         => -1, // Fetch all orders
				];
				$orders = wc_get_orders($args);

				if (!empty($orders)) {
			   		foreach ($orders as $order) {
			   			$order_id = $order->get_id();
			   			$order_meta[$order_id] = [
						    'blood_group' 	=> $order->get_meta('_billing_blood_group'),
						    'dob' 			=> $order->get_meta('billing_dob'),
						    'emm_1' 		=> $order->get_meta('billing_emm_1'),
						    'emm_2' 		=> $order->get_meta('billing_emm_2'),
						    'nid' 			=> $order->get_meta('billing_nid'),
						    'tshirt' 		=> $order->get_meta('billing_tshirt'),
						];

					}
				}
			// Helper::pri($orders);

	}
	
	/**
	 * Enqueue JavaScripts and stylesheets
	 */
	public function enqueue_scripts() {
		$min = defined( 'RUN_MANAGER_DEBUG' ) && RUN_MANAGER_DEBUG ? '' : '.min';

		wp_enqueue_style( $this->slug, plugins_url( "/assets/css/front{$min}.css", RUN_MANAGER ), '', $this->version, 'all' );

		wp_enqueue_script( $this->slug, plugins_url( "/assets/js/front{$min}.js", RUN_MANAGER ), [ 'jquery' ], $this->version, true );
		
		$localized = [
			'ajaxurl'	=> admin_url( 'admin-ajax.php' ),
			'_wpnonce'	=> wp_create_nonce(),
		];
		wp_localize_script( $this->slug, 'RUN_MANAGER', apply_filters( "{$this->slug}-localized", $localized ) );
	}

	public function modal() {
		echo '
		<div id="run-manager-modal" style="display: none">
			<img id="run-manager-modal-loader" src="' . esc_attr( RUN_MANAGER_ASSET . '/img/loader.gif' ) . '" />
		</div>';
	}
}