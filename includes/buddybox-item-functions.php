<?php

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Populates the translation array for js messages
 * 
 * @return array the js translation
 */
function buddybox_get_js_l10n() {
	$buddyboxl10n = array( 
				'one_at_a_time'        => __( 'Please, add only one file at a time', 'buddybox' ),
				'loading'              => __( 'loading..', 'buddybox' ),
				'shared'               => __( 'Shared', 'buddybox' ),
				'group_remove_error'   => __( 'Error: Item could not be removed from current group', 'buddybox' ),
				'cbs_message'          => __( 'Please use the checkboxes to select one or more items', 'buddybox' ),
				'cb_message'           => __( 'Please use the checkbox to select one item to edit', 'buddybox' ),
				'confirm_delete'      => __( 'Are you sure you want to delete %d item(s) ?', 'buddybox' ),
				'delete_error_message' => __( 'Error: Item(s) could not be deleted', 'buddybox' ),
				'title_needed'         => __( 'The title is required', 'buddybox' ),
				'group_needed'         => __( 'Please choose a group in the list', 'buddybox' ),
				'pwd_needed'           => __( 'Please choose a password', 'buddybox' ),
				'define_pwd'           => __( 'Define your password', 'buddybox' )
		);

	return $buddyboxl10n;
}


/**
 * Builds the user's BuddyBox root url
 * 
 * @param  integer $user_id the id of the user
 * @uses bp_displayed_user_id() to get the displayed user id
 * @uses bp_loggedin_user_id() to get the current user id
 * @uses bp_core_get_user_domain to get the user's home page link
 * @uses buddybox_get_slug() to get the slug of BuddyBox
 * @return string $buddybox_link the link to user's BuddyBox
 */
function buddybox_get_user_buddybox_url( $user_id = 0 ) {
	if( empty( $user_id ) ) {
		$displayed_user_id = bp_displayed_user_id();
		$user_id = !empty( $displayed_user_id ) ? $displayed_user_id : bp_loggedin_user_id();
	}
		
	$user_domain = bp_core_get_user_domain( $user_id );

	$buddybox_link = trailingslashit( $user_domain . buddybox_get_slug() );
	
	return $buddybox_link;
}


/**
 * Builds the BuddyBox Group url
 * 
 * @param integer $group_id the group id
 * @uses groups_get_group to get group datas
 * @uses groups_get_current_group() if no id was given to get current group datas
 * @uses bp_get_group_permalink() to build the group permalink
 * @uses buddybox_get_slug() to get the BuddyBox slug to end to the group url
 * @return string $buddybox_link the link to user's BuddyBox
 */
function buddybox_get_group_buddybox_url( $group_id = 0 ) {
	$buddybox_link = false;

	if( !empty( $group_id ) )
		$group = groups_get_group( array( 'group_id' => $group_id ) );
	else
		$group = groups_get_current_group();

	if( !empty( $group ) ) {
		$group_link = bp_get_group_permalink( $group );

		$buddybox_link = trailingslashit( $group_link . buddybox_get_slug() );
	}

	return $buddybox_link;
		
}


/**
 * Builds the link to the Shared by friends BuddyBox
 * 
 * @param  integer $user_id the id of the user
 * @uses bp_displayed_user_id() to get displayed user id
 * @uses bp_core_get_user_domain() to get the user's home page url
 * @uses buddybox_get_slug() to get BuddyBox slug
 * @return string  $buddybox_friends the url to the shared by friends BuddyBox
 */
function buddybox_get_friends_buddybox_url( $user_id = 0 ) { 
	if( empty( $user_id ) )
		$user_id = bp_displayed_user_id();

	$user_domain = bp_core_get_user_domain( $user_id );

	$buddybox_link = trailingslashit( $user_domain . buddybox_get_slug() );

	$buddybox_friends = trailingslashit( $buddybox_link . 'friends' );
	
	return $buddybox_friends;
}

