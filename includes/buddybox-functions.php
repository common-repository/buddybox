<?php

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * What is the version in db ?
 *
 * @uses get_option() to get the BuddyBox version
 * @return string the version
 */
function buddybox_get_db_version(){
	return get_option( '_buddybox_version' );
}

/**
 * What is the version of the plugin.
 *
 * @uses buddybox()
 * @return string the version of the plugin
 */
function buddybox_get_version() {
	return buddybox()->version;
}

/**
 * Is it the first install ?
 *
 * @uses get_option() to get the BuddyBox version
 * @return boolean true or false
 */
function buddybox_is_install() {
	$buddybox_version = get_option( '_buddybox_version', '' );
	
	if( empty( $buddybox_version ) )
		return true;
	else
		return false;
}

/**
 * Do we need to eventually update ?
 *
 * @uses get_option() to get the BuddyBox version
 * @return boolean true or false
 */
function buddybox_is_update() {
	$buddybox_version = get_option( '_buddybox_version', '' );
	
	if( !empty( $buddybox_version ) )
		return true;
	else
		return false;
}

/**
 * displays the slug of the plugin
 * 
 * @uses buddybox_get_slug() to get it!
 */
function buddybox_slug() {
	echo buddybox_get_slug();
}
	
	/**
	 * Gets the slug of the plugin
	 * 
	 * @uses buddybox()
	 * @return string the slug
	 */
	function buddybox_get_slug() {
		return buddybox()->buddybox_slug;
	}

/**
 * displays the name of the plugin
 * 
 * @uses buddybox_get_name() to get it!
 */
function buddybox_name() {
	echo buddybox_get_name();
}

	/**
	 * Gets the name of the plugin
	 * 
	 * @uses buddybox()
	 * @return string the name
	 */
	function buddybox_get_name() {
		return buddybox()->buddybox_name;
	}

/**
 * displays file post type of the plugin
 * 
 * @uses buddybox_get_file_post_type() to get it!
 */
function buddybox_file_post_type() {
	echo buddybox_get_file_post_type();
}
	
	/**
	 * Gets the file post type of the plugin
	 * 
	 * @uses buddybox()
	 * @return string the file post type
	 */
	function buddybox_get_file_post_type() {
		return buddybox()->buddybox_file_post_type;
	}

/**
 * displays folder post type of the plugin
 * 
 * @uses buddybox_get_folder_post_type() to get it!
 */
function buddybox_folder_post_type() {
	echo buddybox_get_folder_post_type();
}

	/**
	 * Gets the folder post type of the plugin
	 * 
	 * @uses buddybox()
	 * @return string the folder post type
	 */
	function buddybox_get_folder_post_type() {
		return buddybox()->buddybox_folder_post_type;
	}

/**
 * What is the path to the includes dir ?
 *
 * @uses  buddybox()
 * @return string the path
 */
function buddybox_get_includes_dir() {
	return buddybox()->includes_dir;
}

/**
 * What is the path of the plugin dir ?
 *
 * @uses  buddybox()
 * @return string the path
 */
function buddybox_get_plugin_dir() {
	return buddybox()->plugin_dir;
}

/**
 * What is the url to the plugin dir ?
 *
 * @uses  buddybox()
 * @return string the url
 */
function buddybox_get_plugin_url() {
	return buddybox()->plugin_url;
}

/**
 * What is the url of includes dir ?
 *
 * @uses  buddybox()
 * @return string the url
 */
function buddybox_get_includes_url() {
	return buddybox()->includes_url;
}

/**
 * What is the url to the images dir ?
 *
 * @uses  buddybox()
 * @return string the url
 */
function buddybox_get_images_url() {
	return buddybox()->images_url;
}

/**
 * What is the root url for BuddyBox ?
 * 
 * @uses buddybox_get_root_url() to get it
 */
function buddybox_root_url() {
	echo buddybox_get_root_url();
}

	/**
	 * Gets the root url for BuddyBox
	 *
	 * @uses bp_get_root_domain() to get the root blog domain
	 * @uses buddybox_get_slug() to get BuddyBox Slug
	 * @return strin the url
	 */
	function buddybox_get_root_url() {
		$root_domain_url = bp_get_root_domain();
		$buddybox_slug = buddybox_get_slug();
		$buddybox_root_url = trailingslashit( $root_domain_url ) . $buddybox_slug;
		return $buddybox_root_url;
	}

/**
 * Builds an array for BuddyBox upload data
 *
 * @uses wp_upload_dir() to get WordPress basedir and baseurl
 * @return array
 */
function buddybox_get_upload_data() {
	$upload_datas = wp_upload_dir();
	
	$buddybox_dir = $upload_datas["basedir"] .'/buddybox';
	$buddybox_url = $upload_datas["baseurl"] .'/buddybox';
	$buddybox_upload_data = array( 'dir' => $buddybox_dir, 'url' => $buddybox_url );
	
	//finally returns $buddybox_upload_data
	return $buddybox_upload_data;
}

/**
 * Handles Plugin activation
 *
 * @uses bp_core_get_directory_page_ids() to get the BuddyPress component page ids
 * @uses buddybox_get_slug() to get BuddyBox slug
 * @uses wp_insert_post() to eventually create a new page for BuddyBox
 * @uses buddybox_get_name() to get BuddyBox plugin name
 * @uses bp_core_update_directory_page_ids() to update the BuddyPres component pages ids
 */
