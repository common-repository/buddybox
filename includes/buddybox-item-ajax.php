<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;


/**
 * Files are ajax uploaded !
 *
 * Adds some customization to the WordPress upload process.
 *
 * @uses check_admin_referer() for security reasons
 * @uses is_multisite() to check for multisite
 * @uses wp_handle_upload() to handle file upload
 * @uses bp_loggedin_user_id() to get current user id
 * @uses get_user_meta() to get some additional data about user (quota)
 * @uses update_user_meta() to update this data (quota)
 * @uses wp_kses() to sanitize content & pwd
 * @uses get_post_meta() to get some privacy data of the parent folder
 * @uses buddybox_get_file_post_type() to get the BuddyFile post type
 * @uses buddybox_save_item() to save the BuddyFile
 * @return int id of the the BuddyFile created
 */
function buddybox_save_new_buddyfile() {

	// Bail if not a POST action
	if ( 'POST' !== strtoupper( $_SERVER['REQUEST_METHOD'] ) )
		return;

	// Check the nonce
	check_admin_referer( 'buddybox-form' );

	$output = '';
	$privacy = $group = $password = $parent = false;
	
	// In multisite, we need to remove some filters
	if( is_multisite() ) {
		remove_filter( 'upload_mimes', 'check_upload_mimes' );
		remove_filter( 'upload_size_limit', 'upload_size_limit_filter' );
	}
	
	// temporarly overrides wp upload dir / wp mime types & wp upload size settings with BuddyBox ones
	add_filter( 'upload_dir', 'buddybox_temporarly_filters_wp_upload_dir', 10, 1);
	add_filter( 'upload_mimes', 'buddybox_allowed_upload_mimes', 10, 1 );
	add_filter( 'wp_handle_upload_prefilter', 'buddybox_check_upload_size', 10, 1 );
	
	$buddybox_file = wp_handle_upload( $_FILES['buddyfile-upload'], array( 'action' => 'buddybox_file_upload', 'test_form' => false ) );
	
	if( !empty( $buddybox_file ) && is_array( $buddybox_file ) && empty( $buddybox_file['error'] ) ) {
		/** 
		 * file was uploaded !!
		 * Now we can create the buddybox_file_post_type
		 * 
		 */
		
		//let's take care of quota !
		$user_id = bp_loggedin_user_id();
		$user_total_space = get_user_meta( $user_id, '_buddybox_total_space', true );
		$update_space = !empty( $user_total_space ) ? intval( $user_total_space ) + intval( $_FILES['buddyfile-upload']['size'] ) : intval( $_FILES['buddyfile-upload']['size'] );
		update_user_meta( $user_id, '_buddybox_total_space', $update_space );


		$name = $_FILES['buddyfile-upload']['name'];
		$name_parts = pathinfo( $name );
		$name = trim( substr( $name, 0, -( 1 + strlen( $name_parts['extension'] ) ) ) );
		$content = !empty( $_POST['buddydesc'] ) ? wp_kses( $_POST['buddydesc'], array() ) : false;
		$meta = false;
		
		if( !empty( $_POST['buddyshared'] ) ) {
			
			$privacy = !empty( $_POST['buddyshared'] ) ? $_POST['buddyshared'] : 'private';
			$group = !empty( $_POST['buddygroup'] ) ? $_POST['buddygroup'] : false;
			$password = !empty( $_POST['buddypass'] ) ? wp_kses( $_POST['buddypass'], array() ) : false;
			
		}
		
		if( !empty( $_POST['buddyfolder'] ) ) {
			
			$parent = intval( $_POST['buddyfolder'] );

			$privacy = get_post_meta( $parent, '_buddybox_sharing_option', true );

			if( $privacy == 'groups' ) 
				$group = get_post_meta( $parent, '_buddybox_sharing_groups', true );
		}
		
		$meta = new stdClass();
		
		$meta->privacy = $privacy;
	
		$meta->password = !empty( $password ) ? $password : false ;
			
		$meta->groups = !empty( $group ) ? $group : false;

		// Construct the buddybox_file_post_type array
		$args = array(
			'type' => buddybox_get_file_post_type(),
			'guid' => $buddybox_file['url'],
			'title' => $name,
			'content' => $content,
			'mime_type' => $buddybox_file['type'],
			'metas' => $meta
		);
		
		if( !empty( $parent ) )
			$args['parent_folder_id'] = $parent;
		
		$buddyfile_id = buddybox_save_item( $args );
		
		echo $buddyfile_id;
		
	} else {
		echo '<div class="error-div"><a class="dismiss" href="#">' . __( 'Dismiss', 'buddybox' ) . '</a><strong>' . sprintf( __( '&#8220;%s&#8221; has failed to upload due to an error : %s', 'buddybox' ), esc_html( $_FILES['buddyfile-upload']['name'] ), $buddybox_file['error'] ) . '</strong><br /></div>';
	}
	
	
	// let's restore wp upload dir settings !
	remove_filter( 'upload_dir', 'buddybox_temporarly_filters_wp_upload_dir', 10, 1);
	remove_filter( 'upload_mimes', 'buddybox_allowed_upload_mimes', 10, 1 );
	remove_filter( 'wp_handle_upload_prefilter', 'buddybox_check_upload_size', 10, 1 );
	
	die();
}

