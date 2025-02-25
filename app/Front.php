<?php
/**
 * All public facing functions
 */
namespace WpPluginHub\Run_Manager\App;
use WpPluginHub\Plugin\Base;
use WpPluginHub\Run_Manager\Helper;

use PhpOffice\PhpSpreadsheet\IOFactory;
/**
 * if accessed directly, exit.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @package Plugin
 * @subpackage Front
 * @author Codexpert <hi@codexpert.io>
 */
class Front extends Base {

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

	public function head() {

// 		$response = sms_send("8801829493782", "Your verification code is 123456");
// echo $response;

		return;
		$upload_dir  = wp_upload_dir();
        $upload_path = $upload_dir['basedir'] . '/race_data';

        $files = glob($upload_path . '/*.xlsx');

        $latest_file = $files[0]; 

        // Load the Excel file (Sheet 2)
        $spreadsheet = IOFactory::load($latest_file);
        $worksheet = $spreadsheet->getSheet(1); // Sheet 2 (index starts from 0)

        $data = [];
        foreach ($worksheet->getRowIterator() as $row) {
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false);
            
            $rowData = [];
            foreach ($cellIterator as $cell) {
                $rowData[] = $cell->getValue();
            }
            $data[] = $rowData;
        }

        $participant_name = $data[1][0]; 
        $rank = $data[1][1];
        $order_number = time(); 

		Helper::pri( $data );
       
	}
	
	/**
	 * Enqueue JavaScripts and stylesheets
	 */
	public function enqueue_scripts() {
		$min = defined( 'RUN_MANAGER_DEBUG' ) && RUN_MANAGER_DEBUG ? '' : '.min';

		wp_enqueue_style( $this->slug, plugins_url( "/assets/css/front{$min}.css", RUN_MANAGER ), '', $this->version, 'all' );

		wp_enqueue_script( $this->slug, plugins_url( "/assets/js/front{$min}.js", RUN_MANAGER ), [ 'jquery' ], time(), true );
		
		$localized = [
			'ajaxurl'	=> admin_url( 'admin-ajax.php' ),
			'_wpnonce'	=> wp_create_nonce(),
		];
		wp_localize_script( $this->slug, 'RUN_MANAGER', apply_filters( "{$this->slug}-localized", $localized ) );
	}

	public function modal() {
		echo '
		<div id="run-manager-modal" style="display: none">
			<img id="run-manager-modal-loader" src="' . esc_attr( RUN_MANAGER_ASSET . '/img/loader.gif' ) . '" />
		</div>';
	}

	public function download_certificate( $actions, $order ){
		$certificate_meta = $order->get_meta( 'is_certified' );

		if ( $certificate_meta == 1 ) {
			$actions['download_certificate'] = array(
		        'url'  => '#', 
		        'name' => __( 'Get Certified', 'run-manager' ),
		        
		    );
		}


		
		return $actions;
	}
}