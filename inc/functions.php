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