add_action( 'wp_ajax_buddybox_upload', 'buddybox_save_new_buddyfile' );


/**
 * Gets the latest created file once uploaded
 * 
 * Fixes IE shit
 * 
 * @uses the BuddyBox loop to return the file created [description]
 * @uses buddybox_get_template() to get the template needed on BP Default or other themes
 * @return string html (the table row)
 */
function buddybox_fetch_created_file() {
	// Bail if not a POST action
	if ( 'POST' !== strtoupper( $_SERVER['REQUEST_METHOD'] ) )
		return;

	$buddyfile_id = intval( $_POST['createdid'] );

	if( !empty( $buddyfile_id ) ) {
		if ( buddybox_has_items ( 'id=' . $buddyfile_id ) ) {
			while ( buddybox_has_items() ) {
				buddybox_the_item();
				buddybox_get_template( 'buddybox-entry' );
			}
		}
	}

	die();
}


add_action( 'wp_ajax_buddybox_fetchfile', 'buddybox_fetch_created_file' );


/**
 * Create a folder thanks to Ajax
 *
 * @uses check_admin_referer() for security reasons
 * @uses wp_kses() to sanitize pwd and title
 * @uses buddybox_get_folder_post_type() to get folder post type
 * @uses buddybox_save_item() to save the folder
 * @uses the BuddyBox loop to retrieve the folder created
 * @uses buddybox_get_template() to get the template for bp-default or any theme
 * @return string the folder created
 */
function buddybox_save_new_buddyfolder() {

	// Bail if not a POST action
	if ( 'POST' !== strtoupper( $_SERVER['REQUEST_METHOD'] ) )
		return;

	// Check the nonce
	check_admin_referer( 'buddybox_actions', '_wpnonce_buddybox_actions' );

	if( !empty( $_POST['title'] ) ) {
		$buddybox_title = $_POST['title'];
		
		if( !empty( $_POST['sharing_option'] ) ) {
			$meta = new stdClass();
			
			$meta->privacy = $_POST['sharing_option'];
			
			if( $_POST['sharing_option'] == 'password' )
				$meta->password = wp_kses( $_POST['sharing_pass'], array() );
				
			if( $_POST['sharing_option'] == 'groups' )
				$meta->groups = $_POST['sharing_group'];
		}
		
		
		$args = array(
			'type'  => buddybox_get_folder_post_type(),
			'title' => wp_kses( $buddybox_title, array() ),
			'content' => '',
			'metas' => $meta
		);
		
		$buddyfolder_id = buddybox_save_item( $args );
		
		if ( buddybox_has_items ( 'id=' . $buddyfolder_id ) ) {
			while ( buddybox_has_items() ) {
				buddybox_the_item();
				buddybox_get_template( 'buddybox-entry' );
			}
		}

	}
	
	die();
}

add_action( 'wp_ajax_buddybox_createfolder', 'buddybox_save_new_buddyfolder');


