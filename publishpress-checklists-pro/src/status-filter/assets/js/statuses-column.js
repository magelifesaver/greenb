/**
 * Status Filter Column JavaScript
 *
 * Handles the status selection in the checklist table
 */
jQuery(document).ready(function($) {
    /**
     * Initialize Select2 for status dropdowns
     */
    const initializeSelect2 = function() {
        if ($('.ppc-statuses-select').length > 0 && typeof $.fn.select2 !== 'undefined') {
            $('.ppc-statuses-select').select2({
                width: 'resolve',
                placeholder: 'Select statuses to exclude',
                allowClear: true
            });
        }
    };

    /**
     * Save status selections via AJAX
     */
    const saveStatusSelections = function() {
        $(document).on('change', '.ppc-statuses-select', function() {
            const $select = $(this);
            const requirementId = $select.data('requirement');
            const statuses = $select.val() || [];
            const postType = $select.data('post_type') || $('input[name="post_type"]').val() || 'post';

            $.post(ajaxurl, {
                action: 'ppc_save_checklist_statuses',
                nonce: window.ppcStatusFilterData?.nonce || '',
                requirement_id: requirementId,
                statuses: statuses,
                post_type: postType
            }, function(response) {
                if (!response.success) {
                    alert('Failed to save statuses.');
                }
            });
        });
    };

    // Initialize components
    initializeSelect2();
    saveStatusSelections();
});
