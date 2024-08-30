<?php

// Register the custom post type
function dynamic_post_importer_register_post_type() {
    $post_type_name = sanitize_title(get_option('importer_custom_post_type_name', 'imported_post'));

    $labels = array(
        'name'               => _x(ucwords(str_replace('_', ' ', $post_type_name)) . 's', 'post type general name'),
        'singular_name'      => _x(ucwords(str_replace('_', ' ', $post_type_name)), 'post type singular name'),
        // Add more labels as needed
    );

    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'publicly_queryable' => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'query_var'          => true,
        'rewrite'            => array('slug' => $post_type_name),
        'capability_type'    => 'post',
        'has_archive'        => true,
        'hierarchical'       => false,
        'menu_position'      => null,
        'supports'           => array('title', 'editor', 'thumbnail', 'excerpt', 'comments'),
    );

    register_post_type($post_type_name, $args);
}
add_action('init', 'dynamic_post_importer_register_post_type');

// Function to delete old posts before importing new ones
function dynamic_post_importer_delete_old_posts($post_type_name) {
    $old_posts = new WP_Query(array(
        'post_type'      => $post_type_name,
        'posts_per_page' => -1,
        'fields'         => 'ids',
    ));

    if ($old_posts->have_posts()) {
        foreach ($old_posts->posts as $post_id) {
            wp_delete_post($post_id, true); // Force delete
        }
    }
}

// Function to import posts from the API
function dynamic_post_importer_import_posts() {
    dynamic_post_importer_log('Starting import process.');

    $api_url = get_option('importer_api_url');
    $post_count = get_option('importer_post_count', 5);
    $post_type_name = sanitize_title(get_option('importer_custom_post_type_name', 'imported_post'));

    if (empty($api_url)) {
        dynamic_post_importer_log('API URL is not set.');
        return;
    }

    dynamic_post_importer_delete_old_posts($post_type_name);

    $response = wp_remote_get(add_query_arg('per_page', intval($post_count), $api_url));
    if (is_wp_error($response)) {
        dynamic_post_importer_log('Failed to fetch posts: ' . $response->get_error_message());
        return;
    }

    $posts = json_decode(wp_remote_retrieve_body($response), true);
    if (empty($posts)) {
        dynamic_post_importer_log('No posts found in the API response.');
        return;
    }

    foreach ($posts as $post_data) {
        $post_title = wp_strip_all_tags($post_data['title']['rendered']);
        $post_content = $post_data['content']['rendered'];

        $post_id = wp_insert_post(array(
            'post_title'   => $post_title,
            'post_content' => $post_content,
            'post_type'    => $post_type_name,
            'post_status'  => 'publish',
        ));

        if (is_wp_error($post_id)) {
            dynamic_post_importer_log('Failed to insert post: ' . $post_title);
            continue;
        }

        dynamic_post_importer_log('Successfully imported post: ' . $post_title);
    }
}