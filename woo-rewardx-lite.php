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

add_action('plugins_loaded', static function (): void {
    RewardX\Plugin::instance()->boot();
});
