<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/content-writer/prompt/class-aipkit-content-writer-prompts.php
// Status: MODIFIED

namespace WPAICG\ContentWriter;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Centralized class for defining default prompts used in the Content Writer module.
 * @since NEXT_VERSION
 */
class AIPKit_Content_Writer_Prompts
{
    /**
     * @return string The default prompt for generating a new title.
     */
    public static function get_default_title_prompt(): string
    {
        return __('You are an expert SEO copywriter. Write a powerful and engaging SEO title that:
- Is under 60 characters
- Starts with the main focus keyword
- Includes at least one power word (e.g., Stunning, Must-Have, Exclusive)
- Includes a positive or negative sentiment word (e.g., Best, Effortless, Affordable)

Return ONLY the new title text. Do not include any introduction, explanation, or quotation marks.

Topic: "{topic}"
Keywords: "{keywords}"', 'gpt3-ai-content-generator');
    }

    /**
     * @return string The default prompt for generating new content.
     */
    public static function get_default_content_prompt(): string
    {
        return __('Write a full article based on the topic and keywords below. The article must:
- Be at least 600 words long
- Include the focus keyword in one or more subheadings (H2, H3, etc.)
- Start the first paragraph with the focus keyword
- Be informative, structured, and engaging
- Use natural tone and clear formatting
- Avoid repeating the title in the content

Topic: "{topic}"
Keywords: "{keywords}"', 'gpt3-ai-content-generator');
    }

    /**
     * @return string The default prompt for generating an SEO meta description.
     */
    public static function get_default_meta_prompt(): string
    {
        return __('Write a meta description (under 155 characters) for a page about the following topic. The description must:
- Begin with or include the focus keyword early
- Use active voice and a clear call-to-action
- Be concise and engaging

Return ONLY the plain meta description without any quotation marks, labels, or formatting.

Topic: "{topic}"
Keywords: "{keywords}"
Summary: "{content_summary}"', 'gpt3-ai-content-generator');
    }

    /**
     * @return string The default prompt for generating an SEO focus keyword.
     */
    public static function get_default_keyword_prompt(): string
    {
        return __('Identify the single most important and relevant SEO focus keyphrase for the article based on the title and summary. The keyphrase must:
- Be 2–4 words
- Be naturally found in the content
- Be suitable for SEO targeting

Return ONLY the keyphrase, with no labels, formatting, or quotation marks.

Title: "{topic}"
Summary:
{content_summary}', 'gpt3-ai-content-generator');
    }

    /**
     * @return string The default prompt for generating an excerpt.
     */
    public static function get_default_excerpt_prompt(): string
    {
        return __('Write a short excerpt (1–2 engaging sentences) for the following article. Use a friendly, clear tone. Include the focus keyword naturally.

Return ONLY the excerpt, without any formatting or explanation.

Title: "{topic}"
Keywords: "{keywords}"
Summary: "{content_summary}"', 'gpt3-ai-content-generator');
    }

    /**
     * @return string The default prompt for generating tags.
     */
    public static function get_default_tags_prompt(): string
    {
        return __('Generate 5–10 relevant SEO tags for a blog post about the following topic. Tags must reflect key themes and keywords.

Return ONLY a comma-separated list of tags. Do not include any explanation, numbering, or formatting.

Title: "{topic}"
Keywords: "{keywords}"
Summary:
{content_summary}', 'gpt3-ai-content-generator');
    }

    /**
     * @return string The default prompt for generating an in-content image.
     */
    public static function get_default_image_prompt(): string
    {
        return __('Generate a high-quality, relevant image prompt for an article about: {topic}', 'gpt3-ai-content-generator');
    }

    /**
     * @return string The default prompt for generating a featured image.
     */
    public static function get_default_featured_image_prompt(): string
    {
        return __('Generate an eye-catching, high-quality featured image prompt for a blog post about: {topic}. Keywords: {keywords}.', 'gpt3-ai-content-generator');
    }
}