/**
 * Opens a folder and list the files attach to it depending on its privacy
 *
 * @uses buddybox_get_buddyfile() to get the folder
 * @uses buddybox_get_folder_post_type() to get the folder post type
 * @uses bp_is_active() to check if friends or groups components are actives
 * @uses friends_check_friendship() to check if current user is a friend of the folder owner
 * @uses groups_is_user_member() to check if the user is a member of the group the folder is attached to
 * @uses buddybox_get_template() to get the template for bp-default or any theme
 * @return string the list of files
 */
function buddybox_open_buddyfolder() {

	// Bail if not a POST action
	if ( 'POST' !== strtoupper( $_SERVER['REQUEST_METHOD'] ) )
		return;

	$buddyfolder_id = $_POST['folder'];
	$buddyfolder = buddybox_get_buddyfile( $buddyfolder_id, buddybox_get_folder_post_type() );
	$result = array();
	$access = false;
	$buddyscope = $_POST['scope'];
	
	if( empty( $buddyfolder->ID ) ) {
		
		$result[] = '<tr id="no-buddyitems"><td colspan="5"><div id="message" class="info"><p>'. __( 'Sorry, this folder does not exist anymore.', 'buddybox' ).'</p></div></td></tr>';
		
	} else {
		
		switch( $buddyfolder->check_for ) {
			case 'private' :
				$access = ( $buddyfolder->user_id == bp_loggedin_user_id() ) ? true : false;
				break;
			
			case 'public' :
				$access = true;
				break;
				
			case 'password' :
				$access = true;
				break;
				
			case 'friends' :
				if( ( bp_is_active( 'friends' ) && friends_check_friendship( $buddyfolder->user_id, bp_loggedin_user_id() ) ) || ( $buddyfolder->user_id == bp_loggedin_user_id() ) )
					$access = true;
				else
					$access = false;

				break;
				
			case 'groups' :
				if( bp_is_active( 'groups' ) && groups_is_user_member( bp_loggedin_user_id(), intval( $buddyfolder->group ) ) )
					$access = true;
				else if( $buddyfolder->user_id == bp_loggedin_user_id() )
					$access = true;
				else
					$access = false;
				break;
		}
		
		if( !empty( $access ) ) {
				
			ob_start();
			buddybox_get_template( 'buddybox-loop' );
			$result[] = ob_get_contents();
			ob_end_clean();
			
		} else {
			
			$result[] = '<tr id="no-access"><td colspan="5"><div id="message" class="info"><p>'. __( 'Sorry, this folder is private', 'buddybox' ).'</p></div></td></tr>';
			
		}

		$name_required = !empty( $_POST['foldername'] ) ? 1 : 0;

		if( !empty( $name_required ) ) {
			$result[] = $buddyfolder->title;
		}
		
	}
	
	echo json_encode( $result );
	
	die();
}

add_action( 'wp_ajax_buddybox_openfolder', 'buddybox_open_buddyfolder');
add_action( 'wp_ajax_nopriv_buddybox_openfolder', 'buddybox_open_buddyfolder');


/**
 * Loads more files or folders in the BuddyBox "explorer"
 * 
 * @uses buddybox_get_template() to load the BuddyBox loop
 * @return string more items if there are some
 */
function buddybox_load_more_items() {
	
	ob_start();
	buddybox_get_template( 'buddybox-loop' );
	$result = ob_get_contents();
	ob_end_clean();
	
	echo $result;
		
	die();
}

add_action( 'wp_ajax_buddybox_loadmore', 'buddybox_load_more_items' );
add_action( 'wp_ajax_nopriv_buddybox_loadmore', 'buddybox_load_more_items' );


/**
 * Same as previous except it's for the admin part of the plugin
 *
 * @uses buddybox_admin_edit_files_loop() to list the files
 * @return string more files if there are any
 */
function buddybox_admin_ajax_loadmore() {
	$folder_id = !empty( $_POST['folder'] ) ? intval( $_POST['folder'] ) : -1;
	$paged = !empty( $_POST['page'] ) ? intval( $_POST['page'] ) : -1;

	ob_start();
	buddybox_admin_edit_files_loop( $folder_id, $paged );
	$result = ob_get_contents();
	ob_end_clean();
	
	echo $result;

	die();
}

