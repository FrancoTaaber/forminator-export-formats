<?php
/**
 * Plugin uninstall script.
 *
 * This file is executed when the plugin is uninstalled via the WordPress admin.
 * It removes all plugin data from the database.
 *
 * @package Forminator_Export_Formats
 */

// If uninstall not called from WordPress, exit.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Delete plugin options.
delete_option('forminator_export_formats_options');

// Delete any transients.
delete_transient('forminator_export_formats_cache');

// Clean up any temporary export files (if any exist).
$upload_dir = wp_upload_dir();
$temp_dir = $upload_dir['basedir'] . '/forminator-export-formats-temp';

if (is_dir($temp_dir)) {
    $files = glob($temp_dir . '/*');
    foreach ($files as $file) {
        if (is_file($file)) {
            wp_delete_file($file);
        }
    }
    rmdir($temp_dir);
}
