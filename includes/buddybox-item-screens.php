<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Adds the path to plugin templates to BuddyPress 1.7 BP Theme Compat stack
 * 
 * @param array $templates the different template stacks available
 * @uses bp_is_current_component() to check for BuddyBox Component
 * @uses buddybox_is_group() to check for BuddyBox component in groups
 * @uses buddybox_get_plugin_dir() the path to plugin dir
 * @return array $templates the same array with a new value for BuddyBox path
 */
function buddybox_get_template_stack( $templates ) {
	
	if ( bp_is_current_component( 'buddybox' ) || buddybox_is_group()  ) {
		
		$templates[] = trailingslashit( buddybox_get_plugin_dir() . 'templates' );
	}
	
	return $templates;
}

add_filter( 'bp_get_template_stack', 'buddybox_get_template_stack', 10, 1 );


/**
 * Filters bp_located_template() to eventually add the path to our template (bp-default)
 * 
 * @param string $found_template
 * @param array $templates
 * @uses buddybox_is_bp_default() to check for BP Default or BuddyPress standalone themes
 * @uses bp_is_current_component() to check for BuddyBox component
 * @uses buddybox_get_plugin_dir() the path to plugin dir
 * @return [type]                 [description]
 */
function buddybox_load_template_filter( $found_template, $templates ) {
	global $bp, $bp_deactivated;
	
	if ( !buddybox_is_bp_default() )
		return $found_template;

	//Only filter the template location when we're on the example component pages.
	if ( !bp_is_current_component( 'buddybox' ) )
		return $found_template;

	foreach ( (array) $templates as $template ) {
		if ( file_exists( STYLESHEETPATH . '/' . $template ) )
			$filtered_templates[] = STYLESHEETPATH . '/' . $template;
		else
			$filtered_templates[] = buddybox_get_plugin_dir() . '/templates/' . $template;
	}

	$found_template = $filtered_templates[0];

	return apply_filters( 'buddybox_load_template_filter', $found_template );
}

add_filter( 'bp_located_template', 'buddybox_load_template_filter', 10, 2 );


/**
 * Checks if the active theme is  BP Default or a child or a standalone
 *
 * @uses get_stylesheet() to check for BP Default
 * @uses get_template() to check for a Child Theme of BP Default
 * @uses current_theme_supports() to check for a standalone BuddyPress theme
 * @return boolean true or false
 */
function buddybox_is_bp_default() {
	if( in_array( 'bp-default', array( get_stylesheet(), get_template() ) ) )
        return true;

    if( current_theme_supports( 'buddypress') )
    	return true;

    else
        return false;
}


/**
 * Chooses the best way to load BuddyBox templates
 * 
 * @param string $template the template needed
 * @param boolean $require_once if we need to load it only once or more
 * @uses buddybox_is_bp_default() to check for BP Default
 * @uses load_template()
 * @uses bp_get_template_part()
 */
function buddybox_get_template( $template = false, $require_once = true ) {
	if( empty( $template ) )
		return false;
	
	if( buddybox_is_bp_default() ) {

		$template = $template . '.php';

		if ( file_exists( STYLESHEETPATH . '/' . $template ) )
			$filtered_templates = STYLESHEETPATH . '/' . $template;
		else
			$filtered_templates = buddybox_get_plugin_dir() . '/templates/' . $template;
		
		load_template( apply_filters( 'buddybox_get_template', $filtered_templates ),  $require_once);
		
	} else {
		bp_get_template_part( $template );
	}
}


/**
 * Filters bp_get_template_part() to use our template file
 * 
 * @param array $templates
 * @param string $slug
 * @param string $name
 * @return array our template
 */
function buddybox_filter_template_part( $templates, $slug, $name ) {
	if( $slug != 'members/single/plugins' )
		return $templates;
	
	return array( 'buddybox-explorer.php' );
}


