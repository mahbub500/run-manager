<?php
use Codexpert\Plugin\License;
use Codexpert\CoSchool\Certificate\Helper;


if( ! function_exists( 'get_plugin_data' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
}

/**
 * Check certificate is active
 * 
 * @return bol
 */
if( ! function_exists( 'coschool_certificate_active' ) ) :
function coschool_certificate_active() {

    $plugin                 = get_plugin_data( COSCHOOL_CERTIFICATE );
    $plugin['server']       = apply_filters( 'coschool-certificate_server', 'https://my.pluggable.io' );
    $license                = new License ( $plugin );

    return $license->_is_active();
}
endif;

/**
 * Is issuing certificate enabled?
 * 
 * @return bool
 */
if( ! function_exists( 'coschool_certificate_enabled' ) ) {
    function coschool_certificate_enabled() {
        return Helper::get_option( 'coschool_certificate' , 'enabled' );
    }
}

/**
 * Can an instructor edit the ceritificate?
 * 
 * @return bool
 */
if( ! function_exists( 'coschool_instructor_can_edit_certificate' ) ) {
    function coschool_instructor_can_edit_certificate() {
        
        if( ! coschool_certificate_enabled() ) return false;
        
        return Helper::get_option( 'coschool_certificate' , 'instructor_access' );
    }
}
