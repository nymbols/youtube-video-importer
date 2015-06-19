/**
 * Theme Default
 */
;(function($){	
	$(document).ready(function(){		
		$('.yti-yt-playlist.default').YYTT_Player_Default();
		
		$.each( $('.yti-yt-playlist.default'), function(i, p){
			$(this).find('.playlist-visibility').click(function(e){
				e.preventDefault();
				if( $(this).hasClass('collapse') ){
					$(this).removeClass('collapse').addClass('expand');
					$(p).find('.yti-playlist').slideUp();
				}else{
					$(this).removeClass('expand').addClass('collapse');
					$(p).find('.yti-playlist').slideDown();
				}
			})
		});
	});	
})(jQuery);

;(function($){
	
	$.fn.YYTT_Player_Default = function( options ){
		
		if( 0 == this.length ){ 
			return false; 
		}
		
		// support multiple elements
       	if (this.length > 1){
       		this.each(function() { 
				$(this).YYTT_Player_Default(options);				
			});
       		return;
       	}
       	
       	var defaults = {
       		'player' 	: '.yti-player',
       		'items'	 	: '.yti-playlist-item a',
       		'attr'		: 'rel'       			
       	};
       	
       	var options 	= $.extend({}, defaults, options),
       		self		= this,
       		player 		= $(this).find( options.player ),
       		yt_player = false,
       		state 		= false,
       		items		= $(this).find( options.items );
       	
       	var initialize = function(){
       		
       		var playerData = $(player).data();// decode_data( $(player).html() );
       		$.each( items, function(i, item){
       			
       			var itemData = $(this).data();// decode_data( $(this).attr( options.attr ) );
       			$(this).data('video_data', itemData).removeAttr( options.attr );
       			if( 0 == i ){
       				var d 		= playerData;
       				d.video_id 	= itemData.video_id;
       				d.volume 	= itemData.volume; 
       				d.stateChange = playerState;       				
       				yt_player = $(player).YYTT_VideoPlayer(d); 
       				if( '1' == itemData.autoplay ){
       					yt_player.play();
       				}
       			}
       			
       			$(this).click(function(e){
       				e.preventDefault();
       				yt_player.load( itemData.video_id );
       				yt_player.setVolume( itemData.volume );
       				if( '1' == itemData.autoplay ){
       					yt_player.play();
       				}
       			})
       			
       		});
       		
       		return self;
       	}
       	
       	var decode_data = function( raw_data ){
       		return $.parseJSON( raw_data.replace(/<!--|-->/g, '') );
       	}
       	
       	var playerState = function( s ){
       		state = s;
       	}
       	
       	return initialize();
	}	
})(jQuery);