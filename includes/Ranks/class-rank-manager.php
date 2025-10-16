<?php

namespace RewardX\Ranks;

if (!defined('ABSPATH')) {
    exit;
}

class Rank_Manager
{
    public const OPTION_KEY = 'rewardx_ranks';

    /**
     * Lấy danh sách thứ hạng đã được cấu hình.
     */
    public function get_ranks(): array
    {
        $stored = get_option(self::OPTION_KEY, []);

        if (!is_array($stored) || empty($stored)) {
            return $this->get_default_ranks();
        }

        $ranks = [];

        foreach ($stored as $rank) {
            if (!is_array($rank)) {
                continue;
            }

            $name      = isset($rank['name']) ? sanitize_text_field($rank['name']) : '';
            $threshold = isset($rank['threshold']) ? (float) $rank['threshold'] : 0.0;

            if ('' === $name) {
                continue;
            }

            $ranks[] = [
                'name'      => $name,
                'threshold' => max(0.0, $threshold),
            ];
        }

        if (empty($ranks)) {
            return $this->get_default_ranks();
        }

        return $this->sort_ranks($ranks);
    }

    /**
     * Lưu thứ hạng mới.
     */
    public function save_ranks(array $ranks): void
    {
        update_option(self::OPTION_KEY, $this->sanitize_ranks($ranks));
    }

    /**
     * Trả về thứ hạng hiện tại theo tổng chi tiêu.
     */
    public function get_rank_for_amount(float $amount): ?array
    {
        $amount = max(0.0, $amount);
        $ranks  = $this->get_ranks();
        $result = null;

        foreach ($ranks as $rank) {
            if ($amount >= (float) $rank['threshold']) {
                $result = $rank;
            } else {
                break;
            }
        }

        return $result;
    }

    /**
     * Lấy thứ hạng tiếp theo (nếu có) dựa trên tổng chi tiêu hiện tại.
     */
    public function get_next_rank(float $amount): ?array
    {
        $amount = max(0.0, $amount);

        foreach ($this->get_ranks() as $rank) {
            if ($amount < (float) $rank['threshold']) {
                return $rank;
            }
        }

        return null;
    }

    private function sanitize_ranks(array $ranks): array
    {
        $sanitized = [];

        foreach ($ranks as $rank) {
            if (!is_array($rank)) {
                continue;
            }

            $name      = isset($rank['name']) ? sanitize_text_field($rank['name']) : '';
            $threshold = isset($rank['threshold']) ? (float) $rank['threshold'] : 0.0;

            if ('' === $name) {
                continue;
            }

            $sanitized[] = [
                'name'      => $name,
                'threshold' => max(0.0, $threshold),
            ];
        }

        return $this->sort_ranks($sanitized);
    }

    private function sort_ranks(array $ranks): array
    {
        usort($ranks, static function (array $a, array $b): int {
            return (float) $a['threshold'] <=> (float) $b['threshold'];
        });

        return array_values($ranks);
    }

    private function get_default_ranks(): array
    {
        return [
            [
                'name'      => __('Thành viên mới', 'woo-rewardx-lite'),
                'threshold' => 0.0,
            ],
            [
                'name'      => __('Đồng hành', 'woo-rewardx-lite'),
                'threshold' => 2000000.0,
            ],
            [
                'name'      => __('Đại sứ', 'woo-rewardx-lite'),
                'threshold' => 5000000.0,
            ],
        ];
    }
}
