jQuery(document).ready(function($) {
	//init();

	var is_shortcode_delivery = tpfwcheckout.foodonline_delivery_shortcode;
	jQuery(document).on('tpfw_loaded_blocks',function(){
		init_events();

	});
	init_events();

	function init() {
		try {
			
			
			var stop, date, minTime;
			var extra = 0;
			if (tpfwcheckout.is_rollover == 0) {
				stop = (tpfwcheckout.todayclose)[0];
				date = (tpfwcheckout.actual_date)[0];
				minTime = (tpfwcheckout.todayopen)[0];
			} else {
				if(tpfwcheckout.is_rollover in tpfwcheckout.actual_date == false ){throw new Error("This was an error");}
				stop = tpfwcheckout.is_rollover in tpfwcheckout.todayclose ? (tpfwcheckout.todayclose)[tpfwcheckout.is_rollover] : 0;
				date = tpfwcheckout.is_rollover in tpfwcheckout.actual_date ? (tpfwcheckout.actual_date)[tpfwcheckout.is_rollover] : null;
				minTime = tpfwcheckout.is_rollover in tpfwcheckout.todayopen ? (tpfwcheckout.todayopen)[tpfwcheckout.is_rollover] : 0 ;
			}
			var disableRange = [];
			if (minTime === 0) {
				disableRange.push(['00:00', '23:59']);
			}
			if ((tpfwcheckout.full_slots).length) {
				_.each(tpfwcheckout.full_slots, function(element, i, list) {
					if (date == element.date) {
						disableRange.push([element.time, element.time_end]);
					}
				}, this);
			}
			if ((tpfwcheckout.disable_array).length) {
				_.each(tpfwcheckout.disable_array, function(element, i, list) {
					if (date == element.date) {
						disableRange.push([element.start, element.stop]);
					}
				}, this);
			}
			var args = {
				'scrollDefault': 'now',
				'minTime': minTime,
				'maxTime': stop,
				'step': tpfwcheckout.step,
				'timeFormat': tpfwcheckout.time_format,
				'disableTimeRanges': disableRange,
				'extra': 0,
				'showDuration': tpfwcheckout.showDuration == 1 ? true : false,
				'listWidth': 1,
				'secondsfromMidnight': parseInt(tpfwcheckout.secondsfromMidnight),
			};
			if (tpfwcheckout.show_asap == 1 && tpfwcheckout.is_open == 1 && tpfwcheckout.is_rollover == 0) {
				args.noneOption = [{
					'label': tpfwcheckout.asap_text,
					'value': tpfwcheckout.asap_text,
					'className': 'tpfw-asap'
				}, ];
			}
			$('#tpfw-time').timepicker(args);
			if (tpfwcheckout.show_asap == 1 && tpfwcheckout.is_open == 1) {
				$('#tpfw-time').on('showTimepicker', function() {
					check_if_asap();
				});
			}
			if (tpfwcheckout.preselect_time == 1 && $('#tpfw-date').find('option:selected').val() == $('#tpfw-date').find('option').first().val()) {
				$('#tpfw-time').timepicker('show');
				var first = $('.ui-timepicker-wrapper').last().find('.ui-timepicker-list').find('li').not('.ui-timepicker-disabled').first();
				if (first.length) {
					first.trigger('click');
					first.trigger('click');
				} else {
					$('#tpfw-time').timepicker('hide');
				}
			}
			$('#tpfw-date').on('change', function() {
				update_form(this, extra, false, false, false);
			});
			$('#tpfw-time').on('showTimepicker', function() {
				remove_disabled_times();
			});
			var timer;
			clearInterval(timer);
			timer = setInterval(function() {
				extra = extra + 120;
				var ulList = $('div.ui-timepicker-wrapper').last().find('ul.ui-timepicker-list');
				if ($('#tpfw-date').find('option:selected').val() == $('#tpfw-date').find('option').first().val()) {
					let first_text = ulList.find('li').not('.ui-timepicker-disabled').first().text();
					let selectedisfirst = !(_.isUndefined(first_text) || _.isNull(first_text) || first_text.trim().length === 0) && ulList.find('li').not('.ui-timepicker-disabled').first().text() == ulList.find('li').filter('.ui-timepicker-selected').text();
					let selected = ulList.find('li').filter('.ui-timepicker-selected').first();
					let time = selected.length ? selected.data('time') : false;
					update_form($('#tpfw-date'), extra, true, time, selectedisfirst);
				}
			}, 120000);
			setTimeout(function() {
				clearInterval(timer);
				window.location.reload();
			}, 60000 * 15);
			$('select#tpfw-date option[value=' + date + ']').prop('selected', true);
			if (jQuery('select#tpfw-date').val() === date) {
				$('select#tpfw-date option[value=' + date + ']').prevAll().prop('disabled', true);
			}
			//Disable days that never opens
			var disabledates = [];
			_.each(tpfwcheckout.disable_array, function(element, i, list) {
				if (element.start == "00:00" && element.stop == "23:59") {
					disabledates.push(element.date);
				}
			});
			_.each($("select#tpfw-date option"), function(element, i, list) {
				var haschanged = false;
				$(element).find('.tpfw-date-closed').remove();
				if (0 === tpfwcheckout.todayopen[i]) {
					$(element).prop('disabled', true);
					if (!$(element).find('span').hasClass('tpfw-date-closed')) {
						$(element).append('<span class="tpfw-date-closed"> (' + tpfwcheckout.closed_text + ')</span>');
					}
					if (!haschanged && $(element).is(':selected')) {
						$("select#tpfw-date option").not(':selected').not(':disabled').first().prop('selected', true);
						$("select#tpfw-date").trigger('change');
						haschanged = true;
					}
				} else if (_.contains(disabledates, $(element).val())) {
					$(element).prop('disabled', true);
					if (!$(element).find('span').hasClass('tpfw-date-closed')) {
						$(element).append('<span class="tpfw-date-closed"> (' + tpfwcheckout.closed_text + ')</span>');
					}
					if (!haschanged && $(element).is(':selected')) {
						$("select#tpfw-date option").not(':selected').not(':disabled').first().prop('selected', true);
						$("select#tpfw-date").trigger('change');
						haschanged = true;
					}
				}
			});
		} catch (err) {
			let args = {
				'scrollDefault': 'now',
				'minTime': '00:00am',
				'maxTime': '11:58pm',
				'step': tpfwcheckout.step,
				'disableTimeRanges': [
					['00:00am', '11:59pm'],
				],
				'extra': 0,
				'time_to_add': tpfwcheckout.time_to_add,
				'showDuration': tpfwcheckout.showDuration == 1 ? true : false,
				'listWidth': 1,
				'secondsfromMidnight': parseInt(tpfwcheckout.secondsfromMidnight),
			};
			$('#tpfw-time').timepicker(args);
		}
	}

	function check_if_asap() {
		var wrapper = $('div.ui-timepicker-wrapper').last();
		if ($('#tpfw-date').find('option:selected').val() != $('#tpfw-date').find('option').first().val()) {
			wrapper.find('ul.ui-timepicker-list').find('li.tpfw-asap').remove();
		}
		if ($('#tpfw-date').find('option:selected').val() == $('#tpfw-date').find('option').first().val()) {
			var first = wrapper.find('ul.ui-timepicker-list').find('li').not('.ui-timepicker-disabled').not('.tpfw-asap').first();
			if (first.length) {} else {
				wrapper.find('ul.ui-timepicker-list').find('li.tpfw-asap').remove();
			}
		}
	}

	function remove_disabled_times() {
		$('div.ui-timepicker-wrapper').last().find('ul.ui-timepicker-list').find('li.ui-timepicker-disabled').remove();
	}

	function update_form($this, extra, fromtimeout, time, selectedisfirst) {
		var extra2 = 0;
		$('#tpfw-time').val('');
		if ($('#tpfw-date').find('option:selected').val() == $('#tpfw-date').find('option').first().val()) {
			stop = (tpfwcheckout.todayclose)[0];
			date = (tpfwcheckout.actual_date)[0];
			minTime = (tpfwcheckout.todayopen)[0];
			extra2 = extra;
		} else if (tpfwcheckout.is_rollover == 1 && $('#tpfw-date').find('option:selected').val() == $("#tpfw-date option:nth-of-type(2)").val()) {
			stop = (tpfwcheckout.todayclose)[1];
			date = (tpfwcheckout.actual_date)[1];
			minTime = (tpfwcheckout.todayopen)[1];
			extra2 = extra;
		} else {
			let index = $("option:selected", $this).index();
			stop = (tpfwcheckout.todayclose)[index];
			date = (tpfwcheckout.actual_date)[index];
			minTime = (tpfwcheckout.todayopen)[index];
			extra2 = 0;
		}
		var disableRange = [];
		if (minTime === 0) {
			disableRange.push(['00:00', '23:59']);
		}
		if ((tpfwcheckout.full_slots).length) {
			_.each(tpfwcheckout.full_slots, function(element, i, list) {
				if (date == element.date) {
					disableRange.push([element.time, element.time_end]);
				}
			}, this);
		}
		if ((tpfwcheckout.disable_array).length) {
			_.each(tpfwcheckout.disable_array, function(element, i, list) {
				if (date == element.date) {
					disableRange.push([element.start, element.stop]);
				}
			}, this);
		}
		let args = {
			'scrollDefault': 'now',
			'minTime': minTime,
			'maxTime': stop,
			'step': tpfwcheckout.step,
			'timeFormat': tpfwcheckout.time_format,
			'disableTimeRanges': disableRange,
			'showDuration': tpfwcheckout.showDuration == 1 ? true : false,
			'extra': extra2,
			'time_to_add': tpfwcheckout.time_to_add,
			'listWidth': 1,
			'secondsfromMidnight': parseInt(tpfwcheckout.secondsfromMidnight),
		};
		//	alert(JSON.stringify(args));
		if (tpfwcheckout.show_asap == 1 && tpfwcheckout.is_open == 1) {
			args.noneOption = [{
				'label': tpfwcheckout.asap_text,
				'value': tpfwcheckout.asap_text,
				'className': 'tpfw-asap'
			}, ];
		}
		$('#tpfw-time').timepicker('remove');
		$('#tpfw-time').timepicker(args);
		if (fromtimeout && !selectedisfirst && time !== false) {
			
			$('#tpfw-time').timepicker('show');
			let wrapper = $('div.ui-timepicker-wrapper').last();
			var trythisone = wrapper.find('ul.ui-timepicker-list').find('li').filter("[data-time='" + time + "']").not('.ui-timepicker-disabled').first();
			if (trythisone.length) {
				$('input#tpfw-time').val(trythisone.text());
				trythisone.trigger('click');
				trythisone.trigger('click');
			} else {
				wrapper.find('ul.ui-timepicker-list').find('li').removeClass('ui-timepicker-selected');
				$('#tpfw-time').timepicker('hide');
			}
		} else if ((fromtimeout && selectedisfirst) ||
			(!fromtimeout && tpfwcheckout.preselect_time == 1 && $('#tpfw-date').find('option:selected').val() == $('#tpfw-date').find('option').first().val())
		) {
			
			$('#tpfw-time').timepicker('show');
			let wrapper = $('div.ui-timepicker-wrapper').last();
			var first = wrapper.find('ul.ui-timepicker-list').find('li').not('.ui-timepicker-disabled').first();
			if (first.length) {
				$('#tpfw-time').val(first.text());
				first.trigger('click');
				first.trigger('click');
				
			} else {
				$('#tpfw-time').timepicker('hide');
			}
		}
	}

	function init_events() {
		if ($('#tpfw-pickup-location').length) {
			$('#tpfw-time').one('click.tpfw-time', function() {
				var val = $('#tpfw-pickup-location').find('option:selected').val();
				if (!val || val === "") {
					alert(tpfwcheckout.locationfirst_text);
					$('#tpfw-time').val('');
				}
			});
			$('#tpfw-date').one('change.tpfw-time', function() {
				var val = $('#tpfw-pickup-location').find('option:selected').val();
				if (!val || val === "") {
					alert(tpfwcheckout.locationfirst_text);
				}
			});
		} else {
			init();
		}
		
		if(tpfwcheckout.ajax_updates == 1){
		jQuery('body').on('updated_checkout',function(){
			if (is_shortcode_delivery){
			
			var data = {
			'action': 'tpfw_get_picktime_args',
			'nonce_ajax': tpfwdel.nonce,
			
		};
		
		$.post(
			woocommerce_params.ajax_url,
			data,
			function(response) {
				tpfwcheckout = response;
				init();
			}).done(function() {
			
		});
			
			}else{
				init();
			}
			});
	}
		
		
		$('#tpfw-pickup-location').on('change', function() {
			//$(this).find('option').first().prop('disabled', true);
			var data = {
				'action': 'tpfw_update_pickup_location',
				'location': $(this).val(),
				'nonce_ajax': tpfwdel.nonce,
			};
			var block = jQuery('#tpfw-date_field');
			block.addClass('processing').block({
				message: null,
				overlayCSS: {
					background: '#fff',
					opacity: 0.6
				}
			});
			$.post(
				woocommerce_params.ajax_url,
				data,
				function() {
					block.unblock();
					$("#tpfw-date option").each(function() {
						$(this).prop('selected', false);
						$(this).removeAttr('selected');
						$(this).prop('disabled', false);
					});
					
				}).done(function() {
				$('#tpfw-date').prop('selectedIndex', 0);
			}).always(function(response) {
				tpfwcheckout = response;
				init();
			});
		});
	}
});
