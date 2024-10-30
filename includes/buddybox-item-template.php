<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;


/**
 * Echoes the right link to BuddyBox root folder regarding to context
 *
 * @uses bp_is_my_profile() to check for user's profile
 * @uses bp_current_action() to check for BuddyBox nav
 * @uses buddybox_get_user_buddybox_url() to print the BuuddyBox user's url
 * @uses buddybox_get_friends_buddybox_url() to print the Shared by friends BuddyBox Url
 * @uses buddybox_is_group() to check for the BuddyBox group area
 * @uses buddybox_get_group_buddybox_url() to print the BuddyBox group's url
 * @return the right url
 */
function buddybox_component_home_url() {
	if( bp_is_my_profile() && bp_current_action() == 'files' )
		echo buddybox_get_user_buddybox_url();
	else if( bp_is_my_profile() && bp_current_action() == 'friends' )
		echo buddybox_get_friends_buddybox_url();
	else if( buddybox_is_group() )
		echo buddybox_get_group_buddybox_url();
}


/**
 * Displays a select box to help user chooses the privacy option
 * 
 * @param string $id       the id of the select box
 * @param string $selected if an option have been selected (edit form)
 * @param string $name     the name of the select boc
 * @uses selected() to activate an option if $selected is defined
 * @uses bp_is_active() to check for friends or groups component
 * @return the select box
 */
function buddybox_select_sharing_options( $id = 'buddybox-sharing-options', $selected = false, $name = false ) {
	?>
	<select id="<?php echo $id;?>" <?php if(!empty( $name ) ) echo 'name="'.$name.'"';?>>
		<option value="private" <?php selected( $selected, 'private' );?>><?php _e( 'Private', 'buddybox' );?></option>
		<option value="password" <?php selected( $selected, 'password' );?>><?php _e( 'Password protected', 'buddybox' );?></option>
		<option value="public" <?php selected( $selected, 'public' );?>><?php _e( 'Public', 'buddybox' );?></option>

		<?php if( bp_is_active( 'friends' ) ):?>
			<option value="friends" <?php selected( $selected, 'friends' );?>><?php _e( 'Friends only', 'buddybox' );?></option>
		<?php endif;?>
		<?php if( bp_is_active( 'groups' ) ):?>
			<option value="groups" <?php selected( $selected, 'groups' );?>><?php _e( 'One of my groups', 'buddybox' );?></option>
		<?php endif;?>
	</select>
	<?php
	
}

/**
 * Displays the select box to choose a folder to attach the BuddyFile to.
 * 
 * @param  int $user_id the user id
 * @param  int $selected the id of the folder in case of edit form
 * @param  string $name  the name of the select box
 * @uses buddybox_get_select_folder_options() to get the select box
 */
function buddybox_select_folder_options( $user_id = false, $selected = false, $name = false ) {
	echo buddybox_get_select_folder_options( $user_id, $selected, $name );
}

	/**
	 * Builds the folder select box to attach the BuddyFile to
	 * @param  int $user_id the user id
 	 * @param  int $selected the id of the folder in case of edit form
 	 * @param  string $name  the name of the select box
 	 * @uses bp_loggedin_user_id() to get current user id
 	 * @uses buddybox_get_folder_post_type() to get BuddyFolder post type
 	 * @uses The BuddyBox loop and some template tags
 	 * @uses selected() to activate a folder if $selected is defined
	 * @return string  the select box
	 */
	function buddybox_get_select_folder_options( $user_id = false, $selected = false, $name = false ) {
		if( empty( $user_id ) )
			$user_id = bp_loggedin_user_id();
			
		if(!empty( $name ) )
			$name = 'name="'.$name.'"';
		
		$output = __( 'No folder available', 'buddybox' );
		
		$buddybox_args = array(
				'user_id'	      => $user_id,
				'per_page'	      => false,
				'paged'		      => false,
				'type'            => buddybox_get_folder_post_type()
		);
			
		if ( buddybox_has_items( $buddybox_args ) ) {
			
			$output = '<select id="folders" '.$name.'>';
			
			$output .= '<option value="0" '.selected( $selected, 0, false ).'>'. __( 'Root folder', 'buddybox' ).'</option>';
			
			while ( buddybox_has_items() ) {
				buddybox_the_item();
				$output .= '<option value="'.buddybox_get_item_id().'" '. selected( $selected, buddybox_get_item_id(), false ) .'>'.buddybox_get_item_title().'</option>';
			}
			
			$output .= '</select>';
		}
			
		return apply_filters( 'buddybox_get_select_folder_options', $output, $buddybox_args );
		
	}


/**
 * Displays a select box to choose the group to attach the BuddyBox Item to
 * 
 * @param  int $user_id  the user id
 * @param  int $selected the group id in case of edit form
 * @param  string $name  the name of the select box
 * @uses buddybox_get_select_user_group() to get the select box
 */
