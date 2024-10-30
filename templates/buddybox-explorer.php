<?php

/**
 * BuddyBox - ExplorerTemplate
 *
 * @package BuddyBox
 */
?>
<?php if( buddybox_is_bp_default() ): ?>

	<?php get_header( 'buddypress' ); ?>

		<div id="content">
			<div class="padder">

				<?php do_action( 'buddybox_before_member_content' ); ?>

				<div id="item-header" role="complementary">

					<?php locate_template( array( 'members/single/member-header.php' ), true ); ?>

				</div><!-- #item-header -->

				<div id="item-nav">
					<div class="item-list-tabs no-ajax" id="object-nav" role="navigation">
						<ul>

							<?php bp_get_displayed_user_nav(); ?>

							<?php do_action( 'bp_member_options_nav' ); ?>

						</ul>
					</div>
				</div><!-- #item-nav -->

<?php endif;?>

			<div id="item-body">
				
				<div class="item-list-tabs buddybox-type-tabs no-ajax" id="subnav">
					<form action="" method="get" id="buddybox-form-filter">
					<ul>
						
						<?php do_action( 'buddybox_member_before_nav' ); ?>
						
						<?php bp_get_options_nav() ?>
						
						<?php do_action( 'buddybox_member_before_toolbar' ); ?>

						<?php if ( buddybox_is_user_buddybox() ):?>

							<li id="buddybox-action-nav" class="last">

								<a href="#" id="buddy-new-file" title="<?php _e('New File', 'buddybox');?>"><i class="icon-createfile"></i></a>
								<a href="#" id="buddy-new-folder" title="<?php _e('New Folder', 'buddybox');?>"><i class="icon-addfolder"></i></a>
								<a href="#" id="buddy-edit-item" title="<?php _e('Edit Item', 'buddybox');?>"><i class="icon-uniF47C"></i></a>
								<a href="#" id="buddy-delete-item" title="<?php _e('Delete Item(s)', 'buddybox');?>"><i class="icon-remove"></i></a>
								<a><i class="icon-analytics3"></i> <?php buddybox_user_used_quota();?></a>
								
							</li>

						<?php endif;?>
					</ul>
					</form>
				</div>
				
				<div id="buddybox-forms">
						<div class="buddybox-crumbs"><a href="<?php buddybox_component_home_url();?>" name="home" id="buddybox-home"><span class="icon-home"></span> <span id="folder-0" class="buddytree current"><?php _e( 'Root folder', 'buddybox' );?></span></a></div>
				
					<?php if ( buddybox_is_user_buddybox() ):?>
					
						<div id="buddybox-file-uploader" class="hide">
							<?php buddybox_upload_form();?>
						</div>
						<div id="buddybox-folder-editor" class="hide">
							<?php buddybox_folder_form()?>
						</div>
						<div id="buddybox-edit-item" class="hide"></div>
					
					<?php endif;?>
					
				</div>
				
				<?php do_action( 'buddybox_after_member_upload_form' ); ?>
				<?php do_action( 'buddybox_before_member_body' );?>
				
				<div class="buddybox single-member" role="main">
					<?php buddybox_get_template('buddybox-loop');?>
				</div><!-- .buddybox.single-member -->

				<?php do_action( 'buddybox_after_member_body' ); ?>

			</div><!-- #item-body -->

			<?php do_action( 'buddybox_after_member_content' ); ?>

<?php if( buddybox_is_bp_default() ):?>
			</div><!-- .padder -->
		</div><!-- #content -->

	<?php get_sidebar( 'buddypress' ); ?>
	<?php get_footer( 'buddypress' ); ?>

<?php endif;?>