function buddybox_activation() {

	// let's check for BuddyBox page in directory pages first !
	$directory_pages = bp_core_get_directory_page_ids();
	$buddybox_slug = buddybox_get_slug();

	if( empty( $directory_pages[$buddybox_slug] ) ) {
		// let's create a page and add it to BuddyPress directory pages
		$buddybox_page_content = __( 'BuddyBox uses this page to manage the downloads of your buddies files, please leave it as is. It will not show in your navigation bar.', 'buddybox');

		$buddybox_page_id = wp_insert_post( array( 
												'comment_status' => 'closed', 
												'ping_status'    => 'closed', 
												'post_title'     => buddybox_get_name(),
												'post_content'   => $buddybox_page_content,
												'post_name'      => $buddybox_slug,
												'post_status'    => 'publish', 
												'post_type'      => 'page' 
												) );
		
		$directory_pages[$buddybox_slug] = $buddybox_page_id;
		bp_core_update_directory_page_ids( $directory_pages );
	}

	do_action( 'buddybox_activation' );
}

/**
 * Handles plugin deactivation
 * 
 * @uses bp_core_get_directory_page_ids() to get the BuddyPress component page ids
 * @uses buddybox_get_slug() to get BuddyBox slug
 * @uses wp_delete_post() to eventually delete the BuddyBox page
 * @uses bp_core_update_directory_page_ids() to update the BuddyPres component pages ids
 */
function buddybox_deactivation() {

	$directory_pages = bp_core_get_directory_page_ids();
	$buddybox_slug = buddybox_get_slug();

	if( !empty( $directory_pages[$buddybox_slug] ) ) {
		// let's remove the page as the plugin is deactivated.
		
		$buddybox_page_id = $directory_pages[$buddybox_slug];
		wp_delete_post( $buddybox_page_id, true );
		
		unset( $directory_pages[$buddybox_slug] );
		bp_core_update_directory_page_ids( $directory_pages );
	}


	do_action( 'buddybox_deactivation' );
}

/**
 * Welcome screen step one : set transient
 * 
 * @uses buddybox_is_install() to check of first install
 * @uses set_transient() to temporarly save some data to db
 */
function buddybox_add_activation_redirect() {

	// Bail if activating from network, or bulk
	if ( isset( $_GET['activate-multi'] ) )
		return;

	// Record that this is a new installation, so we show the right
	// welcome message
	if ( buddybox_is_install() ) {
		set_transient( '_buddybox_is_new_install', true, 30 );
	}

	// Add the transient to redirect
	set_transient( '_buddybox_activation_redirect', true, 30 );
}

/**
 * Welcome screen step two
 * 
 * @uses get_transient() 
 * @uses delete_transient()
 * @uses wp_safe_redirect to redirect to the Welcome screen
 * @uses add_query_arg() to add some arguments to the url
 * @uses bp_get_admin_url() to build the admin url
 */
function buddybox_do_activation_redirect() {

	// Bail if no activation redirect
	if ( ! get_transient( '_buddybox_activation_redirect' ) )
		return;

	// Delete the redirect transient
	delete_transient( '_buddybox_activation_redirect' );

	// Bail if activating from network, or bulk
	if ( isset( $_GET['activate-multi'] ) )
		return;

	$query_args = array( 'page' => 'buddybox-about' );

	if ( get_transient( '_buddybox_is_new_install' ) ) {
		$query_args['is_new_install'] = '1';
		delete_transient( '_buddybox_is_new_install' );
	}

	// Redirect to BuddyBox about page
	wp_safe_redirect( add_query_arg( $query_args, bp_get_admin_url( 'index.php' ) ) );
}


/**
 * Checks plugin version against db and updates
 *
 * @uses buddybox_is_install() to see if first install
 * @uses buddybox_get_db_version() to get db version
 * @uses buddybox_get_version() to get BuddyBox plugin version
 */
function buddybox_check_version() {
	if( buddybox_is_install() || version_compare( buddybox_get_db_version(), buddybox_get_version(), '<' ) ) {
		
		update_option( '_buddybox_version', buddybox_get_version() );

	}
}

add_action( 'buddybox_admin_init', 'buddybox_check_version' );


/**
 * Returns the BuddyBox Max upload size
 * 
 * @param  boolean $bytes do we want it in bytes ?
 * @uses wp_max_upload_size() to get the config max upload size
 * @uses get_option() to get the admin settings for BuddyBox
 * @return int the max upload size
 */
function buddybox_max_upload_size( $bytes = false ) {
	$max_upload = wp_max_upload_size();
	$max_upload_mo = $max_upload / 1024 / 1024;
	
	$buddybox_max_upload  = get_option( '_buddybox_max_upload', $max_upload_mo );
	$buddybox_max_upload = intval( $buddybox_max_upload );

	if( empty( $bytes ) )
		return $buddybox_max_upload;
	else
		return $buddybox_max_upload * 1024 * 1024;

}

/**
 * Tells if a value is checked in an array
 * 
 * @param  string $value the value to check
 * @param  array $array where too check ?
 * @uses checked() to activate the checkbox
 * @return boolean|string (false or 'checked')
 */
function buddybox_array_checked( $value = false, $array = false ) {
	
	if( empty( $value ) || empty( $array ) )
		return false;

	$array = array_flip( $array );

	if( in_array( $value, $array ) )
		return checked(true);

}

/**
 * What are the mime types allowed by admin ?
 * 
 * @param  array $allowed_file_types WordPress default
 * @uses get_option() to get the choice of the admin
 * @return array the mime types allowed by admin
 */
function buddybox_allowed_file_types( $allowed_file_types ) {
	
	$allowed_ext = get_option('_buddybox_allowed_extensions');

	if( empty( $allowed_ext ) || !is_array( $allowed_ext ) || count( $allowed_ext ) < 1 )
		return $allowed_file_types;

	$allowed_ext = array_flip($allowed_ext);
	$allowed_ext = array_intersect_key( $allowed_file_types, $allowed_ext );

	return $allowed_ext;
}
