/**
 * AIPKit Public Chat - Consent UI Initializer (Orchestrator)
 *
 * Initializes the consent box UI and logic for a chat instance.
 * This replaces the old chat-ui-consent.js file.
 */
(function() {
    'use strict';

    /**
     * Initializes the consent box UI and logic for a chat instance.
     * @param {HTMLElement} mainChatEl - The .aipkit_chat_main element where the consent box will be inserted.
     * @param {object} config - Chatbot configuration (needs text.consentTitle, text.consentMessage, text.consentButton, requireConsentCompliance, botId).
     * @param {object} stateRef - A reference to the shared state object (will be mutated with consentGiven).
     * @param {function} onConsentGivenCallback - Callback to execute when consent is given (e.g., to enable input).
     * @returns {{showConsentBoxIfNeeded: function(): boolean, isConsentGiven: function(): boolean}|null} Object with methods or null if setup fails.
     */
    function aipkit_initConsentUI(mainChatEl, config, stateRef, onConsentGivenCallback) {
        const botId = config.botId;
        const consentStorageKey = `aipkit_chatbot_consent_given_${botId}`;

        // Initialize consentGiven in stateRef based on localStorage
        stateRef.consentGiven = localStorage.getItem(consentStorageKey) === 'true';

        let consentBoxElement = null; // Stores the DOM element of the consent box for this instance

        // Define local versions of helper functions that capture `consentBoxElement` in their closure
        function localHandleConsentAgree() {
            if (typeof window.aipkit_chatUI_handleConsentAgree === 'function') {
                window.aipkit_chatUI_handleConsentAgree(stateRef, consentStorageKey, onConsentGivenCallback, mainChatEl, localHideConsentBox, consentBoxElement);
            } else {
                console.error(`AIPKit Consent UI Init (${botId}): aipkit_chatUI_handleConsentAgree helper function not found.`);
            }
        }

        function localShowConsentBox() {
            if (typeof window.aipkit_chatUI_showConsentBox === 'function') {
                consentBoxElement = window.aipkit_chatUI_showConsentBox(mainChatEl, config, localHandleConsentAgree);
            } else {
                console.error(`AIPKit Consent UI Init (${botId}): aipkit_chatUI_showConsentBox helper function not found.`);
            }
        }

        function localHideConsentBox() {
            if (consentBoxElement && typeof window.aipkit_chatUI_hideConsentBox === 'function') {
                window.aipkit_chatUI_hideConsentBox(consentBoxElement);
            } else if (consentBoxElement) {
                 console.error(`AIPKit Consent UI Init (${botId}): aipkit_chatUI_hideConsentBox helper function not found.`);
            }
        }

        // Initial check: Show consent box if required and not yet given
        if (config.requireConsentCompliance && !stateRef.consentGiven) {
            localShowConsentBox();
        }
        
        return {
            showConsentBoxIfNeeded: () => {
                if (config.requireConsentCompliance && !stateRef.consentGiven) {
                    localShowConsentBox();
                    return true; // Indicates box was shown
                }
                return false; // Indicates box was not shown
            },
            isConsentGiven: () => stateRef.consentGiven
        };
    }

    window.aipkit_initConsentUI = aipkit_initConsentUI;

})();