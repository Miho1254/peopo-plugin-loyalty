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

        // ===== Hook phía frontend =====
        add_action('wp_enqueue_scripts', [$this, 'enqueue_public_assets']);
        add_shortcode($this->slug, [$this, 'render_shortcode']);

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
        self::instance()->register_custom_post_type();
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
     * Hiển thị trang quản trị đơn giản.
     */
    public function render_admin_page(): void
    {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Cấu hình Peopo Loyalty', 'peopo-loyalty'); ?></h1>
            <p><?php esc_html_e('Đây là trang cấu hình mẫu. Hãy thay thế nội dung này bằng form cài đặt thực tế.', 'peopo-loyalty'); ?></p>
        </div>
        <?php
    }
}
