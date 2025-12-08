<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/admin/ajax/migration/class-aipkit-analyze-old-data-action.php
// Status: MODIFIED

namespace WPAICG\Admin\Ajax\Migration;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Load the main logic for this action.
require_once __DIR__ . '/analysis/handle-analysis-request.php';

/**
 * Handles the AJAX action for analyzing old data.
 * This class now acts as a simple orchestrator, delegating the main logic.
 */
class AIPKit_Analyze_Old_Data_Action extends AIPKit_Migration_Base_Ajax_Action
{
    /**
     * Handles the AJAX request by calling the externalized logic function.
     */
    public function handle_request()
    {
        // The namespaced function will handle permissions, logic, and response sending.
        \WPAICG\Admin\Ajax\Migration\Analysis\handle_analysis_request_logic($this);
    }
}