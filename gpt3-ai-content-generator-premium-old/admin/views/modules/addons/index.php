<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/admin/views/modules/addons/index.php
// Status: MODIFIED
// I have added 'semantic_search' to the list of available addons.

/**
 * AIPKit Addons Module - Admin View
 */
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
use WPAICG\aipkit_dashboard;

$addon_status = aipkit_dashboard::get_addon_status();
$is_pro = aipkit_dashboard::is_pro_plan();
$upgrade_url = admin_url('admin.php?page=wpaicg-pricing');

$addons = [
    [
        'key' => 'pdf_download', 'title' => __('PDF Download', 'gpt3-ai-content-generator'),
        'description' => __('Allow users to download chat transcripts and AI Form results as PDF files.', 'gpt3-ai-content-generator'),
        'pro' => true, 'category' => 'chat'
    ],
    [
        'key' => 'conversation_starters', 'title' => __('Conversation Starters', 'gpt3-ai-content-generator'),
        'description' => __('Display predefined prompts to help users start conversations with chatbots.', 'gpt3-ai-content-generator'),
        'pro' => false, 'category' => 'chat'
    ],
    [
        'key' => 'token_management', 'title' => __('Token Management', 'gpt3-ai-content-generator'),
        'description' => __('Set token usage limits per user/role for chatbots and other modules.', 'gpt3-ai-content-generator'),
        'pro' => false, 'category' => 'core'
    ],
    [
        'key' => 'ip_anonymization', 'title' => __('IP Anonymization', 'gpt3-ai-content-generator'),
        'description' => __('Anonymize user IP addresses in chat logs for privacy compliance.', 'gpt3-ai-content-generator'),
        'pro' => false, 'category' => 'privacy'
    ],
    [
        'key' => 'consent_compliance', 'title' => __('Consent Compliance', 'gpt3-ai-content-generator'),
        'description' => __('Display a consent box before users interact with chatbots.', 'gpt3-ai-content-generator'),
        'pro' => true, 'category' => 'privacy'
    ],
    [
        'key' => 'openai_moderation', 'title' => __('OpenAI Moderation', 'gpt3-ai-content-generator'),
        'description' => __('Filter harmful content in chat messages using OpenAI\'s Moderation API.', 'gpt3-ai-content-generator'),
        'pro' => true, 'category' => 'security'
    ],
    [
        'key' => 'ai_post_enhancer', 'title' => __('Content Assistant', 'gpt3-ai-content-generator'),
        'description' => __('Generate WooCommerce product titles, short descriptions, meta tags, and excerpts.', 'gpt3-ai-content-generator'),
        'pro' => false, 'category' => 'content'
    ],
    [
        'key' => 'deepseek', 'title' => __('DeepSeek Integration', 'gpt3-ai-content-generator'),
        'description' => __('Enable DeepSeek models for text generation in various modules.', 'gpt3-ai-content-generator'),
        'pro' => false, 'category' => 'core'
    ],
    [
        'key' => 'voice_playback', 'title' => __('Voice Playback (TTS)', 'gpt3-ai-content-generator'),
        'description' => __('Enable Text-to-Speech for chatbot responses using Google, OpenAI, or ElevenLabs voices.', 'gpt3-ai-content-generator'),
        'pro' => false, 'category' => 'chat'
    ],
    [
        'key' => 'vector_databases', 'title' => __('Vector Database', 'gpt3-ai-content-generator'),
        'description' => __('Connect to Pinecone and Qdrant vector databases for advanced AI Training and retrieval.', 'gpt3-ai-content-generator'),
        'pro' => false, 'category' => 'training'
    ],
    [
        'key' => 'file_upload', 'title' => __('File Upload', 'gpt3-ai-content-generator'),
        'description' => __('Allow users to upload files (PDF, TXT) for context in OpenAI chatbots and manage files in AI Training.', 'gpt3-ai-content-generator'),
        'pro' => true, 'category' => 'chat'
    ],
    [
        'key' => 'triggers', 'title' => __('Chatbot Triggers', 'gpt3-ai-content-generator'),
        'description' => __('Automate chatbot interactions with event-based triggers, conditions, and actions.', 'gpt3-ai-content-generator'),
        'pro' => true, 'category' => 'chat'
    ],
    [
        'key' => 'stock_images', 'title' => __('Stock Images', 'gpt3-ai-content-generator'),
        'description' => __('Search and use images from stock providers like Pexels directly within the Content Writer.', 'gpt3-ai-content-generator'),
        'pro' => false, 'category' => 'content'
    ],
    [
        'key' => 'replicate', 'title' => __('Replicate Integration', 'gpt3-ai-content-generator'),
        'description' => __('Enable image generation using models hosted on Replicate.', 'gpt3-ai-content-generator'),
        'pro' => false, 'category' => 'core'
    ],
    [
        'key' => 'ollama', 'title' => __('Ollama Integration', 'gpt3-ai-content-generator'),
        'description' => __('Enable Ollama models for text generation in various modules.', 'gpt3-ai-content-generator'),
        'pro' => true, 'category' => 'core'
    ],
    [
        'key' => 'semantic_search', 'title' => __('Semantic Search', 'gpt3-ai-content-generator'),
        'description' => __('Enable a frontend shortcode for users to perform semantic search on your custom knowledge base.', 'gpt3-ai-content-generator'),
        'pro' => false, 'category' => 'content'
    ],
    [
        'key' => 'realtime_voice',
        'title' => __('Realtime Voice Agent', 'gpt3-ai-content-generator'),
        'description' => __('Enable low-latency, speech-to-speech conversational experiences using OpenAI\'s Realtime API.', 'gpt3-ai-content-generator'),
        'pro' => true,
        'category' => 'chat'
    ],
    [
        'key' => 'embed_anywhere',
        'title' => __('Embed Anywhere', 'gpt3-ai-content-generator'),
        'description' => __('Embed your chatbots on any external website with a simple HTML snippet.', 'gpt3-ai-content-generator'),
        'pro' => true,
        'category' => 'chat'
    ],
    // [
    //     'key' => 'whatsapp',
    //     'title' => __('WhatsApp', 'gpt3-ai-content-generator'),
    //     'description' => __('Connect your chatbots to WhatsApp via Meta\'s Cloud API. Receive and reply to WhatsApp messages.', 'gpt3-ai-content-generator'),
    //     'pro' => true,
    //     'category' => 'chat'
    // ],
];

