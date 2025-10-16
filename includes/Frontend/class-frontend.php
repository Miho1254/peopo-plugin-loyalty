<?php

namespace RewardX\Frontend;

use RewardX\CPT\Reward_CPT;
use RewardX\Points\Points_Manager;
use RewardX\Plugin;
use RewardX\Ranks\Rank_Manager;

if (!defined('ABSPATH')) {
    exit;
}

class Frontend
{
    private Points_Manager $points_manager;

    private Rank_Manager $rank_manager;
    private bool $hooks_registered = false;

    public function __construct(Points_Manager $points_manager, Rank_Manager $rank_manager)
    {
        $this->points_manager = $points_manager;
        $this->rank_manager   = $rank_manager;
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
        add_action('template_redirect', [$this, 'maybe_apply_rank_coupons']);
        add_filter('woocommerce_coupon_is_valid', [$this, 'validate_rank_coupon_for_user'], 10, 2);
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
        $rank_list   = $this->rank_manager->get_ranks();
        $current_rank = $this->rank_manager->get_rank_for_amount($total_spent);
        $next_rank    = $this->rank_manager->get_next_rank($total_spent);

        $current_threshold = $current_rank['threshold'] ?? 0.0;
        $next_threshold    = $next_rank['threshold'] ?? null;

        if (null !== $next_threshold && $next_threshold > $current_threshold) {
            $rank_progress = max(0.0, min(1.0, ($total_spent - $current_threshold) / ($next_threshold - $current_threshold)));
        } elseif (null === $next_threshold) {
            $rank_progress = 1.0;
        } else {
            $rank_progress = 0.0;
        }

        $amount_to_next = null !== $next_threshold ? max(0.0, $next_threshold - $total_spent) : 0.0;

        include REWARDX_PATH . 'includes/views/account-rewardx.php';
    }

    public function validate_rank_coupon_for_user(bool $is_valid, $coupon): bool
    {
        if (!$is_valid) {
            return false;
        }

        if (is_admin() && !(defined('DOING_AJAX') && DOING_AJAX)) {
            return $is_valid;
        }

        if (!($coupon instanceof \WC_Coupon)) {
            return $is_valid;
        }

        $code = $coupon->get_code();

        if ('' === $code) {
            return $is_valid;
        }

        $rank_for_coupon = $this->rank_manager->find_rank_by_coupon($code);

        if (null === $rank_for_coupon) {
            return $is_valid;
        }

        $error_message = __('Mã giảm giá này chỉ áp dụng cho thành viên đủ hạng.', 'woo-rewardx-lite');

        if (!is_user_logged_in()) {
            if (function_exists('wc_add_notice') && (!function_exists('wc_has_notice') || !wc_has_notice($error_message, 'error'))) {
                wc_add_notice($error_message, 'error');
            }

            return false;
        }

        $user_id = get_current_user_id();

        if (!$user_id) {
            if (function_exists('wc_add_notice') && (!function_exists('wc_has_notice') || !wc_has_notice($error_message, 'error'))) {
                wc_add_notice($error_message, 'error');
            }

            return false;
        }

        $total_spent  = function_exists('wc_get_customer_total_spent') ? (float) wc_get_customer_total_spent($user_id) : 0.0;
        $current_rank = $this->rank_manager->get_rank_for_amount($total_spent);

        if ($this->rank_manager->rank_includes_coupon($current_rank, $code)) {
            return true;
        }

        if (function_exists('wc_add_notice') && (!function_exists('wc_has_notice') || !wc_has_notice($error_message, 'error'))) {
            wc_add_notice($error_message, 'error');
        }

        return false;
    }

