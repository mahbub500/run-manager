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

    // Modify the query to order by date in ascending order
    $args = [
        'status' => ['wc-completed', 'wc-processing'],
        'posts_per_page' => -1, // No limit
        'orderby' => 'date', // Order by date
        'order' => 'ASC' // Ascending order
    ];

    $orders = wc_get_orders( $args );

    if ( empty( $orders )) {
        wp_send_json_error(['message' => 'No orders found.']);
        return;
    }

    $data = "Order ID,Date,Total,Customer Name,Blood Group,DOB,EMM 1,NID,T-Shirt,Bib Id\n";

    foreach ( $orders as $order ) {
        $order_id = $order->get_id();
        $date = $order->get_date_created()->date('Y-m-d H:i:s');
        $total = $order->get_total();
        $customer_name = $order->get_formatted_billing_full_name();

        // Get order meta data
        $blood_group = $order->get_meta('billing_blood_group');
        $dob = $order->get_meta('billing_dob');
        $emm_1 = $order->get_meta('billing_emm_1');
        $nid = $order->get_meta('billing_nid');
        $tshirt = $order->get_meta('billing_tshirt');

        // Check if the order has a 'is_certified' meta key and set bib_id
        $bib_id = $order->get_meta('is_certified') ? $order->get_meta('is_certified') : '';

        // Append data row to CSV, including bib_id if found
        $data .= "$order_id,$date,$total,$customer_name,$blood_group,$dob,$emm_1,$nid,$tshirt,$bib_id\n";
    }

    // Set headers for CSV download
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="orders_export.csv"');
    echo $data;
    exit;
}


