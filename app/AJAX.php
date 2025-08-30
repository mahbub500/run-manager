<?php
/**
 * All AJAX related functions
 */
namespace WpPluginHub\Run_Manager\App;
use WpPluginHub\Plugin\Base;
use WpPluginHub\Run_Manager\Helper;

use PhpOffice\PhpSpreadsheet\IOFactory;
use Dompdf\Dompdf;
use Dompdf\Options;
use WpPluginHub\AdnSms\AdnSmsNotification;

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
        if ( ! isset($_POST['nonce']) || ! wp_verify_nonce($_POST['nonce']) ) {
            wp_send_json_error(['message' => 'Invalid nonce']);
            return;
        }

        // CSV headers
        $data = "Sl No,Order ID,Total,Customer Name,Blood Group,DOB,EMM 1,NID/birth/passport,T-Shirt,Bib Id,Evnet\n";

        $sl_no      = 0;
        $batch_size = 200; // number of orders per batch
        $offset     = 0;

        do {
            // Fetch orders batch
            $args = [
                'status'  => ['wc-completed', 'wc-processing'],
                'limit'   => $batch_size,
                'offset'  => $offset,
                'orderby' => 'date',
                'order'   => 'ASC',
            ];
            $orders = wc_get_orders( $args );

            if ( empty($orders) ) {
                break;
            }

            foreach ($orders as $order) {
                $sl_no++;
                $order_id      = $order->get_id();
                $total         = $order->get_total();
                $customer_name = $order->get_formatted_billing_full_name();

                // Get order meta data
                $blood_group	= $order->get_meta('billing_blood_group');
                $dob			= $order->get_meta('billing_dob');
                $emm_1			= $order->get_meta('billing_emm_1');
                $event_name		= $order->get_meta('rm_event_key');

                // Check for NID, Birth Registration, or Passport
                $nid = $order->get_meta('billing_nid') ?: 
                       $order->get_meta('billing_birth_registration') ?: 
                       $order->get_meta('billing_passport');

                $tshirt = $order->get_meta('billing_tshirt');
                $bib_id = $order->get_meta('is_certified') ? $order->get_meta('is_certified') : '';

                // Clean values
                $clean = function($value) {
                    return trim(preg_replace('/\s+/', ' ', (string) $value));
                };

                $data .= sprintf(
				    "\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\"\n",
				    $sl_no,
				    $order_id,
				    $total,
				    $clean($customer_name),
				    $clean($blood_group),
				    $clean($dob),
				    $clean($emm_1),
				    $clean($nid),
				    $clean($tshirt),
				    $clean($bib_id),
				    $clean($event_name)
				);
            }

            $offset += $batch_size;

        } while ( count($orders) === $batch_size );

        // Set headers for CSV download
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="orders_export.csv"');
        echo $data;
        exit;
    }

