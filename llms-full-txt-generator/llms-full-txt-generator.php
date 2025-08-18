<?php
/*
Plugin Name: LLMS Full TXT Generator
Description: Automatically generates llms.txt and llms-full.txt files in the root directory of your WordPress website. Supports SEO settings from WordPress core, Yoast SEO, Rank Math, SEOPress, and All in One SEO.
Version: 2.0.2
Author: rankth
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: llms-full-txt-generator
*/

if (!defined('ABSPATH')) {
    exit;
}

class LLMS_Full_Txt_Generator {
    private $robots_rules = null;
    private $cron_hook = 'llms_txt_generator_auto_update';

    public function __construct() {
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_post_generate_llms_txt', array($this, 'handle_generate_llms_txt'));
        add_action($this->cron_hook, array($this, 'generate_llms_txt_files'));
        add_action('admin_init', array($this, 'maybe_schedule_updates'));
        add_action('admin_init', array($this, 'maybe_initialize_settings'));
        add_filter('cron_schedules', array($this, 'add_weekly_cron_schedule'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate_scheduled_updates'));
    }

    public function maybe_initialize_settings() {
        if (get_option('llms_full_txt_generator_initialized')!=='yes') {
            // Get all public post types
            $post_types = get_post_types(array('public' => true), 'objects');
            
            // Filter out the 'attachment' (media) post type
            $selected_post_types = array();
            foreach ($post_types as $post_type) {
                if ($post_type->name !== 'attachment') {
                    $selected_post_types[] = $post_type->name;
                }
            }
            
            update_option('llms_full_txt_generator_post_types', $selected_post_types);
            update_option('llms_full_txt_generator_initialized', 'yes');
        }
    }

    public function register_settings() {
     

        register_setting(
            'llms_full_txt_generator_settings',
            'llms_full_txt_generator_post_types',
            array(
                'type' => 'array',
                'sanitize_callback' => array($this, 'sanitize_post_types'),
                'default' => array()
            )
        );

        register_setting(
            'llms_full_txt_generator_settings',
            'llms_full_txt_generator_include_excerpt',
            array(
                'type' => 'boolean',
                'sanitize_callback' => 'rest_sanitize_boolean',
                'default' => false
            )
        );

        register_setting(
            'llms_full_txt_generator_settings',
            'llms_full_txt_generator_include_urls',
            array(
                'type' => 'string',
                'sanitize_callback' => 'sanitize_textarea_field',
                'default' => ''
            )
        );

        register_setting(
            'llms_full_txt_generator_settings',
            'llms_full_txt_generator_exclude_urls',
            array(
                'type' => 'string',
                'sanitize_callback' => 'sanitize_textarea_field',
                'default' => ''
            )
        );

        register_setting(
            'llms_full_txt_generator_settings',
            'llms_full_txt_generator_post_types_order',
            array(
                'type' => 'string',
                'sanitize_callback' => 'sanitize_textarea_field',
                'default' => ''
            )
        );

        register_setting(
            'llms_full_txt_generator_settings',
            'llms_full_txt_generator_files_to_generate',
            array(
                'type' => 'array',
                'sanitize_callback' => array($this, 'sanitize_files_to_generate'),
                'default' => array('llms.txt', 'llms-full.txt')
            )
        );

        register_setting(
            'llms_full_txt_generator_settings',
            'llms_full_txt_generator_respect_seo',
            array(
                'type' => 'boolean',
                'sanitize_callback' => 'rest_sanitize_boolean',
                'default' => true
            )
        );

        register_setting(
            'llms_full_txt_generator_settings',
            'llms_full_txt_generator_update_frequency',
            array(
                'type' => 'string',
                'sanitize_callback' => array($this, 'sanitize_update_frequency'),
                'default' => 'manual'
            )
        );
    }

    public function sanitize_post_types($input) {
        // Handle empty input (no checkboxes selected)
        if (empty($input)) {
            return array();
        }
        return array_map('sanitize_text_field', (array) $input);
    }

    public function sanitize_files_to_generate($input) {
        // Skip validation if we're in Settings tab
        if (isset($_POST['option_page']) && $_POST['option_page'] === 'llms_full_txt_generator_settings') {
            return get_option('llms_full_txt_generator_files_to_generate', array('llms.txt', 'llms-full.txt'));
        }
   
        if (empty($input)) {
            add_settings_error(
                'llms_full_txt_generator_files_to_generate',
                'no_files_selected',
                __('You must select at least one file type to generate.', 'llms-full-txt-generator'),
                'error'
            );
            return get_option('llms_full_txt_generator_files_to_generate', array('llms.txt', 'llms-full.txt'));
        }
        return array_map('sanitize_text_field', $input);
    }

    public function sanitize_update_frequency($input) {
        $valid_frequencies = array('daily', 'weekly', 'manual');
        if (!in_array($input, $valid_frequencies)) {
            add_settings_error(
                'llms_full_txt_generator_update_frequency',
                'invalid_frequency',
                __('Invalid update frequency selected.', 'llms-full-txt-generator'),
                'error'
            );
            return get_option('llms_full_txt_generator_update_frequency', 'manual');
        }
        return $input;
    }

    public function add_admin_menu() {
        add_options_page(
            __('LLMS Full TXT Generator Settings', 'llms-full-txt-generator'),
            __('LLMS Full TXT Generator', 'llms-full-txt-generator'),
            'manage_options',
            'llms-full-txt-generator',
            array($this, 'admin_page')
        );
    }

    public function admin_page() {
        if (isset($_GET['llms_generated']) && $_GET['llms_generated'] === 'true') {
            add_settings_error('llms_txt_generator', 'files_generated', __('LLMS.txt files generated successfully.', 'llms-full-txt-generator'), 'updated');
        }
        settings_errors('llms_txt_generator');
        include plugin_dir_path(__FILE__) . 'admin-page.php';
    }

    public function handle_generate_llms_txt() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'llms-full-txt-generator'));
        }

        if (!isset($_POST['llms_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['llms_nonce'])), 'llms_generate_action')) {
            wp_die(__('Invalid nonce specified', 'llms-full-txt-generator'));
        }

        $files_to_generate = isset($_POST['llms_full_txt_generator_files_to_generate']) 
            ? array_map('sanitize_text_field', $_POST['llms_full_txt_generator_files_to_generate'])
            : array();

        if (empty($files_to_generate)) {
            wp_safe_redirect(admin_url('options-general.php?page=llms-full-txt-generator&tab=generate&error=no_files'));
            exit;
        }

        // Save the selection for next time
        update_option('llms_full_txt_generator_files_to_generate', $files_to_generate);
     
        $this->generate_llms_txt_files($files_to_generate);

        wp_safe_redirect(add_query_arg('llms_generated', 'true', admin_url('options-general.php?page=llms-full-txt-generator')));
        exit;
    }

    public function generate_llms_txt_files($files_to_generate = null) {
        // Reset robots rules cache before generation
        $this->robots_rules = null;

        if ($files_to_generate === null) {
            $files_to_generate = get_option('llms_full_txt_generator_files_to_generate', array('llms.txt', 'llms-full.txt'));
        }

        $root_dir = ABSPATH;
        $llms_txt_path = $root_dir . '/llms.txt';
        $llms_full_txt_path = $root_dir . '/llms-full.txt';
        $selected_post_types = get_option('llms_full_txt_generator_post_types', array());

        if (empty($selected_post_types)) {
            // Clear only the selected files
            foreach ($files_to_generate as $file) {
                $file_path = $root_dir . '/' . $file;
                if (file_exists($file_path)) {
                    file_put_contents($file_path, "\xEF\xBB\xBF"); // Write empty file with UTF-8 BOM
                }
            }
            // Only add settings error if we're in admin context (function exists)
            if (function_exists('add_settings_error')) {
                add_settings_error('llms_txt_generator', 'no_post_types', __('No post types selected. Selected files have been cleared.', 'llms-full-txt-generator'), 'updated');
            }
            return;
        }

        // Sort post types if order is specified
        $post_types_order = get_option('llms_full_txt_generator_post_types_order', '');
        if (!empty($post_types_order)) {
            $ordered_types = array_filter(explode(',', $post_types_order));
            $reordered_types = array();
            
            foreach ($ordered_types as $type) {
                if (in_array($type, $selected_post_types)) {
                    $reordered_types[] = $type;
                }
            }
            
            foreach ($selected_post_types as $type) {
                if (!in_array($type, $reordered_types)) {
                    $reordered_types[] = $type;
                }
            }
            
            $selected_post_types = $reordered_types;
        }

        $site_name = get_bloginfo('name');
        $site_description = get_bloginfo('description');
        $header_content = "# {$site_name}\n\n";
        if (!empty($site_description)) {
            $header_content .= "> {$site_description}\n\n";
        }

        $include_excerpt = get_option('llms_full_txt_generator_include_excerpt', false);
        $include_urls = $this->parse_url_rules(get_option('llms_full_txt_generator_include_urls', ''));
        $exclude_urls = $this->parse_url_rules(get_option('llms_full_txt_generator_exclude_urls', ''));

        // Initialize content variables only for selected files
        $llms_txt_content = in_array('llms.txt', $files_to_generate) ? $header_content : null;
        $llms_full_txt_content = in_array('llms-full.txt', $files_to_generate) ? $header_content : null;

        // Create an array to store all URLs grouped by post type
        $urls_by_post_type = array();

        // Initialize files group for storing file URLs
        $urls_by_post_type['files'] = array(
            'name' => __('Additional Files', 'llms-full-txt-generator'),
            'items' => array()
        );

        // First collect all pages/posts from selected post types
        foreach ($selected_post_types as $post_type) {
            $post_type_obj = get_post_type_object($post_type);
            $post_type_name = $post_type_obj ? $post_type_obj->labels->name : ucfirst($post_type);
            $urls_by_post_type[$post_type] = array(
                'name' => $post_type_name,
                'items' => array()
            );
            
            $posts = get_posts(array('post_type' => $post_type, 'posts_per_page' => -1));
            foreach ($posts as $post) {
                if ($post->ID && !$this->has_noindex_meta($post->ID)) {
                    $post_url = get_permalink($post->ID);
                    // Skip if URL matches any exclude pattern
                    $should_exclude = false;
                    foreach ($exclude_urls as $exclude_pattern) {
                        if ($this->match_url_rule($post_url, $exclude_pattern)) {
                            $should_exclude = true;
                            break;
                        }
                    }
                    if (!$should_exclude) {
                        $item = array(
                            'url' => $post_url,
                            'title' => $post->post_title,
                            'content' => $post->post_content,
                            'excerpt' => $post->post_excerpt,
                            'is_password_protected' => post_password_required($post)
                        );

                        // Add media-specific metadata for attachment post type
                        if ($post_type === 'attachment') {
                            $item['is_file'] = true;
                            $item['alt'] = get_post_meta($post->ID, '_wp_attachment_image_alt', true);
                            $item['caption'] = $post->post_excerpt; // WordPress stores caption in post_excerpt for attachments
                            $item['description'] = $post->post_content; // WordPress stores description in post_content for attachments
                        }

                        $urls_by_post_type[$post_type]['items'][] = $item;
                    }
                }
            }
        }

        // 2. Add manually included URLs
        if (!empty($include_urls)) {
            // Add a special group for manually included URLs
            $urls_by_post_type['manual'] = array(
                'name' => 'Additional URLs',
                'items' => array()
            );
            
            $site_url = get_site_url();
            foreach ($include_urls as $url_pattern) {
                // Skip file extension patterns as they'll be handled separately
                if (preg_match('/^\*\.([\w]+)$/', $url_pattern)) {
                    continue;
                }

                $url = (strpos($url_pattern, 'http') === 0) ? $url_pattern : rtrim($site_url, '/') . '/' . ltrim($url_pattern, '/');
                // Skip if URL matches any exclude pattern
                $should_exclude = false;
                foreach ($exclude_urls as $exclude_pattern) {
                    if ($this->match_url_rule($url, $exclude_pattern)) {
                        $should_exclude = true;
                        break;
                    }
                }
                if (!$should_exclude) {
                    $title = basename(untrailingslashit($url));
                    $urls_by_post_type['manual']['items'][] = array(
                        'url' => $url,
                        'title' => $title,
                        'content' => '',
                        'excerpt' => '',
                        'is_password_protected' => false,
                        'is_manual' => true
                    );
                }
            }
        }

        // 3. Process file patterns and add to Additional Files section
        $urls_by_post_type['files'] = array(
            'name' => __('Additional Files', 'llms-full-txt-generator'),
            'items' => array()
        );

        foreach ($include_urls as $rule) {
            if (preg_match('/^\*\.([\w]+)$/', $rule, $matches)) {
                $extension = $matches[1];
                $file_urls = $this->scan_for_files($extension);
                if (!empty($file_urls)) {
                    $urls_by_post_type['files']['items'] = array_merge(
                        $urls_by_post_type['files']['items'],
                        $file_urls
                    );
                }
            }
        }

        // 3. Process all collected URLs by post type
        foreach ($urls_by_post_type as $post_type => $group) {
            if (!empty($group['items'])) {
                // Add post type header for llms.txt
                if ($llms_txt_content !== null) {
                    $llms_txt_content .= "\n## " . esc_html($group['name']) . "\n\n";
                    foreach ($group['items'] as $item) {
                        $llms_txt_content .= "- [" . esc_html($item['title']) . "](" . esc_url($item['url']) . ")\n";
                    }
                    $llms_txt_content .= "\n"; // Add extra space between post type groups
                }

                // Add to llms-full.txt
                if ($llms_full_txt_content !== null) {
                    $llms_full_txt_content .= "\n## " . esc_html($group['name']) . "\n\n";
                    foreach ($group['items'] as $item) {
                        if (isset($item['is_file']) && $item['is_file']) {
                            $llms_full_txt_content .= "### " . esc_html($item['title']) . "\n";
                            $llms_full_txt_content .= "- **URL**: " . esc_url($item['url']) . "\n";
                            if (!empty($item['alt'])) {
                                $llms_full_txt_content .= "- **Alt Text**: " . esc_html($item['alt']) . "\n";
                            }
                            if (!empty($item['caption'])) {
                                $llms_full_txt_content .= "- **Caption**: " . esc_html($item['caption']) . "\n";
                            }
                            if (!empty($item['description'])) {
                                $llms_full_txt_content .= "- **Description**: " . esc_html($item['description']) . "\n";
                            }
                            $llms_full_txt_content .= "\n";
                        } else if (isset($item['is_manual']) && $item['is_manual']) {
                            $llms_full_txt_content .= "### " . esc_html($item['title']) . "\nURL: " . esc_url($item['url']) . "\n\n";
                        } else if ($item['is_password_protected']) {
                    $llms_full_txt_content .= "### " . esc_html($item['title']) . "\n\n[Content is password protected]\n\n";
                } else {
                    $processed_content = do_shortcode($item['content']);
                    $content = wp_strip_all_tags($processed_content);
                    $full_entry = "### " . esc_html($item['title']) . "\n\n{$content}\n\n";
                    
                    if ($include_excerpt && !empty($item['excerpt'])) {
                        $processed_excerpt = do_shortcode($item['excerpt']);
                        $excerpt = wp_strip_all_tags($processed_excerpt);
                        $full_entry .= "Excerpt: {$excerpt}\n\n";
                    }
                    
                    $llms_full_txt_content .= $full_entry;
                        }
                    }
                }
            }
        }

        if ($llms_txt_content !== null) {
            $llms_txt_content .= "\n";
        }
        if ($llms_full_txt_content !== null) {
            $llms_full_txt_content .= "\n";
        }

        $files_generated = array();

        // Generate only selected files
        if ($llms_txt_content !== null) {
            $llms_txt_content = html_entity_decode($llms_txt_content, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            file_put_contents($llms_txt_path, "\xEF\xBB\xBF" . $llms_txt_content);
            $files_generated[] = 'llms.txt';
        }
        if ($llms_full_txt_content !== null) {
            $llms_full_txt_content = html_entity_decode($llms_full_txt_content, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            file_put_contents($llms_full_txt_path, "\xEF\xBB\xBF" . $llms_full_txt_content);
            $files_generated[] = 'llms-full.txt';
        }

        $message = sprintf(
            _n(
                'Generated %s file successfully.',
                'Generated %s files successfully.',
                count($files_generated),
                'llms-full-txt-generator'
            ),
            implode(' and ', $files_generated)
        );
        
        // Only add settings error if we're in admin context (function exists)
        if (function_exists('add_settings_error')) {
            add_settings_error('llms_txt_generator', 'files_generated', $message, 'updated');
        }
    }

    private function parse_url_rules($rules_string) {
        return array_filter(array_map('trim', explode("\n", sanitize_textarea_field($rules_string))));
    }

    private function should_include_url($url, $include_rules, $exclude_rules, $post_id = null) {
        $relative_url = wp_make_link_relative(esc_url_raw(trim($url)));
        
        // Check robots.txt and noindex if the setting is enabled
        if (get_option('llms_full_txt_generator_respect_seo', true)) {
            if ($this->is_blocked_by_robots($url) || ($post_id && $this->has_noindex_meta($post_id))) {
                return false;
            }
        }
        
        // First check if URL is explicitly included
        $explicitly_included = false;
        foreach ($include_rules as $rule) {
            $rule = trim(sanitize_text_field($rule));
            if ($this->match_url_rule($relative_url, $rule)) {
                $explicitly_included = true;
                break;
            }
        }
     
        // Then check exclude rules - these take precedence even over explicit includes
        foreach ($exclude_rules as $rule) {
            $rule = trim(sanitize_text_field($rule));
            if ($this->match_url_rule($relative_url, $rule)) {
                return false;
            }
        }

        // If there are include rules, only include if explicitly included or if it's a post URL
        if (!empty($include_rules)) {
            return $explicitly_included || $post_id !== null;
        }

        // If no include rules, include everything that wasn't excluded
        return true;
    }

    private function has_wildcard_patterns($rules) {
        foreach ($rules as $rule) {
            if (strpos($rule, '*') !== false) {
                return true;
            }
        }
        return false;
    }

    private function match_url_rule($url, $rule) {
        // Make both URLs relative for comparison
        $url = wp_make_link_relative(esc_url_raw(trim($url)));
        $rule = trim($rule);
        
        // If the rule starts with http, make it relative
        if (strpos($rule, 'http') === 0) {
            $rule = wp_make_link_relative($rule);
        }
        
        // If the rule doesn't start with /, add it
        if (strpos($rule, '/') !== 0) {
            $rule = '/' . $rule;
        }

        // If the URL doesn't start with /, add it
        if (strpos($url, '/') !== 0) {
            $url = '/' . $url;
        }

        // Handle trailing slashes consistently
        $url = rtrim($url, '/') . '/';
        $rule = rtrim($rule, '/') . '/';

        // Check if this is a wildcard rule
        if (strpos($rule, '*') !== false) {
            // Convert the wildcard pattern to a regex pattern
            $pattern = preg_quote($rule, '/');
            $pattern = str_replace('\*', '.*', $pattern);
            $pattern = '/^' . $pattern . '/i';
            return preg_match($pattern, $url);
        } else {
            // For exact path matching (no wildcards)
            // Only match the exact path or immediate children
            $rule_parts = explode('/', trim($rule, '/'));
            $url_parts = explode('/', trim($url, '/'));
            
            // If URL has fewer parts than rule, it can't match
            if (count($url_parts) < count($rule_parts)) {
                return false;
            }
            
            // For exact matches, paths must match exactly
            if (count($rule_parts) === count($url_parts)) {
                return $url === $rule;
            }
            
            // For child paths, all rule parts must match at the beginning
            foreach ($rule_parts as $i => $part) {
                if ($part !== $url_parts[$i]) {
                    return false;
                }
            }
            
            // If we get here, it's a child path
            return false;
        }
    }

    private function get_robots_rules() {
        // Return cached rules if already fetched
        if ($this->robots_rules !== null) {
            return $this->robots_rules;
        }

        $this->robots_rules = array();
        $robots_url = home_url('/robots.txt');
        $robots_content = wp_remote_get($robots_url);
        
        if (is_wp_error($robots_content) || wp_remote_retrieve_response_code($robots_content) !== 200) {
            return $this->robots_rules;
        }

        $robots_text = wp_remote_retrieve_body($robots_content);
        $lines = explode("\n", $robots_text);
        $current_agent = '*';
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || strpos($line, '#') === 0) {
                continue;
            }
            
            if (preg_match('/User-agent:\s*(.+)/i', $line, $matches)) {
                $current_agent = trim($matches[1]);
                if (!isset($this->robots_rules[$current_agent])) {
                    $this->robots_rules[$current_agent] = array();
                }
                continue;
            }
            
            if (preg_match('/Disallow:\s*(.+)/i', $line, $matches)) {
                $path = trim($matches[1]);
                if (!empty($path)) {
                    $this->robots_rules[$current_agent][] = $path;
                }
            }
        }
        
        return $this->robots_rules;
    }

    private function is_blocked_by_robots($url) {
        $rules = $this->get_robots_rules();
        
        // No rules or no universal rules (*), so nothing is blocked
        if (empty($rules) || !isset($rules['*'])) {
            return false;
        }

        $relative_url = wp_make_link_relative($url);
        foreach ($rules['*'] as $path) {
            $path = wp_make_link_relative($path);
            if ($path === '/') {
                return true;
            }
            if (strpos($relative_url, $path) === 0) {
                return true;
            }
        }
        
        return false;
    }

    // Removed scan_for_files method as media files are handled through WordPress media library

    private function has_noindex_meta($post_id) {
        // Check WordPress core "Discourage search engines" setting first
        if (get_option('blog_public') === '0') {
            return true;
        }

        // Check for Yoast SEO meta
        if (function_exists('YoastSEO')) {
        
            $noindex = get_post_meta($post_id, '_yoast_wpseo_meta-robots-noindex', true);
            if ($noindex === '1') {
                return true;
            }
           
        }
       
        // Check for Rank Math meta
        if (class_exists('RankMath')) {
            $robots = get_post_meta($post_id, 'rank_math_robots', true);
         
            if (is_array($robots) && in_array('noindex', $robots)) {
                return true;
            }
        }
        
    // Check for SEOPress meta
    if (function_exists('seopress_init')) {
        // Check if noindex is enabled for this post
        $seopress_robots_index = get_post_meta($post_id, '_seopress_robots_index', true);
        if ($seopress_robots_index === 'yes') {
            return true;
        }
        
        // Check global SEOPress settings
        $seopress_titles_option = get_option('seopress_titles_option_name');
        if (!empty($seopress_titles_option)) {
            $post_type = get_post_type($post_id);
            // Check if noindex is enabled globally for this post type
            if (!empty($seopress_titles_option['seopress_titles_single_titles'][$post_type]['noindex'])) {
                return true;
            }
        }
    }


        // Check for All in One SEO meta
        if (function_exists('aioseo')) {
            global $wpdb;
            
            // Check new AIOSEO table first (v4.0+)
            $noindex = $wpdb->get_var($wpdb->prepare(
                "SELECT robots_noindex FROM {$wpdb->prefix}aioseo_posts WHERE post_id = %d",
                $post_id
            ));
            
            if ($noindex === '1') {
                return true;
            }
            
            // Fallback to old post meta for older versions
            $robots = get_post_meta($post_id, '_aioseo_noindex', true);
            if ($robots) {
                return true;
            }
        }
        
        // Check WordPress core per-post noindex setting
        if (get_post_meta($post_id, '_wp_robots_noindex', true)) {
            return true;
        }
        
        return false;
    }

    public function maybe_schedule_updates() {
        $frequency = get_option('llms_full_txt_generator_update_frequency', 'manual');
        $scheduled = wp_next_scheduled($this->cron_hook);

        // Clear existing schedule
        if ($scheduled) {
            wp_unschedule_event($scheduled, $this->cron_hook);
        }

        // Set up new schedule if not manual
        if ($frequency !== 'manual') {
            if (!$scheduled) {
                wp_schedule_event(time(), $frequency, $this->cron_hook);
            }
        }
    }

    public function deactivate_scheduled_updates() {
        $scheduled = wp_next_scheduled($this->cron_hook);
        if ($scheduled) {
            wp_unschedule_event($scheduled, $this->cron_hook);
        }
    }

    public function add_weekly_cron_schedule($schedules) {
        $schedules['weekly'] = array(
            'interval' => 7 * 24 * 60 * 60, // 7 days in seconds
            'display'  => __('Once Weekly', 'llms-full-txt-generator')
        );
        return $schedules;
    }
}

$generator = new LLMS_Full_Txt_Generator();