function buddybox_select_user_group( $user_id = false, $selected = false, $name = false ) {
	echo buddybox_get_select_user_group( $user_id, $selected, $name );
}

	/**
	 * Builds the select box to choose the group to attach the BuddyBox Item to
	 * @param  int $user_id  the user id
 	 * @param  int $selected the group id in case of edit form
 	 * @param  string $name  the name of the select box
 	 * @uses bp_loggedin_user_id() to get current user id
 	 * @uses groups_get_groups() to list the groups of the user
 	 * @uses groups_get_groupmeta() to check group enabled BuddyBox
 	 * @uses selected() to eventually activate a group
	 * @return string the select box
	 */
	function buddybox_get_select_user_group( $user_id = false, $selected = false, $name = false ) {
		if( empty( $user_id ) )
			$user_id = bp_loggedin_user_id();

		$name = !empty( $name ) ? ' name="'.$name.'"' : false ;

		$output = __( 'No group available for BuddyBox', 'buddybox' );
		
		if( !bp_is_active( 'groups' ) )
			return $output;
		
		$user_groups = groups_get_groups( array( 'user_id' => $user_id ) );

		$buddybox_groups = false;

		// checking for available buffybox groups
		if( !empty( $user_groups['groups'] ) ) {
			foreach( $user_groups['groups'] as $group ) {
				if( 1 == groups_get_groupmeta( $group->id, '_buddybox_enabled' ) )
					$buddybox_groups[]= array( 'group_id' => $group->id, 'group_name' => $group->name );
			}
		}

		// building the select box
		if( !empty( $buddybox_groups ) && is_array( $buddybox_groups ) ) {
			$output = '<select id="buddygroup"'.$name.'>' ;
			foreach( $buddybox_groups as $buddybox_group ) {
				$output .= '<option value="'.$buddybox_group['group_id'].'" '. selected( $selected, $buddybox_group['group_id'], false ) .'>'.$buddybox_group['group_name'].'</option>';
			}
			$output .= '</select>';
		}

		return apply_filters( 'buddybox_get_select_user_group', $output );
	}


/**
 * Displays the form to create a new folder
 * 
 * @uses buddybox_select_sharing_options() to display the privacy select box
 */
function buddybox_folder_form() {
	?>
	<form class="standard-form" action="" method="post" id="buddybox-folder-editor-form">
		
		<div id="buddyfolder-first-step">
			<label for="buddyfolder-sharing-options"><?php _e( 'Define your sharing options', 'buddybox' );?></label>
			<?php buddybox_select_sharing_options( 'buddyfolder-sharing-options' );?>
			<div id="buddyfolder-sharing-details"></div>
			<input type="hidden" id="buddyfolder-sharing-settings" value="private">
			<p class="buddybox-action"><a href="#" class="next-step button"><?php _e( 'Next Step', 'buddybox' );?></a></p>
		</div>
		<div id="buddyfolder-second-step" class="hide">
			<label for="buddybox-folder-title"><?php _e( 'Create your folder', 'buddybox' );?></label>
			<input type="text" placeholder="<?php _e( 'Name of your folder', 'buddybox' );?>" id="buddybox-folder-title" name="buddybox_folder[title]">
			<p class="buddybox-action folder"><input type="submit" value="<?php _e( 'Add folder', 'buddybox' );?>" name="buddybox_folder[submit]">&nbsp;<a href="#" class="cancel-folder button"><?php _e( 'Cancel', 'buddybox' );?></a></p>
		</div>
	</form>
	<?php
}


/**
 * Displays the form to upload a new file
 * 
 * @uses BuddyBox_Uploader() class
 */
function buddybox_upload_form() {
	return new BuddyBox_Uploader();
}


/**
 * Displays the space a user is using with his files
 * 
 * @param  string $type    html or a diff
 * @param  int $user_id the user id
 */
function buddybox_user_used_quota( $type = false, $user_id = false ) {
	echo buddybox_get_user_space_left( $type, $user_id );
}

	/**
	 * Gets the space a user is using with his files
	 * @param  string $type    html or a diff
	 * @param  int $user_id the user id
	 * @uses get_option() to get admin choices about available quota for each user
	 * @uses get_user_meta() to get user's space used so far
	 * @return int|string   the space left or html to display it
	 */
	function buddybox_get_user_space_left( $type = false, $user_id = false ){
		if( empty( $user_id ) )
			$user_id = bp_loggedin_user_id();

		$max_space = get_option( '_buddybox_user_quota', 1000 );
		$max_space = intval( $max_space ) * 1024 * 1024 ;

		$used_space = get_user_meta( $user_id, '_buddybox_total_space', true );
		$used_space = intval( $used_space );
		$quota = number_format( ( $used_space / $max_space ) * 100, 2  );

		if( $type == 'diff' )
			return $max_space - $used_space;
		else
			return apply_filters( 'buddybox_get_user_space_left', sprintf( __( '<span id="buddy-quota">%s</span>&#37; used', 'buddybox' ), $quota ), $quota );

	}


