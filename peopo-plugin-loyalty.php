<?php
/*
Plugin Name:       Peopo Loyalty
Description:       Mẫu plugin WordPress tiêu chuẩn với các ví dụ hook cơ bản cho cả backend lẫn frontend.
Version:           1.0.0
Author:            Miho
Text Domain:       peopo-loyalty
Domain Path:       /languages
Requires at least: 6.0
Requires PHP:      7.4
*/

// Chặn truy cập trực tiếp để tránh người dùng gọi file plugin từ trình duyệt.
if (!defined('ABSPATH')) {
    exit;
}

// ===== Cấu hình cơ bản của plugin =====
define('PEOPO_LOYALTY_VERSION', '1.0.0');
define('PEOPO_LOYALTY_PATH', plugin_dir_path(__FILE__));
define('PEOPO_LOYALTY_URL', plugin_dir_url(__FILE__));
define('PEOPO_LOYALTY_BASENAME', plugin_basename(__FILE__));

// ===== Nạp class chính của plugin =====
require_once PEOPO_LOYALTY_PATH . 'includes/class-peopo-plugin-loyalty.php';

// ===== Các hook vòng đời (activate/deactivate) =====
register_activation_hook(__FILE__, [\MihoMemberShip\Plugin::class, 'activate']);
register_deactivation_hook(__FILE__, [\MihoMemberShip\Plugin::class, 'deactivate']);

// ===== Khởi động plugin =====
/**
 * WordPress sẽ gọi hàm này sau khi plugin được nạp (plugins_loaded).
 * Trong hàm này ta khởi tạo singleton chính và chạy toàn bộ logic.
 */
function peopo_loyalty_run() {
    \MihoMemberShip\Plugin::instance()->run();
}

add_action('plugins_loaded', 'peopo_loyalty_run');
