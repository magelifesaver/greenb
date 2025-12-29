/**
 * ============================================================================
 * File Path: /aaa-workflow-ai-reports/assets/js/admin.js
 * Description: Handles report fetching, AI analysis, live debug console updates
 *              and populates separate summary and raw output panels.  This
 *              version no longer overwrites the raw data with the summary; it
 *              displays both side by side for greater transparency.
 * Dependencies: jQuery, AAA_WFAI global
 * File Version: 1.3.0
 * Updated: 2025-12-28
 * Author: AAA Workflow DevOps
 * ============================================================================
 */
jQuery(document).ready(function($) {
    // --- Helper: Append to Debug Console ---
    function addDebugEntry(title, data, type = 'info') {
        const panel = $('#aaa-wf-ai-debug-entries');
        if (!panel.length) return;
        const safe = $('<div class="aaa-debug-entry"></div>');
        const header = $('<h4></h4>').text(title).attr('data-type', type);
        const body = $('<pre></pre>').text(JSON.stringify(data, null, 2));
        safe.append(header).append(body);
        panel.prepend(safe);
        header.on('click', function() {
            safe.toggleClass('open');
        });
    }
    // --- Clear Debug Console ---
    $('#aaa-wf-ai-clear-debug').on('click', function() {
        $('#aaa-wf-ai-debug-entries').empty();
    });
    // --- Fetch & Analyze Report ---
    $('#aaa-wf-ai-fetch').on('click', function(e){
        e.preventDefault();
        const from = $('#aaa-wf-ai-from').val();
        const to   = $('#aaa-wf-ai-to').val();
        // Indicate loading in both panels
        $('#aaa-wf-ai-summary-output').text('Fetching and analyzing report...');
        $('#aaa-wf-ai-raw-output').text('');
        addDebugEntry('Report Triggered', { from, to }, 'trigger');
        // REST call to LokeyReports Summary
        const restUrl = `${AAA_WFAI.restUrl}sales/summary?from=${from}&to=${to}`;
        addDebugEntry('REST Request → LokeyReports', { url: restUrl, method: 'GET' }, 'request');
        $.ajax({
            url: restUrl,
            method: 'GET',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', AAA_WFAI.restNonce);
            },
            success: function(resp) {
                addDebugEntry('REST Response ← LokeyReports', resp, 'response');
                // Immediately populate raw output with the summary JSON (may be overwritten later)
                $('#aaa-wf-ai-raw-output').text(JSON.stringify(resp, null, 2));
                // AI analysis via AJAX
                $.post(AAA_WFAI.ajaxUrl, {
                    action: 'aaa_wf_ai_run_report',
                    nonce: AAA_WFAI.nonce,
                    data: JSON.stringify(resp)
                }, function(aiResp) {
                    addDebugEntry('AI Analysis Request', { from, to }, 'ai-request');
                    addDebugEntry('AI Analysis Response', aiResp, 'ai-response');
                    if (aiResp.success) {
                        $('#aaa-wf-ai-summary-output').text(aiResp.data.summary);
                        $('#aaa-wf-ai-raw-output').text(JSON.stringify(aiResp.data.raw, null, 2));
                    } else {
                        $('#aaa-wf-ai-summary-output').text('AI report failed.');
                    }
                });
            },
            error: function(xhr) {
                addDebugEntry('REST Error', {
                    status: xhr.status,
                    response: xhr.responseJSON || xhr.responseText
                }, 'error');
                $('#aaa-wf-ai-summary-output').text('❌ Error fetching report.');
            }
        });
    });
});