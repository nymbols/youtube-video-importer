/**
 * 
 */
;(function($){
	
	$(document).ready(function(){
		
		var timer = $('#yvi-timer');
		if( 0 == timer.length ){
			return;
		}
		
		var t = $(timer).html(),
			parts = t.split(':');
		
		if( 1 == parts.length ){
			var sec = parseInt(parts[0]),
				min = 0,
				hour = 0;
		}else if( 2 == parts.length ){
			var sec = parseInt(parts[1]),
				min = parseInt(parts[0]),
				hour = 0;			
		}else if( 3 == parts.length ){
			var sec = parseInt(parts[2]),
				min = parseInt(parts[1]),
				hour = parseInt(parts[0]);
		}
		
		var d = $(timer).data();
		
		var decrease = function(){			
			sec--;
			if( sec < 0 ){
				if( min == 0 ){
					sec = 0;
					if( hour > 0 ){
						hour--;
						min = 59;
					}else if( hour < 1 ){
						$('#yvi-update-message').html( yvi_timer.ready );
						clearInterval(timerInterval);
						return;
					}					
				}else{
					sec = 59;
					min--;				
				}				
			}			
			var s = sec < 10 ? '0'+sec : sec,
				m = min < 10 ? '0'+min : min;
 				h = hour == 0 ? '' : hour+':';
			$(timer).html(h + m + ':' + s);			
		}
		
		var increase = function(){
			sec++;
			if( sec > 59 ){
				sec = 0;
				min++;
				if( min > 59 ){
					min = 0;
					hour++;
				}				
			}
			var s = sec < 10 ? '0'+sec : sec,
					m = min < 10 ? '0'+min : min;
	 				h = hour == 0 ? '' : hour+':';
			$(timer).html(h + m + ':' + s);	
		}
		
		if( 'decrease' == d.type ){
			var timerInterval = setInterval(decrease, 1000);
		}
		if( 'increase' == d.type ){
			var timerInterval = setInterval(increase, 1000);
		}
		
	})
	
})(jQuery);