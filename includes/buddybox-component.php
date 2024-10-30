<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;


/**
 * Main BuddyBox Component Class
 *
 * Inspired by BuddyPress skeleton component
 */
class BuddyBox_Component extends BP_Component {

	/**
	 * Constructor method
	 *
	 *
	 * @package BuddyBox
	 * @since 1.0
	 */
	
	function __construct() {
		global $blog_id;

		parent::start(
			buddybox_get_slug(),
			buddybox_get_name(),
			buddybox_get_includes_dir()
		);

	 	$this->includes();
		
		buddypress()->active_components[$this->id] = '1';

		/**
		 * Register the BuddyBox custom post types
		 */
		if( $blog_id == BP_ROOT_BLOG ) {
			add_action( 'init', array( &$this, 'register_post_types' ), 9 );
			
			$this->register_upload_dir();
		}
		
		// register the embed handler
		wp_embed_register_handler( 'buddybox', '#'.buddybox_get_root_url().'\/(.+?)\/(.+?)\/#i', 'wp_embed_handler_buddybox' );
	}

	/**
	 * BuddyBox needed files
	 *
	 * @package BuddyBox
	 * @since 1.0
	 *
	 * @uses bp_is_active() to check if group component is active
	 */
	function includes() {

		// Files to include
		$includes = array(
			'buddybox-item-filters.php',
			'buddybox-item-actions.php',
			'buddybox-item-screens.php',
			'buddybox-item-classes.php',
			'buddybox-item-functions.php',
			'buddybox-item-template.php',
			'buddybox-item-ajax.php'
		);
		
		if( bp_is_active( 'groups' ) )
			$includes[] = 'buddybox-group-class.php';
		

		parent::includes( $includes );

	}

	/**
	 * Set up BuddyBox globals
	 *
	 * @package BuddyBox
	 * @since 1.0
	 *
	 * @global obj $bp BuddyPress's global object
	 * @uses buddypress() to get the instance data
	 * @uses buddybox_get_slug() to get BuddyBox root slug
	 */
	function setup_globals() {
		$bp = buddypress();

		// Set up the $globals array to be passed along to parent::setup_globals()
		$globals = array(
			'slug'                  => buddybox_get_slug(),
			'root_slug'             => isset( $bp->pages->{$this->id}->slug ) ? $bp->pages->{$this->id}->slug : buddybox_get_slug(),
			'has_directory'         => true,
			'notification_callback' => 'buddybox_format_notifications',
			'search_string'         => __( 'Search files...', 'buddybox' )
		);

		// Let BP_Component::setup_globals() do its work.
		parent::setup_globals( $globals );
		
	}

	/**
	 * Set up buddybox navigation.
	 * 
	 * @uses buddypress() to get the instance data
	 * @uses buddybox_get_name() to get BuddyBox name
	 * @uses buddybox_get_slug() to get BuddyBox slug
	 * @uses bp_displayed_user_id() to get the displayed user id
	 * @uses bp_displayed_user_domain() to get displayed user profile link
	 * @uses bp_loggedin_user_domain() to get current user profile link
	 * @uses bp_is_active() to check if the friends component is active
	 */
	function setup_nav() {
		$bp =  buddypress();
		
		$main_nav = array(
			'name' 		      => buddybox_get_name(),
			'slug' 		      => buddybox_get_slug(),
			'position' 	      => 80,
			'screen_function'     => 'buddybox_user_files',
			'default_subnav_slug' => 'files'
		);
		$displayed_user_id = bp_displayed_user_id();
		$user_domain = ( !empty( $displayed_user_id ) ) ? bp_displayed_user_domain() : bp_loggedin_user_domain();

		$buddybox_link = trailingslashit( $user_domain . buddybox_get_slug() );

		// Add a few subnav items under the main Example tab
		$sub_nav[] = array(
			'name'            =>  __( 'BuddyBox Files', 'buddybox' ),
			'slug'            => 'files',
			'parent_url'      => $buddybox_link,
			'parent_slug'     => $this->slug,
			'screen_function' => 'buddybox_user_files',
			'position'        => 10,
		);

		// Add the subnav items to the friends nav item
		if( bp_is_active('friends') && bp_displayed_user_id() == bp_loggedin_user_id() ) {
			$sub_nav[] = array(
				'name'            =>  __( 'Shared by Friends', 'buddybox' ),
				'slug'            => 'friends',
				'parent_url'      => $buddybox_link,
				'parent_slug'     => $this->slug,
				'screen_function' => 'buddybox_friends_files',
				'position'        => 20
			);
		}
		

		parent::setup_nav( $main_nav, $sub_nav );
		
	}
	
