<?php

namespace WPAICG\Lib\Chat\Triggers\Conditions; // UPDATED Namespace

use WPAICG\Lib\Chat\Triggers\Conditions\AIPKit_Condition_Value_Resolver;
use WPAICG\Lib\Chat\Triggers\Conditions\AIPKit_Condition_Comparator;
use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * AIPKit_Condition_Runner
 *
 * Orchestrates the evaluation of multiple conditions.
 */
class AIPKit_Condition_Runner {

    private $value_resolver;
    private $comparator;

    public function __construct(AIPKit_Condition_Value_Resolver $resolver, AIPKit_Condition_Comparator $comparator) {
        $this->value_resolver = $resolver;
        $this->comparator = $comparator;
    }

    /**
     * Evaluates an array of conditions. All conditions must be met for this method to return true.
     *
     * @param array $conditions_array Array of condition objects.
     * @param array $context_data     Associative array of context data to evaluate against.
     * @return bool True if all conditions are met, false otherwise.
     */
    public function run(array $conditions_array, array $context_data): bool {
        if (empty($conditions_array)) {
            return true; // No conditions means conditions are met by default.
        }

        foreach ($conditions_array as $condition) {
            $condition_type = $condition['type'] ?? null;
            $field          = $condition['field'] ?? null;
            $operator       = $condition['operator'] ?? null;
            $expected_value = $condition['value'] ?? null;

            if (!$condition_type || !$field || !$operator) {
                return false; // Invalid condition structure means it fails
            }

            $actual_value_result = $this->value_resolver->get_value($condition_type, $field, $context_data);

            if (is_wp_error($actual_value_result)) {
                return false; // Could not resolve value, condition fails
            }
            $actual_value = $actual_value_result;

            if (!$this->comparator->compare($actual_value, $operator, $expected_value, $condition_type, $field)) {
                return false; // If any condition fails, the whole set fails.
            }
        }
        return true; // All conditions passed.
    }
}