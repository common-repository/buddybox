<?php

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;


/**
 * The main settings arguments
 * 
 * @return array
 */
function buddybox_admin_get_settings_sections() {
	return (array) apply_filters( 'buddybox_admin_get_settings_sections', array(
		'buddybox_settings_main' => array(
			'title'    => __( 'Main Settings', 'buddybox' ),
			'callback' => 'buddybox_admin_setting_callback_main_section',
			'page'     => 'buddybox',
		)
	) );
}

/**
 * The different fields for the main settings
 * 
 * @return array
 */
function buddybox_admin_get_settings_fields() {
	return (array) apply_filters( 'buddybox_admin_get_settings_fields', array(

		/** Main Section ******************************************************/

		'buddybox_settings_main' => array(

			// User's quota
			'_buddybox_user_quota' => array(
				'title'             => __( 'Space available for each user', 'buddybox' ),
				'callback'          => 'buddybox_admin_setting_callback_user_quota',
				'sanitize_callback' => 'buddybox_sanitize_user_quota',
				'args'              => array()
			),

			// Max upload size
			'_buddybox_max_upload' => array(
				'title'             => __( 'Max upload size', 'buddybox' ),
				'callback'          => 'buddybox_admin_setting_callback_max_upload',
				'sanitize_callback' => 'buddybox_sanitize_max_upload',
				'args'              => array()
			),

			// Allowed extensions
			'_buddybox_allowed_extensions' => array(
				'title'             => __( 'Mime types allowed', 'buddybox' ),
				'callback'          => 'buddybox_admin_setting_callback_allowed_extensions',
				'sanitize_callback' => 'buddybox_sanitize_allowed_extension',
				'args'              => array()
			)
		)
	) );
}


/**
 * Gives the setting fields for section (anticipating next versions..??)
 * 
 * @param  string $section_id 
 * @return array  the fields
 */
function buddybox_admin_get_settings_fields_for_section( $section_id = '' ) {

	// Bail if section is empty
	if ( empty( $section_id ) )
		return false;

	$fields = buddybox_admin_get_settings_fields();
	$retval = isset( $fields[$section_id] ) ? $fields[$section_id] : false;

	return (array) apply_filters( 'buddybox_admin_get_settings_fields_for_section', $retval, $section_id );
}

/**
 * Some text to introduce the settings section
 * 
 * @return string html
 */
function buddybox_admin_setting_callback_main_section() {
?>

	<p><?php _e( 'Customize your Buddybox!', 'buddybox' ); ?></p>

<?php
}

/**
 * Let the admin customize users quota
 *
 * @uses get_option() to get the user's quota
 * @return string html
 */
function buddybox_admin_setting_callback_user_quota() {
	$user_quota = get_option( '_buddybox_user_quota', 1000 );
	$user_quota = intval( $user_quota );
	?>

	<input name="_buddybox_user_quota" type="number" min="1" step="1" id="_buddybox_user_quota" value="<?php echo $user_quota;?>" class="small-text" />
	<label for="_buddybox_user_quota"><?php _e( 'MO', 'buddybox' ); ?></label>

	<?php
}

/**
 * Let the admin customize the max upload size as long as it's less than its config can !
 *
 * @uses buddybox_max_upload_size() to get the max upload size choosed
 * @return string html
 */
function buddybox_admin_setting_callback_max_upload() {
	$buddybox_upload = buddybox_max_upload_size();
	?>
	<input name="_buddybox_max_upload" type="number" min="1" step="1" id="_buddybox_max_upload" value="<?php echo $buddybox_upload;?>" class="small-text" />
	<label for="_buddybox_max_upload"><?php _e( 'MO', 'buddybox' ); ?></label>
	<?php
}

/**
 * Let the admin selects the different mime types he wants
 *
 * @uses get_allowed_mime_types() to get all the WordPress mime types
 * @uses buddybox_allowed_file_types() to get the one activated for BuddyBox
 * @uses buddybox_array_checked() to activate the checkboxes if needed
 * @return string html
 */
function buddybox_admin_setting_callback_allowed_extensions() {
	$ext = get_allowed_mime_types();
	$buddybox_ext = buddybox_allowed_file_types( $ext );
	?>
	<ul>
		<li><input type="checkbox" id="buddybox-toggle-all" checked /> <?php _e( 'Select / Unselect all', 'buddybox' );?></li>
		<?php foreach( $ext as $motif => $mime ):?>

			<li style="display:inline-block;width:45%;margin-right:1em"><input type="checkbox" class="buddybox-admin-cb" value="<?php echo $motif;?>" name="_buddybox_allowed_extensions[]" <?php buddybox_array_checked( $motif, $buddybox_ext );?>> <?php echo $mime;?></li>

		<?php endforeach;?>
	</ul>
	<script type="text/javascript">
		jQuery('#buddybox-toggle-all').on('change', function(){
			var status = jQuery(this).attr('checked');
			
			if( !status )
				status = false;
			
			jQuery('.buddybox-admin-cb').each( function() {
				jQuery(this).attr('checked', status );
			});
			
			return false;
		})
	</script>
	<?php
}

