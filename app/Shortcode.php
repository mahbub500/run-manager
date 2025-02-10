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

        $cache_key = 'race_data_file_' . md5($file_path);

        $cached_data = get_transient($cache_key);
        
        if ($cached_data === false) {
            require_once ABSPATH . 'wp-load.php'; 
            require_once ABSPATH . 'wp-admin/includes/file.php'; 
            

            $spreadsheet = IOFactory::load($file_path);
            $worksheet = $spreadsheet->getActiveSheet();
            $data = $worksheet->toArray();

            set_transient($cache_key, $data, 3600); 
        } else {
            $data = $cached_data;
        }

        $html = '<table id="raceDataTable" class="display"><thead><tr>';

        foreach ($data[0] as $header) {
            $html .= "<th>{$header}</th>";
        }
        $html .= '</tr></thead><tbody>';

        foreach (array_slice($data, 1) as $row) {
            if (array_filter($row)) {
                $html .= '<tr>';
                foreach ($row as $cell) {
                    if (!empty($cell)) {
                        $html .= "<td>{$cell}</td>";
                    }
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


}


