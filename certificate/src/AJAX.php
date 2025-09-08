<?php
/**
 * All AJAX related functions
 */
namespace Codexpert\CoSchool\Certificate;
use Codexpert\Plugin\Base;
use Mpdf\Mpdf as PDF;
use Codexpert\CoSchool\Helper;
use Pelago\Emogrifier\CssInliner;


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

	public function save() {
		$response = [ 'status' => 0, 'message' => __( 'Unauthorized', 'coschool' ) ];

		if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'coschool' ) ) {
			wp_send_json( $response );
		}
			// wp_send_json( $_POST );

		$certificate_id = coschool_sanitize( $_POST['certificate'] );

		if( 'default' == $certificate_id && current_user_can( 'manage_options' ) ) {
			update_option( '_certificate_html', $_POST['html'] ); // not escaped
			$response = [ 'status' => 1, 'message' => __( 'Certificate stored successfully', 'coschool' ) ];
			wp_send_json( $response );
		}

		if( ! current_user_can( 'edit_post', $certificate_id ) ) {
			wp_send_json( [ 'status' => 0, 'message' => __( 'You\'re not allowed to edit this certificate!', 'coschool' ) ] );
		}

		$certificate_data = new Data( $certificate_id );
		$certificate_data->set( '_certificate_html', $_POST['html'] ); // not escaped

		$response = [ 'status' => 1, 'message' => __( 'Certificate submission successfully', 'coschool' ) ];
		wp_send_json( $response );
	}

	public function download() {
		$response = [ 'status' => 0, 'message' => __( 'Unauthorized', 'coschool' ) ];

		if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'coschool' ) ) {
			wp_send_json( $response );
		}

		$certificate_id 	= coschool_sanitize( $_POST['certificate_id'] );
		$certificate_data 	= new Data( $certificate_id );
		$html 				= $certificate_data->get( '_certificate_html' );
		$visualHtml 		= CssInliner::fromHtml($html)->inlineCss()->render();

		$upload_dir		= wp_upload_dir();
		$upload_path	= trailingslashit( $upload_dir['basedir'] ) . 'coschool/';
		$upload_url 	= trailingslashit( $upload_dir['baseurl'] ) . 'coschool/';
		$pdf_path		= "{$upload_path}certificate-{$certificate_id}.pdf";
		$pdf_url		= "{$upload_url}certificate-{$certificate_id}.pdf";

		$mpdf = new PDF( ['orientation' => 'L'] );
		$mpdf->WriteHTML( $visualHtml );
		$mpdf->Output( $pdf_path, 'F' );
		
		$response = [ 'status' => 1, 'message' => __( 'Certificate generated', 'coschool' ), 'pdf' => $pdf_url, 'name' => "certificate-{$certificate_id}.pdf" ];
		wp_send_json( $response );
	}

}