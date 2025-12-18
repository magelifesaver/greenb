jQuery(document).ready(function($) {

    // Handler for Print buttons
    $(document).on('click', '.aaa-lpm-print-btn', function(e){
        e.preventDefault();
        e.stopPropagation();
        let $btn = $(this);
        let originalText = $btn.text();
        let orderId = $btn.data('order-id');
        let template = $btn.data('template');
        let nonce = $btn.data('nonce');
        // Use .attr() to get the printer parameter
        let printer = $btn.attr('data-printer') || 'dispatch';
        console.log("Board Print - Printer parameter: " + printer);
       
        $btn.prop('disabled', true).text('Printing...');
       
        $.post(AAA_OC_Vars.ajaxUrl, {
            action: 'aaa_lpm_manual_print',
            _wpnonce: nonce,
            order_id: orderId,
            template: template,
            printer: printer
        })
        .done(function(response){
            $btn.prop('disabled', false).text(originalText);
            if (!response.success) {
                alert('Print error: ' + (response.data || 'Unknown error'));
            }
        })
        .fail(function(xhr, status, error){
            $btn.prop('disabled', false).text(originalText);
            alert('AJAX error: ' + error);
        });
    });

    // Handler for HTML Preview buttons
    $(document).on('click', '.aaa-lpm-preview-html-btn', function(e){
        e.preventDefault();
        e.stopPropagation();
        let $btn = $(this);
        let orderId = $btn.data('order-id');
        let template = $btn.data('template');
        let nonce = $btn.data('nonce');
        if (!orderId || !template || !nonce) {
            alert('Missing required preview parameters.');
            return;
        }
        let previewUrl = AAA_OC_Vars.ajaxUrl
            + '?action=aaa_lpm_preview_html'
            + '&order_id=' + encodeURIComponent(orderId)
            + '&template=' + encodeURIComponent(template)
            + '&_wpnonce=' + encodeURIComponent(nonce);
        window.open(previewUrl, '_blank');
    });

    // Handler for PDF Preview buttons
    $(document).on('click', '.aaa-lpm-preview-pdf-btn', function(e){
        e.preventDefault();
        e.stopPropagation();
        let $btn = $(this);
        let orderId = $btn.data('order-id');
        let template = $btn.data('template');
        let nonce = $btn.data('nonce');
        if (!orderId || !template || !nonce) {
            alert('Missing required preview parameters.');
            return;
        }
        let previewUrl = AAA_OC_Vars.ajaxUrl
            + '?action=aaa_lpm_preview_pdf'
            + '&order_id=' + encodeURIComponent(orderId)
            + '&template=' + encodeURIComponent(template)
            + '&_wpnonce=' + encodeURIComponent(nonce);
        window.open(previewUrl, '_blank');
    });

});
