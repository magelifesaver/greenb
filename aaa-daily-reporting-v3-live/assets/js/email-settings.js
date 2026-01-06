/**
 * File: assets/js/email-settings.js
 * Path: aaa-daily-reporting/assets/js/email-settings.js
 * Description: AJAX handlers for Email Settings page (Send Now, Delete)
 * Version: 3.1.0
 */

(function($){
    // Script load confirmation
    console.log('[EmailSettings] script loaded');

    // Debug: form detection and submission logging
    var $saveForm = $('form[action*="aaa_save_email_config"]');
    if ( $saveForm.length ) {
        console.log('[EmailSettings] Save form found:', $saveForm);
        $saveForm.on('submit', function(e) {
            console.log('[EmailSettings] Save form data:', $saveForm.serialize());
        });
    } else {
        console.warn('[EmailSettings] Save form not found');
    }

    /**
     * Handle 'Send Now' button clicks
     */
    $(document).on('click', '.aaa-send-now-btn', function(e){
        e.preventDefault();
        var $btn = $(this);
        var idx  = $btn.data('index');
        console.log('[EmailSettings] Send Now clicked for index:', idx);

        // Disable button and show progress
        $btn.prop('disabled', true).text('Sending...');

        $.post(
            aaaEmailSettings.ajax_url,
            {
                action: 'aaa_send_email_now',
                index:  idx,
                nonce:  aaaEmailSettings.nonce
            }
        ).done(function(response){
            console.log('[EmailSettings] Send Now response:', response);
            if ( response.success ) {
                $btn.text('✅ Sent');
            } else {
                $btn.text('❌ Failed').prop('disabled', false);
                console.error('[EmailSettings] AJAX send error:', response.data);
            }
        }).fail(function(jqXHR, textStatus){
            $btn.text('❌ Error').prop('disabled', false);
            console.error('[EmailSettings] AJAX request failed:', textStatus, jqXHR);
        });
    });

    /**
     * Handle 'Delete' button clicks
     */
    $(document).on('click', '.aaa-delete-config-btn', function(e){
        e.preventDefault();
        var $btn = $(this);
        var idx  = $btn.data('index');
        console.log('[EmailSettings] Delete clicked for index:', idx);

        if ( ! confirm('Are you sure you want to delete this configuration?') ) {
            console.log('[EmailSettings] Delete cancelled by user for index:', idx);
            return;
        }

        $btn.prop('disabled', true).text('Deleting...');

        $.post(
            aaaEmailSettings.ajax_url,
            {
                action: 'aaa_delete_email_config',
                index:  idx,
                nonce:  aaaEmailSettings.nonce
            }
        ).done(function(response){
            console.log('[EmailSettings] Delete response:', response);
            if ( response.success ) {
                $btn.text('✅ Deleted');
                $btn.closest('tr').fadeOut(300, function(){ $(this).remove(); });
            } else {
                $btn.text('❌ Failed').prop('disabled', false);
                console.error('[EmailSettings] AJAX delete error:', response.data);
            }
        }).fail(function(jqXHR, textStatus){
            $btn.text('❌ Error').prop('disabled', false);
            console.error('[EmailSettings] AJAX request failed:', textStatus, jqXHR);
        });
    });

})(jQuery);
