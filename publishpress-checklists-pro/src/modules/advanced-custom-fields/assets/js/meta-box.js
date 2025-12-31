/**
 * @package PublishPress
 * @author PublishPress
 *
 * Copyright (C) 2021 PublishPress
 *
 * ------------------------------------------------------------------------------
 * Based on Edit Flow
 * Author: Daniel Bachhuber, Scott Bressler, Mohammad Jangda, Automattic, and
 * others
 * Copyright (c) 2009-2016 Mohammad Jangda, Daniel Bachhuber, et al.
 * ------------------------------------------------------------------------------
 *
 * This file is part of PublishPress
 *
 * PublishPress is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * PublishPress is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with PublishPress.  If not, see <http://www.gnu.org/licenses/>.
 */

(function ($, document, PP_Checklists, wpApiSettings) {
  'use strict';

  $(function () {
    /**
     * Input Text
     */
    const requirements = Object.fromEntries(
      Object.entries(objectL10n_checklist_requirements.requirements).filter((req) =>
        String(req[1].type).startsWith('acf_base'),
      ),
    );

    for (const requirementKey in requirements) {
      const labelElements = $(`[id^="pp-checklists-req-${requirementKey}"]`);

      if (labelElements.length > 0) {
        $(document).on(PP_Checklists.EVENT_TIC, function (event) {
          const requirement = requirements[requirementKey];
          const idElement = String(requirementKey).split('__')[1];
          const inputElement = $(`[name="acf[${idElement}]"]`);
          const inputValue = inputElement.val();
          const min = Number(requirement.value[0]);
          const max = Number(requirement.value[1]);
          const count = inputValue.length;
          if (requirement.type === 'acf_base_counter') {
            labelElements.each(function () {
              $(this).trigger(
                PP_Checklists.EVENT_UPDATE_REQUIREMENT_STATE,
                PP_Checklists.check_valid_quantity(count, min, max),
              );
            });
          } else {
            // Validate email if the input is an email field
            if (inputElement.attr('type') === 'email') {
              const isValidEmail = validateEmail(inputValue);
              labelElements.each(function () {
                $(this).trigger(PP_Checklists.EVENT_UPDATE_REQUIREMENT_STATE, isValidEmail);
              });
            } else if (inputElement.attr('type') === 'url') {
              const isValidUrl = validateUrl(inputValue);
              labelElements.each(function () {
                $(this).trigger(PP_Checklists.EVENT_UPDATE_REQUIREMENT_STATE, isValidUrl);
              });
            } else {
              labelElements.each(function () {
                $(this).trigger(PP_Checklists.EVENT_UPDATE_REQUIREMENT_STATE, Boolean(inputValue));
              });
            }
          }
        });
      }
    }
  });

  /**
   * Validate email address using regular expression
   * @param {string} email - The email address to validate
   * @returns {boolean} - Returns true if the email is valid, false otherwise
   */
  function validateEmail(email) {
    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailPattern.test(email);
  }

  /**
   * Validate URL using regular expression
   * @param {string} url - The URL to validate
   * @returns {boolean} - Returns true if the URL is valid, false otherwise
   */
  function validateUrl(url) {
    const urlPattern = /^(https?|ftp):\/\/[^\s/$.?#].[^\s]*$/i;
    return urlPattern.test(url);
  }
})(jQuery, document, PP_Checklists, wpApiSettings);
