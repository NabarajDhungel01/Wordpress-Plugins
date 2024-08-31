<?php
/*
Plugin Name: Dynamic Post Importer
Description: A plugin to dynamically import posts from an external API.
Version: 1.0
Author: NAVY
Author URI: nabarajdhungel.com.np
Text Domain: dynamic-post-importer
Domain Path: /languages
License: GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

// Include necessary files in the correct order
include_once(plugin_dir_path(__FILE__) . 'includes/post-importer.php'); // Core import functionality
include_once(plugin_dir_path(__FILE__) . 'includes/dynamic-post-importer-debugger.php'); // Logging/debugging
include_once(plugin_dir_path(__FILE__) . 'includes/manual-post-importer.php'); // Manual import functionality
include_once(plugin_dir_path(__FILE__) . 'includes/settings.php'); // Settings page