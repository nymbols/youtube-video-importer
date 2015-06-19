/**
 * TinyMce playlist shortcode insert 
 */
var YYTTVideo_DIALOG_WIN = false;
;(function($){
	$(document).ready(function(){
		$('#yti-insert-playlist-shortcode').live('click', function(e){
			e.preventDefault();
			var videos 		= $('#yti-playlist-items').find('input[name=yti_selected_items]').val();
			if( '' == videos ){
				return;
			}
			
			var videos_array = $.grep( videos.split('|'), function(val){ return '' != val }),
				shortcode 	= '[yti_playlist videos="'+( videos_array.join(',') )+'"]';;
			
			send_to_editor(shortcode);
			$(YYTTVideo_DIALOG_WIN).dialog('close');
		});
		
		$('#yti-shortcode-2-post').click(function(e){
			e.preventDefault();
			if( YYTTVideo_DIALOG_WIN ){
				YYTTVideo_DIALOG_WIN.dialog('open');
			}
		});
		
		// dialog window
		$('body').append('<div id="YYTTVideo_Modal_Window"></div>');
		var url = 'edit.php?post_type=video&page=yti_videos';
		
		var dialog = $('#YYTTVideo_Modal_Window').dialog({
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
						'<div class="wrap"><div id="yti-playlist-items">'+
							'<div class="inside">'+
								'<input type="hidden" name="yti_selected_items"  value="" />'+
								'<h2>'+YTI_SHORTCODE_MODAL.playlist_title+' <a href="#" id="yti-insert-playlist-shortcode" class="add-new-h2">'+YTI_SHORTCODE_MODAL.insert_playlist+'</a></h2>'+
								'<div id="yytt-list-items">'+
									'<em>'+YTI_SHORTCODE_MODAL.no_videos+'</em>'+
								'</div>'+
							'</div>'+	
						'</div>'+
						'<div id="yti-display-videos">'+
							'<iframe src="'+url+'" frameborder="0" width="100%" height="100%"></iframe>'+
						'</div></div>'
					);
				
			},
			'close':function(ui){
				$(ui.target).empty();
			}
		})		
		YYTTVideo_DIALOG_WIN = dialog;		
	});
})(jQuery);