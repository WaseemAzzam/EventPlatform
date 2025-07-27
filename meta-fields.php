<?php
add_action('init', function() {
    register_post_meta('event', 'event_date', [
        'show_in_rest' => true,
        'single' => true,
        'type' => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'auth_callback' => function() { return current_user_can('edit_posts'); },
    ]);
    register_post_meta('event', 'event_time', [
        'show_in_rest' => true,
        'single' => true,
        'type' => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'auth_callback' => function() { return current_user_can('edit_posts'); },
    ]);
    register_post_meta('event', 'event_price', [
        'show_in_rest' => true,
        'single' => true,
        'type' => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'auth_callback' => function() { return current_user_can('edit_posts'); },
    ]);
    register_post_meta('event', 'google_map_link', [
        'show_in_rest' => true,
        'single' => true,
        'type' => 'string',
        'sanitize_callback' => 'esc_url_raw',
        'auth_callback' => function() { return current_user_can('edit_posts'); },
    ]);
    register_post_meta('event', '_event_custom_description', [
        'show_in_rest' => true,
        'single' => true,
        'type' => 'string',
        'sanitize_callback' => 'wp_kses_post',
        'auth_callback' => function() { return current_user_can('edit_posts'); },
    ]);
}); 