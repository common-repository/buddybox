<?php

/**
 * BuddyBox Loop
 *
 * Inspired by BuddyPress Activity Loop
 *
 * @package BuddyBox
 * @since  version (1.0)
 */

?>

<?php do_action( 'buddybox_before_loop' ); ?>

<?php if ( buddybox_has_items( buddybox_querystring() ) ): ?>

	<?php if ( empty( $_POST['page'] ) && empty( $_POST['folder'] ) ) : ?>

		<table id="buddybox-dir" class="user-dir">
			<thead>
				<tr><th><?php buddybox_th_owner_or_cb();?></th><th class="buddybox-item-name"><?php _e( 'Name', 'buddybox' );?></th><th class="buddybox-privacy"><?php _e( 'Privacy', 'buddybox' );?></th><th class="buddybox-mime-type"><?php _e( 'Type', 'buddybox' );?></th><th class="buddybox-last-edit"><?php _e( 'Last edit', 'buddybox' );?></th></tr>
			</thead>
			<tbody>
	<?php endif; ?>

	<?php while ( buddybox_has_items() ) : buddybox_the_item(); ?>

		<?php buddybox_get_template( 'buddybox-entry', false );?>

	<?php endwhile; ?>

	<?php if ( buddybox_has_more_items() ) : ?>

		<tr>
			<td class="buddybox-load-more" colspan="5">
				<a href="#more-buddybox"><?php _e( 'Load More', 'buddybox' ); ?></a>
			</td>
		</tr>

	<?php endif; ?>

	<?php if ( empty( $_POST['page'] ) && empty( $_POST['folder'] ) ) : ?>
			</tbody>
		</table>

	<?php endif; ?>

<?php else : ?>

	<?php if ( empty( $_POST['page'] ) && empty( $_POST['folder'] ) ) : ?>
		<table id="buddybox-dir" class="user-dir">
			<thead>
				<tr><th><?php buddybox_th_owner_or_cb();?></th><th class="buddybox-item-name"><?php _e( 'Name', 'buddybox' );?></th><th class="buddybox-privacy"><?php _e( 'Privacy', 'buddybox' );?></th><th class="buddybox-mime-type"><?php _e( 'Type', 'buddybox' );?></th><th class="buddybox-last-edit"><?php _e( 'Last edit', 'buddybox' );?></th></tr>
			</thead>
			<tbody>
	<?php endif;?>
			<tr id="no-buddyitems">
				<td colspan="5">
					<div id="message" class="info">
						<p><?php _e( 'Sorry, there was no buddybox items found.', 'buddybox' ); ?></p>
					</div>
				</td>
			</tr>
	<?php if ( empty( $_POST['page'] ) && empty( $_POST['folder'] ) ) : ?>
			</tbody>
		</table>
	<?php endif;?>
	

<?php endif; ?>

<?php do_action( 'buddybox_after_loop' ); ?>

<?php if ( empty( $_POST['page'] ) && empty( $_POST['folder'] ) ) : ?>

	<form action="" name="buddybox-loop-form" id="buddybox-loop-form" method="post">

		<?php wp_nonce_field( 'buddybox_actions', '_wpnonce_buddybox_actions' ); ?>

	</form>
<?php endif;?>