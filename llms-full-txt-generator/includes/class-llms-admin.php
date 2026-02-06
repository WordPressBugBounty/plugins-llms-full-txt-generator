<?php
if (!defined('ABSPATH')) exit;

class LLMS_Admin {
    public function __construct() {
        add_action('admin_menu', [$this, 'add_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue']);
        add_action('admin_init', [$this, 'init_settings']);
    }

    public function add_menu() {
        add_options_page(
            'LLMS TXT Generator',
            'LLMS TXT Generator',
            'manage_options',
            'llms-full-txt-generator',
            [$this, 'render']
        );
    }

    public function render() {
        if (!current_user_can('manage_options')) wp_die('Unauthorized');
        echo '<div id="llms-react-root"></div>';
    }

    public function enqueue($hook) {
        if ('settings_page_llms-full-txt-generator' !== $hook) return;

        wp_enqueue_script('jquery-ui-sortable');
        wp_enqueue_style('wp-jquery-ui');

        $asset_file = LLMS_BUILD_DIR . '/assets.asset.php';
        if (!file_exists($asset_file)) {
            $helper = LLMS_PLUGIN_DIR . 'build-php-deps.php';
            if (file_exists($helper)) include $helper;
        }

        $asset = file_exists($asset_file) ? include $asset_file : [];
        $deps = $asset['dependencies'] ?? ['jquery'];
        $version = $asset['version'] ?? '1.0';

        wp_register_script(
            'llms-react',
            LLMS_BUILD_URL . '/index.js',
            $deps,
            $version,
            true
        );

        wp_localize_script('llms-react', 'llmsData', [
            'restUrl'  => esc_url_raw(rest_url('llms/v1')),
            'nonce'    => wp_create_nonce('wp_rest'),
            'homeUrl'  => home_url(),
            'cronHook' => 'llms_txt_generator_auto_update',
            'debug'    => defined('WP_DEBUG') && WP_DEBUG
        ]);

        wp_enqueue_script('llms-react');
        wp_enqueue_style('llms-react', LLMS_BUILD_URL . '/index.css', [], $version);

        wp_enqueue_script('dnd-kit-core', 'https://cdn.jsdelivr.net/npm/@dnd-kit/core@6.0.8/dist/core.umd.js', [], '6.0.8', true);
        wp_enqueue_script('dnd-kit-sortable', 'https://cdn.jsdelivr.net/npm/@dnd-kit/sortable@7.0.2/dist/sortable.umd.js', ['dnd-kit-core'], '7.0.2', true);
        wp_enqueue_script('dnd-kit-utilities', 'https://cdn.jsdelivr.net/npm/@dnd-kit/utilities@3.2.1/dist/utilities.umd.js', ['dnd-kit-core'], '3.2.1', true);
    }

    public function init_settings() {
        if (get_option('llms_full_txt_generator_initialized') !== 'yes') {
            update_option('llms_full_txt_generator_post_types', []);
            update_option('llms_full_txt_generator_post_types_order', '');
            update_option('llms_full_txt_generator_initialized', 'yes');
        }
    }
}