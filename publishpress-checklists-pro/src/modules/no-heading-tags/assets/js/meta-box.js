(function ($, document, PP_Checklists) {
  'use strict';

  $(function () {
    var $noHeadingTagsElements = $('[id^="pp-checklists-req-no_heading_tags"]');
    if ($noHeadingTagsElements.length > 0) {
      if (PP_Checklists.is_gutenberg_active()) {
        /**
         * For Gutenberg
         */
        // Cache for regex patterns to avoid recreating them
        var regexCache = {};
        var lastContent = '';
        var pendingUpdate = null;
        
        // Helper function to get or create cached regex
        function getCachedRegex(headingTag) {
          if (!regexCache[headingTag]) {
            regexCache[headingTag] = new RegExp('<' + headingTag + '\\b[^>]*>.*?<\\/' + headingTag + '>', 'is');
          }
          return regexCache[headingTag];
        }
        
        // Debounced function to update block warnings in the DOM
        function updateBlockWarnings(blocks, disallowedHeadingTags) {
          // Use requestIdleCallback for non-critical DOM updates, fallback to setTimeout
          var scheduleUpdate = window.requestIdleCallback || function(cb) { setTimeout(cb, 1); };
          
          scheduleUpdate(function() {
            blocks.forEach(block => {
              let hasDisallowedHeading = false;

              // Check if this is a heading block with a disallowed level
              if (block.name === 'core/heading') {
                const headingLevel = block.attributes.level;
                const headingTag = 'h' + headingLevel;

                if (disallowedHeadingTags.includes(headingTag)) {
                  hasDisallowedHeading = true;
                }
              }
              // Also check block content for HTML headings (for blocks that might contain HTML)
              else {
                const blockContent = block.attributes.content || '';

                // Check if this block contains any disallowed heading tags
                for (var i = 0; i < disallowedHeadingTags.length; i++) {
                  var headingTag = disallowedHeadingTags[i];
                  var pattern = getCachedRegex(headingTag);

                  if (pattern.test(blockContent)) {
                    hasDisallowedHeading = true;
                    break;
                  }
                }
              }

              // Set warning attribute on the list view item
              const listViewElement = document.querySelector(
                `.block-editor-list-view-leaf[data-block="${block.clientId}"]`
              );
              if (listViewElement) {
                listViewElement.setAttribute('data-warning', hasDisallowedHeading);
              }
            });
          });
        }
        
        // Main check function
        function checkHeadingTags() {
          var content = PP_Checklists.getEditor().getEditedPostAttribute('content');

          if (typeof content == 'undefined') {
            return;
          }
          
          // Skip if content hasn't changed
          if (content === lastContent) {
            return;
          }
          lastContent = content;

          $noHeadingTagsElements.each(function () {
            var $element = $(this);
            var requirementId = $element.attr('id').replace('pp-checklists-req-', '');

            // Get config for this specific requirement (try specific first, then fallback to original)
            var config = (typeof objectL10n_checklist_requirements !== 'undefined' && objectL10n_checklist_requirements.requirements[requirementId])
              ? objectL10n_checklist_requirements.requirements[requirementId]
              : (typeof objectL10n_checklist_requirements !== 'undefined' && objectL10n_checklist_requirements.requirements['no_heading_tags'])
                ? objectL10n_checklist_requirements.requirements['no_heading_tags']
                : null;

            if (config && config.value) {
              var disallowedHeadingTags = config.value;
              var status = true;

              if (disallowedHeadingTags && disallowedHeadingTags.length > 0) {
                // Check for disallowed heading tags in the content
                for (var i = 0; i < disallowedHeadingTags.length; i++) {
                  var headingTag = disallowedHeadingTags[i];
                  var pattern = getCachedRegex(headingTag);

                  if (pattern.test(content)) {
                    status = false;
                    break; // Early exit once we find a violation
                  }
                }

                // Check blocks for disallowed heading tags and add warnings
                if (wp.data.select('core/block-editor')) {
                  const blocks = wp.data.select('core/block-editor').getBlocks();
                  updateBlockWarnings(blocks, disallowedHeadingTags);
                }
              }

              $element.trigger(
                PP_Checklists.EVENT_UPDATE_REQUIREMENT_STATE,
                status
              );
            }
          });
        }
        
        // Debounced subscriber - only runs 250ms after last change
        var debouncedCheck = _.debounce(checkHeadingTags, 250);
        
        wp.data.subscribe(function () {
          // Cancel any pending update
          if (pendingUpdate) {
            clearTimeout(pendingUpdate);
          }
          
          // Schedule debounced check
          debouncedCheck();
        });
      } else {
        /**
         * For the Classic Editor
         */
        var $content = $('#content');
        var editor;

        /**
         * Check for prohibited heading tags and update the status of the requirement
         */
        function update() {
          var text;

          if (typeof editor == 'undefined' || !editor || editor.isHidden()) {
            // For the text tab.
            text = $content.val();
          } else {
            // For the editor tab.
            text = editor.getContent({ format: 'raw' });
          }

          $noHeadingTagsElements.each(function () {
            var $element = $(this);
            var requirementId = $element.attr('id').replace('pp-checklists-req-', '');

            // Get config for this specific requirement (try specific first, then fallback to original)
            var config = (typeof objectL10n_checklist_requirements !== 'undefined' && objectL10n_checklist_requirements.requirements[requirementId])
              ? objectL10n_checklist_requirements.requirements[requirementId]
              : (typeof objectL10n_checklist_requirements !== 'undefined' && objectL10n_checklist_requirements.requirements['no_heading_tags'])
                ? objectL10n_checklist_requirements.requirements['no_heading_tags']
                : null;

            if (config && config.value) {
              var disallowedHeadingTags = config.value;
              var status = true;

              if (disallowedHeadingTags && disallowedHeadingTags.length > 0) {
                for (var i = 0; i < disallowedHeadingTags.length; i++) {
                  var headingTag = disallowedHeadingTags[i];
                  // More comprehensive regex that handles various heading formats
                  var pattern = new RegExp('<' + headingTag + '\\b[^>]*>.*?<\\/' + headingTag + '>', 'is');

                  if (pattern.test(text)) {
                    status = false;
                    break;
                  }
                }
              }

              $element.trigger(
                PP_Checklists.EVENT_UPDATE_REQUIREMENT_STATE,
                status
              );
            }
          });
        }

        // For the editor.
        $(document).on(PP_Checklists.EVENT_TINYMCE_LOADED, function (event, tinymce) {
          editor = tinymce.editors['content'];

          if (typeof editor !== 'undefined') {
            editor.onInit.add(function () {
              /**
               * Bind the update triggers.
               *
               * When a node change in the main TinyMCE editor has been triggered.
               * When a key has been released in the plain text content editor.
               */

              if (editor.id !== 'content') {
                return;
              }

              editor.on('nodechange keyup', _.debounce(update, 200));
              
            });
          }
        });

        $content.on('input keyup', _.debounce(update, 200));
        update();
      }
    }
  });
})(jQuery, document, PP_Checklists);