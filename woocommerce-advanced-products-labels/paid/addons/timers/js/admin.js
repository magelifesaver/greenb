(function ($){
    $(document).ready( function () {
        $(document).on('change', '.br_timer_setting', function() {
            var self = $(this);
            $('.br_label_timer, .br_label_timer *').css( self.data('style'), self.val() );
        });

        $(document).on('change', '.br_timer_margin', function() {
            var 
                self = $(this),
                type = $('[name="br_labels[timer_template]"]:checked').val().split('-')[0];
            $('.br_label_timer').css( self.data(type + '-style'), self.val() + $('.br_timer_margin_units').val() );
        });

        $(document).on('change', '.br_timer_margin_units', function() {
            var 
                self = $(this),
                type = $('[name="br_labels[timer_template]"]:checked').val().split('-')[0];

            $('.br_timer_margin').each(function() {
                var margin = $(this);
                $('.br_label_timer').css( margin.data(type + '-style'), margin.val() + self.val() );
            }); 
        });
	});
})(jQuery);
