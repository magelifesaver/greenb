(function ($, document, PP_Checklists) {
  'use strict';

  $(function () {
    var lastCount = 0;
    var $audioCountElements = $('[id^="pp-checklists-req-audio_count"]');

    const AUDIO_REGEX = /<audio[\s\S]*?<\/audio>|<figure[^>]+wp-block-embed[^>]*is-provider-(?:soundcloud|spotify|mixcloud|pocket-casts)[^>]*>/gi;

    if ($audioCountElements.length > 0) {
      if (PP_Checklists.is_gutenberg_active()) {
        /**
         * For Gutenberg
         */
        wp.data.subscribe(function () {
          var content = PP_Checklists.getEditor().getEditedPostAttribute('content');

          if (typeof content === 'undefined') {
            return;
          }

          const matches = content.match(AUDIO_REGEX);
          var count = matches ? matches.length : 0;

          if (lastCount === count) {
            return;
          }

          $audioCountElements.each(function () {
            var $element = $(this);
            var requirementId = $element.attr('id').replace('pp-checklists-req-', '');

            // Get config for this specific requirement (try specific first, then fallback to original)
            var config = (typeof objectL10n_checklist_requirements !== 'undefined' && objectL10n_checklist_requirements.requirements[requirementId])
              ? objectL10n_checklist_requirements.requirements[requirementId]
              : (typeof objectL10n_checklist_requirements !== 'undefined' && objectL10n_checklist_requirements.requirements['audio_count'])
                ? objectL10n_checklist_requirements.requirements['audio_count']
                : null;

            if (config && config.value) {
              var min = parseInt(config.value[0]),
                max = parseInt(config.value[1]);

              $element.trigger(
                PP_Checklists.EVENT_UPDATE_REQUIREMENT_STATE,
                PP_Checklists.check_valid_quantity(count, min, max),
              );
            }
          });

          lastCount = count;
        });
      } else {
        /**
         * For the Classic Editor
         */
        var $content = $('#content');
        var editor;

        /**
         * Get the audio count from TinyMCE and update the status of the requirement
         */
        function update() {
          var text, count;

          if (typeof editor === 'undefined' || !editor || editor.isHidden()) {
            // For the text tab.
            text = $content.val();
          } else {
            // For the editor tab.
            text = editor.getContent({ format: 'raw' });
          }

          const matches = text.match(AUDIO_REGEX);
          count = matches ? matches.length : 0;

          if (lastCount === count) {
            return;
          }

          $audioCountElements.each(function () {
            var $element = $(this);
            var requirementId = $element.attr('id').replace('pp-checklists-req-', '');

            // Get config for this specific requirement (try specific first, then fallback to original)
            var config = (typeof objectL10n_checklist_requirements !== 'undefined' && objectL10n_checklist_requirements.requirements[requirementId])
              ? objectL10n_checklist_requirements.requirements[requirementId]
              : (typeof objectL10n_checklist_requirements !== 'undefined' && objectL10n_checklist_requirements.requirements['audio_count'])
                ? objectL10n_checklist_requirements.requirements['audio_count']
                : null;

            if (config && config.value) {
              var min = parseInt(config.value[0]),
                max = parseInt(config.value[1]);

              $element.trigger(
                PP_Checklists.EVENT_UPDATE_REQUIREMENT_STATE,
                PP_Checklists.check_valid_quantity(count, min, max),
              );
            }
          });

          lastCount = count;
        }

        // For the editor.
        $(document).on(PP_Checklists.EVENT_TINYMCE_LOADED, function (event, tinymce) {
          editor = tinymce.editors['content'];

          if (typeof editor !== 'undefined') {
            editor.onInit.add(function () {
              /**
               * Bind the words count update triggers.
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
