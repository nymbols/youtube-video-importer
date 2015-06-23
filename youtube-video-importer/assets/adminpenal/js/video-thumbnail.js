/**
 * 
 */
;(function($){
	$(document).ready(function(){
		
		$('#yvi-import-video-thumbnail').live('click', function(e){
			e.preventDefault();
			
			var data = {
				'action' 	: 'yvi_import_video_thumbnail',
				'id'		: YVI_POST_DATA.post_id
			};
			
			$.ajax({
				type 	: 'post',
				url 	: ajaxurl,
				data	: data,
				success	: function( response ){
					WPSetThumbnailHTML( response.data );
				}
			});	
			
		});
		
	});
})(jQuery);