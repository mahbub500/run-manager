<?php
/**
 * All Shortcode related functions
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

    function display_race_data_table() {
    $upload_dir  = wp_upload_dir();
    $file_path_xlsx = $upload_dir['basedir'] . '/race_data/race_data.xlsx';
    $file_path_xls  = $upload_dir['basedir'] . '/race_data/race_data.xls';

    // Check if the file exists
    if (file_exists($file_path_xlsx)) {
        $file_path = $file_path_xlsx;
    } elseif (file_exists($file_path_xls)) {
        $file_path = $file_path_xls;
    } else {
        return "<p>No file found.</p>";
    }

    // Load PHPSpreadsheet to read Excel
    require_once ABSPATH . 'wp-load.php'; 
    require_once ABSPATH . 'wp-admin/includes/file.php'; 
    

    $spreadsheet = IOFactory::load($file_path);
    $worksheet = $spreadsheet->getActiveSheet();
    $data = $worksheet->toArray(); 

    // Start building the table
    $html = '<table id="raceDataTable" class="display"><thead><tr>';

    // Print table headers
    foreach ($data[0] as $header) {
        $html .= "<th>{$header}</th>";
    }
    $html .= '</tr></thead><tbody>';

    // Print table rows and exclude empty rows or cells
    foreach (array_slice($data, 1) as $row) {
        // Check if the row is empty (all cells are empty)
        if (array_filter($row)) { // If the row contains any non-empty cell
            $html .= '<tr>';
            foreach ($row as $cell) {
                // Only print non-empty cells
                if (!empty($cell)) {
                    $html .= "<td>{$cell}</td>";
                }
            }
            $html .= '</tr>';
        }
    }

    $html .= '</tbody></table>';

    // Enqueue DataTables scripts and styles
    wp_enqueue_script('jquery');
    wp_enqueue_script('datatables', 'https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js', array('jquery'), null, true);
    wp_enqueue_style('datatables-css', 'https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css');

    // Add JavaScript to initialize DataTables
    $html .= "<script>
        jQuery(document).ready(function($) {
            $('#raceDataTable').DataTable();
        });
    </script>";

    return $html;
}

}


