<?php

namespace RewardX\Admin;

use RewardX\CPT\Reward_CPT;
use RewardX\Points\Points_Manager;
use RewardX\Plugin;

if (!defined('ABSPATH')) {
    exit;
}

class Admin
{
    private Points_Manager $points_manager;

    public function __construct(Points_Manager $points_manager)
    {
        $this->points_manager = $points_manager;
    }

    public function hooks(): void
    {
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('add_meta_boxes', [$this, 'register_meta_boxes']);
        add_action('save_post_' . Reward_CPT::POST_TYPE, [$this, 'save_reward_meta'], 10, 2);
        add_filter('manage_' . Reward_CPT::POST_TYPE . '_posts_columns', [$this, 'manage_columns']);
        add_action('manage_' . Reward_CPT::POST_TYPE . '_posts_custom_column', [$this, 'render_columns'], 10, 2);
        add_action('admin_menu', [$this, 'register_admin_pages']);

        add_action('show_user_profile', [$this, 'render_user_profile']);
        add_action('edit_user_profile', [$this, 'render_user_profile']);
        add_action('personal_options_update', [$this, 'save_user_points']);
        add_action('edit_user_profile_update', [$this, 'save_user_points']);

        add_action('wp_ajax_rewardx_adjust_points', [$this, 'handle_adjust_points']);
    }

