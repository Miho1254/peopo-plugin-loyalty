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
        </ul>
    </section>

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
