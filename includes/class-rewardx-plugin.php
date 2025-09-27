<?php

namespace RewardX;

use RewardX\Admin\Admin;
use RewardX\CPT\Reward_CPT;
use RewardX\CPT\Transaction_CPT;
use RewardX\Emails\Emails;
use RewardX\Frontend\Frontend;
use RewardX\Points\Points_Manager;
use RewardX\Redeem\Redeem_Handler;
use RewardX\Settings\Settings;

require_once REWARDX_PATH . 'includes/Points/class-points-manager.php';
require_once REWARDX_PATH . 'includes/CPT/class-reward-cpt.php';
require_once REWARDX_PATH . 'includes/CPT/class-transaction-cpt.php';
require_once REWARDX_PATH . 'includes/Settings/class-settings.php';
require_once REWARDX_PATH . 'includes/Frontend/class-frontend.php';
require_once REWARDX_PATH . 'includes/Admin/class-admin.php';
require_once REWARDX_PATH . 'includes/Redeem/class-redeem-handler.php';
require_once REWARDX_PATH . 'includes/Emails/class-emails.php';

if (!defined('ABSPATH')) {
    exit;
}

final class Plugin
{
    private static ?Plugin $instance = null;

    private bool $booted = false;

    private Points_Manager $points_manager;

    private Reward_CPT $reward_cpt;

    private Transaction_CPT $transaction_cpt;

    private Frontend $frontend;

    private Admin $admin;

    private Redeem_Handler $redeem_handler;

    private Emails $emails;

    private Settings $settings;

    private function __construct()
    {
        $this->points_manager    = new Points_Manager();
        $this->reward_cpt        = new Reward_CPT();
        $this->transaction_cpt   = new Transaction_CPT();
        $this->settings          = new Settings();
        $this->frontend          = new Frontend($this->points_manager);
        $this->admin             = new Admin($this->points_manager);
        $this->redeem_handler    = new Redeem_Handler($this->points_manager);
        $this->emails            = new Emails();
    }

    public static function instance(): Plugin
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public static function activate(): void
    {
        $instance = self::instance();
        $instance->reward_cpt->register();
        $instance->transaction_cpt->register();
        $instance->frontend->register_endpoint();
        flush_rewrite_rules();
    }

    public static function deactivate(): void
    {
        flush_rewrite_rules();
    }

    public function boot(): void
    {
        if ($this->booted) {
            return;
        }

        $this->booted = true;

        add_action('init', [$this, 'load_textdomain']);

        $this->settings->register();
        $this->reward_cpt->hooks();
        $this->transaction_cpt->hooks();
        $this->frontend->hooks();
        $this->admin->hooks();
        $this->redeem_handler->hooks();
        $this->emails->hooks();
    }

    public function load_textdomain(): void
    {
        load_plugin_textdomain(
            'woo-rewardx-lite',
            false,
            dirname(REWARDX_BASENAME) . '/languages'
        );
    }

    public function get_points_manager(): Points_Manager
    {
        return $this->points_manager;
    }

    public function get_settings(): Settings
    {
        return $this->settings;
    }
}
