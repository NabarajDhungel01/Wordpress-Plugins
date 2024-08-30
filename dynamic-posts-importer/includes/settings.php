<?php

// Register the settings page
function dynamic_post_importer_register_settings() {
    register_setting('dynamic_post_importer_settings', 'importer_api_url');
    register_setting('dynamic_post_importer_settings', 'importer_post_count');
    register_setting('dynamic_post_importer_settings', 'importer_custom_post_type_name');

    add_settings_section(
        'dynamic_post_importer_section',
        'Importer Settings',
        null,
        'dynamic-post-importer'
    );

    add_settings_field(
        'importer_api_url',
        'API URL',
        'dynamic_post_importer_api_url_callback',
        'dynamic-post-importer',
        'dynamic_post_importer_section'
    );

    add_settings_field(
        'importer_post_count',
        'Number of Posts to Import',
        'dynamic_post_importer_post_count_callback',
        'dynamic-post-importer',
        'dynamic_post_importer_section'
    );

    add_settings_field(
        'importer_custom_post_type_name',
        'Custom Post Type Name',
        'dynamic_post_importer_post_type_callback',
        'dynamic-post-importer',
        'dynamic_post_importer_section'
    );
}
add_action('admin_init', 'dynamic_post_importer_register_settings');

// Callbacks for settings fields
function dynamic_post_importer_api_url_callback() {
    $api_url = get_option('importer_api_url', '');
    echo '<input type="text" name="importer_api_url" value="' . esc_attr($api_url) . '" class="regular-text">';
}

function dynamic_post_importer_post_count_callback() {
    $post_count = get_option('importer_post_count', 5);
    echo '<input type="number" name="importer_post_count" value="' . esc_attr($post_count) . '" class="small-text">';
}

function dynamic_post_importer_post_type_callback() {
    $post_type_name = get_option('importer_custom_post_type_name', 'imported_post');
    echo '<input type="text" name="importer_custom_post_type_name" value="' . esc_attr($post_type_name) . '" class="regular-text">';
}

// Render the settings page
function dynamic_post_importer_render_settings_page() {
    ?>
    <div class="wrap">
        <h1>Dynamic Post Importer Settings</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('dynamic_post_importer_settings');
            do_settings_sections('dynamic-post-importer');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

// Register the settings page in the admin menu
function dynamic_post_importer_add_settings_page() {
    add_menu_page(
        'Dynamic Post Importer Settings',
        'Post Importer',
        'manage_options',
        'dynamic-post-importer',
        'dynamic_post_importer_render_settings_page',
        'dashicons-admin-post',
        26
    );

    // Add "Import Posts" submenu
    add_submenu_page(
        'dynamic-post-importer',
        'Import Posts',
        'Import Posts',
        'manage_options',
        'dynamic_post_importer_import_posts',
        'dynamic_post_importer_import_posts_page'
    );

    // Add "View Logs" submenu
    add_submenu_page(
        'dynamic-post-importer',
        'View Logs',
        'View Logs',
        'manage_options',
        'dynamic_post_importer_view_logs',
        'dynamic_post_importer_view_logs_page'
    );

    global $submenu;
    if (isset($submenu['dynamic-post-importer'])) {
        $submenu['dynamic-post-importer'][0][0] = 'Settings';
    }
}
add_action('admin_menu', 'dynamic_post_importer_add_settings_page');