=== AIP: Complete AI Toolkit for WordPress (formerly AI Power) ===
Contributors: senols
Tags: ai, chatbot, openai, gemini, chatgpt
Requires at least: 5.0.0
Tested up to: 6.9
Requires PHP: 8.0
Stable tag: 2.3.46
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

AI, Chatbot, ChatGPT, Content Writer, Auto Content Writer, WooCommerce Product Writer, Image Generator, ChatPDF, AI Training, Embeddings and more.

== Description ==

**AI Power (AIP)** is the **complete AI plugin for WordPress** — a full set of **artificial intelligence tools** to transform your site. From **AI chatbot** and **content generation** to **image creation, automation, and AI training** on your own data, AIP gives you everything in one place, right inside your WordPress dashboard.

Our **"Bring Your Own API Key"** model lets you connect to top AI providers (OpenAI, Google Gemini, Microsoft Azure, OpenRouter, DeepSeek and Ollama). No hidden credits — you use your own account and control your costs.

[📖 Documentation & Guides](https://docs.aipower.org/)  

### Why Choose AIP?

* **All-in-One** – Chatbot, AI Writer, AI Forms, Image Generator, Automation, WooCommerce AI tools, and more.
* **Train on Your Data** – Build your own **AI knowledge base** from posts, pages, products, PDFs, or files.
* **Voice + Chat** – Real-time voice agents and voice input for interactive AI experiences.
* **WooCommerce AI** – Generate product descriptions, titles, SEO tags, and even sell AI tokens to customers.
* **Fast & Flexible** – Works with OpenAI GPT-5/4o, Google Gemini & Imagen, Azure, Replicate, and others.
* **Secure** – 100% hosted on your WordPress site. Your data stays with you.

---

### 🚀 Key Features

#### 🤖 AI Chatbot
- Create custom **AI chatbots** for WordPress or any external site (embed with shortcode or HTML).
- Train bots on your **own website content** or external files.
- Enable **web search** (OpenAI or Google) for real-time answers.
- Add **voice input & playback**, triggers, and usage limits.

#### ✍️ AI Content Generator
- Generate **high-quality articles, blog posts, or product descriptions**.
- Input ideas via text, CSV, RSS feeds, or URLs.
- SEO-friendly output with custom templates and placeholders.

#### 📝 AI Forms
- Drag-and-drop **AI-powered forms** to process user input into useful outputs — from outlines to support replies.
- Connect forms to **web search** and your AI training data.

#### ⚙️ AI Automation Engine
- Schedule recurring or one-time AI tasks.
- Automate content creation, comment replies, or vector indexing.

#### 🎨 AI Image Generator
- Convert text to image with **OpenAI DALL·E 3, GPT-4o, Google Imagen, Replicate models**.
- Pull free stock images from **Pexels** or **Pixabay**.
- Works in posts, tasks, chatbot, and forms.

#### 📚 AI Training / Vector Database
- Build a **knowledge base** from your posts, products, PDFs, or uploaded files.
- Supports **OpenAI Vector Stores**, **Pinecone**, and **Qdrant**.
- Use in Chatbot or Forms for **context-aware AI answers**.

#### 🛒 WooCommerce AI Tools
- Bulk-generate or enhance product descriptions, titles, and tags.
- Sell **AI tokens** to customers via WooCommerce.

#### 🛠 Content Assistant
- Bulk-enhance existing posts, generate SEO titles/excerpts.
- Works in Block Editor, Classic Editor, or directly from the post list.

#### 🔌 REST API Access
- Call text, image, embedding, and chatbot functions programmatically from other apps.

---

== Installation ==

1. Install via Plugins → Add New, or upload to `/wp-content/plugins/gpt3-ai-content-generator`.
2. Activate via the **Plugins** menu.
3. Go to **AIP → Dashboard** and enter your API key for at least one provider (e.g., OpenAI).
4. Click **Sync Models** to load available AI models.
5. Explore modules (Chat, Write, Automate, etc.) and start using AI features.

---

== Frequently Asked Questions ==

= Do I need to buy credits from you? =  
No. AIP works with your **own API key** from AI providers like OpenAI, Google Gemini, etc. You pay them directly for usage.

= Which AI providers and models are supported? =  
We support **OpenAI** (GPT-5, GPT-4o, GPT-3.5, DALL·E 3, etc.), **Google** (Gemini, Imagen), **Microsoft Azure OpenAI**, **OpenRouter**, **DeepSeek**, **Ollama** and **Replicate**.

= Can I train the AI on my own content? =  
Yes. Use the **Train** module to index posts, pages, WooCommerce products, PDFs, or uploaded files into a **vector store**. Then link that knowledge base to your Chatbot or Forms.

= How do I limit AI usage for visitors or members? =  
The **Token Management** add-on lets you set role-based or guest usage limits for Chat, Forms, Images, etc. Limits can reset daily, weekly, monthly, or never.

= Can I monetize my AI tools? =  
Yes. Sell **token packages** via WooCommerce. Tokens are consumed when users access AI features.

= What makes AIP different from other AI plugins? =  
AIP is **all-in-one** — instead of installing separate plugins for chatbots, content writing, AI forms, and WooCommerce AI, you get them all in one optimized toolkit with centralized settings.

= Is AIP compatible with GPT-5 and other latest models? =  
Yes. AIP supports GPT-5, GPT-4o, GPT-4 Turbo, Google Gemini 1.5, Imagen 4.0, and more.

---

== Screenshots ==

1. Main dashboard with quick access to all modules.
2. Add-ons page for enabling/disabling features.
3. Chatbot builder with real-time preview.
4. Content Writer with single, bulk, and RSS generation.
5. Automated Tasks scheduler.
6. Drag-and-drop AI Form builder.
7. AI Image Generator interface.
8. AI Training vector store management.
9. Token Management system.
10. WooCommerce AI integration.

---

== Changelog ==

= 2.3.46 =

- **Improved**: WordPress 6.9 Compatibility.

= 2.3.45 =

- **Fixed**: Chatbot input bar overlaying typed text on mobile and some desktop themes (e.g., OceanWP). The action bar now stacks below the textarea to avoid z-index/position conflicts.
- **Fixed**: AI Forms checkbox control no longer forces the first checkbox to stay selected.

= 2.3.44 =

- **Fixed**: Automated Tasks → Enhance Existing Content — "Fields to Update" incorrectly checked "Title" when editing a task even if it was not selected during save. Checkbox states now persist correctly.
- **Fixed**: Chatbot settings — "Reasoning" parameter was not persisting and always reverted to "low" (default) after save.
- **Improved**: SDK updated.

= 2.3.43 =

- **Improved**: Block Editor formatting — structured outputs now insert as proper blocks (Headings, Paragraphs, Lists).
- **Added**: Default Insert Position (Replace / After / Before) with per-action overrides in settings.
- **Added**: Undo for editor inserts.
- **Added**: Recent Actions section in editor menus for quick reuse.
- **Added**: "Reset to Defaults" button for Content Assistant actions.
- **Fixed**: Dropdown freeze caused by large user counts in Automated Tasks and Content Writer.  
- **Fixed**: Scrolling issue in Chatbot settings.  
- **Fixed**: CSV upload handling errors.  
- **Fixed**: Google Embeddings integration issues.  
- **Fixed**: Category retrieval bug when editing Automated Tasks.  
- **Improved**: SDK updated.  

= 2.3.42 =

- **Added**: Popup hint text — you can now display a custom hint above the chatbot popup.
- **Added**: Custom typing indicator — you can replace the animated dots with your own text.
- **Added**: Option to set different chatbot popup icon sizes — you can choose from small, medium, large, or extra-large popup icon.
- **Added**: Index status — you can now see which posts are indexed directly from the post list screen.
- **Added**: You can now user [username] placeholder in your chatbot system instructions.
- **Fixed**: Fixed an issue where expired cache could prevent the chatbot from working.
- **Improved**: General CSS and styling updates for the chatbot.
- **Improved**: Simplified chatbot settings screen.

**Note**: After updating the plugin, clear your cache and hard refresh the plugin page to see the newest changes.

= 2.3.41 =

- **Added**: Popup hint text — you can now display a custom hint above the chatbot popup.
- **Added**: Custom typing indicator — you can replace the animated dots with your own text.
- **Added**: Option to set different chatbot popup icon sizes — you can choose from small, medium, large, or extra-large popup icon.
- **Added**: Index status — you can now see which posts are indexed directly from the post list screen.
- **Added**: You can now user [username] placeholder in your chatbot system instructions.
- **Fixed**: Fixed an issue where expired cache could prevent the chatbot from working.
- **Improved**: General CSS and styling updates for the chatbot.
- **Improved**: Simplified chatbot settings screen.

**Note**: After updating the plugin, clear your cache and hard refresh the plugin page to see the newest changes.

= 2.3.40 =

- **Added**: New Google video models: Veo 2, Veo 3 Fast.
- **Added**: New Google image model: Gemini 2.0 Flash Preview, Imagen 4 Fast.
- **Improved**: Google model syncing. Once synced, models are now automatically grouped (text, image, embedding, video) across all modules. When Google releases new models, simply hit Sync to retrieve them, no plugin update required.
- **Improved**: You can now select multiple Qdrant collections for each chatbot.
- **Fixed**: File upload issue under AI Train page.
- **Fixed**: AI Training → Settings now keeps fields/taxonomies with special characters in their keys.

= 2.3.39 =

Added Ollama support. You can now use your local AI in our plugin.

[Please read the tutorial here](https://docs.aipower.org/docs/ai-providers#ollama-local-ai)

= 2.3.38 =

- **Added**: Document Chunking support for Pinecone and Qdrant. You can now configure chunk size and overlap in the settings. File uploads to Pinecone and Qdrant are token-aware and automatically split into chunks, preventing token limit issues and improving retrieval quality.
- **Added**: REST Logs Handler for accessing logs via the plugin’s REST API, enabling external integrations.
- **Added**: One-click IP Block button on the logs page.
- **Improved**: AI Train → Site Content now has a default “All by Post Type” mode with per‑type checkboxes, live counts (e.g., Posts 34, Pages 20), and better progress/stop controls. You can still switch to “Pick Specific” to select items per page.
- **Improved**: Performance, cache and UI improvements on the AI Train page.
- **Improved**: New drag‑and‑drop with multi‑file queue for file uploads to Pinecone and Qdrant.
- **Improved**: Better text extracting for file uploads to Pinecone and Qdrant.
- **Improved**: More detailed logs for the Content Assistant and Content Writer modules.
- **Improved**: Support for additional file types in file uploads: TXT, PDF, HTML, and DOCX.
- **Improved**: Vector score threshold for content writer and automated tasks.
- **Fixed**: Missing vector count display for Pinecone and Qdrant.
- **Fixed**: Chat footer text now resizes properly and no longer gets cut off.
- **Fixed**: Resolved decimal_max_decimal_places_exceeded chatbot error.
- **Fixed**: Content Writer & Automated Task scheduling when using "Use Dates from Input". Dates were previously ignored and posts published immediately. Now, multi-format date parsing (ISO, dash, slash, compact) correctly schedules future posts and aligns Run Now with cron behavior.
- **Fixed**: CSV upload issues in the Content Writer module.
- **Fixed**: Image Generator was not visible to logged-in users.

= 2.3.37 =

- **Added**: Document Chunking support for Pinecone and Qdrant. You can now configure chunk size and overlap in the settings. File uploads to Pinecone and Qdrant are token-aware and automatically split into chunks, preventing token limit issues and improving retrieval quality.
- **Added**: REST Logs Handler for accessing logs via the plugin’s REST API, enabling external integrations.
- **Added**: One-click IP Block button on the logs page.
- **Improved**: AI Train → Site Content now has a default “All by Post Type” mode with per‑type checkboxes, live counts (e.g., Posts 34, Pages 20), and better progress/stop controls. You can still switch to “Pick Specific” to select items per page.
- **Improved**: Performance, cache and UI improvements on the AI Train page.
- **Improved**: New drag‑and‑drop with multi‑file queue for file uploads to Pinecone and Qdrant.
- **Improved**: Better text extracting for file uploads to Pinecone and Qdrant.
- **Improved**: More detailed logs for the Content Assistant and Content Writer modules.
- **Improved**: Support for additional file types in file uploads: TXT, PDF, HTML, and DOCX.
- **Improved**: Vector score threshold for content writer and automated tasks.
- **Fixed**: Missing vector count display for Pinecone and Qdrant.
- **Fixed**: Chat footer text now resizes properly and no longer gets cut off.
- **Fixed**: Resolved decimal_max_decimal_places_exceeded chatbot error.
- **Fixed**: Content Writer & Automated Task scheduling when using "Use Dates from Input". Dates were previously ignored and posts published immediately. Now, multi-format date parsing (ISO, dash, slash, compact) correctly schedules future posts and aligns Run Now with cron behavior.
- **Fixed**: CSV upload issues in the Content Writer module.
- **Fixed**: Image Generator was not visible to logged-in users.

= 2.3.36 =

- **Added**: A Vector Score Threshold option for the Chatbot and AI Forms. You can now set a minimum relevance score to exclude the low score results from the AI context.  
- **Added**: Support for Azure Embedding Providers. You can now deploy and use your own embedding models from Azure.  
- **Added**: A new setting under *AI Train → Settings* to show or hide the Index button on Post/Product lists.  
- **Improved**: The positioning of the Index and Content Assistant buttons on the post list screen for better usability.  
- **Improved**: Logging details, which now include vector database scores, payload information, and additional context for clearer debugging and transparency.  
- **Improved**: In Image Generator settings, **Provider & Model Filtering** now supports selecting models directly via the UI instead of manually entering their IDs. 
- **Improved**: Speech-to-Text (chat voice input) now uploads audio via multipart/form-data instead of large Base64 strings, improving compatibility with security plugins (e.g., WordFence), reducing request size, and adding MIME/size validation.
- **Improved**: Content Assistant now automatically applies safe inline formatting (bold / italic) returned by AI in both Block and Classic editors.
- **Fixed**: Token usage tracking for Azure image models in the Image Generator module, which is now recorded correctly.  

= 2.3.35 =

- **Added**: `{original_tags}` and `{categories}` placeholders for the Content Writer.
- **Fixed**: Chatbot Banned Words filter now matches only whole words to avoid false positives.
- **Fixed**: Improved chatbot widget visibility to ensure it stays above other page elements.

= 2.3.34 =

- **Added**: Auto-Delete Logs — automatically removes logs after a specified period using a WP-Cron job.
- **Improved**: Search UI on the AI Train page.
- **Fixed**: Removed the "temperature" parameter for OpenAI’s gpt-5, o1, o3, and o4 models, as they do not support this parameter.

= 2.3.33 =

- **Added**: Support for the latest OpenAI gpt-5 models. You can use "Sync button" to load them.
- **Added**: New "Reasoning Effort" parameter for OpenAI’s reasoning models.
- **Improved**: Content Assistant UI and overall functionality.
- **Fixed**: An issue where a race condition, particularly with autosave enabled, could cause all API keys and settings in the dashboard to be wiped out. The save process is now defensive against database read failures.

= 2.3.32 =

- **New Feature**: Embed Anywhere. You can now embed your chatbots on any external, non-WP website using a simple HTML snippet.

[Read more about this new feature here](https://docs.aipower.org/docs/chat#embed-anywhere-external-sites)

- **Fixed**: Fixed an issue where chatbot assets failed to load when the shortcode was placed inside a modal or other dynamically loaded content.
- **Improved**: The Default Template in Content Writer is now fully editable. Admins can view, edit, rename, and delete all templates. Your last-used template is now auto-loaded during the same browser session.


= 2.3.31 =

- **New Feature**: Embed Anywhere. You can now embed your chatbots on any external, non-WP website using a simple HTML snippet.

[Read more about this new feature here](https://docs.aipower.org/docs/chat#embed-anywhere-external-sites)

- **Fixed**: Fixed an issue where chatbot assets failed to load when the shortcode was placed inside a modal or other dynamically loaded content.
- **Improved**: The Default Template in Content Writer is now fully editable. Admins can view, edit, rename, and delete all templates. Your last-used template is now auto-loaded during the same browser session.

= 2.3.30 =

- **New Feature**: Embed Anywhere. You can now embed your chatbots on any external, non-WP website using a simple HTML snippet.

[Read more about this new feature here](https://docs.aipower.org/docs/chat#embed-anywhere-external-sites)

- **Added**: Web search support in AI Forms. You can now enable web search using either OpenAI or Google directly within your AI forms.
- **Improved**: The last selected chatbot from the dropdown is now automatically remembered and loaded when returning to the Chatbot page.
- **Fixed**: An issue in the AI Training module where console errors appeared when vector stores were deleted from the provider but still referenced locally.

= 2.3.29 =

- **Added**: You can now sort task queue entries in the Automated Tasks module by Name, Type, Status, Attempt, and Time columns.
- **Fixed**: An issue on the AI Train page where the Settings tab failed to load in certain cases.
- **Fixed**: Fixed a bug that prevented voice recording from working properly in the Chatbot module.

= 2.3.28 =

- **Added**: Support for Google’s latest image models (Imagen 4.0 Preview and Imagen 4.0 Ultra Preview) across the Content Writer, Automated Tasks, Chatbot, and Image Generator modules.
- **Added**: Support for Azure OpenAI DALL·E image generation models across all modules. You can now use Azure-deployed DALL·E models for image generation in the Content Writer, Automated Tasks, Chatbot, and Image Generator.
- **Added**: A new “One-time” frequency option for Automated Tasks in both the AutoGPT and Content Writer modules. Tasks set to “One-time” will automatically pause after completion.
- **Fixed**: An issue where real-time voice agents were not respecting token limits, allowing guests and users with a 0-token limit to start voice sessions.
- **Fixed**: An issue in the Automated Tasks module where the “X Value” and “Image Size” settings for Content Writing tasks were not correctly loaded when editing a task.
- **Fixed**: An issue in the Content Writer's “Single” generation mode where requesting multiple images from stock photo providers like Pexels or Pixabay resulted in duplicate images. It now fetches unique images.
- **Fixed**: An issue where the “Run Now” button for the “Auto-Reply to Comments” task did not process all eligible comments—only those created since the last scheduled run.

= 2.3.27 =

- Added: Advanced Content Indexing Controls. You can now select which custom post types, fields, labels and taxonomies for vector indexing.

[Advanced Content Indexing Controls](https://docs.aipower.org/docs/ai-training/intro#advanced-content-indexing-controls)

- Fixed: Fixed a bug in "Update Existing Content" category in automated tasks.
- Fixed: Fixed a bug in Tag generation feature in the Content Assistant.
- Improved: Clear wp object caching after plugin update.


= 2.3.26 =

- **Fixed**: Fixed an issue where pasting long code snippets into the chatbot would fail.
- **Improved**: Chatbot shortcode can now be embedded inside modal windows.
- **Improved**: Enhanced chatbot text area for better touch interaction.
- **Improved**: Improved vector database syncing on the post list screen.
- **Improved**: UI refinements for the Content Assistant module.

= 2.3.25 =

- **Improved**: Improved syncing of indexes across OpenAI, Pinecone, and Qdrant vector databases.
- **Fixed**: The block_message trigger now consistently blocks all subsequent user messages within the same session, as intended in the chatbot.
- **Note**: Renamed plugin from AI Power to AIP to begin the transition to the new branding.

= 2.3.24 =

- **Added**: Google Veo 3 video generation support in the Image Generator module.
- **Added**: Option to enable or disable the safety checker for Replicate image models.
- **Improved**: Enhanced shortcode validation for Chatbot and AI Forms modules.

[Veo 3](https://docs.aipower.org/docs/image-generator)

= 2.3.23 =

- Improved: Added support for deleting individual records from vector indexes.
- Fixed: Writer templates were not being triggered during plugin activation in some cases. Added a more reliable mechanism to ensure required tables are created.
- Fixed: Fixed an issue in Automated Tasks where the {url_content} placeholder was not recognized during title generation.
- Fixed: Fixed an issue in the Write module where saving the selected image model was not working correctly.
- Fixed: Prevented in-content images from being generated when the option was not selected in the Write module.
- Fixed: Fixed a loop issue that caused meta description, tags, and excerpt to be generated multiple times.
- Fixed: Addressed an issue in Automated Tasks where featured images or content images were being generated even when disabled.

= 2.3.22 =

- NEW: Realtime Voice Agents (OpenAI only) feature added to the chatbot module, enabling fully interactive voice conversations.  

[Learn more in the documentation](https://docs.aipower.org/docs/voice-features)

= 2.3.21 =

- NEW: Realtime Voice Agents (OpenAI only) feature added to the chatbot module, enabling fully interactive voice conversations.  

[Learn more in the documentation](https://docs.aipower.org/docs/voice-features)

= 2.3.20 =

- Bug fixes and performance improvements.

= 2.3.19 =

- Improved: Object caching in various modules for better performance.
- Minor bug fixes.

= 2.3.18 =

- Fixed: An issue where the Image Generator frontend shortcode was not populating the AI model list.
- Fixed: Removed various debug console.log statements from production code to improve performance and clean up the browser console.

= 2.3.17 =

- Fixed Vector DB selection in Automated Tasks
- Updated Freemius vendor path
- Minor bug fixes

= 2.3.16 =

- Freemius SDK update.
- Bug fixes.

= 2.3.15 =

- Added: URL Optimization support for Content Writer, Automated Tasks, and Content Assistant.
- Added: {product_categories} placeholder for the WooCommerce Product Writer.
- Added: Support for top_p, frequency_penalty, and presence_penalty parameters in AI Forms. Note: OpenAI's new Responses API does not support frequency_penalty and presence_penalty, but other providers still do.
- Added: {source_url} placeholder is now available for prompts in the RSS module.
- Improved: Improved RSS history handling.
- Improved: Increased maximum token limit to 128,000.

= 2.3.14 =

- Fixed: Non-latin character rendering issues in AI Forms.
- Fixed: URL parsing bug in the Content Writer module.
- Fixed: Compatibility issue with The SEO Framework plugin.
- Added: Delete function for automated tasks queue items.
- Improved: Error handling in Content Assistant.

= 2.3.13 =

This is a major update.

While a Migration Tool is available, I strongly recommend **not migrating old data** and instead starting with a fresh setup.

The new version includes powerful features like **OpenAI Vector Store** support, which is faster and easier to manage.

If you were previously using external services like **Pinecone** or **Qdrant**, you might not need them anymore.

The new built-in OpenAI Vector Store is optimized for performance and fully integrated with AI Power 2.3+.

If you still need to migrate old data please check the [migration tool](https://docs.aipower.org/docs/getting-started/migration-from-legacy)

Please check our [documentation](https://docs.aipower.org/) for the new features.
