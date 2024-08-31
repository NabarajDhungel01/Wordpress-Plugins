<?php

// Log messages to a custom log file
function dynamic_post_importer_log($message) {
    $log_file = plugin_dir_path(__FILE__) . 'dynamic-post-importer.log';
    $timestamp = date('Y-m-d H:i:s');
    $formatted_message = "[$timestamp] $message" . PHP_EOL;

    $existing_content = '';
    if (file_exists($log_file)) {
        $existing_content = file_get_contents($log_file);
    }

    $new_content = $formatted_message . $existing_content;
    file_put_contents($log_file, $new_content);
}

// Display the logs page
function dynamic_post_importer_view_logs_page() {
    $log_file = plugin_dir_path(__FILE__) . 'dynamic-post-importer.log';

    echo '<div class="wrap">';
    echo '<h1>View Logs</h1>';

    if (file_exists($log_file)) {
        $log_content = file_get_contents($log_file);
        echo '<textarea readonly style="width: 100%; height: 80vh; overflow:scroll;">' . esc_textarea($log_content) . '</textarea>';
    } else {
        echo '<p>No logs found.</p>';
    }

    echo '</div>';
}