public function import_excel_to_orders() {
    // Check nonce for security
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'] )) {
        wp_send_json_error(['message' => __('Invalid nonce.', 'run-manager')]);
        return;
    }

    // Check if file is uploaded
    if (empty($_FILES['excel_file']['tmp_name'])) {
        wp_send_json_error(['message' => __('No file uploaded.', 'run-manager')]);
        return;
    }

    if ($_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
        wp_send_json_error(['message' => __('File upload error.', 'run-manager')]);
        return;
    }

    $file = $_FILES['excel_file']['tmp_name'];
    $logger = wc_get_logger();
    $logger->info("Processing file: " . $file, ['source' => 'import_excel']);

    $notify_data    = get_option('notify_wysiwyg_data', []);


    try {
        // Check if PhpSpreadsheet is available
        if (!class_exists('PhpOffice\PhpSpreadsheet\IOFactory')) {
            throw new Exception(__('PhpSpreadsheet library is missing.', 'run-manager'));
        }

        // Load the Excel file
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file);
        $sheetData = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);

        // Extract headers from the first row
        $headers        = array_shift($sheetData);
        $final_data     = [];
        $campain_name   = Helper::get_option( 'run-manager_basic', 'campain_name' );

       foreach ($sheetData as $row) {
		    if (!empty($row['A']) && is_numeric($row['A'])) {
		        $final_data[] = [
		            'order_id'      => sanitize_text_field($row['A']),
		            'bib_id'        => isset($row['C']) ? sanitize_text_field($row['C']) : null,
		            'tshirt_size'   => isset($row['D']) ? sanitize_text_field($row['D']) : null,
		            'race_name'     => isset($row['B']) ? sanitize_text_field($row['B']) : null,
		            'race_category' => isset($row['E']) ? sanitize_text_field($row['E']) : null,
		        ];
		    }
		}
   
         // Process each order
        foreach ( $final_data as $row ) {
            $order_id = $row['order_id'] ?? null;
            if ( ! $order_id ) {
                continue;
            }

            $order = wc_get_order( $order_id );
            if ( ! $order ) {
                continue;
            }

            // Extract row values
            $bib_id        = $row['bib_id']        ?? '';
            $tshirt        = $row['tshirt_size']   ?? '';
            $race_name     = $row['race_name']     ?? '';
            $race_category = $row['race_category'] ?? '';

            // Save meta
            $order->update_meta_data( 'bib_id', $bib_id );
            $order->update_meta_data( 'race_name', $race_name );
            $order->update_meta_data( 'race_category', $race_category );
            $order->save();

            // Build placeholders
            $placeholder_data = [
                'full_name'     => trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() ),
                'first_name'    => $order->get_billing_first_name() ?? '',
                'last_name'     => $order->get_billing_last_name() ?? '',
                'bib_number'    => $bib_id,
                'tshirt_size'   => $tshirt,
                'order_id'      => $order_id,
                'race_category' => $race_category,
            ];

            // Messages
            $test_mode       = ! empty( $notify_data['test_mode'] );
            $email_subject   = notify_placeholders( $notify_data['email_subject'] ?? '', $placeholder_data );
            $email_subject   = wp_strip_all_tags( $email_subject );
            $email_message   = notify_placeholders( $notify_data['email_content'] ?? '', $placeholder_data );
            $sms_message     = wp_strip_all_tags( notify_placeholders( $notify_data['sms_content'] ?? '', $placeholder_data ) );

            // Recipients
            $recipient_email = $test_mode ? sanitize_email( $notify_data['test_email'] ?? '' ) : $order->get_billing_email();
            $recipient_phone = $test_mode ? sanitize_text_field( $notify_data['test_mobile'] ?? '' ) : clean_phone_number( $order->get_billing_phone() );

            // ------------------
            // Send Email
            // ------------------
            if ( ! $order->get_meta( 'is_email_sent' ) || $test_mode ) {
                if ( ! empty( $recipient_email ) && ! empty( $notify_data['notify_email'] ) ) {
                    $this->send_certificate_email( $recipient_email, $email_message, $email_subject, $order_id );

                    if ( ! $test_mode ) {
                        $order->update_meta_data( 'is_email_sent', true );
                        $order->save();
                    }
                    $logger->info( "Email sent to: {$recipient_email}", [ 'source' => 'import_excel' ] );
                }
            }

            // ------------------
            // Send SMS
            // ------------------
            if ( ! $order->get_meta( 'is_sms_sent' ) || $test_mode ) {
                if ( ! empty( $recipient_phone ) && ! empty( $notify_data['notify_sms'] ) ) {
                    send_sms_to_phone( $recipient_phone, $sms_message );

                    if ( ! $test_mode ) {
                        $order->update_meta_data( 'is_sms_sent', true );
                        $order->save();
                    }
                    $logger->info( "SMS sent to: {$recipient_phone}", [ 'source' => 'import_excel' ] );
                }
            }
        }


        wp_send_json_success(['message' => __('File imported successfully. Emails and SMS sent!', 'run-manager')]);

    } catch (Exception $e) {
        $logger->error('Import Error: ' . $e->getMessage(), ['source' => 'import_excel']);
        wp_send_json_error(['message' => __('Error: ', 'run-manager') . $e->getMessage()]);
    }
}

    /**
     * Sends an email to the customer with the certification number.
     */
   private function send_certificate_email($email, $message, $subject, $order_id ) {
	    $order_url = esc_url(admin_url("post.php?post=$order_id&action=edit"));
        // $subject = "Your Certification Number for Order #$order_id";
        $encoded_subject = "=?UTF-8?B?" . base64_encode( $subject ) . "?=";
        $headers = ['Content-Type: text/html; charset=UTF-8'];

        wp_mail($email, $encoded_subject, $message, $headers);
	  
	}



    
	public function download_certificate() {
	    // Verify nonce and order number
	    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce']) ) {
	        wp_send_json_error(['message' => 'Invalid request or order number missing']);
	        return;
	    }

	    $order_number = sanitize_text_field($_POST['order_number']);
	    $order = wc_get_order($order_number);
	    if (!$order) {
	        wp_send_json_error(['message' => 'Order not found.']);
	        return;
	    }

	    // Paths
	    $upload_dir = wp_upload_dir();
	    $certificate_folder = $upload_dir['basedir'] . '/certificate/';
	    if (!file_exists($certificate_folder)) wp_mkdir_p($certificate_folder);

	    $certificate_image = RUN_MANAGER_DIR . '/assets/img/CERTIFICATE.jpg';
	    $font_path = RUN_MANAGER_DIR . '/assets/fonts/arial.ttf';
	    if (!file_exists($certificate_image) || !file_exists($font_path)) {
	        wp_send_json_error(['message' => 'Certificate template or font not found.']);
	        return;
	    }

	    // User & race data
	    $user_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
	    $race_data = [
	        ['text' => $user_name, 'x' => 392, 'y' => 480, 'size' => 50],
	        ['text' => $order->get_meta('_race_category'), 'x' => 430, 'y' => 730, 'size' => 20],
	        ['text' => $order->get_meta('_race_finish_time') . ' MINUTES', 'x' => 654, 'y' => 730, 'size' => 20],
	        ['text' => $order->get_meta('_bib_id'), 'x' => 990, 'y' => 730, 'size' => 20],
	        ['text' => $order->get_meta('_race_overall_rank'), 'x' => 1265, 'y' => 730, 'size' => 20],
	        ['text' => $order->get_meta('_race_chip_time'), 'x' => 450, 'y' => 850, 'size' => 20],
	        ['text' => $order->get_meta('_race_gun_time'), 'x' => 740, 'y' => 850, 'size' => 20],
	        ['text' => $order->get_meta('_race_place_in_age'), 'x' => 1002, 'y' => 850, 'size' => 20],
	        ['text' => $order->get_meta('_race_place_in_gender'), 'x' => 1277, 'y' => 850, 'size' => 20],
	    ];

	    // Load image
	    $image = imagecreatefromjpeg($certificate_image);
	    $text_color = imagecolorallocate($image, 68, 56, 139);

	    // Draw all text
	    foreach ($race_data as $data) {
	        if (!empty($data['text'])) {
	            imagettftext($image, $data['size'], 0, $data['x'], $data['y'], $text_color, $font_path, $data['text']);
	        }
	    }

	    // Save image
	    $image_path = $certificate_folder . "certificate-order-{$order_number}.jpg";
	    imagejpeg($image, $image_path, 100);
	    imagedestroy($image);

	    // Convert to PDF
	    $html = '<html><head><style>img{width:85%;margin-left:80px;}</style></head><body><img src="' . $upload_dir['baseurl'] . "/certificate/certificate-order-{$order_number}.jpg" . '" alt="Certificate"></body></html>';
	    $dompdf = new Dompdf((new Options())->set('isHtml5ParserEnabled', true)->set('isRemoteEnabled', true));
	    $dompdf->loadHtml($html);
	    $dompdf->setPaper('A4', 'landscape');
	    $dompdf->render();
	    $pdf_path = $certificate_folder . "certificate-order-{$order_number}.pdf";
	    file_put_contents($pdf_path, $dompdf->output());

	    if (file_exists($image_path)) unlink($image_path); 
	   
	    $order->save();

	    // Return download link
	    wp_send_json_success([
	        'message'       => 'Certificate created successfully!',
	        'download_link' => $upload_dir['baseurl'] . "/certificate/certificate-order-{$order_number}.pdf",
	    ]);
	}



   

    public function upload_race_data_callback() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'])) {
            wp_send_json_error(['message' => 'Invalid nonce']);
            return;
        }

        if (empty($_FILES['race_excel_file']['tmp_name'])) {
            wp_send_json_error(['message' => 'No file uploaded.']);
            return;
        }

        $file = $_FILES['race_excel_file'];
        $file_ext = pathinfo($file['name'], PATHINFO_EXTENSION);

        // Upload directory
        $upload_dir  = wp_upload_dir();
        $upload_folder = $upload_dir['basedir'] . '/race_data/';
        $upload_path = $upload_folder . 'race_data.' . $file_ext;

        if (!file_exists($upload_folder)) {
            wp_mkdir_p($upload_folder);
        }

        if (file_exists($upload_path)) {
            unlink($upload_path);
        }

        if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
            wp_send_json_error(['message' => 'File upload failed.']);
            return;
        }

        try {
            $spreadsheet = IOFactory::load($upload_path);
            $sheet = $spreadsheet->getSheet(0);
            $rows = $sheet->toArray();

            if (empty($rows)) {
                wp_send_json_error(['message' => 'No data found in Excel.']);
                return;
            }

            // Normalize headers: lowercase, trim, single spaces
            $headers = array_map(function($h){
                return strtolower(trim(preg_replace('/\s+/', ' ', $h)));
            }, $rows[0]);
            unset($rows[0]); // Remove header row

            $updated_total = 0;
            $batch_size = 200;

            // Split rows into batches
            $batches = array_chunk($rows, $batch_size);

            foreach ($batches as $batch_rows) {
                foreach ($batch_rows as $row) {
                    if (empty(array_filter($row))) continue; // skip empty rows

                    $data = array_combine($headers, $row);
                    if (!$data) continue;

                    $order_id = intval($data['order id'] ?? 0);
                    if ($order_id <= 0) continue;

                    $order = wc_get_order($order_id);
                    if (!$order) continue;

                    // Update all order meta
                    $order->update_meta_data('_bib_id', $data['bib no'] ?? '');
                    $order->update_meta_data('_race_category', $data['category'] ?? '');
                    $order->update_meta_data('_race_finish_time', $data['finish time'] ?? '');
                    $order->update_meta_data('_race_overall_rank', $data['overall rank'] ?? '');
                    $order->update_meta_data('_race_chip_time', $data['chip time'] ?? '');
                    $order->update_meta_data('_race_gun_time', $data['gun time'] ?? '');
                    
                    // Correct keys for Plane in Age / Plane in Gender
                    $order->update_meta_data('_race_place_in_age', $data['plane in age'] ?? '');
                    $order->update_meta_data('_race_place_in_gender', $data['plane in gender'] ?? '');
                    
                    $order->update_meta_data('_is_certified', $data['is certified'] ?? '');
                    $order->save();

                    $updated_total++;
                }

                // Optional: free memory after each batch
                unset($batch_rows);
                gc_collect_cycles();
            }

            wp_send_json_success([
                'message' => "File uploaded successfully! {$updated_total} orders updated in batches of {$batch_size}.",
            ]);

        } catch (\Exception $e) {
            wp_send_json_error(['message' => 'Excel parsing failed: ' . $e->getMessage()]);
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

    $bib_id             = sanitize_text_field($_POST['bib_id']);
    $verification_code  = sanitize_text_field($_POST['verification_code']);

    $order_id   = wc_get_order_by_bib_id($bib_id);
    $order      = wc_get_order($order_id);

    if ($order) {
        $is_verified    = $order->get_meta('is_verified');
        $tshirt_size   	= $order->get_meta('billing_tshirt'); 
        $race_name 		= $order->get_meta('race_name');
        $race_category 	= $order->get_meta('race_category');
        $billing_name	= $order->get_billing_first_name();
        $raw_phone     	= $order->get_billing_phone();
		$cleaned_phone 	= clean_phone_number( $raw_phone );

       if ($is_verified) {
            wp_send_json_error([
                'message' => 'This Bib ID has already been verified. and Tshirt size is : <strong>' . esc_html($tshirt_size) . '</strong>'
            ]);
        }


        $stored_code = $order->get_meta('verification_code');

        if ($stored_code === $verification_code) {
        	
            // Fetch billing t-shirt size from order meta
           $sms_message = sprintf(
		        'Hello %s, your race kit for %s has been successfully delivered. Your Bib Number is %s for the %s race category. Good luck with your race, Run Bangladesh.',
		        esc_html( $billing_name ),
		        esc_html( $race_name ),
		        esc_html( $bib_id ),
		        esc_html( $race_category )
		    );

           send_sms_to_phone( $cleaned_phone, $sms_message );

            // Mark as verified
            $order->update_meta_data('is_verified', true);
            $order->save();

            $message = 'Verification successful!';
            if ($tshirt_size) {
                $message .= ' Your T-Shirt size: ' . esc_html($tshirt_size);
            }

            wp_send_json_success(['message' => $message]);
        } else {
            wp_send_json_error(['message' => 'Verification code does not match.' ]);
        }
    } else {
        wp_send_json_error(['message' => 'Bib ID not found.']);
    }

    wp_die();
}
    public function generate_tshirt_size() {
        // Security check
        if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'] ) ) {
            wp_send_json_error( [ 'message' => 'Security check failed!' ] );
        }

        // Get selected product ID
        $product_id = sanitize_text_field( $_POST['id'] ?? '' );
        if ( empty( $product_id ) ) {
            wp_send_json_error( [ 'message' => 'Please select a product' ] );
        }

        // Initialize T-Shirt counts
        $tshirt_counts = [];

        // Fetch all orders that contain this product
        $orders = get_product_order_ids( $product_id );

        foreach ( $orders as $order_id ) {
            $order = wc_get_order( $order_id );
            if ( $order ) {
                $size = $order->get_meta( 'billing_tshirt' );
                if ( ! empty( $size ) ) {
                    if ( ! isset( $tshirt_counts[ $size ] ) ) {
                        $tshirt_counts[ $size ] = 0;
                    }
                    $tshirt_counts[ $size ]++;
                }
            }
        }

        // Generate HTML table with subtotals
        $total_count = 0;
        $html  = '<h2 style="text-align:center;">T-Shirt Size Report</h2>';
        $html .= '<table border="1" cellpadding="8" cellspacing="0" style="width:100%; border-collapse: collapse; text-align:center;">
                    <tr style="background-color:#f2f2f2;">
                        <th>T-Shirt Size</th>
                        <th>Total Count</th>
                    </tr>';

        foreach ( $tshirt_counts as $size => $count ) {
            $html .= '<tr><td>' . esc_html( $size ) . '</td><td>' . esc_html( $count ) . '</td></tr>';
            $total_count += $count;
        }

        // Add grand total row
        $html .= '<tr style="font-weight:bold; background:#e8e8e8;">
                    <td>Total</td>
                    <td>' . esc_html( $total_count ) . '</td>
                  </tr>';
        $html .= '</table>';

        // Initialize DomPDF
        $options = new Options();
        $options->set( 'defaultFont', 'Helvetica' );
        $options->set( 'isHtml5ParserEnabled', true );
        $options->set( 'isRemoteEnabled', true );

        $dompdf = new Dompdf( $options );

        try {
            $dompdf->loadHtml( $html );
            $dompdf->setPaper( 'A4', 'portrait' );
            $dompdf->render();

            // Save PDF
            $upload_dir = wp_upload_dir();
            $pdf_path   = $upload_dir['basedir'] . '/tshirt_report_' . intval( $product_id ) . '.pdf';
            file_put_contents( $pdf_path, $dompdf->output() );

            $pdf_url = $upload_dir['baseurl'] . '/tshirt_report_' . intval( $product_id ) . '.pdf';
            wp_send_json_success( [ 'message' => 'PDF generated successfully.', 'url' => $pdf_url ] );

        } catch ( \Exception $e ) {
            wp_send_json_error( [ 'message' => 'PDF generation failed: ' . $e->getMessage() ] );
        }
    }


    public function custom_clear_cart() {

        if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'] )) {
            wp_send_json_error( ['message' => 'Security check failed!'] );
        }
            WC()->cart->empty_cart();
            wp_send_json_success(
                ['message' => 'Cart Clear' ]
            );
        }

    public function save_notify_data() {

    // Security check
        if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'] ) ) {
            wp_send_json_error( [ 'message' => 'Security check failed!' ] );
        }

        // Prepare data
        $data = [
            'test_mode'     => !empty($_POST['test_mode']) ? 1 : 0,
            'test_email'    => sanitize_email($_POST['test_email'] ?? ''),
            'test_mobile'   => sanitize_text_field($_POST['test_mobile'] ?? ''),
            'email_subject' => wp_kses_post($_POST['email_subject'] ?? ''),
            'notify_email'  => !empty($_POST['notify_email']) ? 1 : 0,
            'notify_sms'    => !empty($_POST['notify_sms']) ? 1 : 0,
            'email_content' => wp_kses_post($_POST['email_content'] ?? ''),
            'sms_content'   => wp_kses_post($_POST['sms_content'] ?? ''),
        ];

        update_option('notify_wysiwyg_data', $data);

        wp_send_json_success( [ 'message' => 'Settings saved successfully!' ] );
    }

    public function product_sales_count() {
        if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'] ) ) {
            wp_send_json_error([ 'message' => 'Security check failed!' ]);
        }

        $all_products = wc_get_products([
                'post_type'      => 'product',
                'post_status'    => 'publish',
                'posts_per_page' => -1,
            ]);
        $products_data = [];

        foreach ($all_products as $product) {
            $product_id = $product->get_id();

            if ($product->is_type('variable')) {
                $variations = $product->get_children();
                $variation_data = [];

                foreach ($variations as $vid) {
                    $variation = wc_get_product($vid);
                    $variation_data[$variation->get_formatted_name()] = get_variation_sales_count($vid);
                }

                $variation_data['Grand Total'] = array_sum($variation_data);
                $products_data[$product_id] = $variation_data;
            } else {
                $products_data[$product_id] = [
                    $product->get_name() => (int) get_post_meta($product_id, 'total_sales', true),
                    'Grand Total' => (int) get_post_meta($product_id, 'total_sales', true),
                ];
            }
        }

        wp_send_json_success([ 'products' => $products_data ]);
    }

    public function save_restriction_products() {
        if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'] ) ) {
            wp_send_json_error([ 'message' => 'Security check failed!' ]);
        }

        $products = isset( $_POST['products'] ) ? (array) $_POST['products'] : [];

        if ( empty( $products ) ) {
            wp_send_json_error( [ 'message' => 'No products selected' ] );
        }

        foreach ( $products as $product_id ) {
            $product_id = intval( $product_id );
            if ( $product_id > 0 ) {
                update_post_meta( $product_id, '_restriction_enabled', true );
            }
        }

        wp_send_json_success( [ 'message' => 'Restriction meta added to products' ] );

           
        }



}
