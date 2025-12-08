/**
 * AIPKit Public Chat - Handle Consent Agree Action
 *
 * Logic for when the user clicks the "I Agree" button on the consent overlay.
 */
(function() {
    'use strict';

    /**
     * Handles the agree action for consent.
     * @param {object} stateRef - A reference to the shared state object (will be mutated with consentGiven).
     * @param {string} consentStorageKey - The localStorage key for storing consent.
     * @param {function} onConsentGivenCallback - Callback to execute when consent is given.
     * @param {HTMLElement} mainChatEl - The .aipkit_chat_main element (for dispatching event).
     * @param {function} hideConsentBoxFunc - Function to hide the consent box.
     * @param {HTMLElement} consentBoxToHide - The DOM element of the consent box to hide.
     */
    function aipkit_chatUI_handleConsentAgree(stateRef, consentStorageKey, onConsentGivenCallback, mainChatEl, hideConsentBoxFunc, consentBoxToHide) {
        stateRef.consentGiven = true;
        localStorage.setItem(consentStorageKey, 'true');

        if (typeof hideConsentBoxFunc === 'function' && consentBoxToHide) {
            hideConsentBoxFunc(consentBoxToHide);
        }

        const botId = mainChatEl.closest('[data-bot-id]')?.dataset.botId || 'unknown';

        if (typeof onConsentGivenCallback === 'function') {
            onConsentGivenCallback();
        }

        const chatContainer = mainChatEl.closest('.aipkit_chat_container');
        if (chatContainer) {
            chatContainer.dispatchEvent(new CustomEvent('aipkit:consentGiven'));
        }
    }

    window.aipkit_chatUI_handleConsentAgree = aipkit_chatUI_handleConsentAgree;

})();