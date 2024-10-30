<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

if ( class_exists( 'BP_Group_Extension' ) ) :
/**
 * The BuddyBox group class
 *
 * @package BuddyBox
 * @since 1.0
 * 
 */
class BuddyBox_Group extends BP_Group_Extension {	

	var $visibility  = 'private';
	var $enable_create_step  = false;
	var $enable_nav_item  = true;
	var $enable_edit_item = true;
	

	/**
	 * construct method to add some settings and hooks
	 *
	 * @uses buddybox_get_name() to get the plugin name
	 * @uses buddybox_get_slug() to get the plugin slug
	 */
	function __construct() {
		
		$this->name = buddybox_get_name();
		$this->slug = buddybox_get_slug();
		$this->nav_item_position = 31;
		$this->enable_nav_item = $this->enable_nav_item();
		
		add_action( 'bp_groups_admin_meta_boxes', array( $this, 'admin_ui_edit_screen' ) );
		add_action( 'bp_group_admin_edit_after',  array( $this, 'edit_screen_save'), 10, 1 );
	}

	/**
	 * The create screen method
	 *
	 * BuddyBox do not add a step there
	 * 
	 * @return boolean false
	 */
	function create_screen() {
		return false;
	}

	/**
	 * The create screen save method
	 *
	 * BuddyBox do not have to handle this step
	 * 
	 * @return boolean false
	 */
	function create_screen_save() {
		return false;
	}

	/**
	 * Displays settings in front/backend group admin
	 *
	 * BuddyBox do not add a step there
	 *
	 * @param object $group the group object sent by backend
	 * @uses bp_get_current_group_id() to get the group id
	 * @uses groups_get_groupmeta() to get the BuddyBox option
	 * @uses checked() to activate/deactivate the checkbox
	 * @uses is_admin() to check if we're in WP backend
	 * @return string html output
	 */
	function edit_screen( $group = false ) {
			
		$group_id   = empty( $group->id ) ? bp_get_current_group_id() : $group->id;
		$checked = groups_get_groupmeta( $group_id, '_buddybox_enabled' );
		?>

		<h4><?php echo esc_attr( $this->name ) ?> <?php _e( 'settings', 'buddybox' );?></h4>
		
		<fieldset>
			<legend class="screen-reader-text"><?php echo esc_attr( $this->name ) ?> <?php _e( 'settings', 'buddybox' );?></legend>
			<p><?php _e( 'Allow members of this group to share their BuddyBox folders or files.', 'buddybox' ); ?></p>

			<div class="field-group">
				<div class="checkbox">
					<label><input type="checkbox" name="_group_buddybox_activate" value="1" <?php checked( $checked )?>> <?php _e( 'Activate BuddyBox', 'buddybox' );?></label>
				</div>
			</div>
		
			<?php if ( !is_admin() ) : ?>
				<input type="submit" name="save" value="<?php _e( 'Save', 'buddybox' );?>" />
			<?php endif; ?>

		</fieldset>

		<?php
		wp_nonce_field( 'groups_edit_save_' . $this->slug, 'buddybox_group_admin' );
	}


	/**
	 * Save the settings of the group
	 * 
	 * @param  integer $group_id the group id we save settings for
	 * @uses check_admin_referer() for security reasons
	 * @uses bp_get_current_group_id() to get the group id
	 * @uses groups_update_groupmeta() to set the BuddyBox option if needed
	 * @uses groups_delete_groupmeta() to delete the BuddyBox option if needed
	 * @uses buddybox_remove_buddyfiles_from_group() to eventually remove attached BuddyBox items
	 * @uses is_admin() to check if we're in WP backend
	 * @uses bp_core_add_message() to inform about success / error
	 * @uses bp_core_redirect() to avoid some refreshing stuff
	 * @uses bp_get_group_permalink() to redirect to
	 */
	function edit_screen_save( $group_id = 0 ) {

		if ( 'POST' !== strtoupper( $_SERVER['REQUEST_METHOD'] ) )
			return false;

		check_admin_referer( 'groups_edit_save_' . $this->slug, 'buddybox_group_admin' );
		
		$group_id   = !empty( $group_id ) ? $group_id : bp_get_current_group_id();

		/* Insert your edit screen save code here */
		$buddybox_ok = !empty( $_POST['_group_buddybox_activate'] ) ? $_POST['_group_buddybox_activate'] : false ;
		
		if( !empty($buddybox_ok) ){
			$success = groups_update_groupmeta( $group_id, '_buddybox_enabled', $buddybox_ok );
		} else { 
			$success = groups_delete_groupmeta( $group_id, '_buddybox_enabled' );
			
			// we need to remove folders and items attached to this group in this case
			buddybox_remove_buddyfiles_from_group( $group_id );
		}
		
		if ( !is_admin() ) {
			/* To post an error/success message to the screen, use the following */
			if ( !$success )
				bp_core_add_message( __( 'There was an error saving, please try again', 'buddybox' ), 'error' );
			else
				bp_core_add_message( __( 'Settings saved successfully', 'buddybox' ) );

			bp_core_redirect( bp_get_group_permalink( buddypress()->groups->current_group ) . 'admin/' . $this->slug );
		}
		
	}
	

	/**
	 * Builds the Group Admin UI metabox
	 * 
	 * @uses add_meta_box()
	 * @uses get_current_screen() to get the admin current screen
	 */
	function admin_ui_edit_screen() {
		
		add_meta_box( 
			'buddybox_group_meta_box', 
			_x( 'BuddyBox', 'group admin edit screen', 'buddybox' ), 
			array( &$this, 'admin_edit_metabox'), 
			get_current_screen()->id, 
			'side', 
			'core' 
		);
		
	}
	
	/**
	 * Populates the metabox with edit screen
	 * @param  object $item the group object
	 * @uses BuddyBox_Group::edit_screen  to display the edit form
	 */
	function admin_edit_metabox( $item ) {
		$this->edit_screen( $item );
	}


	/**
	 * Displays the BuddyBox of the group
	 *
	 * @uses bp_get_current_group_id() to get the group id
	 * @uses buddybox_component_home_url() to print the BuddyBox link in the group
	 * @uses buddybox_get_template() to get the template if bp-default or any theme
	 * @return string html output
	 */
	function display() {
		$group_id = bp_get_current_group_id();
		?>
		
		<div class="buddybox-crumbs"><a href="<?php buddybox_component_home_url();?>" name="home" id="buddybox-home" data-group="<?php echo $group_id;?>"><span class="icon-home"></span> <?php _e( 'Root folder', 'buddybox');?></a></div>
		
		<div class="buddybox single-group" role="main">
			<?php buddybox_get_template('buddybox-loop');?>
		</div><!-- .buddybox.single-group -->	
		
		<?php
	}


	/**
	 * We do not use widgets
	 * 
	 * @return boolean false
	 */
	function widget_display() {
		return false;
	}
	

	/**
	 * Loads the BuddyBox navigation if group admin activated BuddyBox
	 *
	 * @uses bp_get_current_group_id() to get the group id
	 * @uses groups_get_groupmeta() to get the BuddyBox option
	 * @return boolean true or false
	 */
	function enable_nav_item() {
		
		$group_id = bp_get_current_group_id();
		
		if( empty( $group_id ) )
			return false;
		
		if ( groups_get_groupmeta( $group_id, '_buddybox_enabled' ) )
			return true;
		else
			return false;
	}
}


bp_register_group_extension( 'BuddyBox_Group' );

endif;