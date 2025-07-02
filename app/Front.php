<?php
/**
 * All public facing functions
 */
namespace WpPluginHub\Run_Manager\App;
use WpPluginHub\Plugin\Base;
use WpPluginHub\Run_Manager\Helper;
use WpPluginHub\AdnSms\AdnSmsNotification;

use Dompdf\Dompdf;
use Dompdf\Options;

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

		// $bib_id = '5001';
		// $order_id = get_order_id_by_bib_id( $bib_id );


		// if ( $order_id ) {
		//     $order = wc_get_order( $order_id );
		//     // Use the $order object as needed
		// } else {
		//     echo 'Order not found for this BIB ID.';
		// }

		
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

	public function send_confirmation_sms( $order_id ) {
	    $campain_name = Helper::get_option( 'run-manager_basic', 'campain_name' );
	    $order        = wc_get_order( $order_id );
	    $phone        = $order->get_billing_phone(); 
	    $name         = $order->get_billing_first_name(); 

	    // Get product names from the order
	    $product_names = [];
	    foreach ( $order->get_items() as $item ) {
	        $product_names[] = $item->get_name();
	    }
	    $product_list = implode( ', ', $product_names ); // Join with commas

	    // Compose the message
	    $message = "Hi {$name}, congratulations! You're registered for the {$product_list}. Order ID: {$order_id}. Thank you, Team Run Bangladesh.";

	    $requestType = 'SINGLE_SMS';   
	    $messageType = 'TEXT';   

	    if ( ! empty( $phone )) {
	        $sms = new AdnSmsNotification();
	        $sms->sendSms( $requestType, $message, $phone, $messageType );  
	    } 
	}

	function custom_new_order_email_subject( $subject, $order ) {
	    if ( ! is_a( $order, 'WC_Order' ) ) {
	        return $subject;
	    }

	    // Get the first product name
	    $items = $order->get_items();
	    $first_product_name = '';
	    foreach ( $items as $item ) {
	        $first_product_name = $item->get_name();
	        break; 
	    }

	    // Customize subject
	    $subject = "Confirmation – {$first_product_name}";
	    return $subject;
	}

	function add_optional_simple_product() {
	    $optional_product_id = 5037; // Simple product ID (e.g., Mug)

	  echo '<div class="optional-product" style="margin-bottom:15px;">';
	    echo '<label><input type="checkbox" id="add_optional_product_checkbox" name="add_optional_product" value="' . esc_attr($optional_product_id) . '" /> Add a Mug for ৳100</label>';
	    echo '</div>';

	    echo '<div id="tshirt-size-wrapper" style="display:none; margin-bottom:15px;">';
	    echo '<label for="tshirt_size">Choose T-Shirt Size:</label><br>';
	    echo '<select name="tshirt_size" id="tshirt_size_select">
	            <option value="">Select Size</option>
	            <option value="S">Small</option>
	            <option value="M">Medium</option>
	            <option value="L">Large</option>
	            <option value="XL">Extra Large</option>
	          </select>';
	    echo '</div>';
	}

	function maybe_add_optional_product($cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data) {
	    if (isset($_POST['add_optional_product']) && !empty($_POST['add_optional_product'])) {
	        $optional_product_id = intval($_POST['add_optional_product']);

	        // Only add if not already in cart
	        $found = false;
	        foreach (WC()->cart->get_cart() as $cart_item) {
	            if ($cart_item['product_id'] == $optional_product_id) {
	                $found = true;
	                break;
	            }
	        }

	        if (! $found) {
	            WC()->cart->add_to_cart($optional_product_id);
	        }
	    }
	}

	function maybe_remove_optional_product_on_main_removal($cart_item_key, $cart) {
	    $optional_product_id = 5037; // ID of the simple product

	    $has_main_product = false;

	    foreach ($cart->get_cart() as $item) {
	        if ($item['variation_id']) { // assuming main product is variable
	            $has_main_product = true;
	            break;
	        }
	    }

	    // If no more main products, remove optional
	    if (!$has_main_product) {
	        foreach ($cart->get_cart() as $cart_item_key => $item) {
	            if ($item['product_id'] == $optional_product_id) {
	                WC()->cart->remove_cart_item($cart_item_key);
	            }
	        }
	    }
	}

}