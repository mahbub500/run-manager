<?php
/**
 * All AJAX related functions
 */
namespace Codexpert\Run_Manager\App;
use WpPluginHub\Plugin\Base;

/**
 * if accessed directly, exit.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @package Plugin
 * @subpackage AJAX
 * @author Codexpert <hi@codexpert.io>
 */
class AJAX extends Base {

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

	public function woocommerce_orders_export() {
		error_log( 'test' );

		   

		    // Fetch WooCommerce orders
		    $args = [
		        'post_type'   => 'shop_order',
		        'post_status' => 'wc-completed', // Fetch only completed orders
		        'posts_per_page' => -1,
		    ];
		    $orders = get_posts($args);

		    if (empty($orders)) {
		        wp_send_json_error(['message' => 'No orders found.']);
		        return;
		    }

		    // Prepare data for export
		    $data = "Order ID,Date,Total,Customer Name\n"; // CSV headers
		    foreach ($orders as $order_post) {
		        $order = wc_get_order($order_post->ID);
		        $data .= implode(",", [
		            $order->get_id(),
		            $order->get_date_created()->date('Y-m-d H:i:s'),
		            $order->get_total(),
		            $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
		        ]) . "\n";
		    }

		    // Send the data back to the AJAX request
		    wp_send_json_success($data);
		}

}