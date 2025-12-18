<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/woocommerce/class-aipkit-woocommerce-integration.php
// Status: MODIFIED

namespace WPAICG\WooCommerce;

use WPAICG\Core\TokenManager\Constants\MetaKeysConstants;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * AIPKit_WooCommerce_Integration
 *
 * Handles all integration points with WooCommerce for selling token packages.
 * This version uses a simple meta box instead of a custom product type.
 */
class AIPKit_WooCommerce_Integration
{
    private static $instance = null;

    /**
     * Ensures only one instance of the class is loaded.
     * @return AIPKit_WooCommerce_Integration
     */
    public static function get_instance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Private constructor to register hooks.
     */
    private function __construct()
    {
        // Add meta box to product page for token settings
        add_action('add_meta_boxes_product', [$this, 'add_token_package_meta_box']);

        // Save meta box data when a product is saved
        add_action('woocommerce_process_product_meta', [$this, 'save_token_package_meta_box_data']);

        // Hook into order completion to grant tokens to the user
        add_action('woocommerce_order_status_completed', [$this, 'grant_tokens_on_order_completion'], 10, 1);
    }

    /**
     * Adds the "AI Power: Token Package" meta box to the product edit screen.
     */
    public function add_token_package_meta_box($post)
    {
        add_meta_box(
            'aipkit_token_package_meta_box',                // Meta box ID
            __('AI Power: Token Package', 'gpt3-ai-content-generator'), // Title
            [$this, 'render_token_package_meta_box'],       // Callback function
            'product',                                      // Post type
            'side',                                         // Context (side, normal, advanced)
            'default'                                       // Priority
        );
    }

    /**
     * Renders the HTML for the token package meta box.
     *
     * @param \WP_Post $post The post object.
     */
    public function render_token_package_meta_box($post)
    {
        wp_nonce_field('aipkit_save_token_package_meta', 'aipkit_token_package_nonce');

        $is_token_package = get_post_meta($post->ID, '_aipkit_is_token_package', true);
        $tokens_amount = get_post_meta($post->ID, '_aipkit_tokens_amount', true);

        echo '<p><label for="aipkit_is_token_package" style="display:block; margin-bottom: 5px;">';
        echo '<input type="checkbox" id="aipkit_is_token_package" name="_aipkit_is_token_package" value="yes" ' . checked($is_token_package, 'yes', false) . ' onchange="document.getElementById(\'aipkit_tokens_amount_wrapper\').style.display = this.checked ? \'block\' : \'none\';" />';
        esc_html_e(' This is a token package product', 'gpt3-ai-content-generator');
        echo '</label></p>';

        echo '<div id="aipkit_tokens_amount_wrapper" style="display:' . ($is_token_package === 'yes' ? 'block' : 'none') . ';">';
        echo '<p><label for="aipkit_tokens_amount">' . esc_html__('Tokens Granted:', 'gpt3-ai-content-generator') . '</label>';
        echo '<input type="number" id="aipkit_tokens_amount" name="_aipkit_tokens_amount" value="' . esc_attr($tokens_amount) . '" class="short" min="0" step="1" placeholder="e.g., 100000" />';
        echo '</p>';
        echo '</div>';
    }

    /**
     * Saves the token package meta box data.
     *
     * @param int $post_id The ID of the product being saved.
     */
    public function save_token_package_meta_box_data($post_id)
    {
        // --- FIX: Unslash and sanitize POST data before use ---
        $post_data = wp_unslash($_POST);
        if (!isset($post_data['aipkit_token_package_nonce']) || !wp_verify_nonce(sanitize_key($post_data['aipkit_token_package_nonce']), 'aipkit_save_token_package_meta')) {
            return;
        }
        // --- END FIX ---

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        $is_token_package = isset($post_data['_aipkit_is_token_package']) ? 'yes' : 'no';
        update_post_meta($post_id, '_aipkit_is_token_package', $is_token_package);

        if ($is_token_package === 'yes' && isset($post_data['_aipkit_tokens_amount'])) {
            $tokens_amount = absint($post_data['_aipkit_tokens_amount']);
            update_post_meta($post_id, '_aipkit_tokens_amount', $tokens_amount);
        } else {
            // Delete the tokens amount if it's no longer a token package
            delete_post_meta($post_id, '_aipkit_tokens_amount');
        }
    }

    /**
     * Grant tokens to a user when their WooCommerce order is marked as "completed".
     *
     * @param int $order_id The ID of the completed order.
     */
    public function grant_tokens_on_order_completion(int $order_id)
    {
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        $user_id = $order->get_user_id();
        if (!$user_id) {
            return;
        }

        $total_tokens_to_grant = 0;
        foreach ($order->get_items() as $item) {
            $product_id = $item->get_product_id();
            $is_token_package = get_post_meta($product_id, '_aipkit_is_token_package', true);

            if ($is_token_package === 'yes') {
                $tokens_granted = (int) get_post_meta($product_id, '_aipkit_tokens_amount', true);
                $quantity = $item->get_quantity();
                if ($tokens_granted > 0) {
                    $total_tokens_to_grant += ($tokens_granted * $quantity);
                }
            }
        }

        if ($total_tokens_to_grant > 0) {
            $current_balance = (int) get_user_meta($user_id, MetaKeysConstants::TOKEN_BALANCE_META_KEY, true);
            $new_balance = $current_balance + $total_tokens_to_grant;
            update_user_meta($user_id, MetaKeysConstants::TOKEN_BALANCE_META_KEY, $new_balance);

            $order->add_order_note(
                /* translators: 1: The number of tokens granted, 2: The user's new token balance. */
                sprintf(__('AI Power: Granted %1$s tokens to user. New balance: %2$s', 'gpt3-ai-content-generator'),
                    number_format_i18n($total_tokens_to_grant),
                    number_format_i18n($new_balance)
                )
            );
        }
    }
}