add_action( 'wp_ajax_buddybox_adminloadmore', 'buddybox_admin_ajax_loadmore');


/**
 * Displays a list of the user's group where BuddyBox is activated
 *
 * @uses bp_loggedin_user_id() to get current user id
 * @uses buddybox_get_select_user_group() to build the select box
 * @return string a select box
 */
function buddybox_list_user_groups() {

	// Bail if not a POST action
	if ( 'POST' !== strtoupper( $_SERVER['REQUEST_METHOD'] ) )
		return;
	
	if( !bp_is_active('groups') )
		exit();
	
	$user_id = !empty( $_POST['userid'] ) ? intval( $_POST['userid'] ) : bp_loggedin_user_id() ;
	$group_id = !empty( $_POST['groupid'] ) ? intval( $_POST['groupid'] ) : false ;
	$name = !empty( $_POST['selectname'] ) ? $_POST['selectname'] : false ;
		 

	$output = buddybox_get_select_user_group( $user_id, $group_id, $name );
	
	echo $output;
	die();
}

add_action( 'wp_ajax_buddybox_getgroups', 'buddybox_list_user_groups' );


/**
 * Ajax deletes folder or files
 *
 * @uses check_admin_referer() for security reasons
 * @uses buddybox_delete_item() deletes the post_type (file or folder)
 * @return array the result with the item ids deleted
 */
function buddybox_delete_items() {

	// Bail if not a POST action
	if ( 'POST' !== strtoupper( $_SERVER['REQUEST_METHOD'] ) )
		return;

	// Check the nonce
	check_admin_referer( 'buddybox_actions', '_wpnonce_buddybox_actions' );
	
	$items = $_POST['items'];
	
	$items = substr( $items, 0, strlen( $items ) - 1 );
	
	$items = explode( ',', $items );
	
	$items_nbre = buddybox_delete_item( array( 'ids' => $items ) );
	
	if( !empty( $items_nbre) )
		echo json_encode( array( 'result' => $items_nbre, 'items' => $items ) );
	else
		echo json_encode( array( 'result' => 0 ) );
	
	die();
	
}


add_action( 'wp_ajax_buddybox_deleteitems', 'buddybox_delete_items');


/**
 * Loads a form to edit a file or a folder
 *
 * @uses buddybox_get_buddyfile() to get the item to edit
 * @uses buddybox_get_folder_post_type() to get the BuddyFolder post type
 * @uses buddybox_get_file_post_type() to get the BuddyFile post type
 * @uses wp_kses() to sanitize data
 * @uses buddybox_select_sharing_options() to display the privacy choices
 * @uses buddybox_select_user_group() to display the groups available
 * @uses buddybox_select_folder_options() to display the available folders
 * @return string the edit form
 */
