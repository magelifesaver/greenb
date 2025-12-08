(function ($){
    $(document).ready( function () {
        $(document).trigger('br-update-label');
  	});

    $(document).on( 'br-remove-old-labels', '.berocket_better_labels', function () {
        $('.br_timer_container').remove();
    });

    $(document).on( 'br-update-product', '.berocket_better_labels', function () {
        var labels_parent = $(this).parent();
        let timers = labels_parent.find('.br_timer_container');

        if( timers.length ) {
            let timer_parent = timers.parents('.br_timer_hook');
            if( timer_parent.length ) {
                timer_parent.first().append(timers);
            }
        }
        $(document).trigger('br-update-label');
    });

    function br_timer_ajax() {
        var products = [];
        $('.br_timer_ajax_load').each(function() {
            products.push({product: $(this).data('productid'), label: $(this).data('labelid')});
        });
        if( products.length ) {
            $.ajax({
                type: 'POST',
                url: brlabelsHelper.ajax_url,
                dataType: 'json',
                data: {
                    'action': 'br_timer_label_data',
                    'products': products
                },
                success: function(result){
                    if( Array.isArray(result) ) {
                        result.forEach(function(element) {
                            var timer = $('.br_label_timer[data-productid="'+element.id+'"][data-labelid="'+element.label+'"]'),
                            seconds_element = timer.find('.br_label_timer_seconds .br_label_timer_item_value'),
                            minutes_element = timer.find('.br_label_timer_minutes .br_label_timer_item_value'),
                            hours_element   = timer.find('.br_label_timer_hours .br_label_timer_item_value'),
                            days_element    = timer.find('.br_label_timer_days .br_label_timer_item_value');
                            seconds_element.text(element.data.seconds);
                            minutes_element.text(element.data.minutes);
                            hours_element.text(element.data.hours);
                            days_element.text(element.data.days);
                        });
                    }
                }
            });
        }
    }

    $(document).on( 'br-update-label bapl_new_label', function (event) {
        br_timer_ajax();
        $('.br_label_timer').not('.br_timer_ready').each(function() {
            var 
                timer = $(this),
                seconds_element = timer.find('.br_label_timer_seconds .br_label_timer_item_value'),
                minutes_element = timer.find('.br_label_timer_minutes .br_label_timer_item_value'),
                hours_element   = timer.find('.br_label_timer_hours .br_label_timer_item_value'),
                days_element    = timer.find('.br_label_timer_days .br_label_timer_item_value'),

                use_leading_zeros = timer.data('leading-zeros');

            timer.addClass('br_timer_ready');
            setInterval(function () {
                var 
                    seconds = parseInt( seconds_element.text() ),
                    minutes = parseInt( minutes_element.text() ),
                    hours   = parseInt( hours_element.text() ),
                    days    = parseInt( days_element.text() );
                if ( seconds >= 0 ) {
                    seconds -= 1;
                    if ( seconds < 0 ) {
                        minutes -= 1;
                        seconds = 59;
                        if ( minutes < 0 ) {
                            hours -= 1;
                            minutes = 59;
                            if ( hours < 0 ) {
                                days -= 1;
                                hours = 23;
                            }
                        }
                    }
                }

                if ( use_leading_zeros ) {
                    if ( seconds.toString().length == 1 ) {
                        seconds = '0' + seconds;
                    }
                    if ( minutes.toString().length == 1 ) {
                        minutes = '0' + minutes;
                    }
                    if ( hours.toString().length == 1 ) {
                        hours = '0' + hours;
                    }
                    if ( days.toString().length == 1 ) {
                        days = '0' + days;
                    }
                } 

                seconds_element.text(seconds);
                minutes_element.text(minutes);
                hours_element.text(hours);
                if ( typeof timer.attr('class') !== 'undefined' && timer.attr('class').includes('compact') && ( days == 0 || days == '00' ) ) {
                    days_element.hide();    
                } else {
                    days_element.text(days);
                }
            }, 1000);
        });
    });
})(jQuery);
