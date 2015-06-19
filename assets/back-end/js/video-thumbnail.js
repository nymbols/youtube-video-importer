/**
 * 
 */
;(function($){
	$(document).ready(function(){
		
		$('#yti-import-video-thumbnail').live('click', function(e){
			e.preventDefault();
			
			var data = {
				'action' 	: 'yti_import_video_thumbnail',
				'id'		: YTI_POST_DATA.post_id
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