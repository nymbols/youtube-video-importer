/**
 * Video import form functionality
 * @version 1.0
 */
;(function($){
	$(document).ready(function(){
		// search criteria form functionality
		$('#yvi_feed').change(function(){
			var val = $(this).val(),
				ordVal = $('#yvi_order').val();
			
			$('label[for=yvi_query]').html($(this).find('option:selected').html()+' :');
						
			switch( val ){
				case 'query':
					$('tr.yvi_duration').show();
					$('tr.yvi_order').show();
					var hide = ['position', 'commentCount', 'duration', 'reversedPosition', 'title'],
						show = ['relevance', 'rating'];
					
					$.each( hide, function(i, el){
						$('#yvi_order option[value='+el+']').attr({'disabled':'disabled'}).css('display', 'none');
					})
					$.each( show, function(i, el){
						$('#yvi_order option[value='+el+']').removeAttr('disabled').css('display', '');
					})
					
					var hI = $.inArray( ordVal, hide );					
					if( -1 !== hI ){
						$('#yvi_order option[value='+hide[hI]+']').removeAttr('selected');
					}					
					
				break;
				case 'user':
				case 'playlist':
				case 'channel':
					$('tr.yvi_duration').hide();
					$('tr.yvi_order').hide();
					
					var show = ['position', 'commentCount', 'duration', 'reversedPosition', 'title'],
						hide = ['relevance', 'rating'];
				
					$.each( hide, function(i, el){
						$('#yvi_order option[value='+el+']').attr({'disabled':'disabled'}).css('display', 'none');
					})
					$.each( show, function(i, el){
						$('#yvi_order option[value='+el+']').removeAttr('disabled').css('display', '');
					})
					
					var hI = $.inArray( ordVal, hide );					
					if( -1 !== hI ){
						$('#yvi_order option[value='+hide[hI]+']').removeAttr('selected');
					}
					
				break;
			}			
		}).trigger('change');
		
		$('#yvi_load_feed_form').submit(function(e){
			var s = $('#yvi_query').val();
			if( '' == s ){
				e.preventDefault();
				$('#yvi_query, label[for=yvi_query]').addClass('yvi_error');
			}
		});
		$('#yvi_query').keyup(function(){
			var s = $(this).val();
			if( '' == s ){
				$('#yvi_query, label[for=yvi_query]').addClass('yvi_error');
			}else{
				$('#yvi_query, label[for=yvi_query]').removeClass('yvi_error');
			}	
		})
		
		// checkbox selectors
		var selects = $('input[name=select_all]');
		selects.click( function(){
			if( $(this).is(':checked') ){
				$('input[type=checkbox].yvi-item-check').attr('checked', true);
				$('input[type=checkbox].yvi-item-check').parents('.yvi-video-item').addClass('checked');
				$(selects).attr('checked', true);
			}else{
				$('input[type=checkbox].yvi-item-check').attr('checked', false);
				$('input[type=checkbox].yvi-item-check').parents('.yvi-video-item').removeClass('checked');
				$(selects).attr('checked', false);
			}			
		});
		
		$('input[type=checkbox].yvi-item-check').click(function(){
			if( $(this).is(':checked') ){				
				$(this).parents('.yvi-video-item').addClass('checked');
			}else{
				$(this).parents('.yvi-video-item').removeClass('checked');
			}
		})
		
		$('#yvi-new-search').click( function(e){
			e.preventDefault();
			$('#search_box').toggle(100);
		})
		
		// view switcher functionality
		var switches = $('.view-switch .yvi-view');		
		switches.click( function(e){
			e.preventDefault();
			$(switches).removeClass('current');
			$(this).addClass('current');
			
			var v = $(this).data('view'),
				c = v == 'list' ? 'grid' : 'list' ;			
			$('.yvi-video-item').removeClass(c).addClass( v );
			
			yvi_view_data.view = v;
			
			$.ajax({
				type 	: 'post',
				url 	: ajaxurl,
				data	: yvi_view_data,
				dataType: 'json'
			});			
		})
		
		/**
		 * Feed results table functionality
		 */		
		// rename table action from action (which conflicts with ajax) to action_top
		$('.ajax-submit .tablenav.top .actions select[name=action]').attr({'name' : 'action_top'});		
		// form submit on search results
		var submitted = false;
		$('.ajax-submit').submit(function(e){
			e.preventDefault();
			if( submitted ){
				$('.yvi-ajax-response')
					.html(yvi_importMessages.wait);
				return;
			}
			
			var dataString 	= $(this).serialize();
			submitted = true;
			
			$('.yvi-ajax-response')
				.removeClass('success error')
				.addClass('loading')
				.html(yvi_importMessages.loading);
			
			$.ajax({
				type 	: 'post',
				url 	: ajaxurl,
				data	: dataString,
				dataType: 'json',
				success	: function(response){
					if( response.success ){
						$('.yvi-ajax-response')
							.removeClass('loading error')
							.addClass('success')
							.html( response.success );
					}else if( response.error ){
						$('.yvi-ajax-response')
							.removeClass('loading success')
							.addClass('error')
							.html( response.error );
					}										
					submitted = false;
				},
				error: function(response){
					$('.yvi-ajax-response')
						.removeClass('loading success')
						.addClass('error')
						.html( yvi_importMessages.server_error + '<div id="yvi_server_error_output" style="display:none;">'+ response.responseText +'</div>' );
					
					$('#yvi_import_error').click(function(e){
						e.preventDefault();
						$('#yvi_server_error_output').toggle();
					});
					
					submitted = false;
				}
			});			
		});		
	})
})(jQuery);