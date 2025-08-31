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

	public function download_certificate( $actions, $order ) {
	    $certificate_meta = $order->get_meta( '_is_certified' );

	    if ( $certificate_meta ) {
	        $actions['download_certificate'] = array(
	            'url'  => wp_nonce_url(
	                admin_url( 'admin-ajax.php?action=download_certificate&order_id=' . $order->get_id() ),
	                'download_certificate'
	            ),
	            'name' => '📥 ' . __( 'Certificate', 'run-manager' ),

	        );
	    }

	    return $actions;
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

	function custom_save_fields_to_cart($cart_item_data, $product_id, $variation_id) {
	    if (!empty($_POST['tshirt_size'])) {
	        $cart_item_data['tshirt_size'] = sanitize_text_field($_POST['tshirt_size']);
	    }
	    if (!empty($_POST['add_optional_product'])) {
	        $cart_item_data['add_optional_product'] = intval($_POST['add_optional_product']);
	    }
	    return $cart_item_data;
	}

	

	function custom_save_to_order_items( $item, $cart_item_key, $values, $order ) {
	    if (!empty($values['tshirt_size'])) {
	        $order->update_meta_data('T-Shirt Size', sanitize_text_field($values['tshirt_size']));
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

	public function add_tracking_meta( $order_id ) {
	     if ( ! $order_id ) {
	        return;
	    }

	    $order = wc_get_order( $order_id );
	    if ( ! $order ) {
	        return;
	    }

	    $events     = get_option( 'rm_event_names', [] );
	    $last_event = ! empty( $events ) ? end( $events ) : '';

	    // 🚫 If no last event, do nothing
	    if ( empty( $last_event ) ) {
	        return;
	    }

	    // ✅ Always add/update meta
	    $order->update_meta_data( 'rm_event_key', $last_event );
	    $order->save();
	}

	public function cart_validation( $passed, $product_id, $quantity, $variation_id = null, $variations = null ){

		$restricted = get_post_meta($product_id, '_restriction_enabled', true);

	    if($restricted == '1') {
	        // Check if cart already has other products
	        foreach(WC()->cart->get_cart() as $cart_item) {
	            if($cart_item['product_id'] != $product_id) {
	                wc_add_notice('You can only purchase this product alone.', 'error');
	                return false;
	            }
	        }

	        // Check if quantity > 1
	        if($quantity > 1) {
	            wc_add_notice('You can only purchase one of this product.', 'error');
	            return false;
	        }
	    } else {
	        // If restricted product already in cart, block adding other products
	        foreach(WC()->cart->get_cart() as $cart_item) {
	            $cart_product_id = $cart_item['product_id'];
	            if(get_post_meta($cart_product_id, '_restriction_enabled', true) == '1') {
	                wc_add_notice('You cannot add other products because a restricted product is in the cart.', 'error');
	                return false;
	            }
	        }
	    }

	    return $passed;
	}

	public function item_quantity( $quantity, $cart_item_key, $cart_item ){
		if(get_post_meta($cart_item['product_id'], '_restriction_enabled', true) == '1') {
	        $quantity = 1;
	    }
	    return $quantity;
	}
}