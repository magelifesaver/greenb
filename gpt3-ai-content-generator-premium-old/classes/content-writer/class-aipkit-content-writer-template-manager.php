<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/content-writer/class-aipkit-content-writer-template-manager.php
// Status: MODIFIED
// I have added 'tags' to the list of allowed config keys to fix a bug where the enhancer template would not save the state of the 'Tags' checkbox.

namespace WPAICG\ContentWriter;

use WP_Error;

// Load all the new method logic files
$methods_path = __DIR__ . '/template-manager/';
require_once $methods_path . 'ensure-default-template-exists.php';
require_once $methods_path . 'sanitize-config.php';
require_once $methods_path . 'create-template.php';
require_once $methods_path . 'update-template.php';
require_once $methods_path . 'delete-template.php';
require_once $methods_path . 'get-template.php';
require_once $methods_path . 'get-templates-for-user.php';
require_once $methods_path . 'calculate-schedule-datetime.php';


if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
* Manages CRUD operations for Content Writer templates.
* This class now acts as a facade, delegating its methods to namespaced functions.
*/
class AIPKit_Content_Writer_Template_Manager
{
    private $wpdb;
    private $table_name;
    private $allowed_config_keys = [
        'ai_provider', 'ai_model', 'content_title', 'content_title_bulk',
        'content_keywords',
        'ai_temperature', 'content_max_tokens',
        'post_type', 'post_author', 'post_status',
        'post_schedule_date', 'post_schedule_time',
        'post_categories', 'prompt_mode', 'custom_title_prompt', 'custom_content_prompt',
        'generate_meta_description', 'custom_meta_prompt',
        'generate_focus_keyword', 'custom_keyword_prompt',
        'generate_excerpt', 'custom_excerpt_prompt',
        'generate_tags', 'custom_tags_prompt',
        'cw_generation_mode', 'rss_feeds',
        'gsheets_sheet_id', 'gsheets_credentials',
        'url_list',
        'generate_toc',
        'generate_seo_slug', // NEW: Add generate_seo_slug
        'generate_images_enabled', 'image_provider', 'image_model', 'image_prompt',
        'image_count', 'image_placement', 'image_placement_param_x', 'image_alignment', 'image_size',
        'generate_featured_image', 'featured_image_prompt',
    'enable_vector_store', 'vector_store_provider', 'openai_vector_store_ids', 'pinecone_index_name', 'qdrant_collection_name', 'vector_embedding_provider', 'vector_embedding_model', 'vector_store_top_k', 'vector_store_confidence_threshold',
        'rss_include_keywords', 'rss_exclude_keywords',
        'pexels_orientation', 'pexels_size', 'pexels_color',
        'pixabay_orientation', 'pixabay_image_type', 'pixabay_category',
        // --- ADDED: Enhancer-specific config keys ---
        'update_title', 'update_excerpt', 'update_content', 'update_meta',
        'title_prompt', 'excerpt_prompt', 'content_prompt', 'meta_prompt',
        'title', 'excerpt', 'content', 'meta', 'keyword', 'tags', // These are the keys for bulk enhancer templates
        'reasoning_effort' // For o1 models reasoning effort setting
    ];


    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_name = $wpdb->prefix . 'aipkit_content_writer_templates';
    }

    public static function ensure_default_template_exists()
    {
        TemplateManagerMethods\ensure_default_template_exists_logic(new self());
    }

    public function create_template(string $template_name, array $config, string $template_type = 'content_writer'): int|WP_Error
    {
        return TemplateManagerMethods\create_template_logic($this, $template_name, $config, $template_type);
    }

    public function update_template(int $template_id, string $template_name, array $config): bool|WP_Error
    {
        return TemplateManagerMethods\update_template_logic($this, $template_id, $template_name, $config);
    }

    public function delete_template(int $template_id): bool|WP_Error
    {
        return TemplateManagerMethods\delete_template_logic($this, $template_id);
    }

    public function get_template(int $template_id, ?int $user_id_override = null): array|WP_Error
    {
        return TemplateManagerMethods\get_template_logic($this, $template_id, $user_id_override);
    }

    public function get_templates_for_user(string $type = 'content_writer'): array
    {
        return TemplateManagerMethods\get_templates_for_user_logic($this, $type);
    }

    // --- Getters for use by namespaced functions ---
    public function get_wpdb()
    {
        return $this->wpdb;
    }
    public function get_table_name(): string
    {
        return $this->table_name;
    }
    public function get_allowed_config_keys(): array
    {
        return $this->allowed_config_keys;
    }
}
