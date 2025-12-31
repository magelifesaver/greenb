(function ($, document, PP_Checklists) {
  'use strict';

  $(function () {
    var lastSeoScore = 0;
    var lastHeadlineScore = 0;

    /**
     * Monitor SEO Score
     */
    var $seoScoreElements = $('[id^="pp-checklists-req-all_in_one_seo_score"]');
    if ($seoScoreElements.length > 0) {
      if (PP_Checklists.is_gutenberg_active()) {
        /**
         * For Gutenberg
         */
        var seoScoreMonitor = setInterval(function() {
          var scoreElement = $('#aioseo-post-score');
          if (scoreElement.length > 0) {
            var score = parseInt(scoreElement.text().trim(), 10);
            
            // Only update if the score has changed
            if (!isNaN(score) && score !== lastSeoScore) {
              $seoScoreElements.each(function () {
                var $element = $(this);
                var requirementId = $element.attr('id').replace('pp-checklists-req-', '');

                // Get config for this specific requirement (try specific first, then fallback to original)
                var config = (typeof objectL10n_checklist_requirements !== 'undefined' && objectL10n_checklist_requirements.requirements[requirementId])
                  ? objectL10n_checklist_requirements.requirements[requirementId]
                  : (typeof objectL10n_checklist_requirements !== 'undefined' && objectL10n_checklist_requirements.requirements['all_in_one_seo_score'])
                    ? objectL10n_checklist_requirements.requirements['all_in_one_seo_score']
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

              lastSeoScore = score;
            }
          }
        }, 1000); // Check every second
      } else {
        /**
         * For the Classic Editor
         */
        var classicSeoScoreMonitor = setInterval(function() {
          var scoreElement = $('#aioseo-post-score');
          if (scoreElement.length > 0) {
            // Extract the numeric score from the text (format: "62/100")
            var scoreText = scoreElement.text().trim();
            var scoreMatch = scoreText.match(/(\d+)\s*\/\s*100/);
            var score = scoreMatch ? parseInt(scoreMatch[1], 10) : 0;
            
            // Only update if the score has changed
            if (!isNaN(score) && score !== lastSeoScore) {
              $seoScoreElements.each(function () {
                var $element = $(this);
                var requirementId = $element.attr('id').replace('pp-checklists-req-', '');

                // Get config for this specific requirement (try specific first, then fallback to original)
                var config = (typeof objectL10n_checklist_requirements !== 'undefined' && objectL10n_checklist_requirements.requirements[requirementId])
                  ? objectL10n_checklist_requirements.requirements[requirementId]
                  : (typeof objectL10n_checklist_requirements !== 'undefined' && objectL10n_checklist_requirements.requirements['all_in_one_seo_score'])
                    ? objectL10n_checklist_requirements.requirements['all_in_one_seo_score']
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

              lastSeoScore = score;
            }
          }
        }, 1000); // Check every second
      }
    }

    /**
     * Monitor Headline Analyzer Score (Gutenberg only)
     */
    var $headlineScoreElements = $('[id^="pp-checklists-req-all_in_one_seo_headline_score"]');
    if ($headlineScoreElements.length > 0) {
      if (PP_Checklists.is_gutenberg_active()) {
        var headlineScoreMonitor = setInterval(function() {
          var scoreElement = $('#aioseo-headline-analyzer-sidebar-button-score');
          if (scoreElement.length > 0) {
            // Extract the numeric score from the text (format: "28/100")
            var scoreText = scoreElement.text().trim();
            var scoreMatch = scoreText.match(/(\d+)\s*\/\s*100/);
            var score = scoreMatch ? parseInt(scoreMatch[1], 10) : 0;
            
            // Only update if the score has changed
            if (!isNaN(score) && score !== lastHeadlineScore) {
              $headlineScoreElements.each(function () {
                var $element = $(this);
                var requirementId = $element.attr('id').replace('pp-checklists-req-', '');

                // Get config for this specific requirement (try specific first, then fallback to original)
                var config = (typeof objectL10n_checklist_requirements !== 'undefined' && objectL10n_checklist_requirements.requirements[requirementId])
                  ? objectL10n_checklist_requirements.requirements[requirementId]
                  : (typeof objectL10n_checklist_requirements !== 'undefined' && objectL10n_checklist_requirements.requirements['all_in_one_seo_headline_score'])
                    ? objectL10n_checklist_requirements.requirements['all_in_one_seo_headline_score']
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

              lastHeadlineScore = score;
            }
          }
        }, 1000); // Check every second
      }
      // No need to monitor in Classic Editor as headline analyzer is only available in Gutenberg
    }
  });
})(jQuery, document, PP_Checklists);