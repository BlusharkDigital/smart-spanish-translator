# Smart Spanish Translator

Smart Spanish Translator is a custom WordPress plugin designed to automatically translate your website's content—including posts, pages, and custom post types—from English into Spanish using advanced AI APIs.

## Features

- **Multiple AI Translation Engines:** Choose between DeepL (best quality), Claude (Anthropic), or OpenAI (GPT-4o) to handle the translations while preserving complex HTML structures.
- **Physical Translations:** Creates a standalone, permanent WordPress post for the Spanish version in the database, ensuring zero performance hit on page load times and saving API costs.
- **Automatic & Manual Modes:** 
  - *Auto-Translate on Save:* Automatically translates content running asynchronously in the background whenever a post is published or updated.
  - *Bulk Actions:* Batch translate all untranslated English content from the plugin dashboard with one click.
  - *Manual Control:* Turn off translations per-page or trigger a re-translation directly from the WordPress editor.
- **URL & SEO Handling:** Accurately translates your URL slugs into Spanish (e.g., `/our-services/` becomes `/es/nuestros-servicios/`) and correctly formats H1 tags and `<title>` documents.
- **Navigation Menu Switcher:** Automatically injects a **🇺🇸 English / 🇪🇸 Español** language switcher into your primary navigation menu, dynamically linking between the English source and its Spanish counterpart.
- **Language Dropdown Override:** Use the Advanced Custom Field `related_lang_url` on any page to prevent the language switcher from automatically appearing, giving you fine-grained control over your navigation.
- **Automatic GitHub Updates:** This plugin automatically checks for and installs updates published via GitHub Releases!

## Installation
1. Go to your WordPress Dashboard > **Plugins > Add New**.
2. Upload the zipped plugin file or clone the repository into `wp-content/plugins/`.
3. Activate the plugin.
4. Go to **Settings > ES Translator** to configure your API Keys, select the translation engine, and specify the URL prefix for your Spanish pages.

## Usage
* **Settings Page:** Enter your DeepL, Claude, or OpenAI API key. Select the post types you wish to translate.
* **In the Editor:** Look for the **🇪🇸 Spanish Translation** meta box. Click "Translate Now" or check the box to mark the page as "Manually Translated" to prevent the system from auto-translating it.
* **Admin Dashboard:** Navigate to the **ES Translator** menu item to see translation statistics, perform bulk translations, and audit the current sync status.

*Developed specifically to maintain complex HTML structures, preserve specific SEO titles, and scale without high ongoing costs.*