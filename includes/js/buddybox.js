
function fileDialogStart() {
	jQuery("#media-upload-error").empty();
}

// progress and success handlers for media multi uploads
function fileQueued(fileObj) {
	// Get rid of unused form
	jQuery('.media-blank').remove();
	
	if( jQuery('#no-buddyitems').length )
		jQuery('#no-buddyitems').remove();

	var items = jQuery('#media-items').children(), postid = 0;

	// Create a progress bar containing the filename
	jQuery('#buddybox-dir tbody').prepend('<tr><td colspan="4"><div id="media-item-' + fileObj.id + '" class="media-item child-of-' + postid + '"><div class="progress"><div class="percent">0%</div><div class="bar"></div></div><div class="filename original"> ' + fileObj.name + '</div></div></td></tr>');

}

function uploadProgress(up, file) {
	var item = jQuery('#media-item-' + file.id);

	jQuery('.bar', item).width( (200 * file.loaded) / file.size );
	jQuery('.percent', item).html( file.percent + '%' );
}

// check to see if a large file failed to upload
function fileUploading(up, file) {
	var hundredmb = 100 * 1024 * 1024, max = parseInt(up.settings.max_file_size, 10);

	if ( max > hundredmb && file.size > hundredmb ) {
		setTimeout(function(){
			var done;

			if ( file.status < 3 && file.loaded == 0 ) { // not uploading
				wpFileError(file, pluploadL10n.big_upload_failed.replace('%1$s', '<a class="uploader-html" href="#">').replace('%2$s', '</a>'));
				up.stop(); // stops the whole queue
				up.removeFile(file);
				up.start(); // restart the queue
			}
		}, 10000); // wait for 10 sec. for the file to start uploading
	}
}

function updateMediaForm() {
	
	jQuery('#buddybox-sharing-details').html('');
	jQuery('#buddyfile-desc').val('');
	jQuery('#buddybox-sharing-options').val('private');
	jQuery('#buddybox-sharing-settings').val('private');
	
	jQuery('.next-step').each( function(){
		jQuery(this).show();
	});
	
	jQuery('#buddybox-third-step').addClass('hide');
	jQuery('#buddybox-second-step').addClass('hide');
	jQuery('#buddybox-file-uploader').addClass('hide');
	
	
	jQuery('#buddybox-edit-item').html('');
	jQuery('#buddybox-edit-item').addClass('hide');
	
}

function uploadSuccess(fileObj, serverData) {
	var item = jQuery('#media-item-' + fileObj.id);

	// on success serverData should be numeric, fix bug in html4 runtime returning the serverData wrapped in a <pre> tag
	serverData = serverData.replace(/^<pre>(\d+)<\/pre>$/, '$1');

	// if async-upload returned an error message, place it in the media item div and return
	if ( serverData.match(/media-upload-error|error-div/) ) {
		item.html(serverData);
		return;
	} else {
		jQuery('.percent', item).html( pluploadL10n.crunching );
	}

	prepareMediaItem( fileObj, serverData );
	updateMediaForm();
}


function prepareMediaItem(fileObj, serverData ) {
	
	parenttr = jQuery('#media-item-' + fileObj.id).parent().parent();

	var data = {
      action:'buddybox_fetchfile',
      createdid:serverData
    };

	jQuery.post(ajaxurl, data, function(response) {
        parenttr.html( response );
    });

	parenttr.attr({
	  'id': 'item-' + serverData,
	  'class': 'latest'
	});
	
	if( jQuery('#no-buddyitems').length )
		jQuery('#no-buddyitems').remove();

	updateBuddyQuota();
}

function updateBuddyQuota() {
	var data = {
      action:'buddybox_updatequota'
    };

	jQuery.post(ajaxurl, data, function(response) {
        jQuery('#buddy-quota').html(response);
    });

	return false;
}


// generic error message
function wpQueueError(message) {
	jQuery('#media-upload-error').show().html( '<div class="error"><p>' + message + '</p></div>' );
}

// file-specific error messages
function wpFileError(fileObj, message) {
	itemAjaxError(fileObj.id, message);
}

