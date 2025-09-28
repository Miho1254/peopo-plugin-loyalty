<?php

namespace RewardX\Emails;

use RewardX\Plugin;

if (!defined('ABSPATH')) {
    exit;
}

class Voucher_Email extends \WC_Email
{
    public function __construct()
    {
        $this->id             = 'rewardx_voucher';
        $this->title          = __('RewardX – Mã giảm giá của bạn', 'woo-rewardx-lite');
        $this->description    = __('Gửi mã voucher cho khách hàng khi đổi thưởng.', 'woo-rewardx-lite');
        $this->heading        = __('Voucher đổi thưởng', 'woo-rewardx-lite');
        $this->subject        = __('Voucher đổi thưởng từ {site_title}', 'woo-rewardx-lite');
        $this->customer_email = true;

        $this->template_html  = 'emails/rewardx-voucher.php';
        $this->template_plain = 'emails/plain/rewardx-voucher.php';

        parent::__construct();
    }

    public function trigger(int $user_id, array $data = []): void
    {
        $user = get_user_by('id', $user_id);

        if (!$user) {
            return;
        }

        $this->recipient = $user->user_email;

        $this->setup_locale();

        $this->data = [
            'user'          => $user,
            'coupon_code'   => $data['code'] ?? '',
            'coupon_amount' => $data['amount'] ?? 0,
            'coupon_expiry' => $data['expiry'] ?? '',
            'email'         => $this,
        ];

        $settings  = Plugin::instance()->get_settings()->get_settings();
        $subject   = $settings['email_template_subject'] ?? $this->subject;
        $body_html = $settings['email_template_body'] ?? '';

        $replacements = [
            '[user_name]'      => $user->display_name,
            '[coupon_code]'    => $this->data['coupon_code'],
            '[coupon_amount]'  => wc_price($this->data['coupon_amount']),
            '[coupon_expiry]'  => $this->data['coupon_expiry'],
            '[site_name]'      => wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES),
        ];

        $this->subject = strtr($subject, $replacements);

        if ($body_html) {
            $this->template_html  = false;
            $this->template_plain = false;
            $body                 = strtr($body_html, $replacements);
            $this->send($this->get_recipient(), $this->get_subject(), $body, $this->get_headers(), $this->get_attachments());
        } else {
            $this->send($this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments());
        }

        $this->restore_locale();
    }

    public function get_content_html(): string
    {
        return wc_get_template_html($this->template_html, $this->data, '', REWARDX_PATH . 'templates/');
    }

    public function get_content_plain(): string
    {
        return wc_get_template_html($this->template_plain, $this->data, '', REWARDX_PATH . 'templates/');
    }
}
