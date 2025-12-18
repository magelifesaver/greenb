<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/admin/views/modules/settings/partials/settings-advanced-access.php
// Status: MODIFIED
// I have updated the placeholder text for the banned words textarea to be more generic and suitable for a list of comma-separated items.

/**
 * Partial: Content Control Settings (Banned Words/IPs)
 * Included within the "Content Control & Moderation" accordion.
 */
if (!defined('ABSPATH')) {
    exit;
}

// Variables required from parent:
// $saved_banned_words, $saved_word_notification_message, $placeholder_word_message,
// $saved_banned_ips, $saved_ip_notification_message, $placeholder_ip_message
// $image_triggers is NO LONGER needed here

?>

<!-- Banned Words Section -->
<h5><?php esc_html_e('Banned Words', 'gpt3-ai-content-generator'); ?></h5>
<div class="aipkit_form-group">
    <textarea id="aipkit_banned_words" name="banned_words" class="aipkit_form-input aipkit_autosave_trigger" rows="4" placeholder="<?php esc_attr_e('e.g., word1, another word, specific phrase', 'gpt3-ai-content-generator'); ?>"><?php echo esc_textarea($saved_banned_words); ?></textarea>
</div>
 <div class="aipkit_form-group">
    <label class="aipkit_form-label" for="aipkit_banned_words_message"><?php esc_html_e('Banned Word Notification Message', 'gpt3-ai-content-generator'); ?></label>
    <input type="text" id="aipkit_banned_words_message" name="banned_words_message" class="aipkit_form-input aipkit_autosave_trigger" value="<?php echo esc_attr($saved_word_notification_message); ?>" placeholder="<?php echo esc_attr($placeholder_word_message); ?>" />
</div>

 <hr class="aipkit_hr">

<!-- Banned IPs Section -->
<div class="aipkit_form-group">
    <label class="aipkit_form-label" for="aipkit_banned_ips"><?php esc_html_e('Banned IPs (comma-separated)', 'gpt3-ai-content-generator'); ?></label>
    <textarea id="aipkit_banned_ips" name="banned_ips" class="aipkit_form-input aipkit_autosave_trigger" rows="4" placeholder="<?php esc_attr_e('e.g., 123.123.123.123, 111.222.333.444', 'gpt3-ai-content-generator'); ?>"><?php echo esc_textarea($saved_banned_ips); ?></textarea>
</div>
 <div class="aipkit_form-group">
    <label class="aipkit_form-label" for="aipkit_banned_ips_message"><?php esc_html_e('Banned IP Notification Message', 'gpt3-ai-content-generator'); ?></label>
    <input type="text" id="aipkit_banned_ips_message" name="banned_ips_message" class="aipkit_form-input aipkit_autosave_trigger" value="<?php echo esc_attr($saved_ip_notification_message); ?>" placeholder="<?php echo esc_attr($placeholder_ip_message); ?>" />
</div>

<!-- Image Generation Triggers Section REMOVED from here -->