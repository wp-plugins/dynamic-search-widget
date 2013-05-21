jQuery(document).ready(function($) {
	var options = dynsw_script;
	var ajaxurl = options['ajaxurl'];
	
	if ( ! $(".dynsw-search").length ) {
		$(".widget_dynsw").css('display', 'none');
	}
	$.each(options['search_fields'], function(i, value) {
		var search_field = value;
		$(search_field).keyup(function() {
			if ( $(search_field).val().length > 1 ) {
				delay(function(){
					$(".widget_dynsw").slideDown("fast");
					$(".dynsw-loader").css('display', 'inline');
					$.dynsw_post_request( 'dynsw_search_similars', $(search_field).val());
				}, 500 );			
			} 
		});
	});
	$.dynsw_post_request = function( action, data ) {
		var thisData = {
				action: action,				
				data: data
			};
		$.ajax({
		    url: ajaxurl,
		    type: 'POST',
		    data: thisData,
		    success: function (response) {				
		    	$(".dynsw-loader").css('display', 'none');
				$(".dynsw-results").html( response );
		    }
		});
	}
	var delay = ( function() {
		var timer = 0;
		return function(callback, ms){
			clearTimeout (timer);
			timer = setTimeout(callback, ms);
		};
	})();
});