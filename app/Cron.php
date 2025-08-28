<?php
/**
 * All cron related functions
 */
namespace WpPluginHub\Run_Manager\App;
use WpPluginHub\Plugin\Base;

/**
 * if accessed directly, exit.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @package Plugin
 * @subpackage Cron
 * @author Codexpert <hi@codexpert.io>
 */
class Cron extends Base {

	public $plugin;

	/**
	 * Constructor function
	 */
	public function __construct( $plugin ) {
		$this->plugin	= $plugin;
		$this->slug		= $this->plugin['TextDomain'];
		$this->name		= $this->plugin['Name'];
		$this->server	= $this->plugin['server'];
		$this->version	= $this->plugin['Version'];
	}

	/**
	 * Installer. Runs once when the plugin in activated.
	 *
	 * @since 1.0
	 */
	public function install() {
		/**
		 * Schedule an event to sync help docs
		 */
		if ( ! wp_next_scheduled( 'run_manager_cleanup_old_certificates' ) ) {
		    wp_schedule_event( time(), 'daily', 'run_manager_cleanup_old_certificates' );
		}
	}

	/**
	 * Uninstaller. Runs once when the plugin in deactivated.
	 *
	 * @since 1.0
	 */
	public function uninstall() {
		/**
		 * Remove scheduled hooks
		 */
		wp_clear_scheduled_hook( 'run_manager_cleanup_old_certificates' );
	}

	public function delete_old_certificates() {
	    $upload_dir = wp_upload_dir();
	    $certificate_folder = $upload_dir['basedir'] . '/certificate/';

	    if (!is_dir($certificate_folder)) return;

	    $files = glob($certificate_folder . 'certificate-order-*.pdf');

	    $now = time();
	    $expiry_seconds = 30 * 24 * 60 * 60; // 30 days

	    foreach ($files as $file) {
	        if (file_exists($file) && ($now - filemtime($file)) > $expiry_seconds) {
	            unlink($file); // Delete file

	            // Optionally remove meta data from order
	            if (preg_match('/certificate-order-(\d+)\.pdf$/', $file, $matches)) {
	                $order_id = intval($matches[1]);
	                if ($order_id) {
	                    $order = wc_get_order($order_id);
	                    if ($order) {
	                        $order->delete_meta_data('_certificate_pdf_url');
	                        $order->delete_meta_data('_certificate_generated');
	                        $order->delete_meta_data('_certificate_generated_time');
	                        $order->save();
	                    }
	                }
	            }
	        }
	    }
	}

}