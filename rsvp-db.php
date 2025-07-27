<?php
// Create or update custom RSVP table on plugin activation
// Note: This hook should be called from the main plugin file
function ep_create_rsvp_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'event_rsvps';
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE $table_name (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        event_id BIGINT UNSIGNED NOT NULL,
        user_id BIGINT UNSIGNED DEFAULT 0,
        ticket_order_id BIGINT UNSIGNED,
        status VARCHAR(50),
        email VARCHAR(255),
        full_name VARCHAR(255),
        phone VARCHAR(50),
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY event_id (event_id),
        KEY user_id (user_id),
        KEY email (email)
    ) $charset_collate;";
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    
    // Add missing columns if table already exists
    ep_update_rsvp_table_columns();
}

// Update existing table with new columns
function ep_update_rsvp_table_columns() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'event_rsvps';
    
    // Check if email column exists
    $email_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE 'email'");
    if (empty($email_exists)) {
        $wpdb->query("ALTER TABLE $table_name ADD COLUMN email VARCHAR(255)");
    }
    
    // Check if full_name column exists
    $full_name_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE 'full_name'");
    if (empty($full_name_exists)) {
        $wpdb->query("ALTER TABLE $table_name ADD COLUMN full_name VARCHAR(255)");
    }
    
    // Check if phone column exists
    $phone_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE 'phone'");
    if (empty($phone_exists)) {
        $wpdb->query("ALTER TABLE $table_name ADD COLUMN phone VARCHAR(50)");
    }
    
    // Add email index if it doesn't exist
    $email_index_exists = $wpdb->get_results("SHOW INDEX FROM $table_name WHERE Key_name = 'email'");
    if (empty($email_index_exists)) {
        $wpdb->query("ALTER TABLE $table_name ADD INDEX email (email)");
    }
} 