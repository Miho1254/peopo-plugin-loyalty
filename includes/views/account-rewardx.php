<?php
if (!defined('ABSPATH')) {
    exit;
}

$physical_rewards   = $rewards['physical'] ?? [];
$voucher_rewards    = $rewards['voucher'] ?? [];
$has_physical       = !empty($physical_rewards);
$has_voucher        = !empty($voucher_rewards);
$default_tab        = $has_physical ? 'physical' : ($has_voucher ? 'voucher' : '');
$physical_tab_id    = $has_physical && $has_voucher ? 'rewardx-tab-physical' : '';
$voucher_tab_id     = $has_physical && $has_voucher ? 'rewardx-tab-voucher' : '';
$physical_role      = $has_physical && $has_voucher ? 'tabpanel' : 'region';
$voucher_role       = $has_physical && $has_voucher ? 'tabpanel' : 'region';
$all_rewards        = array_merge($physical_rewards, $voucher_rewards);
$total_rewards      = count($all_rewards);
$available_rewards  = 0;
$locked_rewards     = 0;
$out_of_stock       = 0;

foreach ($all_rewards as $reward_item) {
    $stock           = (int) ($reward_item['stock'] ?? 0);
    $is_unlimited    = $stock === -1;
    $is_out_of_stock = !$is_unlimited && $stock <= 0;
    if ($is_out_of_stock) {
        $out_of_stock++;
        continue;
    }

    if ($points >= (int) ($reward_item['cost'] ?? 0)) {
        $available_rewards++;
    } else {
        $locked_rewards++;
    }
}
?>
<div class="rewardx-account" data-current-points="<?php echo esc_attr($points); ?>">
    <header class="rewardx-balance rewardx-hero">
        <div class="rewardx-hero-summary">
            <p class="rewardx-hero-label"><?php esc_html_e('Điểm của bạn', 'woo-rewardx-lite'); ?></p>
            <p class="rewardx-points"><?php echo esc_html(number_format_i18n($points)); ?></p>
            <p class="rewardx-hero-hint"><?php esc_html_e('Đổi thưởng nhanh chóng với bố cục rõ ràng, dễ nhìn và tập trung vào điều quan trọng nhất.', 'woo-rewardx-lite'); ?></p>
        </div>
        <?php if ($total_rewards > 0) : ?>
            <ul class="rewardx-hero-stats" role="list">
                <li>
                    <span class="rewardx-stat-label"><?php esc_html_e('Tổng phần thưởng', 'woo-rewardx-lite'); ?></span>
                    <strong class="rewardx-stat-value"><?php echo esc_html(number_format_i18n($total_rewards)); ?></strong>
                </li>
                <li>
                    <span class="rewardx-stat-label"><?php esc_html_e('Khả dụng ngay', 'woo-rewardx-lite'); ?></span>
                    <strong class="rewardx-stat-value rewardx-stat-value--success"><?php echo esc_html(number_format_i18n($available_rewards)); ?></strong>
                </li>
                <li>
                    <span class="rewardx-stat-label"><?php esc_html_e('Chờ đủ điểm', 'woo-rewardx-lite'); ?></span>
                    <strong class="rewardx-stat-value rewardx-stat-value--muted"><?php echo esc_html(number_format_i18n($locked_rewards)); ?></strong>
                </li>
                <li>
                    <span class="rewardx-stat-label"><?php esc_html_e('Tạm hết', 'woo-rewardx-lite'); ?></span>
                    <strong class="rewardx-stat-value rewardx-stat-value--alert"><?php echo esc_html(number_format_i18n($out_of_stock)); ?></strong>
                </li>
            </ul>
        <?php endif; ?>
    </header>

    <?php if (!$has_physical && !$has_voucher) : ?>
        <div class="rewardx-empty">
            <h3><?php esc_html_e('Hiện chưa có phần thưởng nào khả dụng.', 'woo-rewardx-lite'); ?></h3>
            <p><?php esc_html_e('Hãy quay lại sau hoặc tiếp tục tích điểm để nhận thêm ưu đãi hấp dẫn.', 'woo-rewardx-lite'); ?></p>
        </div>
    <?php endif; ?>

    <?php if ($has_physical || $has_voucher) : ?>
        <div class="rewardx-toolbar">
            <?php if ($has_physical && $has_voucher) : ?>
                <div class="rewardx-tabs" role="tablist" aria-label="<?php esc_attr_e('Danh mục phần thưởng', 'woo-rewardx-lite'); ?>">
                    <button type="button" id="rewardx-tab-physical" class="rewardx-tab<?php echo $default_tab === 'physical' ? ' is-active' : ''; ?>" role="tab" aria-selected="<?php echo $default_tab === 'physical' ? 'true' : 'false'; ?>" aria-controls="rewardx-section-physical" data-target="physical">
                        <?php esc_html_e('Quà vật lý', 'woo-rewardx-lite'); ?>
                    </button>
                    <button type="button" id="rewardx-tab-voucher" class="rewardx-tab<?php echo $default_tab === 'voucher' ? ' is-active' : ''; ?>" role="tab" aria-selected="<?php echo $default_tab === 'voucher' ? 'true' : 'false'; ?>" aria-controls="rewardx-section-voucher" data-target="voucher">
                        <?php esc_html_e('Voucher', 'woo-rewardx-lite'); ?>
                    </button>
                </div>
            <?php else : ?>
                <div class="rewardx-tabs rewardx-tabs--single">
                    <span class="rewardx-tab is-active" aria-current="true">
                        <?php echo esc_html($has_physical ? __('Quà vật lý', 'woo-rewardx-lite') : __('Voucher', 'woo-rewardx-lite')); ?>
                    </span>
                </div>
            <?php endif; ?>

            <label for="rewardx-filter-available" class="rewardx-filter">
                <input type="checkbox" id="rewardx-filter-available" class="rewardx-filter-toggle" />
                <span class="rewardx-filter-switch" aria-hidden="true">
                    <span class="rewardx-filter-knob"></span>
                </span>
                <span class="rewardx-filter-text"><?php esc_html_e('Chỉ hiển thị phần thưởng đủ điểm', 'woo-rewardx-lite'); ?></span>
            </label>
        </div>
    <?php endif; ?>

    <?php if ($has_physical) : ?>
        <section id="rewardx-section-physical" class="rewardx-section<?php echo $default_tab !== 'physical' && $has_voucher ? ' rewardx-section--hidden' : ''; ?>" data-section="physical" role="<?php echo esc_attr($physical_role); ?>"<?php echo $physical_tab_id ? ' aria-labelledby="' . esc_attr($physical_tab_id) . '"' : ''; ?><?php echo $default_tab !== 'physical' && $has_voucher ? ' hidden' : ''; ?>>
            <div class="rewardx-section-header">
                <div>
                    <h3><?php esc_html_e('Quà vật lý', 'woo-rewardx-lite'); ?></h3>
                    <p><?php esc_html_e('Những sản phẩm hiện vật nổi bật được chọn lọc cho bạn.', 'woo-rewardx-lite'); ?></p>
                </div>
            </div>
            <div class="rewardx-grid">
                <?php foreach ($physical_rewards as $item) : ?>
                    <?php
                    $stock            = (int) $item['stock'];
                    $is_unlimited     = $stock === -1;
                    $is_out_of_stock  = !$is_unlimited && $stock <= 0;
                    $has_enough_point = $points >= (int) $item['cost'];
                    $is_disabled      = $is_out_of_stock || !$has_enough_point;
                    $status         = $is_out_of_stock ? 'out_of_stock' : ($has_enough_point ? 'available' : 'missing_points');
                    $has_points_tag = $has_enough_point ? 'yes' : 'no';
                    ?>
                    <article class="rewardx-card" data-reward-id="<?php echo esc_attr($item['id']); ?>" data-type="physical" data-cost="<?php echo esc_attr($item['cost']); ?>" data-state="<?php echo esc_attr($status); ?>" data-has-points="<?php echo esc_attr($has_points_tag); ?>">
                        <div class="rewardx-card-media<?php echo empty($item['thumbnail']) ? ' rewardx-card-media--empty' : ''; ?>">
                            <?php if (!empty($item['thumbnail'])) : ?>
                                <img src="<?php echo esc_url($item['thumbnail']); ?>" alt="<?php echo esc_attr($item['title']); ?>" />
                            <?php endif; ?>
                            <span class="rewardx-badge rewardx-badge-physical"><?php esc_html_e('Vật lý', 'woo-rewardx-lite'); ?></span>
                        </div>
                        <div class="rewardx-card-body">
                            <h4 class="rewardx-card-title"><?php echo esc_html($item['title']); ?></h4>
                            <p class="rewardx-card-description"><?php echo esc_html($item['excerpt']); ?></p>
                            <dl class="rewardx-card-meta">
                                <div class="rewardx-card-meta-item">
                                    <dt><?php esc_html_e('Chi phí', 'woo-rewardx-lite'); ?></dt>
                                    <dd><?php echo esc_html(number_format_i18n($item['cost'])); ?></dd>
                                </div>
                                <div class="rewardx-card-meta-item">
                                    <dt><?php esc_html_e('Tồn kho', 'woo-rewardx-lite'); ?></dt>
                                    <dd>
                                        <?php if ($is_unlimited) : ?>
                                            <span class="rewardx-chip rewardx-chip--soft"><?php esc_html_e('Không giới hạn', 'woo-rewardx-lite'); ?></span>
                                        <?php elseif ($is_out_of_stock) : ?>
                                            <span class="rewardx-chip rewardx-chip--danger"><?php esc_html_e('Hết hàng', 'woo-rewardx-lite'); ?></span>
                                        <?php else : ?>
                                            <span class="rewardx-chip"><?php echo esc_html(number_format_i18n($stock)); ?></span>
                                        <?php endif; ?>
                                    </dd>
                                </div>
                            </dl>
                        </div>
                        <div class="rewardx-card-footer">
                            <button class="button rewardx-redeem" data-action="physical" <?php disabled($is_disabled); ?>>
                                <?php esc_html_e('Đổi quà', 'woo-rewardx-lite'); ?>
                            </button>
                            <?php if ($is_disabled && !$is_out_of_stock) : ?>
                                <div class="rewardx-card-progress" aria-live="polite">
                                    <span class="rewardx-card-progress-label"><?php esc_html_e('Còn thiếu', 'woo-rewardx-lite'); ?></span>
                                    <strong class="rewardx-card-progress-value"><?php echo esc_html(number_format_i18n(max(0, (int) $item['cost'] - $points))); ?></strong>
                                    <span class="rewardx-card-progress-suffix"><?php esc_html_e('điểm', 'woo-rewardx-lite'); ?></span>
                                </div>
                                <span class="rewardx-card-note"><?php esc_html_e('Bạn cần thêm điểm để đổi quà này.', 'woo-rewardx-lite'); ?></span>
                            <?php elseif ($is_out_of_stock) : ?>
                                <span class="rewardx-card-note rewardx-card-note--danger"><?php esc_html_e('Phần thưởng tạm thời đã hết.', 'woo-rewardx-lite'); ?></span>
                            <?php else : ?>
                                <span class="rewardx-card-note rewardx-card-note--success"><?php esc_html_e('Bạn đã đủ điểm để đổi quà ngay.', 'woo-rewardx-lite'); ?></span>
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
        <section id="rewardx-section-voucher" class="rewardx-section<?php echo $default_tab !== 'voucher' && $has_physical ? ' rewardx-section--hidden' : ''; ?>" data-section="voucher" role="<?php echo esc_attr($voucher_role); ?>"<?php echo $voucher_tab_id ? ' aria-labelledby="' . esc_attr($voucher_tab_id) . '"' : ''; ?><?php echo $default_tab !== 'voucher' && $has_physical ? ' hidden' : ''; ?>>
            <div class="rewardx-section-header">
                <div>
                    <h3><?php esc_html_e('Voucher', 'woo-rewardx-lite'); ?></h3>
                    <p><?php esc_html_e('Tiết kiệm nhiều hơn với voucher giảm giá dành riêng cho bạn.', 'woo-rewardx-lite'); ?></p>
                </div>
            </div>
            <div class="rewardx-customer-insights">
                <div>
                    <span class="rewardx-insight-label"><?php esc_html_e('Tổng giá trị đơn hàng đã mua', 'woo-rewardx-lite'); ?></span>
                    <strong class="rewardx-insight-value"><?php echo wp_kses_post(function_exists('wc_price') ? wc_price($total_spent) : number_format_i18n($total_spent, 2)); ?></strong>
                </div>
                <div>
                    <span class="rewardx-insight-label"><?php esc_html_e('Số đơn hàng đã hoàn tất', 'woo-rewardx-lite'); ?></span>
                    <strong class="rewardx-insight-value"><?php echo esc_html(number_format_i18n($order_count)); ?></strong>
                </div>
            </div>
            <div class="rewardx-grid">
                <?php foreach ($voucher_rewards as $item) : ?>
                    <?php
                    $stock            = (int) $item['stock'];
                    $is_unlimited     = $stock === -1;
                    $is_out_of_stock  = !$is_unlimited && $stock <= 0;
                    $has_enough_point = $points >= (int) $item['cost'];
                    $is_disabled      = $is_out_of_stock || !$has_enough_point;
                    $status         = $is_out_of_stock ? 'out_of_stock' : ($has_enough_point ? 'available' : 'missing_points');
                    $has_points_tag = $has_enough_point ? 'yes' : 'no';
                    ?>
                    <article class="rewardx-card" data-reward-id="<?php echo esc_attr($item['id']); ?>" data-type="voucher" data-cost="<?php echo esc_attr($item['cost']); ?>" data-state="<?php echo esc_attr($status); ?>" data-has-points="<?php echo esc_attr($has_points_tag); ?>">
                        <div class="rewardx-card-media<?php echo empty($item['thumbnail']) ? ' rewardx-card-media--empty' : ''; ?>">
                            <?php if (!empty($item['thumbnail'])) : ?>
                                <img src="<?php echo esc_url($item['thumbnail']); ?>" alt="<?php echo esc_attr($item['title']); ?>" />
                            <?php endif; ?>
                            <span class="rewardx-badge rewardx-badge-voucher"><?php esc_html_e('Voucher', 'woo-rewardx-lite'); ?></span>
                        </div>
                        <div class="rewardx-card-body">
                            <h4 class="rewardx-card-title"><?php echo esc_html($item['title']); ?></h4>
                            <p class="rewardx-card-description"><?php echo esc_html($item['excerpt']); ?></p>
                            <dl class="rewardx-card-meta">
                                <div class="rewardx-card-meta-item">
                                    <dt><?php esc_html_e('Chi phí', 'woo-rewardx-lite'); ?></dt>
                                    <dd><?php echo esc_html(number_format_i18n($item['cost'])); ?></dd>
                                </div>
                                <div class="rewardx-card-meta-item">
                                    <dt><?php esc_html_e('Số lượng', 'woo-rewardx-lite'); ?></dt>
                                    <dd>
                                        <?php if ($is_unlimited) : ?>
                                            <span class="rewardx-chip rewardx-chip--soft"><?php esc_html_e('Không giới hạn', 'woo-rewardx-lite'); ?></span>
                                        <?php elseif ($is_out_of_stock) : ?>
                                            <span class="rewardx-chip rewardx-chip--danger"><?php esc_html_e('Đã hết', 'woo-rewardx-lite'); ?></span>
                                        <?php else : ?>
                                            <span class="rewardx-chip"><?php echo esc_html(number_format_i18n($stock)); ?></span>
                                        <?php endif; ?>
                                    </dd>
                                </div>
                                <?php if ($item['amount'] > 0) : ?>
                                    <div class="rewardx-card-meta-item">
                                        <dt><?php esc_html_e('Trị giá', 'woo-rewardx-lite'); ?></dt>
                                        <dd class="rewardx-card-highlight"><?php echo wp_kses_post(function_exists('wc_price') ? wc_price($item['amount']) : number_format_i18n($item['amount'], 0)); ?></dd>
                                    </div>
                                <?php endif; ?>
                            </dl>
                        </div>
                        <div class="rewardx-card-footer">
                            <button class="button rewardx-redeem" data-action="voucher" <?php disabled($is_disabled); ?>>
                                <?php esc_html_e('Đổi voucher', 'woo-rewardx-lite'); ?>
                            </button>
                            <?php if ($is_disabled && !$is_out_of_stock) : ?>
                                <div class="rewardx-card-progress" aria-live="polite">
                                    <span class="rewardx-card-progress-label"><?php esc_html_e('Còn thiếu', 'woo-rewardx-lite'); ?></span>
                                    <strong class="rewardx-card-progress-value"><?php echo esc_html(number_format_i18n(max(0, (int) $item['cost'] - $points))); ?></strong>
                                    <span class="rewardx-card-progress-suffix"><?php esc_html_e('điểm', 'woo-rewardx-lite'); ?></span>
                                </div>
                                <span class="rewardx-card-note"><?php esc_html_e('Bạn chưa đủ điểm để đổi voucher này.', 'woo-rewardx-lite'); ?></span>
                            <?php elseif ($is_out_of_stock) : ?>
                                <span class="rewardx-card-note rewardx-card-note--danger"><?php esc_html_e('Voucher đã được đổi hết.', 'woo-rewardx-lite'); ?></span>
                            <?php else : ?>
                                <span class="rewardx-card-note rewardx-card-note--success"><?php esc_html_e('Voucher sẵn sàng để bạn đổi.', 'woo-rewardx-lite'); ?></span>
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

    <section class="rewardx-section">
        <div class="rewardx-section-header">
            <div>
                <h3><?php esc_html_e('Lịch sử giao dịch gần đây', 'woo-rewardx-lite'); ?></h3>
                <p><?php esc_html_e('Theo dõi những lần cộng trừ điểm một cách rõ ràng, trực quan.', 'woo-rewardx-lite'); ?></p>
            </div>
        </div>
        <ul class="rewardx-ledger">
            <li class="rewardx-ledger-head">
                <span><?php esc_html_e('Thời gian', 'woo-rewardx-lite'); ?></span>
                <span><?php esc_html_e('Nội dung', 'woo-rewardx-lite'); ?></span>
                <span><?php esc_html_e('Điểm', 'woo-rewardx-lite'); ?></span>
                <span><?php esc_html_e('Số dư', 'woo-rewardx-lite'); ?></span>
            </li>
            <?php if (!empty($ledger)) : ?>
                <?php foreach ($ledger as $item) : ?>
                    <li class="rewardx-ledger-row">
                        <span class="rewardx-ledger-date" data-title="<?php esc_attr_e('Thời gian', 'woo-rewardx-lite'); ?>">
                            <?php echo esc_html(date_i18n(get_option('date_format'), $item['timestamp'] ?: time())); ?>
                        </span>
                        <span class="rewardx-ledger-reason" data-title="<?php esc_attr_e('Nội dung', 'woo-rewardx-lite'); ?>">
                            <?php echo esc_html($item['reason'] ?: $item['title']); ?>
                        </span>
                        <span class="rewardx-ledger-delta <?php echo $item['delta'] >= 0 ? 'positive' : 'negative'; ?>" data-title="<?php esc_attr_e('Điểm', 'woo-rewardx-lite'); ?>">
                            <?php echo esc_html($item['delta']); ?>
                        </span>
                        <span class="rewardx-ledger-balance" data-title="<?php esc_attr_e('Số dư', 'woo-rewardx-lite'); ?>">
                            <?php echo esc_html($item['balance_after']); ?>
                        </span>
                    </li>
                <?php endforeach; ?>
            <?php else : ?>
                <li class="rewardx-ledger-empty">
                    <span><?php esc_html_e('Chưa có giao dịch nào.', 'woo-rewardx-lite'); ?></span>
                </li>
            <?php endif; ?>
        </ul>
    </section>

    <div id="rewardx-modal" class="rewardx-modal" aria-hidden="true">
        <div class="rewardx-modal-content">
            <button type="button" class="rewardx-modal-close" aria-label="<?php esc_attr_e('Đóng', 'woo-rewardx-lite'); ?>">×</button>
            <div class="rewardx-modal-body"></div>
        </div>
    </div>
</div>
