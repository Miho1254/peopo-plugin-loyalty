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
    </div>

    <?php if (!empty($physical_rewards)) : ?>
        <section class="rewardx-section">
            <h3><?php esc_html_e('Quà vật lý', 'woo-rewardx-lite'); ?></h3>
            <div class="rewardx-grid">
                <?php foreach ($physical_rewards as $item) : ?>
                    <article class="rewardx-card" data-reward-id="<?php echo esc_attr($item['id']); ?>" data-type="physical" data-cost="<?php echo esc_attr($item['cost']); ?>">
                        <?php if (!empty($item['thumbnail'])) : ?>
                            <img src="<?php echo esc_url($item['thumbnail']); ?>" alt="<?php echo esc_attr($item['title']); ?>" />
                        <?php endif; ?>
                        <span class="rewardx-badge rewardx-badge-physical"><?php esc_html_e('Vật lý', 'woo-rewardx-lite'); ?></span>
                        <h4><?php echo esc_html($item['title']); ?></h4>
                        <p><?php echo esc_html($item['excerpt']); ?></p>
                        <div class="rewardx-meta">
                            <span class="rewardx-cost"><?php printf(esc_html__('Chi phí: %s điểm', 'woo-rewardx-lite'), esc_html(number_format_i18n($item['cost']))); ?></span>
                            <?php if ($item['stock'] !== -1) : ?>
                                <span class="rewardx-stock"><?php printf(esc_html__('Còn: %s', 'woo-rewardx-lite'), esc_html($item['stock'])); ?></span>
                            <?php else : ?>
                                <span class="rewardx-stock unlimited"><?php esc_html_e('Không giới hạn', 'woo-rewardx-lite'); ?></span>
                            <?php endif; ?>
                        </div>
                        <button class="button rewardx-redeem" data-action="physical"><?php esc_html_e('Đổi quà', 'woo-rewardx-lite'); ?></button>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <?php if (!empty($voucher_rewards)) : ?>
        <section class="rewardx-section">
            <h3><?php esc_html_e('Voucher', 'woo-rewardx-lite'); ?></h3>
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
                    <article class="rewardx-card" data-reward-id="<?php echo esc_attr($item['id']); ?>" data-type="voucher" data-cost="<?php echo esc_attr($item['cost']); ?>">
                        <?php if (!empty($item['thumbnail'])) : ?>
                            <img src="<?php echo esc_url($item['thumbnail']); ?>" alt="<?php echo esc_attr($item['title']); ?>" />
                        <?php endif; ?>
                        <span class="rewardx-badge rewardx-badge-voucher"><?php esc_html_e('Voucher', 'woo-rewardx-lite'); ?></span>
                        <h4><?php echo esc_html($item['title']); ?></h4>
                        <p><?php echo esc_html($item['excerpt']); ?></p>
                        <div class="rewardx-meta">
                            <span class="rewardx-cost"><?php printf(esc_html__('Chi phí: %s điểm', 'woo-rewardx-lite'), esc_html(number_format_i18n($item['cost']))); ?></span>
                            <?php if ($item['stock'] !== -1) : ?>
                                <span class="rewardx-stock"><?php printf(esc_html__('Còn: %s', 'woo-rewardx-lite'), esc_html($item['stock'])); ?></span>
                            <?php else : ?>
                                <span class="rewardx-stock unlimited"><?php esc_html_e('Không giới hạn', 'woo-rewardx-lite'); ?></span>
                            <?php endif; ?>
                            <?php if ($item['amount'] > 0) : ?>
                                <span class="rewardx-amount"><?php printf(esc_html__('Trị giá: %s', 'woo-rewardx-lite'), wp_kses_post(wc_price($item['amount']))); ?></span>
                            <?php endif; ?>
                        </div>
                        <button class="button rewardx-redeem" data-action="voucher"><?php esc_html_e('Đổi voucher', 'woo-rewardx-lite'); ?></button>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <section class="rewardx-section">
        <h3><?php esc_html_e('Lịch sử giao dịch gần đây', 'woo-rewardx-lite'); ?></h3>
        <ul class="rewardx-ledger">
            <?php if (!empty($ledger)) : ?>
                <?php foreach ($ledger as $item) : ?>
                    <li>
                        <span class="rewardx-ledger-date"><?php echo esc_html(date_i18n(get_option('date_format'), $item['timestamp'] ?: time())); ?></span>
                        <span class="rewardx-ledger-reason"><?php echo esc_html($item['reason'] ?: $item['title']); ?></span>
                        <span class="rewardx-ledger-delta <?php echo $item['delta'] >= 0 ? 'positive' : 'negative'; ?>"><?php echo esc_html($item['delta']); ?></span>
                        <span class="rewardx-ledger-balance"><?php echo esc_html($item['balance_after']); ?></span>
                    </li>
                <?php endforeach; ?>
            <?php else : ?>
                <li><?php esc_html_e('Chưa có giao dịch nào.', 'woo-rewardx-lite'); ?></li>
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
