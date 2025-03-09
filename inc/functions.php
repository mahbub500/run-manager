<?php
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

if ( ! function_exists( 'sms_send' ) ) {
	function sms_send( $number, $message ) {
	    $url = "http://bulksmsbd.net/api/smsapi";
	    $api_key = "3WVzyjkNVqq82uuZSK6y";
	    $senderid = "8809617614182";

	    // Ensure the number has the correct format with +880
	    $formatted_number = "+88" . $number; // Keeps leading zero and adds "+"

	    $data = [
	        "api_key"   => $api_key,
	        "senderid"  => $senderid,
	        "number"    => $formatted_number, // Proper format: +88017XXXXXXXX
	        "message"   => $message
	    ];

	    $ch = curl_init();
	    curl_setopt($ch, CURLOPT_URL, $url);
	    curl_setopt($ch, CURLOPT_POST, 1);
	    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data)); // Proper encoding
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	    $response = curl_exec($ch);
	    curl_close($ch);

	    return $response;
	}
}

if ( ! function_exists( 'wc_get_order_by_bib_id' ) ) {
   function wc_get_order_by_bib_id( $certificate_number ) {
	    $args = array(
	        'post_type'   => 'shop_order',
	        'post_status' => 'any',
	        'meta_query'  => array(
	            array(
	                'key'     => 'is_certified',
	                'value'   => $certificate_number,
	                'compare' => '=',
	            ),
	        ),
	    );

	    $orders = wc_get_orders( $args );

	    if ( ! empty( $orders ) ) {
	        return $orders[0]->ID;
	    }

	    return null;
	}

}







