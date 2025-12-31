(function ($, document, PP_Checklists) {
  'use strict';

  $(function () {

    /* Table header */
    var $tableHeaderElements = $('[id^="pp-checklists-req-table_header"]');
    if ($tableHeaderElements.length > 0) {
      if (PP_Checklists.is_gutenberg_active()) {
        /**
         * For Gutenberg (Table Header Check)
         */
        wp.data.subscribe(function () {
          var content = PP_Checklists.getEditor().getEditedPostAttribute('content');

          if (typeof content == 'undefined') {
            return;
          }

          const tableRegex = /<table[^>]*>(.*?)<\/table>/gis; // 's' for dotall
          const tables = Array.from(content.matchAll(tableRegex));
          let isValid = true; // Assume true if no tables or all tables are valid

          if (tables.length === 0) {
            // isValid remains true
          } else {
            isValid = true; // Overall validity for all tables
            for (const tableMatch of tables) {
              const tableInnerHtml = tableMatch[1]; // Content inside <table> tags

              const hasTh = /<th[>\s]/.test(tableInnerHtml); // Check for <th > or <th attributes...>

              if (hasTh) {
                isValid = true;
              } else {
                isValid = false; // If any table is invalid, the whole requirement fails
                break; // No need to check other tables if one fails
              }
            }
          }


          $tableHeaderElements.each(function () {
            $(this).trigger(
              PP_Checklists.EVENT_UPDATE_REQUIREMENT_STATE,
              isValid
            );
          });
        });
      } else {
        /**
         * For the Classic Editor (Table Header Check)
         */
        var $classicContent = $('#content'); // Use a different variable name to avoid confusion if $content is used elsewhere
        var classicEditorInstance; // To store the TinyMCE editor instance

        function updateTableHeaderCheckClassic() {
          var text;

          if (typeof classicEditorInstance == 'undefined' || !classicEditorInstance || classicEditorInstance.isHidden()) {
            text = $classicContent.val();
          } else {
            text = classicEditorInstance.getContent({ format: 'raw' });
          }

          const tableRegex = /<table[^>]*>(.*?)<\/table>/gis;
          const tables = Array.from(text.matchAll(tableRegex));
          let isValid = true;

          if (tables.length === 0) {
            isValid = true;
          } else {
            isValid = true;
            for (const tableMatch of tables) {
              const tableInnerHtml = tableMatch[1];

              const hasTh = /<th[>\s]/.test(tableInnerHtml);

              if (hasTh) {
                isValid = true;
              } else {
                isValid = false;
                break;
              }
            }
          }

          $tableHeaderElements.each(function () {
            $(this).trigger(
              PP_Checklists.EVENT_UPDATE_REQUIREMENT_STATE,
              isValid
            );
          });
        }

        $(document).on(PP_Checklists.EVENT_TINYMCE_LOADED, function (event, tinymce) {
          classicEditorInstance = tinymce.editors['content'];

          if (typeof classicEditorInstance !== 'undefined') {
            classicEditorInstance.onInit.add(function () {
              if (classicEditorInstance.id !== 'content') {
                return;
              }

              classicEditorInstance.on('nodechange keyup', _.debounce(updateTableHeaderCheckClassic, 200));
            });
          }
        });

        $classicContent.on('input keyup', _.debounce(updateTableHeaderCheckClassic, 200));
        updateTableHeaderCheckClassic(); // Initial check
      }
    }

    /* Heading in hierarchy */
    var $headingHierarchyElements = $('[id^="pp-checklists-req-heading_in_hierarchy"]');
    if ($headingHierarchyElements.length > 0) {
      if (PP_Checklists.is_gutenberg_active()) {
        /**
         * For Gutenberg (Heading Hierarchy Check)
         */
        wp.data.subscribe(function () {
          var content = PP_Checklists.getEditor().getEditedPostAttribute('content');

          if (typeof content == 'undefined') {
            return;
          }

          const headingRegex = /<(h[1-6])[^>]*>.*?<\/\1>/gi;
          const matches = Array.from(content.matchAll(headingRegex));
          let isValid = true;
          let previousLevel = 0;

          if (matches.length === 0) {
            // isValid remains true
          } else {
            for (const match of matches) {
              const currentLevel = parseInt(match[1].charAt(1), 10);

              if (previousLevel === 0) { // First heading
                previousLevel = currentLevel;
              } else {
                if (currentLevel > previousLevel + 1) {
                  isValid = false;
                  break;
                }
                previousLevel = currentLevel;
              }
            }
          }

          $headingHierarchyElements.each(function () {
            $(this).trigger(
              PP_Checklists.EVENT_UPDATE_REQUIREMENT_STATE,
              isValid
            );
          });
        });
      } else {
        /**
         * For the Classic Editor (Heading Hierarchy Check)
         */
        var $classicContentHeadings = $('#content');
        var classicEditorInstanceHeadings;

        function updateHeadingHierarchyCheckClassic() {
          var text;

          if (typeof classicEditorInstanceHeadings == 'undefined' || !classicEditorInstanceHeadings || classicEditorInstanceHeadings.isHidden()) {
            text = $classicContentHeadings.val();
          } else {
            text = classicEditorInstanceHeadings.getContent({ format: 'raw' });
          }

          const headingRegex = /<(h[1-6])[^>]*>.*?<\/\1>/gi;
          const matches = Array.from(text.matchAll(headingRegex));
          let isValid = true;
          let previousLevel = 0;

          if (matches.length === 0) {
            // isValid remains true
          } else {
            for (const match of matches) {
              const currentLevel = parseInt(match[1].charAt(1), 10);

              if (previousLevel === 0) { // First heading
                previousLevel = currentLevel;
              } else {
                if (currentLevel > previousLevel + 1) {
                  isValid = false;
                  break;
                }
                previousLevel = currentLevel;
              }
            }
          }

          $headingHierarchyElements.each(function () {
            $(this).trigger(
              PP_Checklists.EVENT_UPDATE_REQUIREMENT_STATE,
              isValid
            );
          });
        }

        $(document).on(PP_Checklists.EVENT_TINYMCE_LOADED, function (event, tinymce) {
          classicEditorInstanceHeadings = tinymce.editors['content'];

          if (typeof classicEditorInstanceHeadings !== 'undefined') {
            classicEditorInstanceHeadings.onInit.add(function () {
              if (classicEditorInstanceHeadings.id !== 'content') {
                return;
              }
              classicEditorInstanceHeadings.on('nodechange keyup', _.debounce(updateHeadingHierarchyCheckClassic, 200));
            });
          }
        });

        $classicContentHeadings.on('input keyup', _.debounce(updateHeadingHierarchyCheckClassic, 200));
        updateHeadingHierarchyCheckClassic(); // Initial check
      }
    }

    /* Single H1 per page */
    var $singleH1Elements = $('[id^="pp-checklists-req-single_h1_per_page"]');
    if ($singleH1Elements.length > 0) {
      if (PP_Checklists.is_gutenberg_active()) {
        /**
         * For Gutenberg (Single H1 per page Check)
         */
        wp.data.subscribe(function () {
          var content = PP_Checklists.getEditor().getEditedPostAttribute('content');

          if (typeof content == 'undefined') {
            return;
          }

          // Regex to find all H1 tags
          const h1Regex = /<h1[^>]*>.*?<\/h1>/gi;
          const matches = content.match(h1Regex); // Using match to get an array of matches or null
          let isValid = true;

          // If matches is null (no H1s) or length is 1, it's valid.
          // Fails if more than 1 H1 tag is found.
          if (matches && matches.length > 1) {
            isValid = false;
          }
          // Note: If matches is null (0 H1s) or matches.length is 1, isValid remains true.

          $singleH1Elements.each(function () {
            $(this).trigger(
              PP_Checklists.EVENT_UPDATE_REQUIREMENT_STATE,
              isValid
            );
          });
        });
      } else {
        /**
         * For the Classic Editor (Single H1 per page Check)
         */
        var $classicContentSingleH1 = $('#content');
        var classicEditorInstanceSingleH1;

        function updateSingleH1CheckClassic() {
          var text;

          if (typeof classicEditorInstanceSingleH1 == 'undefined' || !classicEditorInstanceSingleH1 || classicEditorInstanceSingleH1.isHidden()) {
            text = $classicContentSingleH1.val();
          } else {
            text = classicEditorInstanceSingleH1.getContent({ format: 'raw' });
          }

          // Regex to find all H1 tags
          const h1Regex = /<h1[^>]*>.*?<\/h1>/gi;
          const matches = text.match(h1Regex); // Using match to get an array of matches or null
          let isValid = true;

          // If matches is null (no H1s) or length is 1, it's valid.
          // Fails if more than 1 H1 tag is found.
          if (matches && matches.length > 1) {
            isValid = false;
          }
          // Note: If matches is null (0 H1s) or matches.length is 1, isValid remains true.

          $singleH1Elements.each(function () {
            $(this).trigger(
              PP_Checklists.EVENT_UPDATE_REQUIREMENT_STATE,
              isValid
            );
          });
        }

        $(document).on(PP_Checklists.EVENT_TINYMCE_LOADED, function (event, tinymce) {
          classicEditorInstanceSingleH1 = tinymce.editors['content'];

          if (typeof classicEditorInstanceSingleH1 !== 'undefined') {
            classicEditorInstanceSingleH1.onInit.add(function () {
              if (classicEditorInstanceSingleH1.id !== 'content') {
                return;
              }
              classicEditorInstanceSingleH1.on('nodechange keyup', _.debounce(updateSingleH1CheckClassic, 200));
            });
          }
        });

        $classicContentSingleH1.on('input keyup', _.debounce(updateSingleH1CheckClassic, 200));
        updateSingleH1CheckClassic(); // Initial check
      }
    }
  });
})(jQuery, document, PP_Checklists);