// Sort addons alphabetically by title
usort($addons, function ($a, $b) {
    return strcmp($a['title'], $b['title']);
});

// Define categories and their order
$categories = [
    'core'    => __('Core', 'gpt3-ai-content-generator'),
    'chat'    => __('Chat', 'gpt3-ai-content-generator'),
    'content' => __('Content', 'gpt3-ai-content-generator'),
    'training' => __('AI Training & Data', 'gpt3-ai-content-generator'),
    'security' => __('Security & Moderation', 'gpt3-ai-content-generator'),
    'privacy' => __('Privacy & Compliance', 'gpt3-ai-content-generator'),
];


$category_filters = array_merge(['all' => __('All', 'gpt3-ai-content-generator')], $categories);

// Icon + accent color per category (Dashicons + CSS variable fallback)
$category_icons = [
    'core'     => 'admin-generic',
    'chat'     => 'format-chat',
    'content'  => 'media-text',
    'training' => 'database',
    'security' => 'shield-alt',
    'privacy'  => 'lock',
];

// Accent color map (light backgrounds; text color handled via utility)
$category_accents = [
    'core'     => '#EEF4FF',
    'chat'     => '#F0F9FF',
    'content'  => '#F5F3FF',
    'training' => '#F3FDF6',
    'security' => '#FFF7ED',
    'privacy'  => '#FDF2F8',
];
?>
<div class="aipkit_container aipkit_addons_container aipkit_addons_modern" id="aipkit_addons_container"> <?php // Add ID for JS targeting?>
    <div class="aipkit_container-header">
        <div class="aipkit_container-title"><?php esc_html_e('Add-ons', 'gpt3-ai-content-generator'); ?></div>
    </div>
    <div class="aipkit_container-body">
        <!-- Filter Links -->
        <div class="aipkit_addons_filters">
            <div class="aipkit_filter_group" data-filter-group="category">
                <span class="aipkit_filter_label"><?php esc_html_e('Filter by Category:', 'gpt3-ai-content-generator'); ?></span>
                <?php
                $first_filter = true;
