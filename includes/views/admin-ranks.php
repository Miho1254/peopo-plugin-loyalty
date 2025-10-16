<?php
if (!defined('ABSPATH')) {
    exit;
}

$format_decimal = static function (float $value): string {
    if (function_exists('wc_format_decimal')) {
        return wc_format_decimal($value, wc_get_price_decimals());
    }

    return number_format($value, 2, '.', '');
};
?>
<div class="wrap rewardx-ranks-page">
    <h1><?php esc_html_e('Thứ hạng khách hàng RewardX', 'woo-rewardx-lite'); ?></h1>

    <p class="description">
        <?php esc_html_e('Thiết lập các mốc chi tiêu để xếp hạng thành viên. Khách hàng sẽ được gán hạng cao nhất mà họ đạt được dựa trên tổng số tiền đã mua hàng.', 'woo-rewardx-lite'); ?>
    </p>

    <?php if ('true' === $notice) : ?>
        <div class="notice notice-success is-dismissible">
            <p><?php esc_html_e('Đã lưu cấu hình thứ hạng thành công.', 'woo-rewardx-lite'); ?></p>
        </div>
    <?php endif; ?>

    <?php if (!empty($error)) : ?>
        <div class="notice notice-error">
            <?php if ('empty' === $error) : ?>
                <p><?php esc_html_e('Vui lòng thêm ít nhất một thứ hạng trước khi lưu.', 'woo-rewardx-lite'); ?></p>
            <?php else : ?>
                <p><?php esc_html_e('Dữ liệu gửi lên không hợp lệ.', 'woo-rewardx-lite'); ?></p>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="rewardx-ranks-form">
        <?php wp_nonce_field('rewardx_save_ranks'); ?>
        <input type="hidden" name="action" value="rewardx_save_ranks" />

        <table class="widefat fixed striped rewardx-ranks-table" id="rewardx-ranks-table">
            <thead>
                <tr>
                    <th scope="col" class="column-primary">
                        <?php esc_html_e('Tên thứ hạng', 'woo-rewardx-lite'); ?>
                    </th>
                    <th scope="col" class="column-threshold">
                        <?php esc_html_e('Tổng chi tiêu tối thiểu', 'woo-rewardx-lite'); ?>
                    </th>
                    <th scope="col" class="column-coupons">
                        <?php esc_html_e('Coupon mặc định', 'woo-rewardx-lite'); ?>
                    </th>
                    <th scope="col" class="column-actions">&nbsp;</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($ranks as $rank) :
                    $row_id = md5($rank['name'] . $rank['threshold'] . wp_rand());
                    $coupon_value = '';

                    if (isset($rank['coupons'])) {
                        $coupon_list = is_array($rank['coupons']) ? $rank['coupons'] : [$rank['coupons']];
                        $coupon_list = array_filter(array_map('strval', $coupon_list));
                        $coupon_value = implode("\n", $coupon_list);
                    }
                    ?>
                    <tr class="rewardx-rank-row">
                        <td class="column-primary">
                            <label class="screen-reader-text" for="rewardx-rank-name-<?php echo esc_attr($row_id); ?>">
                                <?php esc_html_e('Tên thứ hạng', 'woo-rewardx-lite'); ?>
                            </label>
                            <input
                                type="text"
                                id="rewardx-rank-name-<?php echo esc_attr($row_id); ?>"
                                name="ranks[name][]"
                                value="<?php echo esc_attr($rank['name']); ?>"
                                class="regular-text"
                                required
                            />
                        </td>
                        <td class="column-threshold">
                            <label class="screen-reader-text" for="rewardx-rank-threshold-<?php echo esc_attr($row_id); ?>">
                                <?php esc_html_e('Tổng chi tiêu tối thiểu', 'woo-rewardx-lite'); ?>
                            </label>
                            <div class="rewardx-rank-amount">
                                <span class="rewardx-rank-amount__symbol"><?php echo esc_html($currency); ?></span>
                                <input
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    id="rewardx-rank-threshold-<?php echo esc_attr($row_id); ?>"
                                    name="ranks[threshold][]"
                                    value="<?php echo esc_attr($format_decimal((float) $rank['threshold'])); ?>"
                                    class="small-text"
                                    required
                                />
                            </div>
                            <p class="description">
                                <?php esc_html_e('Khách hàng phải tích lũy ít nhất số tiền này để đạt hạng.', 'woo-rewardx-lite'); ?>
                            </p>
                        </td>
                        <td class="column-coupons">
                            <label class="screen-reader-text" for="rewardx-rank-coupons-<?php echo esc_attr($row_id); ?>">
                                <?php esc_html_e('Coupon mặc định', 'woo-rewardx-lite'); ?>
                            </label>
                            <textarea
                                id="rewardx-rank-coupons-<?php echo esc_attr($row_id); ?>"
                                name="ranks[coupons][]"
                                rows="3"
                                class="large-text"
                                placeholder="COUPON_A\nCOUPON_B"
                            ><?php echo esc_textarea($coupon_value); ?></textarea>
                            <p class="description">
                                <?php esc_html_e('Nhập mã coupon mặc định (phân tách bằng dấu phẩy hoặc xuống dòng) sẽ tự động áp dụng khi khách hàng đạt hạng này.', 'woo-rewardx-lite'); ?>
                            </p>
                        </td>
                        <td class="column-actions">
                            <button type="button" class="button button-link-delete rewardx-remove-rank">
                                <?php esc_html_e('Xóa', 'woo-rewardx-lite'); ?>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <p>
            <button type="button" class="button button-secondary rewardx-add-rank">
                <?php esc_html_e('Thêm thứ hạng', 'woo-rewardx-lite'); ?>
            </button>
        </p>

        <?php submit_button(__('Lưu thứ hạng', 'woo-rewardx-lite')); ?>
    </form>
