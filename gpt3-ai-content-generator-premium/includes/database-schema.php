<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/includes/database-schema.php
// Status: MODIFIED
// I have added a `template_type` column to support different kinds of templates and updated the unique index.

/**
 * Database Schema Definitions for AIPKit.
 * Contains functions to create and update plugin-specific database tables.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Creates or updates the chat logs database table.
 * Uses dbDelta for safe table creation/updates.
 * **REVISED** Schema: One row per conversation thread, messages stored in JSON.
 * **ADDED**: `module` column to track the source.
 */
function aipkit_create_logs_table()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'aipkit_chat_logs';
    $charset_collate = $wpdb->get_charset_collate();

    // Use LONGTEXT for messages JSON to store potentially long history
    $sql = "CREATE TABLE $table_name (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        bot_id bigint(20) unsigned DEFAULT NULL,
        user_id bigint(20) unsigned DEFAULT NULL,
        session_id varchar(64) DEFAULT NULL,
        conversation_uuid varchar(36) NOT NULL,
        is_guest tinyint(1) NOT NULL DEFAULT 0,
        module varchar(50) NULL DEFAULT NULL,
        messages longtext NOT NULL,
        message_count int unsigned NOT NULL DEFAULT 0,
        first_message_ts bigint(20) unsigned DEFAULT NULL,
        last_message_ts bigint(20) unsigned DEFAULT NULL,
        ip_address varchar(100) DEFAULT NULL,
        user_wp_role varchar(100) DEFAULT NULL,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        UNIQUE KEY unique_conversation (bot_id, user_id, session_id, conversation_uuid, module),
        KEY bot_id (bot_id),
        KEY user_id (user_id),
        KEY session_id (session_id),
        KEY conversation_uuid (conversation_uuid),
        KEY is_guest (is_guest),
        KEY module (module),
        KEY last_message_ts (last_message_ts),
        KEY created_at (created_at),
        KEY updated_at (updated_at)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

/**
 * Creates or updates the guest token usage database table.
 * Uses dbDelta for safe table creation/updates.
 */
function aipkit_create_guest_token_usage_table()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'aipkit_guest_token_usage';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        session_id varchar(64) NOT NULL,
        bot_id bigint(20) unsigned NOT NULL,
        tokens_used bigint(20) unsigned NOT NULL DEFAULT 0,
        last_reset_timestamp bigint(20) unsigned NOT NULL DEFAULT 0,
        last_updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        UNIQUE KEY unique_guest_bot (session_id, bot_id),
        KEY session_id (session_id),
        KEY bot_id (bot_id),
        KEY last_reset_timestamp (last_reset_timestamp)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

/**
 * Creates or updates the SSE message cache database table.
 * Uses dbDelta for safe table creation/updates.
 * Only used if WP Object Cache is not available.
 */
function aipkit_create_sse_message_cache_table()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'aipkit_sse_message_cache';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        cache_key varchar(191) NOT NULL,
        message_content longtext NOT NULL,
        expires_at datetime NOT NULL,
        PRIMARY KEY  (cache_key),
        KEY expires_at (expires_at)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

/**
 * Creates or updates the vector data source table.
 */
function aipkit_create_vector_data_source_table()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'aipkit_vector_data_source';
    $charset_collate = $wpdb->get_charset_collate();

    // REMOVED ALL INLINE SQL COMMENTS FOR dbDelta COMPATIBILITY
    $sql = "CREATE TABLE $table_name (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        user_id bigint(20) unsigned DEFAULT NULL,
        timestamp datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        provider varchar(50) NOT NULL,
        vector_store_id varchar(100) NOT NULL,
        vector_store_name varchar(255) DEFAULT NULL,
        post_id bigint(20) unsigned DEFAULT NULL,
        post_title text DEFAULT NULL,
        status varchar(50) NOT NULL,
        message text DEFAULT NULL,
        indexed_content longtext DEFAULT NULL,
        file_id varchar(100) DEFAULT NULL,
        batch_id varchar(100) DEFAULT NULL,
        embedding_provider varchar(50) DEFAULT NULL,
        embedding_model varchar(100) DEFAULT NULL,
        PRIMARY KEY  (id),
        KEY user_id (user_id),
        KEY timestamp (timestamp),
        KEY provider_store_id (provider, vector_store_id),
        KEY post_id (post_id),
        KEY file_id (file_id),
        KEY status (status),
        KEY embedding_provider (embedding_provider),
        KEY embedding_model (embedding_model)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}


/**
 * RENAMED: Creates or updates the automated tasks table.
 */
function aipkit_create_automated_tasks_table()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'aipkit_automated_tasks';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        task_name varchar(255) NOT NULL,
        task_type varchar(50) NOT NULL,
        task_config longtext NOT NULL,
        status varchar(20) NOT NULL DEFAULT 'paused',
        last_run_time datetime DEFAULT NULL,
        next_run_time datetime DEFAULT NULL,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY task_type (task_type),
        KEY status (status),
        KEY next_run_time (next_run_time)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

/**
 * RENAMED: Creates or updates the automated task queue table.
 */
function aipkit_create_automated_task_queue_table()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'aipkit_automated_task_queue';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        task_id bigint(20) unsigned NOT NULL,
        target_identifier varchar(255) NOT NULL,
        task_type varchar(50) NOT NULL,
        item_config longtext DEFAULT NULL,
        status varchar(20) NOT NULL DEFAULT 'pending',
        attempts tinyint unsigned NOT NULL DEFAULT 0,
        last_attempt_time datetime DEFAULT NULL,
        error_message text DEFAULT NULL,
        added_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY task_id (task_id),
        KEY target_identifier (target_identifier),
        KEY task_type (task_type),
        KEY status_added_at (status, added_at)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

/**
 * NEW: Creates or updates the content writer templates table.
 * UPDATED: Added new fields for post settings.
 * UPDATED: Removed post_tags column.
 */
function aipkit_create_content_writer_templates_table()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'aipkit_content_writer_templates';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        user_id bigint(20) unsigned NOT NULL,
        template_name varchar(255) NOT NULL,
        template_type varchar(50) NOT NULL DEFAULT 'content_writer',
        config longtext NOT NULL,
        is_default tinyint(1) NOT NULL DEFAULT 0,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        post_type varchar(20) DEFAULT 'post',
        post_author bigint(20) unsigned DEFAULT NULL,
        post_status varchar(20) DEFAULT 'draft',
        post_schedule datetime DEFAULT NULL,
        post_categories text DEFAULT NULL,
        PRIMARY KEY  (id),
        UNIQUE KEY user_template_name_type (user_id, template_name, template_type),
        KEY user_id (user_id),
        KEY template_type (template_type),
        KEY is_default (is_default),
        KEY post_type (post_type)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

/**
 * Creates the RSS history table to prevent re-processing feed items.
 */
function aipkit_create_rss_history_table()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'aipkit_rss_history';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        task_id bigint(20) unsigned NOT NULL,
        item_guid varchar(255) NOT NULL,
        processed_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        UNIQUE KEY unique_task_guid (task_id, item_guid),
        KEY task_id (task_id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}