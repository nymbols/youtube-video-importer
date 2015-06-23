/**
 * Add new video step 1 functionality
 */

;(function($){
	$(document).ready(function(){
		
		// toggle explanation
		$('#yvi_explain').click(function(e){
			e.preventDefault();
			$('#yvi_explain_output').toggle();
		})		
	})
})(jQuery);