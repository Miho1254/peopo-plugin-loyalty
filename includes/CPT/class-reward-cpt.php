<?php

namespace RewardX\CPT;

if (!defined('ABSPATH')) {
    exit;
}

class Reward_CPT
{
    public const POST_TYPE = 'rewardx_reward';

    public const TAXONOMY = 'rewardx_type';

    public function hooks(): void
    {
        add_action('init', [$this, 'register']);
    }

    public function register(): void
    {
        $labels = [
            'name'               => __('Rewards', 'woo-rewardx-lite'),
            'singular_name'      => __('Reward', 'woo-rewardx-lite'),
            'menu_name'          => __('RewardX Rewards', 'woo-rewardx-lite'),
            'add_new'            => __('Thêm phần thưởng', 'woo-rewardx-lite'),
            'add_new_item'       => __('Thêm phần thưởng mới', 'woo-rewardx-lite'),
            'edit_item'          => __('Chỉnh sửa phần thưởng', 'woo-rewardx-lite'),
            'new_item'           => __('Phần thưởng mới', 'woo-rewardx-lite'),
            'view_item'          => __('Xem phần thưởng', 'woo-rewardx-lite'),
            'search_items'       => __('Tìm phần thưởng', 'woo-rewardx-lite'),
            'not_found'          => __('Không tìm thấy phần thưởng', 'woo-rewardx-lite'),
            'not_found_in_trash' => __('Không có phần thưởng nào trong thùng rác', 'woo-rewardx-lite'),
        ];

        register_post_type(self::POST_TYPE, [
            'labels'             => $labels,
            'public'             => false,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'capability_type'    => 'post',
            'supports'           => ['title', 'editor', 'excerpt', 'thumbnail'],
            'menu_position'      => 56,
            'menu_icon'          => 'dashicons-awards',
            'rewrite'            => false,
            'has_archive'        => false,
        ]);

        register_taxonomy(self::TAXONOMY, self::POST_TYPE, [
            'labels'            => [
                'name'          => __('Loại phần thưởng', 'woo-rewardx-lite'),
                'singular_name' => __('Loại phần thưởng', 'woo-rewardx-lite'),
            ],
            'public'            => false,
            'show_ui'           => true,
            'show_in_quick_edit'=> false,
            'hierarchical'      => false,
            'meta_box_cb'       => [$this, 'render_type_meta_box'],
        ]);

        foreach (['physical' => __('Quà vật lý', 'woo-rewardx-lite'), 'voucher' => __('Voucher', 'woo-rewardx-lite')] as $slug => $label) {
            if (!term_exists($slug, self::TAXONOMY)) {
                wp_insert_term($label, self::TAXONOMY, ['slug' => $slug]);
            }
        }
    }

    public function render_type_meta_box($post, array $box): void
    {
        $terms = get_terms([
            'taxonomy'   => self::TAXONOMY,
            'hide_empty' => false,
        ]);

        if (empty($terms) || is_wp_error($terms)) {
            return;
        }

        $selected = wp_get_post_terms($post->ID, self::TAXONOMY, ['fields' => 'ids']);
        $selected_id = $selected[0] ?? 0;

        echo '<div class="rewardx-taxonomy-field">';
        echo '<p>' . esc_html__('Chọn loại phần thưởng', 'woo-rewardx-lite') . '</p>';
        echo '<select name="tax_input[' . esc_attr(self::TAXONOMY) . '][]">';
        foreach ($terms as $term) {
            printf(
                '<option value="%1$s" %2$s>%3$s</option>',
                esc_attr($term->term_id),
                selected($selected_id, $term->term_id, false),
                esc_html($term->name)
            );
        }
        echo '</select>';
        echo '</div>';
    }
}
