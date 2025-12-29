<?php
/**
 * ============================================================================
 * File Path: /wp-content/plugins/aaa-workflow-ai-reports/includes/helpers/options-helpers.php
 * Description: Handles all custom option CRUD operations for AAA Workflow AI Reports.
 *              Works with scoped keys inside the custom database table {prefix}_aaa_oc_options.
 * Version: 2.0.0
 * Updated: 2025-12-02
 * Author: AAA Workflow DevOps
 * ============================================================================
 */

if ( ! defined( 'ABSPATH' ) ) exit;

global $wpdb;
define( 'AAA_WF_AI_OPTIONS_TABLE', $wpdb->prefix . 'aaa_oc_options' );

/**
 * --------------------------------------------------------------------------
 * 🔄 Save or update a plugin option in the custom options table
 * --------------------------------------------------------------------------
 *
 * @param string $key   Option name (unique key).
 * @param mixed  $value Option value (string|array).
 * @param string $scope Optional. Scope name (default: 'global').
 * @return void
 */
if ( ! function_exists( 'aaa_wf_ai_save_option' ) ) {
        function aaa_wf_ai_save_option( $key, $value, $scope = 'global' ) {
                global $wpdb;
                $table = AAA_WF_AI_OPTIONS_TABLE;

                if ( empty( $key ) ) {
                        return;
                }

                // Ensure JSON/serialized value for storage
                $stored_value = maybe_serialize( $value );

                $exists = $wpdb->get_var(
                        $wpdb->prepare(
                                "SELECT id FROM {$table} WHERE option_key = %s AND scope = %s",
                                $key,
                                $scope
                        )
                );

                if ( $exists ) {
                        $wpdb->update(
                                $table,
                                [
                                        'option_value' => $stored_value,
                                        'updated_at'   => current_time( 'mysql' ),
                                ],
                                [
                                        'option_key' => $key,
                                        'scope'      => $scope,
                                ],
                                [ '%s', '%s' ],
                                [ '%s', '%s' ]
                        );
                        aaa_wf_ai_debug( "🔁 Updated option '{$key}' [scope: {$scope}]", basename( __FILE__ ), 'options' );
                } else {
                        $wpdb->insert(
                                $table,
                                [
                                        'option_key'   => $key,
                                        'option_value' => $stored_value,
                                        'scope'        => $scope,
                                        'autoload'     => 0,
                                        'updated_at'   => current_time( 'mysql' ),
                                ],
                                [ '%s', '%s', '%s', '%d', '%s' ]
                        );
                        aaa_wf_ai_debug( "💾 Inserted option '{$key}' [scope: {$scope}]", basename( __FILE__ ), 'options' );
                }
        }
}

/**
 * --------------------------------------------------------------------------
 * 📥 Get option value from custom table
 * --------------------------------------------------------------------------
 *
 * @param string $key     Option key.
 * @param mixed  $default Default value if not found.
 * @param string $scope   Optional. Scope name (default: 'global').
 * @return mixed
 */
if ( ! function_exists( 'aaa_wf_ai_get_option' ) ) {
        function aaa_wf_ai_get_option( $key, $default = null, $scope = 'global' ) {
                global $wpdb;
                $table = AAA_WF_AI_OPTIONS_TABLE;

                if ( empty( $key ) ) {
                        return $default;
                }

                $value = $wpdb->get_var(
                        $wpdb->prepare(
                                "SELECT option_value FROM {$table} WHERE option_key = %s AND scope = %s LIMIT 1",
                                $key,
                                $scope
                        )
                );

                if ( $value === null ) {
                        return $default;
                }

                aaa_wf_ai_debug( "📦 Loaded option '{$key}' [scope: {$scope}]", basename( __FILE__ ), 'options' );

                return maybe_unserialize( $value );
        }
}

/**
 * --------------------------------------------------------------------------
 * ❌ Delete an option from the custom table
 * --------------------------------------------------------------------------
 *
 * @param string $key   Option key.
 * @param string $scope Optional. Scope (default 'global').
 * @return void
 */
if ( ! function_exists( 'aaa_wf_ai_delete_option' ) ) {
        function aaa_wf_ai_delete_option( $key, $scope = 'global' ) {
                global $wpdb;
                $table = AAA_WF_AI_OPTIONS_TABLE;

                if ( empty( $key ) ) return;

                $wpdb->delete(
                        $table,
                        [
                                'option_key' => $key,
                                'scope'      => $scope,
                        ],
                        [ '%s', '%s' ]
                );

                aaa_wf_ai_debug( "🗑️ Deleted option '{$key}' [scope: {$scope}]", basename( __FILE__ ), 'options' );
        }
}

/**
 * --------------------------------------------------------------------------
 * 🌐 Retrieve all options under a given scope
 * --------------------------------------------------------------------------
 *
 * @param string $scope Scope name (default: 'global').
 * @return array Array of key => value pairs.
 */
if ( ! function_exists( 'aaa_wf_ai_get_all_options_by_scope' ) ) {
        function aaa_wf_ai_get_all_options_by_scope( $scope = 'global' ) {
                global $wpdb;
                $table = AAA_WF_AI_OPTIONS_TABLE;

                $rows = $wpdb->get_results(
                        $wpdb->prepare(
                                "SELECT option_key, option_value FROM {$table} WHERE scope = %s",
                                $scope
                        ),
                        ARRAY_A
                );

                if ( empty( $rows ) ) {
                        return [];
                }

                $options = [];
                foreach ( $rows as $row ) {
                        $options[ $row['option_key'] ] = maybe_unserialize( $row['option_value'] );
                }

                aaa_wf_ai_debug( "📋 Retrieved " . count( $options ) . " options [scope: {$scope}]", basename( __FILE__ ), 'options' );

                return $options;
        }
}