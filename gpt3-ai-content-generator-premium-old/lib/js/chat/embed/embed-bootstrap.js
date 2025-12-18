// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/lib/public/js/chat/embed-bootstrap.js
// Status: NEW FILE (Moved)
/**
 * AIPKit Chatbot Embed Bootstrap
 *
 * This lightweight script is included on an external site. It fetches the
 * chatbot configuration and pre-rendered HTML, then dynamically loads the
 * necessary CSS and JS assets from the WordPress site to render the chatbot.
 */
(function() {
    'use strict';

    // Find the script tag that loaded this bootstrap script
    const bootstrapScript = document.currentScript;
    if (!bootstrapScript) {
        console.error("AIPKit Embed: Could not find the bootstrap script tag.");
        return;
    }

    // Get configuration from the script tag's data attributes
    const botId = bootstrapScript.dataset.botId;
    const wpSiteUrl = bootstrapScript.dataset.wpSite;

    if (!botId || !wpSiteUrl) {
        console.error("AIPKit Embed: Missing 'data-bot-id' or 'data-wp-site' attribute on the script tag.");
        return;
    }

    // --- Asset and API URLs ---
    const configApiUrl = `${wpSiteUrl}/wp-json/aipkit/v1/chatbots/${botId}/embed-config`;
    const targetDivId = `aipkit-chatbot-container-${botId}`;

    // --- Helper Functions ---

    /**
     * Loads a CSS file by creating a <link> element.
     * @param {string} href - The URL of the CSS file.
     * @returns {Promise<void>}
     */
    function loadCss(href) {
        return new Promise((resolve, reject) => {
            if (!href) return resolve();
            if (document.querySelector(`link[href="${href}"]`)) return resolve(); // Already loaded
            const link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = href;
            link.onload = () => resolve();
            link.onerror = () => reject(new Error(`Failed to load CSS: ${href}`));
            document.head.appendChild(link);
        });
    }

    /**
     * Loads a JavaScript file by creating a <script> element.
     * @param {string} src - The URL of the JS file.
     * @returns {Promise<void>}
     */
    function loadJs(src) {
        return new Promise((resolve, reject) => {
            if (!src) return resolve();
            if (document.querySelector(`script[src="${src}"]`)) return resolve(); // Already loaded
            const script = document.createElement('script');
            script.src = src;
            script.async = false; // Load scripts sequentially to respect dependencies
            script.onload = () => resolve();
            script.onerror = () => reject(new Error(`Failed to load JS: ${src}`));
            document.body.appendChild(script);
        });
    }

    /**
     * Fetches the chatbot configuration and HTML from the REST API.
     * @returns {Promise<object>}
     */
    async function fetchConfigAndHtml() {
        const response = await fetch(configApiUrl);
        if (!response.ok) {
            const errorData = await response.json().catch(() => ({ message: 'Unknown API error.' }));
            throw new Error(`Failed to fetch config: ${errorData.message || response.statusText}`);
        }
        return response.json();
    }

    /**
     * The main function to orchestrate the loading and initialization.
     */
    async function main() {
        const targetDiv = document.getElementById(targetDivId);
        if (!targetDiv) {
            console.error(`AIPKit Embed: Target container with ID "${targetDivId}" not found.`);
            return;
        }

        targetDiv.innerHTML = '<p style="text-align:center;font-family:sans-serif;color:#888;padding:20px;">Loading Chatbot...</p>';

        try {
            // 1. Fetch the configuration and HTML
            const responseData = await fetchConfigAndHtml();
            const config = responseData.config;
            const chatbotHtml = responseData.html;

            // 2. Load assets sequentially
            await loadCss(config.assetUrls.css);
            await loadJs(config.assetUrls.markdownit);
            if (config.assetUrls.jspdf) {
                 await loadJs(config.assetUrls.jspdf);
            }
            await loadJs(config.assetUrls.mainJs);
            
            // 3. Inject the pre-rendered HTML into the target container
            targetDiv.innerHTML = chatbotHtml;

            // 4. Find the main chat wrapper element that was just injected
            const chatWrapper = targetDiv.querySelector('.aipkit_chat_container, .aipkit_popup_wrapper');

            if (!chatWrapper) {
                 throw new Error('Chatbot container element not found in the HTML received from the server.');
            }

            // 5. Initialize the chatbot using the standard initializer
            let attempts = 0;
            const maxAttempts = 20; // Try for 2 seconds
            const interval = 100;

            function tryInit() {
                if (typeof window.aipkit_initializeChatInstance === 'function') {
                    window.aipkit_initializeChatInstance(chatWrapper);
                } else if (attempts < maxAttempts) {
                    attempts++;
                    setTimeout(tryInit, interval);
                } else {
                    throw new Error('Main chat initializer function (aipkit_initializeChatInstance) did not become available.');
                }
            }
            tryInit();

        } catch (error) {
            console.error("AIPKit Embed: Initialization failed.", error);
            targetDiv.innerHTML = `<p style="text-align:center;font-family:sans-serif;color:#d9534f;padding:20px;">Error: Could not load chatbot. ${error.message}</p>`;
        }
    }

    // Start the process
    main();

})();