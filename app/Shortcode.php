<?php
/**
 * All Shortcode related functions
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
 * @subpackage Shortcode
 * @author Codexpert <hi@codexpert.io>
 */
class Shortcode extends Base {

    public $plugin;

    /**
     * Constructor function
     */
    public function __construct( $plugin ) {
        $this->plugin   = $plugin;
        $this->slug     = $this->plugin['TextDomain'];
        $this->name     = $this->plugin['Name'];
        $this->version  = $this->plugin['Version'];
    }

    public function my_shortcode() {
        return __( 'My Shortcode', 'run-manager' );
    }

  
    function display_race_data_table( $args ) {
        $upload_dir     = wp_upload_dir();
        $file_path_xlsx = $upload_dir['basedir'] . '/race_data/race_data.xlsx';
        $file_path_xls  = $upload_dir['basedir'] . '/race_data/race_data.xls';

        if (file_exists($file_path_xlsx)) {
            $file_path = $file_path_xlsx;
        } elseif (file_exists($file_path_xls)) {
            $file_path = $file_path_xls;
        } else {
            return "<p>File not found.</p>";
        }

        require_once ABSPATH . 'wp-load.php'; 
        require_once ABSPATH . 'wp-admin/includes/file.php'; 

        $spreadsheet = IOFactory::load($file_path);
        $total_sheets = $spreadsheet->getSheetCount();

        $sheet_index = isset( $args['sheet'] ) && $args['sheet'] - 1 < $total_sheets ? $args['sheet'] - 1 : 0;
        $worksheet = $spreadsheet->getSheet($sheet_index);
        $data = $worksheet->toArray();

        $html = '<table id="raceDataTable" class="display"><thead><tr>';
        foreach ($data[0] as $header) {
            $html .= "<th>{$header}</th>";
        }
        $html .= '</tr></thead><tbody>';

        foreach (array_slice($data, 1) as $row) {
            if (array_filter($row)) {
                $html .= '<tr>';
                foreach ($row as $cell) {
                    $html .= "<td>{$cell}</td>";
                }
                $html .= '</tr>';
            }
        }
        $html .= '</tbody></table>';

        wp_enqueue_script('jquery');
        wp_enqueue_script('datatables', 'https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js', array('jquery'), null, true);
        wp_enqueue_style('datatables-css', 'https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css');

        $html .= "<script>
            jQuery(document).ready(function($) {
                $('#raceDataTable').DataTable();
            });
        </script>";

        return $html;
    }

    // Register the shortcode [verify_bib]
   public function verify_bib_shortcode() {
    if ( ! is_user_logged_in() ) {
        return '<p>You must be logged in to verify Bib ID and verification code.</p>';
    }

    $current_user = wp_get_current_user();
    $allowed_roles = ['administrator', 'moderator']; 

    if ( ! array_intersect( $allowed_roles, $current_user->roles ) ) {
        return '<p>You do not have permission to access this form.</p>';
    }

    $html = '
    <form id="verify_bib_form" action="" method="post">
        <label for="bib_id">Bib ID:</label>
        <input type="number" id="bib_id" name="bib_id" required>
        <br>
        <label for="verification_code">Verification Code:</label>
        <input type="number" id="verification_code" name="verification_code" required>
        <br>
        <button type="submit">Verify</button>
        <div id="verification_message"></div>
    </form>';

    return $html;
}







}


