<?php
/**
 * Perform when the plugin is being uninstalled
 */

// If uninstall is not called, exit
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

$deletable_options = [ 'coschool-certificate_version', 'coschool-certificate_install_time', 'coschool-certificate_docs_json', 'codexpert-blog-json' ];
foreach ( $deletable_options as $option ) {
    delete_option( $option );
}