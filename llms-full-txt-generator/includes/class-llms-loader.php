<?php
if (!defined('ABSPATH')) exit;

class LLMS_Loader {
    private $cron_hook = 'llms_txt_generator_auto_update';

    public function __construct() {
        $this->load_classes();
        $this->init_hooks();
    }

    private function load_classes() {
        require_once LLMS_PLUGIN_DIR . 'includes/class-llms-generator.php';
        require_once LLMS_PLUGIN_DIR . 'includes/class-llms-rest-api.php';
        require_once LLMS_PLUGIN_DIR . 'includes/class-llms-admin.php';
        require_once LLMS_PLUGIN_DIR . 'includes/class-llms-helpers.php';
        require_once LLMS_PLUGIN_DIR . 'includes/class-llms-license.php';

        new LLMS_Generator($this->cron_hook);
        new LLMS_REST_API();
        new LLMS_Admin();
        new LLMS_License();
    }

    private function init_hooks() {
        add_action('before_woocommerce_init', [$this, 'declare_woocommerce_compatibility']);
        add_filter('cron_schedules', [$this, 'add_weekly_schedule']);
    }

    public function declare_woocommerce_compatibility() {
        if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_checkout_blocks', __FILE__, true);
        }
    }

    public function add_weekly_schedule($schedules) {
        $schedules['weekly'] = [
            'interval' => 7 * DAY_IN_SECONDS,
            'display'  => __('Once Weekly')
        ];
        return $schedules;
    }
}