/**
 * BuddyBox Loop : do we have items for the query asked
 * 
 * @param  array $args the arguments of the query
 * @global object $buddybox_template
 * @uses buddybox_get_folder_post_type() to get BuddyFolder post type
 * @uses buddybox_get_file_post_type() to get BuddyFile post type
 * @uses bp_displayed_user_id() to default to current displayed user
 * @uses bp_current_action() to get the current action ( files / friends / admin)
 * @uses bp_is_active() to check if groups component is active
 * @uses buddybox_is_group() are we on a group's BuddyBox ?
 * @uses wp_parse_args() to merge defaults and args
 * @uses BuddyBox_Item::get() to request the DB
 * @uses BuddyBox_Item::have_posts to know if BuddyItems matched the query
 * @return the result of the query
 */
function buddybox_has_items( $args = '' ) {
	global $buddybox_template;

	// This keeps us from firing the query more than once
	if ( empty( $buddybox_template ) ) {

		$defaulttype = array( buddybox_get_folder_post_type(), buddybox_get_file_post_type() );
		$user = $group_id = $buddyscope = false;
		
		if ( bp_displayed_user_id() )
			$user = bp_displayed_user_id();

		$buddyscope = bp_current_action();

		if( is_admin() )
			$buddyscope = 'admin';

		if( bp_is_active( 'groups' ) && buddybox_is_group() ) {
			$group = groups_get_current_group();
			
			$group_id = $group->id;
			$buddyscope = 'groups';
		}
		
		/***
		 * Set the defaults for the parameters you are accepting via the "buddybox_has_items()"
		 * function call
		 */
		$defaults = array(
				'id'              => false,
				'name'            => false,
				'group_id'	      => $group_id,
				'user_id'	      => $user,
				'per_page'	      => 10,
				'paged'		      => 1,
				'type'            => $defaulttype,
				'buddybox_scope'  => $buddyscope,
				'search'          => false,
				'buddybox_parent' => 0,
				'exclude'         => 0,
				'orderby' 		  => 'title', 
				'order'           => 'ASC'
			);
		
		$r = wp_parse_args( $args, $defaults );
		extract( $r, EXTR_SKIP );
			
		$buddybox_template = new BuddyBox_Item();


		if( !empty( $search ) )
			$buddybox_template->get( array( 'per_page' => $per_page, 'paged' => $paged, 'type' => $type, 'buddybox_scope' => $buddybox_scope, 'search' => $search, 'orderby' => $orderby, 'order' => $order ) );
		else
			$buddybox_template->get( array( 'id' => $id, 'name' => $name, 'group_id' => $group_id, 'user_id' => $user_id, 'per_page' => $per_page, 'paged' => $paged, 'type' => $type, 'buddybox_scope' => $buddybox_scope, 'buddybox_parent' => $buddybox_parent, 'exclude' => $exclude, 'orderby' => $orderby, 'order' => $order ) );
		
	}

	return $buddybox_template->have_posts();
}


/**
 * BuddyBox Loop : do we have more items
 *
 * @global object $buddybox_template
 * @return boolean true or false
 */
function buddybox_has_more_items() {
	global $buddybox_template;
	
	$total_items = intval( $buddybox_template->query->found_posts );
	$pag_num = intval( $buddybox_template->query->query_vars['posts_per_page'] );
	$pag_page = intval( $buddybox_template->query->query_vars['paged'] );
	
	$remaining_pages = floor( ( $total_items - 1 ) / ( $pag_num * $pag_page ) );
	$has_more_items  = (int)$remaining_pages ? true : false;

	return apply_filters( 'buddybox_has_more_items', $has_more_items );
}

/**
 * BuddyBox Loop : get the item's data
 *
 * @global object $buddybox_template
 * @return object the item's data
 */
function buddybox_the_item() {
	global $buddybox_template;
	return $buddybox_template->query->the_post();
}

/**
 * Displays the id of the BuddyBox item
 * 
 * @uses buddybox_get_item_id() to get the item id
 */
function buddybox_item_id() {
	echo buddybox_get_item_id();
}

	/**
	 * Gets the item id
	 *
	 * @global object $buddybox_template
	 * @return int the item id
	 */
	function buddybox_get_item_id() {
		global $buddybox_template;
		
		return $buddybox_template->query->post->ID;
	}

/**
 * Displays the parent id of the BuddyBox item
 * 
 * @uses buddybox_get_parent_item_id() to get the parent item id
 */
function buddybox_parent_item_id() {
	echo buddybox_get_parent_item_id();
}

	/**
	 * Gets the parent item id
	 *
	 * @global object $buddybox_template
	 * @return int the parent item id
	 */
	function buddybox_get_parent_item_id() {
		global $buddybox_template;
		
		return $buddybox_template->query->post->post_parent;
	}

