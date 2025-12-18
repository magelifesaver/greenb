<?php
/**
 * Partial: Chatolia Promotion Notice
 * Informs users about the cloud-based Chatolia chatbot plugin.
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
?>
<div id="aipkit_chatolia_notice" class="aipkit_notification_bar aipkit_notification_bar--info" style="margin-bottom: 20px; background-color: #ffffff;">
    <div class="aipkit_notification_bar__icon">
        <span class="dashicons dashicons-info"></span>
    </div>
    <div class="aipkit_notification_bar__content">
        <p>
            <?php esc_html_e('Did you know we also have a cloud-based chatbot? Go to Plugins > Add New and search for "Chatolia" to install it.', 'gpt3-ai-content-generator'); ?>
        </p>
    </div>
    <div class="aipkit_notification_bar__actions">
        <a href="<?php echo esc_url(admin_url('plugin-install.php?s=chatolia&tab=search&type=term')); ?>" class="aipkit_btn aipkit_btn-primary"><?php esc_html_e('Search Plugins', 'gpt3-ai-content-generator'); ?></a>
        <a href="https://chatolia.com" target="_blank" rel="noopener noreferrer" class="aipkit_btn aipkit_btn-secondary"><?php esc_html_e('Visit chatolia.com', 'gpt3-ai-content-generator'); ?></a>
        <button type="button" id="aipkit_dismiss_chatolia_notice_btn" class="aipkit_btn aipkit_btn-secondary"><?php esc_html_e('Dont Show Again', 'gpt3-ai-content-generator'); ?></button>
        <button type="button" class="aipkit_notification_bar__close" title="<?php esc_attr_e('Dismiss for now', 'gpt3-ai-content-generator'); ?>">×</button>
    </div>
</div>
