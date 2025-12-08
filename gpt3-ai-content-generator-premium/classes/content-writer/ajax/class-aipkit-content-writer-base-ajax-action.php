<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/content-writer/ajax/class-aipkit-content-writer-base-ajax-action.php

namespace WPAICG\ContentWriter\Ajax;

use WPAICG\Dashboard\Ajax\BaseDashboardAjaxHandler;
use WPAICG\Chat\Storage\LogStorage;
use WPAICG\Core\AIPKit_AI_Caller;
use WPAICG\Vector\AIPKit_Vector_Store_Manager;
use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
* Base class for Content Writer AJAX actions.
* Initializes common dependencies like LogStorage, AICaller, and VectorStoreManager.
*/
abstract class AIPKit_Content_Writer_Base_Ajax_Action extends BaseDashboardAjaxHandler
{
    public $log_storage;
    public $ai_caller;
    public $vector_store_manager;

    public function __construct()
    {
        // Ensure LogStorage is available
        if (class_exists(\WPAICG\Chat\Storage\LogStorage::class)) {
            $this->log_storage = new LogStorage();
        }

        // Ensure AICaller is available
        if (class_exists(\WPAICG\Core\AIPKit_AI_Caller::class)) {
            $this->ai_caller = new AIPKit_AI_Caller();
        }

        // Ensure VectorStoreManager is available
        if (class_exists(\WPAICG\Vector\AIPKit_Vector_Store_Manager::class)) {
            $this->vector_store_manager = new AIPKit_Vector_Store_Manager();
        }
    }

    /**
    * Public getter for the ai_caller dependency.
    * @return AIPKit_AI_Caller|null
    */
    public function get_ai_caller(): ?AIPKit_AI_Caller
    {
        return $this->ai_caller;
    }

    /**
    * Public getter for the vector_store_manager dependency.
    * @return AIPKit_Vector_Store_Manager|null
    */
    public function get_vector_store_manager(): ?AIPKit_Vector_Store_Manager
    {
        return $this->vector_store_manager;
    }
}
