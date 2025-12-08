// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/lib/js/chat/realtime/audio-handler.js
// Status: NEW FILE
/**
 * AIPKit Realtime Voice - Audio Handler
 * Manages local microphone stream and remote audio playback.
 */
(function () {
  "use strict";

  /**
   * Gets user microphone stream and adds it to the peer connection.
   * @param {RTCPeerConnection} peerConnection The WebRTC peer connection.
   * @returns {Promise<MediaStream>} The local media stream.
   */
  async function startLocalStream(peerConnection) {
    try {
      const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
      stream
        .getTracks()
        .forEach((track) => peerConnection.addTrack(track, stream));
      return stream;
    } catch (err) {
      console.error("Error getting user media:", err);
      throw err;
    }
  }

  /**
   * Sets up the remote audio stream playback.
   * @param {RTCPeerConnection} peerConnection The WebRTC peer connection.
   * @returns {HTMLAudioElement} The audio element for playback.
   */
  function setupRemoteStream(peerConnection) {
    const audioEl = document.createElement("audio");
    audioEl.autoplay = true;
    peerConnection.ontrack = (event) => {
      if (event.streams && event.streams[0]) {
        audioEl.srcObject = event.streams[0];
      }
    };
    return audioEl;
  }

  /**
   * Stops all tracks in a given media stream.
   * @param {MediaStream|null} stream The stream to stop.
   */
  function stopLocalStream(stream) {
    if (stream) {
      stream.getTracks().forEach((track) => track.stop());
    }
  }

  window.aipkit_realtimeAudioHandler = {
    startLocalStream,
    setupRemoteStream,
    stopLocalStream,
  };
})();