/**
 * Displays the title of the BuddyBox item
 * 
 * @uses buddybox_get_item_title() to get the title of the item
 */
function buddybox_item_title() {
	echo buddybox_get_item_title();
}

	/**
	 * Gets the title of the BuddyBox item
	 *
	 * @global object $buddybox_template
	 * @return string the title of the item
	 */
	function buddybox_get_item_title() {
		global $buddybox_template;
		
		return apply_filters('buddybox_get_item_title', $buddybox_template->query->post->post_title );
	}

/**
 * Displays the description of the BuddyBox item
 * 
 * @uses buddybox_get_item_description() to get the description of the item
 */
function buddybox_item_description() {
	echo buddybox_get_item_description();
}

	/**
	 * Gets the description of the BuddyBox item
	 *
	 * @global object $buddybox_template
	 * @return string the description of the item
	 */
	function buddybox_get_item_description() {
		global $buddybox_template;
		
		return apply_filters( 'buddybox_get_item_description', $buddybox_template->query->post->post_content );
	}

/**
 * Do we have a file ?
 *
 * @global object $buddybox_template
 * @uses buddybox_get_file_post_type() to get the BuddyFile post type
 * @return boolean true or false
 */
function buddybox_is_buddyfile() {
	global $buddybox_template;
	
	$is_buddyfile = false;
	
	if( $buddybox_template->query->post->post_type == buddybox_get_file_post_type() )
		$is_buddyfile = true;
		
	return $is_buddyfile;
}

/**
 * Displays the action link (download or open folder) of the BuddyBox item
 * 
 * @uses buddybox_get_action_link() to get the action link of the item
 */
function buddybox_action_link() {
	echo buddybox_get_action_link();
}

	/**
	 * Gets the action link of the BuddyBox item
	 *
	 * @global object $buddybox_template
	 * @uses buddybox_is_buddyfile() to check for a file
	 * @return string the action link of the item
	 */
	function buddybox_get_action_link() {
		global $buddybox_template;
		
		$buddyslug = 'folder';
		
		if( buddybox_is_buddyfile() )
			$buddyslug = 'file';

		$slug = trailingslashit( $buddyslug.'/' . $buddybox_template->query->post->post_name );
			
		$link = buddybox_get_root_url() .'/'. $slug;
		
		return apply_filters( 'buddybox_get_action_link', $link );
	}

/**
 * Displays an action link class for the BuddyBox item
 * 
 * @uses buddybox_get_action_link_class() to get the action link class of the item
 */	
function buddybox_action_link_class() {
	echo buddybox_get_action_link_class();
}

	/**
	 * Gets the action link class for the BuddyBox item
	 *
	 * @global object $buddybox_template
	 * @uses buddybox_is_buddyfile() to check for a file
	 * @return string the action link class for the item
	 */
	function buddybox_get_action_link_class() {
		$class = array();
		
		$class[] =  buddybox_is_buddyfile() ? 'buddyfile' : 'buddyfolder';
		
		$class = apply_filters( 'buddybox_get_action_link_class', $class );
		
		return implode( ' ', $class );
	}

/**
 * Displays an attribute to identify a folder or a file
 * 
 * @uses buddybox_get_item_attribute() to get the attribute of the item
 */	
function buddybox_item_attribute() {
	echo buddybox_get_item_attribute();
}

	/**
	 * Gets the attribute to identify a folder or a file
	 *
	 * @global object $buddybox_template
	 * @uses buddybox_is_buddyfile() to check for a file
	 * @return string the attribute for the item
	 */
	function buddybox_get_item_attribute() {
		
		$data_attr = false;
		
		if( !buddybox_is_buddyfile() )
			$data_attr = ' data-folder="'.buddybox_get_item_id().'"';
		else
			$data_attr = ' data-file="'.buddybox_get_item_id().'"';
			
		return apply_filters( 'buddybox_get_item_attribute', $data_attr );
	}

/**
 * Displays the user id of the owner of a BuddyBox item
 * 
 * @uses buddybox_get_owner_id() to get owner's id
 */	
function buddybox_owner_id() {
	echo buddybox_get_owner_id();
}

	/**
	 * Gets the user id of the owner of a BuddyBox item
	 *
	 * @global object $buddybox_template
	 * @return int the owner's id
	 */
	function buddybox_get_owner_id() {
		global $buddybox_template;

		return apply_filters( 'buddybox_get_owner_id', $buddybox_template->query->post->post_author );
	}

/**
 * Displays the avatar of the owner of a BuddyBox item
 * 
 * @uses buddybox_get_show_owner_avatar() to get avatar of the owner
 */	
