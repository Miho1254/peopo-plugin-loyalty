<?php

namespace RewardX\Frontend;

use RewardX\CPT\Reward_CPT;
use RewardX\Points\Points_Manager;
use RewardX\Plugin;

if (!defined('ABSPATH')) {
    exit;
}

class Frontend
{
    private Points_Manager $points_manager;

    public function __construct(Points_Manager $points_manager)
    {
        $this->points_manager = $points_manager;
    }

    public function hooks(): void
    {
        add_action('init', [$this, 'register_endpoint']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_filter('woocommerce_account_menu_items', [$this, 'add_account_menu']);
        add_action('woocommerce_account_rewardx_endpoint', [$this, 'render_account_page']);
    }

    public function register_endpoint(): void
    {
        add_rewrite_endpoint('rewardx', EP_ROOT | EP_PAGES);
    }

    public function enqueue_assets(): void
    {
        if (!is_user_logged_in()) {
            return;
        }

        if (!is_account_page()) {
            return;
        }

        wp_enqueue_style('rewardx-frontend', REWARDX_URL . 'assets/css/rewardx.css', [], REWARDX_VERSION);
        wp_enqueue_script('rewardx-frontend', REWARDX_URL . 'assets/js/rewardx-frontend.js', ['jquery'], REWARDX_VERSION, true);

        wp_localize_script('rewardx-frontend', 'rewardxFrontend', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('rewardx_redeem_nonce'),
            'i18n'    => [
                'processing'     => __('Đang xử lý...', 'woo-rewardx-lite'),
                'confirm'        => __('Bạn có chắc chắn muốn đổi quà?', 'woo-rewardx-lite'),
                'insufficient'   => __('Bạn không đủ điểm.', 'woo-rewardx-lite'),
                'viewOrder'      => __('Xem đơn hàng', 'woo-rewardx-lite'),
                'voucherCode'    => __('Mã voucher của bạn', 'woo-rewardx-lite'),
                'expiry'         => __('Hết hạn', 'woo-rewardx-lite'),
                'redeemVoucher'  => __('Đổi voucher', 'woo-rewardx-lite'),
                'redeemPhysical' => __('Đổi quà', 'woo-rewardx-lite'),
            ],
        ]);
    }

    public function add_account_menu(array $items): array
    {
        $items = array_slice($items, 0, count($items), true);

        $new_items = [];
        foreach ($items as $key => $label) {
            $new_items[$key] = $label;
            if ('dashboard' === $key) {
                $new_items['rewardx'] = __('Đổi thưởng', 'woo-rewardx-lite');
            }
        }

        if (!isset($new_items['rewardx'])) {
            $new_items['rewardx'] = __('Đổi thưởng', 'woo-rewardx-lite');
        }

        return $new_items;
    }

    public function render_account_page(): void
    {
        if (!is_user_logged_in()) {
            echo wp_kses_post('<p>' . __('Vui lòng đăng nhập để xem điểm thưởng.', 'woo-rewardx-lite') . '</p>');

            return;
        }

        $user_id     = get_current_user_id();
        $points      = $this->points_manager->get_points($user_id);
        $ledger      = $this->points_manager->get_recent_transactions($user_id, 10);
        $settings    = Plugin::instance()->get_settings()->get_settings();
        $rewards     = $this->get_rewards();
        $total_spent = function_exists('wc_get_customer_total_spent') ? (float) wc_get_customer_total_spent($user_id) : 0.0;
        $order_count = function_exists('wc_get_customer_order_count') ? (int) wc_get_customer_order_count($user_id) : 0;

        include REWARDX_PATH . 'includes/views/account-rewardx.php';
    }

    private function get_rewards(): array
    {
        $args = [
            'post_type'      => Reward_CPT::POST_TYPE,
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => ['menu_order' => 'ASC', 'title' => 'ASC'],
        ];

        $query = new \WP_Query($args);
        $items = [
            'physical' => [],
            'voucher'  => [],
        ];

        foreach ($query->posts as $post) {
            $type_terms = wp_get_post_terms($post->ID, Reward_CPT::TAXONOMY, ['fields' => 'slugs']);
            $type       = $type_terms[0] ?? 'physical';

            if (!isset($items[$type])) {
                $items[$type] = [];
            }

            $items[$type][] = [
                'id'            => $post->ID,
                'title'         => get_the_title($post),
                'excerpt'       => wp_trim_words(wp_strip_all_tags($post->post_excerpt ?: $post->post_content), 25),
                'thumbnail'     => get_the_post_thumbnail_url($post, 'medium'),
                'cost'          => (int) get_post_meta($post->ID, '_cost_points', true),
                'stock'         => (int) get_post_meta($post->ID, '_stock', true),
                'amount'        => (float) get_post_meta($post->ID, '_coupon_amount', true),
                'expiry_days'   => (int) get_post_meta($post->ID, '_expiry_days', true),
                'sku'           => get_post_meta($post->ID, '_sku', true),
            ];
        }

        return $items;
    }
}
