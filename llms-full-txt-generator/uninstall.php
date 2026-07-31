<?php
/**
 * Fired when the plugin is uninstalled.
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

require_once __DIR__ . '/includes/class-llms-generator.php';

// Remove generated static files
LLMS_Generator::delete_generated_files();

// Clear scheduled cron events
wp_clear_scheduled_hook('llms_txt_generator_auto_update');

// Option keys to delete
$options_to_delete = [
    'llms_full_txt_generator_post_types',
    'llms_full_txt_generator_post_types_order',
    'llms_full_txt_generator_initialized',
    'llms_full_txt_generator_files_to_generate',
    'llms_full_txt_generator_include_excerpt',
    'llms_full_txt_generator_include_urls',
    'llms_full_txt_generator_exclude_urls',
    'llms_full_txt_generator_admin_email',
    'llms_full_txt_generator_include_admin_email',
    'llms_full_txt_generator_company_name',
    'llms_full_txt_generator_company_description',
    'llms_full_txt_generator_update_frequency',
    'llms_full_txt_generator_respect_seo',
    'llms_full_txt_generator_show_product_price',
    'llms_full_txt_generator_show_product_categories',
    'llms_full_txt_generator_show_product_tags',
    'llms_full_txt_generator_show_product_ratings',
    'llms_full_txt_generator_show_product_url',
    'llms_full_txt_generator_show_product_image_url',
    'llms_full_txt_generator_exclude_product_categories',
    'llms_full_txt_generator_exclude_product_tags',
    'llms_full_txt_generator_show_post_categories',
    'llms_full_txt_generator_show_post_tags',
    'llms_full_txt_generator_exclude_post_categories',
    'llms_full_txt_generator_exclude_post_tags',
    'llms_full_txt_generator_enabled',
    'llms_full_txt_generator_products_enabled',
    'llms_full_txt_generator_multilingual',
    'llms_txt_license_key',
    'llms_txt_license_status',
];

foreach ($options_to_delete as $option) {
    delete_option($option);
}
