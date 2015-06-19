/**
 * Playlist creation/editing script 
 */
;(function($){
	
	$(document).ready(function(){
		
		var submitted 	= false,
			om			= $('#yti_check_playlist').html(),
			message 	= $('#yti_check_playlist');
		
		$('select[name=playlist_type]').change(function(){
			var val = $(this).val();
			switch( val ){
				case 'user':
				case 'channel':	
					$('tr#publish-date-filter').show();	
					$('#playlist-alert').hide();
				break;
				default:
					$('tr#publish-date-filter').hide();
					if( $('#no_reiterate').is(':checked') ){
						$('#playlist-alert').show();
					}
				break;	
			}
		});
		
		$('#playlist_id').keydown(function(){
			$(message).html(om);
		});
		
		$('#yti_verify_playlist').click(function(e){
			e.preventDefault();
			$(this).addClass('loading');
			$(message).addClass('loading-message');
			
			if( submitted ){
				$(message).html( yti_pq.still_loading );
				return;
			}
			submitted = true;
			$(message).html( yti_pq.loading );
			
			var self 			= this,
				playlist_id 	= $('#playlist_id').val(),
				playlist_type 	= $('#playlist_type').val();
			
			var data = {
				'action' 	: 'yti_check_playlist',
				'id'		: playlist_id,
				'type'		: playlist_type
			};
			
			$.ajax({
				type 	: 'post',
				url 	: ajaxurl,
				data	: data,
				success	: function( response ){
					$(message).html( response );
					submitted = false;
					$(self).removeClass('loading');
					$(message).removeClass('loading-message');
				}
			});			
		});
		
		$('#no_reiterate').click(function(){
			if( 'playlist' != $('#playlist_type').val() ){
				$('#playlist-alert').hide();
				return;
			}
			if( $(this).is(':checked') ){
				$('#playlist-alert').show();
			}else{
				$('#playlist-alert').hide();
			}
			
		});
		
		// category changer; keep this last in functions because of the return
		var checkbox = $('#theme_import');
		if( 0 == checkbox.length ){
			return;
		}
		
		$(checkbox).click(function(){
			if( $(this).is(':checked') ){
				$('#native_tax_row').hide();
				$('#theme_tax_row').show();
			}else{
				$('#native_tax_row').show();
				$('#theme_tax_row').hide();
			}			
		});
		
		
	});	
	
})(jQuery);