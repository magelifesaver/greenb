(function ($, document, PP_Checklists) {
  'use strict';

  $(function () {
    var lastCount = 0;
    var $videoCountElements = $('[id^="pp-checklists-req-video_count"]');

    const VIDEO_REGEX = /<video[\s\S]*?<\/video>|<iframe[^>]+src\s*=\s*['\"][^'\"]*(?:youtube\.com|youtu\.be|vimeo\.com)[^'\"]*['\"][^>]*>|<figure[^>]+wp-block-embed[^>]*is-provider-(?:youtube|vimeo)[^>]*>|<a[^>]+href\s*=\s*['\"][^'\"]+\.(?:mp4|mov|webm|m4v|ogg|ogv)(?:\?[^'\"]*)?['\"][^>]*>/gi;

    if ($videoCountElements.length > 0) {
      if (PP_Checklists.is_gutenberg_active()) {
        /**
         * For Gutenberg
         */
        wp.data.subscribe(function () {
          var content = PP_Checklists.getEditor().getEditedPostAttribute('content');
          if (typeof content === 'undefined') {
            return;
          }

          const matches = content.match(VIDEO_REGEX);
          var count = matches ? matches.length : 0;
          
          if (lastCount === count) {
            return;
          }

          $videoCountElements.each(function () {
            var $element = $(this);
            var requirementId = $element.attr('id').replace('pp-checklists-req-', '');

            // Get config for this specific requirement (try specific first, then fallback to original)
            var config = (typeof objectL10n_checklist_requirements !== 'undefined' && objectL10n_checklist_requirements.requirements[requirementId])
              ? objectL10n_checklist_requirements.requirements[requirementId]
              : (typeof objectL10n_checklist_requirements !== 'undefined' && objectL10n_checklist_requirements.requirements['video_count'])
                ? objectL10n_checklist_requirements.requirements['video_count']
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

        function updateVideo() {
          var text;
          if (typeof editor === 'undefined' || !editor || editor.isHidden()) {
            text = $content.val();
          } else {
            text = editor.getContent({ format: 'raw' });
          }

          const matches = text.match(VIDEO_REGEX);
          var count = matches ? matches.length : 0;

          if (lastCount === count) {
            return;
          }

          $videoCountElements.each(function () {
            var $element = $(this);
            var requirementId = $element.attr('id').replace('pp-checklists-req-', '');

            // Get config for this specific requirement (try specific first, then fallback to original)
            var config = (typeof objectL10n_checklist_requirements !== 'undefined' && objectL10n_checklist_requirements.requirements[requirementId])
              ? objectL10n_checklist_requirements.requirements[requirementId]
              : (typeof objectL10n_checklist_requirements !== 'undefined' && objectL10n_checklist_requirements.requirements['video_count'])
                ? objectL10n_checklist_requirements.requirements['video_count']
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
  
        $(document).on(PP_Checklists.EVENT_TINYMCE_LOADED, function (event, tinymce) {
          editor = tinymce.editors['content'];
          if (typeof editor !== 'undefined') {
            editor.onInit.add(function () {
              if (editor.id !== 'content') {
                return;
              }
              editor.on('nodechange keyup', _.debounce(updateVideo, 200));
            });
          }
        });
  
        $content.on('input keyup', _.debounce(updateVideo, 200));
        updateVideo();
      }
    }
  });
})(jQuery, document, PP_Checklists);
