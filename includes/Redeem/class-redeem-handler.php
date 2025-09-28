<?php

namespace RewardX\Redeem;

use RewardX\CPT\Reward_CPT;
use RewardX\Emails\Emails;
use RewardX\Points\Points_Manager;
use RewardX\Plugin;
use WP_Error;

if (!defined('ABSPATH')) {
    exit;
}

class Redeem_Handler
{
    private Points_Manager $points_manager;

    public function __construct(Points_Manager $points_manager)
    {
        $this->points_manager = $points_manager;
    }

    public function hooks(): void
    {
        add_action('wp_ajax_rewardx_redeem_physical', [$this, 'redeem_physical']);
        add_action('wp_ajax_rewardx_redeem_voucher', [$this, 'redeem_voucher']);
        add_action('wp_ajax_nopriv_rewardx_redeem_physical', [$this, 'redeem_physical']);
        add_action('wp_ajax_nopriv_rewardx_redeem_voucher', [$this, 'redeem_voucher']);
    }

    public function redeem_physical(): void
    {
        $reward_id = isset($_POST['reward_id']) ? (int) $_POST['reward_id'] : 0;
        $validation = $this->validate_request($reward_id, 'physical');

        if (is_wp_error($validation)) {
            wp_send_json_error(['message' => $validation->get_error_message()], 400);
        }

        [$reward, $user_id, $cost] = $validation;

        $stock_check = $this->ensure_stock($reward->ID);
        if (is_wp_error($stock_check)) {
            wp_send_json_error(['message' => $stock_check->get_error_message()], 400);
        }

        $adjust = $this->points_manager->adjust_points($user_id, -$cost, [
            'reason'   => sprintf(__('Đổi quà vật lý: %s', 'woo-rewardx-lite'), $reward->post_title),
            'ref_type' => 'order',
            'ref_id'   => '',
        ]);

        if (is_wp_error($adjust)) {
            wp_send_json_error(['message' => $adjust->get_error_message()], 400);
        }

        $order = $this->create_zero_order($user_id, $reward);

        if (is_wp_error($order)) {
            $this->rollback_points($user_id, $cost, sprintf(__('Hoàn điểm do đổi quà thất bại: %s', 'woo-rewardx-lite'), $reward->post_title));
            wp_send_json_error(['message' => $order->get_error_message()], 500);
        }

        $this->reduce_stock($reward->ID);

        wp_send_json_success([
            'message'   => __('Đổi quà thành công!', 'woo-rewardx-lite'),
            'order_url' => $order->get_view_order_url(),
            'balance'   => $adjust,
        ]);
    }

    public function redeem_voucher(): void
    {
        $reward_id  = isset($_POST['reward_id']) ? (int) $_POST['reward_id'] : 0;
        $validation = $this->validate_request($reward_id, 'voucher');

        if (is_wp_error($validation)) {
            wp_send_json_error(['message' => $validation->get_error_message()], 400);
        }

        [$reward, $user_id, $cost] = $validation;

        $stock_check = $this->ensure_stock($reward->ID);
        if (is_wp_error($stock_check)) {
            wp_send_json_error(['message' => $stock_check->get_error_message()], 400);
        }

        $coupon = $this->create_coupon($reward, $user_id);

        if (is_wp_error($coupon)) {
            wp_send_json_error(['message' => $coupon->get_error_message()], 400);
        }

        $adjust = $this->points_manager->adjust_points($user_id, -$cost, [
            'reason'   => sprintf(__('Đổi voucher: %s', 'woo-rewardx-lite'), $reward->post_title),
            'ref_type' => 'coupon',
            'ref_id'   => $coupon['code'],
        ]);

        if (is_wp_error($adjust)) {
            wp_send_json_error(['message' => $adjust->get_error_message()], 400);
        }

        $this->reduce_stock($reward->ID);

        try {
            Emails::send_voucher($user_id, [
                'code'   => $coupon['code'],
                'amount' => $coupon['amount'],
                'expiry' => $coupon['expiry'],
            ]);
        } catch (\Throwable $e) {
            $this->rollback_points($user_id, $cost, sprintf(__('Hoàn điểm do gửi voucher thất bại: %s', 'woo-rewardx-lite'), $reward->post_title));
            $this->restore_stock($reward->ID);

            wp_send_json_error(['message' => $e->getMessage()], 500);
        }

        wp_send_json_success([
            'message' => __('Đổi voucher thành công!', 'woo-rewardx-lite'),
            'code'    => $coupon['code'],
            'expiry'  => $coupon['expiry'],
            'amount'  => wc_price($coupon['amount']),
            'balance' => $adjust,
        ]);
    }

    private function validate_request(int $reward_id, string $expected_type)
    {
        if (!is_user_logged_in()) {
            return new WP_Error('rewardx_not_logged_in', __('Bạn cần đăng nhập để đổi thưởng.', 'woo-rewardx-lite'));
        }

        check_ajax_referer('rewardx_redeem_nonce', 'nonce');

        if ($reward_id <= 0) {
            return new WP_Error('rewardx_invalid_reward', __('Phần thưởng không tồn tại.', 'woo-rewardx-lite'));
        }

        $reward = get_post($reward_id);

        if (!$reward || Reward_CPT::POST_TYPE !== $reward->post_type || 'publish' !== $reward->post_status) {
            return new WP_Error('rewardx_invalid_reward', __('Phần thưởng không hợp lệ.', 'woo-rewardx-lite'));
        }

        $type_terms = wp_get_post_terms($reward_id, Reward_CPT::TAXONOMY, ['fields' => 'slugs']);
        $type       = $type_terms[0] ?? '';

        if ($expected_type !== $type) {
            return new WP_Error('rewardx_invalid_type', __('Loại phần thưởng không phù hợp.', 'woo-rewardx-lite'));
        }

        $user_id = get_current_user_id();
        $cost    = (int) get_post_meta($reward_id, '_cost_points', true);

        $balance = $this->points_manager->get_points($user_id);

        if ($balance < $cost) {
            return new WP_Error('rewardx_insufficient', __('Bạn không đủ điểm để đổi phần thưởng này.', 'woo-rewardx-lite'));
        }

        return [$reward, $user_id, $cost];
    }

