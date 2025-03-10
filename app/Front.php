<?php
/**
 * All public facing functions
 */
namespace WpPluginHub\Run_Manager\App;
use WpPluginHub\Plugin\Base;
use WpPluginHub\Run_Manager\Helper;

use PhpOffice\PhpSpreadsheet\IOFactory;
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

		// Helper::pri( Helper::get_option( 'run-manager_save_message', 'email_text' ) );

	}

	
	/**
	 * Enqueue JavaScripts and stylesheets
	 */
	public function enqueue_scripts() {
		$min = defined( 'RUN_MANAGER_DEBUG' ) && RUN_MANAGER_DEBUG ? '' : '.min';

		wp_enqueue_style( $this->slug, plugins_url( "/assets/css/front{$min}.css", RUN_MANAGER ), '', $this->version, 'all' );

		wp_enqueue_script( $this->slug, plugins_url( "/assets/js/front{$min}.js", RUN_MANAGER ), [ 'jquery' ], time(), true );
		
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

	public function download_certificate( $actions, $order ){
		$certificate_meta = $order->get_meta( 'is_certified' );

		if ( $certificate_meta ) {
			$actions['download_certificate'] = array(
		        'url'  => '#', 
		        'name' => __( 'Get Certified', 'run-manager' ),
		        
		    );
		}


		
		return $actions;
	}
	

	public function restrict_multiple_additions( $passed, $product_id, $quantity ) {
	    $cart_items = WC()->cart->get_cart();

	    if ( count( $cart_items ) > 0 ) {
	        wc_add_notice( 'You can only add one product to your cart at a time. Please remove the existing product first.', 'error' );
	        return false; 
	    }

		    return $passed;
	}



	function send_order_confirmation_sms($order_id) {
	    $order = wc_get_order($order_id);
	    $phone = $order->get_billing_phone(); // Get customer phone number
	    $message = "Thank you for your order #{$order_id}. Your order is now processing.";

	    // Ensure phone number is in correct format
	    if (!empty($phone)) {
	   
	        sms_send($phone, $message);
	        error_log("SMS sent to {$phone} for Order #{$order_id}");
	    } else {
	        error_log("No phone number found for Order #{$order_id}");
	    }
	}



}