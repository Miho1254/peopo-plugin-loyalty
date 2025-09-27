=== Woo RewardX Lite ===
Contributors: peopo
Tags: loyalty, woocommerce, rewards, coupons
Requires at least: 6.0
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Hệ thống đổi thưởng cho WooCommerce, sử dụng user meta và custom post type – không cần bảng dữ liệu riêng.

== Mô tả ==

* Lưu điểm của khách hàng bằng user_meta `rewardx_points`.
* Lịch sử điểm (ledger) được ghi bằng Custom Post Type `rewardx_txn`.
* Danh mục quà/voucher quản lý bởi CPT `rewardx_reward` và taxonomy `rewardx_type`.
* Người dùng đổi quà trực tiếp từ trang "Tài khoản" của WooCommerce.
* Đổi voucher sẽ tự tạo coupon WooCommerce (fixed cart, 1 lần sử dụng) và gửi email thông báo.
* Đổi quà vật lý sẽ tạo đơn hàng 0đ với trạng thái cấu hình được.

== Cài đặt ==

1. Tải plugin và kích hoạt trong WordPress.
2. Vào WooCommerce → RewardX để cấu hình: thời gian hết hạn voucher, trạng thái đơn quà vật lý, template email, quyền.
3. Tạo các phần thưởng trong menu RewardX Rewards.
4. Quản trị viên có thể xem/sửa điểm người dùng tại trang hồ sơ người dùng.

== Hooks & Filters ==

* `rewardx_settings` – Option chứa toàn bộ cấu hình plugin.
* `rewardx_points` – User meta lưu số điểm hiện tại.
* `rewardx_txn` – CPT lưu lịch sử cộng/trừ điểm.
* `rewardx_reward` – CPT quản lý phần thưởng.

== Changelog ==

= 1.0.0 =
* Phát hành bản đầu tiên.
