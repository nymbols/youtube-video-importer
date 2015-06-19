/**
 * 
 */
;(function($){
	$(document).ready(function(){
		
		$('.yti_aspect_ratio').live('change', function(){
			var aspect_ratio_input 	= this,
				parent				= $(this).parents('.yti-player-settings-options'),
				width_input			= $(parent).find('.yti_width'),
				height_output		= $(parent).find('.yti_height');		
			
			var val = $(this).val(),
				w 	= Math.round( parseInt($(width_input).val()) ),
				h 	= 0;
			switch( val ){
				case '4x3':
					h = (w*3)/4;
				break;
				case '16x9':
					h = (w*9)/16;
				break;	
			}
			
			$(height_output).html(h);						
		});
		
		
		$('.yti_width').live( 'keyup', function(){
			var parent				= $(this).parents('.yti-player-settings-options'),
				aspect_ratio_input	= $(parent).find('.yti_aspect_ratio');		
						
			if( '' == $(this).val() ){
				return;				
			}
			var val = Math.round( parseInt( $(this).val() ) );
			$(this).val( val );	
			$(aspect_ratio_input).trigger('change');
		});
				
		
		// hide options dependant on controls visibility
		$('.yti_controls').click(function(e){
			if( $(this).is(':checked') ){
				$('.controls_dependant').show();
			}else{
				$('.controls_dependant').hide();
			}
		})
		
		// in widgets, show/hide player options if latest videos isn't displayed as playlist
		$('.yti-show-as-playlist-widget').live('click', function(){
			var parent 		= $(this).parents('.yti-player-settings-options'),
				player_opt 	= $(parent).find('.yti-recent-videos-playlist-options'),
				list_thumbs = $(parent).find('.yti-widget-show-yt-thumbs');
			if( $(this).is(':checked') ){
				$(player_opt).show();
				$(list_thumbs).hide();
			}else{
				$(player_opt).hide();
				$(list_thumbs).show();
			}
			
		})
		
	});
})(jQuery);