function buddybox_owner_avatar() {
	echo buddybox_get_show_owner_avatar();
}

	/**
	 * Gets the avatar of the owner
	 *
	 * @param int $user_id the user id
	 * @param string $width the width of the avatar
	 * @param string $height the height of the avatar
	 * @uses buddybox_get_owner_id() to get the user id
	 * @uses bp_core_get_username() to get the username of the owner
	 * @uses bp_core_fetch_avatar() to get the avatar of the owner 
	 * @return string avatar of the owner
	 */
	function buddybox_get_show_owner_avatar( $user_id = false, $width = '32', $height = '32' ) {

		if( empty( $user_id ) )
			$user_id = buddybox_get_owner_id();

		$username = bp_core_get_username( $user_id );

		$avatar  = bp_core_fetch_avatar( array(
			'item_id'    => $user_id,
			'object'     => 'user',
			'type'       => 'thumb',
			'avatar_dir' => 'avatars',
			'alt'        => sprintf( __( 'User Avatar of %s', 'buddybox' ), $username ),
			'width'      => $width,
			'height'     => $height,
			'title'      => $username
		) );

		return apply_filters( 'buddybox_get_show_owner_avatar', $avatar, $user_id, $username );
	}

/**
 * Displays the link to the owner's home page
 * 
 * @uses buddybox_get_owner_link() to get the link to the owner's home page
 */	
function buddybox_owner_link() {
	echo buddybox_get_owner_link();
}

	/**
	 * Gets the link to the owner's home page
	 *
	 * @uses buddybox_get_owner_id() to get the owner id
	 * @uses bp_core_get_userlink() to get the link to owner's home page
	 * @return the link
	 */
	function buddybox_get_owner_link() {
		$user_id = buddybox_get_owner_id();

		$userlink = bp_core_get_userlink( $user_id, false, true );

		return apply_filters( 'buddybox_get_owner_link', $userlink );
	}

/**
 * Displays the avatar of the group the item is attached to
 * 
 * @uses buddybox_get_group_avatar() to get the group avatar
 */	
function buddybox_group_avatar() {
	echo buddybox_get_group_avatar();
}

	/**
	 * Gets the group avatar the item is attached to
	 * 
	 * @param  int $item_id the item id
	 * @param  boolean $nolink  should we wrap a link to group's page
	 * @param  string  $width   the width of the avatar
	 * @param  string  $height  the height of the avatar
	 * @uses buddybox_get_parent_item_id() to get parent id
	 * @uses buddybox_get_item_id() to default to item id
	 * @uses get_post_meta() to get the group id attached to the item
	 * @uses groups_get_group() to get the group object for the group_id
	 * @uses bp_get_group_permalink() to get the group link
	 * @uses bp_core_fetch_avatar() to get the group avatar
	 * @return string the group avatar
	 */
	function buddybox_get_group_avatar( $item_id = false, $nolink = false, $width ='32', $height = '32' ) {

		$buddybox_item_group_meta = false;

		if( empty( $item_id ) ) {
			$parent_id = buddybox_get_parent_item_id();
			$item_id = ( !empty( $parent_id ) ) ? $parent_id : buddybox_get_item_id();
		}

		$buddybox_item_group_meta = get_post_meta( $item_id, '_buddybox_sharing_groups', true );

		if( empty( $buddybox_item_group_meta ) )
			return false;
			
		if( !bp_is_active( 'groups' ) )
			return false;

		$group = groups_get_group( array( 'group_id' => $buddybox_item_group_meta ) );
		
		if( empty( $group) )
			return false;

		$group_link = bp_get_group_permalink( $group );
		$group_name = $group->name;

		$group_avatar  = bp_core_fetch_avatar( array(
										'item_id'    => $buddybox_item_group_meta,
										'object'     => 'group',
										'type'       => 'thumb',
										'avatar_dir' => 'group-avatars',
										'alt'        => sprintf( __( 'Group logo of %d', 'buddypress' ), $group_name ),
										'width'      => $width,
										'height'     => $height,
										'title'      => $group_name
									) );

		if( !empty( $nolink ) )
			return $group_avatar;
		else
			return apply_filters( 'buddybox_get_group_avatar', '<a href="'.$group_link.'" title="'.$group_name.'">' . $group_avatar .'</a>');


	}

/**
 * Displays the avatar of the owner or a checkbox
 * 
 * @uses buddybox_get_owner_or_cb()
 */	
function buddybox_owner_or_cb() {
	echo buddybox_get_owner_or_cb();
}

	/**
	 * Choose between the owner's avatar or a checkbox if on loggedin user's BuddyBox
	 *
	 * @uses bp_is_my_profile() to check we're on a user's profile
	 * @uses bp_current_action() to check for BuddyBox scope
	 * @uses buddybox_get_item_id() to get the item id
	 * @uses buddybox_get_owner_link() to get the link to owner's profile
	 * @uses buddybox_get_show_owner_avatar() to get owner's avatar.
	 * @return string the right html
	 */
	function buddybox_get_owner_or_cb() {
		$output = '';
		
		if( bp_is_my_profile() && bp_current_action() == 'files' )
			$output = '<input type="checkbox" name="buddybox-item[]" class="buddybox-item-cb" value="'.buddybox_get_item_id().'">';
		else
			$output = '<a href="'.buddybox_get_owner_link().'" title="'.__('Owner', 'buddybox').'">'.buddybox_get_show_owner_avatar().'</a>';
			
		return apply_filters( 'buddybox_get_owner_or_cb', $output );
	}

