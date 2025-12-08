/**
 * AIPKit Public Chat - Show Consent Box UI
 *
 * Creates and displays the consent box.
 */
(function() {
    'use strict';

    /**
     * Creates and displays the consent overlay if it doesn't exist.
     * @param {HTMLElement} mainChatEl - The .aipkit_chat_main element where the consent box will be inserted.
     * @param {object} config - Chatbot configuration (needs text.consentTitle, text.consentMessage, text.consentButton).
     * @param {function} handleConsentAgreeFunc - The function to call when the agree button is clicked.
     * @returns {HTMLElement|null} The created or existing consentBox DOM element, or null if mainChatEl is missing.
     */
    function aipkit_chatUI_showConsentBox(mainChatEl, config, handleConsentAgreeFunc) {
        if (!mainChatEl) {
            console.error(`AIPKit ShowConsentBox: mainChatEl not found.`);
            return null;
        }

        let consentBox = mainChatEl.querySelector('.aipkit_consent_overlay');

        if (!consentBox) {
            consentBox = document.createElement('div');
            consentBox.className = 'aipkit_consent_overlay';
            consentBox.innerHTML = `
                <h5 class="aipkit_consent_title">${config.text.consentTitle || 'Consent Required'}</h5>
                <p class="aipkit_consent_message">${config.text.consentMessage || 'Please agree to continue.'}</p>
                <button type="button" class="aipkit_btn aipkit_btn-primary aipkit_consent_agree_btn">
                    ${config.text.consentButton || 'I Agree'}
                </button>
            `;
            const inputArea = mainChatEl.querySelector('.aipkit_chat_input');
            if (inputArea) mainChatEl.insertBefore(consentBox, inputArea);
            else mainChatEl.appendChild(consentBox);

            const agreeButton = consentBox.querySelector('.aipkit_consent_agree_btn');
            if (agreeButton && typeof handleConsentAgreeFunc === 'function') {
                agreeButton.addEventListener('click', handleConsentAgreeFunc);
            }
        }
        consentBox.classList.remove('aipkit_hidden');
        return consentBox;
    }

    window.aipkit_chatUI_showConsentBox = aipkit_chatUI_showConsentBox;

})();