/**
 * Sanitize the user's quota
 *
 * @param int $option 
 * @return int the user's quota
 */
function buddybox_sanitize_user_quota( $option ) {
	$input = intval( $_POST['_buddybox_user_quota'] );
	
	return $input;
}

/**
 * Make sure the max upload remains under the config limit
 * 
 * @param  int $option
 * @uses wp_max_upload_size() to get the max value of the config
 * @return int the max upload sanitized
 */
function buddybox_sanitize_max_upload( $option ) {
	$input = intval( $_POST['_buddybox_max_upload'] );

	if( !empty( $input ) ) {
		$max = wp_max_upload_size();
		$check = $input * 1024 * 1024;

		if( $max < $input )
			$input = $max / 1024 / 1024;
	}
		
	return $input;
}

/**
 * Sanitize the extensions choosed
 * 
 * @param  array $option
 * @return array the sanitized allowed mime types
 */
function buddybox_sanitize_allowed_extension( $option ) {
	$input = $_POST['_buddybox_allowed_extensions'];

	if( is_array( $input ) )
		$input = array_map( 'trim', $input );

	return $input;
}

/**
 * Displays the settings page
 * 
 * @uses is_multisite() to check for multisite
 * @uses add_query_arg() to add arguments to query in case of multisite
 * @uses bp_get_admin_url to build the settings url in case of multisite
 * @uses screen_icon() to show BuddyBox icon
 * @uses settings_fields()
 * @uses do_settings_sections()
 * @uses wp_nonce_field() for security reason in case of multisite
 */
function buddybox_admin_settings() {
	$form_action = 'options.php';
	
	if( is_multisite() ) {
		do_action( 'buddybox_multisite_options' );
		
		$form_action = add_query_arg( 'page', 'buddybox', bp_get_admin_url( 'settings.php' ) );
	}
?>

	<div class="wrap">

		<?php screen_icon('buddybox'); ?>

		<h2><?php _e( 'BuddyBox Settings', 'buddybox' ) ?></h2>

		<form action="<?php echo $form_action;?>" method="post">

			<?php settings_fields( 'buddybox' ); ?>

			<?php do_settings_sections( 'buddybox' ); ?>

			<p class="submit">
				<?php if( is_multisite() ) :?>
					<?php wp_nonce_field( 'buddybox_settings', '_wpnonce_buddybox_setting' ); ?>
				<?php endif;?>
				<input type="submit" name="submit" class="button-primary" value="<?php esc_attr_e( 'Save Changes', 'buddybox' ); ?>" />
			</p>
		</form>
	</div>

<?php
}


/**
 * Save settings in case of a multisite config
 *
 * @uses check_admin_referer() to check the nonce
 * @uses buddybox_sanitize_user_quota() to sanitize user's quota
 * @uses bp_update_option() to save the options in root blog
 * @uses buddybox_sanitize_max_upload() to sanitize max upload
 * @uses buddybox_sanitize_allowed_extension() to sanitize the mime types
 */
function buddybox_handle_settings_in_multisite() {
	
	if ( 'POST' !== strtoupper( $_SERVER['REQUEST_METHOD'] ) )
		return;
	
	check_admin_referer( 'buddybox_settings', '_wpnonce_buddybox_setting' );
	
	$user_quota  = buddybox_sanitize_user_quota( $_POST['_buddybox_user_quota'] );
	
	if( ! empty( $user_quota ) )
		bp_update_option( '_buddybox_user_quota', $user_quota );
		
	$max_upload  = buddybox_sanitize_max_upload( $_POST['_buddybox_max_upload'] );
	
	if( ! empty( $max_upload ) )
		bp_update_option( '_buddybox_max_upload', $max_upload );
	
	$allowed_ext = buddybox_sanitize_allowed_extension( $_POST['_buddybox_allowed_extensions'] );
	
	if( ! empty( $allowed_ext ) && is_array( $allowed_ext ) )
		bp_update_option( '_buddybox_allowed_extensions', $allowed_ext );
	
	?>
	<div id="message" class="updated"><p><?php _e( 'Settings saved', 'buddybox' );?></p></div>
	<?php
	
}

add_action( 'buddybox_multisite_options', 'buddybox_handle_settings_in_multisite', 0 );
