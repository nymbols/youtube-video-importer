
/**
 * accordion
 */
;(function($){
	
	$(document).ready(function(){
		// tabs
		$("div.tab").click(function(){
		if($(this).hasClass("active")!==true)
		{
			$('.tab').removeClass("active");
			$(this).addClass("active");
			id=$(this).attr("id");
			$('.content').hide();
			$("."+id).slideDown();
		}
		});

		
	});
	
})(jQuery);