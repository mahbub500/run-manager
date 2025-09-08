<?php
/**
 * All public facing functions
 */
namespace Codexpert\CoSchool\Certificate;
use Codexpert\Plugin\Base;
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

	public function head() {}
	
	/**
	 * Enqueue JavaScripts and stylesheets
	 */
	public function enqueue_scripts() {
		$min = defined( 'COSCHOOL_CERTIFICATE_DEBUG' ) && COSCHOOL_CERTIFICATE_DEBUG ? '' : '.min';

		wp_enqueue_style( $this->slug, plugins_url( "/assets/css/front{$min}.css", COSCHOOL_CERTIFICATE ), '', $this->version, 'all' );
		wp_enqueue_script( $this->slug, plugins_url( "/assets/js/front{$min}.js", COSCHOOL_CERTIFICATE ), '', $this->version, 'all' );

		
		$localized = [
			'ajaxurl'	=> admin_url( 'admin-ajax.php' ),
			'_wpnonce'	=> wp_create_nonce(),
		];
		wp_localize_script( $this->slug, 'COSCHOOL_CERTIFICATE', apply_filters( "{$this->slug}-localized", $localized ) );
	}
}