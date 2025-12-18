<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/chat/frontend/shortcode/renderer/createActionsContainerHTML.php

namespace WPAICG\Chat\Frontend\Shortcode\RendererMethods;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for creating the HTML for message action buttons.
 *
 * @param array $config
 * @return string HTML for the actions container.
 */
function createActionsContainerHTML_logic(array $config): string {
    // SVG definitions
    $copy_svg = '<svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-copy"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7 7m0 2.667a2.667 2.667 0 0 1 2.667 -2.667h8.666a2.667 2.667 0 0 1 2.667 2.667v8.666a2.667 2.667 0 0 1 -2.667 2.667h-8.666a2.667 2.667 0 0 1 -2.667 -2.667z" /><path d="M4.012 16.737a2.005 2.005 0 0 1 -1.012 -1.737v-10c0 -1.1 .9 -2 2 -2h10c.75 0 1.158 .385 1.5 1" /></svg>';
    $thumb_up_svg = '<svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-thumb-up"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7 11v8a1 1 0 0 1 -1 1h-2a1 1 0 0 1 -1 -1v-7a1 1 0 0 1 1 -1h3a4 4 0 0 0 4 -4v-1a2 2 0 0 1 4 0v5h3a2 2 0 0 1 2 2l-1 5a2 3 0 0 1 -2 2h-7a3 3 0 0 1 -3 -3" /></svg>';
    $thumb_down_svg = '<svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-thumb-down"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7 13v-8a1 1 0 0 0 -1 -1h-2a1 1 0 0 0 -1 1v7a1 1 0 0 0 1 1h3a4 4 0 0 1 4 4v1a2 2 0 0 0 4 0v-5h3a2 2 0 0 0 2 -2l-1 -5a2 3 0 0 0 -2 -2h-7a3 3 0 0 0 -3 3" /></svg>';

    $actionsHTML = '';
    $texts = $config['text'] ?? [];
    if ($config['ttsEnabled'] ?? false) {
        $playTitle = $texts['playActionLabel'] ?? 'Play audio';
        $actionsHTML .= sprintf(
             '<button type="button" class="aipkit_action_btn aipkit_play_btn" title="%1$s" aria-label="%1$s">' .
             '<span class="dashicons dashicons-controls-play"></span>' .
             '</button>',
             esc_attr($playTitle)
         );
    }
    if ($config['enableCopyButton'] ?? false) {
        $copyTitle = $texts['copyActionLabel'] ?? 'Copy response';
        $actionsHTML .= sprintf(
            '<button type="button" class="aipkit_action_btn aipkit_copy_btn" title="%1$s" aria-label="%1$s">%2$s</button>',
            esc_attr($copyTitle),
            $copy_svg
        );
    }
    if ($config['enableFeedback'] ?? false) {
        $likeTitle = $texts['feedbackLikeLabel'] ?? 'Like response';
        $dislikeTitle = $texts['feedbackDislikeLabel'] ?? 'Dislike response';
        $actionsHTML .= sprintf(
            '<button type="button" class="aipkit_action_btn aipkit_feedback_btn aipkit_thumb_up_btn" title="%1$s" aria-label="%1$s" data-feedback="up">%2$s</button>',
            esc_attr($likeTitle),
            $thumb_up_svg
        );
         $actionsHTML .= sprintf(
            '<button type="button" class="aipkit_action_btn aipkit_feedback_btn aipkit_thumb_down_btn" title="%1$s" aria-label="%1$s" data-feedback="down">%2$s</button>',
            esc_attr($dislikeTitle),
            $thumb_down_svg
        );
    }

    if ($actionsHTML) {
        return '<div class="aipkit_message_actions">' . $actionsHTML . '</div>';
    }
    return '';
}