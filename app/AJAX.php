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

        $data = "Order ID,Date,Total,Customer Name,Blood Group,DOB,EMM 1,EMM 2,NID,T-Shirt, certified\n";

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

        if ( empty( $_POST['order_number'] ) ) {
            wp_send_json_error( [ 'message' => 'Order number is missing or invalid' ] );
            return;
        }

        $order_number = sanitize_text_field( $_POST['order_number'] );
        $order = wc_get_order( $order_number );

        if ( ! $order ) {
            wp_send_json_error( [ 'message' => 'Order not found.' ] );
            return;
        }

        $user_name  = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
        $user_email = $order->get_billing_email();

        // Certificate image path
        $certificate_image = RUN_MANAGER_DIR . '/assets/img/certificate.jpeg';
        
        if ( ! file_exists( $certificate_image ) ) {
            wp_send_json_error( [ 'message' => 'Certificate template not found.' ] );
            return;
        }

        // Load image
        $image = imagecreatefromjpeg( $certificate_image );
        $text_color = imagecolorallocate( $image, 0, 0, 0 ); // Black color

        // Define font and text positions
        $font_path = RUN_MANAGER_DIR . '/assets/fonts/arial.ttf'; // Ensure the font exists

        if ( ! file_exists( $font_path ) ) {
            wp_send_json_error( [ 'message' => 'Font file not found.' ] );
            return;
        }

        // Add text to image
        imagettftext( $image, 10, 0, 100, 300, $text_color, $font_path, $user_name );
        imagettftext( $image, 10, 0, 100, 400, $text_color, $font_path, $user_email );
        imagettftext( $image, 10, 0, 100, 500, $text_color, $font_path, "Order No: $order_number" );

        // Save modified image
        $upload_dir = wp_upload_dir();
        $image_path = $upload_dir['basedir'] . "/certificate-order-{$order_number}.jpg";
        imagejpeg( $image, $image_path, 100 ); // Save as high quality

        imagedestroy( $image ); // Free memory

        // Convert image to PDF using DomPDF
        $html = '
        <html>
            <head>
                <style>
                    img {
                        width: 85%;
                        margin-left: 80px;
                    }
                </style>
            </head>
            <body>
                <img src="' . $upload_dir['baseurl'] . "/certificate-order-{$order_number}.jpg" . '" alt="Certificate">
            </body>
        </html>';


        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $dompdf = new Dompdf($options);

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        // Save PDF
        $pdf_path = $upload_dir['basedir'] . "/certificate-order-{$order_number}.pdf";
        file_put_contents( $pdf_path, $dompdf->output() );

        if (file_exists( $image_path )) {
            unlink( $image_path );            
        }

        // Return the download link
        wp_send_json_success( [
            'message'       => 'Certificate created successfully!',
            'download_link' => $upload_dir['baseurl'] . "/certificate-order-{$order_number}.pdf",
        ] );
    }


}