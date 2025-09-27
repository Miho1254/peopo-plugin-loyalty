<?php

namespace RewardX\Settings;

if (!defined('ABSPATH')) {
    exit;
}

class Settings
{
    public const OPTION_KEY = 'rewardx_settings';

    public function register(): void
    {
        add_action('admin_menu', [$this, 'register_menu']);
        add_action('admin_init', [$this, 'register_settings']);
    }

    public function register_menu(): void
    {
        add_submenu_page(
            'woocommerce',
            __('RewardX Settings', 'woo-rewardx-lite'),
            __('RewardX', 'woo-rewardx-lite'),
            'manage_woocommerce',
            'rewardx-settings',
            [$this, 'render_settings_page']
        );
    }

    public function register_settings(): void
    {
        register_setting(self::OPTION_KEY, self::OPTION_KEY, [$this, 'sanitize_settings']);

        add_settings_section('rewardx_general', __('Cài đặt chung', 'woo-rewardx-lite'), '__return_false', self::OPTION_KEY);

        add_settings_field('default_expiry_days', __('Số ngày hết hạn mặc định', 'woo-rewardx-lite'), [$this, 'render_number_field'], self::OPTION_KEY, 'rewardx_general', [
            'label_for' => 'default_expiry_days',
            'min'       => 1,
        ]);

        add_settings_field('physical_order_status', __('Trạng thái đơn hàng quà vật lý', 'woo-rewardx-lite'), [$this, 'render_order_status_field'], self::OPTION_KEY, 'rewardx_general', [
            'label_for' => 'physical_order_status',
        ]);

        add_settings_field('email_template_subject', __('Tiêu đề email voucher', 'woo-rewardx-lite'), [$this, 'render_text_field'], self::OPTION_KEY, 'rewardx_general', [
            'label_for' => 'email_template_subject',
        ]);

        add_settings_field('email_template_body', __('Nội dung email voucher (HTML)', 'woo-rewardx-lite'), [$this, 'render_textarea_field'], self::OPTION_KEY, 'rewardx_general', [
            'label_for' => 'email_template_body',
        ]);

        add_settings_field('permissions_roles', __('Vai trò được phép quản lý', 'woo-rewardx-lite'), [$this, 'render_roles_field'], self::OPTION_KEY, 'rewardx_general', [
            'label_for' => 'permissions_roles',
        ]);
    }

    public function render_settings_page(): void
    {
        if (!current_user_can('manage_woocommerce')) {
            return;
        }

        $settings = $this->get_settings();

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('RewardX Settings', 'woo-rewardx-lite') . '</h1>';
        echo '<form method="post" action="options.php">';
        settings_fields(self::OPTION_KEY);
        do_settings_sections(self::OPTION_KEY);
        submit_button();
        echo '</form>';
        echo '<p><em>' . esc_html__('Placeholder khả dụng: [user_name], [coupon_code], [coupon_amount], [coupon_expiry], [site_name]', 'woo-rewardx-lite') . '</em></p>';
        echo '</div>';
    }

    public function render_number_field(array $args): void
    {
        $settings = $this->get_settings();
        $id       = $args['label_for'];
        $value    = isset($settings[$id]) ? (int) $settings[$id] : 30;

        printf('<input type="number" min="%1$d" id="%2$s" name="%3$s[%2$s]" value="%4$d" class="small-text" />', $args['min'] ?? 0, esc_attr($id), esc_attr(self::OPTION_KEY), $value);
    }

    public function render_order_status_field(array $args): void
    {
        $settings = $this->get_settings();
        $id       = $args['label_for'];
        $value    = $settings[$id] ?? 'processing';
        $statuses = function_exists('wc_get_order_statuses') ? wc_get_order_statuses() : [];

        echo '<select id="' . esc_attr($id) . '" name="' . esc_attr(self::OPTION_KEY) . '[' . esc_attr($id) . ']">';
        foreach ($statuses as $status_key => $label) {
            printf('<option value="%1$s" %2$s>%3$s</option>', esc_attr($status_key), selected($value, $status_key, false), esc_html($label));
        }
        echo '</select>';
    }

    public function render_text_field(array $args): void
    {
        $settings = $this->get_settings();
        $id       = $args['label_for'];
        $value    = isset($settings[$id]) ? esc_attr($settings[$id]) : '';

        printf('<input type="text" id="%1$s" name="%2$s[%1$s]" value="%3$s" class="regular-text" />', esc_attr($id), esc_attr(self::OPTION_KEY), $value);
    }

    public function render_textarea_field(array $args): void
    {
        $settings = $this->get_settings();
        $id       = $args['label_for'];
        $value    = isset($settings[$id]) ? esc_textarea($settings[$id]) : '';

        printf('<textarea id="%1$s" name="%2$s[%1$s]" rows="6" class="large-text">%3$s</textarea>', esc_attr($id), esc_attr(self::OPTION_KEY), $value);
    }

    public function render_roles_field(array $args): void
    {
        global $wp_roles;
        $settings = $this->get_settings();
        $id       = $args['label_for'];
        $selected = $settings[$id] ?? [];

        echo '<select id="' . esc_attr($id) . '" name="' . esc_attr(self::OPTION_KEY) . '[' . esc_attr($id) . '][]" multiple size="5">';
        foreach ($wp_roles->roles as $role_key => $role) {
            printf('<option value="%1$s" %2$s>%3$s</option>', esc_attr($role_key), selected(in_array($role_key, $selected, true), true, false), esc_html($role['name']));
        }
        echo '</select>';
    }

    public function sanitize_settings(array $settings): array
    {
        $defaults = $this->get_default_settings();

        $settings['default_expiry_days']   = isset($settings['default_expiry_days']) ? max(1, (int) $settings['default_expiry_days']) : $defaults['default_expiry_days'];
        $settings['physical_order_status'] = isset($settings['physical_order_status']) ? sanitize_text_field($settings['physical_order_status']) : $defaults['physical_order_status'];
        $settings['email_template_subject'] = isset($settings['email_template_subject']) ? sanitize_text_field($settings['email_template_subject']) : $defaults['email_template_subject'];
        $settings['email_template_body']    = isset($settings['email_template_body']) ? wp_kses_post($settings['email_template_body']) : $defaults['email_template_body'];
        $settings['permissions_roles']      = isset($settings['permissions_roles']) && is_array($settings['permissions_roles']) ? array_map('sanitize_key', $settings['permissions_roles']) : $defaults['permissions_roles'];

        return $settings;
    }

    public function get_settings(): array
    {
        return wp_parse_args(get_option(self::OPTION_KEY, []), $this->get_default_settings());
    }

    private function get_default_settings(): array
    {
        return [
            'default_expiry_days'   => 30,
            'physical_order_status' => 'wc-processing',
            'email_template_subject' => __('Voucher đổi thưởng từ {site_title}', 'woo-rewardx-lite'),
            'email_template_body'    => '',
            'permissions_roles'      => ['shop_manager', 'administrator'],
        ];
    }
}
