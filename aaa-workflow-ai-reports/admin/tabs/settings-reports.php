<?php
/**
 * ============================================================================
 * File Path: /aaa-workflow-ai-reports/admin/tabs/settings-reports.php
 * Description: Tab 3 — AI Reports dashboard with advanced debug console.
 *              This version displays both the AI summary and the raw REST
 *              data returned from LokeyReports (including top products).
 * Dependencies: admin.js, lokey-client.php, openai-client.php
 * File Version: 1.3.0
 * Updated: 2025-12-28
 * Author: AAA Workflow DevOps
 * ============================================================================
 */
if ( ! defined( 'ABSPATH' ) ) exit;

// Optional success notice
if ( isset( $_GET['ai_updated'] ) ) {
    echo '<div class="notice notice-success is-dismissible"><p>AI report generated successfully.</p></div>';
}
?>
<h2>AI Reports Dashboard</h2>
<p>Fetch WooCommerce sales data, analyze with OpenAI, and view live debug logs for every REST call.</p>
<div id="aaa-wf-ai-reports-ui" style="margin-top:20px;">
    <p>
        <label for="aaa-wf-ai-from">From:</label>
        <input type="date" id="aaa-wf-ai-from" value="<?php echo esc_attr( date('Y-m-d', strtotime('-7 days')) ); ?>">
        <label for="aaa-wf-ai-to">To:</label>
        <input type="date" id="aaa-wf-ai-to" value="<?php echo esc_attr( date('Y-m-d') ); ?>">
        <button id="aaa-wf-ai-fetch" class="button button-primary">Generate Report</button>
    </p>
    <!-- AI Summary Output -->
    <h3>AI Summary</h3>
    <pre id="aaa-wf-ai-summary-output" style="background:#fff; padding:10px; min-height:100px; overflow:auto; border:1px solid #ccd0d4;"></pre>
    <!-- Raw Data Output -->
    <h3>Raw Data</h3>
    <pre id="aaa-wf-ai-raw-output" style="background:#fff; padding:10px; min-height:100px; overflow:auto; border:1px solid #ccd0d4;"></pre>
    <!-- 🔍 Advanced Debug Panel -->
    <div id="aaa-wf-ai-debug-panel" style="margin-top:25px; background:#f9f9f9; border:1px solid #ccd0d4; border-radius:6px; padding:10px;">
        <h3 style="margin-top:0;">
            Debug Console
            <button id="aaa-wf-ai-clear-debug" class="button button-secondary" style="float:right;">Clear Log</button>
        </h3>
        <div id="aaa-wf-ai-debug-entries" style="max-height:300px; overflow-y:auto; font-family:monospace; font-size:13px; line-height:1.4;"></div>
    </div>
</div>
<style>
.aaa-debug-entry {
    border-bottom:1px solid #e1e1e1;
    padding:6px 0;
}
.aaa-debug-entry h4 {
    margin:0; font-size:13px; color:#0073aa; cursor:pointer;
}
.aaa-debug-entry pre {
    display:none; background:#f6f8fa; padding:8px; border-radius:4px; margin-top:4px;
    overflow-x:auto; white-space:pre-wrap;
}
.aaa-debug-entry.open pre { display:block; }
</style>
<?php aaa_wf_ai_debug('Rendered AI Reports tab with advanced debug console and raw output.', basename(__FILE__)); ?>