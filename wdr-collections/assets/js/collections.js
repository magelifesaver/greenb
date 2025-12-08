jQuery(function ($) {
    const alert_counter = {counts: 1};

    /**
     * Alert notification
     * @param message
     * @param type
     * @param alert_counter
     */
    function notify(message, type = "success", alert_counter = null) {

        switch (type) {
            case "error":
                var class_name = "wdr-alert-error";
                break;
            case "warning":
                var class_name = "wdr-alert-warning";
                break;
            default:
            case "success":
                var class_name = "wdr-alert-success";
                break;
        }

        let div_id = 'wdr-notify-msg-' + alert_counter.counts;
        let html = '<div style="display: none;" class="wdr-alert ' + class_name + '" id="' + div_id + '">' + message + '</div>';
        let notify_holder = $("#notify-msg-holder");
        notify_holder.append(html);
        let message_div = $("#" + div_id);
        var notify_count = alert_counter.counts;
        alert_counter.counts = parseInt(notify_count) + parseInt(1);
        message_div.fadeIn(500);
        setTimeout(
            function () {
                message_div.fadeOut(500);
                message_div.remove();
            }, 5000
        );
    }

    /**
     * To initially hide save and close buttons
     */
    if (wdr_data.rule_id == "view") {
        $("button.wdr_save_collection, button.wdr_save_close_collection").attr("disabled", false).removeClass("wdr_save_btn_disabled");
    } else {
        $("button.wdr_save_collection, button.wdr_save_close_collection").attr("disabled", true).addClass("wdr_save_btn_disabled");
    }

    $(document).on('change', '.wdr-filter-type', function() {
        $("button.wdr_save_collection, button.wdr_save_close_collection").attr("disabled", false).removeClass("wdr_save_btn_disabled");
    });

    /**
     * Save Collections using ajax
     */
    $('#wdr-save-collection').submit(function (e) {
        e.preventDefault();
        let validation = wdr_col_validation($(this));
        if (!validation) {
            return false;
        }
        let loader = $('.woo_discount_loader');
        var collection_id = $('input[name="edit_collection"]').val();
        if (collection_id) {
            var linked_rules = awdr_get_collection_linked_rules(collection_id);
            if (!$.isEmptyObject(linked_rules)) {
                var rules = '';
                $.each(linked_rules, function (id, title) {
                    rules += '\n#' + id + ': ' + title;
                });
                if (!confirm(wdr_col_admin.i18n.save_collection_linked + '\n' + rules)) {
                    return;
                }
            }
        }

        $.ajax({
            data: $(this).serialize(),
            type: 'post',
            url: ajaxurl,
            beforeSend: function () {
                loader.show();
            },
            complete: function () {
                loader.hide();
            },
            error: function (request, error) {
                notify(wdr_data.localization_data.error, 'error', alert_counter);
            },
            success: function (response) {
                var data = response.data;
                if (response.success) {
                    if (data.redirect) {
                        window.location.href = data.redirect;
                        notify(wdr_col_admin.i18n.save_collection, 'success', alert_counter);
                    } else {
                        $('.wdr_desc_text.coupon_error_msg').hide();
                        $(".coupon_name_msg").css("border", "");
                        notify(wdr_col_admin.i18n.save_collection, 'success', alert_counter);
                    }
                } else {
                    if (data.coupon_message) {
                        $(".coupon_name_msg").css("border", "1px solid #FF0000").focus();
                        notify(wdr_data.localization_data.coupon_exists, 'error', alert_counter);
                    }else{
                        for (const [key, value] of Object.entries(data)) {
                            if (data.hasOwnProperty(key)) {
                                value.forEach(function(message){
                                    notify(message, 'error',alert_counter);
                                });
                            }
                        }
                    }
                }
            }
        });
    });

    /**
     * Save and Close Button
     */
    $(document).on('click', '.wdr_save_close_collection', function () {
        $('input[name=wdr_save_close]').val('1');
        $(".wdr_save_collection").click();
    });

    /**
     * Initially load select2
     */
    make_wdr_col_select2_search($('.wdr_col_load_select2'));

    /**
     * Load select2 manually based on the event trigger
     */
    $('.add-product-filter').click(function () {
        make_wdr_col_select2_search($('.wdr-filter-group').find('.wdr_col_select2'));
    });

    $(document).on('change', '.wdr-product-filter-type', function () {
        make_wdr_col_select2_search($(this).closest('.wdr-filter-group').find('.wdr_col_select2'));
    });

    /**
     * Ajax search function
     *
     * @param $el
     */
    function make_wdr_col_select2_search($el) {
        $el.selectWoo({
            width: '100%',
            minimumInputLength: 1,
            placeholder: $el.data('placeholder'),
            escapeMarkup: function (text) {
                return text;
            },
            language: {
                noResults: function () {
                    return wdr_data.labels.select2_no_results;
                },
                errorLoading: function () {
                    return wdr_data.labels.searching_text;
                }
            },
            ajax: {
                url: ajaxurl,
                type: 'POST',
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return {
                        query: params.term,
                        action: 'wdr_col_ajax',
                        method: $el.data('list') || 'products',
                        awdr_nonce: $('input[name=wdr_ajax_select2]').val() || '',
                        selected: $el.val()
                    };
                },
                processResults: function (response) {
                    return {results: response.data || []};
                }
            }
        });
        $el.parent().find('.select2-search__field').css('width', '100%');
    }

    /**
     * Delete collection
     */
    $(document).on('click', '.wdr_delete_collection', function () {
        var wdr_delete_collection_row = $(this).closest('tr');
        if (confirm(wdr_col_admin.i18n.delete_collection_confirm)) {
            var rowid = $(this).data('delete-collection');
            var linked_rules = awdr_get_collection_linked_rules(rowid);
            if (!$.isEmptyObject(linked_rules)) {
                var rules = '';
                $.each(linked_rules, function (id, title) {
                    rules += '\n#' + id + ': ' + title;
                })
                alert(wdr_col_admin.i18n.delete_collection_linked + '\n' + rules);
            } else {
                let loader = $('.woo_discount_loader');
                $.ajax({
                    data: {
                        rowid: rowid,
                        awdr_nonce: $(this).data('awdr_nonce'),
                        method: 'delete_collection',
                        action: 'wdr_col_ajax'
                    },
                    type: 'post',
                    url: ajaxurl,
                    beforeSend: function () {
                        loader.show();
                    },
                    complete: function () {
                        loader.hide();
                    },
                    error: function (request, error) {
                        notify(wdr_data.localization_data.error, 'error', alert_counter);
                    },
                    success: function (data) {
                        if (data.status === 'failed') {
                            notify(wdr_data.localization_data.error, 'error', alert_counter);
                        } else {
                            notify(wdr_col_admin.i18n.deleted_collection, 'success', alert_counter);
                            wdr_delete_collection_row.hide(500, function () {
                                wdr_delete_collection_row.remove();
                            });
                        }
                    }
                });
            }
        }
    });

    /**
     * To get linked rule ids
     */
    function awdr_get_collection_linked_rules(collection_id) {
        var rules = {};
        $.ajax({
            data: {
                rowid: collection_id,
                awdr_nonce: $('#awdr_get_collection_linked_rules_nonce').val(),
                method: 'get_collection_linked_rules',
                action: 'wdr_col_ajax'
            },
            type: 'post',
            url: ajaxurl,
            async: false,
            error: function (request, error) {
                notify(wdr_data.localization_data.error, 'error', alert_counter);
            },
            success: function (data) {
                rules = data.linked_rules;
            }
        });
        return rules;
    }

    $(document).on('change', '.select_bxgy_type', function () {
        var adjustment_mode = $('input[name="buyx_gety_adjustments[mode]"]:checked').val();
        if ($(this).val() == 'bxgy_collection') {
            make_wdr_col_select2_search($('.awdr-discount-container .wdr_col_bxgy_select2'));
            $('.awdr-discount-content').html(wdr_col_admin.i18n.bxgy_collection_discount_content);
            $('.auto_add').hide();
            if (adjustment_mode === undefined || adjustment_mode == 'auto_add') {
                $("input[value='cheapest']").prop("checked", true);
            }
            $('.bxgy-icon').removeClass('awdr-bygy-all');
            $('.bxgy-icon').removeClass('awdr-bygy-cat-products');
            $('.bxgy-icon').addClass('awdr-bygy-col-products');
            $('.bxgy_product').hide();
            $('.bxgy_category').hide();
            $('.bxgy_collection').show();
            $('.bxgy_type_selected').show();
            $('.awdr-example').show();
        } else {
            $('.bxgy-icon').removeClass('awdr-bygy-col-products');
            $('.bxgy_collection').hide();
        }
    });
    $('.select_bxgy_type').trigger('change');

    $(document).on('click', '.add_discount_elements', function () {
        make_wdr_col_select2_search($('.awdr-discount-container .wdr_col_bxgy_select2'));
    });

    /**
     * Collection validation before save
     */
    function wdr_col_validation(form) {
        let wdr_filter_validations = [];
        wdr_filter_validations = wdr_filter_validation();
        if (wdr_filter_validations.indexOf("fails") !== -1) {
            return false;
        }
        return true;
    }
});