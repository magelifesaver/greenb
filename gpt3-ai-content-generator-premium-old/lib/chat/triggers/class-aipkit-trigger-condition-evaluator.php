<?php

namespace WPAICG\Lib\Chat\Triggers; // UPDATED Namespace

// --- MODIFIED: Use new Condition component classes ---
use WPAICG\Lib\Chat\Triggers\Conditions\AIPKit_Condition_Runner;
use WPAICG\Lib\Chat\Triggers\Conditions\AIPKit_Condition_Value_Resolver;
use WPAICG\Lib\Chat\Triggers\Conditions\AIPKit_Condition_Comparator;
// --- END MODIFICATION ---
use WP_Error; // Kept for documentation, though this facade doesn't directly return WP_Error

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * AIPKit_Trigger_Condition_Evaluator (Facade)
 *
 * Evaluates a set of conditions against provided context data by delegating
 * to specialized condition runner, resolver, and comparator components.
 */
class AIPKit_Trigger_Condition_Evaluator {

    private $condition_runner;

    public function __construct() {
        // Instantiate dependencies here
        // Ensure component classes are loaded
        if (!class_exists(\WPAICG\Lib\Chat\Triggers\Conditions\AIPKit_Condition_Value_Resolver::class)) {
            require_once __DIR__ . '/conditions/class-aipkit-condition-value-resolver.php';
        }
        if (!class_exists(\WPAICG\Lib\Chat\Triggers\Conditions\AIPKit_Condition_Comparator::class)) {
            require_once __DIR__ . '/conditions/class-aipkit-condition-comparator.php';
        }
        if (!class_exists(\WPAICG\Lib\Chat\Triggers\Conditions\AIPKit_Condition_Runner::class)) {
            require_once __DIR__ . '/conditions/class-aipkit-condition-runner.php';
        }

        $value_resolver = new AIPKit_Condition_Value_Resolver();
        $comparator     = new AIPKit_Condition_Comparator();
        $this->condition_runner = new AIPKit_Condition_Runner($value_resolver, $comparator);
    }

    /**
     * Evaluates an array of conditions. All conditions must be met for this method to return true.
     *
     * @param array $conditions_array Array of condition objects.
     * @param array $context_data     Associative array of context data to evaluate against.
     * @return bool True if all conditions are met, false otherwise.
     */
    public function are_conditions_met(array $conditions_array, array $context_data): bool {
        if (!$this->condition_runner) {
            return false; // Cannot evaluate if runner is not available
        }
        return $this->condition_runner->run($conditions_array, $context_data);
    }

    // Removed: evaluate_single_condition, _get_context_value, _compare_values
    // Their logic is now in the respective Condition component classes.
}