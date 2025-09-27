<?php

namespace RewardX\CPT;

if (!defined('ABSPATH')) {
    exit;
}

class Transaction_CPT
{
    public const POST_TYPE = 'rewardx_txn';

    public function hooks(): void
    {
        add_action('init', [$this, 'register']);
    }

    public function register(): void
    {
        register_post_type(self::POST_TYPE, [
            'label'               => __('RewardX Ledger', 'woo-rewardx-lite'),
            'public'              => false,
            'show_ui'             => false,
            'show_in_menu'        => false,
            'supports'            => ['title'],
            'capability_type'     => 'post',
            'has_archive'         => false,
            'rewrite'             => false,
            'publicly_queryable'  => false,
        ]);
    }
}
