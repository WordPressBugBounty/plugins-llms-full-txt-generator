=== LLMs.txt and LLMs-Full.txt Generator ===
Contributors: rankth
Tags: llms, txt generator, AI LLM, rankmath, seo, Yoast
Requires at least: 5.0
Tested up to: 6.8
Stable tag: 2.0.1
Requires PHP: 7.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Generate llms.txt and llms-full.txt files for WordPress to guide AI and LLMs, fully compatible with Yoast SEO and Rank Math.
== Description ==
The LLMS Full TXT Generator is a WordPress plugin designed to automatically generate llms.txt and llms-full.txt files in the root directory of your website. These files contain a structured list of your pages and posts, which can be useful for content indexing, AI training, and enhancing how AI systems interact with your site. By using these files, you can optimize your website for AI discovery and interaction, similar to how robots.txt guides search engines

Features:
* Customizable Post Types: Select which post types to include in the generated files.
* Post Excerpts: Option to include post excerpts for more detailed content representation.
* URL Management: Include or exclude specific URLs or URL patterns using wildcards.
* Easy Regeneration: Regenerate files easily when content changes to keep them up-to-date.
* SEO Integration: Fully compatible with popular SEO plugins like Yoast SEO and Rank Math.
* Robots.txt Support: Respects your robots.txt configuration and noindex settings.

== Installation ==
1. Upload the plugin files to the `/wp-content/plugins/llms-full-txt-generator` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Use the Settings->LLMS Full TXT Generator screen to configure the plugin and generate files.

== Frequently Asked Questions ==
= Where are the generated files stored? =
The llms.txt and llms-full.txt files are stored in the root directory of your WordPress installation.

= How often should I regenerate the files? =
It's recommended to regenerate the files whenever you make significant changes to your website content.

= Can I choose which post types are included in the generated files? =
Yes, you can select which post types to include in the plugin settings.

= Can I include or exclude specific URLs? =
Yes, you can specify URLs to include or exclude, and even use wildcards for pattern matching.

= What is the purpose of llms.txt and llms-full.txt files? =
These files help AI models understand and interact with your website more effectively by providing structured content summaries and detailed information.

= How do I structure the llms.txt file for optimal AI interaction? =
Use Markdown formatting to create a clear structure, including headings and links to key content sections


== Screenshots ==
1. The LLMS Full TXT Generator settings page

== Changelog ==
= 2.0.1 =
* PHP Error Fix

= 2.0.0 =
* Added no-index and robots.txt support 
* Added proper UTF-8 BOM handling for generated files
* Improved UX

= 1.9.1 =
* Fixed Security issues.

= 1.9 =
* Added option to choose which files to generate (llms.txt, llms-full.txt, or both).
* Updated button text to "Regenerate" when files already exist.
* Improved file URL display to only show existing files.

= 1.8 =
* Fixed critical error when no public post types are available.
* Added validation to ensure at least one post type is selected.

= 1.7 =
* Added URL include and exclude functionality with wildcard support.
* Improved error handling for file generation.

= 1.0 =
* Initial release.

== Upgrade Notice ==
= 2.0.0 =
This version improves the initial setup by excluding media files by default and adds better error handling. No action required for existing installations as your current settings will be preserved.

= 1.9 =
This version adds the ability to choose which files to generate and improves the user interface for existing files.
