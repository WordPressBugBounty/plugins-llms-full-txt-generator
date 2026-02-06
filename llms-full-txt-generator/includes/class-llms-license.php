<?php
if (!defined('ABSPATH')) exit;

class LLMS_License {
    private $item_id       = 414077;
    private $store_url     = 'https://api.acowebs.com';
    private $option_key    = 'llms_txt_license_key';
    private $option_status = 'llms_txt_license_status';

    public function __construct() {
        add_action('rest_api_init', [$this, 'register_routes']);
        register_deactivation_hook(LLMS_PLUGIN_DIR . 'llms-txt-generator-pro.php', [$this, 'deactivate_license']);
    }

    public function register_routes() {
        register_rest_route('llms/v1', '/initial_config/', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_initial_config'],
            'permission_callback' => [$this, 'perm_check']
        ]);

        register_rest_route('llms/v1', '/update_licence_key/', [
            'methods'             => 'POST',
            'callback'            => [$this, 'update_licence_key'],
            'permission_callback' => [$this, 'perm_check']
        ]);
    }

    public function perm_check() {
        return current_user_can('manage_options');
    }

    public function get_initial_config() {
        return new WP_REST_Response([
            'licenced' => $this->is_licenced(),
            'key'      => $this->get_masked_key()
        ], 200);
    }

    public function update_licence_key(WP_REST_Request $request) {
        $key = trim(sanitize_text_field($request->get_param('licence_key')));
        if (empty($key)) {
            return new WP_REST_Response([
                'licenced' => false,
                'msg'      => __('License key is required.', 'llms-full-txt-generator')
            ], 200);
        }

        update_option($this->option_key, $key);

        $response = wp_remote_post($this->store_url, [
            'timeout'   => 15,
            'sslverify' => true,
            'body'      => [
                'edd_action' => 'activate_license',
                'license'    => $key,
                'item_id'    => $this->item_id,
                'url'        => home_url()
            ]
        ]);

        $message = '';
        if (is_wp_error($response)) {
            $message = $response->get_error_message();
        } elseif (200 !== wp_remote_retrieve_response_code($response)) {
            $message = __('Connection failed. Try again.', 'llms-full-txt-generator');
        } else {
            $license_data = json_decode(wp_remote_retrieve_body($response));

            if (false === $license_data->success) {
                switch ($license_data->error) {
                    case 'expired':
                        $message = sprintf(__('License expired on %s.', 'llms-full-txt-generator'), date_i18n(get_option('date_format'), strtotime($license_data->expires)));
                        break;
                    case 'revoked':
                        $message = __('License revoked.', 'llms-full-txt-generator');
                        break;
                    case 'missing':
                    case 'invalid':
                    case 'site_inactive':
                        $message = __('License not active on this site.', 'llms-full-txt-generator');
                        break;
                    case 'item_name_mismatch':
                        $message = __('Wrong product.', 'llms-full-txt-generator');
                        break;
                    case 'no_activations_left':
                        $message = __('No activations left.', 'llms-full-txt-generator');
                        break;
                    default:
                        $message = __('Activation failed.', 'llms-full-txt-generator');
                        break;
                }
            } else {
                update_option($this->option_status, $license_data->license);
                $message = __('License activated!', 'llms-full-txt-generator');
            }
        }

        return new WP_REST_Response([
            'licenced' => $this->is_licenced(),
            'msg'      => $message
        ], 200);
    }

    public function is_licenced() {
        return 'valid' === get_option($this->option_status, '');
    }

    private function get_masked_key() {
        $key = get_option($this->option_key, '');
        return strlen($key) > 8 ? substr($key, 0, 4) . '****' . substr($key, -4) : $key;
    }

    public function deactivate_license() {
        $key = get_option($this->option_key);
        if (!$key) return;

        wp_remote_post($this->store_url, [
            'body' => [
                'edd_action' => 'deactivate_license',
                'license'    => $key,
                'item_id'    => $this->item_id,
                'url'        => home_url()
            ]
        ]);

        delete_option($this->option_key);
        delete_option($this->option_status);
    }
}