/**
 * Displays a checkbox or a table header
 * 
 * @uses buddybox_get_th_owner_or_cb()
 */	
function buddybox_th_owner_or_cb() {
	echo buddybox_get_th_owner_or_cb();
}

	/**
	 * Gets a checkbox or a table header
	 *
	 * @uses bp_is_my_profile() to check we're on a user's profile
	 * @uses bp_current_action() to check for BuddyBox scope
	 * @return string the right html
	 */
	function buddybox_get_th_owner_or_cb() {
		$output = '';
		
		if( bp_is_my_profile() && bp_current_action() == 'files')
			$output = '<input type="checkbox" id="buddybox-sel-all">';
		else
			$output = __('Owner', 'buddybox');
			
		return apply_filters( 'buddybox_get_th_owner_or_cb', $output );
	}


/**
 * Displays the privacy of an item
 * 
 * @uses buddybox_get_item_privacy() to get the privacy option
 * @uses buddybox_get_group_avatar() to get the group avatar of the group the item is attached to
 * @uses buddybox_get_item_id() to get the id of the item
 */
function buddybox_item_privacy() {
	$status = buddybox_get_item_privacy();
	
	switch( $status['privacy'] ) {
		case 'private' :
			echo '<a title="'.__( 'Private', 'buddybox' ).'"><i class="icon-lock"></i></a>';
			break;
		case 'public' :
			echo '<a title="'.__( 'Public', 'buddybox' ).'"><i class="icon-unlocked"></i></a>';
			break;
		case 'friends' :
			echo '<a title="'.__( 'Friends', 'buddybox' ).'"><i class="icon-users"></i></a>';
			break;
		case 'password' :
			echo '<a title="'.__( 'Password protected', 'buddybox' ).'"><i class="icon-key"></i></a>';
			break;
		case 'groups' :
			if( !empty( $status['group'] ) )
				echo buddybox_get_group_avatar( buddybox_get_item_id() );
			else
				_e( 'Group', 'buddybox' );
			break;		
	}
}

	/**
	 * Gets the item's privacy
	 *
	 * @global object $buddybox_template
	 * @uses buddybox_get_item_id() to get the item id
	 * @uses get_post_meta() to get item's privacy option
	 * @return array the item's privacy
	 */
	function buddybox_get_item_privacy() {
		global $buddybox_template;
		
		$status = array();
		$buddyfile_id = buddybox_get_item_id();
		$item_privacy_id = !( empty( $buddybox_template->query->post->post_parent ) ) ? $buddybox_template->query->post->post_parent : $buddyfile_id ;
		
		$status['privacy'] = get_post_meta( $item_privacy_id, '_buddybox_sharing_option', true );
		
		if( $status['privacy'] == 'groups' )
			$status['group'] = get_post_meta( $item_privacy_id, '_buddybox_sharing_groups', true );
			
		return apply_filters( 'buddybox_get_item_privacy', $status );
	}

/**
 * Displays the mime type of an item
 *
 * @uses buddybox_get_item_mime_type() to get it !
 */
function buddybox_item_mime_type() {
	echo buddybox_get_item_mime_type();
}

	/**
	 * Gets the mime type of an item
	 *
	 * @global object $buddybox_template
	 * @uses buddybox_is_buddyfile() to check for a BuddyFile
	 * @return string the mime type
	 */
	function buddybox_get_item_mime_type() {
		global $buddybox_template;
		
		$mime_type = __( 'folder', 'buddybox' );
		
		if( buddybox_is_buddyfile() ) {
			$doc = $buddybox_template->query->post->guid;

			$mime_type = __( 'file', 'buddybox' );
			
			if ( preg_match( '/^.*?\.(\w+)$/', $doc, $matches ) )
				$mime_type = esc_html( $matches[1] ) .' '. $mime_type;
		}
			
		
		return apply_filters('buddybox_get_item_mime_type', $mime_type );
	}

/**
 * Displays an icon before the item's title
 * 
 * @uses buddybox_get_item_icon() to get the icon
 */
function buddybox_item_icon() {
	echo buddybox_get_item_icon();
}
	
	/**
	 * Gets the item's icon
	 * 
	 * @uses buddybox_is_buddyfile() to check for a BuddyFile
	 * @return string html of the icon
	 */
	function buddybox_get_item_icon() {
		
		$icon = '<span class="icon-folder"></span>';
		
		if( buddybox_is_buddyfile() )
			$icon = '<span class="icon-file"></span>';
		
		return apply_filters( 'buddybox_get_item_icon', $icon );
		
		
	}

