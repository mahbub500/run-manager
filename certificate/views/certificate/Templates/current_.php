<?php
use Codexpert\CoSchool\Certificate\Data as Certificate_Data;
include_once '../../../../../../wp-load.php';

if( ! isset( $_GET['certificate'] ) ) return;

if( coschool_instructor_can_edit_certificate() && 'certificate' == get_post_type( $_GET['certificate'] ) && current_user_can( 'edit_post', $_GET['certificate'] ) ) {
	$certificate_id = coschool_sanitize( $_GET['certificate'] );

	$certificate_data = new Certificate_Data( $certificate_id );
	echo $certificate_data->get( '_certificate_html' );
}

else {
	echo stripslashes( get_option( '_certificate_html' ) );
}