/**
 * Loads the BuddyBox Explorer for user's screen
 *
 * @uses bp_core_load_template to load the template
 * @uses buddybox_is_bp_default() to check for BP Default
 */
function buddybox_user_files(){
	
	bp_core_load_template( apply_filters( 'buddybox_user_files', 'buddybox-explorer' ) );
	
	if( !buddybox_is_bp_default() )
		add_filter( 'bp_get_template_part', 'buddybox_filter_template_part', 10, 3 );
}


/**
 * Loads the BuddyBox Explorer for shared by friends screen
 *
 * @uses bp_core_load_template to load the template
 * @uses buddybox_is_bp_default() to check for BP Default
 */
function buddybox_friends_files(){
	
	bp_core_load_template( apply_filters( 'buddybox_friends_files', 'buddybox-explorer' ) );
	
	if( !buddybox_is_bp_default() )
		add_filter( 'bp_get_template_part', 'buddybox_filter_template_part', 10, 3 );
}


/**
 * Loads the Main BuddyBox template
 * 
 * @uses bp_displayed_user_id() to check we're not on a user's page
 * @uses bp_is_current_component() to check we're on BuddyBox component
 * @uses bp_update_is_directory() to indicates we're on BuddyBox main directory
 * @uses bp_core_load_template() to finally load the template.
 */
function buddybox_screen_index() {
	
	if ( !bp_displayed_user_id() && bp_is_current_component( 'buddybox' ) ) {
		bp_update_is_directory( true, 'buddybox' );

		do_action( 'buddybox_screen_index' );

		bp_core_load_template( apply_filters( 'buddybox_screen_index', 'buddybox' ) );
	}
}

add_action( 'buddybox_screens', 'buddybox_screen_index' );



/** Theme Compatability *******************************************************/

/**
 * The main theme compat class for BuddyBox
 *
 * This class sets up the necessary theme compatability actions to safely output
 * BuddyBox template parts to the_title and the_content areas of a theme.
 *
 * @since BuddyBox (1.0)
 */
class BuddyBox_Theme_Compat {

	/**
	 * Setup the BuddyBox component theme compatibility
	 *
	 * @since BuddyBox (1.0)
	 */
	public function __construct() {
		
		add_action( 'bp_setup_theme_compat', array( $this, 'is_buddybox' ) );
	}

	/**
	 * Are we looking at something that needs BuddyBox theme compatability?
	 *
	 * @since BuddyBox (1.0)
	 * 
	 * @uses bp_displayed_user_id() to check we're not on a user's page
 	 * @uses bp_is_current_component() to check we're on BuddyBox component
	 */
	public function is_buddybox() {
		
		if ( !bp_displayed_user_id() && bp_is_current_component( 'buddybox' ) ) {

			add_action( 'bp_template_include_reset_dummy_post_data', array( $this, 'directory_dummy_post' ) );
			add_filter( 'bp_replace_the_content',                    array( $this, 'directory_content'    ) );

		}
		
	}

	/** Directory *************************************************************/

	/**
	 * Update the global $post with directory data
	 *
	 * @since BuddyBox (1.0)
	 *
	 * @uses bp_theme_compat_reset_post() to reset the post data
	 */
	public function directory_dummy_post() {

		bp_theme_compat_reset_post( array(
			'ID'             => 0,
			'post_title'     => __( 'BuddyBox', 'buddybox' ),
			'post_author'    => 0,
			'post_date'      => 0,
			'post_content'   => '',
			'post_type'      => 'buddybox_dir',
			'post_status'    => 'publish',
			'is_archive'     => true,
			'comment_status' => 'closed'
		) );
	}

	/**
	 * Filter the_content with the groups index template part
	 *
	 * @since BuddyBox (1.0)
	 *
	 * @uses bp_buffer_template_part()
	 */
	public function directory_content() {
		
		bp_buffer_template_part( 'buddybox' );
	}
	
}

new BuddyBox_Theme_Compat();
