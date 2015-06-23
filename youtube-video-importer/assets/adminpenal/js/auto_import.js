;(function($){
	
	$(document).ready( function(){

		$('#yvi_playlist_import_trigger').click(function(e){
			e.preventDefault();
			e.stopPropagation();
			$('#yvi_import_playlists').toggle(100);
		});
		
		$('#yvi_import_playlists').click(function(e){
			e.stopPropagation();
		});
		
		$(document).click( function(){
			$('#yvi_import_playlists').hide(100);
		})
		
	});
	
})(jQuery);