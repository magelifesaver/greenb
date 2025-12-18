<?php
// File: classes/core/token-manager/AIPKit_Token_Manager.php

namespace WPAICG\Core\TokenManager; // New Namespace

use WPAICG\Core\TokenManager\Constants\CronHookConstant; // For CRON_HOOK
use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Load method logic files (these will define functions in their respective sub-namespaces)
$base_path = __DIR__ . '/';
require_once $base_path . 'init/ConstructorLogic.php';
require_once $base_path . 'cron/ScheduleTokenResetEventLogic.php';
require_once $base_path . 'cron/UnscheduleTokenResetEventLogic.php';
require_once $base_path . 'reset/PerformTokenResetLogic.php';
require_once $base_path . 'reset/IsResetDueLogic.php';
require_once $base_path . 'check/CheckAndResetTokensLogic.php';
require_once $base_path . 'record/RecordTokenUsageLogic.php';

// Load constants
require_once $base_path . 'constants/CronHookConstant.php';
require_once $base_path . 'constants/MetaKeysConstants.php';
require_once $base_path . 'constants/GuestTableConstants.php';


/**
 * AIPKit_Token_Manager (New Facade/Entry Point)
 * Handles token usage tracking, limits, and resets for different modules.
 * Delegates logic to namespaced functions.
 */
class AIPKit_Token_Manager {

    // --- Properties for dependencies (injected by ConstructorLogic) ---
    private $guest_table_name;
    private $bot_storage;
    // --- End Properties ---

    public function __construct() {
        // Call the constructor logic from the init sub-namespace
        Init\ConstructorLogic($this);
    }

    // --- Public static methods for cron scheduling ---
    public static function schedule_token_reset_event() {
        Cron\ScheduleTokenResetEventLogic(CronHookConstant::CRON_HOOK);
    }

    public static function unschedule_token_reset_event() {
        Cron\UnscheduleTokenResetEventLogic(CronHookConstant::CRON_HOOK);
    }
    // --- End cron scheduling ---

    // --- Public method for performing token reset ---
    public function perform_token_reset() {
        Reset\PerformTokenResetLogic($this);
    }
    // --- End perform token reset ---

    // --- Public static method for checking reset due ---
    public static function is_reset_due(int $last_reset_timestamp, string $period): bool {
        return Reset\IsResetDueLogic($last_reset_timestamp, $period);
    }
    // --- End checking reset due ---

    // --- Public methods for token checking and recording ---
    public function check_and_reset_tokens(?int $user_id, ?string $session_id, ?int $context_id_or_bot_id, string $module_context = 'chat'): bool|WP_Error {
        return Check\CheckAndResetTokensLogic($this, $user_id, $session_id, $context_id_or_bot_id, $module_context);
    }

    public function record_token_usage(?int $user_id, ?string $session_id, ?int $context_id_or_bot_id, int $tokens_used, string $module_context = 'chat') {
        Record\RecordTokenUsageLogic($this, $user_id, $session_id, $context_id_or_bot_id, $tokens_used, $module_context);
    }
    // --- End token checking and recording ---


    // --- Getters for dependencies needed by logic functions (called via $this passed to them) ---
    public function get_guest_table_name(): string {
        return $this->guest_table_name;
    }

    public function get_bot_storage() { // Type hint can be added if BotStorage class is defined in a way it can be type-hinted here
        return $this->bot_storage;
    }
    // --- End Getters ---

    // --- Setters for dependencies (used by ConstructorLogic) ---
    public function set_guest_table_name(string $name): void {
        $this->guest_table_name = $name;
    }

    public function set_bot_storage($storage_instance): void { // Type hint can be added
        $this->bot_storage = $storage_instance;
    }
    // --- End Setters ---
}