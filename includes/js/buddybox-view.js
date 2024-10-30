function buddyboxStripLast() {
	if( jQuery('#buddybox-dir tbody').find('.buddybox-more').length )
		jQuery('#buddybox-dir tbody').find('.buddybox-more').prev().remove();
}

function openFolder( srcstring ) {
	var folder_id = srcstring.replace('?folder-', '');
	var buddyscope = false;

	if( jQuery('.buddybox-type-tabs li.current a').length )
		buddyscope = jQuery('.buddybox-type-tabs li.current a').attr('id');

	folder_id = Number(folder_id) + 0;

	if( !isNaN( folder_id ) ) {
		var data = {
      		action:'buddybox_openfolder',
	  		folder: folder_id,
			foldername:1,
			scope:buddyscope
    	};

    	jQuery('#buddybox-dir tbody').html('<tr><td colspan="5"><p class="buddybox-opening-dir"><a class="loading">'+buddybox_view.loading+'</a></p></td></tr>');

		jQuery.post(ajaxurl, data, function(response) {
			
			jQuery('#buddy-new-folder').hide();

			jQuery('.buddytree').each(function(){
				jQuery(this).removeClass('current');
			});
			
			if( response.length > 1)
				jQuery('.buddybox-crumbs').append( ' / <span id="folder-'+folder_id+'" class="buddytree current"><input type="hidden" id="buddybox-open-folder" value="'+folder_id+'">'+response[1]+'</span>' );
			
			jQuery('#buddybox-dir tbody').html('');
	        jQuery("#buddybox-dir tbody").prepend(response[0]);
			
	    }, 'json' );
	}
}

jQuery(document).ready(function($){
	$.cookie( 'buddybox-oldestpage', 1, {path: '/'} );

	if ( '-1' != window.location.search.indexOf('folder-') )
		openFolder( window.location.search );
	
	$('#buddybox-dir').on('click', '.buddybox-load-more a', function(){
		var currentfolder = group_id = 0;
		
		$('.buddytree').each(function(){
			if( $(this).hasClass('current') )
				currentfolder = $(this).attr('id').replace('folder-', '');
		});
		
		var buddyscope = 'groups';

		if( $('.buddybox-type-tabs li.current a').length )
			buddyscope = $('.buddybox-type-tabs li.current a').attr('id');

		if( buddyscope == 'groups' && $('#buddybox-home').attr('data-group') )
			group_id = $('#buddybox-home').attr('data-group');
		
		var loadmore_tr = $(this).parent().parent();
		
		$(this).addClass('loading');
		
		if ( null == $.cookie('buddybox-oldestpage') )
	        $.cookie('buddybox-oldestpage', 1, {path: '/'} );

	    var oldest_page = ( $.cookie('buddybox-oldestpage') * 1 ) + 1;
		
		var data = {
	      action:'buddybox_loadmore',
	      page: oldest_page,
		  folder:currentfolder,
		  scope:buddyscope,
		  group:group_id
	    };

	    $.post(ajaxurl, data, function(response) {
	        $.cookie( 'buddybox-oldestpage', oldest_page, {path: '/'} );
	        $("#buddybox-dir tbody").append(response);
			loadmore_tr.hide();
	    });
		
		return false;
	});
	
	$('#buddybox-dir').on('click', '.buddyfolder', function(){
		var buddyscope = false;
		
		$.cookie( 'buddybox-oldestpage', 1, {path: '/'} );

		if( $('.buddybox-type-tabs li.current a').length )
			buddyscope = $('.buddybox-type-tabs li.current a').attr('id');
		
		parent_id = $(this).attr('data-folder');
		$('#buddy-new-folder').hide();

		$('.buddytree').each(function(){
			$(this).removeClass('current');
		});
		
		$('.buddybox-crumbs').append( ' / <span id="folder-'+parent_id+'" class="buddytree current"><input type="hidden" id="buddybox-open-folder" value="'+parent_id+'">'+$(this).html()+'</span>' );
		
		var data = {
	      action:'buddybox_openfolder',
		  folder: parent_id,
		  scope:buddyscope
	    };

	    $('#buddybox-dir tbody').html('<tr><td colspan="5"><p class="buddybox-opening-dir"><a class="loading">'+buddybox_view.loading+'</a></p></td></tr>');
	
		$.post(ajaxurl, data, function(response) {
			$('#buddybox-dir tbody').html('');
	        $("#buddybox-dir tbody").prepend(response[0]);
	    }, 'json' );
		
		return false;
		
	});

	$('#buddybox-dir').on('click', '.buddybox-row-actions a', function(){
		if( $(this).hasClass('buddybox-private-message') )
			return true;

		if( $(this).hasClass('buddybox-group-activity') ) {

			if( $(this).hasClass('loading') )
				return false;

			target = $(this).parent().parent().parent().find('a').first().attr('data-file');
			
			if( !target )
				target = $(this).parent().parent().parent().find('a').first().attr('data-folder');
			
			link = $(this).parent().parent().parent().find('a').first().attr('href');
			var shared = $(this);
			$(this).addClass('loading');
			
			var data = {
		      action:'buddybox_groupupdate',
			  itemid: target,
			  url:link,
			  '_wpnonce_buddybox_actions': $("input#_wpnonce_buddybox_actions").val()
		    };
		
			$.post(ajaxurl, data, function(response) {
				if( response == 1 ) {
					shared.html( buddybox_view.shared );
					shared.css('color', 'green');
				}
				shared.removeClass('loading');
		    });
		}

		if( $(this).hasClass('buddybox-remove-group') ) {
			if( $(this).hasClass('loading') )
				return false;
			
			target = $(this).parent().parent().parent().find('a').first().attr('data-file');
			
			if( !target )
				target = $(this).parent().parent().parent().find('a').first().attr('data-folder');

			group = $(this).attr('data-group');

			$(this).addClass('loading');
			
			var data = {
		      action:'buddybox_removefromgroup',
			  itemid: target,
			  groupid: group,
			  '_wpnonce_buddybox_actions': $("input#_wpnonce_buddybox_actions").val()
		    };
		
			$.post(ajaxurl, data, function(response) {
				if( response == 1 ) {
					$('tr#item-'+target).remove();
				} else {
					alert( buddybox_view.group_remove_error );
				}
		    });


			return false;
		}
		
		var show = $(this).attr('class').replace('buddybox-show-', ''); 
		var desc = $(this).parent().parent().parent().find('.buddybox-ra-'+show);

		$(this).parent().parent().parent().parent().parent().find('.ba').each(function(){
			if( $(this).get(0) != desc.get(0) )
				$(this).addClass('hide');
		});

		if( desc.hasClass('hide') )
			desc.removeClass('hide');
		else
			desc.addClass('hide');

		if( show == 'link' )
			desc.find('input').focus();

		return false;
	});

	$.fn.selectRange = function(start, end) {
	    return this.each(function() {
	        if(this.setSelectionRange) {
	            this.focus();
	            this.setSelectionRange(start, end);
	        } else if(this.createTextRange) {
	            var range = this.createTextRange();
	            range.collapse(true);
	            range.moveEnd('character', end);
	            range.moveStart('character', start);
	            range.select();
	        }
	 });
	};

	$('#buddybox-dir').on('focus', '.buddybox-file-input', function() {
		$(this).selectRange( 0, $(this).val().length );
		return false;
	});
	
});