<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;


/**
 * Loads the css and javascript when on Friends/Group BuddyBox
 *
 * @uses bp_is_current_component()  to check for BuddyBox component
 * @uses buddybox_is_group() to include the group case
 * @uses wp_enqueue_style() to load BuddyBox style
 * @uses wp_enqueue_script() to load BuddyBox script
 * @uses buddybox_get_includes_url() to get the includes url
 * @uses wp_localize_script() to add some translation to js messages/output
 * @uses buddybox_get_js_l10n() to get the translation
 * @uses buddybox_is_user_buddybox() to check we're not on loggedin user's BuddyBox
 */
function buddybox_file_enqueue_scripts() {
	
	if ( bp_is_current_component( 'buddybox' ) || buddybox_is_group()  ) {

		// style is for every BuddyBox screens
		wp_enqueue_style( 'buddybox', buddybox_get_includes_url() .'css/buddybox.css' );
		
		// in group and friends BuddyBox, loads a specific script
		if( !buddybox_is_user_buddybox() ) {
			wp_enqueue_script('buddybox-view', buddybox_get_includes_url() .'js/buddybox-view.js', array( 'jquery' ) );
			wp_localize_script( 'buddybox-view', 'buddybox_view', buddybox_get_js_l10n() );
		}
			

	}
		
}

add_action( 'buddybox_enqueue_scripts', 'buddybox_file_enqueue_scripts');


/**
 * Resets WordPress post data
 * 
 * @uses wp_reset_postdata()
 */
function buddybox_reset_post_data() {
	wp_reset_postdata();
}

add_action( 'buddybox_after_loop', 'buddybox_reset_post_data', 1 );


/**
 * Manages file downloads based on the privacy of the file/folder
 *
 * @uses bp_displayed_user_id() to be sure we're not on a profile
 * @uses bp_is_current_component() to check for BuddyBox component
 * @uses bp_current_action() to check if current action is file / folder
 * @uses esc_url()
 * @uses wp_get_referer() to eventually redirect the user
 * @uses bp_action_variable() to get the name of the file / folder
 * @uses buddybox_get_buddyfile() to get the file / folder object
 * @uses buddybox_get_folder_post_type() to get the folder post type
 * @uses bp_loggedin_user_id() to get current user id
 * @uses is_super_admin() as super admin can download anything
 * @uses bp_core_add_message() to eventually display a warning message to user
 * @uses buddybox_get_user_buddybox_url() to construct the user's BuddyBox url
 * @uses bp_core_redirect() to redirect user if needed
 * @uses friends_check_friendship() to check if the current user is friend with the file owner
 * @uses bp_is_active() to check a BuddyPress component is active
 * @uses groups_is_user_member() to check if the current user is member of the group of the file
 * @uses groups_get_group() to get the group object of the group the file / folder is attached to
 * @uses bp_get_group_permalink() to build the group link
 * @uses buddybox_get_group_buddybox_url() to build the link to the BuddyBox of the group
 * @uses site_url() to redirect to home if nothing match
 * @return binary the file! (or redirects to the folder)
 */
