(function ($){
    $(document).ready( function () {

    	$('.berocket_label_url').on( "change", function() {
    		var self = $(this),
    			pattern = new RegExp('^(https?:\\/\\/)?'+ // protocol
            '((([a-z\\d]([a-z\\d-]*[a-z\\d])*)\\.?)+[a-z]{2,}|'+ // domain name
            '((\\d{1,3}\\.){3}\\d{1,3}))'+ // ip (v4) address
            '(\\:\\d+)?(\\/[-a-z\\d%_.~+]*)*'+ //port
            '(\\?[;&amp;a-z\\d%_.~+=-]*)?'+ // query string
            '(\\#[-a-z\\d_]*)?$','i'),
          elements_to_block = $('.br_alabel_settings a, [type=submit]'), 
          url = self.val(); 

  			if ( url.length == 0 || pattern.test( self.val() ) ) {
  				self.removeClass('br_invalid');
	    		elements_to_block.removeClass('br_non_clickable');
  			} else {
  				self.addClass('br_invalid');
	    		elements_to_block.addClass('br_non_clickable');
  			}

    	});

    	$('input[name="br_labels[label_link]"]').on( "keyup change input", function() {
  			$('[name="br_labels[label_target]"]').prop( "disabled", $(this).val().length == 0 );
    	}).trigger('keyup');

  	});
})(jQuery);
