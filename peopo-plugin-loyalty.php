<?php
/*
Plugin Name: Peopo Loyalty
Description: .
Version: 1.0.0
Author: Miho
Text Domain: peopo-loyalty
Requires at least: 6.0
Requires PHP: 7.4
*/

if (!defined("ABSPATH")) {
    exit();
} // chặn truy cập trực tiếp

// Autoload đơn giản (hoặc dùng composer nếu thích)
require_once __DIR__ . "/includes/class-peopo-plugin-loyalty.php";

// Hằng số tiện dụng
define("MIHO_AWE_VERSION", "1.0.0");
define("MIHO_AWE_PATH", plugin_dir_path(__FILE__));
define("MIHO_AWE_URL", plugin_dir_url(__FILE__));

// Hooks vòng đời
register_activation_hook(__FILE__, function () {
    // ví dụ: tạo option mặc định
    add_option("peopo-loyalty-enabled", true);
});
register_deactivation_hook(__FILE__, function () {
    // ví dụ: dọn tạm, không xóa dữ liệu người dùng
});

// Khởi động plugin
add_action("plugins_loaded", function () {
    // nạp textdomain cho i18n
    load_plugin_textdomain(
        "peopo-loyalty",
        false,
        dirname(plugin_basename(__FILE__)) . "/languages",
    );

    // boot class chính
    \MihoMemberShip\Plugin::instance();
});
