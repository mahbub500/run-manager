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

// // 		$response = sms_send("8801677226743", "This is mahbub form run bangladesh");
// // echo $response;
// 		$order_id = 102;
// 		$order = wc_get_order( $order_id );
// 		$get_billing_phone = $order->get_billing_phone();
		
//                         $get_billing_phone = $order->get_billing_phone();
//                         if ($get_billing_phone) {
//                             // Generate random verification code
//                             $random_number = mt_rand(100000, 999999);

//                             // Save random number in order meta
//                             $order->update_meta_data('verification_code', $random_number);
//                             $order->save();

//                             // Get billing name
//                             $billing_name = $order->get_billing_first_name();
//                             $message = "Hi $billing_name, your bib is 10 and your verification code is $random_number. Thanks Run Bangladesh.";

//                             // Send SMS
//                             $response = sms_send($get_billing_phone, $message);
//                             echo $response;

//                             $order->update_meta_data('is_sms_sent', true);
//                             $order->save();
//                         }
                    

// 		// Helper::pri( $get_billing_phone );
       
// 	}
	
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
}