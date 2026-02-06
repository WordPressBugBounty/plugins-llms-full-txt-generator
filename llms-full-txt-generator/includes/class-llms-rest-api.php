<?php
if (!defined('ABSPATH')) exit;

class LLMS_REST_API
{
    public function __construct()
    {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes()
    {
        register_rest_route('llms/v1', '/settings', [
            'methods'  => 'GET,POST',
            'callback' => [$this, 'handle_settings'],
            'permission_callback' => [$this, 'check_permissions']
        ]);

        register_rest_route('llms/v1', '/generate', [
            'methods'  => 'POST',
            'callback' => [$this, 'generate'],
            'permission_callback' => [$this, 'check_permissions']
        ]);

        register_rest_route('llms/v1', '/delete/(?P<file>[\w\.\-]+)', [
            'methods'  => 'POST',
            'callback' => [$this, 'delete_file'],
            'permission_callback' => [$this, 'check_permissions']
        ]);
    }

    public function check_permissions()
    {
        return current_user_can('manage_options');
    }

    public function handle_settings($req)
    {
        if ($req->get_method() === 'GET') {
            return $this->get_settings();
        }

        $data = $req->get_json_params();
        $this->save_settings($data);
        return ['success' => true];
    }
private function get_settings() {
    // Prevent fatal errors if WooCommerce not active
    $product_cats = [];
    $product_tags = [];
    if (class_exists('WooCommerce')) {
        $product_cats = get_terms(['taxonomy' => 'product_cat', 'hide_empty' => false]);
        $product_tags = get_terms(['taxonomy' => 'product_tag', 'hide_empty' => false]);
    }

    $post_cats = get_terms(['taxonomy' => 'category', 'hide_empty' => false]);
    $post_tags = get_terms(['taxonomy' => 'post_tag', 'hide_empty' => false]);

    // Safely convert terms (avoid WP_Error crash)
    $safe_terms = function($terms) {
        if (is_wp_error($terms) || empty($terms)) return [];
        return array_map(fn($t) => ['id' => $t->term_id, 'name' => $t->name], $terms);
    };

    $post_types = get_post_types(['public' => true], 'objects');
    $selected   = get_option('llms_full_txt_generator_post_types', []);
    $order_str  = get_option('llms_full_txt_generator_post_types_order', '');
    $order      = $order_str ? explode(',', $order_str) : array_keys($post_types);

    $ordered = [];
    foreach ($order as $name) {
        if (isset($post_types[$name])) {
            $ordered[$name] = $post_types[$name];
            unset($post_types[$name]);
        }
    }
    $ordered = array_merge($ordered, $post_types);

    return [
        'pluginEnabled'      => (bool) get_option('llms_full_txt_generator_enabled', true),
        'productsEnabled'    => (bool) get_option('llms_full_txt_generator_products_enabled', true),

        'postTypes' => array_map(fn($pt) => [
            'name'     => $pt->name,
            'label'    => $pt->label,
            'selected' => in_array($pt->name, $selected, true)
        ], array_values($ordered)),

        // THIS WAS MISSING — CAUSES 500 ERROR!
        'filesToGenerate' => array_map(fn($f) => [
            'value'   => $f,
            'checked' => in_array($f, get_option('llms_full_txt_generator_files_to_generate', ['llms.txt', 'llms-full.txt']), true)
        ], ['llms.txt', 'llms-full.txt']),

        'includeExcerpt'      => (bool) get_option('llms_full_txt_generator_include_excerpt', false),
        'includeUrls'         => get_option('llms_full_txt_generator_include_urls', ''),
        'excludeUrls'         => get_option('llms_full_txt_generator_exclude_urls', ''),
        'adminEmail'          => get_option('llms_full_txt_generator_admin_email', get_option('admin_email')),
        'includeAdminEmail'   => (bool) get_option('llms_full_txt_generator_include_admin_email', true),
        'companyName'         => get_option('llms_full_txt_generator_company_name', get_bloginfo('name')),
        
        'updateFrequency'     => get_option('llms_full_txt_generator_update_frequency', 'manual'),
        'respectSeo'          => (bool) get_option('llms_full_txt_generator_respect_seo', true),
        'multilingual'        => (bool) get_option('llms_full_txt_generator_multilingual', false),

        // Product Settings
        'product' => [
            'showPrice'          => (bool) get_option('llms_full_txt_generator_show_product_price', true),
            'showCategories'     => (bool) get_option('llms_full_txt_generator_show_product_categories', true),
            'showTags'           => (bool) get_option('llms_full_txt_generator_show_product_tags', true),
            'showRatings'        => (bool) get_option('llms_full_txt_generator_show_product_ratings', true),
            'showUrl'            => (bool) get_option('llms_full_txt_generator_show_product_url', true),
            'showImageUrl'       => (bool) get_option('llms_full_txt_generator_show_product_image_url', true),
            'excludeCategories'  => get_option('llms_full_txt_generator_exclude_product_categories', []),
            'excludeTags'        => get_option('llms_full_txt_generator_exclude_product_tags', []),
            'allCategories'      => $safe_terms($product_cats),
            'allTags'            => $safe_terms($product_tags),
        ],

        // Post Settings
        'post' => [
            'showCategories'     => (bool) get_option('llms_full_txt_generator_show_post_categories', true),
            'showTags'           => (bool) get_option('llms_full_txt_generator_show_post_tags', true),
            'excludeCategories'  => get_option('llms_full_txt_generator_exclude_post_categories', []),
            'excludeTags'        => get_option('llms_full_txt_generator_exclude_post_tags', []),
            'allCategories'      => $safe_terms($post_cats),
            'allTags'            => $safe_terms($post_tags),
        ],

        'existingFiles' => [
            'llms.txt'      => file_exists(ABSPATH . 'llms.txt') ? home_url('/llms.txt') : null,
            'llms-full.txt' => file_exists(ABSPATH . 'llms-full.txt') ? home_url('/llms-full.txt') : null,
        ]
    ];
}

    private function save_settings($data)
    {
        $incoming = $data['postTypes'] ?? [];
        $selected = [];
        $order = [];

        foreach ($incoming as $pt) {
            if (!empty($pt['selected'])) $selected[] = $pt['name'];
            $order[] = $pt['name'];
        }

        update_option('llms_full_txt_generator_post_types', $selected);
        update_option('llms_full_txt_generator_post_types_order', implode(',', $order));
        update_option('llms_full_txt_generator_files_to_generate', array_column($data['filesToGenerate'] ?? [], 'value'));

        update_option('llms_full_txt_generator_include_excerpt', !empty($data['includeExcerpt']));
        update_option('llms_full_txt_generator_include_urls', sanitize_textarea_field($data['includeUrls'] ?? ''));
        update_option('llms_full_txt_generator_exclude_urls', sanitize_textarea_field($data['excludeUrls'] ?? ''));
        update_option('llms_full_txt_generator_admin_email', sanitize_email($data['adminEmail'] ?? ''));
        update_option('llms_full_txt_generator_include_admin_email', !empty($data['includeAdminEmail']));
        update_option('llms_full_txt_generator_company_name', sanitize_text_field($data['companyName'] ?? ''));
        update_option('llms_full_txt_generator_company_description', sanitize_textarea_field($data['companyDescription'] ?? ''));
        update_option('llms_full_txt_generator_update_frequency', $data['updateFrequency'] ?? 'manual');
        update_option('llms_full_txt_generator_respect_seo', !empty($data['respectSeo']));

        $p = $data['product'] ?? [];
        update_option('llms_full_txt_generator_show_product_url', !empty($p['showUrl']));
        update_option('llms_full_txt_generator_show_product_image_url', !empty($p['showImageUrl']));
     
    
    }

    public function generate($req)
    {



        $files = $req->get_param('files') ?? [];
        $allowed = ['llms.txt', 'llms-full.txt'];
        $files = array_intersect($files, $allowed);

        if (empty($files)) {
            return new WP_Error('invalid_files', 'Invalid file selection.', ['status' => 400]);
        }

        try {
            do_action('llms_txt_generator_auto_update', $files);
            return ['success' => true, 'files' => array_values($files)];
        } catch (Exception $e) {
            return new WP_Error('generate_failed', $e->getMessage(), ['status' => 500]);
        }
    }

    public function delete_file($req)
    {
        $file = $req['file'];
        if (!in_array($file, ['llms.txt', 'llms-full.txt'])) {
            return new WP_Error('invalid', 'Invalid file.');
        }

        $path = ABSPATH . $file;
        if (file_exists($path)) unlink($path);

        return ['deleted' => $file];
    }
}
