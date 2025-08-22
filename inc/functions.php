<?php
use WpPluginHub\AdnSms\AdnSmsNotification;
use WpPluginHub\Run_Manager\Helper;

if( ! function_exists( 'get_plugin_data' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
}



/**
 * Gets the site's base URL
 * 
 * @uses get_bloginfo()
 * 
 * @return string $url the site URL
 */
if( ! function_exists( 'runm_site_url' ) ) :
function runm_site_url() {
	$url = get_bloginfo( 'url' );

	return $url;
}
endif;

if ( ! function_exists( 'mask_number' ) ) {
	function mask_number( $number ) {
	    $start 		= substr( $number, 0, 3 ); 
	    $end 		= substr( $number, -3 ); 
	    $masked 	= $start . str_repeat( '*', 5 ) . $end;
	    return $masked;
	}
}

/**
 * Sends an email to the customer with the certification number.
 */
if ( function_exists( 'send_certificate_email' ) ) {
	function send_certificate_email( $email, $certificate_number, $order_id ) {
	    $subject = "Your Certification Number for Order #$order_id";
	    $message = "Dear Customer,\n\nYour certification number for Order #$order_id is: $certificate_number.\n\nThank you!";
	    $headers = ['Content-Type: text/plain; charset=UTF-8'];

	    wp_mail($email, $subject, $message, $headers);
	}
}

if ( ! function_exists( 'send_sms_to_phone' ) ) {
	function send_sms_to_phone( $phone, $message ) {
	    if ( empty( $phone ) || empty( $message ) ) {
	        return false;
	    }

	    $requestType  = 'SINGLE_SMS';
	    $messageType  = 'TEXT';

	    $sms = new AdnSmsNotification();

	    try {
	       
	        ob_start();
	        $response = $sms->sendSms( $requestType, $message, $phone, $messageType );
	        ob_end_clean(); // Clean and discard output
	        
	        return true;
	    } catch ( Exception $e ) {
	        error_log( 'SMS sending failed: ' . $e->getMessage() );
	        return false;
	    }
	}
}


if ( ! function_exists( 'wc_get_order_by_bib_id' ) ) {
    function wc_get_order_by_bib_id( $bib_number ) {
        $args = array(
            'post_type'   => 'shop_order',
            'post_status' => 'any',
            'meta_query'  => array(
                array(
                    'key'     => 'bib_id',
                    'value'   => $bib_number,
                    'compare' => '=',
                ),
            ),
            'limit' => 1,
        );

        $orders = wc_get_orders( $args );

        if ( ! empty( $orders ) && is_a( $orders[0], 'WC_Order' ) ) {
            return $orders[0]->get_id(); // ✅ FIX: use get_id() instead of ->ID
        }

        return null;
    }
}


if ( ! function_exists( 'get_woocommerce_product_sales' ) ) {
	function get_woocommerce_product_sales() {
	    $args = array(
	        'post_type'      => 'product',
	        'posts_per_page' => -1,
	        'post_status'    => 'publish',
	    );

	    $products = get_posts($args);

	    echo '<h2>Product Sales Report</h2>';
	    echo '<table border="1" cellpadding="5" cellspacing="0">';
	    echo '<tr><th>Product Name</th><th>Sales Count</th></tr>';

	    foreach ($products as $product) {
	        $product_id = $product->ID;
	        $sales_count = get_post_meta($product_id, 'total_sales', true);
	        echo '<tr>';
	        echo '<td>' . get_the_title($product_id) . '</td>';
	        echo '<td>' . ($sales_count ? $sales_count : 0) . '</td>';
	        echo '</tr>';
	    }

	    echo '</table>';
	}
}

if ( ! function_exists( 'clean_phone_number' ) ) {
	function clean_phone_number($number) {
    // Remove all non-digit characters
    $number = preg_replace('/\D+/', '', $number);

    // Remove leading +88 or 88
    $number = preg_replace('/^88/', '', $number);

    return $number;
}


}

function display_product_sales_count() {
    global $wpdb;

    $results = $wpdb->get_results("
	    SELECT oi.order_item_name, COUNT(*) as item_count
	    FROM {$wpdb->prefix}woocommerce_order_items oi
	    JOIN {$wpdb->prefix}wc_orders o 
	        ON oi.order_id = o.id
	    WHERE o.status IN ('wc-processing', 'wc-completed')
	        AND oi.order_item_type = 'line_item'  -- ✅ This line ensures only products
	    GROUP BY oi.order_item_name
	    ORDER BY item_count DESC
	");


    if (empty($results)) {
        echo 'No sales data found.';
        return;
    }

    // Calculate total sales count
    $total_sales = array_sum(array_column($results, 'item_count'));

    echo '<table border="1" cellspacing="0" cellpadding="5">';
    echo '<tr><th>Product Name</th><th>Sales Count</th></tr>';

    foreach ($results as $row) {
        echo '<tr>';
        echo '<td>' . esc_html($row->order_item_name) . '</td>';
        echo '<td>' . esc_html($row->item_count) . '</td>';
        echo '</tr>';
    }

    // Add total sales count row
    echo '<tr>';
    echo '<td><strong>Total Sales:</strong></td>';
    echo '<td><strong>' . esc_html($total_sales) . '</strong></td>';
    echo '</tr>';

    echo '</table>';
}

if ( ! function_exists( 'notify_placeholders' ) ) {

    /**
     * Get placeholder list or replace placeholders in content
     *
     * @param string $content Optional. Content to replace placeholders.
     * @param array  $data    Optional. Data to replace placeholders.
     * @return array|string
     */
    function notify_placeholders( $content = '', $data = [] ) {
        // Define all placeholders and their descriptions
        $placeholders = [
            '{{full_name}}'    => $data['full_name'] ?? '',
            '{{bib_number}}'   => $data['bib_number'] ?? '',
            '{{tshirt_size}}'  => $data['tshirt_size'] ?? '',
            '{{order_id}}'     => $data['order_id'] ?? '',
            '{{race_category}}'=> $data['race_category'] ?? '',
        ];

        // If content is provided, replace placeholders
        if ( ! empty( $content ) ) {
            return str_replace( array_keys( $placeholders ), array_values( $placeholders ), $content );
        }

        // Otherwise, return placeholder list with description
        $list = [];
        foreach ( $placeholders as $key => $value ) {
            $list[$key] = $value; // Key = placeholder, Value = description
        }

        return $list;
    }
}















