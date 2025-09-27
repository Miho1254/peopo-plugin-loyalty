<?php

namespace RewardX\Emails;

if (!defined('ABSPATH')) {
    exit;
}

class Emails
{
    public function hooks(): void
    {
        add_filter('woocommerce_email_classes', [$this, 'register_emails']);
    }

    public function register_emails(array $emails): array
    {
        require_once REWARDX_PATH . 'includes/Emails/class-email-voucher.php';
        $emails['RewardX_Email_Voucher'] = new Voucher_Email();

        return $emails;
    }

    public static function send_voucher(int $user_id, array $data): void
    {
        if (!function_exists('WC')) {
            return;
        }

        $mailer = WC()->mailer();

        if (!$mailer) {
            return;
        }

        $emails = $mailer->get_emails();

        foreach ($emails as $email) {
            if ($email instanceof Voucher_Email) {
                $email->trigger($user_id, $data);

                return;
            }
        }
    }
}
