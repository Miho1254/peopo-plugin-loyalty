<?php
/*
Plugin Name:       Woo RewardX Lite
Description:       Hệ thống đổi thưởng WooCommerce sử dụng user meta và custom post type, không cần bảng dữ liệu riêng.
Version:           1.0.0
Author:            Peopo
Text Domain:       woo-rewardx-lite
Domain Path:       /languages
Requires at least: 6.0
Requires PHP:      7.4
*/

use RewardX\Plugin;

if (!defined('ABSPATH')) {
    exit;
}

define('REWARDX_VERSION', '1.0.0');
define('REWARDX_FILE', __FILE__);
define('REWARDX_PATH', plugin_dir_path(__FILE__));
define('REWARDX_URL', plugin_dir_url(__FILE__));
define('REWARDX_BASENAME', plugin_basename(__FILE__));

require_once REWARDX_PATH . 'includes/class-rewardx-plugin.php';

register_activation_hook(__FILE__, ['RewardX\\Plugin', 'activate']);
register_deactivation_hook(__FILE__, ['RewardX\\Plugin', 'deactivate']);

if (!function_exists('rewardx_lite_dependencies_met')) {
    function rewardx_lite_dependencies_met(): bool
    {
        return class_exists('WooCommerce', false) || did_action('woocommerce_loaded');
    }
}

if (!function_exists('rewardx_lite_missing_wc_notice')) {
    function rewardx_lite_missing_wc_notice(): void
    {
        if (!current_user_can('activate_plugins')) {
            return;
        }

        echo '<div class="notice notice-error"><p>' . esc_html__('Woo RewardX Lite yêu cầu WooCommerce đang hoạt động. Plugin đã bị vô hiệu hóa.', 'woo-rewardx-lite') . '</p></div>';
    }
}

if (!function_exists('rewardx_lite_handle_missing_dependencies')) {
    function rewardx_lite_handle_missing_dependencies(): void
    {
        if (!is_admin()) {
            return;
        }

        add_action('admin_notices', 'rewardx_lite_missing_wc_notice');
        add_action('network_admin_notices', 'rewardx_lite_missing_wc_notice');

        add_action('admin_init', static function (): void {
            if (!current_user_can('activate_plugins')) {
                return;
            }

            if (!function_exists('deactivate_plugins')) {
                require_once ABSPATH . 'wp-admin/includes/plugin.php';
            }

            deactivate_plugins(REWARDX_BASENAME);
        });
    }
}

add_action('plugins_loaded', static function (): void {
    if (!rewardx_lite_dependencies_met()) {
        rewardx_lite_handle_missing_dependencies();

        return;
    }

    $plugin = Plugin::instance();
    $plugin->boot();
    $plugin->get_frontend()->hooks();
});

if (!function_exists('rewardx_get_nfc_profile_url')) {
    function rewardx_get_nfc_profile_url($user_id): ?string
    {
        if (!is_numeric($user_id)) {
            return null;
        }

        $user_id = (int) $user_id;

        if ($user_id <= 0) {
            return null;
        }

        $plugin = Plugin::instance();
        $frontend = $plugin->get_frontend();

        if (!method_exists($frontend, 'get_nfc_url_for_user')) {
            return null;
        }

        return $frontend->get_nfc_url_for_user($user_id);
    }
}
