(function ($, document, PP_Checklists) {
  'use strict';

  $(function () {
    // Exact time requirement
    var $publishTimeExactElements = $('[id^="pp-checklists-req-publish_time_exact"]');
    if ($publishTimeExactElements.length > 0) {
        // Gutenberg editor
        if (PP_Checklists.is_gutenberg_active()) {
          wp.data.subscribe(function () {
            var dateAttr = PP_Checklists.getEditor().getEditedPostAttribute('date');
            if (!dateAttr) {
              return;
            }
            var date = new Date(dateAttr);
            var h = ('0' + date.getHours()).slice(-2);
            var m = ('0' + date.getMinutes()).slice(-2);
            var currentTime = h + ':' + m;
  
            $publishTimeExactElements.each(function () {
              var $element = $(this);
              var requirementId = $element.attr('id').replace('pp-checklists-req-', '');

              // Get config for this specific requirement (try specific first, then fallback to original)
              var config = (typeof objectL10n_checklist_requirements !== 'undefined' && objectL10n_checklist_requirements.requirements[requirementId])
                ? objectL10n_checklist_requirements.requirements[requirementId]
                : (typeof objectL10n_checklist_requirements !== 'undefined' && objectL10n_checklist_requirements.requirements['publish_time_exact'])
                  ? objectL10n_checklist_requirements.requirements['publish_time_exact']
                  : null;

              if (config && config.value) {
                // Get configured time from localized data
                var configuredTime = config.value[0];
                var isValid = currentTime === configuredTime;

                $element.trigger(
                  PP_Checklists.EVENT_UPDATE_REQUIREMENT_STATE,
                  isValid
                );
              }
            });
          });
        } else {
          // Classic editor
          var lastTime = '';
          var $hh = $('#hh'),
              $mn = $('#mn');
  
          function updateTime() {
            var hh = $hh.val() || '';
            var mn = $mn.val() || '';
            if (!hh || !mn) {
              return;
            }
            var h = ('0' + parseInt(hh, 10)).slice(-2);
            var m = ('0' + parseInt(mn, 10)).slice(-2);
            var currentTime = h + ':' + m;
            if (currentTime === lastTime) {
              return;
            }

            $publishTimeExactElements.each(function () {
              var $element = $(this);
              var requirementId = $element.attr('id').replace('pp-checklists-req-', '');

              // Get config for this specific requirement (try specific first, then fallback to original)
              var config = (typeof objectL10n_checklist_requirements !== 'undefined' && objectL10n_checklist_requirements.requirements[requirementId])
                ? objectL10n_checklist_requirements.requirements[requirementId]
                : (typeof objectL10n_checklist_requirements !== 'undefined' && objectL10n_checklist_requirements.requirements['publish_time_exact'])
                  ? objectL10n_checklist_requirements.requirements['publish_time_exact']
                  : null;

              if (config && config.value) {
                var configuredTime = config.value[0];
                var isValid = currentTime === configuredTime;

                $element.trigger(
                  PP_Checklists.EVENT_UPDATE_REQUIREMENT_STATE,
                  isValid
                );
              }
            });
  
            lastTime = currentTime;
          }
  
          // Bind TinyMCE changes (for publish meta inputs in Gutenberg compatibility mode)
          $(document).on(PP_Checklists.EVENT_TINYMCE_LOADED, function (event, tinymce) {
            var editor = tinymce.editors['content'];
            if (editor && !editor.isHidden()) {
              editor.on('nodechange keyup', _.debounce(updateTime, 200));
            }
          });
          // Bind direct input changes on the time fields
          $hh.on('change keyup', _.debounce(updateTime, 200));
          $mn.on('change keyup', _.debounce(updateTime, 200));
  
          // Initial check
          updateTime();
        }
      }

    // Future time requirement
    var lastDateStatus = false;
    var $publishTimeFutureElements = $('[id^="pp-checklists-req-publish_time_future"]');

    if ($publishTimeFutureElements.length > 0) {
      if (PP_Checklists.is_gutenberg_active()) {
        /**
         * For Gutenberg
         */
        wp.data.subscribe(function () {
          // Get the scheduled date from Gutenberg
          var scheduledDate = PP_Checklists.getEditor().getEditedPostAttribute('date');
          
          if (typeof scheduledDate == 'undefined') {
            return;
          }
          
          // Parse scheduled date string (site timezone) into epoch ms
          var dateStr = scheduledDate;
          var parts = dateStr.split(/[-T:]/);
          var year = parseInt(parts[0], 10),
              mon  = parseInt(parts[1], 10) - 1,
              day  = parseInt(parts[2], 10),
              hour = parseInt(parts[3], 10),
              min  = parseInt(parts[4], 10);
          var scheduledEpoch = Date.UTC(year, mon, day, hour, min)
                                - (parseFloat(ppPublishTimeFuture.gmt_offset || 0) * 3600000);
          
          // Current epoch ms
          var currentEpoch = Date.now();
          
          // Check if scheduled time is in the future
          var isFuture = scheduledEpoch > currentEpoch;
          
          // Only update if the status has changed
          if (lastDateStatus === isFuture) {
            return;
          }
          
          $publishTimeFutureElements.each(function () {
            $(this).trigger(
              PP_Checklists.EVENT_UPDATE_REQUIREMENT_STATE,
              isFuture
            );
          });
          
          lastDateStatus = isFuture;
        });
      } else {
        /**
         * For the Classic Editor
         */
        var lastDateStatus = false;
        
        /**
         * Check if the publish date is in the future
         */
        function update() {
          var mm = $('#mm').val();
          var jj = $('#jj').val();
          var aa = $('#aa').val();
          var hh = $('#hh').val();
          var mn = $('#mn').val();

          // Compute scheduled epoch in site timezone
          var scheduledEpoch = Date.UTC(
            parseInt(aa, 10),
            parseInt(mm, 10) - 1,
            parseInt(jj, 10),
            parseInt(hh, 10),
            parseInt(mn, 10)
          ) - (parseFloat(ppPublishTimeFuture.gmt_offset || 0) * 3600000);

          var currentEpoch = Date.now();
          var isFuture = scheduledEpoch > currentEpoch;

          if (lastDateStatus === isFuture) {
            return;
          }

          $publishTimeFutureElements.each(function () {
            $(this).trigger(
              PP_Checklists.EVENT_UPDATE_REQUIREMENT_STATE,
              isFuture
            );
          });

          lastDateStatus = isFuture;
        }
        
        // Monitor changes to the date fields
        $('#mm, #jj, #aa, #hh, #mn').on('change', _.debounce(update, 200));
        
        // Run initial check
        update();
      }
    }
  });
})(jQuery, document, PP_Checklists);