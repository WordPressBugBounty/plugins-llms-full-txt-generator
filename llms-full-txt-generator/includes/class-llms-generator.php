<?php
if (!defined('ABSPATH')) exit;

class LLMS_Generator
{
    private $cron_hook;

    public function __construct($cron_hook)
    {
        $this->cron_hook = $cron_hook;
        add_action($this->cron_hook, [$this, 'generate_llms_txt_files']);
        add_action('update_option_llms_full_txt_generator_update_frequency', [$this, 'handle_frequency'], 10, 2);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);
    }

    /**
     * Main generation function - Free version only
     */
    public function generate_llms_txt_files($files_to_generate = null)
    {
        if ($files_to_generate === null) {
            $files_to_generate = get_option('llms_full_txt_generator_files_to_generate', ['llms.txt', 'llms-full.txt']);
        }
        if (!is_array($files_to_generate)) $files_to_generate = [];

        $root_dir = ABSPATH;  // This is the REAL web root of your WordPress install
        $llms_txt_path      = $root_dir . 'llms.txt';
        $llms_full_txt_path = $root_dir . 'llms-full.txt';

        $selected_post_types = get_option('llms_full_txt_generator_post_types', []);
        $respect_seo         = get_option('llms_full_txt_generator_respect_seo', true);
        $include_urls_raw    = get_option('llms_full_txt_generator_include_urls', '');
        $exclude_urls_raw    = get_option('llms_full_txt_generator_exclude_urls', '');

        // Parse URLs (one per line)
        $include_urls = array_filter(array_map('trim', explode("\n", $include_urls_raw)));
        $exclude_urls = array_filter(array_map('trim', explode("\n", $exclude_urls_raw)));

        // === HEADER WITH SITE NAME, TAGLINE & EMAIL ===
        $site_name = get_bloginfo('name');
        $site_desc = get_bloginfo('description');
        $include_admin_email = get_option('llms_full_txt_generator_include_admin_email', true);
        $admin_email = get_option('llms_full_txt_generator_admin_email', get_option('admin_email'));

        $header = "# {$site_name}\n\n";
        if ($site_desc) {
            $header .= "> {$site_desc}\n\n";
        }

        if ($include_admin_email && !empty($admin_email)) {
            $header .= "> Contact: " . esc_html($admin_email) . "\n\n";
        }

        $llms_txt_content     = in_array('llms.txt', $files_to_generate) ? $header : null;
        $llms_full_txt_content = in_array('llms-full.txt', $files_to_generate) ? $header : null;

        // Add full export link in llms.txt
        if ($llms_txt_content !== null && in_array('llms-full.txt', $files_to_generate)) {
            $llms_txt_content .= "## Full Content Export\n";
            $llms_txt_content .= "- **URL**: " . home_url('/llms-full.txt') . "\n\n";
        }

        $items_by_type = [];

        // === 1. Collect content from selected post types ===
        if (!empty($selected_post_types)) {
            // Respect custom order
            $order_str = get_option('llms_full_txt_generator_post_types_order', '');
            if ($order_str) {
                $ordered = array_filter(explode(',', $order_str));
                $reordered = [];
                foreach ($ordered as $pt) {
                    if (in_array($pt, $selected_post_types)) $reordered[] = $pt;
                }
                foreach ($selected_post_types as $pt) {
                    if (!in_array($pt, $reordered)) $reordered[] = $pt;
                }
                $selected_post_types = $reordered;
            }

            foreach ($selected_post_types as $post_type) {
                $pto = get_post_type_object($post_type);
                $label = $pto ? $pto->labels->name : ucfirst($post_type);

                $status = ($post_type === 'attachment') ? 'inherit' : 'publish';

                $posts = get_posts([
                    'post_type'      => $post_type,
                    'posts_per_page' => -1,
                    'post_status'    => $status,
                    'orderby'        => 'title',
                    'order'          => 'ASC',
                ]);

                foreach ($posts as $post) {
                    $permalink = get_permalink($post->ID);  // For display in output

                    // For exclusion matching: use direct file URL if it's an attachment
                    $url_for_matching = $permalink;
                    if ($post_type === 'attachment') {
                        $direct_url = wp_get_attachment_url($post->ID);
                        if ($direct_url) {
                            $url_for_matching = $direct_url;
                        }
                    }

                    // Respect SEO: skip noindex + robots.txt blocked
                    if ($respect_seo) {
                        if ($this->has_noindex_meta($post->ID)) continue;
                        if ($this->is_blocked_by_robots($url_for_matching)) continue;
                    }

                    // Exclude URL patterns — now checks actual file extension for attachments
                    $skip = false;
                    foreach ($exclude_urls as $pattern) {
                        if ($this->match_url_rule($url_for_matching, $pattern)) {
                            $skip = true;
                            break;
                        }
                    }
                    if ($skip) continue;

                    // Clean content
                    $content = do_shortcode($post->post_content);
                    $content = wp_strip_all_tags($content);
                    $content = preg_replace('/<!--.*?-->/s', '', $content);
                    $content = trim(preg_replace('/\n{3,}/', "\n\n", $content));

                    $items_by_type[$label][] = [
                        'title'   => $post->post_title ?: '(No Title)',
                        'url'     => $permalink,  // Use clean permalink for output
                        'content' => $content,
                    ];
                }
            }
        }

        // === 2. Add up to 3 manual URLs (free limit) ===
        $manual_count = 0;
        foreach ($include_urls as $raw) {
            if ($manual_count >= 3) break;
            if (preg_match('/^\*\.\w+$/', $raw)) continue; // Skip *.pdf (Pro)

            $url = (strpos($raw, 'http') === 0) ? $raw : home_url('/') . ltrim($raw, '/');

            if ($respect_seo && $this->is_blocked_by_robots($url)) continue;

            $title = trim(parse_url($url, PHP_URL_PATH) ?? '', '/');
            $title = $title === '' ? 'Home' : ucwords(str_replace(['-', '_'], ' ', $title));

            $items_by_type['Additional URLs'][] = [
                'title'   => $title,
                'url'     => $url,
                'content' => '',
            ];
            $manual_count++;
        }

        // === 3. Generate llms.txt (links only) ===
        if ($llms_txt_content !== null) {
            foreach ($items_by_type as $type => $items) {
                if (empty($items)) continue;
                $llms_txt_content .= "### {$type}\n\n";
                foreach ($items as $item) {
                    $llms_txt_content .= "- [{$item['title']}]({$item['url']})\n";
                }
                $llms_txt_content .= "\n";
            }
            file_put_contents($llms_txt_path, "\xEF\xBB\xBF" . $llms_txt_content);
        }

        // === 4. Generate llms-full.txt (with content) ===
        if ($llms_full_txt_content !== null) {
            foreach ($items_by_type as $type => $items) {
                if (empty($items)) continue;
                $llms_full_txt_content .= "### {$type}\n\n";
                foreach ($items as $item) {
                    $llms_full_txt_content .= "#### {$item['title']}\n";
                    if ($item['content']) {
                        $llms_full_txt_content .= $item['content'] . "\n\n";
                    } else {
                        $llms_full_txt_content .= "URL: {$item['url']}\n\n";
                    }
                }
            }
            file_put_contents($llms_full_txt_path, "\xEF\xBB\xBF" . $llms_full_txt_content);
        }

        // Success message (only in admin)
        if (function_exists('add_settings_error')) {
            $generated = [];
            if (in_array('llms.txt', $files_to_generate)) $generated[] = 'llms.txt';
            if (in_array('llms-full.txt', $files_to_generate)) $generated[] = 'llms-full.txt';

            if ($generated) {
                $msg = count($generated) === 1
                    ? sprintf(__('Generated %s successfully.', 'llms-full-txt-generator'), $generated[0])
                    : __('Generated llms.txt and llms-full.txt successfully.', 'llms-full-txt-generator');

                add_settings_error('llms_txt_generator', 'generated', $msg, 'success');
            }
        }
    }

    // Simple URL pattern matching
    private function match_url_rule($url, $rule)
    {
        $rule = trim($rule);
        if ($rule === '') return false;

        // Extract just the path (ignore query strings, fragments)
        $parsed = parse_url($url);
        $path   = isset($parsed['path']) ? $parsed['path'] : '/';
        $path   = '/' . ltrim($path, '/');  // Ensure leading slash

        // If rule is full URL, extract its path
        if (strpos($rule, 'http') === 0) {
            $rule_parsed = parse_url($rule);
            $rule = isset($rule_parsed['path']) ? $rule_parsed['path'] : '';
            if ($rule === '') return false;
        }

        // Wildcard rule (*.png, /private/*, etc.)
        if (strpos($rule, '*') !== false) {
            // Escape and replace * with .*
            $pattern = preg_quote($rule, '#');
            $pattern = str_replace('\\*', '.*', $pattern);

            // Build regex: case-insensitive, full path match
            $pattern = '#^' . $pattern . '$#i';

            return preg_match($pattern, $path) === 1;
        }

        // Exact match (no wildcard)
        $rule_path = '/' . ltrim($rule, '/');
        return rtrim($path, '/') === rtrim($rule_path, '/');
    }

    // Check robots.txt
    private function is_blocked_by_robots($url)
    {
        static $disallowed = null;
        if ($disallowed === null) {
            $response = wp_remote_get(home_url('/robots.txt'), ['timeout' => 5]);
            if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
                $body = wp_remote_retrieve_body($response);
                preg_match_all('/^Disallow:\s*(.+)$/im', $body, $matches);
                $disallowed = array_map('trim', $matches[1] ?? []);
            } else {
                $disallowed = [];
            }
        }

        $path = wp_parse_url($url, PHP_URL_PATH) ?: '/';
        foreach ($disallowed as $rule) {
            if ($rule === '' || $rule === '/') return true;
            if (strpos($path, $rule) === 0) return true;
        }
        return false;
    }

    // Check noindex meta (Yoast, Rank Math, SEOPress, AIOSEO)
    private function has_noindex_meta($post_id)
    {
        if (get_option('blog_public') === '0') return true;

        // Yoast
        if (get_post_meta($post_id, '_yoast_wpseo_meta-robots-noindex', true) === '1') return true;

        // Rank Math
        $rm = get_post_meta($post_id, 'rank_math_robots', true);
        if (is_array($rm) && in_array('noindex', $rm)) return true;

        // SEOPress
        if (get_post_meta($post_id, '_seopress_robots_index', true) === 'yes') return true;

        // AIOSEO
        if (function_exists('aioseo')) {
            global $wpdb;
            $noindex = $wpdb->get_var($wpdb->prepare(
                "SELECT robots_noindex FROM {$wpdb->prefix}aioseo_posts WHERE post_id = %d",
                $post_id
            ));
            if ($noindex === '1') return true;
        }

        return false;
    }

    // Cron frequency handler
    public function handle_frequency($old, $new)
    {
        wp_clear_scheduled_hook($this->cron_hook);
        if ($new && $new !== 'manual') {
            wp_schedule_event(time(), $new, $this->cron_hook);
        }
    }

    // Deactivation
    public function deactivate()
    {
        wp_clear_scheduled_hook($this->cron_hook);
    }
}
