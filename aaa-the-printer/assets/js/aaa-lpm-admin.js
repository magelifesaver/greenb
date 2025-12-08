jQuery(document).ready(function($) {

    // 1) Print to PrintNode
    $(document).on('click', '.aaa-lpm-print-btn', function(e) {
        e.preventDefault();
        let $btn      = $(this);
        let orderId   = $btn.data('order-id');
        let template  = $btn.data('template');
        let nonce     = $btn.data('nonce');

        if (!orderId || !template || !nonce) {
            alert('Missing required print parameters.');
            return;
        }

        $btn.prop('disabled', true).text('Printing...');

        $.post(aaaLpmAjax.ajaxurl, {
            action: 'aaa_lpm_manual_print',
            _wpnonce: nonce,
            order_id: orderId,
            template: template
        }).done(function(response) {
            $btn.prop('disabled', false).text('Print');
            if (!response.success) {
                alert('Print Error: ' + (response.data || 'Unknown error'));
            } else {
                alert('Print job sent successfully.');
            }
        })
        .fail(function(xhr, status, error) {
            $btn.prop('disabled', false).text('Print');
            alert('AJAX Error: ' + error);
        });
    });


    // 2) Preview HTML
    $(document).on('click', '.aaa-lpm-preview-html-btn', function(e) {
        e.preventDefault();
        let $btn      = $(this);
        let orderId   = $btn.data('order-id');
        let template  = $btn.data('template');
        let nonce     = $btn.data('nonce');

        if (!orderId || !template || !nonce) {
            alert('Missing required preview parameters.');
            return;
        }

        // We'll open a new tab pointing to our AJAX endpoint, passing parameters via GET.
        // e.g. wp-admin/admin-ajax.php?action=aaa_lpm_preview_html&order_id=XXX&template=YYY&_wpnonce=ZZZ
        let previewUrl = aaaLpmAjax.ajaxurl 
            + '?action=aaa_lpm_preview_html'
            + '&order_id=' + encodeURIComponent(orderId)
            + '&template=' + encodeURIComponent(template)
            + '&_wpnonce=' + encodeURIComponent(nonce);

        window.open(previewUrl, '_blank');
    });


    // 3) Preview PDF
    $(document).on('click', '.aaa-lpm-preview-pdf-btn', function(e) {
        e.preventDefault();
        let $btn      = $(this);
        let orderId   = $btn.data('order-id');
        let template  = $btn.data('template');
        let nonce     = $btn.data('nonce');

        if (!orderId || !template || !nonce) {
            alert('Missing required preview parameters.');
            return;
        }

        // Similar approach - open a new tab for the PDF.
        let previewUrl = aaaLpmAjax.ajaxurl
            + '?action=aaa_lpm_preview_pdf'
            + '&order_id=' + encodeURIComponent(orderId)
            + '&template=' + encodeURIComponent(template)
            + '&_wpnonce=' + encodeURIComponent(nonce);

        window.open(previewUrl, '_blank');
    });

});
