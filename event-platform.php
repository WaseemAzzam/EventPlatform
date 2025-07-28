<?php
/*
Plugin Name: Event Platform
Description: Test.
Version: 2.1.1
Author: WASEEM_AZZAM
*/

if ( ! defined( 'ABSPATH' ) ) exit;

require_once __DIR__ . '/cpt-event.php';
require_once __DIR__ . '/meta-boxes.php';
require_once __DIR__ . '/meta-fields.php';
require_once __DIR__ . '/woocommerce-integration.php';
require_once __DIR__ . '/rsvp-db.php';
require_once __DIR__ . '/rest-api.php';
require_once __DIR__ . '/update-checker.php';


// Add Organizer role and create RSVP table on plugin activation
register_activation_hook(__FILE__, function() {
    // Create RSVP table
    ep_create_rsvp_table();
    
    // Add Organizer role
    $caps = [
        'read' => true,
        'edit_posts' => true, // Add this to fix admin bar issue
        'organizer' => true, // Custom capability for organizer endpoints
        'edit_events' => true,
        'publish_events' => true,
        'delete_events' => true,
        'list_attendees' => true
    ];
    if (!get_role('organizer')) {
        add_role('organizer', 'Organizer', $caps);
    } else {
        // Ensure the capabilities are present if role already exists
        $role = get_role('organizer');
        foreach ($caps as $cap => $grant) {
            if ($role && !$role->has_cap($cap)) {
                $role->add_cap($cap);
            }
        }
    }
});

// Ensure existing Organizer users have edit_posts capability
add_action('init', function() {
    $organizer_role = get_role('organizer');
    if ($organizer_role && !$organizer_role->has_cap('edit_posts')) {
        $organizer_role->add_cap('edit_posts');
    }
});

// Load custom template for single event pages
add_filter('single_template', function($template) {
    global $post;
    
    if ($post->post_type === 'event') {
        $new_template = plugin_dir_path(__FILE__) . 'templates/single-event.php';
        if (file_exists($new_template)) {
            return $new_template;
        }
    }
    
    return $template;
});

// Build and enqueue React app assets
add_action('wp_enqueue_scripts', function() {
    if (is_singular('event')) {
        wp_enqueue_script('event-spa', plugin_dir_url(__FILE__) . '../../../frontend/dist/assets/index.js', [], '1.0.0', true);
        wp_enqueue_style('event-spa', plugin_dir_url(__FILE__) . '../../../frontend/dist/assets/index.css');
        
        // Add nonce for REST API
        wp_localize_script('event-spa', 'wpApiSettings', array(
            'nonce' => wp_create_nonce('wp_rest'),
            'root' => esc_url_raw(rest_url())
        ));
    }
});

// Enqueue admin scripts for plugin page
add_action('admin_enqueue_scripts', function($hook) {
    if ($hook === 'plugins.php') {
        wp_enqueue_script('event-platform-admin', plugin_dir_url(__FILE__) . 'assets/admin.js', array('jquery'), '1.0.0', true);
        wp_localize_script('event-platform-admin', 'eventPlatformAjax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('event_platform_update_check')
        ));
        wp_enqueue_style('event-platform-admin', plugin_dir_url(__FILE__) . 'assets/admin.css', array(), '1.0.0');
    }
}); 

// Add admin notice to check if table exists
add_action('admin_notices', function() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'event_rsvps';
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
    
    if (!$table_exists) {
        echo '<div class="notice notice-error is-dismissible">';
        echo '<p><strong>Event Platform:</strong> جدول RSVP غير موجود. يرجى إعادة تفعيل البلوجن.</p>';
        echo '</div>';
    } else {
        echo '<div class="notice notice-success is-dismissible">';
        echo '<p><strong>Event Platform:</strong> جدول RSVP موجود بنجاح! ✅</p>';
        echo '</div>';
    }
    
    // Show update checker button for administrators
    if (current_user_can('manage_options') && isset($_GET['post_type']) && $_GET['post_type'] === 'event') {
        echo '<div class="notice notice-info is-dismissible">';
        echo '<p><strong>Event Platform Updates:</strong> ';
        echo '<a href="' . admin_url('admin.php?page=event-platform-updates') . '" class="button button-primary">';
        echo '<span class="dashicons dashicons-update" style="margin-right: 5px;"></span>';
        echo 'Check for Plugin Updates</a>';
        echo '</p>';
        echo '</div>';
    }
    
    // Show update checker notice on plugins page
    if (current_user_can('manage_options') && isset($_GET['page']) && $_GET['page'] === 'plugins.php') {
        echo '<div class="notice notice-info is-dismissible">';
        echo '<p><strong>Event Platform:</strong> ';
        echo '<a href="' . admin_url('admin.php?page=event-platform-updates') . '" class="button button-primary">';
        echo '<span class="dashicons dashicons-update" style="margin-right: 5px;"></span>';
        echo 'Check Event Platform Updates</a>';
        echo '</p>';
        echo '</div>';
    }
}); 