    public function enqueue_assets(string $hook): void
    {
        if (!in_array($hook, ['user-edit.php', 'profile.php', 'post.php', 'post-new.php', 'woocommerce_page_rewardx-nfc-urls'], true)) {
            return;
        }

        wp_enqueue_style('rewardx-admin', REWARDX_URL . 'assets/css/rewardx-admin.css', [], REWARDX_VERSION);
        wp_enqueue_script('rewardx-admin', REWARDX_URL . 'assets/js/rewardx-admin.js', ['jquery'], REWARDX_VERSION, true);

        wp_localize_script('rewardx-admin', 'rewardxAdmin', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('rewardx_admin_nonce'),
            'i18n'    => [
                'confirm' => __('Bạn có chắc chắn muốn cập nhật điểm?', 'woo-rewardx-lite'),
                'missing' => __('Vui lòng nhập số điểm và lý do.', 'woo-rewardx-lite'),
            ],
        ]);
    }

    public function register_meta_boxes(): void
    {
        add_meta_box(
            'rewardx_reward_details',
            __('Chi tiết phần thưởng', 'woo-rewardx-lite'),
            [$this, 'render_reward_meta_box'],
            Reward_CPT::POST_TYPE,
            'normal',
            'high'
        );
    }

    public function render_reward_meta_box(\WP_Post $post): void
    {
        wp_nonce_field('rewardx_reward_meta', 'rewardx_reward_meta_nonce');

        $cost      = (int) get_post_meta($post->ID, '_cost_points', true);
        $amount    = (float) get_post_meta($post->ID, '_coupon_amount', true);
        $expiry    = (int) get_post_meta($post->ID, '_expiry_days', true);
        $stock     = get_post_meta($post->ID, '_stock', true);
        $sku       = get_post_meta($post->ID, '_sku', true);

        if ('' === $stock) {
            $stock = -1;
        }

        $type_terms = wp_get_post_terms($post->ID, Reward_CPT::TAXONOMY, ['fields' => 'slugs']);
        $type       = $type_terms[0] ?? 'physical';

        if (!$expiry) {
            $settings = Plugin::instance()->get_settings()->get_settings();
            $expiry   = (int) ($settings['default_expiry_days'] ?? 30);
        }

        $fields = [
            'cost'   => $cost,
            'amount' => $amount,
            'expiry' => $expiry,
            'stock'  => (int) $stock,
            'sku'    => $sku,
            'type'   => $type,
        ];

        include REWARDX_PATH . 'includes/views/meta-box-reward.php';
    }

    public function save_reward_meta(int $post_id, \WP_Post $post): void
    {
        if (!isset($_POST['rewardx_reward_meta_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['rewardx_reward_meta_nonce'])), 'rewardx_reward_meta')) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        $cost   = isset($_POST['rewardx_cost_points']) ? (int) wp_unslash($_POST['rewardx_cost_points']) : 0;
        $amount = isset($_POST['rewardx_coupon_amount']) ? (float) wp_unslash($_POST['rewardx_coupon_amount']) : 0.0;
        $expiry = isset($_POST['rewardx_expiry_days']) ? (int) wp_unslash($_POST['rewardx_expiry_days']) : 30;
        $stock  = isset($_POST['rewardx_stock']) ? (int) wp_unslash($_POST['rewardx_stock']) : -1;
        $sku    = isset($_POST['rewardx_sku']) ? sanitize_text_field(wp_unslash($_POST['rewardx_sku'])) : '';

        update_post_meta($post_id, '_cost_points', max(0, $cost));
        update_post_meta($post_id, '_coupon_amount', $amount);
        update_post_meta($post_id, '_expiry_days', $expiry > 0 ? $expiry : 30);
        update_post_meta($post_id, '_stock', $stock);
        update_post_meta($post_id, '_sku', $sku);

        $taxonomy = Reward_CPT::TAXONOMY;

        if (isset($_POST['tax_input'][$taxonomy]) && is_array($_POST['tax_input'][$taxonomy])) {
            $raw_terms    = wp_unslash($_POST['tax_input'][$taxonomy]);
            $terms_to_set = [];

            foreach ($raw_terms as $term_value) {
                $term_value = sanitize_text_field($term_value);

                if (is_numeric($term_value)) {
                    $term = get_term((int) $term_value, $taxonomy);
                    if ($term && !is_wp_error($term)) {
                        $terms_to_set[] = $term->slug;
                    }
                } elseif ('' !== $term_value) {
                    $terms_to_set[] = $term_value;
                }
            }

            if (!empty($terms_to_set)) {
                wp_set_post_terms($post_id, $terms_to_set, $taxonomy, false);
            }
        }

        if (empty(wp_get_post_terms($post_id, $taxonomy, ['fields' => 'ids']))) {
            wp_set_post_terms($post_id, ['physical'], $taxonomy, false);
        }
    }

    public function manage_columns(array $columns): array
    {
        $columns['rewardx_type']  = __('Loại', 'woo-rewardx-lite');
        $columns['rewardx_cost']  = __('Điểm cần', 'woo-rewardx-lite');
        $columns['rewardx_stock'] = __('Tồn kho', 'woo-rewardx-lite');

        return $columns;
    }

    public function render_columns(string $column, int $post_id): void
    {
        switch ($column) {
            case 'rewardx_type':
                $terms = wp_get_post_terms($post_id, Reward_CPT::TAXONOMY, ['fields' => 'names']);
                echo esc_html($terms[0] ?? '—');
                break;
            case 'rewardx_cost':
                echo esc_html((int) get_post_meta($post_id, '_cost_points', true));
                break;
            case 'rewardx_stock':
                $stock = get_post_meta($post_id, '_stock', true);
                echo esc_html('-1' === $stock || (int) $stock === -1 ? __('Không giới hạn', 'woo-rewardx-lite') : (int) $stock);
                break;
        }
    }

    public function render_user_profile(\WP_User $user): void
    {
        if (!$this->current_user_can_manage()) {
            return;
        }

        $points   = $this->points_manager->get_points($user->ID, false);
        $ledger   = $this->points_manager->get_recent_transactions($user->ID, 25);
        $settings = Plugin::instance()->get_settings()->get_settings();

        include REWARDX_PATH . 'includes/views/user-profile-points.php';
    }

    public function save_user_points(int $user_id): void
    {
        if (!$this->current_user_can_manage()) {
            return;
        }

        if (!isset($_POST['rewardx_points_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['rewardx_points_nonce'])), 'rewardx_points_update')) {
            return;
        }

        $points = isset($_POST['rewardx_points']) ? (int) $_POST['rewardx_points'] : 0;
        $this->points_manager->set_points($user_id, $points);
    }

    public function handle_adjust_points(): void
    {
        if (!$this->current_user_can_manage()) {
            wp_send_json_error(['message' => __('Bạn không có quyền thực hiện.', 'woo-rewardx-lite')], 403);
        }

        check_ajax_referer('rewardx_admin_nonce', 'nonce');

        $user_id = isset($_POST['user_id']) ? (int) $_POST['user_id'] : 0;
        $delta   = isset($_POST['delta']) ? (int) $_POST['delta'] : 0;
        $reason  = isset($_POST['reason']) ? sanitize_text_field(wp_unslash($_POST['reason'])) : '';

        if ($user_id <= 0 || 0 === $delta || '' === $reason) {
            wp_send_json_error(['message' => __('Dữ liệu không hợp lệ.', 'woo-rewardx-lite')]);
        }

        $result = $this->points_manager->adjust_points($user_id, $delta, [
            'reason'   => $reason,
            'ref_type' => 'admin',
            'ref_id'   => get_current_user_id(),
        ]);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }

        wp_send_json_success([
            'balance' => $result,
            'message' => __('Cập nhật điểm thành công.', 'woo-rewardx-lite'),
        ]);
    }

    public function register_admin_pages(): void
    {
        add_submenu_page(
            'woocommerce',
            __('URL NFC khách hàng', 'woo-rewardx-lite'),
            __('URL NFC khách hàng', 'woo-rewardx-lite'),
            'read',
            'rewardx-nfc-urls',
            [$this, 'render_nfc_page']
        );

        if (!$this->current_user_can_manage()) {
            remove_submenu_page('woocommerce', 'rewardx-nfc-urls');
        }
    }

    public function render_nfc_page(): void
    {
        if (!$this->current_user_can_manage()) {
            wp_die(esc_html__('Bạn không có quyền truy cập trang này.', 'woo-rewardx-lite'));
        }

        $per_page = 20;
        $paged    = isset($_GET['paged']) ? max(1, (int) $_GET['paged']) : 1;
        $search   = isset($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : '';

        $args = [
            'number'      => $per_page,
            'offset'      => ($paged - 1) * $per_page,
            'orderby'     => 'registered',
            'order'       => 'DESC',
            'count_total' => true,
            'fields'      => ['ID', 'display_name', 'user_email'],
        ];

        if ('' !== $search) {
            $args['search']         = '*' . $search . '*';
            $args['search_columns'] = ['user_login', 'user_email', 'display_name'];
        }

        $query       = new \WP_User_Query($args);
        $users       = $query->get_results();
        $total       = (int) $query->get_total();
        $total_pages = (int) ceil($total / $per_page);
        $pagination  = '';

        if ($total_pages > 1) {
            $add_args = ['page' => 'rewardx-nfc-urls'];

            if ('' !== $search) {
                $add_args['s'] = $search;
            }

            $pagination = paginate_links([
                'base'      => add_query_arg('paged', '%#%'),
                'format'    => '',
                'current'   => $paged,
                'total'     => $total_pages,
                'add_args'  => $add_args,
                'prev_text' => __('« Trước', 'woo-rewardx-lite'),
                'next_text' => __('Sau »', 'woo-rewardx-lite'),
                'type'      => 'list',
            ]);
        }

        include REWARDX_PATH . 'includes/views/admin-nfc-urls.php';
    }

    private function current_user_can_manage(): bool
    {
        if (current_user_can('manage_woocommerce')) {
            return true;
        }

        $user = wp_get_current_user();
        if (!$user || empty($user->roles)) {
            return false;
        }

        $settings = Plugin::instance()->get_settings()->get_settings();
        $allowed  = $settings['permissions_roles'] ?? [];

        foreach ($user->roles as $role) {
            if (in_array($role, $allowed, true)) {
                return true;
            }
        }

        return false;
    }
}
