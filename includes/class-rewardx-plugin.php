<?php

namespace RewardX;

use RewardX\Admin\Admin;
use RewardX\Admin\Ranks_Admin;
use RewardX\CPT\Reward_CPT;
use RewardX\CPT\Transaction_CPT;
use RewardX\Emails\Emails;
use RewardX\Frontend\Frontend;
use RewardX\Points\Points_Manager;
use RewardX\Ranks\Rank_Manager;
use RewardX\Redeem\Redeem_Handler;
use RewardX\Settings\Settings;

require_once REWARDX_PATH . 'includes/Points/class-points-manager.php';
require_once REWARDX_PATH . 'includes/CPT/class-reward-cpt.php';
require_once REWARDX_PATH . 'includes/CPT/class-transaction-cpt.php';
require_once REWARDX_PATH . 'includes/Settings/class-settings.php';
require_once REWARDX_PATH . 'includes/Frontend/class-frontend.php';
require_once REWARDX_PATH . 'includes/Admin/class-admin.php';
require_once REWARDX_PATH . 'includes/Admin/class-ranks-admin.php';
require_once REWARDX_PATH . 'includes/Ranks/class-rank-manager.php';
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

    private Ranks_Admin $ranks_admin;

    private Redeem_Handler $redeem_handler;

    private Emails $emails;

    private Settings $settings;

    private Rank_Manager $rank_manager;

    private function __construct()
    {
        $this->points_manager  = new Points_Manager();
        $this->reward_cpt      = new Reward_CPT();
        $this->transaction_cpt = new Transaction_CPT();
        $this->settings        = new Settings();
        $this->rank_manager    = new Rank_Manager();
        $this->frontend        = new Frontend($this->points_manager, $this->rank_manager);
        $this->admin           = new Admin($this->points_manager);
        $this->ranks_admin     = new Ranks_Admin($this->rank_manager);
        $this->redeem_handler  = new Redeem_Handler($this->points_manager);
        $this->emails          = new Emails();
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
        $instance->frontend->register_nfc_route();
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
        $this->admin->hooks();
        $this->ranks_admin->hooks();
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

    public function get_frontend(): Frontend
    {
        return $this->frontend;
    }

    public function get_rank_manager(): Rank_Manager
    {
        return $this->rank_manager;
    }
}
