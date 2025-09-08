<?php
/**
 * All admin facing functions
 */
namespace Codexpert\CoSchool\Certificate;
use Codexpert\Plugin\Base;
// use Codexpert\Plugin\Metabox;

/**
 * if accessed directly, exit.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @package Plugin
 * @subpackage Admin
 * @author Codexpert <hi@codexpert.io>
 */
class Admin extends Base {

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

	/**
	 * Installer. Runs once when the plugin in activated.
	 *
	 * @since 1.0
	 */
	public function install() {

		if( ! get_option( 'coschool-certificate_version' ) ){
			update_option( 'coschool-certificate_version', $this->version );
		}
		
		if( ! get_option( 'coschool-certificate_install_time' ) ){
			update_option( 'coschool-certificate_install_time', time() );
		}
	}
	
	/**
	 * Enqueue JavaScripts and stylesheets
	 */
	public function enqueue_scripts() {
		$min = defined( 'COSCHOOL_CERTIFICATE_DEBUG' ) && COSCHOOL_CERTIFICATE_DEBUG ? '' : '.min';
		
		wp_enqueue_style( $this->slug, plugins_url( "/assets/css/admin{$min}.css", COSCHOOL_CERTIFICATE ), '', $this->version, 'all' );

		wp_enqueue_script( $this->slug, plugins_url( "/assets/js/admin{$min}.js", COSCHOOL_CERTIFICATE ), [ 'jquery' ], $this->version, true );

		$localized = [
			'ajaxurl'		=> admin_url( 'admin-ajax.php' ),
			'_wpnonce'		=> wp_create_nonce( 'coschool' ),
		];


		if( isset( $_GET['certificate'] ) && ( 'certificate' == get_post_type( $_GET['certificate'] ) || 'default' == $_GET['certificate'] ) ) {
			$certificate_id = coschool_sanitize( $_GET['certificate'] );
			$localized['certificate_id'] = $certificate_id;

			// load templates
			$templates_dir 	= trailingslashit( COSCHOOL_CERTIFICATE_DIR ) . 'views/certificate/Templates';
			foreach ( scandir( $templates_dir ) as $folder ) {
				if( is_dir( "{$templates_dir}/{$folder}" ) && ! in_array( $folder, [ '.', '..', 'assets' ] ) ) {
					foreach ( scandir( "{$templates_dir}/{$folder}" ) as $template ) {
						if( ! in_array( $template, [ '.', '..' ] ) ) {
							$name 		= ucwords( str_replace( [ '.php', '-' ], [ '', ' ' ], $template ) );
							$img_name 	= str_replace( 'php', 'png', $template );
							$localized['templates'][] = [
								'name'		=> $name,
								'title'		=> $name,
								'url'		=> plugins_url( "views/certificate/Templates/{$folder}/{$template}", COSCHOOL_CERTIFICATE ),
								'file'		=> plugins_url( "views/certificate/Templates/{$folder}/{$template}", COSCHOOL_CERTIFICATE ),
								'img'		=> plugins_url( "views/certificate/Templates/assets/img/icon/{$img_name}", COSCHOOL_CERTIFICATE ),
								'folder'	=> $folder,
								'assets'	=> []
							];
						}
					}
				}
			}

			//
			if ( isset( $_GET['certificate'] ) && ( $_GET['certificate'] == 'default' || 'certificate' == get_post_type( coschool_sanitize( $_GET['certificate'] ) ) ) ) {
				$localized['templates'][] = [
					'name'		=> __( 'Current', 'coschool' ),
					'title'		=> __( 'Current', 'coschool' ),
					'url'		=> add_query_arg( 'certificate', $certificate_id, plugins_url( "views/certificate/Templates/current_.php", COSCHOOL_CERTIFICATE ) ),
					'file'		=> add_query_arg( 'certificate', $certificate_id, plugins_url( "views/certificate/Templates/current_.php", COSCHOOL_CERTIFICATE ) ),
					'img'		=> plugins_url( "views/certificate/Templates/assets/img/icon/{$img_name}", COSCHOOL_CERTIFICATE ),
					'assets'	=> []
				];
			}

		}

		wp_localize_script( $this->slug, 'COSCHOOL_CERTIFICATE', apply_filters( "{$this->slug}-admin-localized", $localized ) );
	}

	public function action_links( $links ) {
		$this->admin_url = admin_url( 'admin.php' );

		$new_links = [
			'settings'	=> sprintf( '<a href="%1$s">' . __( 'Settings', 'coschool' ) . '</a>', add_query_arg( 'page', 'coschool', $this->admin_url ) )
		];
		
		return array_merge( $new_links, $links );
	}

	public function update_cache( $post_id, $post, $update ) {
		wp_cache_delete( "coschool_certificate_{$post->post_type}", 'coschool_certificate' );
	}

	/**
	 * Generates config metabox
	 * 
	 * @uses add_meta_box()
	 */
	public function config() {
		add_meta_box( 'coschool-certificate-config', __( 'Configuration', 'coschool' ), [ $this, 'callback_config_metabox' ], 'certificate', 'side', 'high' );
		add_meta_box( 'coschool-certificate-builder', __( 'Certificate Builder', 'coschool' ), [ $this, 'callback_builder_metabox' ], 'certificate', 'normal', 'high' );
	}

