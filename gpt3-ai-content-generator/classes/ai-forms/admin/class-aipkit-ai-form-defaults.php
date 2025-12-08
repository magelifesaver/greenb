<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/ai-forms/admin/class-aipkit-ai-form-defaults.php
// Purpose: This file ensures that default, pre-built AI Forms are created for the user upon plugin activation or update.
// I have replaced the deprecated `get_page_by_title` function with a `WP_Query` to prevent PHP warnings and adhere to modern WordPress standards.

namespace WPAICG\AIForms\Admin;

use WPAICG\AIForms\Storage\AIPKit_AI_Form_Storage;
use WP_Error;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handles creation of default, pre-built AI Forms.
 */
class AIPKit_AI_Form_Defaults
{
    /**
     * Ensures the default AI Forms exist in the database.
     * This method is idempotent and can be called on plugin activation or updates.
     */
    public static function ensure_default_forms_exist()
    {
        if (!class_exists(AIPKit_AI_Form_Storage::class) || !class_exists(AIPKit_AI_Form_Admin_Setup::class)) {
            return;
        }

        $default_forms = self::get_default_forms_data();
        $form_storage = new AIPKit_AI_Form_Storage();

        foreach ($default_forms as $form_key => $form_data) {
            $option_name = 'aipkit_default_form_created_' . $form_key;
            if (get_option($option_name)) {
                continue; // Already created.
            }

            // To avoid duplicates, also check by title.
            // FIX: Replaced deprecated get_page_by_title with WP_Query.
            $query = new \WP_Query([
                'post_type'      => AIPKit_AI_Form_Admin_Setup::POST_TYPE,
                'title'          => $form_data['title'],
                'post_status'    => 'publish',
                'posts_per_page' => 1,
                'no_found_rows'  => true,
            ]);

            if ($query->have_posts()) {
                $existing_post = $query->posts[0];
                update_option($option_name, $existing_post->ID, 'no');
                continue;
            }
            // --- END FIX ---

            $new_form_id_or_error = $form_storage->create_form($form_data['title'], $form_data['settings']);

            if (!is_wp_error($new_form_id_or_error)) {
                update_option($option_name, $new_form_id_or_error, 'no');
                // Also add meta to the post itself for easier identification if needed later
                update_post_meta($new_form_id_or_error, '_aipkit_is_default_form', '1');
            }
        }
    }