foreach ($category_filters as $slug => $name) :
    if (!$first_filter) {
        echo '<span class="aipkit_filter_separator">|</span>';
    }
    $active_class = ($slug === 'all') ? 'aipkit_active' : ''; // 'All' is active by default
    ?>
                    <a
                        href="#"
                        class="aipkit_filter_link <?php echo esc_attr($active_class); ?>"
                        data-filter-value="<?php echo esc_attr($slug); ?>"
                        role="button"
                        tabindex="0"
                        aria-pressed="<?php echo ($slug === 'all') ? 'true' : 'false'; ?>"
                    >
                        <?php echo esc_html($name); ?>
                    </a>
                <?php
        $first_filter = false;
endforeach; ?>
            </div>
        </div>

        <div class="aipkit_addon-grid" id="aipkit_addons_grid">
            <?php foreach ($addons as $addon) :
                $key = $addon['key'];
                $isActive = isset($addon_status[$key]) ? $addon_status[$key] : false;
                $isProFeature = (bool) $addon['pro'];
                $canActivate = (!$isProFeature || ($isProFeature && $is_pro));
                $category = $addon['category'];
                $icon = $category_icons[$category] ?? 'admin-generic';
                $accent = $category_accents[$category] ?? '#EEF2F6';
                $category_label = $categories[$category] ?? ucfirst($category);
                ?>
                <div class="aipkit_addon_card" data-status="<?php echo $isActive ? 'active' : 'inactive'; ?>" data-category="<?php echo esc_attr($category); ?>" style="--aipkit_addon-accent: <?php echo esc_attr($accent); ?>;">
                    <div class="aipkit_addon_card-header">
                        <div class="aipkit_addon_icon-wrap">
                            <span class="dashicons dashicons-<?php echo esc_attr($icon); ?>"></span>
                        </div>
                        <div class="aipkit_addon_header-text">
                            <div class="aipkit_addon_title-line">
                                <span class="aipkit_addon_title"><?php echo esc_html($addon['title']); ?></span>
                            </div>
                            <?php if ($isProFeature || $isActive) : ?>
                            <div class="aipkit_addon_badges">
                                <?php if ($isProFeature) : ?><span class="aipkit_badge aipkit_badge-pro"><?php esc_html_e('Pro', 'gpt3-ai-content-generator'); ?></span><?php endif; ?>
                                <?php if ($isActive) : ?><span class="aipkit_badge aipkit_badge-active"><?php esc_html_e('Active', 'gpt3-ai-content-generator'); ?></span><?php endif; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="aipkit_addon_body">
                        <p class="aipkit_addon_desc" title="<?php echo esc_attr($addon['description']); ?>"><?php echo esc_html($addon['description']); ?></p>
                    </div>
                    <div class="aipkit_addon_actions">
                        <?php if ($canActivate) : ?>
                            <button type="button" class="aipkit_btn aipkit_btn-small <?php echo $isActive ? 'aipkit_btn-secondary' : 'aipkit_btn-primary'; ?> aipkit_addon_toggle_btn" data-addon-key="<?php echo esc_attr($key); ?>" data-active="<?php echo $isActive ? '1' : '0'; ?>">
                                <span class="aipkit_btn-text"><?php echo $isActive ? esc_html__('Deactivate', 'gpt3-ai-content-generator') : esc_html__('Activate', 'gpt3-ai-content-generator'); ?></span>
                                <span class="aipkit_spinner" style="display:none;"></span>
                            </button>
                        <?php else : ?>
                            <a href="<?php echo esc_url($upgrade_url); ?>" class="aipkit_btn aipkit_btn-small aipkit_btn-primary" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Upgrade', 'gpt3-ai-content-generator'); ?></a>
                        <?php endif; ?>
                        <div class="aipkit_addon_status_msg"></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <div id="aipkit_no_addons_message" style="display:none; text-align:center; padding:20px; color: var(--aipkit_text-secondary);">
            <?php esc_html_e('No add-ons match the current filter.', 'gpt3-ai-content-generator'); ?>
        </div>
    </div><!-- /.aipkit_container-body -->
</div><!-- /.aipkit_container -->
