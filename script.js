jQuery(document).ready(function($) {
	var options = dynsw_script;
	var ajaxurl = options['ajaxurl'];
	
	if ( ! $(".dynsw-search").length ) {
		$(".widget_dynsw").css('display', 'none');
	}

	var search_fields = options['search_fields'];

	$.each(search_fields, function(i, value) {
		var search_term = new Array(2);
		$(value).keyup(function() {				
			if ( search_term[i] != $.trim($(this).val())) {
				search_term[i] = $.trim($(this).val());
				$(search_fields[1]).val( search_term[i] );
				if ( search_term[i].length > 2 ) {					
					delay(function(){						
			    		$(".widget_dynsw").slideDown("fast");
						$(".dynsw-loader").css('display', 'inline');
						$.dynsw_post_request( 'dynsw_search_similars', search_term[i]);
			    	}, 500 );
				}
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