function buddybox_edit_form() {
	// Bail if not a POST action
	if ( 'POST' !== strtoupper( $_SERVER['REQUEST_METHOD'] ) )
		return;

	$item_id = !empty( $_POST['buddybox_item'] ) ? intval( $_POST['buddybox_item'] ) : false ;
	
	if( !empty( $item_id ) ) {
		$item  = buddybox_get_buddyfile( $item_id, array( buddybox_get_folder_post_type(), buddybox_get_file_post_type() ) );
		?>
		<form class="standard-form" id="buddybox-item-edit-form">

			<div id="buddyitem-description">
				<input type="hidden" id="buddybox-item-id" value="<?php echo $item->ID;?>">
				<label for="buddybox-item-title"><?php _e( 'Name', 'buddybox' );?></label>
				<input type="text" name="buddybox-edit[item-title]" id="buddybox-item-title" value="<?php echo esc_attr( stripslashes( $item->title ) ) ?>" />

				<?php if( $item->post_type == buddybox_get_file_post_type() ) :?>
					<label for="buddybox-item-content"><?php _e( 'Description', 'buddybox' );?></label>
					<textarea name="buddybox-edit[item-content]" id="buddybox-item-content" maxlength="140"><?php echo wp_kses( stripslashes( $item->content ), array() );?></textarea>

				<?php endif;?>
				
			</div>
			
			<?php if( empty( $item->post_parent) ) :?>
				
				<div id="buddybox-privacy-section-options">
					<label for="buddybox-sharing-option"><?php _e( 'Item Sharing options', 'buddybox' );?></label>
					<?php buddybox_select_sharing_options( 'buddyitem-sharing-options', $item->check_for, 'buddybox-edit[sharing]' );?>

					<div id="buddybox-admin-privacy-detail">
						<?php if( $item->check_for == 'password' ):?>
							<label for="buddybox-password"><?php _e( 'Password', 'buddybox' );?></label>
							<input type="text" value="<?php echo esc_attr( stripslashes( $item->password ) ) ?>" name="buddybox-edit[password]" id="buddypass"/>
						<?php elseif( $item->check_for == 'groups' ) :?>
							<label for="buddygroup"><?php _e( 'Choose the group', 'buddybox' );?></label>
							<?php buddybox_select_user_group( $item->user_id, $item->group, 'buddybox-edit[buddygroup]' );?>
						<?php endif;?>
					</div>
				</div>
				
			<?php else :?>
				
				<div id="buddyitem-sharing-option">
					<label for="buddybox-sharing-option"><?php _e( 'Edit your sharing options', 'buddybox' );?></label>
					<p><?php _e( 'Privacy of this item rely on its parent folder', 'buddybox' );?></p>
				</div>
				
			<?php endif;?>
			
			<?php if( $item->post_type == buddybox_get_file_post_type() ) :?>

				<div class="buddyitem-folder-section" id="buddyitem-folder-section-options">
					<label for="buddyitem-folder-option"><?php _e( 'Folder', 'buddybox' );?></label>
					<?php buddybox_select_folder_options( $item->user_id, $item->post_parent, 'buddybox-edit[folder]' );?>
				</div>

			<?php endif;?>
			
			<p class="buddybox-action folder"><input type="submit" value="Edit item" name="buddybox_edit[submit]">&nbsp;<a href="#" class="cancel-item button"><?php _e( 'Cancel', 'buddybox' );?></a></p>
			
		</form>
		<?php
	}
	die();

}

add_action( 'wp_ajax_buddybox_editform', 'buddybox_edit_form' );


/**
 * Updates an item
 *
 * @uses check_admin_referer() for security reasons
 * @uses buddybox_get_buddyfile() to get the item
 * @uses buddybox_get_folder_post_type() to get the BuddyFolder post type
 * @uses buddybox_get_file_post_type() to get the BuddyFile post type
 * @uses wp_kses() to sannitize data
 * @uses buddybox_update_item() to update the item (folder or file)
 * @uses the BuddyBox Loop to get the item updated
 * @uses buddybox_get_template() to get the template for bp-default or any theme
 * @return array containing the updated item
 */
function buddybox_ajax_update_item(){
	// Bail if not a POST action
	if ( 'POST' !== strtoupper( $_SERVER['REQUEST_METHOD'] ) )
		return;

	// Check the nonce
	check_admin_referer( 'buddybox_actions', '_wpnonce_buddybox_actions' );

	$item_id = intval( $_POST['id'] );

	$item = buddybox_get_buddyfile( $item_id, array( buddybox_get_folder_post_type(), buddybox_get_file_post_type() ) );

	if ( empty( $item->title ) ) {
		echo json_encode(array(0));
		die();
	}

	$args = array();
		
	if( !empty( $_POST['title'] ) )
		$args['title'] = wp_kses( $_POST['title'], array() );
	if( !empty( $_POST['content'] ) )
		$args['content'] = wp_kses( $_POST['content'], array() );
	if( !empty( $_POST['sharing'] ) )
		$args['privacy'] = $_POST['sharing'];
	if( !empty( $_POST['password'] ) )
		$args['password'] = wp_kses( $_POST['password'], array() );
	if( !empty( $_POST['group'] ) )
		$args['group'] = $_POST['group'];
	
	$args['parent_folder_id'] = !empty( $_POST['folder'] ) ? intval( $_POST['folder'] ) : 0 ;
		
	$updated = buddybox_update_item( $args, $item );

	$result = array();
	
	if( !empty( $updated ) ) {
		
		if ( buddybox_has_items ( 'id=' . $updated ) ) {
			ob_start();
			while ( buddybox_has_items() ) {
				buddybox_the_item();
				buddybox_get_template( 'buddybox-entry', false );
			}
			$result[] = ob_get_contents();
			ob_end_clean();
		}
		$result[] = $args['parent_folder_id'];
		echo json_encode($result);
	}
	else 
		echo json_encode(array(0));

	die();
}

