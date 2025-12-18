// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/lib/js/chat/realtime/webrtc-connector.js
// Status: MODIFIED

/**
 * AIPKit Realtime Voice - WebRTC Connector
 * Handles the low-level WebRTC connection and SDP exchange with OpenAI.
 */
(function() {
    'use strict';

    /**
     * Connects to OpenAI's Realtime API via WebRTC.
     * @param {string} ephemeralKey The short-lived API key from our server.
     * @param {object} config The chatbot configuration object.
     * @param {RTCPeerConnection} peerConnection The pre-configured RTCPeerConnection instance.
     * @returns {Promise<RTCDataChannel>} A promise that resolves with the data channel.
     */
    async function connect(ephemeralKey, config, peerConnection) {
        const dc = peerConnection.createDataChannel('aipkit-events');

        let turnState = { userTranscript: '', botTranscript: '' };

        const logSessionTurn = async (responsePayload) => {
            const usageData = responsePayload?.usage || null;
            // Debug: Log what is about to be logged
            if (!turnState.userTranscript && !turnState.botTranscript) {
                return;
            }
            const logData = {
                user_transcript: turnState.userTranscript,
                bot_transcript: turnState.botTranscript,
                usage_data: usageData ? JSON.stringify(usageData) : null,
            };
            try {
                await window.aipkit_frontendApiRequest('aipkit_log_realtime_session_turn', logData, config);
            } catch(err) {
                console.error('AIPKit Realtime: Failed to log session turn.', err);
            } finally {
                // Reset for the next turn
                turnState = { userTranscript: '', botTranscript: '' };
            }
        };

        dc.onopen = () => console.log('AIPKit Realtime: Data channel opened.');
        dc.onmessage = (e) => {
            try {
                const event = JSON.parse(e.data);
                if (event.type === 'input.transcription' && event.transcription) {
                    // Accumulate user transcript for this turn
                    turnState.userTranscript += event.transcription.trim() + ' ';
                }
                // Log the turn on 'response.done', regardless of 'usage' presence
                if (event.type === 'response.done' && event.response) {
                    if (event.response.output && event.response.output[0] && event.response.output[0].content && event.response.output[0].content[0] && event.response.output[0].content[0].transcript) {
                        turnState.botTranscript = event.response.output[0].content[0].transcript;
                    }
                    logSessionTurn(event.response);
                }
            } catch(err) {
                console.error('AIPKit Realtime: Error processing message from server:', e.data, err);
            }
        };
        dc.onclose = () => console.log('AIPKit Realtime: Data channel closed.');
        dc.onerror = (e) => console.error('AIPKit Realtime: Data channel error:', e);


        try {
            const offer = await peerConnection.createOffer();
            await peerConnection.setLocalDescription(offer);

            const realtimeModel = config.realtimeModel || 'gpt-4o-realtime-preview';
            const sdpResponse = await fetch(`https://api.openai.com/v1/realtime?model=${realtimeModel}`, {
                method: 'POST',
                body: offer.sdp,
                headers: {
                    'Authorization': `Bearer ${ephemeralKey}`,
                    'Content-Type': 'application/sdp'
                }
            });

            if (!sdpResponse.ok) {
                const errorText = await sdpResponse.text();
                throw new Error(`SDP exchange failed with status ${sdpResponse.status}: ${errorText}`);
            }

            const answer = {
                type: 'answer',
                sdp: await sdpResponse.text(),
            };
            await peerConnection.setRemoteDescription(answer);

            // --- FIX: Return the correct variable 'dc' instead of 'dataChannel' ---
            return dc;
            // --- END FIX ---

        } catch (err) {
            console.error('AIPKit Realtime: WebRTC connection failed.', err);
            // Clean up the peer connection if it exists
            if (peerConnection && peerConnection.connectionState !== 'closed') {
                peerConnection.close();
            }
            throw err; // Re-throw for the caller to handle
        }
    }

    window.aipkit_realtimeConnector = {
        connect
    };

})();