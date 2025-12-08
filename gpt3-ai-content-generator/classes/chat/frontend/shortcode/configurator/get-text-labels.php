<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/chat/frontend/shortcode/configurator/get-text-labels.php
// Status: NEW FILE

namespace WPAICG\Chat\Frontend\Shortcode\ConfiguratorMethods;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Prepares the `text` array for localization in JavaScript.
 *
 * @param array $settings Bot settings.
 * @param array $consent_texts Prepared consent texts.
 * @return array The array of text labels.
 */
function get_text_labels_logic(array $settings, array $consent_texts): array {
    return [
        'sendMessage' => __('Send Message', 'gpt3-ai-content-generator'),
        'sending' => __('Sending...', 'gpt3-ai-content-generator'),
        'typeMessage' => $settings['input_placeholder'] ?? __('Type your message...', 'gpt3-ai-content-generator'),
        'thinking' => __('Thinking', 'gpt3-ai-content-generator'),
        'streaming' => __('Streaming...', 'gpt3-ai-content-generator'),
        'errorPrefix' => __('Error:', 'gpt3-ai-content-generator'),
        'userPrefix' => __('User', 'gpt3-ai-content-generator'),
        'clearChat' => __('Clear Chat', 'gpt3-ai-content-generator'),
        'closeChat' => __('Close Chat', 'gpt3-ai-content-generator'),
        'fullscreen' => __('Fullscreen', 'gpt3-ai-content-generator'),
        'exitFullscreen' => __('Exit Fullscreen', 'gpt3-ai-content-generator'),
        'download' => __('Download Transcript', 'gpt3-ai-content-generator'),
        'downloadTxt' => __('Download TXT', 'gpt3-ai-content-generator'),
        'downloadPdf' => __('Download PDF', 'gpt3-ai-content-generator'),
        'downloadEmpty' => __('Nothing to download.', 'gpt3-ai-content-generator'),
        'pdfError' => __('Could not generate PDF. jsPDF library might be missing.', 'gpt3-ai-content-generator'),
        'streamError' => __('Stream error. Please try again.', 'gpt3-ai-content-generator'),
        'connError' => __('Connection error. Please try again.', 'gpt3-ai-content-generator'),
        'initialGreeting' => $settings['greeting'] ?? __('Hello! How can I assist you?', 'gpt3-ai-content-generator'),
        'sidebarToggle' => __('Toggle Conversation Sidebar', 'gpt3-ai-content-generator'),
        'newChat' => __('New Chat', 'gpt3-ai-content-generator'),
        'conversations' => __('Conversations', 'gpt3-ai-content-generator'),
        'historyGuests' => __('History unavailable for guests.', 'gpt3-ai-content-generator'),
        'historyEmpty' => __('No past conversations.', 'gpt3-ai-content-generator'),
        'feedbackLikeLabel' => __('Like response', 'gpt3-ai-content-generator'),
        'feedbackDislikeLabel' => __('Dislike response', 'gpt3-ai-content-generator'),
        'feedbackSubmitted' => __('Feedback submitted', 'gpt3-ai-content-generator'),
        'copyActionLabel' => __('Copy response', 'gpt3-ai-content-generator'),
        'consentTitle' => $consent_texts['consent_title'],
        'consentMessage' => $consent_texts['consent_message'],
        'consentButton' => $consent_texts['consent_button'],
        'playActionLabel' => __('Play audio', 'gpt3-ai-content-generator'),
        'imageCommandEmptyPrompt' => __('Please provide a description after the image command (e.g., /image a cat playing with a ball).', 'gpt3-ai-content-generator'),
        'pauseActionLabel' => __('Pause audio', 'gpt3-ai-content-generator'),
        'webSearchToggle' => __('Toggle Web Search', 'gpt3-ai-content-generator'),
        'webSearchActive' => __('Web Search Active', 'gpt3-ai-content-generator'),
        'webSearchInactive' => __('Web Search Inactive', 'gpt3-ai-content-generator'),
        'googleSearchGroundingToggle' => __('Toggle Google Search Grounding', 'gpt3-ai-content-generator'),
        'googleSearchGroundingActive' => __('Google Search Grounding Active', 'gpt3-ai-content-generator'),
        'googleSearchGroundingInactive' => __('Google Search Grounding Inactive', 'gpt3-ai-content-generator'),
        // Popup hint related
        'dismissHint' => __('Dismiss', 'gpt3-ai-content-generator'),
    ];
}