add_action( 'wp_ajax_buddybox_updateitem', 'buddybox_ajax_update_item' );


/**
 * Post an activity to the group
 *
 * @uses check_admin_referer() for security reasons
 * @uses bp_loggedin_user_id() to get the current user id
 * @uses buddybox_get_folder_post_type() to get the BuddyFolder post type
 * @uses buddybox_get_file_post_type() to get the BuddyFile post type
 * @uses get_post_meta() to get item extra data (privacy)
 * @uses buddybox_get_buddyfile() to get item
 * @uses groups_get_group() to get the group
 * @uses bp_core_get_userlink() to get link to user's profile
 * @uses bp_get_group_permalink() to build the group permalink
 * @uses groups_record_activity() to finaly record the activity
 * @return int 1 or string an error message
 */
function buddybox_share_in_group() {

	// Bail if not a POST action
	if ( 'POST' !== strtoupper( $_SERVER['REQUEST_METHOD'] ) )
		return;

	// Check the nonce
	check_admin_referer( 'buddybox_actions', '_wpnonce_buddybox_actions' );

	$buddyitem = intval( $_POST['itemid'] );
	
	if( empty( $buddyitem ) ) {
		_e( 'this is embarassing, it did not work :(', 'buddybox' );
		die();
	}
	
	if( !bp_is_active( 'groups' ) ) {
		_e( 'Group component is deactivated, please contact the administrator.', 'buddybox' );
		die();
	}
	
	$link = $_POST['url'] ;
	$result = false;
	$user_id = bp_loggedin_user_id();
	$item_type = ( 'folder' == $_POST['itemtype'] ) ? buddybox_get_folder_post_type() : buddybox_get_file_post_type();

	if( !empty( $buddyitem ) ) {
		$group_id = get_post_meta( $buddyitem, '_buddybox_sharing_groups', true );

		if( empty( $group_id ) ) {
			$buddyfile = buddybox_get_buddyfile( $buddyitem, $item_type );
			$parent_id = $buddyfile->post_parent;
			$group_id = get_post_meta( $parent_id, '_buddybox_sharing_groups', true );
		}

		if( !empty( $group_id ) ) {
			$group = groups_get_group( array( 'group_id' => $group_id ) );
		
			$action  = $activity_action  = sprintf( __( '%1$s shared a BuddyBox Item in the group %2$s', 'buddybox'), bp_core_get_userlink( $user_id ), '<a href="' . bp_get_group_permalink( $group ) . '">' . esc_attr( $group->name ) . '</a>' );
			$content = $link;
			$args = array(
					'user_id'   => $user_id,
					'action'    => $action,
					'content'   => $content,
					'type'      => 'activity_update',
					'component' => 'groups',
					'item_id'   => $group_id
			);

			$result = groups_record_activity( $args );
		}

	}
	if( !empty( $result ) )
		echo 1;
	else
		_e( 'this is embarassing, it did not work :(', 'buddybox' );
	die();

}

add_action( 'wp_ajax_buddybox_groupupdate', 'buddybox_share_in_group' );


/**
 * Post an activity in user's profile
 *
 * @uses check_admin_referer() for security reasons
 * @uses bp_loggedin_user_id() to get the current user id
 * @uses buddybox_get_folder_post_type() to get the BuddyFolder post type
 * @uses buddybox_get_file_post_type() to get the BuddyFile post type
 * @uses buddybox_get_buddyfile() to get item
 * @uses bp_core_get_userlink() to get link to user's profile
 * @uses bp_activity_add() to finaly record the activity without updating the latest meta
 * @return int 1 or string an error message
 */
