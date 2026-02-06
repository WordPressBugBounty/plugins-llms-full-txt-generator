<?php
/*
Plugin Name: LLMS Full TXT Generator
Description: Automatically generates llms.txt and llms-full.txt files in the root directory of your WordPress website. Supports SEO settings from WordPress core, Yoast SEO, Rank Math, SEOPress, and All in One SEO.
Version: 2.0.6
Author: rankth
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: llms-full-txt-generator

*/

if (!defined('ABSPATH')) exit;

define('LLMS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('LLMS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('LLMS_BUILD_DIR', LLMS_PLUGIN_DIR . 'build');
define('LLMS_BUILD_URL', LLMS_PLUGIN_URL . 'build');

require_once LLMS_PLUGIN_DIR . 'includes/class-llms-loader.php';


add_action( 'before_woocommerce_init', function() {

    if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
    }

} );

new LLMS_Loader();