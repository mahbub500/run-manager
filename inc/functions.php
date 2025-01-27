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

