(function() {
    'use strict';

    /**
     * Generates and triggers the download of the chat transcript as a PDF file.
     * Requires the jsPDF library to be loaded.
     * @param {object} elements - Object containing references to UI elements { messagesEl }.
     * @param {object} config - The chatbot configuration object.
     */
    function aipkit_chatUI_downloadTranscriptActionPdf(elements, config) {
        const { messagesEl } = elements;
        if (!messagesEl || !config || !config.text) {
            console.error("AIPKit Download PDF: Missing messages element or config.");
            return;
        }

        // Ensure helper functions are available
        if (typeof window.aipkit_chatUI_extractTextFromBubble !== 'function' ||
            typeof window.aipkit_chatUI_generateDownloadFilename !== 'function' ||
            typeof window.aipkit_chatUI_triggerBlobDownload !== 'function') {
            console.error("AIPKit Download PDF: One or more helper functions (extractText, generateFilename, triggerBlobDownload) not found.");
            alert("Error: Could not prepare PDF download.");
            return;
        }

        // Check if jsPDF is available
        if (typeof window.jspdf === 'undefined' || typeof window.jspdf.jsPDF === 'undefined') {
            console.error("AIPKit Download PDF: jsPDF library is not loaded.");
            alert(config.text.pdfError || "Could not generate PDF. jsPDF library might be missing.");
            return;
        }
        const { jsPDF } = window.jspdf;

        const doc = new jsPDF();
        const messages = messagesEl.querySelectorAll('.aipkit_chat_message');
        const botName = config?.headerName || 'Bot';
        const userPrefix = config.text.userPrefix || 'User';
        const errorPrefix = config.text.errorPrefix || 'Error';

        let yPos = 10; // Initial Y position in mm
        const pageHeight = doc.internal.pageSize.height;
        const margin = 10;
        const lineHeight = 6; // Approximate line height in mm for 12pt font
        const usableHeight = pageHeight - (margin * 2);
        let hasContent = false;

        doc.setFontSize(16);
        doc.text(`Chat Transcript - ${botName}`, margin, yPos);
        yPos += lineHeight * 2;
        doc.setFontSize(12);

        messages.forEach(msg => {
            const bubble = msg.querySelector('.aipkit_chat_bubble');
            if (!bubble) return;

            const messageText = window.aipkit_chatUI_extractTextFromBubble(bubble); // Use helper
            let senderPrefix = "";

            if (msg.classList.contains('aipkit_chat_message-user')) {
                senderPrefix = `${userPrefix}:`;
                doc.setFont(undefined, 'bold'); // Make user prefix bold
            } else if (msg.classList.contains('aipkit_chat_message-bot')) {
                senderPrefix = `${botName}:`;
                doc.setFont(undefined, 'normal');
            } else if (msg.classList.contains('aipkit_chat_message-error')) {
                senderPrefix = `${errorPrefix}:`;
                doc.setFont(undefined, 'italic');
            } else {
                 doc.setFont(undefined, 'normal'); // Reset font style
            }

            if (senderPrefix && messageText) {
                hasContent = true;
                const prefixText = senderPrefix;
                const fullText = `${messageText}`; // Text without prefix for splitting

                // Check space for prefix
                if (yPos + lineHeight > usableHeight + margin) {
                    doc.addPage();
                    yPos = margin;
                }
                doc.text(prefixText, margin, yPos);
                yPos += lineHeight; // Move down after prefix

                // Split the main message text and add line by line
                const lines = doc.splitTextToSize(fullText, doc.internal.pageSize.width - margin * 2);
                lines.forEach(line => {
                    if (yPos + lineHeight > usableHeight + margin) {
                        doc.addPage();
                        yPos = margin;
                    }
                    doc.text(line, margin, yPos);
                    yPos += lineHeight;
                });

                // Add a small gap after each message
                yPos += lineHeight / 2;
                doc.setFont(undefined, 'normal'); // Reset font style
            }
        });

        if (!hasContent) {
             console.warn("AIPKit Chat Download PDF: No transcript content found.");
            alert(config.text.downloadEmpty || "Nothing to download.");
            return;
        }

        const filename = window.aipkit_chatUI_generateDownloadFilename(config, 'pdf');
        try {
            doc.save(filename);
        } catch(e) {
            console.error("AIPKit Download PDF: Error calling doc.save()", e);
             alert("An error occurred while generating the PDF.");
        }
    }

    window.aipkit_chatUI_downloadTranscriptActionPdf = aipkit_chatUI_downloadTranscriptActionPdf;

})();