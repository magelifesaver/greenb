/**
 * File: assets/js/cfc-index-manage.js
 * Purpose: Handle Reindex / Sync / Clear actions on the Live Search Index page.
 */
(function($){
    'use strict';

    $(document).ready(function(){

        var $msg      = $('#cfc-ls-index-msg');
        var $btnReidx = $('#cfc-ls-reindex');
        var $btnSync  = $('#cfc-ls-sync');
        var $btnClear = $('#cfc-ls-clear');

        function showMessage(text, isError){
            if (!$msg.length) {
                return;
            }
            $msg.text(text);
            if (isError) {
                $msg.removeClass('updated').addClass('error');
            } else {
                $msg.removeClass('error').addClass('updated');
            }
        }

        function disableButtons(disabled){
            if ($btnReidx.length) { $btnReidx.prop('disabled', disabled); }
            if ($btnSync.length)  { $btnSync.prop('disabled',  disabled); }
            if ($btnClear.length) { $btnClear.prop('disabled', disabled); }
        }

        function doPost(endpoint, successMsg){
            disableButtons(true);
            showMessage('', false);

            $.ajax({
                url: CFC_LS_Settings.api_base + endpoint,
                method: 'POST',
                beforeSend: function(xhr){
                    xhr.setRequestHeader('X-WP-Nonce', CFC_LS_Settings.api_nonce);
                }
            }).done(function(resp){
                if (resp && typeof resp.count !== 'undefined') {
                    showMessage(successMsg + ' ' + resp.count + ' entries.', false);
                } else {
                    showMessage(successMsg, false);
                }
            }).fail(function(jqXHR){
                var msg = 'Request failed.';
                if (jqXHR.responseJSON && jqXHR.responseJSON.message) {
                    msg = jqXHR.responseJSON.message;
                }
                showMessage(msg, true);
            }).always(function(){
                disableButtons(false);
            });
        }

        if ($btnReidx.length) {
            $btnReidx.on('click', function(e){
                e.preventDefault();
                // REST route: /wp-json/ls/v1/index/build
                doPost('index/build', 'Index rebuilt.');
            });
        }

        if ($btnSync.length) {
            $btnSync.on('click', function(e){
                e.preventDefault();
                // REST route: /wp-json/ls/v1/index/sync
                doPost('index/sync', 'Index synced.');
            });
        }

        if ($btnClear.length) {
            $btnClear.on('click', function(e){
                e.preventDefault();
                if (!window.confirm('Clear the index table? This cannot be undone.')) {
                    return;
                }
                // REST route: /wp-json/ls/v1/index/clear
                doPost('index/clear', 'Index cleared.');
            });
        }

    });

})(jQuery);
