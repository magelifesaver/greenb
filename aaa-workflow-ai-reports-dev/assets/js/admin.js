/**
 * ============================================================================
 * File Path: /aaa-workflow-ai-reports/assets/js/admin.js
 * Description: Handles report fetching, AI analysis, and live debug console updates.
 * Dependencies: jQuery, AAA_WFAI global
 * File Version: 1.2.0
 * Updated: 2025-12-02
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

		$('#aaa-wf-ai-output').text('Fetching and analyzing report...');
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
				$('#aaa-wf-ai-output').text(JSON.stringify(resp, null, 2));

				// AI analysis via AJAX
$.post(AAA_WFAI.ajaxUrl, {
	action: 'aaa_wf_ai_run_report',
	nonce: AAA_WFAI.nonce,
	data: JSON.stringify(resp) // 👈 send the full REST JSON, not just from/to
}, function(aiResp) {
					addDebugEntry('AI Analysis Request', { from, to }, 'ai-request');
					addDebugEntry('AI Analysis Response', aiResp, 'ai-response');
					if (aiResp.success) {
						$('#aaa-wf-ai-output').text(aiResp.data.summary);
					} else {
						$('#aaa-wf-ai-output').text('AI report failed.');
					}
				});
			},
			error: function(xhr) {
				addDebugEntry('REST Error', {
					status: xhr.status,
					response: xhr.responseJSON || xhr.responseText
				}, 'error');
				$('#aaa-wf-ai-output').text('❌ Error fetching report.');
			}
		});
	});
});