// Add admin menu for database status
add_action('admin_menu', function() {
    add_submenu_page(
        'edit.php?post_type=event',
        'Database Status',
        'Database Status',
        'manage_options',
        'event-db-status',
        function() {
            global $wpdb;
            $table_name = $wpdb->prefix . 'event_rsvps';
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
            
            echo '<div class="wrap">';
            echo '<h1>Event Platform Database Status</h1>';
            
            // Show success message if table was just created
            if (isset($_GET['table_created'])) {
                echo '<div class="notice notice-success is-dismissible">';
                echo '<p><strong>✅ تم إنشاء جدول RSVP بنجاح!</strong></p>';
                echo '</div>';
            }
            
            if ($table_exists) {
                echo '<div class="notice notice-success">';
                echo '<h3>✅ جدول RSVP موجود</h3>';
                echo '<p>اسم الجدول: <code>' . $table_name . '</code></p>';
                
                // Get table structure
                $columns = $wpdb->get_results("SHOW COLUMNS FROM $table_name");
                echo '<h4>هيكل الجدول:</h4>';
                echo '<table class="wp-list-table widefat fixed striped">';
                echo '<thead><tr><th>العمود</th><th>النوع</th><th>NULL</th><th>المفتاح</th><th>الافتراضي</th></tr></thead>';
                echo '<tbody>';
                foreach ($columns as $column) {
                    echo '<tr>';
                    echo '<td><strong>' . $column->Field . '</strong></td>';
                    echo '<td>' . $column->Type . '</td>';
                    echo '<td>' . $column->Null . '</td>';
                    echo '<td>' . $column->Key . '</td>';
                    echo '<td>' . $column->Default . '</td>';
                    echo '</tr>';
                }
                echo '</tbody></table>';
                
                // Get row count
                $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
                echo '<p><strong>عدد السجلات:</strong> ' . $count . '</p>';
                
            } else {
                echo '<div class="notice notice-error">';
                echo '<h3>❌ جدول RSVP غير موجود</h3>';
                echo '<p>اسم الجدول المتوقع: <code>' . $table_name . '</code></p>';
                echo '<p>';
                echo '<a href="' . admin_url('plugins.php') . '" class="button button-primary">إعادة تفعيل البلوجن</a> ';
                echo '<a href="' . wp_nonce_url(admin_url('edit.php?post_type=event&page=event-db-status&create_rsvp_table=1'), 'create_rsvp_table') . '" class="button button-secondary">إنشاء الجدول يدوياً</a>';
                echo '</p>';
                echo '</div>';
            }
            
            echo '</div>';
        }
    );
}); 

// Handle manual table creation
add_action('admin_init', function() {
    if (isset($_GET['create_rsvp_table']) && isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'create_rsvp_table')) {
        ep_create_rsvp_table();
        wp_redirect(admin_url('edit.php?post_type=event&page=event-db-status&table_created=1'));
        exit;
    }
}); 

// Update RSVP table structure on plugin load (for existing installations)
add_action('init', function() {
    if (function_exists('ep_update_rsvp_table_columns')) {
        ep_update_rsvp_table_columns();
    }
});

// Add plugin action links with Generate Button
add_filter('plugin_action_links_' . plugin_basename(__FILE__), function($links) {
    $update_link = '<a href="#" id="event-platform-check-updates" class="check-updates-btn">Check Updates</a>';
    $updates_link = '<a href="' . admin_url('admin.php?page=event-platform-updates') . '">Plugin Updates</a>';
    array_unshift($links, $updates_link);
    array_unshift($links, $update_link);
    return $links;
}); 


