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
* Hỗ trợ hệ thống thứ hạng khách hàng dựa trên tổng chi tiêu tích lũy, có thể cấu hình coupon mặc định cho từng hạng.

== Cài đặt ==

1. Tải plugin và kích hoạt trong WordPress.
2. Vào WooCommerce → RewardX để cấu hình: thời gian hết hạn voucher, trạng thái đơn quà vật lý, template email, quyền.
3. Vào WooCommerce → RewardX - Thứ hạng để thiết lập các mốc chi tiêu, tên hạng thành viên và coupon mặc định (nếu có).
4. Tạo các phần thưởng trong menu RewardX Rewards.
5. Quản trị viên có thể xem/sửa điểm người dùng tại trang hồ sơ người dùng.

== Hệ thống thứ hạng ==

* Truy cập WooCommerce → RewardX - Thứ hạng để thêm, sửa hoặc xóa các hạng thành viên.
* Mỗi hạng gồm tên hiển thị, ngưỡng chi tiêu tối thiểu (tổng tiền đã mua hàng) và danh sách coupon mặc định (phân tách bằng dấu phẩy hoặc xuống dòng).
* Khi khách hàng được chuyển đến trang thanh toán, các coupon mặc định của hạng hiện tại sẽ được tự động áp dụng vào giỏ hàng nếu đủ điều kiện.
* Người dùng đạt hạng cao nhất mà họ thỏa điều kiện; trang "Tài khoản" sẽ hiển thị hạng hiện tại, tiến độ lên hạng tiếp theo và bảng các hạng.
* Dữ liệu lưu tại option `rewardx_ranks`, có thể xuất/nhập bằng các công cụ quản lý option nếu cần sao lưu.

== Hooks & Filters ==

* `rewardx_settings` – Option chứa toàn bộ cấu hình plugin.
* `rewardx_points` – User meta lưu số điểm hiện tại.
* `rewardx_txn` – CPT lưu lịch sử cộng/trừ điểm.
* `rewardx_reward` – CPT quản lý phần thưởng.

== Changelog ==

= 1.0.0 =
* Phát hành bản đầu tiên.
