<?php

// Register the custom post type
function dynamic_post_importer_register_post_type() {
    $post_type_name = sanitize_title(get_option('importer_custom_post_type_name', 'imported_post'));

    $labels = array(
        'name'               => _x(ucwords(str_replace('_', ' ', $post_type_name)) . 's', 'post type general name'),
        'singular_name'      => _x(ucwords(str_replace('_', ' ', $post_type_name)), 'post type singular name'),
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
    global $wpdb;
    $upload_dir = wp_upload_dir();
    $importer_folder = trailingslashit($upload_dir['basedir']) . 'dynamic-post-importer-featured-images';

    // Batch delete old posts for efficiency
    $old_posts = $wpdb->get_col($wpdb->prepare("
        SELECT ID FROM $wpdb->posts 
        WHERE post_type = %s
    ", $post_type_name));

    if ($old_posts) {
        foreach ($old_posts as $post_id) {
            wp_delete_post($post_id, true); // Force delete each post
            dynamic_post_importer_log('Deleted post ID: ' . $post_id);
        }
    }

    // Delete the folder and its contents
    if (file_exists($importer_folder)) {
        delete_directory($importer_folder);
        dynamic_post_importer_log('Deleted the folder: ' . $importer_folder);
    }
}

// Function to recursively delete a directory
function delete_directory($dir) {
    if (file_exists($dir) && is_dir($dir)) {
        $files = array_diff(scandir($dir), array('.', '..'));
        foreach ($files as $file) {
            $file_path = "$dir/$file";
            if (is_dir($file_path)) {
                delete_directory($file_path);
            } else {
                unlink($file_path);
                dynamic_post_importer_log('Deleted file: ' . $file_path);
            }
        }
        rmdir($dir);
        dynamic_post_importer_log('Deleted directory: ' . $dir);
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

    $upload_dir = wp_upload_dir();
    $importer_folder = trailingslashit($upload_dir['basedir']) . 'dynamic-post-importer-featured-images';

    // Recreate the folder for storing imported images
    if (!file_exists($importer_folder)) {
        wp_mkdir_p($importer_folder);
        dynamic_post_importer_log('Created the folder: ' . $importer_folder);
    }

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
        $original_post_url = isset($post_data['link']) ? esc_url_raw($post_data['link']) : '';

        // Insert the post
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

        // Add custom field for original post URL
        if (!empty($original_post_url)) {
            update_post_meta($post_id, 'original_post_url', $original_post_url);
            dynamic_post_importer_log('Added original post URL to post: ' . $post_title . ' | URL: ' . $original_post_url);
        }
    
        // Fetch and attach the featured image URL
        if (!empty($post_data['featured_media'])) {
            $featured_image_url = get_featured_image_url($post_data['featured_media']);
            if ($featured_image_url) {
                $image_id = dynamic_post_importer_download_image($featured_image_url, $importer_folder, $post_id);
                if ($image_id) {
                    set_post_thumbnail($post_id, $image_id);
                    dynamic_post_importer_log('Attached featured image to post: ' . $post_title . ' | URL: ' . $featured_image_url);
                } else {
                    dynamic_post_importer_log('Failed to download featured image for post: ' . $post_title);
                }
            } else {
                dynamic_post_importer_log('Failed to fetch featured image URL for post: ' . $post_title);
            }
        } else {
            dynamic_post_importer_log('No featured media found for post: ' . $post_title);
        }
    
        dynamic_post_importer_log('Successfully imported post: ' . $post_title);
    }
}

// Function to fetch the featured image URL from the media endpoint
function get_featured_image_url($media_id) {
    $response = wp_remote_get("https://laviehospitality.com.np/wp-json/wp/v2/media/{$media_id}");
    if (is_wp_error($response)) {
        return '';
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if (isset($data['media_details']['sizes']['full']['source_url'])) {
        return $data['media_details']['sizes']['full']['source_url'];
    } elseif (isset($data['source_url'])) {
        return $data['source_url'];
    }

    return '';
}

// Function to download an image and return the attachment ID
function dynamic_post_importer_download_image($image_url, $importer_folder, $post_id) {
    $filename = basename($image_url);
    $filepath = trailingslashit($importer_folder) . $filename;

    // Download and save the image
    $image_data = file_get_contents($image_url);
    if ($image_data === false) {
        dynamic_post_importer_log('Failed to download image: ' . $image_url);
        return false;
    }

    file_put_contents($filepath, $image_data);
    dynamic_post_importer_log('Downloaded image to: ' . $filepath);

    // Get WordPress upload directory details
    $upload_dir = wp_upload_dir();
    $relative_filepath = str_replace($upload_dir['basedir'], '', $filepath);

    // Prepare an array of attachment data
    $attachment_data = array(
        'post_mime_type' => wp_check_filetype($filename, null)['type'],
        'post_title'     => sanitize_file_name($filename),
        'post_content'   => '',
        'post_status'    => 'inherit',
        'guid'           => $upload_dir['baseurl'] . $relative_filepath,
    );

    // Insert the attachment to the media library
    $attachment_id = wp_insert_attachment($attachment_data, $filepath, $post_id);

    if (!is_wp_error($attachment_id)) {
        // Generate the metadata for the attachment and update the database record
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $attachment_metadata = wp_generate_attachment_metadata($attachment_id, $filepath);
        wp_update_attachment_metadata($attachment_id, $attachment_metadata);
        dynamic_post_importer_log('Image uploaded and attached: ' . $filename);
        return $attachment_id;
    }

    dynamic_post_importer_log('Failed to insert attachment: ' . $filename);
    return false;
}

// Function for verbose logging
// function dynamic_post_importer_log($message) {
//     if (defined('WP_DEBUG') && WP_DEBUG) {
//         error_log('[Dynamic Post Importer] ' . $message);
//     }
// }