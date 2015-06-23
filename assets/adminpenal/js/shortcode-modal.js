/**
 * TinyMce playlist shortcode insert 
 */
var YVIIVideo_DIALOG_WIN = false;
;(function($){
	$(document).ready(function(){
		$('#yvi-insert-playlist-shortcode').live('click', function(e){
			e.preventDefault();
			var videos 		= $('#yvi-playlist-items').find('input[name=yvi_selected_items]').val();
			if( '' == videos ){
				return;
			}
			
			var videos_array = $.grep( videos.split('|'), function(val){ return '' != val }),
				shortcode 	= '[yvi_playlist videos="'+( videos_array.join(',') )+'"]';;
			
			send_to_editor(shortcode);
			$(YVIIVideo_DIALOG_WIN).dialog('close');
		});
		
		$('#yvi-shortcode-2-post').click(function(e){
			e.preventDefault();
			if( YVIIVideo_DIALOG_WIN ){
				YVIIVideo_DIALOG_WIN.dialog('open');
			}
		});
		
		// dialog window
		$('body').append('<div id="YVIIVideo_Modal_Window"></div>');
		var url = 'edit.php?post_type=video&page=yvi_videos';
		
		var dialog = $('#YVIIVideo_Modal_Window').dialog({
			'autoOpen'		: false,
			'width'			: '90%',
			'height'		: 750,
			'maxWidth'		: '90%',
			'maxHeight'		: 750,
			'minWidth'		: '90%',
			'minHeight'		: 750,
			'modal'			: true,
			'dialogClass'	: 'wp-dialog',
			'title'			: '',
			'resizable'		: true,
			'open'			:function(ui){
				$(ui.target)
					.css({'overflow':'hidden'})
					.append(
						'<div class="wrap"><div id="yvi-playlist-items">'+
							'<div class="inside">'+
								'<input type="hidden" name="yvi_selected_items"  value="" />'+
								'<h2>'+YVI_SHORTCODE_MODAL.playlist_title+' <a href="#" id="yvi-insert-playlist-shortcode" class="add-new-h2">'+YVI_SHORTCODE_MODAL.insert_playlist+'</a></h2>'+
								'<div id="yvii-list-items">'+
									'<em>'+YVI_SHORTCODE_MODAL.no_videos+'</em>'+
								'</div>'+
							'</div>'+	
						'</div>'+
						'<div id="yvi-display-videos">'+
							'<iframe src="'+url+'" frameborder="0" width="100%" height="100%"></iframe>'+
						'</div></div>'
					);
				
			},
			'close':function(ui){
				$(ui.target).empty();
			}
		})		
		YVIIVideo_DIALOG_WIN = dialog;		
	});
})(jQuery);