function buddybox_share_in_profile() {

	// Bail if not a POST action
	if ( 'POST' !== strtoupper( $_SERVER['REQUEST_METHOD'] ) )
		return;

	// Check the nonce
	check_admin_referer( 'buddybox_actions', '_wpnonce_buddybox_actions' );

	$buddyitem = intval( $_POST['itemid'] );
	
	if( empty( $buddyitem ) ) {
		_e( 'this is embarassing, it did not work :(', 'buddybox' );
		die();
	}
	
	$link = $_POST['url'] ;
	$result = false;
	$user_id = bp_loggedin_user_id();
	$item_type = ( 'folder' == $_POST['itemtype'] ) ? buddybox_get_folder_post_type() : buddybox_get_file_post_type();

	if( !empty( $buddyitem ) ) {
		
		$buddyfile = buddybox_get_buddyfile( $buddyitem, $item_type );
		
		if( empty( $buddyfile->ID ) || $buddyfile->check_for != 'public' ) {
			// no item or not a public one ??
			_e( 'We could not find your BuddyBox item or its privacy is not set to public', 'buddybox');
			die();
		}
		
		$action  = sprintf( __( '%s shared a BuddyBox Item', 'buddybox'), bp_core_get_userlink( $user_id ) );
		$content = $link;
		$args = array(
				'user_id'      => $user_id,
				'action'       => $action,
				'content'      => $content,
				'primary_link' => bp_core_get_userlink( $user_id, false, true ),
				'component'    => 'activity',
				'type'         => 'activity_update'
		);

		$result = bp_activity_add( $args );

	}
	if( !empty( $result ) )
		echo 1;
	else
		echo _e( 'this is embarassing, it did not work :(', 'buddybox' );
	die();

}

add_action( 'wp_ajax_buddybox_profileupdate', 'buddybox_share_in_profile' );


/**
 * Updates the display of the quota of the current user
 *
 * @uses buddybox_get_user_space_left() to get the quota
 * @return string the quota
 */
function buddybox_update_quota(){
	// Bail if not a POST action
	if ( 'POST' !== strtoupper( $_SERVER['REQUEST_METHOD'] ) )
		return;

	add_filter( 'buddybox_get_user_space_left', 'buddybox_filter_user_space_left', 10, 2 );

	echo buddybox_get_user_space_left();

	remove_filter( 'buddybox_get_user_space_left', 'buddybox_filter_user_space_left', 10, 2 );

	die();
}

add_action( 'wp_ajax_buddybox_updatequota', 'buddybox_update_quota');


/**
 * Filters the querystring
 *
 * Ajax Scope is admin, so we need to uses this trick to have the data we're requesting
 * 
 * @param  array $args the arguments of the BuddyBox query
 * @return array the merge $args with post args
 */
function buddybox_ajax_querystring( $args = false ) {
	$args = array();
	if( !empty( $_POST['page'] ) )
		$args['paged'] = $_POST['page'];
		
	if( !empty( $_POST['folder'] ) )
		$args['buddybox_parent'] = $_POST['folder'];

	if( !empty( $_POST['exclude'] ) )
		$args['exclude'] = $_POST['exclude'];
		
	if( !empty( $_POST['scope'] ) ) {
		$args['buddybox_scope'] = $_POST['scope'];

		if( $args['buddybox_scope'] == 'groups' && !empty( $_POST['group'] ) )
			$args['group_id'] = $_POST['group'];
	}
		
	return $args;
}

add_filter( 'buddybox_querystring', 'buddybox_ajax_querystring', 1, 1 );


/**
 * Removes an item from the group (group admins may wish to)
 *
 * @uses check_admin_referer() for security reasons
 * @uses buddybox_remove_item_from_group() to unattached the file or folder from the group
 * @return int the result
 */
function buddybox_remove_from_group() {
	// Bail if not a POST action
	if ( 'POST' !== strtoupper( $_SERVER['REQUEST_METHOD'] ) )
		return;

	// Check the nonce
	check_admin_referer( 'buddybox_actions', '_wpnonce_buddybox_actions' );

	$item_id = $_POST['itemid'];
	$group_id = $_POST['groupid'];

	if( empty( $item_id ) || empty( $group_id ) )
		echo 0;
	else {
		$removed = buddybox_remove_item_from_group( $item_id, $group_id );
		echo $removed;
	}

	die();
			  
}

add_action( 'wp_ajax_buddybox_removefromgroup', 'buddybox_remove_from_group' );