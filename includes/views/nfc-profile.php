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
        $name        = isset($profile['name']) ? (string) $profile['name'] : '';
        $ocg         = $profile['ocg_remaining'] ?? null;
        ?>
        <header class="rewardx-nfc-header">
            <h1><?php esc_html_e('Hồ sơ thành viên Peopo Loyalty', 'woo-rewardx-lite'); ?></h1>
            <p><?php esc_html_e('Thông tin tóm tắt được hiển thị khi quét thẻ NFC của khách hàng.', 'woo-rewardx-lite'); ?></p>
        </header>

        <section class="rewardx-account rewardx-nfc-account">
            <section class="rewardx-nfc-hero" aria-label="<?php esc_attr_e('Thông tin nhận diện thành viên', 'woo-rewardx-lite'); ?>">
                <article class="rewardx-nfc-identity">
                    <span class="rewardx-nfc-eyebrow"><?php esc_html_e('Thành viên Peopo Loyalty', 'woo-rewardx-lite'); ?></span>
                    <h2 class="rewardx-nfc-name">
                        <?php
                        echo esc_html(
                            '' !== $name
                                ? $name
                                : __('Khách hàng chưa cập nhật tên', 'woo-rewardx-lite')
                        );
                        ?>
                    </h2>
                    <?php if ('' !== $rank_name) : ?>
                        <span class="rewardx-nfc-rank-badge"><?php echo esc_html($rank_name); ?></span>
                    <?php endif; ?>
                    <?php if ($rank_no && $rank_total) : ?>
                        <span class="rewardx-nfc-rank-meta">
                            <?php
                            printf(
                                esc_html__('Thứ tự hiện tại: %1$d / %2$d thành viên', 'woo-rewardx-lite'),
                                $rank_no,
                                $rank_total
                            );
                            ?>
                        </span>
                    <?php endif; ?>
                </article>

                <article class="rewardx-nfc-hero-card" aria-live="polite">
                    <span class="rewardx-nfc-hero-label"><?php esc_html_e('Điểm tích lũy', 'woo-rewardx-lite'); ?></span>
                    <strong class="rewardx-nfc-hero-value"><?php echo esc_html(number_format_i18n($points)); ?></strong>
                    <span class="rewardx-nfc-hero-note"><?php esc_html_e('Được cập nhật theo thời gian thực từ hệ thống Peopo Loyalty.', 'woo-rewardx-lite'); ?></span>
                </article>
            </section>

            <section class="rewardx-summary" aria-label="<?php esc_attr_e('Thông tin tổng quan', 'woo-rewardx-lite'); ?>">
                <ul class="rewardx-summary-list">
                    <li>
                        <span class="rewardx-summary-label"><?php esc_html_e('Điểm hiện tại', 'woo-rewardx-lite'); ?></span>
                        <strong class="rewardx-summary-value rewardx-points"><?php echo esc_html(number_format_i18n($points)); ?></strong>
                        <small class="rewardx-summary-subtext"><?php esc_html_e('Tính cả điểm thưởng và điểm quy đổi.', 'woo-rewardx-lite'); ?></small>
                    </li>
                    <li>
                        <span class="rewardx-summary-label"><?php esc_html_e('Tổng chi tiêu', 'woo-rewardx-lite'); ?></span>
                        <strong class="rewardx-summary-value"><?php echo wp_kses_post($format_currency($total_spent)); ?></strong>
                        <small class="rewardx-summary-subtext"><?php esc_html_e('Lũy kế toàn bộ đơn hàng đã hoàn tất.', 'woo-rewardx-lite'); ?></small>
                    </li>
                    <li>
                        <span class="rewardx-summary-label"><?php esc_html_e('Tổng đơn hàng', 'woo-rewardx-lite'); ?></span>
                        <strong class="rewardx-summary-value"><?php echo esc_html(number_format_i18n($order_count)); ?></strong>
                        <small class="rewardx-summary-subtext"><?php esc_html_e('Bao gồm cả đơn hàng mua trực tiếp và trực tuyến.', 'woo-rewardx-lite'); ?></small>
                    </li>
                    <li>
                        <span class="rewardx-summary-label"><?php esc_html_e('Thứ hạng hiện tại', 'woo-rewardx-lite'); ?></span>
                        <strong class="rewardx-summary-value">
                            <?php echo esc_html($rank_name); ?>
                            <?php if ($rank_no && $rank_total) : ?>
                                <small class="rewardx-summary-subtext">
                                    <?php
                                    printf(
                                        esc_html__('Đang ở vị trí %1$d trong %2$d thành viên tích cực.', 'woo-rewardx-lite'),
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
