<?php
/**
 * All AJAX related functions
 */
namespace WpPluginHub\Run_Manager\App;
use WpPluginHub\Plugin\Base;

use PhpOffice\PhpSpreadsheet\IOFactory;

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
        if ( !isset($_POST['nonce']) || ! wp_verify_nonce($_POST['nonce'] ) ) {
            wp_send_json_error(['message' => 'Invalid nonce']);
            return;
        }
        $args = [
            'status' => ['wc-completed', 'wc-processing']
        ];
        $orders = wc_get_orders($args);

        if (empty($orders)) {
            wp_send_json_error(['message' => 'No orders found.']);
            return;
        }

        $data = "Order ID,Date,Total,Customer Name,Blood Group,DOB,EMM 1,EMM 2,NID,T-Shirt\n";

        foreach ($orders as $order) {
            $order_id = $order->get_id();
            $date = $order->get_date_created()->date('Y-m-d H:i:s');
            $total = $order->get_total();
            $customer_name = $order->get_formatted_billing_full_name();

            // Get order meta data
            $blood_group = $order->get_meta('_billing_blood_group');
            $dob = $order->get_meta('billing_dob');
            $emm_1 = $order->get_meta('billing_emm_1');
            $emm_2 = $order->get_meta('billing_emm_2');
            $nid = $order->get_meta('billing_nid');
            $tshirt = $order->get_meta('billing_tshirt');

            // Append data row to CSV
            $data .= "$order_id,$date,$total,$customer_name,$blood_group,$dob,$emm_1,$emm_2,$nid,$tshirt\n";
        }

        // Set headers for CSV download
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="orders_export.csv"');
        echo $data;
        exit;
    }

    public function import_excel_to_orders(){
        if ( !isset($_POST['nonce']) || ! wp_verify_nonce($_POST['nonce'] ) ) {
            wp_send_json_error(['message' => 'Invalid nonce']);
            return;
        }

        $file = $_FILES['excel_file']['tmp_name'];

        // $filepath =  RUN_MANAGER_DIR . '/assets/img/RunBangladeash.xlsx' ;

        $spreadsheet = IOFactory::load($file);

        // Read the first sheet
        $sheet = $spreadsheet->getActiveSheet();

        // Get data as an array
        $data = $sheet->toArray();

        update_option( 'xl_file', $data );

        wp_send_json_success(['message' => 'File imported successfully!']);

    }


}