function buddybox_file_downloader() {

	if ( !bp_displayed_user_id() && bp_is_current_component( 'buddybox' ) && 'file' == bp_current_action() ) {
		
		$redirect = esc_url( wp_get_referer() );
		
		$buddyfile_name = bp_action_variable( 0 );
		
		$buddybox_file = buddybox_get_buddyfile( $buddyfile_name );
		
		if( empty( $buddybox_file ) )
			bp_core_redirect( buddybox_get_root_url() );
		
		$buddybox_file_path = $buddybox_file->path;
		$buddybox_file_name = $buddybox_file->file;
		$buddybox_file_mime = $buddybox_file->mime_type;
		
		// if the file belongs to a folder, we need to get the folder's privacy settings
		if( !empty( $buddybox_file->post_parent ) ){
			$parent = $buddybox_file->post_parent;
			
			$buddybox_file = buddybox_get_buddyfile( $parent, buddybox_get_folder_post_type() );
		}
		
		$can_donwload = false;
		
		if( !empty( $buddybox_file->check_for ) ) {
			
			switch( $buddybox_file->check_for ) {
				
				case 'private' :
					if( $buddybox_file->user_id == bp_loggedin_user_id() || is_super_admin() )
						$can_donwload = true;
					break;
					
				case 'password' :
					if( $buddybox_file->user_id == bp_loggedin_user_id() || is_super_admin() )
						$can_donwload = true;
					elseif( empty( $_POST['buddyfile-form'] ) ) {
						bp_core_add_message( __( 'This file is password protected', 'buddybox' ), 'error' );
						add_action( 'buddybox_directory_content', 'buddybox_file_password_form' );
						$can_donwload = false;
					} else {
						//check admin referer

						if( $buddybox_file->password == $_POST['buddyfile-form']['password']  )
							$can_donwload = true;

						else {
							$redirect = buddybox_get_user_buddybox_url( $buddybox_file->user_id );
							bp_core_add_message( __( 'Wrong password', 'buddybox' ), 'error' );
							bp_core_redirect( $redirect );
							$can_donwload = false;
						}

					}
					break;
					
				case 'public' :
					$can_donwload = true;
					break;
					
				case 'friends' :
					if( $buddybox_file->user_id == bp_loggedin_user_id() || is_super_admin() )
						$can_donwload = true;
					elseif( bp_is_active( 'friends' ) && friends_check_friendship( $buddybox_file->user_id, bp_loggedin_user_id() ) )
						$can_donwload = true;
					else {
						$redirect = buddybox_get_user_buddybox_url( $buddybox_file->user_id );
						bp_core_add_message( __( 'You must be a friend of this member to download the file', 'buddybox' ), 'error' );
						bp_core_redirect( $redirect );
						$can_donwload = false;
					}
					break;
					
				case 'groups' :
					if( $buddybox_file->user_id == bp_loggedin_user_id() || is_super_admin() )
						$can_donwload = true;
					elseif( !bp_is_active( 'groups' ) ) {
						bp_core_add_message( __( 'Group component is deactivated, please contact the administrator.', 'buddybox' ), 'error' );
						bp_core_redirect( buddybox_get_root_url() );
						$can_donwload = false;
					}
					elseif( groups_is_user_member( bp_loggedin_user_id(), intval( $buddybox_file->group ) ) )
						$can_donwload = true;
					else{
						$group = groups_get_group( array( 'group_id' => $buddybox_file->group ) );
						$redirect = bp_get_group_permalink( $group );
						bp_core_add_message( __( 'You must be member of the group to download the file', 'buddybox' ), 'error' );
						bp_core_redirect( $redirect );
						$can_donwload = false;
					}
					break;
			}
			
		} else {
			if( $buddybox_file->user_id == bp_loggedin_user_id() || is_super_admin() )
				$can_donwload = true;
		}
		
		// we have a file! let's force download.
		if( file_exists( $buddybox_file_path ) && !empty( $can_donwload ) ){
			status_header( 200 );
			header( 'Cache-Control: cache, must-revalidate' );
			header( 'Pragma: public' );
			header( 'Content-Description: File Transfer' );
			header( 'Content-Length: ' . filesize( $buddybox_file_path ) );
			header( 'Content-Disposition: attachment; filename='.$buddybox_file_name );
			header( 'Content-Type: ' .$buddybox_file_mime );
			readfile( $buddybox_file_path );
			die();
		}
		
	} else if( !bp_displayed_user_id() && bp_is_current_component( 'buddybox' ) && 'folder' == bp_current_action() ) {
		
		$buddyfolder_name = bp_action_variable( 0 );
		
		$buddyfolder = buddybox_get_buddyfile( $buddyfolder_name, buddybox_get_folder_post_type() );

		if( empty( $buddyfolder ) )
			bp_core_redirect( buddybox_get_root_url() );

		// in case of the folder, we open it on the user's BuddyBox or the group one
		$buddybox_root_link = ( $buddyfolder->check_for == 'groups' ) ? buddybox_get_group_buddybox_url( $buddyfolder->group ) : buddybox_get_user_buddybox_url( $buddyfolder->user_id ) ;
		$link = $buddybox_root_link .'?folder-'. $buddyfolder->ID;
		bp_core_redirect( $link );
	}
}

add_action( 'buddybox_actions', 'buddybox_file_downloader', 1 );


/**
 * Adds post datas to include a file / folder to a private message
 *
 * @uses buddybox_get_buddyfile() gets the BuddyFile Object
 * @uses buddybox_get_file_post_type() gets the BuddyFile post type
 * @uses buddybox_get_folder_post_type() gets the BuddyFolder post type
 * @uses bp_loggedin_user_id() to get current user id
 * @uses buddybox_get_user_buddybox_url() to build the folder url on user's BuddyBox
 * @return string html output and inputs
 */
