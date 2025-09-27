<?php
namespace MihoMemberShip;

// Luôn bảo vệ file trong thư mục includes khỏi truy cập trực tiếp.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Plugin
 *
 * Đây là class "điều phối" trung tâm, chịu trách nhiệm đăng ký các hook
 * và gom logic quản trị/frontend vào cùng một nơi. Khi mở rộng plugin, ta
 * chỉ cần thêm method mới hoặc tạo class con rồi gọi tại đây.
 */
final class Plugin
{
    /**
     * Giữ duy nhất một instance (singleton) để tránh khởi tạo nhiều lần.
     *
     * @var Plugin|null
     */
    private static ?Plugin $instance = null;

    /**
     * Tên viết tắt của plugin, dùng khi đăng ký shortcode, option, v.v.
     */
    private string $slug = 'peopo-loyalty';

    /**
     * Phiên bản plugin, phục vụ việc cache-busting khi enqueue assets.
     */
    private string $version;

    /**
     * Option key lưu trữ catalog phần thưởng.
     */
    private string $reward_option_key = 'peopo_loyalty_rewards';

    /**
     * Trạng thái đã đăng ký hook chưa, để tránh gọi run() nhiều lần.
     */
    private bool $booted = false;

    /**
     * Lấy instance duy nhất của plugin.
     */
    public static function instance(): Plugin
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Hàm khởi tạo private để đảm bảo singleton.
     */
    private function __construct()
    {
        $this->version = defined('PEOPO_LOYALTY_VERSION') ? PEOPO_LOYALTY_VERSION : '1.0.0';
    }

