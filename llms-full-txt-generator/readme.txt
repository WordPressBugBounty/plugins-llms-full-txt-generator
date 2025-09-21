=== LLMs.txt and LLMs-Full.txt Generator ===
Contributors: rankth
Tags: llms, txt generator, AI LLM, rankmath, seo, Yoast, SEOPress, AIOSEO
Requires at least: 5.0
Tested up to: 6.8
Stable tag: 2.0.2
Requires PHP: 7.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Generate llms.txt and llms-full.txt files for WordPress to guide AI and LLMs, fully compatible with Yoast SEO, Rank Math, SEOPress, and All in One SEO.
== Description ==
The LLMS Full TXT Generator is a WordPress plugin designed to automatically generate llms.txt and llms-full.txt files in the root directory of your website. These files contain a structured list of your pages and posts, which can be useful for content indexing, AI training, and enhancing how AI systems interact with your site. By using these files, you can optimize your website for AI discovery and interaction, similar to how robots.txt guides search engines

Features:
* Customizable Post Types: Select which post types to include in the generated files.
* Enhanced Media Support: 
  - Full WordPress media library integration through attachment post type
  - Detailed media information including titles, URLs, alt text, captions, and descriptions
  - Structured media documentation in Markdown format
* Post Excerpts: Option to include post excerpts for more detailed content representation.
* URL Management: Include or exclude specific URLs or URL patterns using wildcards.
* Easy Regeneration: Regenerate files easily when content changes to keep them up-to-date.
* Enhanced SEO Integration: Fully compatible with all major SEO plugins including:
  - Yoast SEO
  - Rank Math
  - SEOPress
  - All in One SEO
* Smart URL Pattern Matching: Advanced path matching for better content organization
* Robots.txt Support: Respects your robots.txt configuration and noindex settings
* UTF-8 Support: Proper handling of special characters with UTF-8 BOM

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
Yes, you can specify URLs to include or exclude, and even use wildcards for pattern matching. For example, use `/products/*` to match all product pages or `/private/*` to exclude private content.

= What is the purpose of llms.txt and llms-full.txt files? =
These files help AI models understand and interact with your website more effectively by providing structured content summaries and detailed information.

= Which SEO plugins are supported? =
The plugin fully supports and respects noindex settings from:
* WordPress core "Discourage search engines" setting
* Yoast SEO
* Rank Math
* SEOPress (both global and individual post settings)
* All in One SEO (AIOSEO)

= How does the wildcard pattern matching work? =
You can use asterisk (*) as a wildcard in your include/exclude patterns. Examples:
* `/blog/*` - matches all blog posts
* `/2023/*` - matches all content from 2023
* `/private/*` - excludes all private content
* `/courses/*` - matches all course pages

= How do I structure the llms.txt file for optimal AI interaction? =
Use Markdown formatting to create a clear structure, including headings and links to key content sections.

= How are media files documented in llms-full.txt? =
Media files are documented in a structured format with detailed information:

Example for images:

* Image Title
  - **URL**: https://example.com/image.jpg
  - **Alt Text**: Descriptive alt text for the image
  - **Caption**: Image caption if available
  - **Description**: Detailed description of the image

Example for documents:
*  Document Title
  - **URL**: https://example.com/document.pdf
  - **Caption**: Document caption if available
  - **Description**: Description or summary of the document


This structured format helps AI systems better understand your media content.


== Screenshots ==
1. The LLMS Full TXT Generator settings page

== Changelog ==
= 2.0.2 =
* Added SEOPress integration with support for both global and individual post settings
* Improved URL pattern matching for better include/exclude functionality
* Fixed path matching issues with trailing slashes
* Enhanced wildcard pattern handling in URL rules
* Improved content organization by grouping entries by post type in both files
* Added post type headers and proper spacing for better readability
* Updated documentation with detailed wildcard usage examples

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
