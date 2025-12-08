;(function($){
    "use strict";

    // Handle clicking “Add Note” in the expanded card
    $(document).on('click', '.aaa-add-admin-note', function(e){
        e.preventDefault();
        const $btn     = $(this);
        const orderId  = $btn.data('order-id');
        const $entry   = $btn.siblings('textarea[name="new_admin_note"]');
        const note     = $entry.val().trim();
        if (!note) {
            alert('Please enter a note before saving.');
            return;
        }

        console.log('[NOTE] Adding new admin note for order', orderId, note);

        // Send only the new note and order_id
        $.post( AAA_OC_Payment.ajaxUrl, {
            action:          'aaa_oc_update_payment_index',
            order_id:        orderId,
            new_admin_note:  note
        }, function(response){
            if (!response.success) {
                console.error('[NOTE] Error saving note:', response.data);
                return alert('Error saving note: ' + response.data);
            }

            // Append the note to the display block
            const timestamp = new Date().toISOString().slice(0,19).replace('T',' ');
            const entryHtml = '<div>[' + timestamp + '] ' + $('<div/>').text(note).html() + '</div>';
            let $notes = $btn
                .closest('.aaa-payment-wrapper')
                .find('.aaa-admin-notes__content');
            
            // If notes container doesn’t exist yet, create it
            if (!$notes.length) {
                const wrapper = $(
                  '<div class="aaa-admin-notes" style="margin-top:1rem; padding:.75rem; border:1px solid #ddd; background:#f9f9f9;">' +
                    '<h4 class="aaa-admin-notes__heading">Admin Notes</h4>' +
                    '<div class="aaa-admin-notes__content"></div>' +
                  '</div>'
                );
                wrapper.insertBefore( $btn.closest('.aaa-admin-note-entry') );
                $notes = wrapper.find('.aaa-admin-notes__content');
            }

            $notes.append( entryHtml );
            $entry.val('');  // clear input
            console.log('[NOTE] Note appended to UI');
        }).fail(function(xhr, status, err){
            console.error('[NOTE] AJAX failure:', status, err);
            alert('Failed to save note.');
        });
    });

})(jQuery);
