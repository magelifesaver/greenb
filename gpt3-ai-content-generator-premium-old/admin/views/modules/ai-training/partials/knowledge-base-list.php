<?php
/**
 * Partial: AI Training - Knowledge Base List View
 * Displays all available knowledge bases (vector stores) in a compact list/table style.
 */

if (!defined('ABSPATH')) {
    exit;
}

use WPAICG\Vector\AIPKit_Vector_Store_Registry;

$training_general_settings = get_option('aipkit_training_general_settings', ['hide_user_uploads' => true]);
$hide_user_uploads = $training_general_settings['hide_user_uploads'] ?? true;

$all_stores = [];
if (class_exists(AIPKit_Vector_Store_Registry::class)) {
    // OpenAI
    $openai_stores = AIPKit_Vector_Store_Registry::get_registered_stores_by_provider('OpenAI');
    if (is_array($openai_stores)) {
        foreach ($openai_stores as $store) {
            $store_name = isset($store['name']) ? (string) $store['name'] : (string) ($store['id'] ?? '');
            $is_user_upload = strpos($store_name, 'chat_file_') === 0;
            if ($hide_user_uploads && $is_user_upload) continue;
            $document_count = !empty($store['file_counts']['total']) ? (int) $store['file_counts']['total'] : 'N/A';
            $all_stores[] = [
                'name' => $store_name,
                'id' => isset($store['id']) ? (string) $store['id'] : $store_name,
                'provider' => 'OpenAI',
                'is_user_upload' => $is_user_upload,
                'expires_at' => $store['expires_at'] ?? null,
                'document_count' => $document_count,
            ];
        }
    }
    // Pinecone
    $pinecone_indexes = AIPKit_Vector_Store_Registry::get_registered_stores_by_provider('Pinecone');
    if (is_array($pinecone_indexes)) {
        foreach ($pinecone_indexes as $index) {
            $name = isset($index['name']) ? (string) $index['name'] : (string) ($index['id'] ?? '');
            $document_count = !empty($index['total_vector_count']) ? (int) $index['total_vector_count'] : 'N/A';
            $all_stores[] = [
                'name' => $name,
                'id' => $name,
                'provider' => 'Pinecone',
                'is_user_upload' => false,
                'document_count' => $document_count,
            ];
        }
    }
    // Qdrant
    $qdrant_collections = AIPKit_Vector_Store_Registry::get_registered_stores_by_provider('Qdrant');
    if (is_array($qdrant_collections)) {
        foreach ($qdrant_collections as $collection) {
            $name = isset($collection['name']) ? (string) $collection['name'] : (string) ($collection['id'] ?? '');
            $document_count = !empty($collection['vectors_count']) ? (int) $collection['vectors_count'] : 'N/A';
            $all_stores[] = [
                'name' => $name,
                'id' => $name,
                'provider' => 'Qdrant',
                'is_user_upload' => false,
                'document_count' => $document_count,
            ];
        }
    }
}

// Compute last updated timestamp for each store for recency sorting
global $wpdb;
if ($wpdb instanceof \wpdb && !empty($all_stores)) {
    $table_name = $wpdb->prefix . 'aipkit_vector_data_source';
    $table_exists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table_name));
    foreach ($all_stores as &$store) {
        $store['last_updated_ts'] = 0;
        if ($table_exists === $table_name) {
            $last_updated_ts = $wpdb->get_var($wpdb->prepare(
                "SELECT MAX(timestamp) FROM {$table_name} WHERE provider = %s AND vector_store_id = %s",
                $store['provider'],
                $store['id']
            ));
            if ($last_updated_ts) {
                $ts_num = is_numeric($last_updated_ts) ? (int) $last_updated_ts : strtotime($last_updated_ts);
                if ($ts_num) {
                    $store['last_updated_ts'] = $ts_num;
                }
            }
        }
    }
    unset($store);
}

// Sort by last updated desc, then by name
usort($all_stores, function ($a, $b) {
    $a_ts = (int)($a['last_updated_ts'] ?? 0);
    $b_ts = (int)($b['last_updated_ts'] ?? 0);
    if ($a_ts === $b_ts) {
        return strcasecmp($a['name'], $b['name']);
    }
    return $b_ts <=> $a_ts;
});

?>

<?php if (empty($all_stores)): ?>
    <?php include __DIR__ . '/vector-store/_empty-state.php'; ?>