/**
 * Are we on a group's BuddyBox ?
 *
 * @uses bp_is_groups_component() to check we're on the group component
 * @uses bp_is_single_item() to check we're in a single group
 * @uses bp_is_current_action() to check the acction is BuddyBox
 * @return boolean true or false
 */
function buddybox_is_group() {
	if( bp_is_groups_component() && bp_is_single_item() && bp_is_current_action( 'buddybox' ) )
		return true;
		
	else return false;
}


/**
 * Are we on current user's BuddyBox
 *
 * @uses is_user_logged_in() to check we have a loggedin user
 * @uses bp_is_my_profile() to check the current user is on his profile
 * @uses bp_current_action() to check he's on his BuddyBox
 * @return boolean true or false
 */
function buddybox_is_user_buddybox() {
	if ( is_user_logged_in() && bp_is_my_profile() && bp_current_action() == 'files' )
		return true;
		
	else
		return false;
}

/**
 * Holds the variables we need while using ajax
 * 
 * @return array the args to pass to the BuddyBox Loop
 */
function buddybox_querystring() {
	return apply_filters( 'buddybox_querystring', array() );
}


/**
 * Saves or Updates a BuddyBox item
 * 
 * @param  array $args the different argument of the item to save
 * @uses bp_loggedin_user_id() to default to current user id
 * @uses wp_parse_args() to merge defaults and args array
 * @uses BuddyBox_Item::save() to save data in DB
 * @return int the item id
 */
function buddybox_save_item( $args = '' ) {

	$defaults = array(
		'id'               => false,
		'type'             => '',
		'user_id'          => bp_loggedin_user_id(),
		'parent_folder_id' => 0,
		'title'            => false,
		'content'          => false,
		'mime_type'        => false,
		'guid'             => false,
		'metas'            => false,
	);
	
	$params = wp_parse_args( $args, $defaults );
	extract( $params, EXTR_SKIP );

	// Setup item to be added
	$buddybox_item                   = new BuddyBox_Item();
	$buddybox_item->id               = $id;
	$buddybox_item->type             = $type;
	$buddybox_item->user_id          = $user_id;
	$buddybox_item->parent_folder_id = $parent_folder_id;
	$buddybox_item->title            = $title;
	$buddybox_item->content          = $content;
	$buddybox_item->mime_type        = $mime_type;
	$buddybox_item->guid             = $guid;
	$buddybox_item->metas            = $metas;
	
	if ( !$buddybox_item->save() )
		return false;
		
	do_action( 'buddybox_save_item', $buddybox_item->id, $params );

	return $buddybox_item->id;
}


/**
 * Updates a BuddyBox item
 * 
 * @param array  $args the arguments to update
 * @param object $item the BuddyBox item
 * @uses wp_parse_args() to merge defaults and args array
 * @uses buddybox_get_file_post_type() to get the BuddyFile post type
 * @uses get_post_meta() to get privacy options
 * @uses buddybox_save_item() to update the item
 * @return integer $modified the id of the item updated
 */