    public function add_menu() {
    	$certificate_edit = current_user_can( 'manage_options' ) ? 'manage_options' : 'create_courses';
    	add_submenu_page( 'coschool', __( 'Certificate Builder', 'coschool' ), __( 'Certificate Builder', 'coschool' ), $certificate_edit, 'certificate-builder', [ $this, 'callback_certificate_builder' ] );
    }

    public function highlight_menu( $submenu_file ) {

        global $plugin_page;

        $hidden_submenus = array(
            'certificate-builder' => true,
        );

        // Select another submenu item to highlight (optional).
        if ( $plugin_page && isset( $hidden_submenus[ $plugin_page ] ) ) {
            $submenu_file = 'edit.php?post_type=certificate';
        }

        // Hide the submenu.
        foreach ( $hidden_submenus as $submenu => $unused ) {
            remove_submenu_page( 'coschool', $submenu );
        }

        return $submenu_file;
    }

	public function callback_config_metabox() {
		echo Helper::get_template( 'config', 'views/certificate' );
	}

	public function callback_builder_metabox() {
		global $post;
		
		$certificate_data = new Data( $post->ID );

		if( '' != $html = $certificate_data->get( '_certificate_html' ) ) {
			echo $html;

			echo "<div id='coschool-certificate-download-section'> <a class='button button-primary button-hero' id='coschool-certificate-download' data-id='{$post->ID}' href='#'>" . __( 'Download Sample', 'coschool' ) . "</a>";
		}

		if( coschool_instructor_can_edit_certificate() ) {
			echo "<a class='button button-primary button-hero' href='" . add_query_arg( [ 'page' => 'certificate-builder', 'certificate' => $post->ID ], admin_url( 'admin.php' ) ) . "'>" . __( 'Open Builder', 'coschool' ) . "</a></div>";
		}
	}

	public function callback_certificate_builder() {
		// @todo move CSS
		?>
		<style type="text/css">
			html.wp-toolbar { padding-top: 0; } #adminmenumain, #wpadminbar, #wpfooter { display: none; } #wpcontent { margin-left: 0; padding-left: 0; }
		</style>
		<?php
		include_once trailingslashit( dirname( COSCHOOL_CERTIFICATE ) ) . 'views/certificate/Builder/editor.php';
	}
	
	public function certificate_settings( $metabox ){

		$certificate_args 	= [ 'post_type'	=> 'certificate'];

		if( coschool_certificate_enabled() ) {

			$metabox[ 'sections' ][ 'coschool_certification' ] = [
				'id'        => 'coschool_certification',
				'label'     => __( 'Certification', 'coschool' ),
				'icon'      => 'dashicons-text-page',
				'no_heading'=> true,
				'fields'    => [
					'enable_certificate' => [
						'id'      => 'enable_certificate',
						'label'     => __( 'Enable', 'coschool' ),
						'type'      => 'select',
						'desc'      => __( 'Are you going to issue a certificate after completing this course?', 'coschool' ),
						'options'   => [
							'yes'	=> __( 'Yes', 'coschool' ),
							'no'	=> __( 'No', 'coschool' ),
						],
						'default'   => 'no',
					],
					'certificate' => [
						'id'        => 'certificate',
						'label'     => __( 'Choose a Certificate', 'coschool' ),
						'type'      => 'select',
						'options'	=> Helper::get_posts( $certificate_args ),
						'condition'	=> [
							'key'	=> 'enable_certificate',
							'value'	=> 'yes'
						]
					],
				]
			];
		}

	return $metabox;

	}

	/**
	* Certificate updated text
	* 
	* @access public
	* 
 	* @param string mesages
	*/
	public function certificate_updated_message( $messages ){
		
		$messages['certificate'] = array(
			0  => '', // Unused. Messages start at index 1.
			1  => __( 'Certificate updated. ', 'coschool') ,
			6  => __( 'Certificate created. ', 'coschool') ,
		);

		return $messages;
	}

	/**
	* Bulk Certificate delete text
	* 
 	* @param string mesages
	*/
	public function bulk_certificate_updated_message( $bulk_messages, $bulk_counts ){
		
	    $bulk_messages['certificate'] = array(
	        'updated'   => _n( '%s Certificate updated.', '%s Certificates updated.', $bulk_counts['updated'], 'coschool' ),
	        'locked'    => _n( '%s Certificate not updated, somebody is editing it.', '%s Certificates not updated, somebody is editing them.', $bulk_counts['locked'], 'coschool' ),
	        'deleted'   => _n( '%s Certificate permanently deleted.', '%s Certificates permanently deleted.', $bulk_counts['deleted'], 'coschool' ),
	        'trashed'   => _n( '%s Certificate moved to the Trash.', '%s Certificates moved to the Trash.', $bulk_counts['trashed'], 'coschool' ),
	        'untrashed' => _n( '%s Certificate restored from the Trash.', '%s Certificates restored from the Trash.', $bulk_counts['untrashed'], 'coschool' ),
	    );
	 
	    return $bulk_messages;
	}
}