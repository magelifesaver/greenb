(function ($, document, PP_Checklists) {
  'use strict';

  $(function () {
    var lastCount = 0;
    var $rankMathScoreElements = $('[id^="pp-checklists-req-rank_math_score"]');

    if ($rankMathScoreElements.length > 0) {
      if (PP_Checklists.is_gutenberg_active()) {
        /**
         * For Gutenberg
         */
        wp.data.subscribe(function () {

          var score = $('.rank-math-toolbar-score .score-text').text().trim().split(' / ')[0];

          $rankMathScoreElements.each(function () {
            var $element = $(this);
            var requirementId = $element.attr('id').replace('pp-checklists-req-', '');

            // Get config for this specific requirement (try specific first, then fallback to original)
            var config = (typeof objectL10n_checklist_requirements !== 'undefined' && objectL10n_checklist_requirements.requirements[requirementId])
              ? objectL10n_checklist_requirements.requirements[requirementId]
              : (typeof objectL10n_checklist_requirements !== 'undefined' && objectL10n_checklist_requirements.requirements['rank_math_score'])
                ? objectL10n_checklist_requirements.requirements['rank_math_score']
                : null;

            if (config && config.value) {
              var min = parseInt(config.value[0]),
                max = parseInt(config.value[1]);

              $element.trigger(
                PP_Checklists.EVENT_UPDATE_REQUIREMENT_STATE,
                PP_Checklists.check_valid_quantity(score, min, max),
              );
            }
          });

          lastCount = score;
        });
      } else {
        /**
         * For the Classic Editor
         */
        var $content = $('#content');
        var lastCount = 0;
        var editor;

        /**
         * Check for Rank Math score and update the requirement
         */
        function update() {
          var scoreText = $('.rank-math-seo-score .score-text').text().trim();
          
          // Extract the numeric score from the text
          // The format is typically "SEO: XX / 100" where XX is the score
          var scoreMatch = scoreText.match(/(\d+)\s*\/\s*100/);
          var score = scoreMatch ? scoreMatch[1] : 0;
          
          // Convert to number
          score = parseInt(score) || 0;
          
          // Only update if the score has changed
          if (score !== lastCount) {
            
            $rankMathScoreElements.each(function () {
              var $element = $(this);
              var requirementId = $element.attr('id').replace('pp-checklists-req-', '');

              // Get config for this specific requirement (try specific first, then fallback to original)
              var config = (typeof objectL10n_checklist_requirements !== 'undefined' && objectL10n_checklist_requirements.requirements[requirementId])
                ? objectL10n_checklist_requirements.requirements[requirementId]
                : (typeof objectL10n_checklist_requirements !== 'undefined' && objectL10n_checklist_requirements.requirements['rank_math_score'])
                  ? objectL10n_checklist_requirements.requirements['rank_math_score']
                  : null;

              if (config && config.value) {
                var min = parseInt(config.value[0]),
                  max = parseInt(config.value[1]);

                $element.trigger(
                  PP_Checklists.EVENT_UPDATE_REQUIREMENT_STATE,
                  PP_Checklists.check_valid_quantity(score, min, max),
                );
              }
            });

            lastCount = score;
          }
        }

        $(document).on(PP_Checklists.EVENT_TIC, function (event) {
          update();
        });

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