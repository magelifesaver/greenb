<?php

namespace PublishPress\ChecklistsPro\DuplicateChecklist;

/**
 * Handles the admin UI and actions for the Debug Duplicate feature.
 */
class DebugDuplicateAdmin
{
    /**
     * @var DebugDuplicateHandler
     */
    private $debugHandler;

    /**
     * @var array|null
     */
    private $actionResult;

    public function __construct(DebugDuplicateHandler $debugHandler)
    {
        $this->debugHandler = $debugHandler;
    }

    /**
     * Handle incoming debug duplicate requests when applicable.
     *
     * @param array $request
     */
    public function maybeHandleRequest($request)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }

        if (!isset($request['tools_action'], $request['tools_nonce'])) {
            return;
        }

        if (!wp_verify_nonce($request['tools_nonce'], 'ppch_tools_action')) {
            return;
        }

        $action = sanitize_text_field(wp_unslash($request['tools_action']));

        switch ($action) {
            case 'cleanup_duplicate':
                if (!empty($request['duplicate_name'])) {
                    $duplicateName = sanitize_text_field(wp_unslash($request['duplicate_name']));
                    $this->actionResult = $this->debugHandler->cleanupDuplicate($duplicateName);
                }
                break;
            case 'cleanup_orphaned':
                $this->actionResult = $this->debugHandler->cleanupOrphanedMappings();
                break;
            default:
                // Unknown action; ignore silently for now.
                break;
        }
    }

    /**
     * Render the debug duplicate admin interface.
     */
    public function render()
    {
        $currentDuplicates = $this->debugHandler->getCurrentDuplicates();
        ?>
        <div class="ppch-tools-container">

            <h2><?php esc_html_e('Duplicate Checklist Tools', 'publishpress-checklists-pro'); ?></h2>

            <div class="ppch-tools-section">
                <h3><?php esc_html_e('Current Duplicate Requirements', 'publishpress-checklists-pro'); ?></h3>

                <?php if (!empty($currentDuplicates['total_count'])): ?>
                    <p><?php printf(esc_html__('Found %d duplicate requirements:', 'publishpress-checklists-pro'), (int) $currentDuplicates['total_count']); ?></p>

                    <div class="ppch-duplicates-list">
                        <?php foreach ($currentDuplicates['duplicates'] as $duplicate): ?>
                            <div class="ppch-duplicate-item">
                                <div>
                                    <strong><?php echo esc_html($duplicate['display_name']); ?></strong>
                                    <br>
                                    <small><?php echo esc_html($duplicate['name']); ?></small>
                                </div>
                                <div>
                                    <form method="post" class="ppch-cleanup-form">
                                        <?php wp_nonce_field('ppch_tools_action', 'tools_nonce'); ?>
                                        <input type="hidden" name="tools_action" value="cleanup_duplicate">
                                        <input type="hidden" name="duplicate_name" value="<?php echo esc_attr($duplicate['name']); ?>">
                                        <button type="submit" class="button button-secondary"
                                            onclick="return confirm('<?php esc_attr_e('Are you sure you want to clean up this duplicate? This action cannot be undone.', 'publishpress-checklists-pro'); ?>')">
                                            <?php esc_html_e('Clean Up', 'publishpress-checklists-pro'); ?>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="ppch-test-result ppch-test-success">
                        <?php esc_html_e('No duplicate requirements found. Your system is clean!', 'publishpress-checklists-pro'); ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="ppch-tools-section">
                <h3><?php esc_html_e('Manual Cleanup', 'publishpress-checklists-pro'); ?></h3>
                <p><?php esc_html_e('If you know the exact name of a stuck duplicate requirement, you can force clean it up here.', 'publishpress-checklists-pro'); ?></p>

                <form method="post" class="ppch-cleanup-form">
                    <?php wp_nonce_field('ppch_tools_action', 'tools_nonce'); ?>
                    <input type="hidden" name="tools_action" value="cleanup_duplicate">
                    <input type="text" name="duplicate_name" placeholder="<?php esc_attr_e('e.g., title_count_duplicate_2', 'publishpress-checklists-pro'); ?>">
                    <button type="submit" class="button button-secondary"
                        onclick="return confirm('<?php esc_attr_e('Are you sure you want to force cleanup this duplicate? This action cannot be undone.', 'publishpress-checklists-pro'); ?>')">
                        <?php esc_html_e('Force Cleanup', 'publishpress-checklists-pro'); ?>
                    </button>
                </form>
            </div>

            <?php if (!empty($currentDuplicates['mappings'])): ?>
                <div class="ppch-tools-section">
                    <h3><?php esc_html_e('Orphaned PHP Class Mappings', 'publishpress-checklists-pro'); ?></h3>
                    <p><?php esc_html_e('Class mappings link duplicate requirement names to their PHP class names. The system uses these mappings to determine which PHP class to instantiate for each duplicate requirement.', 'publishpress-checklists-pro'); ?></p>
                    <p><?php printf(esc_html__('Found %d class mappings. Clean up any orphaned mappings (mappings without corresponding duplicate requirements) to keep your database clean.', 'publishpress-checklists-pro'), count($currentDuplicates['mappings'])); ?></p>

                    <form method="post">
                        <?php wp_nonce_field('ppch_tools_action', 'tools_nonce'); ?>
                        <input type="hidden" name="tools_action" value="cleanup_orphaned">
                        <button type="submit" class="button button-secondary">
                            <?php esc_html_e('Clean Up Orphaned Mappings', 'publishpress-checklists-pro'); ?>
                        </button>
                    </form>
                </div>
            <?php endif; ?>

        </div>
        <?php
    }
}
