<?php
/**
 * All lesson related functions
 */
namespace Codexpert\CoSchool\Certificate;
use Codexpert\CoSchool\Abstracts\Post_Data;
use Codexpert\CoSchool\App\Course\Data as Course_Data;

/**
 * if accessed directly, exit.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @package Plugin
 * @subpackage Certificate
 * @author Codexpert <hi@codexpert.io>
 */
class Data extends Post_Data {

    /**
     * @var obj
     */
    public $certificate;

    /**
     * Constructor function
     * 
     * @uses WP_User class
     * @param int|obj $certificate the certificate
     */
    public function __construct( $certificate ) {
        $this->plugin   = $certificate;

        $this->certificate = get_post( $certificate );
        parent::__construct( $this->certificate );
    }

    /**
     * Gets associated course ID
     * 
     * @return int|obj the course ID $post_id|$post
     */
    public function get_course() {
        return $this->get( 'course_id' );
    }

    /**
     * Gets the banner
     * 
     * @return string The URL
     */
    public function get_banner( $size = 'coschool-banner' ) {
        return get_the_post_thumbnail( $this->get( 'id' ), $size );
    }

    /**
     * Can a student see this content?
     * 
     * @param int $user_id the student ID
     * 
     * @return bool
     */
    public function is_visible_by( $user_id = null ) {

        if( is_null( $user_id ) ) {
            $user_id = get_current_user_id();
        }

        if( user_can( $user_id, 'edit_post', $this->certificate->ID ) ) {
            return true;
        }

        $course = new Course_Data( $this->get_course() );
        if( $course->get_type() == 'free' ) {
            return true;
        }

        $student_data = new Student_Data( $user_id );
        if( $student_data->has_course( $this->get_course() ) ) {
            return true;
        }

        return false;
    }
}