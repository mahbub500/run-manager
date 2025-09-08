<?php
/**
 * All settings related functions
 */
namespace Codexpert\CoSchool\Certificate;
use Codexpert\Plugin\Base;
use Codexpert\Plugin\License;

/**
 * @package Plugin
 * @subpackage Settings
 * @author codexpert <hello@codexpert.io>
 */
class Settings extends Base {

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

	public function settings_fields( $Settings ) {

		$Settings['sections']['coschool_certificate'] = array(

			'id'        => 'coschool_certificate',
			'label'     => __( 'Certificate', 'coschool' ),
			'icon'      => 'dashicons-awards',
			'sticky'	=> false,
			'page_load'	=> true,
			'fields'    => [
				'enabled' => [
					'id'        => 'enabled',
					'label'     => __( 'Enable', 'coschool' ),
					'desc'		=> __( 'Enable certificate.', 'coschool' ),
					'type'      => 'switch',
				],
				'template' => [
					'id'        => 'template',
					'label'     => __( 'Preview', 'coschool' ),
					'type'      => '',
					'condition'	=> [
						'key'		=> 'enabled',
						'compare'	=> 'checked'
					],
				],
				'instructor_access' => [
					'id'        => 'instructor_access',
					'label'     => __( 'Instructor Access', 'instructor_access' ),
					'desc'		=> __( 'Enable this if you want to allow instructors to issue certificate to their students.', 'coschool' ),
					'type'      => 'switch',
					'condition'	=> [
						'key'		=> 'enabled',
						'compare'	=> 'checked'
					],
				],
			]
	
		);

		return $Settings;
	}
}