/**
 * Video import form functionality
 * @version 1.0
 */
;(function($){
	$(document).ready(function(){
		// search criteria form functionality
		$('#yti_feed').change(function(){
			var val = $(this).val(),
				ordVal = $('#yti_order').val();
			
			$('label[for=yti_query]').html($(this).find('option:selected').html()+' :');
						
			switch( val ){
				case 'query':
					$('tr.yti_duration').show();
					$('tr.yti_order').show();
					var hide = ['position', 'commentCount', 'duration', 'reversedPosition', 'title'],
						show = ['relevance', 'rating'];
					
					$.each( hide, function(i, el){
						$('#yti_order option[value='+el+']').attr({'disabled':'disabled'}).css('display', 'none');
					})
					$.each( show, function(i, el){
						$('#yti_order option[value='+el+']').removeAttr('disabled').css('display', '');
					})
					
					var hI = $.inArray( ordVal, hide );					
					if( -1 !== hI ){
						$('#yti_order option[value='+hide[hI]+']').removeAttr('selected');
					}					
					
				break;
				case 'user':
				case 'playlist':
				case 'channel':
					$('tr.yti_duration').hide();
					$('tr.yti_order').hide();
					
					var show = ['position', 'commentCount', 'duration', 'reversedPosition', 'title'],
						hide = ['relevance', 'rating'];
				
					$.each( hide, function(i, el){
						$('#yti_order option[value='+el+']').attr({'disabled':'disabled'}).css('display', 'none');
					})
					$.each( show, function(i, el){
						$('#yti_order option[value='+el+']').removeAttr('disabled').css('display', '');
					})
					
					var hI = $.inArray( ordVal, hide );					
					if( -1 !== hI ){
						$('#yti_order option[value='+hide[hI]+']').removeAttr('selected');
					}
					
				break;
			}			
		}).trigger('change');
		
		$('#yti_load_feed_form').submit(function(e){
			var s = $('#yti_query').val();
			if( '' == s ){
				e.preventDefault();
				$('#yti_query, label[for=yti_query]').addClass('yti_error');
			}
		});
		$('#yti_query').keyup(function(){
			var s = $(this).val();
			if( '' == s ){
				$('#yti_query, label[for=yti_query]').addClass('yti_error');
			}else{
				$('#yti_query, label[for=yti_query]').removeClass('yti_error');
			}	
		})
		
		// checkbox selectors
		$('#select_all').click( function(){
			if( $(this).is(':checked') ){
				$('input[type=checkbox].yti-item-check').attr('checked', true);
				$('input[type=checkbox].yti-item-check').parents('.yti-video-item').addClass('checked');
			}else{
				$('input[type=checkbox].yti-item-check').attr('checked', false);
				$('input[type=checkbox].yti-item-check').parents('.yti-video-item').removeClass('checked');
			}			
		});
		
		$('input[type=checkbox].yti-item-check').click(function(){
			if( $(this).is(':checked') ){				
				$(this).parents('.yti-video-item').addClass('checked');
			}else{
				$(this).parents('.yti-video-item').removeClass('checked');
			}
		})
		
		$('#yti-new-search').click( function(e){
			e.preventDefault();
			$('#search_box').toggle(100);
		})
		
		// view switcher functionality
		var switches = $('.view-switch .yti-view');		
		switches.click( function(e){
			e.preventDefault();
			$(switches).removeClass('current');
			$(this).addClass('current');
			
			var v = $(this).data('view'),
				c = v == 'list' ? 'grid' : 'list' ;			
			$('.yti-video-item').removeClass(c).addClass( v );
			
			yti_view_data.view = v;
			
			$.ajax({
				type 	: 'post',
				url 	: ajaxurl,
				data	: yti_view_data,
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
				$('.yti-ajax-response')
					.html(yti_importMessages.wait);
				return;
			}
			
			var dataString 	= $(this).serialize();
			submitted = true;
			
			$('.yti-ajax-response')
				.removeClass('success error')
				.addClass('loading')
				.html(yti_importMessages.loading);
			
			$.ajax({
				type 	: 'post',
				url 	: ajaxurl,
				data	: dataString,
				dataType: 'json',
				success	: function(response){
					if( response.success ){
						$('.yti-ajax-response')
							.removeClass('loading error')
							.addClass('success')
							.html( response.success );
					}else if( response.error ){
						$('.yti-ajax-response')
							.removeClass('loading success')
							.addClass('error')
							.html( response.error );
					}										
					submitted = false;
				},
				error: function(response){
					$('.yti-ajax-response')
						.removeClass('loading success')
						.addClass('error')
						.html( yti_importMessages.server_error + '<div id="yti_server_error_output" style="display:none;">'+ response.responseText +'</div>' );
					
					$('#yti_import_error').click(function(e){
						e.preventDefault();
						$('#yti_server_error_output').toggle();
					});
					
					submitted = false;
				}
			});			
		});		
	})
})(jQuery);