/**
 * Displays the file name of the uploaded file
 * 
 * @uses buddybox_get_uploaded_file_name() to get it
 */
function buddybox_uploaded_file_name() {
	echo buddybox_get_uploaded_file_name();
}

	/**
	 * Gets the mime type of an item
	 *
	 * @global object $buddybox_template
	 * @return string the uploaded file name
	 */
	function buddybox_get_uploaded_file_name() {
		global $buddybox_template;
		
		return basename( $buddybox_template->query->post->guid );
	}

/**
 * Displays the last modified date of an item
 * 
 * @uses buddybox_get_item_date() to get it!
 */
function buddybox_item_date() {
	echo buddybox_get_item_date();
}

	/**
	 * Gets the item date
	 *
	 * @global object $buddybox_template
	 * @uses  bp_format_time() to format the date
	 * @return string the formatted date
	 */
	function buddybox_get_item_date() {
		global $buddybox_template;
		
		$date = $buddybox_template->query->post->post_modified_gmt;
		
		$date = bp_format_time( strtotime( $date ), true, false );
		
		return apply_filters( 'buddybox_get_item_date', $date );
	}

/**
 * Various checks to see if a user can remove an item from a group
 * 
 * @param  int $group_id the group id
 * @uses bp_get_current_group_id() to get current group id
 * @uses buddybox_is_group() to check we're on a group's BuddyBox
 * @uses buddybox_get_parent_item_id() to get parent item
 * @uses groups_is_user_admin() to check if the current user is admin of the group
 * @uses bp_loggedin_user_id() to get current user id
 * @uses is_super_admin() to give power to admin !
 * @return boolean $can_remove
 */
function buddybox_current_user_can_remove( $group_id = false ) {
	$can_remove = false;

	if( empty( $group_id ) )
		$group_id = bp_get_current_group_id();

	if( !buddybox_is_group() || buddybox_get_parent_item_id() )
		$can_remove = false;

	elseif( groups_is_user_admin( bp_loggedin_user_id(), $group_id ) )
		$can_remove = true;
	else if( is_super_admin() )
		$can_remove = true;

	return apply_filters( 'buddybox_current_user_can_remove', $can_remove );
}


/**
 * Checks if a user can share an item
 *
 * @uses buddybox_get_owner_id() to get owner's id
 * @uses bp_loggedin_user_id() to get current user id
 * @return boolean true or false
 */
function buddybox_current_user_can_share() {
	$can_share = false;

	if( buddybox_get_owner_id() == bp_loggedin_user_id() && !buddybox_is_group() )
		$can_share = true;

	return apply_filters( 'buddybox_current_user_can_share', $can_share );
}

/**
 * Checks if the user can get the link of an item
 * 
 * @param  array $privacy the sharing options
 * @uses buddybox_get_owner_id() to get owner's id
 * @uses bp_loggedin_user_id() to get current user id
 * @uses is_user_logged_in() to check if the visitor is not logged in
 * @uses bp_is_active() to check for friends and groups component
 * @uses friends_check_friendship() to check the friendship between owner and current user
 * @uses groups_is_user_member() to check if the current user is member of the group the BuddyBox item is attached to
 * @return boolean true or false
 */
function buddybox_current_user_can_link( $privacy = false ) {
	$can_link = false;

	if( buddybox_get_owner_id() == bp_loggedin_user_id() )
		$can_link = true;

	elseif( empty( $privacy ) )
		$can_link = false;

	elseif( !is_user_logged_in() )
		$can_link = false;

	elseif( $privacy['privacy'] == 'public' )
		$can_link = true;

	else if( $privacy['privacy'] == 'friends' && bp_is_active('friends') && friends_check_friendship( buddybox_get_owner_id(), bp_loggedin_user_id() ) )
		$can_link = true;

	else if( $privacy['privacy'] == 'groups' && bp_is_active('groups') && !empty( $privacy['group'] ) && groups_is_user_member( bp_loggedin_user_id(), intval( $privacy['group'] ) ) )
		$can_link = true;

	elseif( is_super_admin() )
		$can_link = true;

	return apply_filters( 'buddybox_current_user_can_link', $can_link );
}

/**
 * Displays the link to row actions
 * 
 * @uses buddybox_get_row_actions()
 */
