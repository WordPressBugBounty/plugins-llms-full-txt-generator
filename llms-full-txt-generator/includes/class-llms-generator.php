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

        // Register dynamic rewrite rules & fallback template redirect
        add_action('init', [$this, 'add_rewrite_rules']);
        add_action('template_redirect', [$this, 'handle_dynamic_request'], 5);
    }

    /**
     * Get real root directory of WordPress installation
     */
    public static function get_root_directory()
    {
        if (function_exists('get_home_path')) {
            $path = get_home_path();
        } else {
            $path = ABSPATH;
        }
        return rtrim($path, '/\\') . '/';
    }

    /**
     * Safely delete generated files
     */
    public static function delete_generated_files()
    {
        $root_dir = self::get_root_directory();
        $allowed_files = ['llms.txt', 'llms-full.txt'];
        foreach ($allowed_files as $filename) {
            $path = $root_dir . $filename;
            if (file_exists($path) && is_file($path)) {
                @unlink($path);
            }
        }
    }

    /**
     * Plugin activation hook handler
     */
    public static function activate_plugin()
    {
        $instance = new self('llms_txt_generator_auto_update');
        $instance->add_rewrite_rules();
        if (function_exists('flush_rewrite_rules')) {
            flush_rewrite_rules();
        }
    }

    /**
     * Plugin deactivation hook handler
     */
    public static function deactivate_plugin()
    {
        wp_clear_scheduled_hook('llms_txt_generator_auto_update');
        self::delete_generated_files();
        if (function_exists('flush_rewrite_rules')) {
            flush_rewrite_rules();
        }
    }

    /**
     * Register rewrite rules for virtual llms.txt & llms-full.txt endpoints
     */
    public function add_rewrite_rules()
    {
        add_rewrite_rule('^llms\.txt$', 'index.php?llms_txt_file=llms.txt', 'top');
        add_rewrite_rule('^llms-full\.txt$', 'index.php?llms_txt_file=llms-full.txt', 'top');
        add_filter('query_vars', function ($vars) {
            $vars[] = 'llms_txt_file';
            return $vars;
        });
    }

    /**
     * Dynamic request handler for llms.txt & llms-full.txt
     */
    public function handle_dynamic_request()
    {
        $file = get_query_var('llms_txt_file');
        if (empty($file)) {
            $path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
            if (rtrim($path, '/') === '/llms.txt') {
                $file = 'llms.txt';
            } elseif (rtrim($path, '/') === '/llms-full.txt') {
                $file = 'llms-full.txt';
            }
        }

        if (!in_array($file, ['llms.txt', 'llms-full.txt'], true)) {
            return;
        }

        $root_dir  = self::get_root_directory();
        $file_path = $root_dir . $file;

        $content = '';
        if (file_exists($file_path) && is_file($file_path)) {
            $content = file_get_contents($file_path);
        } else {
            $content = $this->get_generated_content_for_file($file);
        }

        if ($content) {
            status_header(200);
            header('Content-Type: text/plain; charset=utf-8');
            header('X-Robots-Tag: noarchive');
            echo $content;
            exit;
        }
    }

    /**
     * Switch current user context to anonymous (logged-out visitor)
     */
    private function switch_to_anonymous_context()
    {
        $previous_user_id = get_current_user_id();
        wp_set_current_user(0);
        return $previous_user_id;
    }

    /**
     * Restore previous user context
     */
    private function restore_user_context($previous_user_id)
    {
        wp_set_current_user($previous_user_id);
    }

    /**
     * Check if site-wide password protection is active (e.g. Password Protected plugin)
     */
    public function is_site_password_protected()
    {
        $is_protected = false;

        // 1. Password Protected plugin check
        if (get_option('password_protected_status') || (function_exists('password_protected_is_active') && password_protected_is_active())) {
            $is_protected = true;
        }

        // 2. Popular Maintenance Mode / Coming Soon plugins
        if (get_option('vmm_status') === '1' || get_option('seedprod_coming_soon_enable') === '1' || get_option('seedprod_maintenance_mode_enable') === '1') {
            $is_protected = true;
        }

        // 3. Force Login plugin
        if (function_exists('vfl_force_login')) {
            $is_protected = true;
        }

        // 4. Extensible filter
        return (bool) apply_filters('llms_txt_generator_is_site_password_protected', $is_protected);
    }

    /**
     * Check if a post is publicly viewable by an anonymous visitor
     */
    private function is_post_publicly_accessible($post)
    {
        if (!$post || !($post instanceof WP_Post)) {
            return false;
        }

        // 0. If site-wide password protection is active, all posts are protected from anonymous visitors
        if ($this->is_site_password_protected()) {
            return false;
        }

        // 1. Post type viewable check
        if (!is_post_type_viewable($post->post_type)) {
            return false;
        }

        // 2. Post status viewable check
        if ($post->post_type === 'attachment') {
            if ($post->post_parent > 0) {
                $parent = get_post($post->post_parent);
                if (!$parent || !$this->is_post_publicly_accessible($parent)) {
                    return false;
                }
            }
        } else {
            if (!is_post_status_viewable($post->post_status)) {
                return false;
            }
        }

        // 3. Password protected check - passwords must NEVER be bypassed
        if (!empty($post->post_password) || post_password_required($post)) {
            return false;
        }

        // 4. Core WP viewable check
        if (function_exists('is_post_publicly_viewable') && !is_post_publicly_viewable($post)) {
            return false;
        }

        // 5. Extensible filter for plugins / access control
        return (bool) apply_filters('llms_txt_generator_is_post_accessible', true, $post);
    }

    /**
     * Render post content safely through WordPress filters as an anonymous user
     */
    private function get_rendered_post_content($post_obj)
    {
        global $post;
        $previous_global_post = $post;
        $post = $post_obj;
        setup_postdata($post);

        $rendered = apply_filters('the_content', $post_obj->post_content);

        $post = $previous_global_post;
        if ($previous_global_post) {
            setup_postdata($previous_global_post);
        } else {
            wp_reset_postdata();
        }

        if (empty($rendered) || strpos($rendered, 'post_password') !== false || strpos($rendered, 'password_form') !== false) {
            return '';
        }

        $content = wp_strip_all_tags($rendered);
        $content = preg_replace('/<!--.*?-->/s', '', $content);
        $content = trim(preg_replace('/\n{3,}/', "\n\n", $content));

        return $content;
    }

    /**
     * Write file atomically using temporary file swap
     */
    private function write_file_atomically($filepath, $content)
    {
        $dir = dirname($filepath);
        $temp_file = $dir . '/' . wp_unique_filename($dir, basename($filepath) . '.tmp.' . wp_generate_password(6, false));

        $written = @file_put_contents($temp_file, "\xEF\xBB\xBF" . $content, LOCK_EX);
        if ($written === false) {
            if (file_exists($temp_file)) {
                @unlink($temp_file);
            }
            return false;
        }

        $renamed = @rename($temp_file, $filepath);
        if (!$renamed) {
            $copied = @copy($temp_file, $filepath);
            @unlink($temp_file);
            return $copied;
        }

        return true;
    }

    /**
     * Helper to generate text string for a specific file (llms.txt or llms-full.txt)
     */
    public function get_generated_content_for_file($target_filename)
    {
        $contents = $this->build_content_data([$target_filename]);
        return $contents[$target_filename] ?? '';
    }

    /**
     * Core content builder algorithm running under Anonymous User Context
     */
    private function build_content_data($files_to_generate)
    {
        if (!is_array($files_to_generate)) $files_to_generate = [];

        $selected_post_types = get_option('llms_full_txt_generator_post_types', []);
        $respect_seo         = get_option('llms_full_txt_generator_respect_seo', true);
        $include_urls_raw    = get_option('llms_full_txt_generator_include_urls', '');
        $exclude_urls_raw    = get_option('llms_full_txt_generator_exclude_urls', '');

        $include_urls = array_filter(array_map('trim', explode("\n", $include_urls_raw)));
        $exclude_urls = array_filter(array_map('trim', explode("\n", $exclude_urls_raw)));

        $previous_user_id = $this->switch_to_anonymous_context();

        $output = [];

        try {
            $site_name = get_bloginfo('name');
            $site_desc = get_bloginfo('description');
            $admin_email = get_option('llms_full_txt_generator_admin_email', get_option('admin_email'));

            $header = "# {$site_name}\n\n";
            if ($site_desc) {
                $header .= "> {$site_desc}\n\n";
            }

            if (!empty($admin_email)) {
                $header .= "> Contact: " . esc_html($admin_email) . "\n\n";
            }

            $llms_txt_content      = in_array('llms.txt', $files_to_generate) ? $header : null;
            $llms_full_txt_content = in_array('llms-full.txt', $files_to_generate) ? $header : null;

            if ($llms_txt_content !== null && in_array('llms-full.txt', $files_to_generate)) {
                $llms_txt_content .= "## Full Content Export\n";
                $llms_txt_content .= "- **URL**: " . home_url('/llms-full.txt') . "\n\n";
            }

            $items_by_type = [];

            if (!empty($selected_post_types)) {
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
                    if (!$pto || !is_post_type_viewable($post_type)) {
                        continue;
                    }

                    $label  = $pto->labels->name ?: ucfirst($post_type);
                    $status = ($post_type === 'attachment') ? 'inherit' : 'publish';

                    $posts = get_posts([
                        'post_type'      => $post_type,
                        'posts_per_page' => -1,
                        'post_status'    => $status,
                        'orderby'        => 'title',
                        'order'          => 'ASC',
                        'suppress_filters' => false,
                    ]);

                    foreach ($posts as $post) {
                        // Security check: Verify post is publicly accessible to anonymous visitors
                        if (!$this->is_post_publicly_accessible($post)) {
                            continue;
                        }

                        $permalink = get_permalink($post->ID);
                        $url_for_matching = $permalink;
                        if ($post_type === 'attachment') {
                            $direct_url = wp_get_attachment_url($post->ID);
                            if ($direct_url) {
                                $url_for_matching = $direct_url;
                            }
                        }

                        if ($respect_seo) {
                            if ($this->has_noindex_meta($post->ID)) continue;
                        }

                        $skip = false;
                        foreach ($exclude_urls as $pattern) {
                            if ($this->match_url_rule($url_for_matching, $pattern)) {
                                $skip = true;
                                break;
                            }
                        }
                        if ($skip) continue;

                        $content = $this->get_rendered_post_content($post);

                        $items_by_type[$label][] = [
                            'title'   => get_the_title($post) ?: '(No Title)',
                            'url'     => $permalink,
                            'content' => $content,
                        ];
                    }
                }
            }

            // Manual URLs
            $manual_count = 0;
            foreach ($include_urls as $raw) {
                if ($manual_count >= 3) break;
                if (preg_match('/^\*\.\w+$/', $raw)) continue;

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

            if ($llms_txt_content !== null) {
                foreach ($items_by_type as $type => $items) {
                    if (empty($items)) continue;
                    $llms_txt_content .= "### {$type}\n\n";
                    foreach ($items as $item) {
                        $llms_txt_content .= "- [{$item['title']}]({$item['url']})\n";
                    }
                    $llms_txt_content .= "\n";
                }
                $output['llms.txt'] = $llms_txt_content;
            }

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
                $output['llms-full.txt'] = $llms_full_txt_content;
            }
        } finally {
            $this->restore_user_context($previous_user_id);
        }

        return $output;
    }

    /**
     * Main generation function - executed in Anonymous Visitor Context
     */
    public function generate_llms_txt_files($files_to_generate = null)
    {
        if ($files_to_generate === null) {
            $files_to_generate = get_option('llms_full_txt_generator_files_to_generate', ['llms.txt', 'llms-full.txt']);
        }
        if (!is_array($files_to_generate)) $files_to_generate = [];

        $root_dir           = self::get_root_directory();
        $llms_txt_path      = $root_dir . 'llms.txt';
        $llms_full_txt_path = $root_dir . 'llms-full.txt';

        $content_data = $this->build_content_data($files_to_generate);

        if (isset($content_data['llms.txt'])) {
            $this->write_file_atomically($llms_txt_path, $content_data['llms.txt']);
        }

        if (isset($content_data['llms-full.txt'])) {
            $this->write_file_atomically($llms_full_txt_path, $content_data['llms-full.txt']);
        }

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

    private function match_url_rule($url, $rule)
    {
        $rule = trim($rule);
        if ($rule === '') return false;

        $parsed = parse_url($url);
        $path   = isset($parsed['path']) ? $parsed['path'] : '/';
        $path   = '/' . ltrim($path, '/');

        if (strpos($rule, 'http') === 0) {
            $rule_parsed = parse_url($rule);
            $rule = isset($rule_parsed['path']) ? $rule_parsed['path'] : '';
            if ($rule === '') return false;
        }

        if (strpos($rule, '*') !== false) {
            $pattern = preg_quote($rule, '#');
            $pattern = str_replace('\\*', '.*', $pattern);
            $pattern = '#^' . $pattern . '$#i';
            return preg_match($pattern, $path) === 1;
        }

        $rule_path = '/' . ltrim($rule, '/');
        return rtrim($path, '/') === rtrim($rule_path, '/');
    }

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

    private function has_noindex_meta($post_id)
    {
        if (get_option('blog_public') === '0') return true;

        if (get_post_meta($post_id, '_yoast_wpseo_meta-robots-noindex', true) === '1') return true;

        $rm = get_post_meta($post_id, 'rank_math_robots', true);
        if (is_array($rm) && in_array('noindex', $rm)) return true;

        if (get_post_meta($post_id, '_seopress_robots_index', true) === 'yes') return true;

        if (function_exists('aioseo')) {
            global $wpdb;
            $table = $wpdb->prefix . 'aioseo_posts';
            if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table)) === $table) {
                $noindex = $wpdb->get_var($wpdb->prepare(
                    "SELECT robots_noindex FROM {$table} WHERE post_id = %d",
                    $post_id
                ));
                if ($noindex === '1') return true;
            }
        }

        return false;
    }

    public function handle_frequency($old, $new)
    {
        wp_clear_scheduled_hook($this->cron_hook);
        if ($new && $new !== 'manual') {
            wp_schedule_event(time(), $new, $this->cron_hook);
        }
    }
}
