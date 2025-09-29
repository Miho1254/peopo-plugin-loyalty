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
    private bool $hooks_registered = false;

    public function __construct(Points_Manager $points_manager)
    {
        $this->points_manager = $points_manager;
    }

    public function hooks(): void
    {
        if ($this->hooks_registered) {
            return;
        }

        $this->hooks_registered = true;

        add_action('init', [$this, 'register_endpoint']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('wp_enqueue_scripts', [$this, 'register_shortcode_assets']);
        add_action('init', [$this, 'register_shortcodes']);
        add_filter('woocommerce_account_menu_items', [$this, 'add_account_menu']);
        add_action('woocommerce_account_rewardx_endpoint', [$this, 'render_account_page']);
    }

    public function register_shortcodes(): void
    {
        add_shortcode('rewardx_top_customers', [$this, 'render_top_customers_shortcode']);

        if (defined('REWARDX_DEBUG_SHORTCODES') && REWARDX_DEBUG_SHORTCODES) {
            global $shortcode_tags;

            if (isset($shortcode_tags['rewardx_top_customers'])) {
                error_log('[RewardX] Shortcode rewardx_top_customers is registered');
            } else {
                error_log('[RewardX] Shortcode rewardx_top_customers is NOT registered');
            }
        }
    }

    public function register_endpoint(): void
    {
        add_rewrite_endpoint('rewardx', EP_ROOT | EP_PAGES);
    }

    public function register_shortcode_assets(): void
    {
        wp_register_style(
            'rewardx-top-customers',
            REWARDX_URL . 'assets/css/rewardx-top-customers.css',
            [],
            REWARDX_VERSION
        );
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
                'sessionExpired' => __('Phiên đăng nhập đã hết hạn. Vui lòng đăng nhập lại.', 'woo-rewardx-lite'),
                'unknownError'   => __('Đã xảy ra lỗi không xác định. Vui lòng thử lại.', 'woo-rewardx-lite'),
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

    public function render_top_customers_shortcode(array $atts = []): string
    {
        $atts = shortcode_atts([
            'limit' => 20,
        ], $atts, 'rewardx_top_customers');

        $limit = (int) $atts['limit'];
        if ($limit <= 0) {
            $limit = 20;
        }

        $customers = $this->get_top_customers($limit);

        wp_enqueue_style('rewardx-top-customers');

        if (empty($customers)) {
            ob_start();
            ?>
            <div class="rewardx-top-customers rewardx-top-customers--empty">
                <p class="rewardx-top-customers__empty"><?php echo esc_html__('Hiện chưa có dữ liệu xếp hạng khách hàng.', 'woo-rewardx-lite'); ?></p>
            </div>
            <?php

            return (string) ob_get_clean();
        }

        ob_start();
        ?>
        <div class="rewardx-top-customers">
            <div class="rewardx-top-customers__header">
                <h3 class="rewardx-top-customers__title"><?php echo esc_html__('Top khách hàng thân thiết', 'woo-rewardx-lite'); ?></h3>
                <p class="rewardx-top-customers__subtitle"><?php echo esc_html(sprintf(
                    /* translators: %s: limit number */
                    esc_html__('Cảm ơn vì đã đồng hành cùng cửa hàng. Dưới đây là bảng xếp hạng %s khách hàng mua sắm nhiều nhất.', 'woo-rewardx-lite'),
                    number_format_i18n($limit)
                )); ?></p>
            </div>
            <ol class="rewardx-top-customers__list">
                <?php foreach ($customers as $index => $customer) :
                    $position = $index + 1;
                    $class    = '';

                    if (1 === $position) {
                        $class = ' rewardx-top-customers__item--gold';
                    } elseif (2 === $position) {
                        $class = ' rewardx-top-customers__item--silver';
                    } elseif (3 === $position) {
                        $class = ' rewardx-top-customers__item--bronze';
                    }
                    ?>
                    <li class="rewardx-top-customers__item<?php echo esc_attr($class); ?>">
                        <span class="rewardx-top-customers__rank"><?php echo esc_html($position); ?></span>
                        <div class="rewardx-top-customers__info">
                            <p class="rewardx-top-customers__name"><?php echo esc_html($customer['name']); ?></p>
                            <?php if (!empty($customer['meta'])) : ?>
                                <span class="rewardx-top-customers__meta"><?php echo esc_html($customer['meta']); ?></span>
                            <?php endif; ?>
                        </div>
                        <span class="rewardx-top-customers__value"><?php echo wp_kses_post($this->format_currency($customer['total_spent'])); ?></span>
                    </li>
                <?php endforeach; ?>
            </ol>
        </div>
        <?php

        return (string) ob_get_clean();
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

    /**
     * @return array<int, array{name: string, meta: string, total_spent: float}>
     */
    private function get_top_customers(int $limit = 20): array
    {
        global $wpdb;

        $limit      = max(1, min($limit, 50));
        $table_name = $wpdb->prefix . 'wc_customer_lookup';
        $customers  = [];

        $table_exists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table_name));

        if ($table_exists === $table_name) {
            $results = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT customer_id, user_id, first_name, last_name, email, city, total_spent FROM {$table_name} WHERE total_spent > 0 ORDER BY total_spent DESC LIMIT %d",
                    $limit
                ),
                ARRAY_A
            );

            if (!empty($results)) {
                foreach ($results as $row) {
                    $customers[] = [
                        'name'        => $this->mask_customer_name((string) ($row['first_name'] ?? ''), (string) ($row['last_name'] ?? ''), (string) ($row['email'] ?? '')),
                        'meta'        => $this->format_customer_meta((string) ($row['city'] ?? '')),
                        'total_spent' => (float) ($row['total_spent'] ?? 0),
                    ];
                }
            }
        }

        if (empty($customers) && class_exists('\\WC_Customer_Query')) {
            $query = new \WC_Customer_Query([
                'number'  => $limit,
                'orderby' => 'total_spent',
                'order'   => 'DESC',
                'return'  => 'objects',
            ]);

            foreach ($query->get_results() as $customer) {
                if (!$customer instanceof \WC_Customer) {
                    continue;
                }

                $total_spent = (float) $customer->get_total_spent();
                if ($total_spent <= 0) {
                    continue;
                }

                $customers[] = [
                    'name'        => $this->mask_customer_name((string) $customer->get_first_name(), (string) $customer->get_last_name(), (string) $customer->get_email()),
                    'meta'        => $this->format_customer_meta((string) $customer->get_billing_city()),
                    'total_spent' => $total_spent,
                ];

                if (count($customers) >= $limit) {
                    break;
                }
            }
        }

        if (empty($customers)) {
            $order_stats_table = $wpdb->prefix . 'wc_order_stats';
            $table_exists      = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $order_stats_table));

            if ($table_exists === $order_stats_table) {
                $statuses = function_exists('wc_get_is_paid_statuses') ? wc_get_is_paid_statuses() : ['completed', 'processing'];
                /**
                 * Filters the order statuses used to build the top customers leaderboard.
                 *
                 * @param string[] $statuses List of order statuses.
                 */
                $statuses = apply_filters('rewardx_top_customers_order_statuses', array_values(array_filter($statuses)));

                if (!empty($statuses)) {
                    $placeholders    = implode(',', array_fill(0, count($statuses), '%s'));
                    $prepared_values = array_merge($statuses, [$limit]);

                    $results = $wpdb->get_results(
                        $wpdb->prepare(
                            "SELECT billing_first_name, billing_last_name, billing_email, billing_city, SUM(net_total) AS total_spent " .
                            "FROM {$order_stats_table} " .
                            "WHERE status IN ({$placeholders}) AND net_total > 0 " .
                            "GROUP BY billing_first_name, billing_last_name, billing_email, billing_city " .
                            "ORDER BY total_spent DESC LIMIT %d",
                            ...$prepared_values
                        ),
                        ARRAY_A
                    );

                    foreach ($results as $row) {
                        $total_spent = (float) ($row['total_spent'] ?? 0);

                        if ($total_spent <= 0) {
                            continue;
                        }

                        $customers[] = [
                            'name'        => $this->mask_customer_name((string) ($row['billing_first_name'] ?? ''), (string) ($row['billing_last_name'] ?? ''), (string) ($row['billing_email'] ?? '')),
                            'meta'        => $this->format_customer_meta((string) ($row['billing_city'] ?? '')),
                            'total_spent' => $total_spent,
                        ];

                        if (count($customers) >= $limit) {
                            break;
                        }
                    }
                }
            }
        }

        return $customers;
    }

    private function mask_customer_name(string $first_name, string $last_name, string $email = ''): string
    {
        $full_name = trim($first_name . ' ' . $last_name);

        if ('' === $full_name && '' === $email) {
            return esc_html__('Khách hàng ẩn danh', 'woo-rewardx-lite');
        }

        if ('' !== $full_name) {
            $parts   = preg_split('/\s+/u', $full_name) ?: [];
            $masked  = [];

            foreach ($parts as $part) {
                $length = mb_strlen($part);
                if ($length <= 1) {
                    $masked[] = $part;

                    continue;
                }

                $first_character = mb_substr($part, 0, 1);
                $masked[]        = $first_character . str_repeat('*', $length - 1);
            }

            return implode(' ', $masked);
        }

        return $this->mask_email($email);
    }

    private function mask_email(string $email): string
    {
        [$local, $domain] = array_pad(explode('@', $email, 2), 2, '');

        if ('' === $local || '' === $domain) {
            return str_repeat('*', max(3, mb_strlen($email)));
        }

        $local_length = mb_strlen($local);
        $start        = mb_substr($local, 0, 1);
        $end          = $local_length > 1 ? mb_substr($local, -1, 1) : '';
        $mask_length  = max(1, $local_length - ('' === $end ? 1 : 2));

        return $start . str_repeat('*', $mask_length) . $end . '@' . $domain;
    }

    private function format_customer_meta(string $city): string
    {
        return '' !== $city ? sprintf(
            /* translators: %s: city name */
            esc_html__('Khu vực: %s', 'woo-rewardx-lite'),
            $city
        ) : '';
    }

    private function format_currency(float $amount): string
    {
        if (function_exists('wc_price')) {
            return wc_price($amount);
        }

        return number_format_i18n($amount, 0);
    }
}
