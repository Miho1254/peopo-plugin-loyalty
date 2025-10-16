<?php

namespace RewardX\Admin;

use RewardX\Ranks\Rank_Manager;

if (!defined('ABSPATH')) {
    exit;
}

class Ranks_Admin
{
    private Rank_Manager $rank_manager;

    public function __construct(Rank_Manager $rank_manager)
    {
        $this->rank_manager = $rank_manager;
    }

    public function hooks(): void
    {
        add_action('admin_menu', [$this, 'register_menu']);
        add_action('admin_post_rewardx_save_ranks', [$this, 'handle_save']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    public function register_menu(): void
    {
        add_submenu_page(
            'woocommerce',
            __('Thứ hạng khách hàng RewardX', 'woo-rewardx-lite'),
            __('RewardX - Thứ hạng', 'woo-rewardx-lite'),
            'manage_woocommerce',
            'rewardx-ranks',
            [$this, 'render_page']
        );
    }

    public function enqueue_assets(string $hook): void
    {
        if ('woocommerce_page_rewardx-ranks' !== $hook) {
            return;
        }

        wp_enqueue_style('rewardx-admin', REWARDX_URL . 'assets/css/rewardx-admin.css', [], REWARDX_VERSION);
        wp_enqueue_script(
            'rewardx-ranks-admin',
            REWARDX_URL . 'assets/js/rewardx-ranks-admin.js',
            [],
            REWARDX_VERSION,
            true
        );

        wp_localize_script('rewardx-ranks-admin', 'rewardxRanksAdmin', [
            'confirmRemove' => __('Bạn có chắc chắn muốn xóa thứ hạng này?', 'woo-rewardx-lite'),
        ]);
    }

    public function render_page(): void
    {
        if (!current_user_can('manage_woocommerce')) {
            return;
        }

        $ranks    = $this->rank_manager->get_ranks();
        $notice   = isset($_GET['updated']) ? sanitize_text_field(wp_unslash($_GET['updated'])) : '';
        $error    = isset($_GET['error']) ? sanitize_text_field(wp_unslash($_GET['error'])) : '';
        $currency = function_exists('get_woocommerce_currency_symbol') ? get_woocommerce_currency_symbol() : '₫';

        include REWARDX_PATH . 'includes/views/admin-ranks.php';
    }

    public function handle_save(): void
    {
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('Bạn không có quyền thực hiện thao tác này.', 'woo-rewardx-lite'));
        }

        check_admin_referer('rewardx_save_ranks');

        $redirect = add_query_arg(['page' => 'rewardx-ranks'], admin_url('admin.php'));

        if (!isset($_POST['ranks']) || !is_array($_POST['ranks'])) {
            $redirect = add_query_arg('error', 'missing', $redirect);
            wp_safe_redirect($redirect);
            exit;
        }

        $names      = isset($_POST['ranks']['name']) ? (array) $_POST['ranks']['name'] : [];
        $thresholds = isset($_POST['ranks']['threshold']) ? (array) $_POST['ranks']['threshold'] : [];
        $coupons    = isset($_POST['ranks']['coupons']) ? (array) $_POST['ranks']['coupons'] : [];

        $ranks = [];
        foreach ($names as $index => $name) {
            $name = sanitize_text_field(wp_unslash($name));
            $threshold_raw = $thresholds[$index] ?? '';
            $threshold_raw = is_scalar($threshold_raw) ? wp_unslash((string) $threshold_raw) : '';
            $threshold     = is_numeric($threshold_raw) ? (float) $threshold_raw : 0.0;
            $coupon_raw    = $coupons[$index] ?? '';
            $coupon_raw    = is_scalar($coupon_raw) ? wp_unslash((string) $coupon_raw) : '';

            if ('' === $name) {
                continue;
            }

            $ranks[] = [
                'name'      => $name,
                'threshold' => max(0.0, $threshold),
                'coupons'   => $coupon_raw,
            ];
        }

        if (empty($ranks)) {
            $redirect = add_query_arg('error', 'empty', $redirect);
            wp_safe_redirect($redirect);
            exit;
        }

        $this->rank_manager->save_ranks($ranks);

        $redirect = add_query_arg('updated', 'true', $redirect);
        wp_safe_redirect($redirect);
        exit;
    }
}
