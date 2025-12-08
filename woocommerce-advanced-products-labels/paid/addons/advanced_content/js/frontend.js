(function ($){
    $(document).ready( function () {

        $(document).on( "click", '.br_alabel_linked', function(e) {
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();

            var label = $(this),
                win = window.open( label.data('link'), label.data('target') );
            win.focus();        
        });
  	});
})(jQuery);