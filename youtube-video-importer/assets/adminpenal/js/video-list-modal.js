/**
 * Modal window for videos list functionality
 */
;(function($){
	$(document).ready(function(){
		
		// check all functionality
		var chkbxs = $('#cb-select-all-1, #cb-select-all-2, .yvii-video-list-select-all');		
		$(chkbxs).click(function(){
			if( $(this).is(':checked') ){
				$('.yvi-video-checkboxes').attr('checked', 'checked').trigger('change');
				$(chkbxs).attr('checked', 'checked');
			}else{
				$('.yvi-video-checkboxes').removeAttr('checked').trigger('change');
				$(chkbxs).removeAttr('checked');
			}
		});
		
		// some elements
		var playlistItemsContainer 	= window.parent.jQuery('#yvii-list-items'),
			m						= window.parent.YVI_SHORTCODE_MODAL,
			inputField				= $( window.parent.jQuery('#yvi-playlist-items') ).find('input[name=yvi_selected_items]'),
			in_playlist				= $.grep( $(inputField).val().split('|'), function(val){ return '' != val });
			
		
		// check boxes on load
		if(in_playlist.length > 0){
			$.each( in_playlist, function(i, post_id){
				$('#yvi-video-'+post_id).attr('checked', 'checked');
			});
		}
		
		// checkboxes functionality
		$('.yvi-video-checkboxes').change( function(){
			var post_id = $(this).val();			
			if( $(this).is(':checked') ){				
				if( in_playlist.length == 0 ){
					$(playlistItemsContainer).empty();
				}				
				if( -1 == $.inArray( post_id, in_playlist ) ){				
					in_playlist = $.merge(in_playlist, [post_id]);				
					var c = $('<div />', {
						'class'	: 'playlist_item',
						'id' 	: 'playlist_item_'+post_id,
						'html' 	: $('#title'+post_id).html() + ' <span class="duration">[' + $('#duration'+post_id).html() + ']</span>'
					}).appendTo( playlistItemsContainer );
					
					$('<a />', {
						'id' 	: 'yvi-del-'+post_id,
						'class' : 'yvi-del-item',
						'html' 	: m.deleteItem,
						'href' 	: '#',
						'click' : function(e){
							e.preventDefault();
							$('#yvi-video-'+post_id).removeAttr('checked');
							$(c).remove();
							in_playlist = $.grep( in_playlist, function(value, i){
								return post_id != value;
							});							
							if( in_playlist.length == 0 ){
								$(playlistItemsContainer).empty().html( '<em>'+m.no_videos+'</em>' );				
							}							
							$(inputField).val( in_playlist.join('|') );
						}
					}).prependTo(c);					
				}				
			}else{
				in_playlist = $.grep( in_playlist, function(value, i){
					if( post_id == value ){
						$(playlistItemsContainer).find('div#playlist_item_'+post_id).remove();
					}					
					return post_id != value;
				})
			}			
			if( in_playlist.length == 0 ){
				$(playlistItemsContainer).empty().html( '<em>'+m.no_videos+'</em>' );				
			}			
			$(inputField).val( in_playlist.join('|') );
		});
		
		
		// single shortcode
		var form = $('#yvi-video-list-form'),
			attsContainer = $('#yvi-shortcode-atts'),
			divId = false;
		
		$('.yvi-show-form').click(function(e){
			e.preventDefault();
			var post_id = $(this).attr('id').replace('yvi-embed-', '');
			divId = 'single-video-settings-'+post_id;
			
			$(form).hide();
			$(attsContainer).html( $('#'+divId).html() );
			$('#'+divId).empty();
		})
		
		$('.yvi-cancel-shortcode').live( 'click', function(e){
			e.preventDefault();
			
			var post_id = $(this).attr('id').replace('cancel', ''),
			divId = 'single-video-settings-'+post_id;;
			
			$('#'+divId).html( $(attsContainer).html() );
			$(attsContainer).empty();
			$(form).show();
		})
		
		$('.yvi-insert-shortcode').live('click', function(e){
			e.preventDefault();
			var post_id = $(this).attr('id').replace('shortcode', ''),
				divId	= 'single-video-settings-'+post_id,
				fields 	= $('#'+divId).find('input, select');
			
			var volume = $('#yvi_volume'+post_id).val(),
				width = $('#yvi_width'+post_id).val(),
				aspect = $('#yvi_aspect_ratio'+post_id).val(),
				autoplay = $('#yvi_autoplay'+post_id).is(':checked') ? 1 : 0,
				controls = $('#yvi_controls'+post_id).is(':checked') ? 1 : 0;
			
			var shortcode = '[yvi_video id="'+post_id+'" volume="'+volume+'" width="'+width+'" aspect_ratio="'+aspect+'" autoplay="'+autoplay+'" controls="'+controls+'"]';
			
			window.parent.send_to_editor(shortcode);
			window.parent.jQuery(window.parent.window.YVIIVideo_DIALOG_WIN).dialog('close');
			
		})
		
	});	
})(jQuery);