<?php else: ?>
    <div class="aipkit_kb_list_container" role="table" aria-label="<?php esc_attr_e('Knowledge Base List', 'gpt3-ai-content-generator'); ?>">
        <div class="aipkit_kb_list_header" role="row">
            <div class="aipkit_kb_list_col_name" role="columnheader"><?php esc_html_e('Name', 'gpt3-ai-content-generator'); ?></div>
            <div class="aipkit_kb_list_col_provider" role="columnheader"><?php esc_html_e('Provider', 'gpt3-ai-content-generator'); ?></div>
            <div class="aipkit_kb_list_col_docs" role="columnheader"><?php esc_html_e('Documents', 'gpt3-ai-content-generator'); ?></div>
            <div class="aipkit_kb_list_col_updated" role="columnheader"><?php esc_html_e('Updated', 'gpt3-ai-content-generator'); ?></div>
            <div class="aipkit_kb_list_col_action" role="columnheader"></div>
        </div>
        <div class="aipkit_kb_list_body">
            <?php foreach ($all_stores as $store): $provider_lower = strtolower($store['provider']); 
                // Server-side stats (precomputed)
                $doc_count_display = isset($store['document_count']) ? $store['document_count'] : 'N/A';
                global $wpdb; $last_updated_display = 'N/A';
                if ($wpdb instanceof \wpdb) {
                    $table_name = $wpdb->prefix . 'aipkit_vector_data_source';
                    $table_exists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table_name));
                    if ($table_exists === $table_name) {
                        $last_updated_ts = $wpdb->get_var($wpdb->prepare(
                            "SELECT MAX(timestamp) FROM {$table_name} WHERE provider = %s AND vector_store_id = %s",
                            $store['provider'],
                            $store['id']
                        ));
                        if ($last_updated_ts) {
                            $ts_num = is_numeric($last_updated_ts) ? (int) $last_updated_ts : strtotime($last_updated_ts);
                            if ($ts_num) {
                                $last_updated_display = human_time_diff($ts_num, current_time('timestamp')) . ' ' . esc_html__('ago', 'gpt3-ai-content-generator');
                            }
                        }
                    }
                }
            ?>
             <div class="aipkit_kb_list_row" role="row"
                     data-provider="<?php echo esc_attr($store['provider']); ?>"
                     data-id="<?php echo esc_attr($store['id']); ?>"
                 data-name="<?php echo esc_attr($store['name']); ?>"
                 data-last-updated-ts="<?php echo esc_attr($store['last_updated_ts'] ?? 0); ?>"
                     tabindex="0" role="button"
                     aria-label="<?php echo esc_attr(sprintf(__('View details for %s knowledge base', 'gpt3-ai-content-generator'), $store['name'])); ?>">

                    <div class="aipkit_kb_list_col_name" role="cell">
                        <span class="aipkit_kb_list_name"><?php echo esc_html($store['name']); ?></span>
                        <?php if (!empty($store['is_user_upload'])): ?>
                            <span class="aipkit_kb_list_user_badge" title="<?php esc_attr_e('User uploaded knowledge base from chat interface', 'gpt3-ai-content-generator'); ?>">
                                <span class="dashicons dashicons-admin-users"></span>
                                <?php esc_html_e('User', 'gpt3-ai-content-generator'); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    <div class="aipkit_kb_list_col_provider" role="cell">
                        <span class="aipkit_kb_card_provider aipkit_provider_tag_<?php echo esc_attr($provider_lower); ?>"><?php echo esc_html($store['provider']); ?></span>
                    </div>
                    <div class="aipkit_kb_list_col_docs" role="cell">
                        <span class="aipkit_kb_list_stat" data-stat="doc-count" data-initialized="true"><?php echo is_numeric($doc_count_display) ? esc_html(number_format_i18n($doc_count_display)) : esc_html($doc_count_display); ?></span>
                    </div>
                    <div class="aipkit_kb_list_col_updated" role="cell">
                        <?php if (!empty($store['is_user_upload']) && !empty($store['expires_at'])): ?>
                            <?php 
                                $expires_timestamp = is_numeric($store['expires_at']) ? $store['expires_at'] : strtotime($store['expires_at']);
                                echo esc_html(date_i18n(get_option('date_format'), $expires_timestamp));
                            ?>
                        <?php else: ?>
                            <span class="aipkit_kb_list_stat" data-stat="last-updated" data-initialized="true"><?php echo esc_html($last_updated_display); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="aipkit_kb_list_col_action" role="cell">
                        <span class="dashicons dashicons-arrow-right-alt2" aria-hidden="true"></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>