function buddybox_attached_file_to_message() {
	
	if( !empty( $_REQUEST['buddyitem'] ) ) {
		
		$link = $buddytype = $password = false;
		$buddyitem = buddybox_get_buddyfile( $_REQUEST['buddyitem'], array( buddybox_get_file_post_type(), buddybox_get_folder_post_type() ) );
		
		if( !empty( $buddyitem->ID ) ){
			
			if( $buddyitem->user_id != bp_loggedin_user_id() ) {
				?>
				<div id="message" class="error"><p><?php _e( 'Cheating ?', 'buddybox' );?></p></div>
				<?php
				return;
			}
			
			$link = $buddyitem->link;

			if( $buddyitem->post_type == buddybox_get_file_post_type() ) {
				$displayed_link = $buddyitem->link;
				$buddytype = 'BuddyBox File';

				if( !empty( $buddyitem->post_parent ) ) {
					$parent = buddybox_get_buddyfile( $buddyitem->post_parent, buddybox_get_folder_post_type() );
					$password = !empty( $parent->password ) ? $parent->password : false ;
				} else
					$password = !empty( $buddyitem->password  ) ? $buddyitem->password : false ;

			} else {
				$displayed_link = buddybox_get_user_buddybox_url( bp_loggedin_user_id() ) . '?folder-'.$buddyitem->ID ;
				$buddytype = 'BuddyBox Folder';
				$password = !empty( $buddyitem->password  ) ? $buddyitem->password : false ;
			}
			?>
			<p>
				<label for="buddyitem-link"><?php printf( __( '%s attached : %s', 'buddybox' ), $buddytype, '<a href="'. $displayed_link.'">'.$buddyitem->title.'</a>');?></label>
				<input type="hidden" value="<?php echo $link;?>" id="buddyitem-link" name="_buddyitem_link">
				<input type="hidden" value="<?php echo $buddyitem->ID;?>" id="buddyitem-id" name="_buddyitem_id">
				
				<?php if( !empty( $password ) ) :?>
					<input type="checkbox" name="_buddyitem_pass" value="1" checked> <?php _e('Automatically add the password in the message', 'buddybox');?>
				<?php endif;?>
			</p>
			<?php
		}
	}
}


/**
 * adds a hook to include previous function and a filter to eventually add friends recipients
 * 
 * @uses bp_is_active() to check a BuddyPress component is active
 */
function buddybox_messages_screen_compose() {
	
	if( !empty( $_REQUEST['buddyitem'] ) ) {
		
		add_action( 'bp_after_messages_compose_content', 'buddybox_attached_file_to_message' );
		
		if( !empty( $_REQUEST['friends'] ) && bp_is_active( 'friends' ) )
			add_filter( 'bp_get_message_get_recipient_usernames', 'buddybox_add_friend_to_recipients', 10, 1 );
		
	}
	
}

add_action( 'messages_screen_compose', 'buddybox_messages_screen_compose' );


/**
 * Adds the link to the file or list of files at the bottom of the message
 * 
 * @param  string $message the content of the  private message
 * @uses buddybox_get_buddyfile() to get the file or folder object
 * @uses buddybox_get_file_post_type() to get the file post type
 * @uses buddybox_get_folder_post_type() to get the folder post type
 * @return string $message with the link to the file/folder
 */
function buddybox_update_message_content( $message ) {
	
	if( !empty( $_POST['_buddyitem_link'] ) ){
		
		$password = $password_check = false;
		
		if( !empty( $_POST['_buddyitem_pass'] ) ) {
			
			$buddyitem = buddybox_get_buddyfile( $_REQUEST['_buddyitem_id'], array( buddybox_get_file_post_type(), buddybox_get_folder_post_type() ) );
			
			if( !empty( $buddyitem->post_parent ) ) {
				$parent = buddybox_get_buddyfile( $buddyitem->post_parent, buddybox_get_folder_post_type() );
				$password_check = $parent->password;
			} else
				$password_check = $buddyitem->password;

			$password = !empty( $password_check ) ? '<p>'.sprintf( __('Password : %s', 'buddybox'), $password_check ) .'</p>' : false;
			
		}
		
		$message->message .= "\n" . $_POST['_buddyitem_link'] . "\n" . $password ;
	}
	
}

add_action( 'messages_message_before_save', 'buddybox_update_message_content', 10, 1 );


/**
 * Update the privacy of children files linked to a folder if updated
 * 
 * @param  array $params associtive array of parameters
 * @param  array $args   
 * @param  object $item the folder object
 * @uses buddybox_get_folder_post_type() to check it's a folder
 * @uses BuddyBox_Item::update_children() to update the files
 */
function buddybox_update_children( $params, $args, $item ) {
	if( $item->post_type != buddybox_get_folder_post_type() )
		return;
		
	$parent_id = intval( $params['id'] );
	$metas = $params['metas'];
	
	$buddybox_update_children = new BuddyBox_Item();
	$buddybox_update_children->update_children( $parent_id, $metas );
	
}

add_action( 'buddybox_update_item', 'buddybox_update_children', 1, 3 ) ;


/**
 * Removes Buddyfiles, BuddyFolders and files of a deleted user
 * 
 * @param  int $user_id the id of the deleted user
 * @uses buddybox_delete_item() to remove user's BuddyBox content
 */
function buddybox_remove_user( $user_id ) {
	buddybox_delete_item( array( 'user_id' => $user_id ) );
}

add_action( 'wpmu_delete_user',  'buddybox_remove_user', 11, 1 );
add_action( 'delete_user',       'buddybox_remove_user', 11, 1 );
add_action( 'bp_make_spam_user', 'buddybox_remove_user', 11, 1 );