function buddybox_update_item( $args = '', $item = false ) {
	
	if( empty( $item ) )
		return false;
		
	$old_pass = !empty( $item->password ) ? $item->password : false;
	$old_group = !empty( $item->group ) ? $item->group : false;
	
	$defaults = array(
		'id'               => $item->ID,
		'type'             => $item->post_type,
		'user_id'          => $item->user_id,
		'parent_folder_id' => $item->post_parent,
		'title'            => $item->title,
		'content'          => $item->content,
		'mime_type'        => $item->mime_type,
		'guid'             => $item->guid,
		'privacy'          => $item->check_for,
		'password'         => $old_pass,
		'group'            => $old_group,
	);
	
	$params = wp_parse_args( $args, $defaults );
	extract( $params, EXTR_SKIP );
	
	// if the parent folder was set, then we need to define a default privacy status
	if( !empty( $item->post_parent ) && empty( $parent_folder_id ) )
		$privacy = 'private';
	elseif( !empty( $parent_folder_id ) && $type == buddybox_get_file_post_type() )
		$privacy = get_post_meta( $parent_folder_id, '_buddybox_sharing_option', true );

	// building the meta object
	$meta = new stdClass();

	$meta->privacy = $privacy;

	if( $meta->privacy == 'password' )
		$meta->password = !empty( $password ) ? $password : false ;

	if( $meta->privacy == 'groups' )
		$meta->groups = !empty( $group ) ? $group : get_post_meta( $parent_folder_id, '_buddybox_sharing_groups', true );
		
	// preparing the args for buddybox_save_item
	$params['metas'] = $meta;
	// we dont need privacy, password and group as it's in $meta
	unset( $params['privacy'] );
	unset( $params['password'] );
	unset( $params['group'] );
		
	
	$modified = buddybox_save_item( $params );
	
	if( empty( $modified ) )
		return false;
	
	do_action( 'buddybox_update_item', $params, $args, $item );
	
	return $modified;
	
}


/**
 * Deletes one or more BuddyBox Item(s)
 * 
 * @param array $args the argument ( the ids to delete and the user_id to check upon  )
 * @uses bp_loggedin_user_id() to default to current user id
 * @uses wp_parse_args() to merge defaults and args array
 * @uses BuddyBox_Item::delete() to remove datas from DB and files from sysfile
 * @return integer|boolean the number of deleted items or false
 */
function buddybox_delete_item( $args = '' ) {
	$defaults = array(
		'ids'              => false,
		'user_id'          => bp_loggedin_user_id()
	);
	
	$params = wp_parse_args( $args, $defaults );
	extract( $params, EXTR_SKIP );

	if( !empty( $ids ) && !is_array( $ids ) )
		$ids = explode( ',', $ids );

	$buddybox_item = new BuddyBox_Item();

	if( $items = $buddybox_item->delete( $ids, $user_id ) )
		return $items;

	else
		return false;
}


/**
 * Returns BuddyBox items datas for an array of ids
 * 
 * @param array $ids the list of BuddyBox items ids
 * @uses BuddyBox_Item::get_buddybox_by_ids() to query the DB for items
 * @return array BuddyBox items
 */
function buddybox_get_buddyfiles_by_ids( $ids = array() ) {
	if( empty( $ids ) )
		return false;
		
	$buddybox_item = new BuddyBox_Item();
	
	return $buddybox_item->get_buddybox_by_ids( $ids );
}


/**
 * Removes all the BuddyBox Items from a group if it's about to be deleted
 * 
 * @param integer $group_id the group id
 * @uses groups_get_group() to get a group object for the group id
 * @uses BuddyBox_Item::group_remove_items() to delete the group id options for the BuddyBox items
 * @return boolean true or false
 */
function buddybox_remove_buddyfiles_from_group( $group_id = 0 ) {
	if( empty( $group_id ) )
		return false;
		
	$group = groups_get_group( array( 'group_id' => $group_id ) );

	if( empty( $group ) )
		$new_status = 'private';

	else {
		$new_status = ( isset( $group->status ) && 'public' != $group->status ) ? 'private' : 'public';
	}
		
	$buddybox_item = new BuddyBox_Item();
	
	return $buddybox_item->group_remove_items( $group_id, $new_status );
}

add_action( 'groups_before_delete_group', 'buddybox_remove_buddyfiles_from_group', 1 );


/**
 * Gets a single BuddyBox items
 * 
 * @param string|int $name the post name or the id of the item to get
 * @param string $type the BuddyBox post type
 * @uses buddybox_get_file_post_type() to default to the BuddyFile post type
 * @uses BuddyBox_Item::get() to get the BuddyBox item
 * @uses buddybox_get_root_url() to get BuddyBox root url
 * @uses get_post_meta() to get item's privacy options
 * @return object the BuddyBox item
 */