public function import_excel_to_orders() {
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'])) {
        wp_send_json_error(['message' => 'Invalid nonce']);
        return;
    }

    if (empty($_FILES['excel_file']['tmp_name'])) {
        wp_send_json_error(['message' => 'No file uploaded']);
        return;
    }

    $file = $_FILES['excel_file']['tmp_name'];

    try {
    foreach ($final_data as $row) {
    $order_id     = $row['Order ID'] ?? null;
    $is_certified = $row['Bib Id'] ?? null;

    if ($order_id) {
        $order = wc_get_order($order_id);
        if ($order) {
            // Assign certificate number
            $certificate_number = $is_certified;
            $order->update_meta_data('is_certified', $certificate_number);
            $order->save();

            // Ensure email is not sent multiple times
            $is_email_sent = filter_var($order->get_meta('is_email_sent'), FILTER_VALIDATE_BOOLEAN);
            if (!$is_email_sent) {
                $billing_email = $order->get_billing_email();
                if ($billing_email) {
                    $this->send_certificate_email($billing_email, $certificate_number, $order_id);
                    $order->update_meta_data('is_email_sent', true);
                    $order->save();
                }
            }

            // Ensure SMS is not sent multiple times
            $is_sms_sent = filter_var($order->get_meta('is_sms_sent'), FILTER_VALIDATE_BOOLEAN);
            if (!$is_sms_sent) {
                $get_billing_phone = $order->get_billing_phone();
                if ($get_billing_phone) {
                    // Generate random verification code
                    $random_number = mt_rand(100000, 999999);
                    $order->update_meta_data('verification_code', $random_number);
                    $order->save();

                    // Get billing name
                    $billing_name = $order->get_billing_first_name();
                    $message = "Hi $billing_name, your bib is $certificate_number and your verification code is $random_number. Thanks Run Bangladesh.";

                    // Send SMS
                    $response = sms_send($get_billing_phone, $message);

                    $order->update_meta_data('is_sms_sent', true);
                    $order->save();
                }
            }
        }
    }
}


    // Return JSON response
    wp_send_json_success([
        'message'  => 'File imported, emails, and SMS sent successfully!',
        'response' => $response
    ]);

} catch (Exception $e) {
    wp_send_json_error(['message' => 'Error: ' . $e->getMessage()]);
}


}


    /**
     * Sends an email to the customer with the certification number.
     */
   private function send_certificate_email($email, $certificate_number, $order_id) {
	    $order_url = esc_url(admin_url("post.php?post=$order_id&action=edit"));
	    
	    $subject = "Your Certification Number for Order #$order_id";
	    
	    $message = "Dear Customer,<br><br>";
	    $message .= "Your certification number for Order #$order_id is: <strong>$certificate_number</strong>.<br><br>";
	    $message .= "You can view your order details by clicking the link below:<br>";
	    $message .= "<a href='$order_url' target='_blank'>View Order #$order_id</a><br><br>";
	    $message .= "Thank you!";
	    
	    $headers = ['Content-Type: text/html; charset=UTF-8'];
	
	    // // Temporarily change sender email and name
	    // add_filter('wp_mail_from', function() {
	    //     return get_option('admin_email'); // Get admin email from settings
	    // });
	
	    // add_filter('wp_mail_from_name', function() {
	    //     return get_bloginfo('name'); // Get site title as sender name
	    // });
	
	    // wp_mail($email, $subject, $message, $headers);
	
	    // // Remove filters after sending the email
	    // remove_filter('wp_mail_from', 'custom_mail_from');
	    // remove_filter('wp_mail_from_name', 'custom_mail_from_name');
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
        imagejpeg( $image, $image_path, 100 ); 

        imagedestroy( $image );

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

    public function upload_race_data_callback() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'])) {
            wp_send_json_error(['message' => 'Invalid nonce']);
            return;
        }

        $file = $_FILES['race_excel_file'];
        $file_ext = pathinfo($file['name'], PATHINFO_EXTENSION);

        // Upload directory
        $upload_dir  = wp_upload_dir();
        $upload_folder = $upload_dir['basedir'] . '/race_data/';
        $upload_path = $upload_folder . 'race_data.' . $file_ext;

        // Ensure the directory exists
        if (!file_exists($upload_folder)) {
            wp_mkdir_p($upload_folder);
        }

        // Remove existing file
        if (file_exists($upload_path)) {
            unlink($upload_path);
        }

        // Move the uploaded file
        if (move_uploaded_file($file['tmp_name'], $upload_path)) {
            wp_send_json_success(['message' => 'File uploaded successfully!', 'file_path' => $upload_path]);
        } else {
            wp_send_json_error(['message' => 'File upload failed.']);
        }
    }


   public function generate_certificate() {
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'])) {
        wp_send_json_error(['message' => 'Invalid nonce']);
        return;
    }

    if (!isset($_POST['sheet_number']) || !is_numeric($_POST['sheet_number'])) {
        wp_send_json_error(['message' => 'Invalid sheet number']);
        return;
    }

    $sheet_index = intval($_POST['sheet_number']) - 1; // Convert input to zero-based index

    $upload_dir  = wp_upload_dir();
    $upload_path = $upload_dir['basedir'] . '/race_data';
    $files = glob($upload_path . '/*.xlsx');

    if (empty($files)) {
        wp_send_json_error(['message' => 'Please Upload the data']);
        return;
    }

    // Sort files by modification time (latest first)
    usort($files, function ($a, $b) {
        return filemtime($b) - filemtime($a);
    });

    $latest_file = $files[0];

    // Load the Excel file
    try {
        $spreadsheet = IOFactory::load($latest_file);
    } catch (Exception $e) {
        wp_send_json_error(['message' => 'Error loading Excel file: ' . $e->getMessage()]);
        return;
    }

    // Validate sheet index
    $sheet_count = $spreadsheet->getSheetCount();
    if ($sheet_index < 0 || $sheet_index >= $sheet_count) {
        wp_send_json_error(['message' => 'Sheet number out of range.']);
        return;
    }

    $worksheet = $spreadsheet->getSheet($sheet_index);

    $data = [];
    foreach ($worksheet->getRowIterator() as $row) {
        $cellIterator = $row->getCellIterator();
        $cellIterator->setIterateOnlyExistingCells(false);
        
        $rowData = [];
        foreach ($cellIterator as $cell) {
            $rowData[] = trim($cell->getValue());
        }
        
        if (!empty(array_filter($rowData))) { // Remove empty rows
            $data[] = $rowData;
        }
    }

    $certificates = [];
    foreach ($data as $index => $row) {
        if ($index === 0) continue; // Skip the header row
        
        $serial_no = $row[0];
        $rank = $row[1];
        $participant_name = $row[2];

        // Load the certificate template image
        $image_path = RUN_MANAGER_DIR . '/assets/img/certificate.jpeg';
        if (!file_exists($image_path)) {
            wp_send_json_error(['message' => 'Certificate template not found.']);
            return;
        }

        $image = imagecreatefromjpeg($image_path);

        // Check font file
        $font_path = RUN_MANAGER_DIR . '/assets/fonts/arial.ttf';
        if (!file_exists($font_path)) {
            wp_send_json_error(['message' => 'Font file not found.']);
            return;
        }

        $text_color = imagecolorallocate($image, 0, 0, 0);

        // Add participant details to the image
        imagettftext($image, 10, 0, 100, 300, $text_color, $font_path, "Name : $participant_name");
        imagettftext($image, 10, 0, 100, 400, $text_color, $font_path, "Rank: $rank");
        imagettftext($image, 10, 0, 100, 500, $text_color, $font_path, "Sl No: $serial_no");

        // Ensure the directory exists
        $upload_folder = $upload_dir['basedir'] . '/certificate/';
        if (!is_dir($upload_folder)) {
            wp_mkdir_p($upload_folder);
        }

        // Check if folder is writable
        if (!is_writable($upload_folder)) {
            wp_send_json_error(['message' => 'Upload directory is not writable.']);
            return;
        }

        // Save modified image
        $new_image_path = $upload_folder . "certificate-order-{$serial_no}.jpg";
        imagejpeg($image, $new_image_path, 100);
        imagedestroy($image);

        // Generate PDF with the image
        $html = "
        <html>
            <head>
                <style>
                    img { width: 85%; margin-left: 80px; }
                </style>
            </head>
            <body>
                <img src='" . $upload_dir['baseurl'] . "/certificate/certificate-order-{$serial_no}.jpg' alt='Certificate'>
            </body>
        </html>";

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        // Save PDF
        $pdf_path = $upload_folder . "certificate-order-{$serial_no}.pdf";
        file_put_contents($pdf_path, $dompdf->output());

        // Delete the image after saving the PDF
        unlink($new_image_path);

        // Add PDF to response
        $certificates[] = $upload_dir['baseurl'] . "/certificate/certificate-order-{$serial_no}.pdf";
    }

    wp_send_json_success(['certificates' => $certificates]);
}
    public function verify_bib_action_callback() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'])) {
            wp_send_json_error(['message' => 'Invalid nonce.']);
        }

        $bib_id = sanitize_text_field($_POST['bib_id']);
        $verification_code = sanitize_text_field($_POST['verification_code']);

        $order_id = wc_get_order_by_bib_id($bib_id);
        $order = wc_get_order($order_id);

        if ($order) {
            $is_verified = $order->get_meta('is_verified');

            if ($is_verified) {
                wp_send_json_error(['message' => 'This Bib ID has already been verified.']);
            }

            $stored_code = $order->get_meta('verification_code');

            if ($stored_code === $verification_code) {
                $order->update_meta_data('is_verified', true);
                $order->save();

                wp_send_json_success(['message' => 'Verification successful!']);
            } else {
                wp_send_json_error(['message' => 'Verification code does not match.']);
            }
        } else {
            wp_send_json_error(['message' => 'Bib ID not found.']);
        }

        wp_die();
    }


}
