<?php if( empty( $_POST['createdid'] ) ) :?>

	<tr id="item-<?php buddybox_item_id();?>">

<?php endif;?>

		<td>
			<?php buddybox_owner_or_cb();?>
		</td>
		<td>
			<?php buddybox_item_icon();?>&nbsp;<a href="<?php buddybox_action_link();?>" class="<?php buddybox_action_link_class();?>" title="<?php buddybox_item_title();?>"<?php buddybox_item_attribute();?>><?php buddybox_item_title();?></a>
			<?php buddybox_row_actions();?>
		</td>
		<td>
			<?php buddybox_item_privacy();?>
		</td>
		<td>
			<?php buddybox_item_mime_type();?>
		</td>
		<td>
			<?php buddybox_item_date();?>
		</td>

<?php if( empty( $_POST['createdid'] ) ) :?>

	</tr>

<?php endif;?>