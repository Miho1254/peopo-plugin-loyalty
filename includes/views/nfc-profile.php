<?php
if (!defined('ABSPATH')) {
    exit;
}

$profile       = $profile ?? null;
$error_message = $error_message ?? null;

?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <?php wp_head(); ?>
</head>
<body <?php body_class('rewardx-nfc-page'); ?>>
<div class="rewardx-nfc-container">
    <?php if (is_array($profile)) :
        $format_currency = static function (float $amount): string {
            if (function_exists('wc_price')) {
                return (string) wc_price($amount);
            }

            return number_format_i18n($amount, 0);
        };

        $points      = isset($profile['points']) ? (int) $profile['points'] : 0;
        $total_spent = isset($profile['total_spent']) ? (float) $profile['total_spent'] : 0.0;
        $order_count = isset($profile['order_count']) ? (int) $profile['order_count'] : 0;
        $rank        = $profile['current_rank'] ?? null;
        $rank_name   = is_array($rank) && isset($rank['name']) ? (string) $rank['name'] : __('Chưa xếp hạng', 'woo-rewardx-lite');
        $rank_no     = isset($profile['rank_position']) ? (int) $profile['rank_position'] : null;
        $rank_total  = isset($profile['rank_total']) ? (int) $profile['rank_total'] : 0;
        $phone       = isset($profile['phone']) ? (string) $profile['phone'] : '';
        $name        = isset($profile['name']) ? (string) $profile['name'] : '';
        $email       = isset($profile['email']) ? (string) $profile['email'] : '';
        $ocg         = $profile['ocg_remaining'] ?? null;
        ?>
        <header class="rewardx-nfc-header">
            <h1><?php esc_html_e('Hồ sơ thành viên Peopo Loyalty', 'woo-rewardx-lite'); ?></h1>
            <p><?php esc_html_e('Thông tin tóm tắt được hiển thị khi quét thẻ NFC của khách hàng.', 'woo-rewardx-lite'); ?></p>
        </header>

        <section class="rewardx-account rewardx-nfc-account">
            <section class="rewardx-summary" aria-label="<?php esc_attr_e('Thông tin tổng quan', 'woo-rewardx-lite'); ?>">
                <ul class="rewardx-summary-list">
                    <li>
                        <span class="rewardx-summary-label"><?php esc_html_e('Họ tên', 'woo-rewardx-lite'); ?></span>
                        <strong class="rewardx-summary-value"><?php echo esc_html($name); ?></strong>
                    </li>
                    <li>
                        <span class="rewardx-summary-label"><?php esc_html_e('Điểm hiện tại', 'woo-rewardx-lite'); ?></span>
                        <strong class="rewardx-summary-value rewardx-points"><?php echo esc_html(number_format_i18n($points)); ?></strong>
                    </li>
                    <li>
                        <span class="rewardx-summary-label"><?php esc_html_e('Tổng chi tiêu', 'woo-rewardx-lite'); ?></span>
                        <strong class="rewardx-summary-value"><?php echo wp_kses_post($format_currency($total_spent)); ?></strong>
                    </li>
                    <li>
                        <span class="rewardx-summary-label"><?php esc_html_e('Tổng đơn hàng', 'woo-rewardx-lite'); ?></span>
                        <strong class="rewardx-summary-value"><?php echo esc_html(number_format_i18n($order_count)); ?></strong>
                    </li>
                    <li>
                        <span class="rewardx-summary-label"><?php esc_html_e('Thứ hạng hiện tại', 'woo-rewardx-lite'); ?></span>
                        <strong class="rewardx-summary-value">
                            <?php echo esc_html($rank_name); ?>
                            <?php if ($rank_no && $rank_total) : ?>
                                <small style="display:block;font-size:0.85rem;color:var(--rx-muted);font-weight:500;">
                                    <?php
                                    printf(
                                        esc_html__('Thứ tự: %1$d / %2$d', 'woo-rewardx-lite'),
                                        $rank_no,
                                        $rank_total
                                    );
                                    ?>
                                </small>
                            <?php endif; ?>
                        </strong>
                    </li>
                </ul>
            </section>

            <section class="rewardx-nfc-meta" aria-label="<?php esc_attr_e('Thông tin liên hệ', 'woo-rewardx-lite'); ?>">
                <article class="rewardx-nfc-meta-card">
                    <strong><?php esc_html_e('Số điện thoại', 'woo-rewardx-lite'); ?></strong>
                    <span><?php echo '' !== $phone ? esc_html($phone) : esc_html__('Chưa cập nhật', 'woo-rewardx-lite'); ?></span>
                </article>
                <article class="rewardx-nfc-meta-card">
                    <strong><?php esc_html_e('Email đăng ký', 'woo-rewardx-lite'); ?></strong>
                    <span><?php echo '' !== $email ? esc_html($email) : esc_html__('Chưa cập nhật', 'woo-rewardx-lite'); ?></span>
                </article>
            </section>

            <?php if (is_array($ocg)) :
                $expires_at = isset($ocg['expires_at']) ? (int) $ocg['expires_at'] : 0;
                $is_active  = !empty($ocg['is_active']);
                $human_diff = isset($ocg['human_diff']) ? (string) $ocg['human_diff'] : '';
                ?>
                <section class="rewardx-nfc-ocg <?php echo $is_active ? '' : 'is-expired'; ?>" aria-live="polite">
                    <h2><?php esc_html_e('Thời gian còn chơi OCG', 'woo-rewardx-lite'); ?></h2>
                    <?php if ($is_active) : ?>
                        <p>
                            <?php
                            printf(
                                esc_html__('Còn %1$s (đến %2$s).', 'woo-rewardx-lite'),
                                esc_html($human_diff),
                                esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $expires_at))
                            );
                            ?>
                        </p>
                    <?php else : ?>
                        <p>
                            <?php
                            printf(
                                esc_html__('Quyền OCG đã hết hạn %s.', 'woo-rewardx-lite'),
                                esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $expires_at))
                            );
                            ?>
                        </p>
                    <?php endif; ?>
                </section>
            <?php else : ?>
                <section class="rewardx-nfc-ocg is-expired" aria-live="polite">
                    <h2><?php esc_html_e('Thời gian còn chơi OCG', 'woo-rewardx-lite'); ?></h2>
                    <p><?php esc_html_e('Chưa có dữ liệu về quyền OCG cho thành viên này.', 'woo-rewardx-lite'); ?></p>
                </section>
            <?php endif; ?>
        </section>
    <?php else : ?>
        <section class="rewardx-nfc-empty">
            <h1><?php esc_html_e('Liên kết không khả dụng', 'woo-rewardx-lite'); ?></h1>
            <p><?php echo esc_html($error_message ?: __('Không tìm thấy dữ liệu tương ứng.', 'woo-rewardx-lite')); ?></p>
        </section>
    <?php endif; ?>
</div>
<?php wp_footer(); ?>
</body>
</html>
