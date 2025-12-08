<?php

namespace WPAICG\Lib\Chat\Triggers\Conditions; // UPDATED Namespace

use WP_Error; // Not strictly needed as it returns bool, but good for context.

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * AIPKit_Condition_Comparator
 *
 * Handles the comparison logic between an actual value and an expected value
 * based on a specified operator.
 */
class AIPKit_Condition_Comparator {

    /**
     * Compares the actual value with the expected value using the specified operator.
     *
     * @param mixed  $actual_value   The actual value from context.
     * @param string $operator       The comparison operator.
     * @param mixed  $expected_value The value to compare against.
     * @param string $condition_type The type of condition (e.g., 'text_content') - used for specific operator logic.
     * @param string $field          The field being evaluated (e.g., 'user_message_text') - used for specific operator logic.
     * @return bool True if the comparison holds, false otherwise.
     */
    public function compare($actual_value, string $operator, $expected_value, string $condition_type = '', string $field = ''): bool {
        switch ($operator) {
            // Boolean
            case 'is_true': return $actual_value === true;
            case 'is_false': return $actual_value === false;

            // String specific
            case 'equals':
                return is_string($actual_value) && is_string($expected_value) && $actual_value === $expected_value;
            case 'equals_ignore_case':
                return is_string($actual_value) && is_string($expected_value) && strtolower($actual_value) === strtolower($expected_value);
            case 'not_equals':
                return is_string($actual_value) && is_string($expected_value) && $actual_value !== $expected_value;
            case 'contains':
                if ($condition_type === 'text_content' && $field === 'user_message_text' && is_string($actual_value)) {
                    if (is_array($expected_value)) { // Array of keywords
                        foreach ($expected_value as $keyword) {
                            if (is_string($keyword) && $keyword !== '' && stripos($actual_value, $keyword) !== false) {
                                return true; // Found one keyword
                            }
                        }
                        return false; // No keyword found
                    } elseif (is_string($expected_value) && $expected_value !== '') {
                        return stripos($actual_value, $expected_value) !== false; // Single keyword, case-insensitive
                    }
                } elseif (is_string($actual_value) && is_string($expected_value)) { // Fallback for other types
                     return strpos($actual_value, $expected_value) !== false; // Original case-sensitive
                }
                return false;
            case 'not_contains':
                 if ($condition_type === 'text_content' && $field === 'user_message_text' && is_string($actual_value)) {
                    if (is_array($expected_value)) { // Array of keywords
                        foreach ($expected_value as $keyword) {
                            if (is_string($keyword) && $keyword !== '' && stripos($actual_value, $keyword) !== false) {
                                return false; // Found one keyword, so "not_contains" is false
                            }
                        }
                        return true; // No keyword found, so "not_contains" is true
                    } elseif (is_string($expected_value) && $expected_value !== '') {
                        return stripos($actual_value, $expected_value) === false; // Single keyword, case-insensitive
                    }
                } elseif (is_string($actual_value) && is_string($expected_value)) { // Fallback for other types
                     return strpos($actual_value, $expected_value) === false; // Original case-sensitive
                }
                return true; // Default to true if expected value is invalid for this specific type
            case 'starts_with':
                return is_string($actual_value) && is_string($expected_value) && strpos($actual_value, $expected_value) === 0;
            case 'ends_with':
                return is_string($actual_value) && is_string($expected_value) && substr($actual_value, -strlen($expected_value)) === $expected_value;
            case 'matches_regex':
                return is_string($actual_value) && is_string($expected_value) && @preg_match($expected_value, $actual_value) === 1;
            case 'is_empty':
                return is_string($actual_value) && trim($actual_value) === '';
            case 'is_not_empty':
                return is_string($actual_value) && trim($actual_value) !== '';

            // String/Array (where actual_value is a string or array, expected_value is an array of strings)
            case 'is_one_of':
                if (is_string($actual_value) && is_array($expected_value)) {
                    return in_array($actual_value, $expected_value, true);
                }
                elseif (is_array($actual_value) && is_array($expected_value)) { // Actual value is an array (e.g. user roles)
                    return !empty(array_intersect($actual_value, $expected_value));
                }
                return false;
            case 'is_not_one_of':
                if (is_string($actual_value) && is_array($expected_value)) {
                    return !in_array($actual_value, $expected_value, true);
                }
                elseif (is_array($actual_value) && is_array($expected_value)) { // Actual value is an array (e.g. user roles)
                    return empty(array_intersect($actual_value, $expected_value));
                }
                return false;


            // Numeric
            case 'equals_numeric':
                return is_numeric($actual_value) && is_numeric($expected_value) && (float)$actual_value == (float)$expected_value;
            case 'not_equals_numeric':
                return is_numeric($actual_value) && is_numeric($expected_value) && (float)$actual_value != (float)$expected_value;
            case 'greater_than':
                return is_numeric($actual_value) && is_numeric($expected_value) && (float)$actual_value > (float)$expected_value;
            case 'less_than':
                return is_numeric($actual_value) && is_numeric($expected_value) && (float)$actual_value < (float)$expected_value;
            case 'greater_than_or_equals':
                return is_numeric($actual_value) && is_numeric($expected_value) && (float)$actual_value >= (float)$expected_value;
            case 'less_than_or_equals':
                return is_numeric($actual_value) && is_numeric($expected_value) && (float)$actual_value <= (float)$expected_value;

            default:
                return false;
        }
    }
}