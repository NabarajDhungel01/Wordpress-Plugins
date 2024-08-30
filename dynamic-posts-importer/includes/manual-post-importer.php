<?php

// Add a manual trigger for post imports
function dynamic_post_importer_manual_trigger() {
    if (isset($_GET['dynamic_post_importer_import']) && $_GET['dynamic_post_importer_import'] == '1') {
        dynamic_post_importer_import_posts();
        dynamic_post_importer_log('Manual import triggered.');
        wp_redirect(admin_url('edit.php?post_type=' . sanitize_title(get_option('importer_custom_post_type_name', 'imported_post'))));
        exit;
    }
}
add_action('admin_init', 'dynamic_post_importer_manual_trigger');

function dynamic_post_importer_import_posts_page() {
    ?>
    <div class="wrap">
        <h1>Import Posts</h1>
        <p>Click the button below to manually import posts from the configured API.</p>
        <a href="<?php echo esc_url(admin_url('admin.php?page=dynamic_post_importer_import_posts&dynamic_post_importer_import=1')); ?>" class="button button-primary">Import Now</a>
    </div>
    <?php
}