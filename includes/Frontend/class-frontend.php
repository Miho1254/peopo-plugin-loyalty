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

        $metric        = $customers[0]['metric'] ?? 'spending';
        $is_points_ranking = 'points' === $metric;
        $subtitle     = $is_points_ranking
            ? __('Cảm ơn vì đã đồng hành cùng cửa hàng. Dưới đây là bảng xếp hạng %s khách hàng tích lũy nhiều điểm thưởng nhất.', 'woo-rewardx-lite')
            : __('Cảm ơn vì đã đồng hành cùng cửa hàng. Dưới đây là bảng xếp hạng %s khách hàng mua sắm nhiều nhất.', 'woo-rewardx-lite');

        ob_start();
        ?>
        <div class="rewardx-top-customers">
            <div class="rewardx-top-customers__header">
                <h3 class="rewardx-top-customers__title"><?php echo esc_html__('Top khách hàng thân thiết', 'woo-rewardx-lite'); ?></h3>
                <p class="rewardx-top-customers__subtitle"><?php echo esc_html(sprintf(
                    /* translators: %s: limit number */
                    $subtitle,
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
                        <?php if ('points' === ($customer['metric'] ?? 'spending')) : ?>
                            <span class="rewardx-top-customers__value rewardx-top-customers__value--points"><?php echo esc_html(sprintf(
                                /* translators: %s: points amount */
                                __('%s điểm', 'woo-rewardx-lite'),
                                number_format_i18n((int) $customer['total_spent'])
                            )); ?></span>
                        <?php else : ?>
                            <span class="rewardx-top-customers__value"><?php echo wp_kses_post($this->format_currency((float) $customer['total_spent'])); ?></span>
                        <?php endif; ?>
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
     * @return array<int, array{name: string, meta: string, total_spent: float, metric: string}>
     */
    private function get_top_customers(int $limit = 20): array
    {
        global $wpdb;

        $limit      = max(1, min($limit, 50));
        $table_name = $wpdb->prefix . 'wc_customer_lookup';
        $customers  = [];

        $raw_statuses = function_exists('wc_get_is_paid_statuses') ? wc_get_is_paid_statuses() : ['completed', 'processing'];

        /**
         * Filters the order statuses used to build the top customers leaderboard.
         *
         * @param string[] $statuses List of order statuses.
         */
        $raw_statuses = apply_filters('rewardx_top_customers_order_statuses', array_values(array_filter($raw_statuses)));

        $normalized_statuses = array_values(array_filter(array_map(
            static function ($status) {
                $status = trim((string) $status);

                if ('' === $status) {
                    return '';
                }

                return 0 === strpos($status, 'wc-') ? $status : 'wc-' . $status;
            },
            $raw_statuses
        )));

        if ($this->database_table_exists($table_name)) {
            $available_columns = [];

            foreach (['customer_id', 'user_id', 'first_name', 'last_name', 'email', 'city', 'total_spent'] as $column) {
                if ($this->database_table_column_exists($table_name, $column)) {
                    $available_columns[] = $column;
                }
            }

            if (in_array('total_spent', $available_columns, true)) {
                $select_columns = implode(
                    ', ',
                    array_map(
                        static fn(string $column): string => "`{$column}`",
                        $available_columns
                    )
                );

                $results = $wpdb->get_results(
                    $wpdb->prepare(
                        "SELECT {$select_columns} FROM {$table_name} WHERE total_spent > 0 ORDER BY total_spent DESC LIMIT %d",
                        $limit
                    ),
                    ARRAY_A
                );

                if (!empty($results)) {
                    foreach ($results as $row) {
                        $total_spent = (float) ($row['total_spent'] ?? 0);

                        if ($total_spent <= 0) {
                            continue;
                        }

                        $first_name = (string) ($row['first_name'] ?? '');
                        $last_name  = (string) ($row['last_name'] ?? '');
                        $email      = (string) ($row['email'] ?? '');
                        $city       = (string) ($row['city'] ?? '');

                        if ('' === $first_name && '' === $last_name) {
                            $user_id = isset($row['user_id']) ? (int) $row['user_id'] : 0;

                            if ($user_id > 0) {
                                $first_name = (string) get_user_meta($user_id, 'first_name', true);
                                $last_name  = (string) get_user_meta($user_id, 'last_name', true);

                                if ('' === $email) {
                                    $user = get_userdata($user_id);
                                    if ($user instanceof \WP_User) {
                                        $email = (string) $user->user_email;
                                    }
                                }

                                if ('' === $city) {
                                    $city = (string) get_user_meta($user_id, 'billing_city', true);
                                }
                            }
                        }

                        $customers[] = [
                            'name'        => $this->mask_customer_name($first_name, $last_name, $email),
                            'meta'        => $this->format_customer_meta($city),
                            'total_spent' => $total_spent,
                            'metric'      => 'spending',
                        ];
                    }
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
                    'metric'      => 'spending',
                ];

                if (count($customers) >= $limit) {
                    break;
                }
            }
        }

        if (empty($customers) && class_exists('\\WP_User_Query')) {
            $roles = apply_filters('rewardx_top_customers_user_roles', ['customer', 'subscriber']);

            $roles = array_values(array_filter(array_map(
                static function ($role): string {
                    return (string) $role;
                },
                is_array($roles) ? $roles : []
            )));

            $query_args = [
                'fields' => 'ID',
                'number' => (int) apply_filters('rewardx_top_customers_user_query_number', 200),
            ];

            if (!empty($roles)) {
                $query_args['role__in'] = $roles;
            }

            $user_query = new \WP_User_Query($query_args);
            $user_ids   = $user_query->get_results();

            if (!empty($user_ids)) {
                $aggregated = [];

                foreach ($user_ids as $user_id) {
                    $user_id = (int) $user_id;

                    if ($user_id <= 0) {
                        continue;
                    }

                    $total_spent = function_exists('wc_get_customer_total_spent') ? (float) wc_get_customer_total_spent($user_id) : 0.0;

                    if ($total_spent <= 0) {
                        continue;
                    }

                    $first_name = '';
                    $last_name  = '';
                    $email      = '';
                    $city       = '';

                    if (class_exists('\\WC_Customer')) {
                        $customer = new \WC_Customer($user_id);

                        if ($customer instanceof \WC_Customer) {
                            $first_name = (string) $customer->get_first_name();
                            $last_name  = (string) $customer->get_last_name();
                            $email      = (string) $customer->get_email();
                            $city       = (string) $customer->get_billing_city();
                        }
                    }

                    if ('' === $first_name && '' === $last_name) {
                        $first_name = (string) get_user_meta($user_id, 'first_name', true);
                        $last_name  = (string) get_user_meta($user_id, 'last_name', true);
                    }

                    if ('' === $email) {
                        $user  = get_userdata($user_id);
                        $email = $user instanceof \WP_User ? (string) $user->user_email : '';
                    }

                    if ('' === $city) {
                        $city = (string) get_user_meta($user_id, 'billing_city', true);
                    }

                    $key = 'user-' . $user_id;

                    $aggregated[$key] = [
                        'first_name'  => $first_name,
                        'last_name'   => $last_name,
                        'email'       => $email,
                        'city'        => $city,
                        'total_spent' => $total_spent,
                    ];
                }

                if (!empty($aggregated)) {
                    uasort($aggregated, static function (array $a, array $b): int {
                        return $b['total_spent'] <=> $a['total_spent'];
                    });

                    foreach (array_slice($aggregated, 0, $limit) as $data) {
                        $customers[] = [
                            'name'        => $this->mask_customer_name($data['first_name'], $data['last_name'], $data['email']),
                            'meta'        => $this->format_customer_meta($data['city']),
                            'total_spent' => (float) $data['total_spent'],
                            'metric'      => 'spending',
                        ];
                    }
                }
            }
        }

        if (empty($customers)) {
            $order_stats_table = $wpdb->prefix . 'wc_order_stats';

            if (
                $this->database_table_exists($order_stats_table)
                && $this->database_table_column_exists($order_stats_table, 'status')
                && $this->database_table_column_exists($order_stats_table, 'net_total')
                && $this->database_table_column_exists($order_stats_table, 'billing_first_name')
                && $this->database_table_column_exists($order_stats_table, 'billing_last_name')
                && $this->database_table_column_exists($order_stats_table, 'billing_email')
                && $this->database_table_column_exists($order_stats_table, 'billing_city')
                && !empty($normalized_statuses)
            ) {
                $placeholders    = implode(',', array_fill(0, count($normalized_statuses), '%s'));
                $prepared_values = array_merge($normalized_statuses, [$limit]);

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
                        'metric'      => 'spending',
                    ];

                    if (count($customers) >= $limit) {
                        break;
                    }
                }
            }
        }

        if (empty($customers) && class_exists('\\WC_Order_Query') && function_exists('wc_get_order')) {
            $order_statuses = array_values(array_filter(array_map(
                static function ($status) {
                    $status = trim((string) $status);

                    if ('' === $status) {
                        return '';
                    }

                    return 0 === strpos($status, 'wc-') ? substr($status, 3) : $status;
                },
                $normalized_statuses
            )));

            if (!empty($order_statuses)) {
                $order_query = new \WC_Order_Query([
                    'status' => $order_statuses,
                    'limit'  => -1,
                    'return' => 'ids',
                ]);

                $order_ids = $order_query->get_orders();

                if (!empty($order_ids)) {
                    $aggregated = [];

                    foreach ($order_ids as $order_id) {
                        $order = wc_get_order($order_id);

                        if (!$order instanceof \WC_Order) {
                            continue;
                        }

                        $total = (float) $order->get_total();

                        if ($total <= 0) {
                            continue;
                        }

                        $customer_id = (int) $order->get_customer_id();
                        $email       = (string) $order->get_billing_email();
                        $key         = $customer_id > 0 ? 'user-' . $customer_id : 'guest-' . ('' !== $email ? strtolower($email) : $order_id);

                        if (!isset($aggregated[$key])) {
                            $aggregated[$key] = [
                                'first_name'  => (string) $order->get_billing_first_name(),
                                'last_name'   => (string) $order->get_billing_last_name(),
                                'email'       => $email,
                                'city'        => (string) $order->get_billing_city(),
                                'total_spent' => 0.0,
                            ];
                        }

                        $aggregated[$key]['total_spent'] += $total;
                    }

                    if (!empty($aggregated)) {
                        uasort($aggregated, static function (array $a, array $b): int {
                            return $b['total_spent'] <=> $a['total_spent'];
                        });

                        foreach (array_slice($aggregated, 0, $limit) as $data) {
                            $customers[] = [
                                'name'        => $this->mask_customer_name($data['first_name'], $data['last_name'], $data['email']),
                                'meta'        => $this->format_customer_meta($data['city']),
                                'total_spent' => (float) $data['total_spent'],
                                'metric'      => 'spending',
                            ];
                        }
                    }
                }
            }
        }

        if (empty($customers)) {
            $user_query = new \WP_User_Query([
                'meta_key' => Points_Manager::META_KEY,
                'orderby'  => 'meta_value_num',
                'order'    => 'DESC',
                'number'   => $limit * 2,
                'fields'   => 'ID',
            ]);

            $user_ids = $user_query->get_results();

            if (!empty($user_ids)) {
                foreach ($user_ids as $user_id) {
                    $user_id = (int) $user_id;

                    if ($user_id <= 0) {
                        continue;
                    }

                    $points = (int) get_user_meta($user_id, Points_Manager::META_KEY, true);

                    if ($points <= 0) {
                        continue;
                    }

                    $first_name = (string) get_user_meta($user_id, 'first_name', true);
                    $last_name  = (string) get_user_meta($user_id, 'last_name', true);
                    $email      = '';
                    $city       = (string) get_user_meta($user_id, 'billing_city', true);

                    $user = get_userdata($user_id);
                    if ($user instanceof \WP_User) {
                        $email = (string) $user->user_email;
                    }

                    $customers[] = [
                        'name'        => $this->mask_customer_name($first_name, $last_name, $email),
                        'meta'        => $this->format_customer_meta($city),
                        'total_spent' => (float) $points,
                        'metric'      => 'points',
                    ];

                    if (count($customers) >= $limit) {
                        break;
                    }
                }
            }
        }

        return $customers;
    }

    private function database_table_exists(string $table): bool
    {
        global $wpdb;

        $table = $this->normalize_db_identifier($table);

        if ('' === $table) {
            return false;
        }

        $result = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table));

        return $result === $table;
    }

    private function database_table_column_exists(string $table, string $column): bool
    {
        global $wpdb;

        $table  = $this->normalize_db_identifier($table);
        $column = $this->normalize_db_identifier($column);

        if ('' === $table || '' === $column) {
            return false;
        }

        $sql = sprintf('SHOW COLUMNS FROM `%s` LIKE %%s', $table);

        return null !== $wpdb->get_var($wpdb->prepare($sql, $column));
    }

    private function normalize_db_identifier(string $identifier): string
    {
        $identifier = trim($identifier);

        if ('' === $identifier) {
            return '';
        }

        if (!preg_match('/^[A-Za-z0-9_]+$/', $identifier)) {
            return '';
        }

        return $identifier;
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