function buddybox_get_buddyfile( $name = false, $type = false ) {
	if( empty( $name ) )
		return false;

	if( empty( $type ) )
		$type = buddybox_get_file_post_type();
		
	$buddybox_file = new BuddyBox_Item();
	
	if( is_numeric( $name ) )
		$args = array( 'id' => $name, 'type' => $type );
	else
		$args = array( 'name' => $name, 'type' => $type );
		
	$buddybox_file->get( $args );
	
	if( empty( $buddybox_file->query->post->ID ) )
		return false;
	
	$buddyfile = new stdClass();
	
	$buddyfile->ID = $buddybox_file->query->post->ID;
	$buddyfile->user_id = $buddybox_file->query->post->post_author;
	$buddyfile->title = $buddybox_file->query->post->post_title;
	$buddyfile->content = $buddybox_file->query->post->post_content;
	$buddyfile->post_parent = $buddybox_file->query->post->post_parent;
	$buddyfile->post_type = $buddybox_file->query->post->post_type;
	$buddyfile->guid = $buddybox_file->query->post->guid;

	// let's default to a folder
	$buddyitem_slug = $buddyfile->mime_type = 'folder';

	// do we have a file ?
	if( $buddyfile->post_type == buddybox_get_file_post_type() ) {
		$buddyitem_slug = 'file';
		$buddyfile->file = basename( $buddybox_file->query->post->guid );
		$buddyfile->path = buddybox()->upload_dir .'/'. $buddyfile->file;
		$buddyfile->mime_type = $buddybox_file->query->post->post_mime_type;
	}

	$slug = trailingslashit( $buddyitem_slug .'/' . $buddybox_file->query->post->post_name );
	$link = buddybox_get_root_url() .'/'. $slug;
	$buddyfile->link = $link;
	
	/* privacy */
	$privacy = get_post_meta( $buddyfile->ID, '_buddybox_sharing_option', true );
	
	// by default check for user_id
	
	$buddyfile->check_for = 'private';
	
	if( !empty( $privacy ) ) {
		switch ( $privacy ) {
			case 'private' :
				$buddyfile->check_for = 'private';
				break;

			case 'password' :
				$buddyfile->check_for = 'password';
				$buddyfile->password = !empty( $buddybox_file->query->post->post_password ) ? $buddybox_file->query->post->post_password : false ;
				break;

			case 'public'  :
				$buddyfile->check_for = 'public';
				break;
				
			case 'friends'  :
				$buddyfile->check_for = 'friends';
				break;
			
			case 'groups'  :
				$buddyfile->check_for = 'groups';
				$buddyfile->group = get_post_meta( $buddyfile->ID, '_buddybox_sharing_groups', true );
				break;
				
			default :
				$buddyfile->check_for = 'private';
				break;
		}
	}
	
	return $buddyfile;
	
}


/**
 * Removes a single BuddyBox items from group 
 * 
 * @param int $item_id  the BuddyBox item id
 * @param int $group_id the group id
 * @uses groups_get_group() to get the group object for the given group_id
 * @uses BuddyBox_Item::remove_from_group() to delete the options in the DBs
 * @return boolean true or false
 */
function buddybox_remove_item_from_group( $item_id =false , $group_id = false ) {

	$buddybox_item = new BuddyBox_Item();

	$group = groups_get_group( array( 'group_id' => $group_id ) );

	if( empty( $group ) )
		$new_status = 'private';

	else {
		$new_status = ( isset( $group->status ) && 'public' != $group->status ) ? 'private' : 'public';
	}
	
	return $buddybox_item->remove_from_group( $item_id, $new_status );
}


