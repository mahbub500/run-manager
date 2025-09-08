<?php
/**
 * All common functions to load in both admin and front
 */
namespace Codexpert\CoSchool\Certificate;
use Codexpert\Plugin\Base;
use Mpdf\Mpdf as PDF;
use Codexpert\CoSchool\Helper;
use Pelago\Emogrifier\CssInliner;
use Codexpert\CoSchool\App\Course\Data as Course_Data;
use Codexpert\CoSchool\Certificate\Data as Certificate_Data;
use Codexpert\CoSchool\App\Student\Data as Student_Data;
use Codexpert\CoSchool\App\Enrollment\Data as Enrollment_Data;
use Codexpert\CoSchool\App\Instructor\Data as Instructor_Data;


/**
 * if accessed directly, exit.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @package Plugin
 * @subpackage Common
 * @author Codexpert <hi@codexpert.io>
 */
class Common extends Base {

	public $plugin;

	/**
	 * Constructor function
	 */
	public function __construct( $plugin ) {
		$this->plugin	= $plugin;
		$this->slug		= $this->plugin['TextDomain'];
		$this->name		= $this->plugin['Name'];
		$this->server	= $this->plugin['server'];
		$this->version	= $this->plugin['Version'];
	}

	public function register_cpt() {
	
		$labels = array(
			'name'               	=> __( 'Certificates', 'coschool' ),
			'singular_name'      	=> __( 'Certificate', 'coschool' ),
			'add_new'            	=> _x( 'Add New Certificate', 'coschool', 'coschool' ),
			'add_new_item'       	=> __( 'Add New Certificate', 'coschool' ),
			'edit_item'          	=> __( 'Edit Certificate', 'coschool' ),
			'new_item'           	=> __( 'New Certificate', 'coschool' ),
			'view_item'          	=> __( 'View Certificate', 'coschool' ),
			'search_items'       	=> __( 'Search Certificates', 'coschool' ),
			'not_found'          	=> __( 'No Certificates found', 'coschool' ),
			'not_found_in_trash' 	=> __( 'No Certificates found in Trash', 'coschool' ),
			'parent_item_colon'  	=> __( 'Parent Certificate:', 'coschool' ),
			'menu_name'          	=> __( 'Certificates', 'coschool' ),
			'featured_image'     	=> __( 'Banner', 'coschool' ),
			'set_featured_image' 	=> __( 'Add Banner', 'coschool' ),
			'remove_featured_image'	=> __( 'Remove Banner', 'coschool' ),
		);
	
		$args = array(
			'labels'              => $labels,
			'hierarchical'        => false,
			'description'         => 'description',
			'taxonomies'          => array(),
			'public'              => false,
			'show_ui'             => coschool_instructor_can_edit_certificate() ? 'coschool' : false,
			'show_in_menu'        => 'coschool',
			'show_in_admin_bar'   => true,
			'menu_position'       => null,
			'menu_icon'           => null,
			'show_in_nav_menus'   => true,
			'publicly_queryable'  => false,
			'exclude_from_search' => true,
			'has_archive'         => true,
			'query_var'           => true,
			'can_export'          => true,
			'rewrite'             => true,
			'capability_type'     => 'post',
			'supports'            => array( 'title', 'thumbnail' ),
		);
	
		register_post_type( 'certificate', $args );
	}

	/**
	* Adds table column
	* 
	* @access public
	* 
 	* @param array $columns
 	* 
	* @return array $columns
	*/
	public function add_table_columns( $columns ) {
		unset( $columns['date'] );

		$columns['course']	= __( 'Course', 'coschool' );
		$columns['date']	= __( 'Date', 'coschool' );

		return $columns;
	}

	/**
	* Adds column content
	* 
	* @access public
	* 
 	* @param string $column the column id
 	* @param int $certificate_id item ID
	*/
	public function add_column_content( $column, $certificate_id ) {

		switch ( $column ) {

		    case 'course' :
				$certificate_data 	= new Data( $certificate_id );
				$course_id 			= $certificate_data->get( 'course_id' );

				if ( ! is_null( $course_id ) ) {
					$course_data	= new Data( $course_id );
			    	echo '<a href="' . get_edit_post_link( $course_data->get( 'id' ) ) . '">'. $course_data->get( 'name' ) . '</a>';
				}
				
		    break;
		}
	}

   	public function get_certificate() {
        $response = [];

        if( !wp_verify_nonce( $_POST['_wpnonce'], 'coschool' ) ) {
            $response['status']     = 0;
            $response['message']    = __( 'Unauthorized!', 'coschool' );
            wp_send_json( $response );
        }

        $student_data       = new Student_Data( get_current_user_id() );

        $course_id          = coschool_sanitize( $_POST['course_id'] );
        $course_data        = new Course_Data( $course_id );

        $instructor_data     = new Instructor_Data( $course_data->get_instructor() );


        $certificate_id     = $course_data->get_certificate_id();
        $certificate_data   = new Certificate_Data( $certificate_id );
        $html               = $certificate_data->get( '_certificate_html' );

        $enrollment_id      = $student_data->get_course_enrollment( $course_id )->id;
        $enrollment_data    = new Enrollment_Data( $enrollment_id );

        $format             = get_option('date_format');

        $course_complete_date = $enrollment_data->data_completed();

        $instructor_sign    = $instructor_data->get_signature_url();  ; 
        if ( $instructor_sign ) {
        $instructor_sign_tag   = "<img src='{$instructor_sign}'  width='80' height='50' />";

        }else{
        $instructor_sign_tag   = " ";
        } 


        $certificate_data   = array(

            '%%student_name%%'      => $student_data->get( 'name' ),
            '%%course_name%%'       => $course_data->get( 'name' ),
            '%%teacher_name%%'      => $instructor_data->get( 'name' ),
            '%%date%%'              => date( $format, $course_complete_date ),
            '%%signature%%'         => $instructor_sign_tag,
        );

        $html               = str_replace( array_keys( $certificate_data ) , array_values( $certificate_data ) , $html );
        $visualHtml 		= CssInliner::fromHtml($html)->inlineCss()->render();
        $upload_dir         = wp_upload_dir();
        $upload_path        = trailingslashit( $upload_dir['basedir'] ) . 'coschool/';
        $upload_url         = trailingslashit( $upload_dir['baseurl'] ) . 'coschool/';
        $pdf_path           = "{$upload_path}certificate-{$certificate_id}.pdf";
        $pdf_url            = "{$upload_url}certificate-{$certificate_id}.pdf";

        $mpdf = new PDF( ['orientation' => 'L'] );
        $mpdf->WriteHTML( $visualHtml );
        $mpdf->Output( $pdf_path, 'F' );

        $response = [ 'status' => 1, 'message' => __( 'Certificate generated', 'coschool' ), 'pdf' => $pdf_url, 'name' => "certificate-{$certificate_id}.pdf" ];
        wp_send_json( $response );
    }

}