    private function ensure_stock(int $reward_id)
    {
        $stock = get_post_meta($reward_id, '_stock', true);

        if ('' === $stock || (int) $stock === -1) {
            return true;
        }

        $stock = (int) $stock;

        if ($stock <= 0) {
            return new WP_Error('rewardx_out_of_stock', __('Phần thưởng đã hết hàng.', 'woo-rewardx-lite'));
        }

        return true;
    }

    private function reduce_stock(int $reward_id): void
    {
        $stock = get_post_meta($reward_id, '_stock', true);

        if ('' === $stock || (int) $stock === -1) {
            return;
        }

        $attempts = 0;
        do {
            $current = (int) get_post_meta($reward_id, '_stock', true);

            if ($current <= 0) {
                return;
            }

            $updated = update_post_meta($reward_id, '_stock', $current - 1, $current);
            $attempts++;
        } while (!$updated && $attempts < 2);
    }

    private function restore_stock(int $reward_id): void
    {
        $stock = get_post_meta($reward_id, '_stock', true);

        if ('' === $stock || (int) $stock === -1) {
            return;
        }

        $attempts = 0;
        do {
            $current = (int) get_post_meta($reward_id, '_stock', true);
            $updated = update_post_meta($reward_id, '_stock', $current + 1, $current);
            $attempts++;
        } while (!$updated && $attempts < 2);
    }

    private function create_zero_order(int $user_id, \WP_Post $reward)
    {
        if (!function_exists('wc_create_order')) {
            return new WP_Error('rewardx_no_woo', __('WooCommerce chưa được kích hoạt.', 'woo-rewardx-lite'));
        }

        $settings = Plugin::instance()->get_settings()->get_settings();

        try {
            $order = wc_create_order([
                'customer_id' => $user_id,
            ]);

            $item = new \WC_Order_Item_Product();
            $item->set_name(sprintf(__('Reward: %s', 'woo-rewardx-lite'), $reward->post_title));
            $item->add_meta_data('_rewardx_reward_id', $reward->ID, true);
            $item->add_meta_data('_rewardx', 'yes', true);

            $sku = get_post_meta($reward->ID, '_sku', true);
            if ($sku) {
                $item->add_meta_data('_sku', $sku, true);
            }

            $order->add_item($item);
            $order->set_shipping_total(0);
            $order->set_discount_total(0);
            $order->set_total(0);

            $status = $settings['physical_order_status'] ?? 'wc-processing';
            $status = str_replace('wc-', '', $status);

            $order->save();
            $order->update_status($status, __('Đơn hàng đổi quà RewardX.', 'woo-rewardx-lite'));

            return $order;
        } catch (\Exception $e) {
            return new WP_Error('rewardx_order_failed', $e->getMessage());
        }
    }

    private function create_coupon(\WP_Post $reward, int $user_id)
    {
        if (!class_exists('WC_Coupon')) {
            return new WP_Error('rewardx_no_coupon', __('WooCommerce chưa được kích hoạt.', 'woo-rewardx-lite'));
        }

        $user   = get_user_by('id', $user_id);
        $amount = (float) get_post_meta($reward->ID, '_coupon_amount', true);

        if ($amount <= 0) {
            return new WP_Error('rewardx_invalid_amount', __('Giá trị voucher chưa được cấu hình.', 'woo-rewardx-lite'));
        }

        $settings    = Plugin::instance()->get_settings()->get_settings();
        $expiry_days = (int) get_post_meta($reward->ID, '_expiry_days', true);

        if ($expiry_days <= 0) {
            $expiry_days = (int) $settings['default_expiry_days'];
        }

        $code = $this->generate_coupon_code($user_id);

        $coupon = new \WC_Coupon();
        $coupon->set_code($code);
        $coupon->set_amount($amount);
        $coupon->set_discount_type('fixed_cart');
        $coupon->set_usage_limit(1);
        $coupon->set_individual_use(true);
        $coupon->set_email_restrictions([$user->user_email]);

        $expiry = gmdate('Y-m-d', strtotime('+' . $expiry_days . ' days'));
        $coupon->set_date_expires(strtotime($expiry . ' 23:59:59'));
        $coupon->save();

        return [
            'code'   => $code,
            'amount' => $amount,
            'expiry' => date_i18n(get_option('date_format'), strtotime($expiry)),
        ];
    }

    private function generate_coupon_code(int $user_id): string
    {
        do {
            $code = 'RWX-' . $user_id . '-' . strtoupper(wp_generate_password(6, false, false));
        } while (wc_get_coupon_id_by_code($code));

        return $code;
    }

    private function rollback_points(int $user_id, int $amount, string $reason): void
    {
        $rollback = $this->points_manager->adjust_points($user_id, $amount, [
            'reason'   => $reason,
            'ref_type' => 'system',
            'ref_id'   => '',
        ]);

        if (is_wp_error($rollback)) {
            error_log(sprintf('[RewardX] Failed to rollback points for user %d: %s', $user_id, $rollback->get_error_message()));
        }
    }
}