    public function maybe_apply_rank_coupons(): void
    {
        if (is_admin()) {
            return;
        }

        if (function_exists('wp_doing_ajax') && wp_doing_ajax()) {
            return;
        }

        if (!is_user_logged_in()) {
            return;
        }

        if (!function_exists('is_checkout') || !is_checkout()) {
            return;
        }

        if (!function_exists('WC')) {
            return;
        }

        $woocommerce = WC();
        $cart        = isset($woocommerce->cart) ? $woocommerce->cart : null;

        if (!$cart) {
            return;
        }

        $user_id     = get_current_user_id();
        $total_spent = function_exists('wc_get_customer_total_spent') ? (float) wc_get_customer_total_spent($user_id) : 0.0;
        $rank        = $this->rank_manager->get_rank_for_amount($total_spent);
        $coupons     = $this->extract_rank_coupons($rank);

        if (empty($coupons)) {
            return;
        }

        $session = isset($woocommerce->session) ? $woocommerce->session : null;

        if (!$session) {
            return;
        }

        $rank_signature = $this->generate_rank_coupon_signature($rank, $coupons);
        $stored_signature = $session->get('rewardx_rank_coupon_signature');

        if ($stored_signature !== $rank_signature) {
            $session->set('rewardx_rank_coupon_signature', $rank_signature);
            $session->set('rewardx_rank_removed_coupons', []);
        }

        $removed = $session->get('rewardx_rank_removed_coupons');
        $removed = is_array($removed) ? array_values(array_unique(array_map('strval', $removed))) : [];

        $remove_request = '';
        if (isset($_GET['remove_coupon'])) {
            $remove_request = wp_unslash((string) $_GET['remove_coupon']);
            if (function_exists('wc_format_coupon_code')) {
                $remove_request = wc_format_coupon_code($remove_request);
            } else {
                $remove_request = sanitize_text_field($remove_request);
            }
        }

        if ('' !== $remove_request && in_array($remove_request, $coupons, true)) {
            $removed[] = $remove_request;
            $removed = array_values(array_unique($removed));
            $session->set('rewardx_rank_removed_coupons', $removed);

            return;
        }

        $coupons = array_values(array_diff($coupons, $removed));

        if (empty($coupons)) {
            return;
        }

        foreach ($coupons as $coupon_code) {
            if ($cart->has_discount($coupon_code)) {
                continue;
            }

            try {
                $coupon = new \WC_Coupon($coupon_code);
            } catch (\Exception $e) {
                continue;
            }

            if (!$coupon->get_id()) {
                continue;
            }

            try {
                $cart->apply_coupon($coupon_code);
            } catch (\Exception $e) {
                continue;
            }
        }
    }

    private function extract_rank_coupons(?array $rank): array
    {
        if (null === $rank) {
            return [];
        }

        $coupons = $rank['coupons'] ?? [];

        if (is_string($coupons)) {
            $coupons = preg_split('/[\r\n,]+/', $coupons) ?: [];
        }

        if (!is_array($coupons)) {
            return [];
        }

        $sanitized = [];

        foreach ($coupons as $coupon) {
            if (!is_scalar($coupon)) {
                continue;
            }

            $coupon = trim((string) $coupon);

            if ('' === $coupon) {
                continue;
            }

            if (function_exists('wc_format_coupon_code')) {
                $coupon = wc_format_coupon_code($coupon);
            }

            if ('' === $coupon) {
                continue;
            }

            $sanitized[$coupon] = $coupon;
        }

        return array_values($sanitized);
    }