function itemAjaxError(id, message) {
	var item = jQuery('#media-item-' + id), filename = item.find('.filename').text(), last_err = item.data('last-err');

	if ( last_err == id ) // prevent firing an error for the same file twice
		return;

	item.html('<div class="error-div">'
				+ '<a class="dismiss" href="#">' + pluploadL10n.dismiss + '</a>'
				+ '<strong>' + pluploadL10n.error_uploading.replace('%s', jQuery.trim(filename)) + '</strong> '
				+ message
				+ '</div>').data('last-err', id);
}


function dndHelper(s) {
	var d = document.getElementById('dnd-helper');

	if ( s ) {
		d.style.display = 'block';
	} else {
		d.style.display = 'none';
	}
}

function uploadError(fileObj, errorCode, message, uploader) {
	var hundredmb = 100 * 1024 * 1024, max;

	switch (errorCode) {
		case plupload.FAILED:
			wpFileError(fileObj, pluploadL10n.upload_failed);
			break;
		case plupload.FILE_EXTENSION_ERROR:
			wpFileError(fileObj, pluploadL10n.invalid_filetype);
			break;
		case plupload.FILE_SIZE_ERROR:
			uploadSizeError(uploader, fileObj);
			break;
		case plupload.IMAGE_FORMAT_ERROR:
			wpFileError(fileObj, pluploadL10n.not_an_image);
			break;
		case plupload.IMAGE_MEMORY_ERROR:
			wpFileError(fileObj, pluploadL10n.image_memory_exceeded);
			break;
		case plupload.IMAGE_DIMENSIONS_ERROR:
			wpFileError(fileObj, pluploadL10n.image_dimensions_exceeded);
			break;
		case plupload.GENERIC_ERROR:
			wpQueueError(pluploadL10n.upload_failed);
			break;
		case plupload.IO_ERROR:
			max = parseInt(uploader.settings.max_file_size, 10);

			if ( max > hundredmb && fileObj.size > hundredmb )
				wpFileError(fileObj, pluploadL10n.big_upload_failed.replace('%1$s', '<a class="uploader-html" href="#">').replace('%2$s', '</a>'));
			else
				wpQueueError(pluploadL10n.io_error);
			break;
		case plupload.HTTP_ERROR:
			wpQueueError(pluploadL10n.http_error);
			break;
		case plupload.INIT_ERROR:
			jQuery('.media-upload-form').addClass('html-uploader');
			break;
		case plupload.SECURITY_ERROR:
			wpQueueError(pluploadL10n.security_error);
			break;
/*		case plupload.UPLOAD_ERROR.UPLOAD_STOPPED:
		case plupload.UPLOAD_ERROR.FILE_CANCELLED:
			jQuery('#media-item-' + fileObj.id).remove();
			break;*/
		default:
			wpFileError(fileObj, pluploadL10n.default_error);
	}
}

function uploadSizeError( up, file, over100mb ) {
	var message;

	if ( over100mb )
		message = pluploadL10n.big_upload_queued.replace('%s', file.name) + ' ' + pluploadL10n.big_upload_failed.replace('%1$s', '<a class="uploader-html" href="#">').replace('%2$s', '</a>');
	else
		message = pluploadL10n.file_exceeds_size_limit.replace('%s', file.name);

	jQuery('#buddybox-dir tbody').prepend('<tr><td colspan="4"><div id="media-item-' + file.id + '" class="media-item error"><div class="error-div"><a class="dismiss" href="#">' + pluploadL10n.dismiss + '</a> <strong>' + message + '</strong></div></div></td></tr>');
	up.removeFile(file);
}

function buddyBoxListGroups( element ) {
	var data = {
      action:'buddybox_getgroups'
    };

    jQuery.post(ajaxurl, data, function(response) {
        jQuery(element).html( '<label for="buddygroup">Choose the group</label>' + response);
    });
}

