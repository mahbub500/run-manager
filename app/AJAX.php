<?php
/**
 * All AJAX related functions
 */
namespace WpPluginHub\Run_Manager\App;
use WpPluginHub\Plugin\Base;

use PhpOffice\PhpSpreadsheet\IOFactory;
use Dompdf\Dompdf;
use Dompdf\Options;


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
            $blood_group = $order->get_meta('billing_blood_group');
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

        $spreadsheet = IOFactory::load($file);
        $sheet = $spreadsheet->getActiveSheet();
        $data = $sheet->toArray();

        $headers    = array_shift( $data );
        $final_data = [];

        foreach ($data as &$row) {
            $final_data[] = array_combine($headers, $row);    
        }

        foreach ( $final_data as $key => $row ) {
            $is_certified = $row['certified'];
            $order_id = $row['Order ID'];

            if ( $order_id ) {
                $order = wc_get_order( $order_id );

                if ( $order ) {
                    $certificate_meta = $order->get_meta( 'is_certified' );

                    if ( empty( $certificate_meta ) ) {
                        $order->update_meta_data( 'is_certified', $is_certified );
                        $order->save();
                    }else{
                        $order->update_meta_data( 'is_certified', $is_certified );
                        $order->save();
                    }
                }
            }
        }

        wp_send_json_success(['message' => 'File imported successfully!']);

    }
    
    public function download_certificate() {
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'] ) ) {
            wp_send_json_error( [ 'message' => 'Invalid nonce' ] );
            return;
        }

        if ( isset( $_POST['order_number'] ) && ! empty( $_POST['order_number'] ) ) {
            $order_number = sanitize_text_field( $_POST['order_number'] );
        } else {
            wp_send_json_error( [ 'message' => 'Order number is missing or invalid' ] );
            return;
        }

        $order = wc_get_order( $order_number );
        if ( ! $order ) {
            wp_send_json_error( [ 'message' => 'Order not found.' ] );
            return;
        }

        $user_name  = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
        $user_email = $order->get_billing_email();

        // Generate the HTML for the certificate
        $html = "
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; text-align: center; }
                    h1 { color: #333; }
                    .certificate { border: 5px solid #ccc; padding: 20px; margin: 50px auto; max-width: 600px; }
                    .info { margin-top: 20px; }
                </style>
            </head>
            <body>
                <div class='certificate'>
                    <h1>Certificate of Completion</h1>
                    <p>This certifies that</p>
                    <h2>$user_name</h2>
                    <p>with email</p>
                    <h3>$user_email</h3>
                    <p>has successfully completed the course/order</p>
                    <h3>Order Number: $order_number</h3>
                </div>
            </body>
            </html>
        ";

        // Configure DOMPDF
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $dompdf = new Dompdf($options);

        // Load HTML to DOMPDF
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');

        // Render the PDF
        $dompdf->render();

        // Save the PDF file to the uploads directory
        $upload_dir = wp_upload_dir();
        $file_path  = $upload_dir['basedir'] . "/certificate-order-{$order_number}.pdf";
        file_put_contents( $file_path, $dompdf->output() );

        // Return the download link
        wp_send_json_success( [
            'message'       => 'Certificate created successfully!',
            'download_link' => $upload_dir['baseurl'] . "/certificate-order-{$order_number}.pdf",
        ] );
    }

}