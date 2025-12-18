/**
 * AIPKit Public Chat - Hide Consent Box UI
 *
 * Hides the consent box.
 */
(function() {
    'use strict';

    /**
     * Hides the provided consent box element.
     * @param {HTMLElement} consentBoxElement - The consent box DOM element.
     */
    function aipkit_chatUI_hideConsentBox(consentBoxElement) {
        if (consentBoxElement) {
            consentBoxElement.classList.add('aipkit_hidden');
        }
    }

    window.aipkit_chatUI_hideConsentBox = aipkit_chatUI_hideConsentBox;

})();