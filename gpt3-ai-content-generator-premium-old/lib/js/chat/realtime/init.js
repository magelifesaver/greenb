// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/lib/js/chat/realtime/init.js
// Status: MODIFIED

/**
 * AIPKit Realtime Voice - Main Initializer
 * Orchestrates the entire realtime voice agent session.
 */
(function() {
    'use strict';

    // This state is now managed per-instance via a closure in the initializer.
    let sessionState = {
        peerConnection: null,
        localStream: null,
        remoteAudioElement: null,
        dataChannel: null,
        isSessionActive: false,
        currentBotId: null,
        elements: null,
        config: null,
        mainUIState: null, // To access consentGiven
        uiInstance: null, // To call openPopup for consent
    };

    /**
     * Handles the click on the realtime voice button to start or stop a session.
     */
    async function handleRealtimeToggle(event) {
        if (event) {
            event.preventDefault();
            event.stopPropagation();
        }
        if (sessionState.isSessionActive) {
            stopSession();
        } else {
            await startSession();
        }
    }

    /**
     * Starts a new realtime voice session.
     */
    async function startSession() {
        // --- ADDED: Consent Check ---
        if (sessionState.config.requireConsentCompliance && !sessionState.mainUIState.consentGiven && !sessionState.config.directVoiceMode) {
            console.warn(`AIPKit Realtime (${sessionState.config.botId}): Cannot start session, consent required. Opening popup.`);
            if (sessionState.uiInstance && typeof sessionState.uiInstance.openPopup === 'function') {
                sessionState.uiInstance.openPopup();
                // User must agree, then click the trigger again. This is a clean one-time interruption.
            }
            return; // Stop the process
        }
        // --- END ADDED ---

        // Ensure a conversation UUID exists. A new realtime session starts a new conversation if one isn't active.
        if (!window.aipkit_current_conversation_uuid) {
          if (typeof window.aipkit_regenerateConversationUUID === "function") {
            window.aipkit_regenerateConversationUUID();
          } else {
            console.error(
              "AIPKit Realtime: Cannot start session, aipkit_regenerateConversationUUID function is missing."
            );
            // Update button state to error and show a message
            const triggerButton = sessionState.elements.triggerButton;
            const internalButton = sessionState.elements.container.querySelector('.aipkit_realtime_voice_agent_btn');
            const buttonToUpdate = sessionState.config.directVoiceMode ? triggerButton : internalButton;
            window.aipkit_realtimeUIHandler.setButtonState(buttonToUpdate, 'error', "Error: Cannot create session ID.");
            return; // Stop the process
          }
        }
        window.aipkit_is_fresh_session = false;

        const triggerButton = sessionState.elements.triggerButton;
        const internalButton = sessionState.elements.container.querySelector('.aipkit_realtime_voice_agent_btn');
        const buttonToUpdate = sessionState.config.directVoiceMode ? triggerButton : internalButton;
        const { ...otherElements } = sessionState.elements;
        window.aipkit_realtimeUIHandler.setButtonState(buttonToUpdate, 'connecting');
        if (!sessionState.config.directVoiceMode) {
            window.aipkit_realtimeUIHandler.toggleMainInputs(otherElements, true);
        }

        try {
            const response = await window.aipkit_frontendApiRequest(
                'aipkit_create_realtime_session',
                { bot_id: sessionState.currentBotId },
                sessionState.config
            );
            const ephemeralKey = response?.client_secret?.value;

            if (!ephemeralKey) {
                throw new Error('Could not retrieve a session key from the server.');
            }

            const pc = new RTCPeerConnection();
            sessionState.peerConnection = pc;

            sessionState.remoteAudioElement = window.aipkit_realtimeAudioHandler.setupRemoteStream(pc);
            sessionState.localStream = await window.aipkit_realtimeAudioHandler.startLocalStream(pc);
            
            const dataChannel = await window.aipkit_realtimeConnector.connect(ephemeralKey, sessionState.config, pc);
            sessionState.dataChannel = dataChannel;

            pc.onconnectionstatechange = () => {
                if (pc.connectionState === 'disconnected' || pc.connectionState === 'closed' || pc.connectionState === 'failed') {
                    stopSession('Connection lost.');
                }
            };

            sessionState.isSessionActive = true;
            window.aipkit_realtimeUIHandler.setButtonState(buttonToUpdate, 'listening');

            // --- ADDED: TTS for initial greeting ---
            const initialGreeting = sessionState.config.text?.initialGreeting;
            if (initialGreeting && typeof window.aipkit_handlePlayAction === 'function' && sessionState.config.ttsEnabled) {
                // We need a dummy button element for the play action handler to target for state management
                const dummyPlayButton = document.createElement('button');
                const playSvg = `<svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-player-play"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7 4v16l13 -8z" /></svg>`;
                dummyPlayButton.className = 'aipkit_action_btn aipkit_play_btn';
                dummyPlayButton.innerHTML = playSvg;
                const dummyBubble = document.createElement('div');
                dummyBubble.setAttribute('data-raw-text', initialGreeting);
                const dummyMessage = document.createElement('div');
                dummyMessage.appendChild(dummyBubble);
                dummyMessage.appendChild(dummyPlayButton);
                // We don't append this to the DOM, just use it to call the handler
                window.aipkit_handlePlayAction(dummyPlayButton);
            }
            // --- END ADDED ---

        } catch (error) {
            console.error('AIPKit Realtime: Failed to start session.', error);
            stopSession(`Error: ${error.message || 'Could not connect.'}`, true);
        }
    }

    /**
     * Stops the current realtime voice session and cleans up resources.
     * @param {string|null} message Message to display on the button tooltip.
     * @param {boolean} isError Indicates if the stop was due to an error.
     */
    function stopSession(message = null, isError = false) {
        if (sessionState.peerConnection) {
            sessionState.peerConnection.close();
            sessionState.peerConnection = null;
        }
        if (sessionState.localStream) {
            window.aipkit_realtimeAudioHandler.stopLocalStream(sessionState.localStream);
            sessionState.localStream = null;
        }
        if (sessionState.remoteAudioElement) {
            sessionState.remoteAudioElement.srcObject = null;
            sessionState.remoteAudioElement = null;
        }
        if (sessionState.currentAudio && typeof window.aipkit_tts_stopCurrentAudio === 'function') {
            window.aipkit_tts_stopCurrentAudio();
        }

        sessionState.isSessionActive = false;
        
        const triggerButton = sessionState.elements.triggerButton;
        const internalButton = sessionState.elements.container.querySelector('.aipkit_realtime_voice_agent_btn');
        const buttonToUpdate = sessionState.config.directVoiceMode ? triggerButton : internalButton;
        const { ...otherElements } = sessionState.elements;
        window.aipkit_realtimeUIHandler.setButtonState(buttonToUpdate, isError ? 'error' : 'idle', message);
        if (!sessionState.config.directVoiceMode) {
            window.aipkit_realtimeUIHandler.toggleMainInputs(otherElements, false);
        }
    }

    /**
     * Initializes the realtime voice agent feature for a chat instance.
     * @param {object} elements The chat UI elements.
     * @param {object} config The chatbot configuration.
     * @param {object} state The main UI state object.
     * @param {object} actions The main UI actions object.
     * @param {object} uiInstance The main chat UI public API instance.
     */
    function aipkit_initRealtimeVoiceAgent(elements, config, state, actions, uiInstance) {
        // Find the correct button to attach the listener to.
        const internalButton = elements.container.querySelector('.aipkit_realtime_voice_agent_btn');
        const wrapper = elements.container.closest('.aipkit_popup_wrapper');
        const triggerButton = wrapper ? wrapper.querySelector('.aipkit_popup_trigger') : null;

        if (!internalButton || (!triggerButton && config.popupEnabled)) {
            return;
        }

        // Store references to the main UI instance for later use (e.g., opening popup for consent)
        sessionState.uiInstance = uiInstance;
        sessionState.mainUIState = state;
        sessionState.config = config;
        sessionState.elements = { ...elements, triggerButton }; // Store all elements including the trigger
        sessionState.currentBotId = config.botId;

        // Determine which button gets the main toggle handler
        if (config.directVoiceMode && triggerButton) {
            // Direct Voice Mode: The popup trigger becomes the main controller
            triggerButton.addEventListener('click', handleRealtimeToggle);
            internalButton.style.display = 'none'; // Hide the button inside the chat window
        } else {
            // Standard Mode: The button inside the chat window is the controller
            internalButton.addEventListener('click', handleRealtimeToggle);
        }
    }

    // Expose the main initializer
    window.aipkit_initRealtimeVoiceAgent = aipkit_initRealtimeVoiceAgent;

})();