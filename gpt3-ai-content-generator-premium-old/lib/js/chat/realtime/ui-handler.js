// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/lib/js/chat/realtime/ui-handler.js
// Status: MODIFIED

/**
 * AIPKit Realtime Voice - UI Handler
 * Manages the visual state of the realtime voice agent button and related UI elements.
 */
(function () {
  "use strict";
  
  // Define SVGs at the top of the IIFE
  const volumeOnSvg = `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-volume"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M15 8a5 5 0 0 1 0 8" /><path d="M17.7 5a9 9 0 0 1 0 14" /><path d="M6 15h-2a1 1 0 0 1 -1 -1v-4a1 1 0 0 1 1 -1h2l3.5 -4.5a.8 .8 0 0 1 1.5 .5v14a.8 .8 0 0 1 -1.5 .5l-3.5 -4.5" /></svg>`;
  const pauseIconSvg = `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-player-pause"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M6 5m0 1a1 1 0 0 1 1 -1h2a1 1 0 0 1 1 1v12a1 1 0 0 1 -1 1h-2a1 1 0 0 1 -1 -1z" /><path d="M14 5m0 1a1 1 0 0 1 1 -1h2a1 1 0 0 1 1 1v12a1 1 0 0 1 -1 1h-2a1 1 0 0 1 -1 -1z" /></svg>`;
  const warningIconSvg = `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-alert-square-rounded"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 3c7.2 0 9 1.8 9 9s-1.8 9 -9 9s-9 -1.8 -9 -9s1.8 -9 9 -9z" /><path d="M12 8v4" /><path d="M12 16h.01" /></svg>`;

  /**
   * Sets the visual state of the voice agent button.
   * @param {HTMLElement} button The voice agent button element.
   * @param {string} state 'idle', 'connecting', 'listening', 'speaking', 'error'.
   * @param {string|null} message Optional message for tooltip.
   */
  function setButtonState(button, state, message = null) {
    if (!button) return;

    button.classList.remove(
      "is-connecting",
      "is-listening",
      "is-speaking",
      "is-error"
    );

    // Clear previous content (icon or spinner)
    button.innerHTML = ''; 

    switch (state) {
      case "connecting":
        button.classList.add("is-connecting");
        const newSpinner = document.createElement('span');
        newSpinner.className = 'aipkit_spinner';
        newSpinner.style.display = 'inline-block';
        button.appendChild(newSpinner);
        button.title = message || "Connecting...";
        button.disabled = true;
        break;
      case "listening":
        button.classList.add("is-listening");
        button.innerHTML = pauseIconSvg;
        button.title = message || "Listening... (Click to stop)";
        button.disabled = false;
        break;
      case "speaking":
        button.classList.add("is-speaking");
        button.innerHTML = volumeOnSvg;
        button.title = message || "AI is speaking... (Click to stop)";
        button.disabled = false;
        break;
      case "error":
        button.classList.add("is-error");
        button.innerHTML = warningIconSvg;
        button.title = message || "Error. Click to retry.";
        button.disabled = false;
        break;
      case "idle":
      default:
        button.innerHTML = volumeOnSvg;
        button.title = message || "Start Voice Conversation";
        button.disabled = false;
        break;
    }
  }

  /**
   * Toggles the disabled state of the main chat input field and send button.
   * @param {object} elements The chat UI elements object.
   * @param {boolean} disable True to disable, false to enable.
   */
  function toggleMainInputs(elements, disable) {
    if (elements.inputField) {
      elements.inputField.disabled = disable;
    }
    if (elements.actionButton) {
      elements.actionButton.disabled = disable;
    }
    // Also disable other input actions
    if (elements.inputActionButton) {
      elements.inputActionButton.disabled = disable;
    }
     if (elements.voiceInputButton) {
      elements.voiceInputButton.disabled = disable;
    }
  }

  window.aipkit_realtimeUIHandler = {
    setButtonState,
    toggleMainInputs,
  };
})();