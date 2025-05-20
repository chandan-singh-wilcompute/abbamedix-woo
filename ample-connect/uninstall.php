<?php
/**
 * Triggered when the plugin is uninstalled.
 *
 * @package Ample_Connect
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    die;
}

// Remove options stored in the database
delete_option('ample_connect_settings');

// Remove user meta data added by the plugin
$users = get_users();
foreach ($users as $user) {
    delete_user_meta($user->ID, 'client_id');
    delete_user_meta($user->ID, 'active_registration_id');
}

// Clean up transients
global $wpdb;
$wpdb->query(
    $wpdb->prepare(
        "
        DELETE FROM $wpdb->options
        WHERE option_name LIKE %s
        ",
        $wpdb->esc_like('_transient_ample_connect_%') . '%'
    )
);
