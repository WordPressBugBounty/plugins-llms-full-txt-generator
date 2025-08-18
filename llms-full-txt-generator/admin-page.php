<?php
// If this file is called directly, abort
if (!defined('WPINC')) {
    die;
}

// Check user capabilities
if (!current_user_can('manage_options')) {
    return;
}

// Add jQuery UI
wp_enqueue_script('jquery-ui-sortable');
wp_enqueue_style('wp-jquery-ui-sortable');

// Get current tab
$current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'generate';

// Check for and display error messages
if (isset($_GET['error']) && $_GET['error'] === 'no_files') {
    add_settings_error(
        'llms_txt_generator',
        'no_files_selected',
        __('Please select at least one file type to generate.', 'llms-full-txt-generator'),
        'error'
    );
}
?>
<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <?php //ssettings_errors('llms_txt_generator'); ?>
    
    <nav class="nav-tab-wrapper">
        <a href="?page=llms-full-txt-generator&tab=generate" class="nav-tab <?php echo $current_tab === 'generate' ? 'nav-tab-active' : ''; ?>">
            <?php esc_html_e('Generate', 'llms-full-txt-generator'); ?>
        </a>
        <a href="?page=llms-full-txt-generator&tab=settings" class="nav-tab <?php echo $current_tab === 'settings' ? 'nav-tab-active' : ''; ?>">
            <?php esc_html_e('Settings', 'llms-full-txt-generator'); ?>
        </a>
    </nav>

    <div class="tab-content">
        <?php if ($current_tab === 'generate'): ?>
            <!-- Generate Tab Content -->
            <div class="generate-tab-content">
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <?php wp_nonce_field('llms_generate_action', 'llms_nonce'); ?>
                    <input type="hidden" name="action" value="generate_llms_txt">
                    
                    <table class="form-table">
                        <tr valign="top">
                            <th scope="row"><?php esc_html_e('Select Files to Generate', 'llms-full-txt-generator'); ?></th>
                            <td>
                                <?php
                                $files_to_generate = get_option('llms_full_txt_generator_files_to_generate', array('llms.txt', 'llms-full.txt'));
                                ?>
                                <label style="display: block; margin-bottom: 8px;">
                                    <input type="checkbox" name="llms_full_txt_generator_files_to_generate[]" value="llms.txt" 
                                        <?php checked(in_array('llms.txt', $files_to_generate)); ?>>
                                    <?php esc_html_e('llms.txt - Basic list of content with titles and URLs', 'llms-full-txt-generator'); ?>
                                </label>
                                <label style="display: block;">
                                    <input type="checkbox" name="llms_full_txt_generator_files_to_generate[]" value="llms-full.txt"
                                        <?php checked(in_array('llms-full.txt', $files_to_generate)); ?>>
                                    <?php esc_html_e('llms-full.txt - Detailed content including full text and excerpts', 'llms-full-txt-generator'); ?>
                                </label>
                            </td>
                        </tr>
                    </table>

                    <p>
                        <?php 
                        $button_text = (file_exists(ABSPATH . '/llms.txt') || file_exists(ABSPATH . '/llms-full.txt')) 
                            ? esc_attr__('Regenerate Selected Files', 'llms-full-txt-generator')
                            : esc_attr__('Generate Selected Files', 'llms-full-txt-generator');
                        ?>
                        <input type="submit" name="generate_llms_txt" class="button button-primary" value="<?php echo $button_text; ?>">
                    </p>
                </form>

                <?php
                $root_dir = ABSPATH;
                $llms_txt_path = $root_dir . '/llms.txt';
                $llms_full_txt_path = $root_dir . '/llms-full.txt';
                $existing_files = array();

                // Handle delete action through POST
                if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_file']) && isset($_POST['_wpnonce'])) {
                    if (wp_verify_nonce($_POST['_wpnonce'], 'delete_llms_file')) {
                        $file_to_delete = sanitize_text_field($_POST['delete_file']);
                        if (in_array($file_to_delete, array('llms.txt', 'llms-full.txt'))) {
                            $file_path = $root_dir . '/' . $file_to_delete;
                            if (file_exists($file_path) && unlink($file_path)) {
                                wp_redirect(add_query_arg('deleted', $file_to_delete, admin_url('options-general.php?page=llms-full-txt-generator')));
                                exit;
                            }
                        }
                    }
                    wp_die(__('Security check failed.', 'llms-full-txt-generator'));
                }

                // Show success message after redirect
                if (isset($_GET['deleted'])) {
                    $deleted_file = sanitize_text_field($_GET['deleted']);
                    if (in_array($deleted_file, array('llms.txt', 'llms-full.txt'))) {
                        add_settings_error(
                            'llms_txt_generator',
                            'file_deleted',
                            sprintf(__('Successfully deleted %s file.', 'llms-full-txt-generator'), $deleted_file),
                            'updated'
                        );
                    }
                }

                // Add a wrapper div for file listings with flexbox styling
                echo '<div class="llms-files-list">';

                if (file_exists($llms_txt_path)) {
                    $llms_txt_url = home_url('/llms.txt');
                    $existing_files['llms.txt'] = $llms_txt_url;
                    echo '<div class="llms-file-item">' . 
                        sprintf(
                            esc_html__('LLMS.txt file: %s', 'llms-full-txt-generator'),
                            '<a href="' . esc_url($llms_txt_url) . '" target="_blank">' . esc_html($llms_txt_url) . '</a>'
                        ) . 
                        '<form method="post" class="delete-form">
                            <input type="hidden" name="delete_file" value="llms.txt">
                            ' . wp_nonce_field('delete_llms_file', '_wpnonce', true, false) . '
                            <button type="submit" class="delete-file" 
                                onclick="return confirm(\'' . esc_js(__('Are you sure you want to delete this file? This action cannot be undone.', 'llms-full-txt-generator')) . '\');"
                                title="' . esc_attr__('Delete file', 'llms-full-txt-generator') . '">
                                <span class="dashicons dashicons-trash" style="color: #dc3232;"></span>
                            </button>
                        </form></div>';
                }
                if (file_exists($llms_full_txt_path)) {
                    $llms_full_txt_url = home_url('/llms-full.txt');
                    $existing_files['llms-full.txt'] = $llms_full_txt_url;
                    echo '<div class="llms-file-item">' . 
                        sprintf(
                            esc_html__('LLMS-Full.txt file: %s', 'llms-full-txt-generator'),
                            '<a href="' . esc_url($llms_full_txt_url) . '" target="_blank">' . esc_html($llms_full_txt_url) . '</a>'
                        ) . 
                        '<form method="post" class="delete-form">
                            <input type="hidden" name="delete_file" value="llms-full.txt">
                            ' . wp_nonce_field('delete_llms_file', '_wpnonce', true, false) . '
                            <button type="submit" class="delete-file" 
                                onclick="return confirm(\'' . esc_js(__('Are you sure you want to delete this file? This action cannot be undone.', 'llms-full-txt-generator')) . '\');"
                                title="' . esc_attr__('Delete file', 'llms-full-txt-generator') . '">
                                <span class="dashicons dashicons-trash" style="color: #dc3232;"></span>
                            </button>
                        </form></div>';
                }

                echo '</div>'; // Close files list wrapper
                ?>
            </div>

        <?php else: ?>
            <!-- Settings Tab Content -->
            <div class="settings-tab-content">
                <form method="post" action="options.php">
                    <?php
                    settings_fields('llms_full_txt_generator_settings');
                    do_settings_sections('llms_full_txt_generator_settings');
                    ?>
                    <table class="form-table">
                        <tr valign="top">
                            <th scope="row"><?php esc_html_e('Select Post Types', 'llms-full-txt-generator'); ?></th>
                            <td>
                                <ul id="sortable-post-types" class="sortable-list">
                                <?php
                                $post_types = get_post_types(array('public' => true), 'objects');
                                $selected_post_types = get_option('llms_full_txt_generator_post_types', array());
                                $post_types_order = get_option('llms_full_txt_generator_post_types_order', '');
                                $post_types_order = !empty($post_types_order) ? explode(',', $post_types_order) : array();
                                
                                // Sort post types based on saved order
                                $ordered_post_types = array();
                                foreach ($post_types_order as $post_type_name) {
                                    if (isset($post_types[$post_type_name])) {
                                        $ordered_post_types[$post_type_name] = $post_types[$post_type_name];
                                        unset($post_types[$post_type_name]);
                                    }
                                }
                                // Add remaining post types
                                $ordered_post_types = array_merge($ordered_post_types, $post_types);

                                foreach ($ordered_post_types as $post_type) {
                                    $checked = in_array($post_type->name, $selected_post_types) ? 'checked' : '';
                                    echo '<li class="ui-state-default" data-post-type="' . esc_attr($post_type->name) . '">';
                                    echo '<span class="dashicons dashicons-menu"></span>';
                                    echo '<label><input type="checkbox" name="llms_full_txt_generator_post_types[]" value="' . esc_attr($post_type->name) . '" ' . $checked . '> ' . esc_html($post_type->label) . '</label>';
                                    echo '</li>';
                                }
                                ?>
                                </ul>
                                <input type="hidden" name="llms_full_txt_generator_post_types_order" id="post-types-order" value="<?php echo esc_attr(implode(',', array_keys($ordered_post_types))); ?>">
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row"><?php esc_html_e('Include Excerpt', 'llms-full-txt-generator'); ?></th>
                            <td>
                                <input type="checkbox" name="llms_full_txt_generator_include_excerpt" value="1" <?php checked(1, get_option('llms_full_txt_generator_include_excerpt'), true); ?> />
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row"><?php esc_html_e('Include URLs', 'llms-full-txt-generator'); ?></th>
                            <td>
                                <textarea name="llms_full_txt_generator_include_urls" rows="5" cols="50"><?php echo esc_textarea(get_option('llms_full_txt_generator_include_urls')); ?></textarea>
                                <p class="description">
                                    <?php 
                                    echo wp_kses(
                                        __('Enter URLs to include, one per line. Examples:<br>
                                        • /checkout (checkout page)<br>
                                        • https://yoursitename/your-landing-page/ (your landing page)<br/>
                                        These included links will be appended with the list of links generated with selected posttypes.', 'llms-full-txt-generator'),
                                        array('br' => array())
                                    ); 
                                    ?>
                                </p>
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row"><?php esc_html_e('Exclude URLs', 'llms-full-txt-generator'); ?></th>
                            <td>
                                <textarea name="llms_full_txt_generator_exclude_urls" rows="5" cols="50"><?php echo esc_textarea(get_option('llms_full_txt_generator_exclude_urls')); ?></textarea>
                                <p class="description">
                                    <?php 
                                    echo wp_kses(
                                        __('Enter URLs to exclude, one per line. Examples:<br>
                                        • /private/* (exclude all pages under private)<br>
                                        • /draft-* (exclude URLs starting with draft-)<br>
                                        • *.tmp (exclude files ending with .tmp)<br>
                                        • /members/* (exclude member pages)<br>
                                        Excluded URLs take precedence over included URLs.', 'llms-full-txt-generator'),
                                        array('br' => array())
                                    ); 
                                    ?>
                                </p>
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row"><?php esc_html_e('Update Frequency', 'llms-full-txt-generator'); ?></th>
                            <td>
                                <select name="llms_full_txt_generator_update_frequency">
                                    <?php
                                    $current_frequency = get_option('llms_full_txt_generator_update_frequency', 'manual');
                                    $frequencies = array(
                                        'manual' => __('Manual (only when triggered by user)', 'llms-full-txt-generator'),
                                        'daily' => __('Daily', 'llms-full-txt-generator'),
                                        'weekly' => __('Weekly', 'llms-full-txt-generator'),
                                    );
                                    
                                    foreach ($frequencies as $value => $label) {
                                        printf(
                                            '<option value="%s" %s>%s</option>',
                                            esc_attr($value),
                                            selected($current_frequency, $value, false),
                                            esc_html($label)
                                        );
                                    }
                                    ?>
                                </select>
                                <p class="description"></p></p>
                                    <?php esc_html_e('Choose how often the plugin should automatically update the generated files. Set to Manual if you want to update only when clicking the Generate button.', 'llms-full-txt-generator'); ?>
                                </p>
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row"><?php esc_html_e('Respect SEO Settings', 'llms-full-txt-generator'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="llms_full_txt_generator_respect_seo" value="1" 
                                        <?php checked(1, get_option('llms_full_txt_generator_respect_seo', true), true); ?> />
                                    <?php esc_html_e('Exclude pages blocked by robots.txt or marked as noindex', 'llms-full-txt-generator'); ?>
                                </label>
                                <p class="description">
                                    <?php esc_html_e('When enabled, pages that are blocked in robots.txt or have noindex meta tags will be excluded from the generated files. This works with popular SEO plugins like Yoast SEO, Rank Math, SEOPress and All in One SEO.', 'llms-full-txt-generator'); ?>
                                </p>
                            </td>
                        </tr>
                    </table>
                    <?php submit_button(__('Save Settings', 'llms-full-txt-generator')); ?>
                </form>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.nav-tab-wrapper {
    margin-bottom: 20px;
}
.tab-content {
    padding: 20px 0;
}
.sortable-list {
    list-style-type: none;
    padding: 0;
    margin: 0;
    max-width: 400px;
}
.sortable-list li {
    padding: 10px;
    margin: 4px 0;
    background: #f5f5f5;
    border: 1px solid #ddd;
    cursor: move;
    display: flex;
    align-items: center;
}
.sortable-list .dashicons-menu {
    color: #999;
    margin-right: 10px;
    cursor: move;
    flex-shrink: 0;
    line-height: 1.4;
}
.sortable-list label {
    margin: 0;
    display: flex;
    align-items: center;
    gap: 8px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    line-height: 1.4;
}
.sortable-list input[type="checkbox"] {
    margin: 0;
}
.llms-files-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
    margin: 15px 0;
}
.llms-file-item {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 8px;
}
.delete-form {
    display: inline-flex;
    margin-left: 8px;
}
.delete-file {
    padding: 0;
    border: none;
    background: none;
    cursor: pointer;
    display: flex;
    align-items: center;
}
.delete-file:hover .dashicons-trash {
    color: #a00 !important;
}
.submit-buttons {
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 20px 0;
}
.submit-buttons form {
    margin: 0;
}
</style>

<script>
jQuery(document).ready(function($) {
    $("#sortable-post-types").sortable({
        handle: '.dashicons-menu',
        update: function(event, ui) {
            var order = $(this).sortable('toArray', {attribute: 'data-post-type'});
            $('#post-types-order').val(order.join(','));
        }
    });
});
</script>
