<?php
if (!defined('ABSPATH')) {
    exit;
}

$physical_rewards = $rewards['physical'] ?? [];
$voucher_rewards  = $rewards['voucher'] ?? [];
?>
<div class="rewardx-account">
    <div class="rewardx-balance">
        <h2><?php esc_html_e('Điểm của bạn', 'woo-rewardx-lite'); ?></h2>
        <p class="rewardx-points"><?php echo esc_html(number_format_i18n($points)); ?></p>
        <p class="rewardx-balance-hint"><?php esc_html_e('Đổi thưởng dễ dàng với những gợi ý được sắp xếp rõ ràng bên dưới.', 'woo-rewardx-lite'); ?></p>
    </div>

    <?php if (empty($physical_rewards) && empty($voucher_rewards)) : ?>
        <div class="rewardx-empty">
            <h3><?php esc_html_e('Hiện chưa có phần thưởng nào khả dụng.', 'woo-rewardx-lite'); ?></h3>
            <p><?php esc_html_e('Hãy quay lại sau hoặc tiếp tục tích điểm để nhận thêm ưu đãi hấp dẫn.', 'woo-rewardx-lite'); ?></p>
        </div>
    <?php endif; ?>

    <?php if (!empty($physical_rewards)) : ?>
        <section class="rewardx-section">
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
                    ?>
                    <article class="rewardx-card" data-reward-id="<?php echo esc_attr($item['id']); ?>" data-type="physical" data-cost="<?php echo esc_attr($item['cost']); ?>">
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
                                <span class="rewardx-card-note"><?php esc_html_e('Bạn cần thêm điểm để đổi quà này.', 'woo-rewardx-lite'); ?></span>
                            <?php elseif ($is_out_of_stock) : ?>
                                <span class="rewardx-card-note rewardx-card-note--danger"><?php esc_html_e('Phần thưởng tạm thời đã hết.', 'woo-rewardx-lite'); ?></span>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <?php if (!empty($voucher_rewards)) : ?>
        <section class="rewardx-section">
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
                    ?>
                    <article class="rewardx-card" data-reward-id="<?php echo esc_attr($item['id']); ?>" data-type="voucher" data-cost="<?php echo esc_attr($item['cost']); ?>">
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
                                <span class="rewardx-card-note"><?php esc_html_e('Bạn chưa đủ điểm để đổi voucher này.', 'woo-rewardx-lite'); ?></span>
                            <?php elseif ($is_out_of_stock) : ?>
                                <span class="rewardx-card-note rewardx-card-note--danger"><?php esc_html_e('Voucher đã được đổi hết.', 'woo-rewardx-lite'); ?></span>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
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
