<?php

namespace RewardX\Points;

use RewardX\CPT\Transaction_CPT;
use WP_Error;

if (!defined('ABSPATH')) {
    exit;
}

class Points_Manager
{
    public const META_KEY = 'rewardx_points';

    private const TRANSIENT_POINTS_PREFIX = 'rewardx_points_';

    private const TRANSIENT_LEDGER_PREFIX = 'rewardx_ledger_';

    public function get_points(int $user_id, bool $use_cache = true): int
    {
        if ($user_id <= 0) {
            return 0;
        }

        $transient_key = self::TRANSIENT_POINTS_PREFIX . $user_id;

        if ($use_cache) {
            $cached = get_transient($transient_key);
            if (false !== $cached) {
                return (int) $cached;
            }
        }

        $points = (int) get_user_meta($user_id, self::META_KEY, true);

        set_transient($transient_key, $points, MINUTE_IN_SECONDS * 10);

        return $points;
    }

    public function set_points(int $user_id, int $points): bool
    {
        $points = max(0, $points);
        $updated = update_user_meta($user_id, self::META_KEY, $points);
        $this->clear_cache($user_id);

        return (bool) $updated;
    }

    public function adjust_points(int $user_id, int $delta, array $args = [])
    {
        $reason   = $args['reason'] ?? '';
        $ref_type = $args['ref_type'] ?? 'system';
        $ref_id   = $args['ref_id'] ?? '';

        $lock_key = "rewardx_lock_{$user_id}";
        if (get_transient($lock_key)) {
            return new WP_Error('rewardx_locked', __('Vui lòng thử lại sau giây lát.', 'woo-rewardx-lite'));
        }

        set_transient($lock_key, '1', 5);

        $attempts = 0;

        do {
            $current = (int) get_user_meta($user_id, self::META_KEY, true);
            $new     = $current + $delta;

            if ($new < 0) {
                delete_transient($lock_key);

                return new WP_Error('rewardx_insufficient_points', __('Bạn không đủ điểm để thực hiện thao tác này.', 'woo-rewardx-lite'));
            }

            $updated = update_user_meta($user_id, self::META_KEY, $new, $current);
            $attempts++;
        } while (!$updated && $attempts < 2);

        delete_transient($lock_key);

        if (!$updated) {
            return new WP_Error('rewardx_update_failed', __('Không thể cập nhật điểm, vui lòng thử lại.', 'woo-rewardx-lite'));
        }

        $this->clear_cache($user_id);

        $this->add_transaction($user_id, $delta, [
            'reason'        => $reason,
            'ref_type'      => $ref_type,
            'ref_id'        => $ref_id,
            'balance_after' => $new,
            'timestamp'     => time(),
        ]);

        return $new;
    }

    public function add_transaction(int $user_id, int $delta, array $args = []): int
    {
        $reason        = $args['reason'] ?? '';
        $ref_type      = $args['ref_type'] ?? 'system';
        $ref_id        = $args['ref_id'] ?? '';
        $balance_after = $args['balance_after'] ?? $this->get_points($user_id, false);
        $timestamp     = $args['timestamp'] ?? time();

        $title = sprintf(
            '%s%s %s',
            $delta > 0 ? '+' : '',
            $delta,
            $reason ? '– ' . $reason : ''
        );

        $post_id = wp_insert_post([
            'post_author'  => $user_id,
            'post_title'   => $title,
            'post_status'  => 'private',
            'post_type'    => Transaction_CPT::POST_TYPE,
            'post_content' => '',
        ]);

        if ($post_id && !is_wp_error($post_id)) {
            update_post_meta($post_id, '_delta', $delta);
            update_post_meta($post_id, '_reason', sanitize_text_field($reason));
            update_post_meta($post_id, '_ref_type', sanitize_key($ref_type));
            update_post_meta($post_id, '_ref_id', sanitize_text_field($ref_id));
            update_post_meta($post_id, '_balance_after', (int) $balance_after);
            update_post_meta($post_id, '_timestamp', (int) $timestamp);

            $this->clear_ledger_cache($user_id);
        }

        return (int) $post_id;
    }

    public function get_recent_transactions(int $user_id, int $limit = 10): array
    {
        $cache_key = self::TRANSIENT_LEDGER_PREFIX . $user_id;
        $cached    = get_transient($cache_key);

        if (false !== $cached) {
            return $cached;
        }

        $query = new \WP_Query([
            'post_type'      => Transaction_CPT::POST_TYPE,
            'post_status'    => 'private',
            'author'         => $user_id,
            'posts_per_page' => $limit,
            'orderby'        => 'date',
            'order'          => 'DESC',
            'no_found_rows'  => true,
        ]);

        $items = [];

        foreach ($query->posts as $post) {
            $items[] = [
                'id'             => $post->ID,
                'title'          => $post->post_title,
                'delta'          => (int) get_post_meta($post->ID, '_delta', true),
                'reason'         => get_post_meta($post->ID, '_reason', true),
                'ref_type'       => get_post_meta($post->ID, '_ref_type', true),
                'ref_id'         => get_post_meta($post->ID, '_ref_id', true),
                'balance_after'  => (int) get_post_meta($post->ID, '_balance_after', true),
                'timestamp'      => (int) get_post_meta($post->ID, '_timestamp', true),
            ];
        }

        set_transient($cache_key, $items, MINUTE_IN_SECONDS * 5);

        return $items;
    }

    public function clear_cache(int $user_id): void
    {
        delete_transient(self::TRANSIENT_POINTS_PREFIX . $user_id);
        $this->clear_ledger_cache($user_id);
    }

    public function clear_ledger_cache(int $user_id): void
    {
        delete_transient(self::TRANSIENT_LEDGER_PREFIX . $user_id);
    }
}
