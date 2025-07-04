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
			'optional_product_id'	=> get_optional_product_id(),
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
	    $allowed_product_id = get_optional_product_id();

	    $has_5037 = false;
	    $total_cart_count = count( $cart_items );

	    foreach ( $cart_items as $item ) {
	        if ( intval( $item['product_id'] ) === $allowed_product_id ) {
	            $has_5037 = true;
	            break;
	        }
	    }

	    if ( $has_5037 ) {
	        // If product 5037 is already in cart, allow only one additional product (max 2 total)
	        if ( $total_cart_count >= 2 ) {
	            wc_add_notice( 'You can only add one additional product along with the special product.', 'error' );
	            return false;
	        }
	    } else {
	        // If 5037 is not in cart, allow only one product
	        if ( $total_cart_count >= 1 ) {
	            wc_add_notice( 'You can only add one product to your cart at a time.', 'error' );
	            return false;
	        }
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
			$is_tshirt_in_cart = false;
			foreach ( WC()->cart->get_cart() as $cart_item ) {
		        if ( intval( $cart_item['product_id'] ) === get_optional_product_id() ) {
		            $is_tshirt_in_cart = true;
		            break;
		        }
		    }
		 	echo '<div class="optional-product-box">';
		    echo '<label><input type="checkbox" id="add_optional_product_checkbox" ' . checked( $is_tshirt_in_cart, true, false ) .' /> <strong>Add a T-Shirt</strong></label>';
		    echo '</div>';

		    echo '<div id="tshirt-size-wrapper" class="tshirt-select-box">';
		    echo '<label for="tshirt_size">Choose T-Shirt Size:</label><br>';
		    echo '<select name="tshirt_size" id="tshirt_size_select">
			    <option value="">Select Size</option>
			    <option value="XS">XS (Chest=36", Length=25")</option>
			    <option value="S">S (Chest=38", Length=26")</option>
			    <option value="M">M (Chest=40", Length=27")</option>
			    <option value="L">L (Chest=42", Length=28")</option>
			    <option value="XL">XL (Chest=44", Length=29")</option>
			    <option value="XXL">XXL (Chest=46", Length=30")</option>
			    <option value="3XL">3XL (Chest=48", Length=31")</option>
			    <option value="4XL">4XL (Chest=50", Length=32")</option>
			    <option value="3-4">3-4 Year\'s (Chest=26", Length=18")</option>
			    <option value="5-6">5-6 Year\'s (Chest=28", Length=19")</option>
			    <option value="7-8">7-8 Year\'s (Chest=30", Length=20")</option>
			    <option value="9-10">9-10 Year\'s (Chest=32", Length=21")</option>
			    <option value="11-12">11-12 Year\'s (Chest=34", Length=22")</option>
			    <option value="12-14">12-14 Year\'s (Chest=36", Length=23")</option>
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

	function hide_checkout_field_if_product_in_cart( $fields ) {
	    $desired_product_id = 4852; 

	    // Check if product is in cart
	    $product_in_cart = false;

	    foreach ( WC()->cart->get_cart() as $cart_item ) {
	        if ( $cart_item['product_id'] == $desired_product_id ) {
	            $product_in_cart = true;
	            break;
	        }
	    }

	    if ( $product_in_cart ) {
	        unset( $fields['billing']['billing_tshirt'] );
	    }

	    return $fields;
	}

}