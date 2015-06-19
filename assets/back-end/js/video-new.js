/**
 * Add new video step 1 functionality
 */

;(function($){
	$(document).ready(function(){
		
		// toggle explanation
		$('#yti_explain').click(function(e){
			e.preventDefault();
			$('#yti_explain_output').toggle();
		})		
	})
})(jQuery);