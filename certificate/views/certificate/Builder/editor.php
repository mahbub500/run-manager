<?php

if( ! coschool_certificate_active() ) {
   echo $html = sprintf( "<h4 class='certificate-disabled'>%s</h>", __( 'Certificate license is not active so module is disabled. ', 'coschool' ) );
   return;
}

if( ! coschool_certificate_enabled() ) {
   echo $html = sprintf( "<h4 class='certificate-disabled'>%s</h>", __( 'Certificate module is disabled. ', 'coschool' ) );
   return;
}

if( ! coschool_instructor_can_edit_certificate() && ! is_null( $certificate_id = $_GET['certificate'] ) && 'certificate' == get_post_type( $certificate_id ) ) {
   echo $html = sprintf( "<h4 class='certificate-disabled'>%s</h>", __( 'You\'re not allowed to edit this. ', 'coschool' ) );
   return;
}

if( is_null( $certificate_id = $_GET['certificate'] ) || 'certificate' != get_post_type( $certificate_id ) && 'default' != $certificate_id ) {
   echo $html = sprintf( "<h4 class='certificate-disabled'>%s</h>", __( 'Invalid certificate ID ', 'coschool' ) );
   return;
}

include( dirname( __FILE__ ) . '/editor_.php' );