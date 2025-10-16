<?php
if (!defined('ABSPATH')) {
    exit;
}

$rank_list       = isset($rank_list) && is_array($rank_list) ? $rank_list : [];
$current_rank    = $current_rank ?? null;
$next_rank       = $next_rank ?? null;
$rank_progress   = isset($rank_progress) ? max(0.0, min(1.0, (float) $rank_progress)) : 0.0;
$amount_to_next  = isset($amount_to_next) ? max(0.0, (float) $amount_to_next) : 0.0;
$rank_progress_percent = (int) round($rank_progress * 100);
$rank_progress_style   = number_format($rank_progress * 100, 2, '.', '');
$format_currency = static function (float $amount): string {
    if (function_exists('wc_price')) {
        return (string) wc_price($amount);
    }

    return number_format_i18n($amount, 0);
};

$physical_rewards   = $rewards['physical'] ?? [];
$voucher_rewards    = $rewards['voucher'] ?? [];
$has_physical       = !empty($physical_rewards);
$has_voucher        = !empty($voucher_rewards);
$default_tab        = $has_physical ? 'physical' : ($has_voucher ? 'voucher' : '');
$physical_tab_id    = $has_physical && $has_voucher ? 'rewardx-tab-physical' : '';
$voucher_tab_id     = $has_physical && $has_voucher ? 'rewardx-tab-voucher' : '';
$physical_role      = $has_physical && $has_voucher ? 'tabpanel' : 'region';
$voucher_role       = $has_physical && $has_voucher ? 'tabpanel' : 'region';
?>
<div class="rewardx-account" data-current-points="<?php echo esc_attr($points); ?>">
    <header class="rewardx-intro">
        <p class="rewardx-eyebrow"><?php esc_html_e('Chào mừng bạn trở lại', 'woo-rewardx-lite'); ?></p>
        <h2><?php esc_html_e('Trung tâm điểm thưởng', 'woo-rewardx-lite'); ?></h2>
        <p><?php esc_html_e('Theo dõi điểm, chi tiêu và chọn phần thưởng phù hợp một cách nhanh chóng.', 'woo-rewardx-lite'); ?></p>
    </header>

    <section class="rewardx-summary" aria-label="<?php esc_attr_e('Tổng quan nhanh', 'woo-rewardx-lite'); ?>">
        <ul class="rewardx-summary-list">
            <li>
                <span class="rewardx-summary-label"><?php esc_html_e('Điểm hiện có', 'woo-rewardx-lite'); ?></span>
                <strong class="rewardx-summary-value rewardx-points"><?php echo esc_html(number_format_i18n($points)); ?></strong>
            </li>
            <li>
                <span class="rewardx-summary-label"><?php esc_html_e('Tổng giá trị mua hàng', 'woo-rewardx-lite'); ?></span>
                <strong class="rewardx-summary-value">
                    <?php echo wp_kses_post(function_exists('wc_price') ? wc_price($total_spent) : number_format_i18n($total_spent, 0)); ?>
                </strong>
            </li>
            <?php if (!empty($rank_list)) : ?>
                <li class="rewardx-summary-rank">
                    <span class="rewardx-summary-label"><?php esc_html_e('Thứ hạng hiện tại', 'woo-rewardx-lite'); ?></span>
                    <strong class="rewardx-summary-value">
                        <?php echo esc_html($current_rank['name'] ?? __('Chưa xếp hạng', 'woo-rewardx-lite')); ?>
                    </strong>
                    <p class="rewardx-summary-note">
                        <?php if ($next_rank) : ?>
                            <?php
                            printf(
                                wp_kses(
                                    /* translators: 1: formatted amount, 2: rank name */
                                    __('Cần thêm <span class="rewardx-summary-note__amount">%1$s</span> để đạt hạng <strong>%2$s</strong>.', 'woo-rewardx-lite'),
                                    [
                                        'span'   => ['class' => true],
                                        'strong' => [],
                                    ]
                                ),
                                wp_kses_post($format_currency($amount_to_next)),
                                esc_html($next_rank['name'])
                            );
                            ?>
                        <?php else : ?>
                            <?php esc_html_e('Bạn đang ở hạng cao nhất.', 'woo-rewardx-lite'); ?>
                        <?php endif; ?>
                    </p>
                </li>
            <?php endif; ?>
        </ul>
    </section>

    <?php if (!empty($rank_list)) : ?>
        <section class="rewardx-section rewardx-section--rank" aria-label="<?php esc_attr_e('Thứ hạng khách hàng', 'woo-rewardx-lite'); ?>">
            <header class="rewardx-rank-header">
                <h3><?php esc_html_e('Thứ hạng thành viên', 'woo-rewardx-lite'); ?></h3>
                <p><?php esc_html_e('Chi tiêu càng nhiều, thứ hạng càng cao và có thêm ưu đãi dành riêng cho bạn.', 'woo-rewardx-lite'); ?></p>
            </header>
            <div class="rewardx-rank-status">
                <div class="rewardx-rank-status__label"><?php esc_html_e('Hạng hiện tại', 'woo-rewardx-lite'); ?></div>
                <div class="rewardx-rank-status__value">
                    <?php echo esc_html($current_rank['name'] ?? __('Chưa xếp hạng', 'woo-rewardx-lite')); ?>
                </div>
            </div>
            <div class="rewardx-rank-progress" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?php echo esc_attr($rank_progress_percent); ?>">
                <span class="rewardx-rank-progress__bar" style="--rewardx-rank-progress: <?php echo esc_attr($rank_progress_style); ?>;"></span>
            </div>
            <p class="rewardx-rank-message">
                <?php if ($next_rank) : ?>
                    <?php
                    printf(
                        wp_kses(
                            /* translators: 1: formatted amount, 2: rank name */
                            __('Bạn cần tích lũy thêm <strong>%1$s</strong> để đạt hạng <strong>%2$s</strong>.', 'woo-rewardx-lite'),
                            [
                                'strong' => [],
                            ]
                        ),
                        wp_kses_post($format_currency($amount_to_next)),
                        esc_html($next_rank['name'])
                    );
                    ?>
                <?php else : ?>
                    <?php esc_html_e('Chúc mừng! Bạn đang ở hạng cao nhất.', 'woo-rewardx-lite'); ?>
                <?php endif; ?>
            </p>
            <ul class="rewardx-rank-list">
                <?php foreach ($rank_list as $rank_item) :
                    $is_active = $current_rank && $current_rank['name'] === $rank_item['name'] && abs(($current_rank['threshold'] ?? 0.0) - (float) $rank_item['threshold']) < 0.01;
                    ?>
                    <li class="rewardx-rank-list__item<?php echo $is_active ? ' is-active' : ''; ?>">
                        <span class="rewardx-rank-list__name"><?php echo esc_html($rank_item['name']); ?></span>
                        <span class="rewardx-rank-list__threshold"><?php echo wp_kses_post($format_currency((float) $rank_item['threshold'])); ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php endif; ?>

    <section class="rewardx-section rewardx-section--redeem">
        <header>
            <h3><?php esc_html_e('Phần thưởng có thể đổi', 'woo-rewardx-lite'); ?></h3>
        </header>

        <?php if (!$has_physical && !$has_voucher) : ?>
            <p><?php esc_html_e('Hiện chưa có phần thưởng nào khả dụng. Hãy quay lại sau hoặc tích thêm điểm.', 'woo-rewardx-lite'); ?></p>
        <?php endif; ?>

        <?php if ($has_physical) : ?>
            <section id="rewardx-section-physical" class="rewardx-section-body" data-section="physical" role="<?php echo esc_attr($physical_role); ?>">
                <header class="rewardx-subsection-header">
                    <h4><?php esc_html_e('Quà vật lý', 'woo-rewardx-lite'); ?></h4>
                </header>
                <div class="rewardx-card-grid">
                    <?php foreach ($physical_rewards as $item) : ?>
                        <?php
                    $stock            = (int) $item['stock'];
                    $is_unlimited     = $stock === -1;
                    $is_out_of_stock  = !$is_unlimited && $stock <= 0;
                    $has_enough_point = $points >= (int) $item['cost'];
                    $is_disabled      = $is_out_of_stock || !$has_enough_point;
                    $status           = $is_out_of_stock ? 'out_of_stock' : ($has_enough_point ? 'available' : 'missing_points');
                    $has_points_tag   = $has_enough_point ? 'yes' : 'no';
                    ?>
                        <article class="rewardx-card" data-reward-id="<?php echo esc_attr($item['id']); ?>" data-type="physical" data-cost="<?php echo esc_attr($item['cost']); ?>" data-state="<?php echo esc_attr($status); ?>" data-has-points="<?php echo esc_attr($has_points_tag); ?>">
                            <header class="rewardx-card-header">
                                <h5 class="rewardx-card-title"><?php echo esc_html($item['title']); ?></h5>
                                <?php if (!empty($item['excerpt'])) : ?>
                                    <p class="rewardx-card-description"><?php echo esc_html($item['excerpt']); ?></p>
                                <?php endif; ?>
                            </header>
                            <dl class="rewardx-card-meta">
                                <div>
                                    <dt><?php esc_html_e('Chi phí', 'woo-rewardx-lite'); ?></dt>
                                    <dd><?php echo esc_html(number_format_i18n($item['cost'])); ?></dd>
                                </div>
                                <div>
                                    <dt><?php esc_html_e('Tồn kho', 'woo-rewardx-lite'); ?></dt>
                                    <dd>
                                        <?php if ($is_unlimited) : ?>
                                            <?php esc_html_e('Không giới hạn', 'woo-rewardx-lite'); ?>
                                        <?php elseif ($is_out_of_stock) : ?>
                                            <?php esc_html_e('Hết hàng', 'woo-rewardx-lite'); ?>
                                        <?php else : ?>
                                            <?php echo esc_html(number_format_i18n($stock)); ?>
                                        <?php endif; ?>
                                    </dd>
                                </div>
                            </dl>
                            <div class="rewardx-card-footer">
                                <button class="button rewardx-redeem" data-action="physical" <?php disabled($is_disabled); ?>>
                                    <?php esc_html_e('Đổi quà', 'woo-rewardx-lite'); ?>
                                </button>
                                <?php if ($is_disabled && !$is_out_of_stock) : ?>
                                    <p class="rewardx-card-note"><?php esc_html_e('Bạn cần thêm điểm để đổi quà này.', 'woo-rewardx-lite'); ?>
                                        (<?php esc_html_e('Còn thiếu', 'woo-rewardx-lite'); ?>
                                        <?php echo esc_html(number_format_i18n(max(0, (int) $item['cost'] - $points))); ?>
                                        <?php esc_html_e('điểm', 'woo-rewardx-lite'); ?>)
                                    </p>
                                <?php elseif ($is_out_of_stock) : ?>
                                    <p class="rewardx-card-note"><?php esc_html_e('Phần thưởng tạm thời đã hết.', 'woo-rewardx-lite'); ?></p>
                                <?php else : ?>
                                    <p class="rewardx-card-note"><?php esc_html_e('Bạn đã đủ điểm để đổi quà ngay.', 'woo-rewardx-lite'); ?></p>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
                <p class="rewardx-empty-filter" aria-live="polite" hidden>
                    <?php esc_html_e('Không tìm thấy phần thưởng phù hợp với bộ lọc hiện tại.', 'woo-rewardx-lite'); ?>
                </p>
            </section>
        <?php endif; ?>

        <?php if ($has_voucher) : ?>
            <section id="rewardx-section-voucher" class="rewardx-section-body" data-section="voucher" role="<?php echo esc_attr($voucher_role); ?>">
                <header class="rewardx-subsection-header">
                    <h4><?php esc_html_e('Voucher', 'woo-rewardx-lite'); ?></h4>
                </header>
                <div class="rewardx-card-grid">
                    <?php foreach ($voucher_rewards as $item) : ?>
                        <?php
                    $stock            = (int) $item['stock'];
                    $is_unlimited     = $stock === -1;
                    $is_out_of_stock  = !$is_unlimited && $stock <= 0;
                    $has_enough_point = $points >= (int) $item['cost'];
                    $is_disabled      = $is_out_of_stock || !$has_enough_point;
                    $status           = $is_out_of_stock ? 'out_of_stock' : ($has_enough_point ? 'available' : 'missing_points');
                    $has_points_tag   = $has_enough_point ? 'yes' : 'no';
                    ?>
                        <article class="rewardx-card" data-reward-id="<?php echo esc_attr($item['id']); ?>" data-type="voucher" data-cost="<?php echo esc_attr($item['cost']); ?>" data-state="<?php echo esc_attr($status); ?>" data-has-points="<?php echo esc_attr($has_points_tag); ?>">
                            <header class="rewardx-card-header">
                                <h5 class="rewardx-card-title"><?php echo esc_html($item['title']); ?></h5>
                                <?php if (!empty($item['excerpt'])) : ?>
                                    <p class="rewardx-card-description"><?php echo esc_html($item['excerpt']); ?></p>
                                <?php endif; ?>
                            </header>
                            <dl class="rewardx-card-meta">
                                <div>
                                    <dt><?php esc_html_e('Chi phí', 'woo-rewardx-lite'); ?></dt>
                                    <dd><?php echo esc_html(number_format_i18n($item['cost'])); ?></dd>
                                </div>
                                <div>
                                    <dt><?php esc_html_e('Số lượng', 'woo-rewardx-lite'); ?></dt>
                                    <dd>
                                        <?php if ($is_unlimited) : ?>
                                            <?php esc_html_e('Không giới hạn', 'woo-rewardx-lite'); ?>
                                        <?php elseif ($is_out_of_stock) : ?>
                                            <?php esc_html_e('Đã hết', 'woo-rewardx-lite'); ?>
                                        <?php else : ?>
                                            <?php echo esc_html(number_format_i18n($stock)); ?>
                                        <?php endif; ?>
                                    </dd>
                                </div>
                                <?php if (!empty($item['amount'])) : ?>
                                    <div>
                                        <dt><?php esc_html_e('Trị giá', 'woo-rewardx-lite'); ?></dt>
                                        <dd><?php echo wp_kses_post(function_exists('wc_price') ? wc_price($item['amount']) : number_format_i18n($item['amount'], 0)); ?></dd>
                                    </div>
                                <?php endif; ?>
                            </dl>
                            <div class="rewardx-card-footer">
                                <button class="button rewardx-redeem" data-action="voucher" <?php disabled($is_disabled); ?>>
                                    <?php esc_html_e('Đổi voucher', 'woo-rewardx-lite'); ?>
                                </button>
                                <?php if ($is_disabled && !$is_out_of_stock) : ?>
                                    <p class="rewardx-card-note"><?php esc_html_e('Bạn chưa đủ điểm để đổi voucher này.', 'woo-rewardx-lite'); ?>
                                        (<?php esc_html_e('Còn thiếu', 'woo-rewardx-lite'); ?>
                                        <?php echo esc_html(number_format_i18n(max(0, (int) $item['cost'] - $points))); ?>
                                        <?php esc_html_e('điểm', 'woo-rewardx-lite'); ?>)
                                    </p>
                                <?php elseif ($is_out_of_stock) : ?>
                                    <p class="rewardx-card-note"><?php esc_html_e('Voucher đã được đổi hết.', 'woo-rewardx-lite'); ?></p>
                                <?php else : ?>
                                    <p class="rewardx-card-note"><?php esc_html_e('Voucher sẵn sàng để bạn đổi.', 'woo-rewardx-lite'); ?></p>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
                <p class="rewardx-empty-filter" aria-live="polite" hidden>
                    <?php esc_html_e('Không tìm thấy phần thưởng phù hợp với bộ lọc hiện tại.', 'woo-rewardx-lite'); ?>
                </p>
            </section>
        <?php endif; ?>
    </section>

    <section class="rewardx-section rewardx-section--history">
        <h3><?php esc_html_e('Lịch sử giao dịch gần đây', 'woo-rewardx-lite'); ?></h3>
        <?php if (!empty($ledger)) : ?>
            <table class="rewardx-ledger" role="table">
                <thead>
                    <tr>
                        <th scope="col"><?php esc_html_e('Thời gian', 'woo-rewardx-lite'); ?></th>
                        <th scope="col"><?php esc_html_e('Nội dung', 'woo-rewardx-lite'); ?></th>
                        <th scope="col"><?php esc_html_e('Điểm', 'woo-rewardx-lite'); ?></th>
                        <th scope="col"><?php esc_html_e('Số dư', 'woo-rewardx-lite'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ledger as $item) : ?>
                        <tr>
                            <td><?php echo esc_html(date_i18n(get_option('date_format'), $item['timestamp'] ?: time())); ?></td>
                            <td><?php echo esc_html($item['reason'] ?: $item['title']); ?></td>
                            <td class="rewardx-ledger-delta <?php echo $item['delta'] >= 0 ? 'positive' : 'negative'; ?>"><?php echo esc_html($item['delta']); ?></td>
                            <td><?php echo esc_html($item['balance_after']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else : ?>
            <p><?php esc_html_e('Chưa có giao dịch nào.', 'woo-rewardx-lite'); ?></p>
        <?php endif; ?>
    </section>

    <div id="rewardx-modal" class="rewardx-modal" aria-hidden="true">
        <div class="rewardx-modal-content">
            <button type="button" class="rewardx-modal-close" aria-label="<?php esc_attr_e('Đóng', 'woo-rewardx-lite'); ?>">×</button>
            <div class="rewardx-modal-body"></div>
        </div>
    </div>
</div>
