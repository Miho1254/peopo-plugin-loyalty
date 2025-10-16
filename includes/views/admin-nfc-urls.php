<?php
/**
 * Admin page hiển thị URL NFC cho khách hàng.
 *
 * @package RewardX
 */

if (!defined('ABSPATH')) {
    exit;
}

?>
<div class="wrap rewardx-nfc-admin">
    <h1 class="wp-heading-inline"><?php esc_html_e('URL NFC khách hàng', 'woo-rewardx-lite'); ?></h1>

    <form method="get" class="rewardx-nfc-search">
        <input type="hidden" name="page" value="rewardx-nfc-urls" />
        <p class="search-box">
            <label class="screen-reader-text" for="rewardx-nfc-search"><?php esc_html_e('Tìm kiếm khách hàng', 'woo-rewardx-lite'); ?></label>
            <input
                type="search"
                id="rewardx-nfc-search"
                name="s"
                value="<?php echo esc_attr($search); ?>"
                placeholder="<?php esc_attr_e('Tên, email hoặc tên đăng nhập...', 'woo-rewardx-lite'); ?>"
            />
            <?php submit_button(__('Tìm kiếm khách hàng', 'woo-rewardx-lite'), 'secondary', '', false); ?>
        </p>
    </form>

    <table class="widefat striped">
        <thead>
            <tr>
                <th><?php esc_html_e('Tên khách hàng', 'woo-rewardx-lite'); ?></th>
                <th><?php esc_html_e('Email', 'woo-rewardx-lite'); ?></th>
                <th><?php esc_html_e('Số điện thoại', 'woo-rewardx-lite'); ?></th>
                <th><?php esc_html_e('URL NFC', 'woo-rewardx-lite'); ?></th>
                <th><?php esc_html_e('Hành động', 'woo-rewardx-lite'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($users)) : ?>
                <?php foreach ($users as $user) : ?>
                    <?php
                    $user_id   = (int) $user->ID;
                    $phone     = (string) get_user_meta($user_id, 'billing_phone', true);
                    $nfc_url   = function_exists('rewardx_get_nfc_profile_url') ? rewardx_get_nfc_profile_url($user_id) : null;
                    $edit_link = get_edit_user_link($user_id);
                    ?>
                    <tr>
                        <td>
                            <?php if ($edit_link) : ?>
                                <a href="<?php echo esc_url($edit_link); ?>">
                                    <?php echo esc_html($user->display_name ?: $user->user_login); ?>
                                </a>
                            <?php else : ?>
                                <?php echo esc_html($user->display_name ?: $user->user_login); ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (is_email($user->user_email)) : ?>
                                <a href="mailto:<?php echo esc_attr($user->user_email); ?>">
                                    <?php echo esc_html($user->user_email); ?>
                                </a>
                            <?php else : ?>
                                <span class="description"><?php esc_html_e('Chưa có email hợp lệ', 'woo-rewardx-lite'); ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ('' !== $phone) : ?>
                                <?php echo esc_html($phone); ?>
                            <?php else : ?>
                                <span class="description"><?php esc_html_e('Chưa cập nhật', 'woo-rewardx-lite'); ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($nfc_url)) : ?>
                                <code><?php echo esc_html($nfc_url); ?></code>
                            <?php else : ?>
                                <span class="description"><?php esc_html_e('Không khả dụng', 'woo-rewardx-lite'); ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($nfc_url)) : ?>
                                <a class="button button-primary" href="<?php echo esc_url($nfc_url); ?>" target="_blank" rel="noopener noreferrer">
                                    <?php esc_html_e('Mở hồ sơ', 'woo-rewardx-lite'); ?>
                                </a>
                            <?php else : ?>
                                <span class="description"><?php esc_html_e('Không thể tạo URL', 'woo-rewardx-lite'); ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan="5">
                        <p><?php esc_html_e('Không tìm thấy khách hàng nào phù hợp.', 'woo-rewardx-lite'); ?></p>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <?php if (!empty($pagination)) : ?>
        <div class="tablenav">
            <div class="tablenav-pages">
                <?php echo wp_kses_post($pagination); ?>
            </div>
        </div>
    <?php endif; ?>
</div>