function buddybox_row_actions() {
	echo buddybox_get_row_actions();
}

	/**
	 * Builds the row actions
	 *
	 * @global object $buddybox_template
	 * @uses buddybox_get_item_id() to get item id
	 * @uses buddybox_is_buddyfile() to check for a file
	 * @uses buddybox_get_item_description() to get item's description
	 * @uses buddybox_get_item_privacy() to get item's privacy options
	 * @uses buddybox_current_user_can_link()
	 * @uses buddybox_get_action_link()
	 * @uses buddybox_current_user_can_share()
	 * @uses bp_is_active() to check for the messages, activity and group components.
	 * @uses bp_loggedin_user_domain() to get user's home url
	 * @uses bp_get_messages_slug() to get the messages component slug
	 * @uses buddybox_current_user_can_share()
	 * @return [type] [description]
	 */
	function buddybox_get_row_actions() {
		global $buddybox_template;

		$row_actions = $inside_top = $inside_bottom = false;

		$buddyfile_id = buddybox_get_item_id();

		if( buddybox_is_buddyfile() ) {
			$description = buddybox_get_item_description();

			if( !empty( $description ) ) {
				$inside_top[]= '<a class="buddybox-show-desc" href="#">' . __('Description', 'buddybox'). '</a>';
				$inside_bottom .= '<div class="buddybox-ra-desc hide ba">'.$description.'</div>';
			}
		}

		$privacy = buddybox_get_item_privacy();

		switch( $privacy['privacy'] ) {
			case 'public':
				if( buddybox_current_user_can_link( $privacy ) ){
					$inside_top[]= '<a class="buddybox-show-link" href="#">' . __( 'Link', 'buddybox' ). '</a>';
					$inside_bottom .= '<div class="buddybox-ra-link hide ba"><input type="text" class="buddybox-file-input" id="buddybox-link-' .$buddyfile_id. '" value="' .buddybox_get_action_link(). '"></div>';
				}
				if( buddybox_current_user_can_share() && bp_is_active( 'activity' ) )
					$inside_top[]= '<a class="buddybox-profile-activity" href="#">' . __( 'Share', 'buddybox' ). '</a>';
				break;
			case 'password':
				if( buddybox_current_user_can_link( $privacy ) ){
					$inside_top[]= '<a class="buddybox-show-link" href="#">' . __( 'Link', 'buddybox' ). '</a>';
					$inside_bottom .= '<div class="buddybox-ra-link hide ba"><input type="text" class="buddybox-file-input" id="buddybox-link-' .$buddyfile_id. '" value="' .buddybox_get_action_link(). '"></div>';
				}
				if( buddybox_current_user_can_share() && bp_is_active( 'messages' ) )
					$inside_top[]= '<a class="buddybox-private-message" href="'.bp_loggedin_user_domain() . bp_get_messages_slug() . '/compose/?buddyitem='.$buddyfile_id.'">' . __('Share', 'buddybox'). '</a>';
				break;
			case 'friends':
				if( buddybox_current_user_can_link( $privacy ) ){
					$inside_top[]= '<a class="buddybox-show-link" href="#">' . __( 'Link', 'buddybox' ). '</a>';
					$inside_bottom .= '<div class="buddybox-ra-link hide ba"><input type="text" class="buddybox-file-input" id="buddybox-link-' .$buddyfile_id. '" value="' .buddybox_get_action_link(). '"></div>';
				}
				if( buddybox_current_user_can_share() && bp_is_active( 'messages' ) )
					$inside_top[]= '<a class="buddybox-private-message" href="'.bp_loggedin_user_domain() . bp_get_messages_slug() . '/compose/?buddyitem='.$buddyfile_id.'&friends=1">' . __( 'Share', 'buddybox' ). '</a>';
				break;
			case 'groups':
				if( buddybox_current_user_can_link( $privacy ) ){
					$inside_top[]= '<a class="buddybox-show-link" href="#">' . __( 'Link', 'buddybox' ). '</a>';
					$inside_bottom .= '<div class="buddybox-ra-link hide ba"><input type="text" class="buddybox-file-input" id="buddybox-link-' .$buddyfile_id. '" value="' .buddybox_get_action_link(). '"></div>';
				}
				if( buddybox_current_user_can_share() && bp_is_active( 'activity' ) && bp_is_active( 'groups' ) )
					$inside_top[]= '<a class="buddybox-group-activity" href="#">' . __( 'Share', 'buddybox' ). '</a>';
				if( buddybox_current_user_can_remove( $privacy['group'] ) && bp_is_active( 'groups') )
					$inside_top[]= '<a class="buddybox-remove-group" href="#" data-group="'.$privacy['group'].'">' . __( 'Remove', 'buddybox' ). '</a>';
				break;
		}

		if( !empty( $inside_top ) )
			$inside_top = '<div class="buddybox-action-btn">'. implode( ' | ', $inside_top ).'</div>';

		if( !empty( $inside_top ) )
			$row_actions .= '<div class="buddybox-row-actions">' . $inside_top . $inside_bottom .'</div>';

		return apply_filters( 'buddybox_get_row_actions', $row_actions );
	}


/**
 * Displays a form if the file needs a password to be downloaded.
 */
function buddybox_file_password_form() {
	?>
	<form action="" method="post" class="standard-form">
		<p><label for="buddypass"><?php _e( 'Password required', 'buddybox' );?></label>
		<input type="password" id="buddypass" name="buddyfile-form[password]"></p>
		<p><input type="submit" value="Send" name="buddyfile-form[submit]"></p>
	</form>
	<?php
}