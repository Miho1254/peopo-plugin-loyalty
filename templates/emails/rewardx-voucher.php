<?php
if (!defined('ABSPATH')) {
    exit;
}

/** @var WC_Email $email */
?>
<p><?php printf(esc_html__('Xin chào %s,', 'woo-rewardx-lite'), esc_html($email->get_recipient())); ?></p>

<p><?php esc_html_e('Cảm ơn bạn đã đổi thưởng trên cửa hàng của chúng tôi. Dưới đây là thông tin voucher:', 'woo-rewardx-lite'); ?></p>

<table style="width:100%;border-collapse:collapse;">
    <tr>
        <th style="text-align:left;border:1px solid #ddd;padding:8px;"><?php esc_html_e('Mã voucher', 'woo-rewardx-lite'); ?></th>
        <td style="border:1px solid #ddd;padding:8px;font-weight:bold;font-size:18px;"><?php echo esc_html($email->data['coupon_code']); ?></td>
    </tr>
    <tr>
        <th style="text-align:left;border:1px solid #ddd;padding:8px;"><?php esc_html_e('Giá trị', 'woo-rewardx-lite'); ?></th>
        <td style="border:1px solid #ddd;padding:8px;"><?php echo wp_kses_post(wc_price($email->data['coupon_amount'])); ?></td>
    </tr>
    <tr>
        <th style="text-align:left;border:1px solid #ddd;padding:8px;"><?php esc_html_e('Hết hạn vào', 'woo-rewardx-lite'); ?></th>
        <td style="border:1px solid #ddd;padding:8px;"><?php echo esc_html($email->data['coupon_expiry']); ?></td>
    </tr>
</table>

<p><?php esc_html_e('Voucher chỉ sử dụng cho tài khoản email này và có hiệu lực một lần.', 'woo-rewardx-lite'); ?></p>

<p><?php esc_html_e('Chúc bạn mua sắm vui vẻ!', 'woo-rewardx-lite'); ?></p>
