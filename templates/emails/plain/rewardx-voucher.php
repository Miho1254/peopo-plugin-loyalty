<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<?php printf(__('Xin chào %s,', 'woo-rewardx-lite'), $email->get_recipient()); ?>

<?php esc_html_e('Cảm ơn bạn đã đổi thưởng trên cửa hàng của chúng tôi. Dưới đây là thông tin voucher:', 'woo-rewardx-lite'); ?>

<?php esc_html_e('Mã voucher:', 'woo-rewardx-lite'); ?> <?php echo $email->data['coupon_code']; ?>
<?php esc_html_e('Giá trị:', 'woo-rewardx-lite'); ?> <?php echo wp_strip_all_tags(wc_price($email->data['coupon_amount'])); ?>
<?php esc_html_e('Hết hạn vào:', 'woo-rewardx-lite'); ?> <?php echo $email->data['coupon_expiry']; ?>

<?php esc_html_e('Voucher chỉ sử dụng cho tài khoản email này và có hiệu lực một lần.', 'woo-rewardx-lite'); ?>

<?php esc_html_e('Chúc bạn mua sắm vui vẻ!', 'woo-rewardx-lite'); ?>