    private function generate_rank_coupon_signature(?array $rank, array $coupons): string
    {
        if (null === $rank) {
            return '';
        }

        $parts = [
            (string) ($rank['name'] ?? ''),
            (string) ($rank['threshold'] ?? ''),
            implode('|', $coupons),
        ];

        return hash('sha256', implode('::', $parts));
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

        $metric            = $customers[0]['metric'] ?? 'spending';
        $is_points_ranking = 'points' === $metric;
        $subtitle          = $is_points_ranking
            ? __('Cảm ơn vì đã đồng hành cùng cửa hàng. Dưới đây là bảng xếp hạng %s khách hàng tích lũy nhiều điểm thưởng nhất.', 'woo-rewardx-lite')
            : __('Cảm ơn vì đã đồng hành cùng cửa hàng. Dưới đây là bảng xếp hạng %s khách hàng mua sắm nhiều nhất.', 'woo-rewardx-lite');
        $metric_label      = $is_points_ranking
            ? __('Bảng xếp hạng theo điểm thưởng', 'woo-rewardx-lite')
            : __('Bảng xếp hạng theo chi tiêu', 'woo-rewardx-lite');

        $top_value = isset($customers[0]['total_spent']) ? (float) $customers[0]['total_spent'] : 0.0;

        ob_start();
        ?>
        <div class="rewardx-top-customers">
            <div class="rewardx-top-customers__header">
                <span class="rewardx-top-customers__metric">
                    <?php echo esc_html($metric_label); ?>
                </span>
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
                    $customer_total = isset($customer['total_spent']) ? (float) $customer['total_spent'] : 0.0;
                    $progress_ratio = $top_value > 0 ? max(0.0, min(1.0, $customer_total / $top_value)) : 0.0;
                    $progress_value = round($progress_ratio * 100, 1);
                    $progress_style = number_format((float) $progress_value, 1, '.', '');
                    $progress_label = number_format_i18n((float) round($progress_value));

                    if (1 === $position) {
                        $class = ' rewardx-top-customers__item--gold';
                    } elseif (2 === $position) {
                        $class = ' rewardx-top-customers__item--silver';
                    } elseif (3 === $position) {
                        $class = ' rewardx-top-customers__item--bronze';
                    }

                    $medal_class = '';
                    if ($position <= 3) {
                        $medal_class = ' rewardx-top-customers__medal--' . ($position === 1 ? 'gold' : ($position === 2 ? 'silver' : 'bronze'));
                    }
                    ?>
                    <li class="rewardx-top-customers__item<?php echo esc_attr($class); ?>">
                        <div class="rewardx-top-customers__row">
                            <div class="rewardx-top-customers__rank-wrapper">
                                <span class="rewardx-top-customers__rank"><?php echo esc_html($position); ?></span>
                                <?php if (!empty($medal_class)) : ?>
                                    <span class="rewardx-top-customers__medal<?php echo esc_attr($medal_class); ?>" aria-hidden="true"></span>
                                <?php endif; ?>
                            </div>
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
                        </div>
                        <div class="rewardx-top-customers__progress" role="img" aria-label="<?php echo esc_attr(sprintf(
                            /* translators: %s: percentage value. */
                            __('Mức độ đạt được: %s%%', 'woo-rewardx-lite'),
                            $progress_label
                        )); ?>">
                            <span class="rewardx-top-customers__progress-bar" style="--rewardx-progress: <?php echo esc_attr($progress_style); ?>%;"></span>
                        </div>
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

        $limit = max(1, $limit);

        /**
         * Filters the maximum number of customers that will be collected before ranking.
         * Return 0, a negative value, or strings such as "all"/"unlimited" to fetch
         * every available customer.
         *
         * @param int|string $collection_limit Default collection limit.
         * @param int        $limit            Requested leaderboard size.
         */
        $raw_collection_limit = apply_filters('rewardx_top_customers_collection_limit', 200, $limit);
        $collection_limit     = $limit;
        $is_unlimited         = false;

        if (is_string($raw_collection_limit)) {
            $normalized_raw_limit = strtolower(trim($raw_collection_limit));

            if (in_array($normalized_raw_limit, ['all', 'unlimited', '-1', '*'], true)) {
                $is_unlimited = true;
            }
        }

        if (!$is_unlimited) {
            $filtered_limit = (int) $raw_collection_limit;

            if ($filtered_limit <= 0) {
                $is_unlimited = true;
            } else {
                $collection_limit = max($limit, $filtered_limit);
            }
        }

        if ($is_unlimited) {
            $collection_limit = PHP_INT_MAX;
        }

        $is_unlimited_collection = PHP_INT_MAX === $collection_limit;
        $table_name        = $wpdb->prefix . 'wc_customer_lookup';
        $customers         = [];

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

                $sql = "SELECT {$select_columns} FROM {$table_name} WHERE total_spent > 0 ORDER BY total_spent DESC";

                if (!$is_unlimited_collection) {
                    $sql .= $wpdb->prepare(' LIMIT %d', $collection_limit);
                }

                $results = $wpdb->get_results($sql, ARRAY_A);

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
                        $unique_key = '';

                        $user_id = isset($row['user_id']) ? (int) $row['user_id'] : 0;
                        if ($user_id > 0) {
                            $unique_key = 'user:' . $user_id;
                        } elseif (isset($row['customer_id'])) {
                            $customer_id = (int) $row['customer_id'];

                            if ($customer_id > 0) {
                                $unique_key = 'customer:' . $customer_id;
                            }
                        }

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

                        $this->add_customer_to_collection(
                            $customers,
                            $first_name,
                            $last_name,
                            $email,
                            $city,
                            $total_spent,
                            'spending',
                            $unique_key
                        );
                    }
                }
            }
        }

        if (count($customers) < $collection_limit && class_exists('\\WC_Customer_Query')) {
            $query = new \WC_Customer_Query([
                'number'  => $is_unlimited_collection ? -1 : $collection_limit,
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

                $customer_id_key = (int) $customer->get_id() > 0 ? 'user:' . (int) $customer->get_id() : '';

                $this->add_customer_to_collection(
                    $customers,
                    (string) $customer->get_first_name(),
                    (string) $customer->get_last_name(),
                    (string) $customer->get_email(),
                    (string) $customer->get_billing_city(),
                    $total_spent,
                    'spending',
                    $customer_id_key
                );

                if (!$is_unlimited_collection && count($customers) >= $collection_limit) {
                    break;
                }
            }
        }

        if (count($customers) < $collection_limit && class_exists('\\WP_User_Query')) {
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
                        'user_id'     => $user_id,
                    ];
                }

                if (!empty($aggregated)) {
                    uasort($aggregated, static function (array $a, array $b): int {
                        return $b['total_spent'] <=> $a['total_spent'];
                    });

                    $remaining = $collection_limit - count($customers);

                    if ($remaining > 0) {
                        foreach (array_slice($aggregated, 0, $remaining, true) as $data) {
                            $this->add_customer_to_collection(
                                $customers,
                                $data['first_name'],
                                $data['last_name'],
                                $data['email'],
                                $data['city'],
                                (float) $data['total_spent'],
                                'spending',
                                isset($data['user_id']) && (int) $data['user_id'] > 0 ? 'user:' . (int) $data['user_id'] : ''
                            );
                        }
                    }
                }
            }
        }

        if (count($customers) < $collection_limit) {
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

                    $this->add_customer_to_collection(
                        $customers,
                        (string) ($row['billing_first_name'] ?? ''),
                        (string) ($row['billing_last_name'] ?? ''),
                        (string) ($row['billing_email'] ?? ''),
                        (string) ($row['billing_city'] ?? ''),
                        $total_spent,
                        'spending'
                    );

                    if (!$is_unlimited_collection && count($customers) >= $collection_limit) {
                        break;
                    }
                }
            }
        }

        if (count($customers) < $collection_limit && class_exists('\\WC_Order_Query') && function_exists('wc_get_order')) {
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
                                'customer_id' => $customer_id,
                            ];
                        }

                        $aggregated[$key]['total_spent'] += $total;
                    }

                    if (!empty($aggregated)) {
                        uasort($aggregated, static function (array $a, array $b): int {
                            return $b['total_spent'] <=> $a['total_spent'];
                        });

                        $remaining = $collection_limit - count($customers);

                        if ($remaining > 0) {
                            foreach (array_slice($aggregated, 0, $remaining, true) as $data) {
                                $customer_id = isset($data['customer_id']) ? (int) $data['customer_id'] : 0;
                                $email_key   = '';

                                if ($customer_id > 0) {
                                    $email_key = 'user:' . $customer_id;
                                } else {
                                    $normalized_email = strtolower(trim((string) ($data['email'] ?? '')));

                                    if ('' !== $normalized_email) {
                                        $email_key = 'guest-email:' . $normalized_email;
                                    }
                                }

                                $this->add_customer_to_collection(
                                    $customers,
                                    $data['first_name'],
                                    $data['last_name'],
                                    $data['email'],
                                    $data['city'],
                                    (float) $data['total_spent'],
                                    'spending',
                                    $email_key
                                );

                                if (!$is_unlimited_collection && count($customers) >= $collection_limit) {
                                    break;
                                }
                            }
                        }
                    }
                }
            }
        }

        if (!empty($customers)) {
            $customers = array_filter(
                $customers,
                static function (array $customer): bool {
                    return 'spending' === ($customer['metric'] ?? 'spending');
                }
            );

            if (!empty($customers)) {
                uasort($customers, static function (array $a, array $b): int {
                    return $b['total_spent'] <=> $a['total_spent'];
                });
            }
        }

        $customers = array_values($customers);

        return array_slice($customers, 0, $limit);
    }

    /**
     * @param array<string, array{name: string, meta: string, total_spent: float, metric: string}> $customers
     * @param string                                                                                 $unique_key Optional explicit key to prevent duplicates.
     */
    private function add_customer_to_collection(array &$customers, string $first_name, string $last_name, string $email, string $city, float $total_spent, string $metric, string $unique_key = ''): bool
    {
        if ($total_spent <= 0) {
            return false;
        }

        $normalized_unique_key = $this->normalize_customer_key($unique_key);
        $key                   = '' !== $normalized_unique_key
            ? $normalized_unique_key
            : $this->generate_customer_key($first_name, $last_name, $email, $city, $total_spent);
        $meta = $this->format_customer_meta($city);
        $name = $this->mask_customer_name($first_name, $last_name, $email);

        if (isset($customers[$key])) {
            if ($total_spent > $customers[$key]['total_spent']) {
                $customers[$key]['total_spent'] = $total_spent;
            }

            if ('' !== $normalized_unique_key) {
                $current_metric = $customers[$key]['metric'] ?? '';

                if ('spending' === $metric || '' === $current_metric) {
                    $customers[$key]['metric'] = $metric;
                }
            }

            if ('' !== $meta && '' === ($customers[$key]['meta'] ?? '')) {
                $customers[$key]['meta'] = $meta;
            }

            $anonymous_label = esc_html__('Khách hàng ẩn danh', 'woo-rewardx-lite');
            if ($name !== $anonymous_label && ($customers[$key]['name'] ?? '') === $anonymous_label) {
                $customers[$key]['name'] = $name;
            }

            return false;
        }

        $customers[$key] = [
            'name'        => $name,
            'meta'        => $meta,
            'total_spent' => $total_spent,
            'metric'      => $metric,
        ];

        return true;
    }

    /**
     * Ensures the unique customer key is normalized for consistent comparisons.
     */
    private function normalize_customer_key(string $unique_key): string
    {
        $unique_key = trim($unique_key);

        if ('' === $unique_key) {
            return '';
        }

        return strtolower($unique_key);
    }

    private function generate_customer_key(string $first_name, string $last_name, string $email, string $city, float $total_spent): string
    {
        $normalized_email = strtolower(trim($email));
        if ('' !== $normalized_email) {
            return 'email:' . $normalized_email;
        }

        $normalized_name = trim($first_name . ' ' . $last_name);
        if ('' !== $normalized_name) {
            return 'name:' . mb_strtolower($normalized_name);
        }

        $normalized_city = trim($city);
        if ('' !== $normalized_city) {
            return 'city:' . mb_strtolower($normalized_city);
        }

        return 'anon:' . md5(sprintf('%.2f', $total_spent));
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
