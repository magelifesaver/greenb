<?php
/**
 * Plugin Name: AAA Term Description FAQ Accordion
 * Description: Converts long taxonomy term descriptions (like product attributes) into a simple FAQ/accordion on archive pages.
 * Author: Webmaster Workflow
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'wp_enqueue_scripts', function () {

    // Only run on taxonomy archive pages (product attributes, categories, etc.).
    if ( ! is_tax() ) {
        return;
    }

    // Dummy handle just so we can attach inline JS cleanly.
    wp_register_script( 'aaa-term-description-faq-dummy', '', [], null, true );
    wp_enqueue_script( 'aaa-term-description-faq-dummy' );

    $inline_js = <<<JS
document.addEventListener('DOMContentLoaded', function () {
    // Target archive description containers.
    // Adjust or add selectors if your theme uses something custom.
    var containers = document.querySelectorAll(
        '.archive-description, .term-description, .woocommerce-term-description'
    );

    if (!containers || !containers.length) {
        return;
    }

    function buildFaqFromText(fullText) {
        if (!fullText) {
            return null;
        }

        // Normalize line breaks
        var normalized = fullText.replace(/\\r\\n/g, '\\n').replace(/\\r/g, '\\n');

        // Split into blocks separated by blank lines (2+ newlines)
        var blocks = normalized
            .split(/\\n{2,}/)
            .map(function (b) { return b.trim(); })
            .filter(function (b) { return b.length > 0; });

        if (!blocks.length) {
            return null;
        }

        var faqs = [];
        var current = null;

        blocks.forEach(function (block) {
            var trimmed = block.trim();
            if (!trimmed) {
                return;
            }

            // Treat a block with a question mark, starting with a capital letter, as a question.
            var looksLikeQuestion = trimmed.indexOf('?') !== -1 && /^[A-Z]/.test(trimmed.charAt(0));

            if (looksLikeQuestion) {
                if (current) {
                    faqs.push(current);
                }
                current = {
                    question: trimmed,
                    answers: []
                };
            } else if (current) {
                current.answers.push(trimmed);
            }
        });

        if (current) {
            faqs.push(current);
        }

        if (!faqs.length) {
            return null;
        }

        // Build the FAQ wrapper
        var wrapper = document.createElement('div');
        wrapper.className = 'aaa-term-faq';

        faqs.forEach(function (faq, index) {
            var item = document.createElement('div');
            item.className = 'aaa-term-faq-item';

            // Question button
            var questionBtn = document.createElement('button');
            questionBtn.type = 'button';
            questionBtn.className = 'aaa-term-faq-question';
            questionBtn.setAttribute('aria-expanded', index === 0 ? 'true' : 'false');

            // Simple text-only button styling
            questionBtn.style.display = 'block';
            questionBtn.style.width = '100%';
            questionBtn.style.textAlign = 'left';
            questionBtn.style.border = 'none';
            questionBtn.style.background = 'none';
            questionBtn.style.padding = '0.4em 0';
            questionBtn.style.margin = '0';
            questionBtn.style.cursor = 'pointer';
            questionBtn.style.fontWeight = '600';

            questionBtn.textContent = faq.question;

            // Answer container
            var answerDiv = document.createElement('div');
            answerDiv.className = 'aaa-term-faq-answer';
            answerDiv.style.padding = '0 0 0.6em 0';
            answerDiv.style.display = (index === 0) ? 'block' : 'none';

            faq.answers.forEach(function (para) {
                var p = document.createElement('p');
                p.textContent = para;
                answerDiv.appendChild(p);
            });

            // Toggle open/close
            questionBtn.addEventListener('click', function () {
                var isOpen = questionBtn.getAttribute('aria-expanded') === 'true';
                questionBtn.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
                answerDiv.style.display = isOpen ? 'none' : 'block';
            });

            item.appendChild(questionBtn);
            item.appendChild(answerDiv);
            wrapper.appendChild(item);
        });

        return wrapper;
    }

    containers.forEach(function (el) {
        if (!el) {
            return;
        }

        // Avoid double-processing if the script ever runs twice.
        if (el.querySelector('.aaa-term-faq')) {
            return;
        }

        var fullText = (el.textContent || '').trim();
        if (!fullText) {
            return;
        }

        var faqWrapper = buildFaqFromText(fullText);
        if (!faqWrapper) {
            return;
        }

        // Clear original description HTML and insert FAQ accordion
        while (el.firstChild) {
            el.removeChild(el.firstChild);
        }

        el.appendChild(faqWrapper);
    });
});
JS;

    wp_add_inline_script( 'aaa-term-description-faq-dummy', $inline_js );
} );