	/**
	 * Builds the user's navigation in WP Admin Bar
	 *
	 * @uses buddybox_get_slug() to get BuddyBox slug
	 * @uses is_user_logged_in() to check if the user is logged in
	 * @uses bp_loggedin_user_domain() to get current user's profile link
	 * @uses buddybox_get_name() to get BuddyBox plugin name
	 * @uses bp_is_active() to check for the friends component
	 */
	function setup_admin_bar() {

		// Prevent debug notices
		$wp_admin_nav = array();
		$buddybox_slug = buddybox_get_slug();

		// Menus for logged in user
		if ( is_user_logged_in() ) {

			// Setup the logged in user variables
			$buddybox_link = trailingslashit( bp_loggedin_user_domain() . $buddybox_slug );

			// Add main BuddyBox menu
			$wp_admin_nav[] = array(
				'parent' => 'my-account-buddypress',
				'id'     => 'my-account-' . $buddybox_slug,
				'title'  => buddybox_get_name(),
				'href'   => trailingslashit( $buddybox_link )
			);
			
			// Add BuddyBox submenu
			$wp_admin_nav[] = array(
				'parent' => 'my-account-' . $buddybox_slug,
				'id'     => 'my-account-' . $buddybox_slug .'-files',
				'title'  => __( 'BuddyBox Files', 'buddybox' ),
				'href'   => trailingslashit( $buddybox_link )
			);
			
			if( bp_is_active('friends') ) {
				// Add shared by friends BuddyBox submenu
				$wp_admin_nav[] = array(
					'parent' => 'my-account-' . $buddybox_slug,
					'id'     => 'my-account-' . $buddybox_slug .'-friends',
					'title'  => __( 'Shared by Friends', 'buddybox' ),
					'href'   => trailingslashit( $buddybox_link . 'friends' )
				);
			}

		}

		parent::setup_admin_bar( $wp_admin_nav );
	}

	/**
	 * registering BuddyBox custom post types
	 * 
	 * @uses buddybox_get_folder_post_type() to get the BuddyFolder post type
 	 * @uses buddybox_get_file_post_type() to get the BuddyFile post type
 	 * @uses register_post_type() to register the post type
	 */
	function register_post_types() {
		
		// Set up some labels for the post type
		$labels_file = array(
			'name'	             => __( 'BuddyFiles', 'buddybox' ),
			'singular'           => __( 'BuddyFile', 'buddybox' ),
			'menu_name'          => __( 'BuddyBox Files', 'buddybox' ),
			'all_items'          => __( 'All BuddyFiles', 'buddybox' ),
			'singular_name'      => __( 'BuddyFile', 'buddybox' ),
			'add_new'            => __( 'Add New BuddyFile', 'buddybox' ),
			'add_new_item'       => __( 'Add New BuddyFile', 'buddybox' ),
			'edit_item'          => __( 'Edit BuddyFile', 'buddybox' ),
			'new_item'           => __( 'New BuddyFile', 'buddybox' ),
			'view_item'          => __( 'View BuddyFile', 'buddybox' ),
			'search_items'       => __( 'Search BuddyFiles', 'buddybox' ),
			'not_found'          => __( 'No BuddyFiles Found', 'buddybox' ),
			'not_found_in_trash' => __( 'No BuddyFiles Found in Trash', 'buddybox' )
		);
		
		$args_file = array(
			'label'	            => __( 'BuddyFile', 'buddybox' ),
			'labels'            => $labels_file,
			'public'            => false,
			'rewrite'           => false,
			'show_ui'           => false,
			'show_in_admin_bar' => false,
			'supports'          => array( 'title', 'editor', 'author' )
		);

		// Register the post type for files.
		register_post_type( buddybox_get_file_post_type(), $args_file );


		$labels_folder = array(
			'name'	             => __( 'BuddyFolders', 'buddybox' ),
			'singular'           => __( 'BuddyFolder', 'buddybox' ),
			'menu_name'          => __( 'BuddyBox Folders', 'buddybox' ),
			'all_items'          => __( 'All BuddyFolders', 'buddybox' ),
			'singular_name'      => __( 'BuddyFolder', 'buddybox' ),
			'add_new'            => __( 'Add New BuddyFolder', 'buddybox' ),
			'add_new_item'       => __( 'Add New BuddyFolder', 'buddybox' ),
			'edit_item'          => __( 'Edit BuddyFolder', 'buddybox' ),
			'new_item'           => __( 'New BuddyFolder', 'buddybox' ),
			'view_item'          => __( 'View BuddyFolder', 'buddybox' ),
			'search_items'       => __( 'Search BuddyFolders', 'buddybox' ),
			'not_found'          => __( 'No BuddyFolders Found', 'buddybox' ),
			'not_found_in_trash' => __( 'No BuddyFolders Found in Trash', 'buddybox' )
		);
		
		$args_folder = array(
			'label'	            => __( 'BuddyFolder', 'buddybox' ),
			'labels'            => $labels_folder,
			'public'            => false,
			'rewrite'           => false,
			'show_ui'           => false,
			'show_in_admin_bar' => false,
			'supports'          => array( 'title', 'editor', 'author' )
		);

		// Register the post type for files.
		register_post_type( buddybox_get_folder_post_type(), $args_folder );

		parent::register_post_types();
	}
	
	
	/**
	 * register the BuddyBox upload data in instance
	 *
	 * @uses buddybox_get_upload_data() to get the specific BuddyBox upload datas
	 */
	function register_upload_dir() {
		$upload_data = buddybox_get_upload_data();
		
		if( is_array( $upload_data ) ) {
			buddybox()->upload_dir = $upload_data['dir'];
			buddybox()->upload_url = $upload_data['url'];
		}
		
	}

}

/**
 * Finally Loads the component into the $bp global
 *
 * @uses buddypress()
 */
function buddybox_load_component() {
	buddypress()->buddybox = new BuddyBox_Component;
}
add_action( 'bp_loaded', 'buddybox_load_component' );
?>