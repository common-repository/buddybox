<?php if( buddybox_is_bp_default() ): ?>

	<?php get_header( 'buddypress' ); ?>

		<?php do_action( 'buddybox_before_directory_page' ); ?>

		<div id="content">
			<div class="padder">

				<?php do_action( 'buddybox_before_directory_content' ); ?>

				

				<h3><?php _e( 'BuddyBox', 'buddybox' ); ?></h3>

			
<?php else:?>

		<div id="buddypress">

				<?php do_action( 'buddybox_before_directory_content' ); ?>

<?php endif;?>

			<?php do_action( 'template_notices' ); ?>


			<div class="buddybox" role="main">
				
				<?php do_action( 'buddybox_directory_content' ); ?>

			</div><!-- .buddybox -->

			<?php do_action( 'buddybox_after_directory_content' ); ?>


<?php if( buddybox_is_bp_default() ):?>

			</div><!-- .padder -->
		</div><!-- #content -->

	<?php get_sidebar( 'buddypress' ); ?>
	<?php get_footer( 'buddypress' ); ?>

<?php else:?>

		</div>

<?php endif;?>