    /**
     * Đăng ký toàn bộ hook của plugin.
     */
    public function run(): void
    {
        // Đảm bảo chỉ chạy một lần duy nhất.
        if ($this->booted) {
            return;
        }

        $this->booted = true;

        // ===== Các hook liên quan đến đa ngôn ngữ =====
        add_action('init', [$this, 'load_textdomain']);

        // ===== Hook phía backend (admin) =====
        add_action('admin_menu', [$this, 'register_admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('admin_init', [$this, 'handle_admin_actions']);

        // ===== Hook phía frontend =====
        add_action('wp_enqueue_scripts', [$this, 'enqueue_public_assets']);
        add_shortcode($this->slug, [$this, 'render_shortcode']);

        // ===== Hook WooCommerce =====
        add_action('init', [$this, 'register_account_endpoint']);
        add_filter('woocommerce_account_menu_items', [$this, 'add_account_menu_item']);
        add_action('woocommerce_account_peopo-loyalty_endpoint', [$this, 'render_account_endpoint']);
        add_action('woocommerce_order_status_completed', [$this, 'sync_order_points']);

        add_action('show_user_profile', [$this, 'render_user_profile_fields']);
        add_action('edit_user_profile', [$this, 'render_user_profile_fields']);
        add_action('personal_options_update', [$this, 'save_user_profile_fields']);
        add_action('edit_user_profile_update', [$this, 'save_user_profile_fields']);

        // ===== Hook dữ liệu =====
        add_action('init', [$this, 'register_custom_post_type']);
    }

    /**
     * Hàm chạy khi plugin được kích hoạt lần đầu.
     * Có thể tạo option mặc định, tạo bảng, seed dữ liệu...
     */
    public static function activate(): void
    {
        // Thiết lập một option ví dụ để lưu trạng thái kích hoạt.
        if (!get_option('peopo_loyalty_enabled')) {
            add_option('peopo_loyalty_enabled', 'yes');
        }

        // Thường sẽ cần flush rewrite khi đăng ký CPT.
        $instance = self::instance();
        $instance->register_custom_post_type();
        $instance->register_account_endpoint();
        flush_rewrite_rules();
    }

    /**
     * Hàm chạy khi plugin bị deactivate (nhưng chưa uninstall).
     * Có thể dọn các cache tạm hoặc tắt cron job.
     */
    public static function deactivate(): void
    {
        // Ví dụ: tắt cron job nếu có.
        wp_clear_scheduled_hook('peopo_loyalty_cron');

        flush_rewrite_rules();
    }

    /**
     * Nạp file dịch trong thư mục languages.
     */
    public function load_textdomain(): void
    {
        load_plugin_textdomain(
            'peopo-loyalty',
            false,
            dirname(PEOPO_LOYALTY_BASENAME) . '/languages'
        );
    }

    /**
     * Đăng ký một menu đơn giản trong trang quản trị.
     */
    public function register_admin_menu(): void
    {
        add_menu_page(
            __('Peopo Loyalty', 'peopo-loyalty'),
            __('Peopo Loyalty', 'peopo-loyalty'),
            'manage_options',
            $this->slug,
            [$this, 'render_admin_page'],
            'dashicons-admin-users'
        );
    }

    /**
     * Enqueue CSS/JS cho trang quản trị.
     */
    public function enqueue_admin_assets(string $hook): void
    {
        // Chỉ nạp script khi đang ở đúng trang admin của plugin để tránh lãng phí.
        if ($hook !== 'toplevel_page_' . $this->slug) {
            return;
        }

        wp_enqueue_style(
            $this->slug . '-admin',
            PEOPO_LOYALTY_URL . 'assets/css/admin.css',
            [],
            $this->version
        );

        wp_enqueue_script(
            $this->slug . '-admin',
            PEOPO_LOYALTY_URL . 'assets/js/admin.js',
            ['jquery'],
            $this->version,
            true
        );
    }

    /**
     * Enqueue CSS/JS ở frontend.
     */
    public function enqueue_public_assets(): void
    {
        wp_enqueue_style(
            $this->slug . '-public',
            PEOPO_LOYALTY_URL . 'assets/css/public.css',
            [],
            $this->version
        );

        wp_enqueue_script(
            $this->slug . '-public',
            PEOPO_LOYALTY_URL . 'assets/js/public.js',
            ['jquery'],
            $this->version,
            true
        );
    }

    /**
     * Ví dụ render nội dung shortcode [peopo-loyalty].
     */
    public function render_shortcode(array $atts = [], ?string $content = null): string
    {
        // Merge attribute với giá trị mặc định.
        $atts = shortcode_atts([
            'title' => __('Thành viên thân thiết', 'peopo-loyalty'),
        ], $atts, $this->slug);

        ob_start();
        ?>
        <div class="peopo-loyalty-box">
            <h3 class="peopo-loyalty-box__title"><?php echo esc_html($atts['title']); ?></h3>
            <p class="peopo-loyalty-box__content">
                <?php echo esc_html($content ?? __('Bạn chưa có điểm thưởng nào, hãy mua sắm để tích điểm nhé!', 'peopo-loyalty')); ?>
            </p>
        </div>
        <?php
        return (string) ob_get_clean();
    }

    /**
     * Đăng ký Custom Post Type ví dụ, phục vụ lưu trữ lịch sử tích điểm.
     */
    public function register_custom_post_type(): void
    {
        $labels = [
            'name' => __('Giao dịch loyalty', 'peopo-loyalty'),
            'singular_name' => __('Giao dịch loyalty', 'peopo-loyalty'),
            'add_new' => __('Thêm mới', 'peopo-loyalty'),
            'add_new_item' => __('Thêm giao dịch mới', 'peopo-loyalty'),
            'edit_item' => __('Sửa giao dịch', 'peopo-loyalty'),
            'menu_name' => __('Giao dịch loyalty', 'peopo-loyalty'),
        ];

        $args = [
            'labels' => $labels,
            'public' => false,
            'show_ui' => true,
            'supports' => ['title', 'editor', 'custom-fields'],
            'show_in_menu' => false, // quản lý thông qua menu riêng đã tạo.
        ];

        register_post_type('peopo_loyalty_transaction', $args);
    }

    /**
     * Đăng ký endpoint loyalty tại trang tài khoản WooCommerce.
     */
    public function register_account_endpoint(): void
    {
        add_rewrite_endpoint('peopo-loyalty', EP_ROOT | EP_PAGES);
    }

    /**
     * Thêm mục menu mới trong trang tài khoản.
     */
    public function add_account_menu_item(array $items): array
    {
        if (!function_exists('wc_get_page_id') || wc_get_page_id('myaccount') === -1) {
            return $items;
        }

        $logout = $items['customer-logout'] ?? null;

        if (null !== $logout) {
            unset($items['customer-logout']);
        }

        $items['peopo-loyalty'] = __('Thành viên thân thiết', 'peopo-loyalty');

        if (null !== $logout) {
            $items['customer-logout'] = $logout;
        }

        return $items;
    }

    /**
     * Render nội dung của tab loyalty trong tài khoản người dùng.
     */
    public function render_account_endpoint(): void
    {
        if (!is_user_logged_in()) {
            echo '<p>' . esc_html__('Vui lòng đăng nhập để xem thông tin thành viên.', 'peopo-loyalty') . '</p>';
            return;
        }

        $user_id = get_current_user_id();
        $user = get_userdata($user_id);

        if (!$user instanceof \WP_User) {
            echo '<p>' . esc_html__('Không tìm thấy thông tin tài khoản.', 'peopo-loyalty') . '</p>';
            return;
        }

        $total_spent = function_exists('wc_get_customer_total_spent')
            ? (float) wc_get_customer_total_spent($user_id)
            : (float) get_user_meta($user_id, '_money_spent', true);

        $points = (int) get_user_meta($user_id, 'peopo_loyalty_points', true);
        $points_value = $points * 1000;

        $tier = $this->determine_tier($total_spent);

        ?>
        <div class="peopo-loyalty-account">
            <h2><?php esc_html_e('Chương trình khách hàng thân thiết', 'peopo-loyalty'); ?></h2>
            <ul class="peopo-loyalty-account__list">
                <li><strong><?php esc_html_e('Tên khách hàng:', 'peopo-loyalty'); ?></strong> <?php echo esc_html($user->display_name); ?></li>
                <li>
                    <strong><?php esc_html_e('Tổng chi tiêu:', 'peopo-loyalty'); ?></strong>
                    <?php echo wp_kses_post($this->format_price($total_spent)); ?>
                </li>
                <li>
                    <strong><?php esc_html_e('Điểm đã tích luỹ:', 'peopo-loyalty'); ?></strong>
                    <?php echo esc_html(number_format_i18n($points)); ?>
                    <span class="peopo-loyalty-account__note">
                        <?php
                        printf(
                            esc_html__('(Quy đổi tương đương %s)', 'peopo-loyalty'),
                            wp_kses_post($this->format_price($points_value))
                        );
                        ?>
                    </span>
                </li>
                <li>
                    <strong><?php esc_html_e('Hạng hiện tại:', 'peopo-loyalty'); ?></strong>
                    <?php echo esc_html($tier['label']); ?>
                </li>
            </ul>
            <p class="peopo-loyalty-account__desc"><?php echo esc_html($tier['description']); ?></p>
        </div>
        <?php
    }

    /**
     * Cộng điểm sau khi đơn hàng được hoàn tất.
     */
    public function sync_order_points($order_id): void
    {
        if (!function_exists('wc_get_order')) {
            return;
        }

        $order = wc_get_order($order_id);

        if (!$order instanceof \WC_Order) {
            return;
        }

        $user_id = $order->get_user_id();

        if (!$user_id) {
            return;
        }

        $total = (float) $order->get_total();
        $points = (int) floor($total / 1000);

        if ($points <= 0) {
            return;
        }

        $current_points = (int) get_user_meta($user_id, 'peopo_loyalty_points', true);
        update_user_meta($user_id, 'peopo_loyalty_points', $current_points + $points);
    }

    /**
     * Trường tuỳ chỉnh trong hồ sơ người dùng để quản trị viên chỉnh điểm.
     */
    public function render_user_profile_fields($user): void
    {
        if (!current_user_can('manage_woocommerce') && !current_user_can('manage_options')) {
            return;
        }

        $points = (int) get_user_meta($user->ID, 'peopo_loyalty_points', true);
        ?>
        <h2><?php esc_html_e('Điểm thành viên Peopo Loyalty', 'peopo-loyalty'); ?></h2>
        <table class="form-table" role="presentation">
            <tr>
                <th scope="row"><label for="peopo_loyalty_points"><?php esc_html_e('Điểm tích luỹ', 'peopo-loyalty'); ?></label></th>
                <td>
                    <input type="number" class="regular-text" name="peopo_loyalty_points" id="peopo_loyalty_points" min="0" value="<?php echo esc_attr($points); ?>" />
                    <p class="description"><?php esc_html_e('Điền số điểm mong muốn để cập nhật cho khách hàng.', 'peopo-loyalty'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Lưu lại điểm khi quản trị viên cập nhật hồ sơ người dùng.
     */
    public function save_user_profile_fields($user_id): void
    {
        if (!current_user_can('manage_woocommerce') && !current_user_can('manage_options')) {
            return;
        }

        if (!isset($_POST['peopo_loyalty_points'])) {
            return;
        }

        $points = max(0, (int) sanitize_text_field(wp_unslash($_POST['peopo_loyalty_points'])));
        update_user_meta($user_id, 'peopo_loyalty_points', $points);
    }

    /**
     * Xác định hạng thành viên dựa trên tổng chi tiêu.
     */
    private function determine_tier(float $total_spent): array
    {
        $tiers = [
            [
                'min' => 50000000,
                'label' => __('Kim cương', 'peopo-loyalty'),
                'description' => __('Bạn ở hạng cao nhất với những ưu đãi độc quyền.', 'peopo-loyalty'),
            ],
            [
                'min' => 20000000,
                'label' => __('Vàng', 'peopo-loyalty'),
                'description' => __('Bạn đã ở hạng Vàng. Hãy tiếp tục mua sắm để chạm tới Kim cương.', 'peopo-loyalty'),
            ],
            [
                'min' => 5000000,
                'label' => __('Bạc', 'peopo-loyalty'),
                'description' => __('Chỉ còn một bước nữa để đạt hạng Vàng!', 'peopo-loyalty'),
            ],
        ];

        foreach ($tiers as $tier) {
            if ($total_spent >= $tier['min']) {
                return $tier;
            }
        }

        return [
            'min' => 0,
            'label' => __('Đồng', 'peopo-loyalty'),
            'description' => __('Bắt đầu tích điểm ngay hôm nay để nâng hạng nhanh chóng.', 'peopo-loyalty'),
        ];
    }

    /**
     * Định dạng tiền tệ theo cấu hình WooCommerce.
     */
    private function format_price(float $amount): string
    {
        if (function_exists('wc_price')) {
            return wc_price($amount);
        }

        return esc_html(number_format_i18n($amount)) . ' VND';
    }

    /**
     * Hiển thị trang quản trị đơn giản.
     */
    public function render_admin_page(): void
    {
        $rewards = $this->get_reward_items();
        $current_action = isset($_GET['action']) ? sanitize_key(wp_unslash($_GET['action'])) : '';
        $editing_id = isset($_GET['reward_id']) ? sanitize_text_field(wp_unslash($_GET['reward_id'])) : '';
        $is_edit = 'edit' === $current_action && isset($rewards[$editing_id]);
        $editing_reward = $is_edit ? $rewards[$editing_id] : [
            'id'          => '',
            'name'        => '',
            'type'        => 'digital',
            'points'      => 0,
            'description' => '',
        ];

        $message_key = isset($_GET['message']) ? sanitize_key(wp_unslash($_GET['message'])) : '';
        $messages = [
            'created' => __('Đã thêm phần thưởng mới.', 'peopo-loyalty'),
            'updated' => __('Đã cập nhật phần thưởng.', 'peopo-loyalty'),
            'deleted' => __('Đã xoá phần thưởng.', 'peopo-loyalty'),
            'error'   => __('Có lỗi xảy ra, vui lòng thử lại.', 'peopo-loyalty'),
        ];

        ?>
        <div class="wrap peopo-loyalty-admin">
            <h1><?php esc_html_e('Quản lý phần thưởng Loyalty', 'peopo-loyalty'); ?></h1>
            <p class="peopo-loyalty-admin__intro">
                <?php esc_html_e('Tạo catalog phần thưởng để khách hàng quy đổi điểm. Bạn có thể thêm, chỉnh sửa hoặc xoá từng phần thưởng ngay tại đây.', 'peopo-loyalty'); ?>
            </p>

            <?php if ($message_key && isset($messages[$message_key])) : ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php echo esc_html($messages[$message_key]); ?></p>
                </div>
            <?php endif; ?>

            <div class="peopo-loyalty-admin__grid">
                <section class="peopo-loyalty-admin__card">
                    <header>
                        <h2>
                            <?php
                            echo $is_edit
                                ? esc_html__('Chỉnh sửa phần thưởng', 'peopo-loyalty')
                                : esc_html__('Thêm phần thưởng mới', 'peopo-loyalty');
                            ?>
                        </h2>
                        <p>
                            <?php esc_html_e('Đặt tên, loại phần thưởng và số điểm quy đổi tương ứng.', 'peopo-loyalty'); ?>
                        </p>
                    </header>

                    <form method="post" class="peopo-loyalty-admin__form">
                        <?php wp_nonce_field('peopo_loyalty_save_reward'); ?>
                        <input type="hidden" name="peopo_loyalty_action" value="<?php echo $is_edit ? 'update' : 'create'; ?>">
                        <?php if ($is_edit) : ?>
                            <input type="hidden" name="reward_id" value="<?php echo esc_attr($editing_reward['id']); ?>">
                        <?php endif; ?>

                        <div class="peopo-loyalty-admin__field">
                            <label for="reward_name" class="peopo-loyalty-admin__label"><?php esc_html_e('Tên phần thưởng', 'peopo-loyalty'); ?></label>
                            <input type="text" id="reward_name" name="reward_name" class="regular-text" required value="<?php echo esc_attr($editing_reward['name']); ?>">
                        </div>

                        <div class="peopo-loyalty-admin__field">
                            <label for="reward_type" class="peopo-loyalty-admin__label"><?php esc_html_e('Loại phần thưởng', 'peopo-loyalty'); ?></label>
                            <select id="reward_type" name="reward_type">
                                <option value="digital" <?php selected('digital', $editing_reward['type']); ?>><?php esc_html_e('Phi vật lý (voucher, ưu đãi)', 'peopo-loyalty'); ?></option>
                                <option value="physical" <?php selected('physical', $editing_reward['type']); ?>><?php esc_html_e('Vật lý (quà tặng, sản phẩm)', 'peopo-loyalty'); ?></option>
                            </select>
                        </div>

                        <div class="peopo-loyalty-admin__field">
                            <label for="reward_points" class="peopo-loyalty-admin__label"><?php esc_html_e('Điểm quy đổi', 'peopo-loyalty'); ?></label>
                            <input type="number" id="reward_points" name="reward_points" class="small-text" min="0" step="1" required value="<?php echo esc_attr((int) $editing_reward['points']); ?>">
                        </div>

                        <div class="peopo-loyalty-admin__field">
                            <label for="reward_description" class="peopo-loyalty-admin__label"><?php esc_html_e('Mô tả / điều kiện', 'peopo-loyalty'); ?></label>
                            <textarea id="reward_description" name="reward_description" rows="4" class="large-text"><?php echo esc_textarea($editing_reward['description']); ?></textarea>
                        </div>

                        <p class="submit">
                            <button type="submit" class="button button-primary">
                                <?php
                                echo $is_edit
                                    ? esc_html__('Cập nhật phần thưởng', 'peopo-loyalty')
                                    : esc_html__('Thêm phần thưởng', 'peopo-loyalty');
                                ?>
                            </button>
                            <?php if ($is_edit) : ?>
                                <a class="button" href="<?php echo esc_url(remove_query_arg(['action', 'reward_id'])); ?>">
                                    <?php esc_html_e('Huỷ chỉnh sửa', 'peopo-loyalty'); ?>
                                </a>
                            <?php endif; ?>
                        </p>
                    </form>
                </section>

                <section class="peopo-loyalty-admin__card">
                    <header>
                        <h2><?php esc_html_e('Danh sách phần thưởng', 'peopo-loyalty'); ?></h2>
                        <p><?php esc_html_e('Quản trị nhanh các phần thưởng đang mở cho khách hàng.', 'peopo-loyalty'); ?></p>
                    </header>

                    <table class="peopo-loyalty-admin__table">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Tên phần thưởng', 'peopo-loyalty'); ?></th>
                                <th><?php esc_html_e('Loại', 'peopo-loyalty'); ?></th>
                                <th><?php esc_html_e('Điểm', 'peopo-loyalty'); ?></th>
                                <th><?php esc_html_e('Mô tả', 'peopo-loyalty'); ?></th>
                                <th class="peopo-loyalty-admin__table-actions"><?php esc_html_e('Thao tác', 'peopo-loyalty'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($rewards)) : ?>
                                <tr>
                                    <td colspan="5" class="peopo-loyalty-admin__empty">
                                        <?php esc_html_e('Chưa có phần thưởng nào, hãy thêm phần thưởng đầu tiên.', 'peopo-loyalty'); ?>
                                    </td>
                                </tr>
                            <?php else : ?>
                                <?php foreach ($rewards as $reward) : ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo esc_html($reward['name']); ?></strong>
                                        </td>
                                        <td><?php echo esc_html($this->get_reward_type_label($reward['type'])); ?></td>
                                        <td>
                                            <strong><?php echo esc_html(number_format_i18n((int) $reward['points'])); ?></strong>
                                            <span class="peopo-loyalty-admin__points-label"><?php esc_html_e('điểm', 'peopo-loyalty'); ?></span>
                                        </td>
                                        <td><?php echo esc_html($reward['description']); ?></td>
                                        <td class="peopo-loyalty-admin__table-actions">
                                            <?php
                                            $edit_url = add_query_arg(
                                                [
                                                    'action'    => 'edit',
                                                    'reward_id' => rawurlencode($reward['id']),
                                                ]
                                            );
                                            $delete_url = wp_nonce_url(
                                                add_query_arg(
                                                    [
                                                        'action'    => 'delete',
                                                        'reward_id' => rawurlencode($reward['id']),
                                                    ]
                                                ),
                                                'peopo_loyalty_delete_reward_' . $reward['id']
                                            );
                                            ?>
                                            <a class="button button-small" href="<?php echo esc_url($edit_url); ?>"><?php esc_html_e('Sửa', 'peopo-loyalty'); ?></a>
                                            <a class="button button-small button-link-delete" href="<?php echo esc_url($delete_url); ?>" onclick="return confirm('<?php echo esc_js(__('Bạn có chắc muốn xoá phần thưởng này?', 'peopo-loyalty')); ?>');">
                                                <?php esc_html_e('Xoá', 'peopo-loyalty'); ?>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </section>
            </div>

            <p class="peopo-loyalty-admin__note">
                <?php esc_html_e('Gợi ý: bạn có thể kết hợp hệ thống voucher hoặc quà độc quyền để tăng trải nghiệm đổi điểm.', 'peopo-loyalty'); ?>
            </p>
        </div>
        <?php
    }

    /**
     * Xử lý các hành động CRUD của phần thưởng.
     */
    public function handle_admin_actions(): void
    {
        if (!is_admin()) {
            return;
        }

        $page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';

        if ($page !== $this->slug) {
            return;
        }

        if (!current_user_can('manage_options')) {
            return;
        }

        $redirect = menu_page_url($this->slug, false);

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['peopo_loyalty_action'])) {
            check_admin_referer('peopo_loyalty_save_reward');

            $action = sanitize_key(wp_unslash($_POST['peopo_loyalty_action']));
            $name = isset($_POST['reward_name']) ? sanitize_text_field(wp_unslash($_POST['reward_name'])) : '';
            $type = isset($_POST['reward_type']) ? sanitize_key(wp_unslash($_POST['reward_type'])) : 'digital';
            $points = isset($_POST['reward_points']) ? max(0, (int) sanitize_text_field(wp_unslash($_POST['reward_points']))) : 0;
            $description = isset($_POST['reward_description']) ? sanitize_textarea_field(wp_unslash($_POST['reward_description'])) : '';

            if (!in_array($type, ['digital', 'physical'], true) || '' === $name) {
                wp_safe_redirect(add_query_arg('message', 'error', $redirect));
                exit;
            }

            $rewards = $this->get_reward_items();

            if ('create' === $action) {
                $id = wp_generate_uuid4();
                $rewards[$id] = [
                    'id'          => $id,
                    'name'        => $name,
                    'type'        => $type,
                    'points'      => $points,
                    'description' => $description,
                ];

                $this->save_reward_items($rewards);
                wp_safe_redirect(add_query_arg('message', 'created', $redirect));
                exit;
            }

            if ('update' === $action) {
                $reward_id = isset($_POST['reward_id']) ? sanitize_text_field(wp_unslash($_POST['reward_id'])) : '';

                if ('' === $reward_id || !isset($rewards[$reward_id])) {
                    wp_safe_redirect(add_query_arg('message', 'error', $redirect));
                    exit;
                }

                $rewards[$reward_id] = [
                    'id'          => $reward_id,
                    'name'        => $name,
                    'type'        => $type,
                    'points'      => $points,
                    'description' => $description,
                ];

                $this->save_reward_items($rewards);
                wp_safe_redirect(add_query_arg('message', 'updated', $redirect));
                exit;
            }
        }

        if (isset($_GET['action']) && 'delete' === sanitize_key(wp_unslash($_GET['action']))) {
            $reward_id = isset($_GET['reward_id']) ? sanitize_text_field(wp_unslash($_GET['reward_id'])) : '';

            if ('' === $reward_id) {
                return;
            }

            check_admin_referer('peopo_loyalty_delete_reward_' . $reward_id);

            $rewards = $this->get_reward_items();

            if (!isset($rewards[$reward_id])) {
                wp_safe_redirect(add_query_arg('message', 'error', $redirect));
                exit;
            }

            unset($rewards[$reward_id]);
            $this->save_reward_items($rewards);

            wp_safe_redirect(add_query_arg('message', 'deleted', $redirect));
            exit;
        }
    }

    /**
     * Lấy danh sách phần thưởng đã lưu.
     */
    private function get_reward_items(): array
    {
        $stored = get_option($this->reward_option_key, []);

        if (!is_array($stored)) {
            return [];
        }

        $normalized = [];

        foreach ($stored as $reward) {
            if (!is_array($reward) || empty($reward['id'])) {
                continue;
            }

            $reward_id = (string) $reward['id'];

            $normalized[$reward_id] = [
                'id'          => $reward_id,
                'name'        => isset($reward['name']) ? (string) $reward['name'] : '',
                'type'        => isset($reward['type']) ? (string) $reward['type'] : 'digital',
                'points'      => isset($reward['points']) ? (int) $reward['points'] : 0,
                'description' => isset($reward['description']) ? (string) $reward['description'] : '',
            ];
        }

        return $normalized;
    }

    /**
     * Lưu danh sách phần thưởng.
     */
    private function save_reward_items(array $rewards): void
    {
        update_option($this->reward_option_key, array_values($rewards));
    }

    /**
     * Trả về nhãn hiển thị của loại phần thưởng.
     */
    private function get_reward_type_label(string $type): string
    {
        $labels = [
            'digital'  => __('Phi vật lý', 'peopo-loyalty'),
            'physical' => __('Vật lý', 'peopo-loyalty'),
        ];

        return $labels[$type] ?? $labels['digital'];
    }
}