function buddyboxStripLast() {
	if( jQuery('#buddybox-dir tbody').find('.buddybox-load-more').length )
		jQuery('#buddybox-dir tbody').find('.buddybox-load-more').parent().prev().remove();
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

    	jQuery('#buddybox-dir tbody').html('<tr><td colspan="5"><p class="buddybox-opening-dir"><a class="loading">'+pluploadL10n.loading+'</a></p></td></tr>');

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
	
	$('.next-step').each( function(){
		$(this).show();
	});
	
	$('#buddy-new-file').on('click', function(){
		if( !$('#buddybox-folder-editor').hasClass('hide') )
			$('#buddybox-folder-editor').addClass('hide');
		
		if( !$('#buddybox-edit-item').hasClass('hide') ){
			$('#buddybox-edit-item').html('');
			$('#buddybox-edit-item').addClass('hide');
		}
			
		$('#buddybox-file-uploader').removeClass('hide');
		
		return false;
	});
	
	$('#buddy-new-folder').on('click', function(){
		if( !$('#buddybox-file-uploader').hasClass('hide') )
			$('#buddybox-file-uploader').addClass('hide');
			
		if( !$('#buddybox-edit-item').hasClass('hide') ){
			$('#buddybox-edit-item').html('');
			$('#buddybox-edit-item').addClass('hide');
		}
			
		updateMediaForm();
			
		$('#buddybox-folder-editor').removeClass('hide');
		
		return false;
	});
	
	$('#buddybox-sel-all').on('change', function(){
		var status = $(this).attr('checked');
		
		if( !status )
			status = false;
		
		$('.buddybox-item-cb').each( function() {
			$(this).attr('checked', status );
		});
		
		return false;
	})

	$('#buddy-delete-item').on('click', function(){
		var itemlist="";
		var count = 0;
		
		if( !$('#buddybox-file-uploader').hasClass('hide') )
			$('#buddybox-file-uploader').addClass('hide');
			
		if( !$('#buddybox-folder-editor').hasClass('hide') )
			$('#buddybox-folder-editor').addClass('hide');
			
		if( !$('#buddybox-edit-item').hasClass('hide') ){
			$('#buddybox-edit-item').html('');
			$('#buddybox-edit-item').addClass('hide');
		}
		
		$('.buddybox-item-cb').each(function(){
			if( $(this).attr('checked') ) {
				itemlist += $(this).val()+',';
				count += 1;
			}
				
		});
		
		if( count == 0 ) {
			alert( pluploadL10n.cbs_message );
			return false;
		}
		
		var confirm_message = pluploadL10n.confirm_delete.replace( '%d', count );
		keepon = confirm( confirm_message );
		
		if( keepon ) {
			var data = {
		      action:'buddybox_deleteitems',
		      items: itemlist,
		      '_wpnonce_buddybox_actions': $("input#_wpnonce_buddybox_actions").val()
		    };

		    $.post(ajaxurl, data, function(response) {
			
				if( response['result'] == 0 ){
					alert( pluploadL10n.delete_error_message );
					return false;
				} else {
					
					for( i in response['items'] ){
						
						$("#buddybox-dir tbody #item-"+response['items'][i]).fadeOut(200, function(){
							$(this).remove();
						});
					}
					
				}
			
		        
		    }, 'json' );
		
			updateBuddyQuota();
		
		}

		return false;
	});
	
	$('#buddy-edit-item').on('click', function(){
		var count = 0;
		var item;
		
		if( !$('#buddybox-file-uploader').hasClass('hide') )
			$('#buddybox-file-uploader').addClass('hide');
			
		if( !$('#buddybox-folder-editor').hasClass('hide') )
			$('#buddybox-folder-editor').addClass('hide');
		
		$('.buddybox-item-cb').each(function(){
			if( $(this).attr('checked') ) {
				item = $(this).val();
				count += 1;
			}
				
		});
		
		if( count != 1 ) {
			alert( pluploadL10n.cb_message );
		} else {
			var data = {
		      action:'buddybox_editform',
		      buddybox_item: item
		    };

		    $.post(ajaxurl, data, function(response) {
			
				$('#buddybox-edit-item').html(response);
				$('#buddybox-edit-item').removeClass('hide');
			
		    } );
		}
		
		return false;
	});
	
	$('.cancel-step').on('click', function(){
		updateMediaForm();
		return false;
	});
	
	$('#buddybox-forms').on('click', '.cancel-item', function(){
		$('#buddybox-edit-item').html('');
		$('#buddybox-edit-item').addClass('hide');
		return false;
	});

	$('#buddybox-forms').on('submit', '#buddybox-item-edit-form', function(){

		var item_id = $('#buddybox-item-id').val();
		var item_title = $('#buddybox-item-title').val();
		var item_content = $('#buddybox-item-content').val();
		var item_sharing = $('#buddyitem-sharing-options').val();
		var item_folder = $('#folders').val();
		var item_password = false;
		var item_group = false;
		var errors = Array();
		
		switch( item_sharing ) {
			case 'password' :
				item_password = $('#buddypass').val();
				break;

			case 'groups':
				item_group = $('#buddygroup').val();
				break;
		}

		if( item_title.length < 1 ){
			errors.push( pluploadL10n.title_needed );
		}

		if( item_sharing == 'groups' && !item_group ){
			errors.push( pluploadL10n.group_needed );
		}

		if( item_sharing == 'password' && !item_password ){
			errors.push( pluploadL10n.pwd_needed );
		}

		if( errors.length >= 1 ) {
			var message = '';
			for( i in errors ) {
				message += errors[i] +"\n";
			}
			alert( message );
			return false;
		}

		var data = {
		      action:'buddybox_updateitem',
		      id: item_id,
		      title: item_title,
		      content: item_content,
		      sharing: item_sharing,
		      folder: item_folder,
		      password: item_password,
		      group: item_group,
		      '_wpnonce_buddybox_actions': $("input#_wpnonce_buddybox_actions").val()
		    };

		    $.post(ajaxurl, data, function(response) {
				$('#buddybox-edit-item').html('');
				$('#buddybox-edit-item').addClass('hide');
				
				if( response[0] != 0 ){
					currentfolder = 0;
					output = response[0].replace(/<tr[^>]*>/, '');
					output = output.replace(/<\/tr>/, '');

					$('.buddytree').each(function(){
						if( $(this).hasClass('current') )
							currentfolder = $(this).attr('id').replace('folder-', '');
					});

					if( response[1] === parseInt(currentfolder) ) {
						$('tr#item-'+item_id).html(output);
						$('tr#item-'+item_id).addClass('latest');
					} else {
						$('tr#item-'+item_id).remove();
					}
					
				} else {
					alert('oops');
				}
		    }, 'json' );

		return false;
	});
	
	$('.next-step').on('click', function(){
		var parent = $(this).parent().parent().parent();
		
		var nextstep = parent.find('.hide').first();
		
		$(this).hide();
		
		if( $('#buddybox-open-folder').length )
			$('#buddybox-third-step').removeClass('hide');
		else
			nextstep.removeClass('hide');
			
		return false;
	});
	
	$('#buddybox-forms').on('change', 'select', function(){
		
		var id_details, id_settings;
		
		if( $(this).attr('id') == 'buddygroup' || $(this).attr('id') == 'folders' ){
			return false;
		} else if( $(this).attr('id') == 'buddybox-sharing-options' ) {
			id_details = '#buddybox-sharing-details';
			id_settings = '#buddybox-sharing-settings';
		} else if( $(this).attr('id') == 'buddyitem-sharing-options' ){
			id_details = '#buddybox-admin-privacy-detail';
		}else {
			id_details = '#buddyfolder-sharing-details';
			id_settings = '#buddyfolder-sharing-settings';
		}
			
		
		var sharing_option = $(this).val();
		
		switch(sharing_option) {
			case 'password':
				$(id_details).html('<label for="buddypass">'+pluploadL10n.define_pwd+'</label><input type="text" id="buddypass">');
				if(id_settings)
					$(id_settings).val( sharing_option );
				break;
			case 'groups':
				buddyBoxListGroups( id_details );
				if(id_settings)
					$(id_settings).val( sharing_option );
				break;
			default:
				$(id_details).html('');
				if(id_settings)
					$(id_settings).val( sharing_option );
				break;
		}
		
		return false;
	});
	
	$('#buddybox-dir').on('click', '.buddybox-load-more a', function(){
		var currentfolder = buddyscope = 0;
		var itemlist = '';
		
		$('.buddytree').each(function(){
			if( $(this).hasClass('current') )
				currentfolder = $(this).attr('id').replace('folder-', '');
		});

		if( $('.buddybox-type-tabs li.current a').length )
			buddyscope = $('.buddybox-type-tabs li.current a').attr('id');

		$('tr.latest .buddybox-item-cb').each( function(){
				itemlist += $(this).val() +',';
		});

		if( itemlist.length >= 2 )
			itemlist = itemlist.substring( 0, itemlist.length - 1 );
		
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
		  exclude:itemlist
	    };

	    $.post(ajaxurl, data, function(response) {
	        $.cookie( 'buddybox-oldestpage', oldest_page, {path: '/'} );
	        $("#buddybox-dir tbody").append(response);
			loadmore_tr.hide();
	    });
		
		return false;
	});
	
	$('#buddybox-folder-editor-form').on('submit', function(){
		var buddygroup, buddyshared, buddypass;
		
		if( $('#buddybox-folder-title').val().length < 1 ) {
			alert( pluploadL10n.title_needed );
			return false;
		} 
			
		if( $('#buddyfolder-sharing-settings').val().length > 1 ) {
			buddyshared = $('#buddyfolder-sharing-settings').val();
			
			switch(buddyshared) {
				case 'password':
					if( $('#buddypass').val().length < 1 ){
						alert( pluploadL10n.pwd_needed );
						return false;
					} else {
						buddypass = $('#buddypass').val();
					}
					break;
				case 'groups':
					buddygroup = $('#buddygroup').val();
					break;
			}
			
			var data = {
		      action:'buddybox_createfolder',
			  title: $('#buddybox-folder-title').val(),
		      sharing_option: buddyshared,
			  sharing_pass: buddypass,
			  sharing_group: buddygroup,
			  '_wpnonce_buddybox_actions': $("input#_wpnonce_buddybox_actions").val()
		    };
		
			$.post(ajaxurl, data, function(response) {
		        $("#buddybox-dir tbody").prepend(response);
		        $("#buddybox-dir tbody tr").first().addClass('latest');
		        if( $('#no-buddyitems').length )
		        	$('#no-buddyitems').remove();
				
				$('.cancel-folder').trigger('click');
		    });
			
			return false;
		}
		
		return false;
	});
	
	$('.cancel-folder').on('click', function(){
		$('.next-step').each( function(){
			$(this).show();
		});
		
		$('#buddyfolder-second-step').addClass('hide');
		$('#buddybox-folder-editor').addClass('hide');
		
		jQuery('#buddyfolder-sharing-details').html('');
		jQuery('#buddybox-folder-title').val('');
		jQuery('#buddyfolder-sharing-options').val('private');
		jQuery('#buddyfolder-sharing-settings').val('private');
		
		return false;
	});
	
	$('#buddybox-dir').on('click', '.buddyfolder', function(){
		var buddyscope;
		
		$.cookie( 'buddybox-oldestpage', 1, {path: '/'} );
		
		updateMediaForm();

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

	    $('#buddybox-dir tbody').html('<tr><td colspan="5"><p class="buddybox-opening-dir"><a class="loading">'+pluploadL10n.loading+'</a></p></td></tr>');
	
		$.post(ajaxurl, data, function(response) {
			$('#buddybox-dir tbody').html('');
	        $("#buddybox-dir tbody").prepend(response[0]);
	    }, 'json' );
		
		return false;
		
	});

	$('#buddybox-dir').on('click', '.dismiss', function(){
		$(this).parent().parent().parent().parent().fadeOut(200, function(){
				$(this).remove();
			});
		return false;
	});

	$('#buddybox-dir').on('click', '.buddybox-row-actions a', function(){
		if( $(this).hasClass('buddybox-private-message') )
			return true;

		if( $(this).hasClass('buddybox-group-activity') || $(this).hasClass('buddybox-profile-activity') ) {

			if( $(this).hasClass('loading') || $(this).hasClass('shared') )
				return false;

			target = $(this).parent().parent().parent().parent().find('.buddybox-item-cb').val();
			link = $(this).parent().parent().parent().find('a').first().attr('href');
			buddytype = $(this).parent().parent().parent().find('a').first().attr('class');
			
			if( buddytype.indexOf( 'buddyfile' ) != -1 )
				buddytype = 'file';
				
			if( buddytype.indexOf( 'buddyfolder' ) != -1 )
				buddytype = 'folder';
			
			var shared = $(this);
			$(this).addClass('loading');
			
			var activity_type = 'buddybox_groupupdate';
			
			if( $(this).hasClass('buddybox-profile-activity') )
				activity_type = 'buddybox_profileupdate';
			
			var data = {
		      action: activity_type,
			  itemid: target,
			  url:link,
			  itemtype: buddytype,
			  '_wpnonce_buddybox_actions': $("input#_wpnonce_buddybox_actions").val()
		    };
		
			$.post(ajaxurl, data, function(response) {

				if( response == 1 ) {
					shared.html( pluploadL10n.shared );
					shared.css('color', 'green');
					shared.addClass('shared');
				} else {
					alert( response );
				}
				shared.removeClass('loading');
		    });
		}

		if( $(this).hasClass('buddybox-remove-group') )
			return false;
		
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
	

	// init and set the uploader
	uploader_init = function() {
		uploader = new plupload.Uploader(wpUploaderInit);


		uploader.bind('Init', function(up) {
			var uploaddiv = $('#plupload-upload-ui');

			if ( up.features.dragdrop && ! $(document.body).hasClass('mobile') ) {
				uploaddiv.addClass('drag-drop');
				$('#drag-drop-area').bind('dragover.wp-uploader', function(){ // dragenter doesn't fire right :(
					uploaddiv.addClass('drag-over');
				}).bind('dragleave.wp-uploader, drop.wp-uploader', function(){
					uploaddiv.removeClass('drag-over');
				});
			} else {
				uploaddiv.removeClass('drag-drop');
				$('#drag-drop-area').unbind('.wp-uploader');
			}

			if ( up.runtime == 'html4' )
				$('.upload-flash-bypass').hide();
		});

		uploader.init();

		uploader.bind('FilesAdded', function(up, files) {
			// one file at a time !
			if(files.length > 1) {
				alert( pluploadL10n.one_at_a_time );
				return false;
			}
			
			if( $('#buddyfile-desc').val().length > 1 )
				up.settings.multipart_params.buddydesc = $('#buddyfile-desc').val();
				
			if( $('#buddybox-sharing-settings').val().length > 1 ) {
				up.settings.multipart_params.buddyshared = $('#buddybox-sharing-settings').val();
				
				switch(up.settings.multipart_params.buddyshared) {
					case 'password':
						if( $('#buddypass').val().length < 1 ){
							alert( pluploadL10n.pwd_needed );
							return false;
						} else {
							up.settings.multipart_params.buddypass = $('#buddypass').val();
						}
						break;
					case 'groups':
						up.settings.multipart_params.buddygroup = $('#buddygroup').val();
						break;
				}
			}
			
			if( $('#buddybox-open-folder').length )
				up.settings.multipart_params.buddyfolder = $('#buddybox-open-folder').val();
				
			
			var hundredmb = 100 * 1024 * 1024, max = parseInt(up.settings.max_file_size, 10);

			$('#media-upload-error').html('');

			plupload.each(files, function(file){
				if ( max > hundredmb && file.size > hundredmb && up.runtime != 'html5' )
					uploadSizeError( up, file, true );
				else
					fileQueued(file);
			});

			up.refresh();
			up.start();
		});

		uploader.bind('BeforeUpload', function(up, file) {
			// something
		});

		uploader.bind('UploadFile', function(up, file) {
			fileUploading(up, file);
		});

		uploader.bind('UploadProgress', function(up, file) {
			uploadProgress(up, file);
		});

		uploader.bind('Error', function(up, err) {
			uploadError(err.file, err.code, err.message, up);
			up.refresh();
		});

		uploader.bind('FileUploaded', function(up, file, response) {
			uploadSuccess(file, response.response);
		});

		uploader.bind('UploadComplete', function(up, files) {
			// something
		});
	}

	if ( typeof(wpUploaderInit) == 'object' )
		uploader_init();

});