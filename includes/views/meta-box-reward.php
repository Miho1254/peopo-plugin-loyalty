<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<table class="form-table rewardx-meta-table">
    <tr>
        <th><label for="rewardx_cost_points"><?php esc_html_e('Điểm cần để đổi', 'woo-rewardx-lite'); ?></label></th>
        <td>
            <input type="number" min="0" name="rewardx_cost_points" id="rewardx_cost_points" value="<?php echo esc_attr($fields['cost']); ?>" class="small-text" />
        </td>
    </tr>
    <tr class="rewardx-field-voucher">
        <th><label for="rewardx_coupon_amount"><?php esc_html_e('Giá trị voucher (VNĐ)', 'woo-rewardx-lite'); ?></label></th>
        <td>
            <input type="number" step="0.01" min="0" name="rewardx_coupon_amount" id="rewardx_coupon_amount" value="<?php echo esc_attr($fields['amount']); ?>" class="regular-text" />
            <p class="description"><?php esc_html_e('Bắt buộc với phần thưởng voucher.', 'woo-rewardx-lite'); ?></p>
        </td>
    </tr>
    <tr>
        <th><label for="rewardx_expiry_days"><?php esc_html_e('Số ngày hết hạn', 'woo-rewardx-lite'); ?></label></th>
        <td>
            <input type="number" min="1" name="rewardx_expiry_days" id="rewardx_expiry_days" value="<?php echo esc_attr($fields['expiry']); ?>" class="small-text" />
            <p class="description"><?php esc_html_e('Áp dụng cho voucher. Để trống sẽ dùng giá trị mặc định.', 'woo-rewardx-lite'); ?></p>
        </td>
    </tr>
    <tr>
        <th><label for="rewardx_stock"><?php esc_html_e('Tồn kho', 'woo-rewardx-lite'); ?></label></th>
        <td>
            <input type="number" name="rewardx_stock" id="rewardx_stock" value="<?php echo esc_attr($fields['stock']); ?>" />
            <p class="description"><?php esc_html_e('-1 nghĩa là không giới hạn.', 'woo-rewardx-lite'); ?></p>
        </td>
    </tr>
    <tr class="rewardx-field-physical">
        <th><label for="rewardx_sku"><?php esc_html_e('SKU (tuỳ chọn)', 'woo-rewardx-lite'); ?></label></th>
        <td>
            <input type="text" name="rewardx_sku" id="rewardx_sku" value="<?php echo esc_attr($fields['sku']); ?>" class="regular-text" />
        </td>
    </tr>
</table>