    /**
     * Defines the structure and content for the default AI forms.
     *
     * @return array
     */
    private static function get_default_forms_data(): array
    {
        return [
            'blog_post_generator' => [
                'title' => 'Blog Post Generator',
                'settings' => [
                    'ai_provider' => 'OpenAI',
                    'ai_model' => 'gpt-4o-mini',
                    'prompt_template' => "You are an expert SEO content writer and professional blogger. Your task is to write a high-quality, engaging, and SEO-friendly blog post.\n\n**Instructions:**\n1.  **Analyze the Input:** Carefully consider the provided topic, target audience, desired length, and tone.\n2.  **Structure the Article:** The article must have a clear structure:\n    - An engaging introduction that hooks the reader.\n    - A well-organized body with multiple sections, using H2 and H3 headings.\n    - A concise and impactful conclusion that summarizes the key points.\n3.  **Incorporate Keywords:** Naturally weave the provided keywords throughout the article, especially in headings and the introduction.\n4.  **Tone:** Strictly adhere to the specified tone of voice.\n5.  **Length:** Adjust the depth and detail of the content to match the desired length.\n6.  **Output:** Return only the full article content in Markdown format. Do not include any pre-amble, notes, or post-article commentary.\n\n---\n**Blog Post Details:**\n*   **Topic/Title:** {topic}\n*   **Target Audience:** {audience}\n*   **Desired Length:** {length}\n*   **Tone of Voice:** {tone}\n*   **Keywords:** {keywords}\n---\n\nBegin the blog post now:",
                    'form_structure' => wp_json_encode([
                        ['internalId' => 'row-1', 'type' => 'layout-row', 'columns' => [
                            ['internalId' => 'col-1-1', 'width' => '100%', 'elements' => [
                                ['internalId' => 'el-1', 'type' => 'text-input', 'label' => 'Blog Post Topic / Title', 'placeholder' => 'e.g., The Future of Artificial Intelligence', 'fieldId' => 'topic', 'required' => true, 'helpText' => 'What is the main subject of your article?']
                            ]]
                        ]],
                        ['internalId' => 'row-2', 'type' => 'layout-row', 'columns' => [
                            ['internalId' => 'col-2-1', 'width' => '100%', 'elements' => [
                                ['internalId' => 'el-2', 'type' => 'text-input', 'label' => 'Target Audience', 'placeholder' => 'e.g., Tech enthusiasts, beginners, marketing professionals', 'fieldId' => 'audience', 'required' => true, 'helpText' => 'Who are you writing for?']
                            ]]
                        ]],
                        ['internalId' => 'row-3', 'type' => 'layout-row', 'columns' => [
                            ['internalId' => 'col-3-1', 'width' => '50%', 'elements' => [
                                ['internalId' => 'el-3', 'type' => 'select', 'label' => 'Desired Length', 'fieldId' => 'length', 'required' => true, 'options' => [
                                    ['value' => 'short (approx. 500 words)', 'text' => 'Short (~500 words)'],
                                    ['value' => 'medium (approx. 1000 words)', 'text' => 'Medium (~1000 words)'],
                                    ['value' => 'long (approx. 1500+ words)', 'text' => 'Long (1500+ words)']
                                ], 'helpText' => '']
                            ]],
                            ['internalId' => 'col-3-2', 'width' => '50%', 'elements' => [
                                ['internalId' => 'el-4', 'type' => 'select', 'label' => 'Tone of Voice', 'fieldId' => 'tone', 'required' => true, 'options' => [
                                    ['value' => 'Professional', 'text' => 'Professional'],
                                    ['value' => 'Casual', 'text' => 'Casual'],
                                    ['value' => 'Witty', 'text' => 'Witty'],
                                    ['value' => 'Persuasive', 'text' => 'Persuasive']
                                ], 'helpText' => '']
                            ]]
                        ]],
                        ['internalId' => 'row-4', 'type' => 'layout-row', 'columns' => [
                            ['internalId' => 'col-4-1', 'width' => '100%', 'elements' => [
                                ['internalId' => 'el-5', 'type' => 'text-input', 'label' => 'Keywords (comma-separated)', 'placeholder' => 'e.g., AI, machine learning, neural networks', 'fieldId' => 'keywords', 'required' => false, 'helpText' => '(Optional) Helps with SEO focus.']
                            ]]
                        ]],
                    ])
                ]
            ],
            'youtube_script_writer' => [
                'title' => 'YouTube Script Writer',
                'settings' => [
                    'ai_provider' => 'OpenAI',
                    'ai_model' => 'gpt-4o-mini',
                    'prompt_template' => "You are an expert YouTube scriptwriter with a knack for creating highly engaging and shareable video content. Your goal is to write a complete video script based on the details provided.\n\n**Instructions:**\n1.  **Script Structure:** The script should be structured logically with clear sections:\n    - **Hook (0-15 seconds):** A captivating opening to grab the viewer's attention immediately.\n    - **Intro:** Briefly introduce the video's topic and what the viewer will learn or experience.\n    - **Main Content:** Break down the core content into logical segments or talking points. Use bullet points for clarity.\n    - **Call to Action (CTA):** Integrate the provided Call to Action seamlessly. If none is provided, suggest a relevant one (e.g., \"like, comment, and subscribe\").\n    - **Outro:** A concluding segment that wraps up the video and gives a final thought.\n2.  **Timestamps:** Provide approximate timestamps (e.g., [00:30]) for each major section to guide the creator, based on the target duration.\n3.  **Visual Cues:** Include suggestions for on-screen text, B-roll, or graphics in brackets, like [Show on-screen: Key takeaway point].\n4.  **Tone & Style:** The script must match the specified video style.\n\n---\n**Video Details:**\n*   **Title/Topic:** {video_topic}\n*   **Target Duration:** {duration} minutes\n*   **Style:** {style}\n*   **Call to Action:** {cta}\n---\n\nWrite the complete YouTube script now:",
                    'form_structure' => wp_json_encode([
                        ['internalId' => 'row-5', 'type' => 'layout-row', 'columns' => [
                            ['internalId' => 'col-5-1', 'width' => '100%', 'elements' => [
                                ['internalId' => 'el-6', 'type' => 'text-input', 'label' => 'Video Title or Topic', 'placeholder' => 'e.g., How to Make the Perfect Sourdough Bread', 'fieldId' => 'video_topic', 'required' => true, 'helpText' => '']
                            ]]
                        ]],
                        ['internalId' => 'row-6', 'type' => 'layout-row', 'columns' => [
                            ['internalId' => 'col-6-1', 'width' => '50%', 'elements' => [
                                ['internalId' => 'el-7', 'type' => 'text-input', 'label' => 'Target Duration (in minutes)', 'placeholder' => 'e.g., 10', 'fieldId' => 'duration', 'required' => true, 'helpText' => 'Enter a number.']
                            ]],
                             ['internalId' => 'col-6-2', 'width' => '50%', 'elements' => [
                                ['internalId' => 'el-8', 'type' => 'select', 'label' => 'Video Style', 'fieldId' => 'style', 'required' => true, 'options' => [
                                    ['value' => 'Tutorial / How-to', 'text' => 'Tutorial / How-to'],
                                    ['value' => 'Vlog', 'text' => 'Vlog'],
                                    ['value' => 'Product Review', 'text' => 'Product Review'],
                                    ['value' => 'Storytelling / Narrative', 'text' => 'Storytelling / Narrative']
                                ], 'helpText' => '']
                            ]]
                        ]],
                         ['internalId' => 'row-7', 'type' => 'layout-row', 'columns' => [
                            ['internalId' => 'col-7-1', 'width' => '100%', 'elements' => [
                                ['internalId' => 'el-9', 'type' => 'textarea', 'label' => "Call to Action (Optional)", 'placeholder' => "e.g., Subscribe for more tips!", 'fieldId' => 'cta', 'required' => false, 'helpText' => 'What should viewers do at the end?']
                            ]]
                        ]],
                    ])
                ]
            ],
            'customer_support_reply_builder' => [
                'title' => 'Customer Support Reply Builder',
                'settings' => [
                    'ai_provider' => 'OpenAI',
                    'ai_model' => 'gpt-4o-mini',
                    'prompt_template' => "You are a highly skilled and empathetic customer support professional. Your task is to draft a helpful and well-written reply to the customer's message below.\n\n**Instructions:**\n1.  **Acknowledge:** Start by acknowledging the customer's issue or question.\n2.  **Address the Core Problem:** Directly address the main points from the customer's message.\n3.  **Maintain Tone:** Adhere strictly to the specified response tone.\n4.  **Incorporate Details:** Use the provided Product/Service Name and any additional notes to make the reply specific and helpful.\n5.  **Clarity and Conciseness:** Write a clear, concise, and easy-to-understand response.\n6.  **Format:** Output the reply only. Do not include any surrounding text, greetings to me, or explanations of what you did.\n\n---\n**Support Ticket Details:**\n*   **Customer's Message:**\n{customer_message}\n\n*   **Product/Service Mentioned:** {product_name}\n*   **Desired Response Tone:** {tone}\n*   **Internal Notes for your reply:** {notes}\n---\n\nDraft the customer support reply now:",
                    'form_structure' => wp_json_encode([
                        ['internalId' => 'row-8', 'type' => 'layout-row', 'columns' => [
                            ['internalId' => 'col-8-1', 'width' => '100%', 'elements' => [
                                ['internalId' => 'el-10', 'type' => 'textarea', 'label' => "Customer's Message", 'placeholder' => "Paste the customer's full email or message here.", 'fieldId' => 'customer_message', 'required' => true, 'helpText' => '']
                            ]]
                        ]],
                        ['internalId' => 'row-9', 'type' => 'layout-row', 'columns' => [
                            ['internalId' => 'col-9-1', 'width' => '50%', 'elements' => [
                                ['internalId' => 'el-11', 'type' => 'text-input', 'label' => 'Product/Service Name (Optional)', 'placeholder' => 'e.g., Pro Plan, Widget X', 'fieldId' => 'product_name', 'required' => false, 'helpText' => '']
                            ]],
                             ['internalId' => 'col-9-2', 'width' => '50%', 'elements' => [
                                ['internalId' => 'el-12', 'type' => 'select', 'label' => 'Response Tone', 'fieldId' => 'tone', 'required' => true, 'options' => [
                                    ['value' => 'Polite & Empathetic', 'text' => 'Polite & Empathetic'],
                                    ['value' => 'Friendly & Casual', 'text' => 'Friendly & Casual'],
                                    ['value' => 'Formal & Direct', 'text' => 'Formal & Direct'],
                                    ['value' => 'Neutral', 'text' => 'Neutral']
                                ], 'helpText' => '']
                            ]]
                        ]],
                         ['internalId' => 'row-10', 'type' => 'layout-row', 'columns' => [
                            ['internalId' => 'col-10-1', 'width' => '100%', 'elements' => [
                                ['internalId' => 'el-13', 'type' => 'textarea', 'label' => 'Additional Notes for AI (Optional)', 'placeholder' => 'e.g., Offer a 10% discount, Escalate to tier 2 support if not resolved.', 'fieldId' => 'notes', 'required' => false, 'helpText' => 'Internal context for the AI to use in the reply.']
                            ]]
                        ]],
                    ])
                ]
            ],
        ];
    }
}