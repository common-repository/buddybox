<?php
/*
Plugin Name: Buddybox
Plugin URI: http://imathi.eu/tag/buddybox/
Description: A plugin to share files, the BuddyPress way!
Version: 1.0
Author: imath
Author URI: http://imathi.eu/
License: GPLv2
Network: true
Text Domain: buddybox
Domain Path: /languages/
*/


// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;


if ( !class_exists( 'BuddyBox' ) ) :

/**
 * Main BuddyBox Class
 *
 * Inspired by bbpress 2.3
 */
class BuddyBox {
	
	private $data;

	private static $instance;

	/**
	 * Main BuddyBox Instance
	 *
	 * Inspired by bbpress 2.3
	 *
	 * Avoids the use of a global
	 *
	 * @package BuddyBox
	 * @since 1.0
	 *
	 * @uses BuddyBox::setup_globals() to set the global needed
	 * @uses BuddyBox::includes() to include the required files
	 * @uses BuddyBox::setup_actions() to set up the hooks
	 * @return object the instance
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new BuddyBox;
			self::$instance->setup_globals();
			self::$instance->includes();
			self::$instance->setup_actions();
		}
		return self::$instance;
	}

	
	private function __construct() { /* Do nothing here */ }
	
	public function __clone() { _doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'buddybox' ), '1.0' ); }

	public function __wakeup() { _doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'buddybox' ), '1.0' ); }

	public function __isset( $key ) { return isset( $this->data[$key] ); }

	public function __get( $key ) { return isset( $this->data[$key] ) ? $this->data[$key] : null; }

	public function __set( $key, $value ) { $this->data[$key] = $value; }

	public function __unset( $key ) { if ( isset( $this->data[$key] ) ) unset( $this->data[$key] ); }

	public function __call( $name = '', $args = array() ) { unset( $name, $args ); return null; }


	/**
	 * Some usefull vars
	 *
	 * @package BuddyBox
	 * @since 1.0
	 *
	 * @uses plugin_basename()
	 * @uses plugin_dir_path() to build BuddyBox plugin path
	 * @uses plugin_dir_url() to build BuddyBox plugin url
	 */
	private function setup_globals() {

		/** Version ***********************************************************/

		$this->version    = '1.0';

		/** Paths *************************************************************/

		// Setup some base path and URL information
		$this->file       = __FILE__;
		$this->basename   = apply_filters( 'buddybox_plugin_basename', plugin_basename( $this->file ) );
		$this->plugin_dir = apply_filters( 'buddybox_plugin_dir_path',  plugin_dir_path( $this->file ) );
		$this->plugin_url = apply_filters( 'buddybox_plugin_dir_url',   plugin_dir_url ( $this->file ) );

		// Includes
		$this->includes_dir = apply_filters( 'buddybox_includes_dir', trailingslashit( $this->plugin_dir . 'includes'  ) );
		$this->includes_url = apply_filters( 'buddybox_includes_url', trailingslashit( $this->plugin_url . 'includes'  ) );
		$this->upload_dir   = false;
		$this->upload_url   = false;
		$this->images_url = apply_filters( 'buddybox_images_url', trailingslashit( $this->includes_url . 'images'  ) );

		// Languages
		$this->lang_dir     = apply_filters( 'buddybox_lang_dir',     trailingslashit( $this->plugin_dir . 'languages' ) );
		
		// BuddyBox slug and name
		$this->buddybox_slug = apply_filters( 'buddybox_slug', 'buddybox' );
		$this->buddybox_name = apply_filters( 'buddybox_name', 'BuddyBox' );

		// Post type identifiers
		$this->buddybox_file_post_type   = apply_filters( 'buddybox_file_post_type',   'buddybox-file' );
		$this->buddybox_folder_post_type = apply_filters( 'buddybox_folder_post_type', 'buddybox-folder' );


		/** Misc **************************************************************/

		$this->domain         = 'buddybox';
		$this->errors         = new WP_Error(); // Feedback
		
	}
	
	/**
	 * includes the needed files
	 *
	 * @package BuddyBox
	 * @since 1.0
	 *
	 * @uses is_admin() for the settings files
	 */
	private function includes() {
		require( $this->includes_dir . 'buddybox-actions.php'        );
		require( $this->includes_dir . 'buddybox-functions.php'        );
		
		if( is_admin() ){
			require( $this->includes_dir . 'admin/buddybox-admin.php'        );
		}
	}
	

	/**
	 * It's about hooks!
	 *
	 * @package BuddyBox
	 * @since 1.0
	 *
	 * The main hook used is bp_include to load our custom BuddyPress component
	 */
	private function setup_actions() {

		// Add actions to plugin activation and deactivation hooks
		add_action( 'activate_'   . $this->basename, 'buddybox_activation'   );
		add_action( 'deactivate_' . $this->basename, 'buddybox_deactivation' );
		
		add_action( 'bp_init', array( $this, 'load_textdomain' ), 6 );
		add_action( 'bp_include', array( $this, 'load_component' ) );

		do_action_ref_array( 'buddybox_after_setup_actions', array( &$this ) );
	}
	
	/**
	 * Loads the translation
	 *
	 * @package BuddyBox
	 * @since 1.0
	 * @uses get_locale()
	 * @uses load_textdomain()
	 */
	public function load_textdomain() {
		// try to get locale
		$locale = apply_filters( 'buddybox_load_textdomain_get_locale', get_locale() );

		// if we found a locale, try to load .mo file
		if ( !empty( $locale ) ) {
			// default .mo file path
			$mofile_default = sprintf( '%s/languages/%s-%s.mo', $this->plugin_dir, $this->domain, $locale );
			// final filtered file path
			$mofile = apply_filters( 'buddybox_textdomain_mofile', $mofile_default );
			// make sure file exists, and load it
			if ( file_exists( $mofile ) ) {
				load_textdomain( $this->domain, $mofile );
			}
		}
	}

	/**
	 * Finally, let's safely load our component
	 *
	 * @package BuddyBox
	 * @since 1.0
	 */
	public function load_component() {
		require( $this->includes_dir . 'buddybox-component.php' );
	}
	
}

function buddybox() {
	return buddybox::instance();
}

buddybox();


endif;