</div>

<script type="text/html" id="tmpl-rewardx-rank-row">
    <tr class="rewardx-rank-row">
        <td class="column-primary">
            <label class="screen-reader-text" for="rewardx-rank-name-{{ data.id }}">
                <?php esc_html_e('Tên thứ hạng', 'woo-rewardx-lite'); ?>
            </label>
            <input
                type="text"
                id="rewardx-rank-name-{{ data.id }}"
                name="ranks[name][]"
                class="regular-text"
                required
            />
        </td>
        <td class="column-threshold">
            <label class="screen-reader-text" for="rewardx-rank-threshold-{{ data.id }}">
                <?php esc_html_e('Tổng chi tiêu tối thiểu', 'woo-rewardx-lite'); ?>
            </label>
            <div class="rewardx-rank-amount">
                <span class="rewardx-rank-amount__symbol"><?php echo esc_html($currency); ?></span>
                <input
                    type="number"
                    min="0"
                    step="0.01"
                    id="rewardx-rank-threshold-{{ data.id }}"
                    name="ranks[threshold][]"
                    class="small-text"
                    required
                />
            </div>
            <p class="description">
                <?php esc_html_e('Khách hàng phải tích lũy ít nhất số tiền này để đạt hạng.', 'woo-rewardx-lite'); ?>
            </p>
        </td>
        <td class="column-coupons">
            <label class="screen-reader-text" for="rewardx-rank-coupons-{{ data.id }}">
                <?php esc_html_e('Coupon mặc định', 'woo-rewardx-lite'); ?>
            </label>
            <textarea
                id="rewardx-rank-coupons-{{ data.id }}"
                name="ranks[coupons][]"
                rows="3"
                class="large-text"
                placeholder="COUPON_A\nCOUPON_B"
            ></textarea>
            <p class="description">
                <?php esc_html_e('Nhập mã coupon mặc định (phân tách bằng dấu phẩy hoặc xuống dòng) sẽ tự động áp dụng khi khách hàng đạt hạng này.', 'woo-rewardx-lite'); ?>
            </p>
        </td>
        <td class="column-actions">
            <button type="button" class="button button-link-delete rewardx-remove-rank">
                <?php esc_html_e('Xóa', 'woo-rewardx-lite'); ?>
            </button>
        </td>
    </tr>
</script>
