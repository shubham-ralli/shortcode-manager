<?php

/**
 * Name: Shortcode Clone
 */

add_filter('post_row_actions', function ($actions, $post) {
    if ($post->post_type === 'shortcode') {
        $url = wp_nonce_url(admin_url('admin.php?action=clone_shortcode&post=' . $post->ID), 'clone_shortcode_' . $post->ID);
        $actions['clone'] = '<a href="' . esc_url($url) . '">Clone</a>';
    }
    return $actions;
}, 10, 2);

add_action('admin_action_clone_shortcode', function () {
    if (!isset($_GET['post']) || !isset($_GET['_wpnonce'])) {
        return;
    }

    $post_id = (int) $_GET['post'];
    $nonce = sanitize_text_field(wp_unslash($_GET['_wpnonce']));

    if (!wp_verify_nonce($nonce, 'clone_shortcode_' . $post_id)) {
        return;
    }

    $post = get_post($post_id);
    if (!$post || $post->post_type !== 'shortcode') {
        return;
    }

    // Generate unique title
    $base_title = $post->post_title . ' (Copy)';
    $new_title = $base_title;
    $i = 1;
    while (post_exists($new_title)) {
        $new_title = $base_title . ' ' . $i;
        $i++;
    }

    // Create draft clone
    $new_post = [
        'post_title'  => $new_title,
        'post_status' => 'draft',
        'post_type'   => 'shortcode',
    ];
    $new_post_id = wp_insert_post($new_post);

    if ($new_post_id && !is_wp_error($new_post_id)) {
        $meta = get_post_meta($post_id, '_sm_shortcode_content', true);
        update_post_meta($new_post_id, '_sm_shortcode_content', $meta);

        // Redirect to list, not edit page
        wp_redirect(admin_url('edit.php?post_type=shortcode&cloned=1'));
        exit;
    }
});
