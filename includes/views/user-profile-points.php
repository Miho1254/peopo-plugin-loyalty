<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<h2><?php esc_html_e('RewardX Points', 'woo-rewardx-lite'); ?></h2>
<table class="form-table">
    <tr>
        <th><label for="rewardx_points"><?php esc_html_e('Điểm hiện tại', 'woo-rewardx-lite'); ?></label></th>
        <td>
            <input type="number" name="rewardx_points" id="rewardx_points" value="<?php echo esc_attr($points); ?>" class="small-text" />
            <?php wp_nonce_field('rewardx_points_update', 'rewardx_points_nonce'); ?>
        </td>
    </tr>
</table>

<h3><?php esc_html_e('Cộng / Trừ điểm nhanh', 'woo-rewardx-lite'); ?></h3>
<div class="rewardx-adjust-points" data-user-id="<?php echo esc_attr($user->ID); ?>">
    <input type="number" class="rewardx-delta" placeholder="<?php esc_attr_e('Số điểm (+/-)', 'woo-rewardx-lite'); ?>" />
    <input type="text" class="rewardx-reason" placeholder="<?php esc_attr_e('Lý do', 'woo-rewardx-lite'); ?>" />
    <button type="button" class="button rewardx-adjust-button"><?php esc_html_e('Thực hiện', 'woo-rewardx-lite'); ?></button>
    <p class="description"><?php esc_html_e('Nhập lý do để ghi lại lịch sử giao dịch.', 'woo-rewardx-lite'); ?></p>
</div>

<h3><?php esc_html_e('Lịch sử gần đây', 'woo-rewardx-lite'); ?></h3>
<table class="widefat fixed striped">
    <thead>
        <tr>
            <th><?php esc_html_e('Thời gian', 'woo-rewardx-lite'); ?></th>
            <th><?php esc_html_e('Giao dịch', 'woo-rewardx-lite'); ?></th>
            <th><?php esc_html_e('Điểm', 'woo-rewardx-lite'); ?></th>
            <th><?php esc_html_e('Số dư sau', 'woo-rewardx-lite'); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php if (!empty($ledger)) : ?>
            <?php foreach ($ledger as $item) : ?>
                <tr>
                    <td><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $item['timestamp'] ?: time())); ?></td>
                    <td><?php echo esc_html($item['reason'] ?: $item['title']); ?></td>
                    <td><?php echo esc_html($item['delta']); ?></td>
                    <td><?php echo esc_html($item['balance_after']); ?></td>
                </tr>
            <?php endforeach; ?>
        <?php else : ?>
            <tr>
                <td colspan="4"><?php esc_html_e('Chưa có giao dịch nào.', 'woo-rewardx-lite'); ?></td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>
