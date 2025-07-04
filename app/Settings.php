<?php
/**
 * All settings related functions
 */
namespace WpPluginHub\Run_Manager\App;
use WpPluginHub\Run_Manager\Helper;
use WpPluginHub\Plugin\Base;
use WpPluginHub\Plugin\Settings as Settings_API;

/**
 * @package Plugin
 * @subpackage Settings
 * @author Codexpert <hi@codexpert.io>
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
	
	public function init_menu() {
		
		$site_config = [
			'PHP Version'				=> PHP_VERSION,
			'WordPress Version' 		=> get_bloginfo( 'version' ),
			'WooCommerce Version'		=> is_plugin_active( 'woocommerce/woocommerce.php' ) ? get_option( 'woocommerce_version' ) : 'Not Active',
			'Memory Limit'				=> defined( 'WP_MEMORY_LIMIT' ) && WP_MEMORY_LIMIT ? WP_MEMORY_LIMIT : 'Not Defined',
			'Debug Mode'				=> defined( 'WP_DEBUG' ) && WP_DEBUG ? 'Enabled' : 'Disabled',
			'Active Plugins'			=> get_option( 'active_plugins' ),
		];

		$settings = [
			'id'            => $this->slug,
			'label'         => $this->name,
			'title'         => "{$this->name} v{$this->version}",
			'header'        => $this->name,
			'parent'     => 'woocommerce',
			// 'priority'   => 10,
			// 'capability' => 'manage_options',
			// 'icon'       => 'dashicons-wordpress',
			// 'position'   => 25,
			// 'topnav'	=> true,
			'sections'      => [
				'run-manager_basic'	=> [
					'id'        => 'run-manager_basic',
					'label'     => __( 'Basic Settings', 'run-manager' ),
					'icon'      => 'dashicons-admin-tools',
					// 'color'		=> '#4c3f93',
					'sticky'	=> false,
					'fields'    => [
						'sample_tabs' => [
							'id'      => 'sample_tabs',
							'label'     => __( 'Sample Tabs' ),
							'type'      => 'tabs',
							'items'     => [
								'run-manager_export_order_data' => [
									'id'        => 'run-manager_export_order_data',
									'label'     => __( 'Export Order Data', 'run-manager' ),
									'icon'      => 'dashicons-editor-table',
									// 'color'		=> '#28c9ee',
									'hide_form'	=> true,
									'template'  => RUN_MANAGER_DIR . '/views/export/export.php',
								],
								'run-manager_import_order_data' => [
									'id'        => 'run-manager_import_order_data',
									'label'     => __( 'Import Order Data', 'run-manager' ),
									'icon'      => 'dashicons-editor-table',
									// 'color'		=> '#28c9ee',
									'hide_form'	=> true,
									'template'  => RUN_MANAGER_DIR . '/views/export/import.php',
								],
								'run-manager_save_message' => [
									'id'        => 'run-manager_save_message',
									'label'     => __( 'Campain ', 'run-manager' ),
									'icon'      => 'dashicons-editor-table',
									// 'color'		=> '#28c9ee',
									'hide_form'	=> true,
									'fields'    => [
										'campain_name' => [
											'id'        => 'campain_name',
											'label'     => __( 'Campain Name', 'run-manager' ),
											'type'      => 'text',
											'desc'      => __( 'Enter your Campain Name.', 'run-manager' ),
											// 'class'     => '',
											'default'   => 'DMHM2025',
											'readonly'  => false, // true|false
											'disabled'  => false, // true|false
										],
										'select_optional_product' => [
											'id'      => 'select_optional_product',
											'label'     => __( 'Select Optional product', 'run-manager' ),
											'type'      => 'select',
											'desc'      => __( 'This is a select field.', 'run-manager' ),
											// 'class'     => '',
											'options'   =>  Helper::get_posts(  array(
																        'post_type'      => 'product',
																        'posts_per_page' => -1,
																        'post_status'    => 'publish',
																    ) )  ,
											'default'   => 'option_2',
											'disabled'  => false, // true|false
											'multiple'  => false, // true|false
										],
									],
								],
								
							],
						],
					]
				],
				
				
			],
		];

		new Settings_API( $settings );
	}
}