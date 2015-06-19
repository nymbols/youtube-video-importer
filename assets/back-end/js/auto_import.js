;(function($){
	
	$(document).ready( function(){

		$('#yti_playlist_import_trigger').click(function(e){
			e.preventDefault();
			e.stopPropagation();
			$('#yti_import_playlists').toggle(100);
		});
		
		$('#yti_import_playlists').click(function(e){
			e.stopPropagation();
		});
		
		$(document).click( function(){
			$('#yti_import_playlists').hide(100);
		})
		
	});
	
})(jQuery);