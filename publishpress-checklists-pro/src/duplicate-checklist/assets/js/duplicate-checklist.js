(function ($, document) {
  'use strict';

  $(function () {
    // Handle duplicate button clicks
    $(document).on('click', '.ppc-duplicate-btn', function (e) {
      e.preventDefault();

      var $button = $(this);

      if ($button.prop('disabled')) {
        return false;
      }

      var requirementName = $button.data('requirement');
      var postType = $button.data('post-type');

      if (!confirm(ppcDuplicateChecklist.strings.confirm_duplicate)) {
        return;
      }
      var customName = '';

      $button.prop('disabled', true);
      var originalText = $button.html();
      $button.html(ppcDuplicateChecklist.strings.duplicating);

      $.ajax({
        url: ppcDuplicateChecklist.ajaxurl,
        type: 'POST',
        data: {
          action: 'ppc_duplicate_requirement',
          requirement: requirementName,
          post_type: postType,
          custom_name: customName,
          _wpnonce: ppcDuplicateChecklist.nonce
        },

        success: function (response) {
          console.log(response);
          if (response.success) {
            var displayName = response.data.display_name || response.data.duplicate_name;
            showNotice(ppcDuplicateChecklist.strings.success + ' (' + displayName + ')', 'success');

            setTimeout(function () {
              window.location.reload();
            }, 1000);
          } else {
            showNotice(response.data || ppcDuplicateChecklist.strings.error, 'error');
            resetButton($button, originalText);
          }
        },
        error: function () {
          showNotice(ppcDuplicateChecklist.strings.error, 'error');
          resetButton($button, originalText);
        }
      });
    });

    // Handle delete duplicate button clicks
    $(document).on('click', '.ppc-delete-duplicate-btn', function (e) {
      e.preventDefault();

      var $button = $(this);
      var requirementName = $button.data('requirement');

      if (!confirm('Are you sure you want to delete this duplicate requirement? This action cannot be undone.')) {
        return;
      }

      $button.prop('disabled', true);
      var originalHtml = $button.html();


      $.ajax({
        url: ppcDuplicateChecklist.ajaxurl,
        type: 'POST',
        data: {
          action: 'ppc_delete_duplicate_requirement',
          requirement: requirementName,
          _wpnonce: ppcDuplicateChecklist.nonce
        },

        success: function (response) {
          if (response.success) {
            // Remove the row from the table
            $button.closest('tr').fadeOut(function () {
              $(this).remove();
            });
            showNotice('Duplicate requirement deleted successfully.', 'success');
          } else {
            showNotice(response.data || 'Error deleting duplicate requirement.', 'error');
            resetButton($button, originalHtml);
          }
        },
        error: function () {
          showNotice('Error deleting duplicate requirement.', 'error');
          resetButton($button, originalHtml);
        }
      });
    });

    /**
     * Reset button to original state
     */
    function resetButton($button, originalText) {
      $button.prop('disabled', false);
      $button.html(originalText);
    }

    /**
     * Show admin notice
     */
    function showNotice(message, type) {
      var noticeClass = type === 'success' ? 'notice-success' : 'notice-error';
      var $notice = $('<div class="notice ' + noticeClass + ' is-dismissible"><p>' + message + '</p></div>');

      if ($('.wrap h1').length) {
        $('.wrap h1').after($notice);
      } else {
        $('.wrap').prepend($notice);
      }

      setTimeout(function () {
        $notice.fadeOut();
      }, 5000);

      $notice.on('click', '.notice-dismiss', function () {
        $notice.fadeOut();
      });
    }

    /**
     * Add visual indicators for duplicate requirements
     */
    function addDuplicateIndicators() {
      $('.pp-checklists-requirement-row').each(function () {
        var $row = $(this);
        var requirementName = $row.find('input[name*="_title"]').attr('name');

        if (requirementName && requirementName.indexOf('_duplicate_') !== -1) {
          $row.addClass('ppc-duplicate-requirement');

          var duplicateMatch = requirementName.match(/_duplicate_(\d+)/);
          var duplicateNumber = duplicateMatch ? duplicateMatch[1] : '2';


          var $titleCell = $row.find('td:first');
          if (!$titleCell.find('.ppc-duplicate-badge').length) {
            $titleCell.append('<span class="ppc-duplicate-badge">(' + duplicateNumber + ')</span>');
          }
        }
      });
    }

    moveDuplicateColumnToEnd();
    addDuplicateIndicators();

    // Re-run adjustments when new rows are injected (dynamic content)
    $(document).on('DOMNodeInserted', function () {
      setTimeout(function () {
        moveDuplicateColumnToEnd();
        addDuplicateIndicators();
      }, 100);
    });
  });



})(jQuery, document);

function moveDuplicateColumnToEnd() {
  var $headerRow = jQuery('#pp-checklists-requirements thead tr');
  if (!$headerRow.length) {
    return;
  }

  var $duplicateHeader = $headerRow.find('.ppc-duplicate-header').detach();
  if ($duplicateHeader.length) {
    var $proHeader = $headerRow.find('#pp-checklists-pro-badge-heading');
    if ($proHeader.length) {
      $duplicateHeader.insertBefore($proHeader);
    } else {
      $headerRow.append($duplicateHeader);
    }
  }

  jQuery('#pp-checklists-requirements tbody tr').each(function () {
    var $row = jQuery(this);
    var $duplicateCell = $row.find('td.ppc-duplicate-actions').detach();
    if (!$duplicateCell.length) {
      return;
    }

    var $proCell = $row.find('td.ppc-pro-overlay-cell').first();
    if ($proCell.length) {
      $duplicateCell.insertBefore($proCell);
    } else {
      $row.append($duplicateCell);
    }
  });
}
