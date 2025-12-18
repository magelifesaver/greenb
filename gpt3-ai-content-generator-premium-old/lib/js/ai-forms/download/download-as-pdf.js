// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/lib/js/ai-forms/download-as-pdf.js
// Status: NEW FILE

/**
 * AIPKit AI Forms - PDF Download (Pro Feature)
 */
(function () {
  "use strict";

  /**
   * Handles the click event for the "Download PDF" button on an AI form result.
   * @param {Event} event The click event.
   */
  function aipkitForms_handleDownloadPdf(event) {
    const button = event.currentTarget;
    const wrapper = button.closest(".aipkit-ai-form-wrapper");
    const contentDiv = wrapper
      ? wrapper.querySelector(".aipkit-ai-form-results-content")
      : null;
    const formId = wrapper ? wrapper.dataset.formId : "unknown";

    if (!contentDiv) {
      console.error(
        "AI Forms PDF: Results content div (.aipkit-ai-form-results-content) not found."
      );
      return;
    }

    if (
      typeof window.jspdf === "undefined" ||
      typeof window.jspdf.jsPDF === "undefined"
    ) {
      console.error("AI Forms PDF: jsPDF library is not loaded.");
      alert("Could not generate PDF. Required library is missing.");
      return;
    }
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();

    // Use innerText to get a clean text representation of the rendered HTML
    const textToExport = contentDiv.innerText || contentDiv.textContent || "";

    if (!textToExport.trim()) {
      alert("Nothing to export.");
      return;
    }

    const pageHeight = doc.internal.pageSize.height;
    const margin = 10;
    const lineHeight = 6; // Approximate line height in mm for 12pt font
    const usableHeight = pageHeight - margin * 2;
    let yPos = margin;

    doc.setFontSize(12);

    // Split the text into lines that fit the page width
    const lines = doc.splitTextToSize(
      textToExport,
      doc.internal.pageSize.width - margin * 2
    );

    // Iterate through lines and add new pages as needed
    lines.forEach((line) => {
      if (yPos + lineHeight > usableHeight + margin) {
        doc.addPage();
        yPos = margin; // Reset Y position for new page
      }
      doc.text(line, margin, yPos);
      yPos += lineHeight;
    });

    // Generate filename
    const date = new Date();
    const timestamp = `${date.getFullYear()}${String(
      date.getMonth() + 1
    ).padStart(2, "0")}${String(date.getDate()).padStart(2, "0")}`;
    const filename = `aiform-${formId}-result-${timestamp}.pdf`;

    // Save the PDF
    doc.save(filename);
  }

  // Expose globally for event listeners
  window.aipkitForms_handleDownloadPdf = aipkitForms_handleDownloadPdf;
})();