/**
 * Handles an embed BuddyBox item
 * 
 * @param array $matches the result of the preg_match
 * @param array $attr
 * @param string $url
 * @param array $rawattr
 * @global int $blog_id
 * @uses is_multisite() to check for multisite config
 * @uses bp_get_root_blog_id() to get the root blog id
 * @uses switch_to_blog() to change for root blog id
 * @uses buddybox_get_buddyfile() to get the BuddyBox Item
 * @uses buddybox_get_file_post_type() to get the BuddyFile post type
 * @uses wp_mime_type_icon() to get the WordPress crystal icon
 * @uses buddybox_get_folder_post_type() to get the BuddyFolder post type
 * @uses buddybox_get_group_buddybox_url() to build the url to the BuddyBox group
 * @uses buddybox_get_user_buddybox_url() to get the user's BuddyBox url
 * @uses buddybox_get_images_url() to get the image url of the plugin
 * @uses the BuddyBox Loop and some tempkate tags
 * @uses wp_reset_postdata() to avoid some weird link..
 * @uses restore_current_blog() to restore the child blog.
 * @return string $embed the html output
 */
function wp_embed_handler_buddybox( $matches, $attr, $url, $rawattr ) {
	global $blog_id;
	
	$link = $title = $icon = $content = $mime_type = $filelist = false;
	$current_blog = $blog_id;
	
	if( is_multisite() && $current_blog != bp_get_root_blog_id() )
		switch_to_blog( bp_get_root_blog_id() );

	if( $matches[1] == 'file' ) {
		$buddyfile = buddybox_get_buddyfile( $matches[2], buddybox_get_file_post_type() );

		if( empty( $buddyfile ) )
			return false;

		$link = $buddyfile->link;
		$title = $buddyfile->title;
		$content = $buddyfile->content;
		$icon = wp_mime_type_icon( $buddyfile->ID );
		$mime_type = $buddyfile->mime_type;
	} else {

		$buddyfile = buddybox_get_buddyfile( $matches[2], buddybox_get_folder_post_type() );

		if( empty( $buddyfile ) )
			return false;

		$buddybox_root_link = ( $buddyfile->check_for == 'groups' ) ? buddybox_get_group_buddybox_url( $buddyfile->group ) : buddybox_get_user_buddybox_url( $buddyfile->user_id ) ;
		$link = $buddybox_root_link .'?folder-'. $buddyfile->ID;
		$title = $buddyfile->title;
		$mime_type = $buddyfile->mime_type;
		$icon = buddybox_get_images_url() . 'folder.png';
	}
	
	$embed = '<table style="width:auto"><tr>';
	$embed .= '<td style="vertical-align:middle;width:60px;"><a href="'.$link.'" title="'.$title.'"><img src="'.$icon.'" alt="'.$mime_type.'" class="buddybox-thumb"></a></td>';
	$embed .= '<td style="vertical-align:middle"><h6 style="margin:0"><a href="'.$link.'" title="'.$title.'">'.$title.'</a></h6>';
	
	if( !empty( $content ) )
		$embed .= '<p style="margin:0">'.$content.'</p>';

	if( $matches[1] == 'folder' ) {

		if ( buddybox_has_items( array( 'buddybox_parent' => $buddyfile->ID ) ) ) {
			$filelist = '<p style="margin-top:1em;margin-bottom:0">'.__('Files included in this folder :', 'buddybox') .'</p><ul>';
			while ( buddybox_has_items() ) {
				buddybox_the_item();
				$filelist .= '<li><a href="'.buddybox_get_action_link().'" title="'.buddybox_get_item_title().'">'.buddybox_get_item_title().'</a></li>';
			}
			$filelist .= '</ul>';
		}
		wp_reset_postdata();
		$embed .= $filelist;

	}
		
	$embed .= '</td></tr></table>';
	
	if( is_multisite() && $current_blog != bp_get_root_blog_id() )
		restore_current_blog();

	return apply_filters( 'embed_buddybox', $embed, $matches, $attr, $url, $rawattr );
}