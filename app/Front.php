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
// 4852 
		global $product;
		$product_id = $product->get_id();

		if ( $product_id == 4852 ) {
		 	echo '<div class="optional-product-box">';
		    echo '<label><input type="checkbox" id="add_optional_product_checkbox" /> <strong>Add a T-Shirt</strong></label>';
		    echo '</div>';

		    echo '<div id="tshirt-size-wrapper" class="tshirt-select-box">';
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
	    
	}


	function custom_save_fields_to_cart($cart_item_data, $product_id, $variation_id) {
	    if (!empty($_POST['tshirt_size'])) {
	        $cart_item_data['tshirt_size'] = sanitize_text_field($_POST['tshirt_size']);
	    }
	    if (!empty($_POST['add_optional_product'])) {
	        $cart_item_data['add_optional_product'] = intval($_POST['add_optional_product']);
	    }
	    return $cart_item_data;
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

	function custom_save_to_order_items( $item, $cart_item_key, $values, $order ) {
	    if (!empty($values['tshirt_size'])) {
	        $order->update_meta_data('T-Shirt Size', sanitize_text_field($values['tshirt_size']));
	    }

	}
	function maybe_remove_optional_product_on_main_removal($cart_item_key, $cart) {
	    $optional_product_id = get_optional_product_id(); // ID of the simple product

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

	function show_order_meta_tshirt_size($order_id) {
	    if (!$order_id) return;

	    $order = wc_get_order($order_id);

	    // Get the meta you saved
	    $tshirt_size = $order->get_meta('T-Shirt Size');

	    if ($tshirt_size) {
	        echo '<div class="woocommerce-message" style="margin-top: 20px;">';
	        echo '<h3>T-Shirt Size</h3>';
	        echo '<p><strong>' . esc_html($tshirt_size) . '</strong></p>';
	        echo '</div>';
	    }
	}	

	function show_tshirt_size_after_order_table($order) {
    // Get meta value from the order
    $tshirt_size = $order->get_meta('T-Shirt Size');

    if ($tshirt_size) {
        echo '<section class="woocommerce-order-tshirt-size" style="margin-top:30px;">';
        echo '<h3 class="woocommerce-column__title">T-Shirt Information</h3>';

        echo '<table class="shop_table woocommerce-table woocommerce-table--custom-fields">';
        echo '<tbody>';
        echo '<tr>';
        echo '<th>T-Shirt Size</th>';
        echo '<td>' . esc_html($tshirt_size) . '</td>';
        echo '</tr>';
        echo '</tbody>';
        echo '</table>';
        echo '</section>';
    }
}

}