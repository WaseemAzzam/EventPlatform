<?php
// Register custom REST API endpoints for events
add_action('rest_api_init', function() {
    // Allow CORS for frontend app (adjust the domain as needed)
    if (isset(
        $_SERVER['HTTP_ORIGIN']) && preg_match('#^https?://(localhost(:[0-9]+)?|driven-event-ticketingg.local|127.0.0.1(:[0-9]+)?)$#', $_SERVER['HTTP_ORIGIN'])
    ) {
        header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Allow-Headers: Authorization, Content-Type');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE');
    }
    
    // Handle preflight requests
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        status_header(200);
        exit();
    }
    
    $namespace = 'events/v1';

// GET /events/v1/list - Public access for viewing events
register_rest_route($namespace, '/list', [
    'methods' => 'GET',
    'callback' => 'ep_rest_list_events',
    'permission_callback' => '__return_true', // Public access for viewing events
]);

// GET /events/v1/event/<id> - Public access for viewing event details
register_rest_route($namespace, '/event/(?P<id>\\d+)', [
    'methods' => 'GET',
    'callback' => 'ep_rest_event_details',
    'permission_callback' => '__return_true', // Public access for viewing event details
]);

// POST /events/v1/event/<id>/rsvp - Requires authentication
register_rest_route($namespace, '/event/(?P<id>\\d+)/rsvp', [
    'methods' => 'POST',
    'callback' => 'ep_rest_event_rsvp',
    'permission_callback' => 'ep_rest_check_auth_friendly',
]);

// POST /events/v1/event/<id>/verify-ticket - Requires authentication
register_rest_route($namespace, '/event/(?P<id>\\d+)/verify-ticket', [
    'methods' => 'POST',
    'callback' => 'ep_rest_event_verify_ticket',
    'permission_callback' => 'ep_rest_check_auth_friendly',
]);

// GET /events/v1/event/<id>/attendees - Requires organizer or admin
register_rest_route($namespace, '/event/(?P<id>\\d+)/attendees', [
    'methods' => 'GET',
    'callback' => 'ep_rest_event_attendees',
    'permission_callback' => 'ep_rest_check_organizer_or_admin',
]);

// GET /events/v1/event/<id>/has-ticket - Requires authentication
register_rest_route($namespace, '/event/(?P<id>\\d+)/has-ticket', [
    'methods' => 'GET',
    'callback' => 'ep_rest_event_has_ticket',
    'permission_callback' => 'ep_rest_check_auth_friendly',
]);

// GET /events/v1/event/<id>/ticket-status - Requires authentication
register_rest_route($namespace, '/event/(?P<id>\d+)/ticket-status', [
    'methods' => 'GET',
    'callback' => 'ep_rest_event_ticket_status',
    'permission_callback' => 'ep_rest_check_auth_friendly',
]);

// PUBLIC GET /events/v1/events - Public access for viewing events
register_rest_route($namespace, '/events', [
    'methods' => 'GET',
    'callback' => 'ep_rest_list_events',
    'permission_callback' => '__return_true', // Public access for viewing events
]);

// GET /events/v1/current-user - Get current user information (public for checking login status)
register_rest_route($namespace, '/current-user', [
    'methods' => 'GET',
    'callback' => 'ep_rest_current_user',
    'permission_callback' => '__return_true', // Public access for checking login status
]);

// GET /events/v1/test-auth - Simple test endpoint (requires authentication)
register_rest_route($namespace, '/test-auth', [
    'methods' => 'GET',
    'callback' => 'ep_rest_test_auth',
    'permission_callback' => 'ep_rest_check_auth_friendly',
]);


});


// Permission: user must be logged in
function ep_rest_check_auth() {
    return is_user_logged_in();
}

// Permission: user must be logged in and have Organizer role
function ep_rest_check_organizer() {
    return is_user_logged_in() && current_user_can('organizer');
}

// Permission: user must be logged in (with friendly error)
function ep_rest_check_auth_friendly() {
    // First try WordPress session
    if (is_user_logged_in()) {
        return true;
    }
    
    // Check for custom authentication headers
    $user_id = $_SERVER['HTTP_X_WP_USER_ID'] ?? null;
    $user_name = $_SERVER['HTTP_X_WP_USER_NAME'] ?? null;
    $user_email = $_SERVER['HTTP_X_WP_USER_EMAIL'] ?? null;
    $is_admin = $_SERVER['HTTP_X_WP_IS_ADMIN'] ?? '0';
    $is_organizer = $_SERVER['HTTP_X_WP_IS_ORGANIZER'] ?? '0';
    
    if ($user_id && $user_name && $user_email) {
        // Verify user exists in WordPress
        $user = get_user_by('ID', $user_id);
        if ($user && $user->user_email === $user_email) {
            // Set up a temporary user session
            wp_set_current_user($user_id);
            return true;
        }
    }
    
    return new WP_Error(
        'rest_forbidden', 
        'يجب تسجيل الدخول للوصول إلى هذه الميزة. يرجى تسجيل الدخول أولاً.', 
        array('status' => 401)
    );
}

// Permission: user must be organizer or admin (with friendly error)
function ep_rest_check_organizer_or_admin() {
    // First try WordPress session
    if (is_user_logged_in()) {
        if (current_user_can('organizer') || current_user_can('manage_options')) {
            return true;
        }
        return new WP_Error(
            'rest_forbidden', 
            'ليس لديك صلاحية للوصول إلى هذه الميزة. يلزم دور المنظم أو المدير.', 
            array('status' => 403)
        );
    }
    
    // Check for custom authentication headers
    $user_id = $_SERVER['HTTP_X_WP_USER_ID'] ?? null;
    $user_name = $_SERVER['HTTP_X_WP_USER_NAME'] ?? null;
    $user_email = $_SERVER['HTTP_X_WP_USER_EMAIL'] ?? null;
    $is_admin = $_SERVER['HTTP_X_WP_IS_ADMIN'] ?? '0';
    $is_organizer = $_SERVER['HTTP_X_WP_IS_ORGANIZER'] ?? '0';
    
    if ($user_id && $user_name && $user_email) {
        // Verify user exists in WordPress
        $user = get_user_by('ID', $user_id);
        if ($user && $user->user_email === $user_email) {
            // Set up a temporary user session
            wp_set_current_user($user_id);
            
            // Check if user has required permissions
            if ($is_admin === '1' || $is_organizer === '1' || current_user_can('organizer') || current_user_can('manage_options')) {
                return true;
            }
        }
    }
    
    return new WP_Error(
        'rest_forbidden', 
        'يجب تسجيل الدخول للوصول إلى هذه الميزة. يرجى تسجيل الدخول أولاً.', 
        array('status' => 401)
    );
}



// Permission: user must be logged in and own the resource or be admin
function ep_rest_check_owner_or_admin($user_id) {
    if (!is_user_logged_in()) {
        return new WP_Error(
            'rest_forbidden', 
            'يجب تسجيل الدخول للوصول إلى هذه الميزة.', 
            array('status' => 401)
        );
    }
    $current_user_id = get_current_user_id();
    if ($current_user_id != $user_id && !current_user_can('manage_options')) {
        return new WP_Error(
            'rest_forbidden', 
            'ليس لديك صلاحية للوصول إلى هذا المورد.', 
            array('status' => 403)
        );
    }
    return true;
}

// GET /current-user: Get current user information
function ep_rest_current_user() {
    // Debug logging
    error_log('=== Current user endpoint called ===');
    error_log('REQUEST_METHOD: ' . $_SERVER['REQUEST_METHOD']);
    error_log('HTTP_ORIGIN: ' . ($_SERVER['HTTP_ORIGIN'] ?? 'not set'));
    error_log('is_user_logged_in(): ' . (is_user_logged_in() ? 'true' : 'false'));
    
    if (!is_user_logged_in()) {
        error_log('User not logged in, returning empty data');
        return [
            'logged_in' => false,
            'user' => null,
            'roles' => [],
            'is_organizer' => false,
            'is_admin' => false
        ];
    }
    
    $user = wp_get_current_user();
    error_log('User ID: ' . $user->ID);
    error_log('User login: ' . $user->user_login);
    error_log('User roles: ' . print_r($user->roles, true));
    
    $is_organizer = in_array('organizer', $user->roles);
    $is_admin = current_user_can('manage_options');
    
    error_log('Is organizer: ' . ($is_organizer ? 'true' : 'false'));
    error_log('Is admin: ' . ($is_admin ? 'true' : 'false'));
    
    $result = [
        'logged_in' => true,
        'user' => [
            'id' => $user->ID,
            'name' => $user->display_name,
            'email' => $user->user_email,
            'username' => $user->user_login
        ],
        'roles' => $user->roles,
        'is_organizer' => $is_organizer,
        'is_admin' => $is_admin
    ];
    
    error_log('Returning result: ' . print_r($result, true));
    return $result;
}

// GET /test-auth: Simple test endpoint
function ep_rest_test_auth() {
    return [
        'message' => 'Test endpoint working',
        'timestamp' => current_time('mysql'),
        'user_logged_in' => is_user_logged_in(),
        'current_user_id' => get_current_user_id(),
        'user_roles' => wp_get_current_user()->roles ?? []
    ];
}

// GET /list: Paginated list of upcoming events (with caching)
function ep_rest_list_events($request) {
    $paged = max(1, intval($request->get_param('page')));
    $per_page = min(50, max(1, intval($request->get_param('per_page', 10)))); // Limit per_page
    $today = date('Y-m-d');
    
    $cache_key = 'ep_event_list_' . $paged . '_' . $per_page . '_user_' . get_current_user_id();
    $cached = get_transient($cache_key);
    if ($cached !== false) {
        return $cached;
    }
    
    $query = new WP_Query([
        'post_type' => 'event',
        'posts_per_page' => $per_page,
        'paged' => $paged,
        'post_status' => ['publish', 'draft', 'pending', 'private'], // All event statuses
        'orderby' => 'date',
        'order' => 'DESC',
    ]);
    
    $events = [];
    foreach ($query->posts as $post) {
        $is_premium = get_post_meta($post->ID, '_is_premium', true) === '1';
        $has_rsvp = false;
        $has_ticket = false;
        
        // Always set to false for public access (no restrictions)
        $has_rsvp = false;
        $has_ticket = false;
        
        $events[] = [
            'id' => $post->ID,
            'title' => get_the_title($post),
            'date' => get_post_meta($post->ID, 'event_date', true),
            'location' => get_post_meta($post->ID, '_event_location', true),
            'is_premium' => $is_premium,
            'has_rsvp' => $has_rsvp,
            'has_ticket' => $has_ticket,
            'ticket_product_id' => get_post_meta($post->ID, 'linked_ticket_id', true),
        ];
    }
    
    $result = [
        'events' => $events,
        'total' => $query->found_posts,
        'page' => $paged,
        'pages' => $query->max_num_pages,
        'per_page' => $per_page,
    ];
    
    set_transient($cache_key, $result, 5 * MINUTE_IN_SECONDS);
    return $result;
}

// Clear event list cache when an event is published or updated
add_action('save_post_event', function($post_id) {
    global $wpdb;
    $sql = "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_ep_event_list_%' OR option_name LIKE '_transient_timeout_ep_event_list_%'";
    $wpdb->query($sql);
});





// GET /event/<id>: Event details
function ep_rest_event_details($request) {
    $id = intval($request['id']);
    $post = get_post($id);
    
    if (!$post || $post->post_type !== 'event') {
        return new WP_Error(
            'not_found', 
            'الحدث غير موجود أو تم حذفه.', 
            ['status' => 404]
        );
    }
    

    
    $is_premium = get_post_meta($post->ID, '_is_premium', true) === '1';
    $price = get_post_meta($post->ID, 'event_price', true);
    $ticket_product_id = get_post_meta($post->ID, 'linked_ticket_id', true);
    
    // Always set to null for public access (no restrictions)
    $user_status = null;
    
    return [
        'id' => $post->ID,
        'title' => get_the_title($post),
        'content' => apply_filters('the_content', $post->post_content),
        'excerpt' => get_the_excerpt($post),
        'event_custom_description' => get_post_meta($post->ID, '_event_custom_description', true),
        'date' => get_post_meta($post->ID, 'event_date', true),
        'time' => get_post_meta($post->ID, 'event_time', true),
        'location' => get_post_meta($post->ID, '_event_location', true),
        'is_premium' => $is_premium,
        'price' => $price,
        'price_formatted' => $price ? number_format($price, 2) . ' ' . get_woocommerce_currency() : null,
        'google_map_link' => get_post_meta($post->ID, 'google_map_link', true),
        'ticket_product_id' => $ticket_product_id,
        'user_status' => $user_status,
        'created_at' => $post->post_date,
        'modified_at' => $post->post_modified,
    ];
}

// POST /event/<id>/rsvp: RSVP to free event
function ep_rest_event_rsvp($request) {
    global $wpdb;
    $event_id = intval($request['id']);
    $user = wp_get_current_user();
    $user_id = $user->ID ? $user->ID : 0; // Use 0 for guest users
    $table = $wpdb->prefix . 'event_rsvps';
    
    // Check if event exists
    $event = get_post($event_id);
    if (!$event || $event->post_type !== 'event') {
        return new WP_Error(
            'not_found', 
            'الحدث غير موجود أو تم حذفه.', 
            ['status' => 404]
        );
    }
    
    // Check if event is in the future
    $event_date = get_post_meta($event_id, 'event_date', true);
    if ($event_date && strtotime($event_date) < time()) {
        return new WP_Error(
            'event_passed', 
            'لا يمكن التسجيل في حدث انتهى موعده.', 
            ['status' => 400]
        );
    }
    
    // Only allow RSVP for free events
    $is_premium = get_post_meta($event_id, '_is_premium', true) === '1';
    if ($is_premium) {
        return new WP_Error(
            'premium_event', 
            'لا يمكن التسجيل في الأحداث المدفوعة. يرجى شراء تذكرة.', 
            ['status' => 403]
        );
    }
    
    // Check if user already RSVP'd (only for logged in users)
    if ($user_id > 0) {
        $existing_rsvp = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table WHERE event_id = %d AND user_id = %d",
            $event_id, $user_id
        ));
        if ($existing_rsvp) {
            return new WP_Error(
                'already_rsvpd', 
                'لقد قمت بالتسجيل في هذا الحدث مسبقاً.', 
                ['status' => 400]
            );
        }
    }
    
    // Validate input
    $full_name = sanitize_text_field($request->get_param('full_name'));
    $email = sanitize_email($request->get_param('email'));
    $phone = sanitize_text_field($request->get_param('phone'));
    
    // Debug: Log the received data
    error_log('RSVP Debug - Received data: full_name=' . $full_name . ', email=' . $email . ', phone=' . $phone);
    
    if (empty($full_name) || empty($email)) {
        return new WP_Error(
            'invalid_input', 
            'Full name and email are required.', 
            ['status' => 400]
        );
    }
    
    if (!is_email($email)) {
        return new WP_Error(
            'invalid_email', 
            'Invalid email format.', 
            ['status' => 400]
        );
    }
    
    // Check if username already exists for this event (for all users)
    $existing_username = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM $table WHERE event_id = %d AND full_name = %s",
        $event_id, $full_name
    ));
    if ($existing_username) {
        return new WP_Error(
            'username_exists', 
            'This username is already registered for this event.', 
            ['status' => 400]
        );
    }
    
    // For guest users, check if email already RSVP'd
    if ($user_id === 0) {
        $existing_rsvp = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table WHERE event_id = %d AND user_id = %d AND email = %s",
            $event_id, $user_id, $email
        ));
        if ($existing_rsvp) {
            return new WP_Error(
                'already_rsvpd', 
                ' This email is pre-registered for this event.', 
                ['status' => 400]
            );
        }
    }
    
    // Insert RSVP
    $insert_data = [
        'event_id' => $event_id,
        'user_id' => $user_id,
        'ticket_order_id' => null,
        'status' => 'rsvp',
        'created_at' => current_time('mysql'),
    ];
    
    // Add email for guest users
    if ($user_id === 0) {
        $insert_data['email'] = $email;
        $insert_data['full_name'] = $full_name;
        $insert_data['phone'] = $phone;
    }
    
    $insert_result = $wpdb->insert($table, $insert_data);
    
    if ($insert_result === false) {
        return new WP_Error(
            'database_error', 
            'An error occurred while saving the recording. Please try again.', 
            ['status' => 500]
        );
    }
    
    return [
        'success' => true,
        'message' => 'Registration for the event was successful!',
        'rsvp_id' => $wpdb->insert_id
    ];
}

// POST /event/<id>/verify-ticket: Verify WooCommerce ticket and RSVP
function ep_rest_event_verify_ticket($request) {
    global $wpdb;
    $event_id = intval($request['id']);
    $user = wp_get_current_user();
    $order_id = intval($request->get_param('order_id'));
    $table = $wpdb->prefix . 'event_rsvps';
    
    // Check if event exists
    $event = get_post($event_id);
    if (!$event || $event->post_type !== 'event') {
        return new WP_Error(
            'not_found', 
            'The event does not exist or has been deleted.', 
            ['status' => 404]
        );
    }
    
    // Check if event is premium
    $is_premium = get_post_meta($event_id, '_is_premium', true) === '1';
    if (!$is_premium) {
        return new WP_Error(
            'not_premium_event', 
            'This event is free. Use RSVP instead of ticket verification.', 
            ['status' => 400]
        );
    }
    
    // Validate order_id
    if (!$order_id) {
        return new WP_Error(
            'missing_order_id', 
            'معرف الطلب مطلوب.', 
            ['status' => 400]
        );
    }
    
    // Check if WooCommerce is active
    if (!class_exists('WC_Order')) {
        return new WP_Error(
            'woocommerce_missing', 
            'WooCommerce غير مفعل. لا يمكن التحقق من التذاكر.', 
            ['status' => 500]
        );
    }
    
    // Get order and verify ownership
    $order = wc_get_order($order_id);
    if (!$order) {
        return new WP_Error(
            'order_not_found', 
            'الطلب غير موجود.', 
            ['status' => 404]
        );
    }
    
    if ($order->get_user_id() != $user->ID) {
        return new WP_Error(
            'order_not_owned', 
            'هذا الطلب لا يخصك.', 
            ['status' => 403]
        );
    }
    
    // Check if order is completed or processing
    $order_status = $order->get_status();
    if (!in_array($order_status, ['completed', 'processing'])) {
        return new WP_Error(
            'order_not_completed', 
            'الطلب لم يكتمل بعد. يرجى انتظار اكتمال الطلب.', 
            ['status' => 400]
        );
    }
    
    // Check if user already has RSVP for this event
    $existing_rsvp = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM $table WHERE event_id = %d AND user_id = %d",
        $event_id, $user->ID
    ));
    if ($existing_rsvp) {
        return new WP_Error(
            'already_rsvpd', 
            'لقد قمت بالتسجيل في هذا الحدث مسبقاً.', 
            ['status' => 400]
        );
    }
    
    // Verify ticket in order
    $linked_ticket_id = get_post_meta($event_id, 'linked_ticket_id', true);
    if (!$linked_ticket_id) {
        return new WP_Error(
            'no_ticket_product', 
            'لا يوجد منتج تذكرة مرتبط بهذا الحدث.', 
            ['status' => 500]
        );
    }
    
    $has_ticket = false;
    foreach ($order->get_items() as $item) {
        if (method_exists($item, 'get_product_id') && $item->get_product_id() == $linked_ticket_id) {
            $has_ticket = true;
            break;
        }
    }
    
    if (!$has_ticket) {
        return new WP_Error(
            'no_ticket_in_order', 
            'لا توجد تذكرة صالحة لهذا الحدث في الطلب.', 
            ['status' => 403]
        );
    }
    
    // Insert RSVP
    $insert_result = $wpdb->insert($table, [
        'event_id' => $event_id,
        'user_id' => $user->ID,
        'ticket_order_id' => $order_id,
        'status' => 'ticket',
        'created_at' => current_time('mysql'),
    ]);
    
    if ($insert_result === false) {
        return new WP_Error(
            'database_error', 
            'حدث خطأ أثناء حفظ التسجيل. يرجى المحاولة مرة أخرى.', 
            ['status' => 500]
        );
    }
    
    return [
        'success' => true,
        'message' => 'تم التحقق من التذكرة والتسجيل في الحدث بنجاح!',
        'rsvp_id' => $wpdb->insert_id
    ];
}

// GET /event/<id>/attendees: List of RSVPs for organizers
function ep_rest_event_attendees($request) {
    global $wpdb;
    $event_id = intval($request['id']);
    $table = $wpdb->prefix . 'event_rsvps';
    
    // Check if event exists
    $event = get_post($event_id);
    if (!$event || $event->post_type !== 'event') {
        return new WP_Error(
            'not_found', 
            'الحدث غير موجود أو تم حذفه.', 
            ['status' => 404]
        );
    }
    
    // Get attendees directly from RSVP table
    $results = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table 
         WHERE event_id = %d 
         ORDER BY created_at DESC",
        $event_id
    ));
    
    // Format the response
    $attendees = [];
    foreach ($results as $row) {
        // Get user info from WordPress users table if user_id exists
        $user_name = 'مستخدم غير معروف';
        $user_email = $row->email ?: 'غير متوفر';
        
        if ($row->user_id > 0) {
            $user = get_user_by('ID', $row->user_id);
            if ($user) {
                $user_name = $user->display_name ?: $user->user_login;
                $user_email = $user->user_email ?: $row->email ?: 'غير متوفر';
            }
        } else {
            // Use full_name from RSVP table for guest users
            $user_name = $row->full_name ?: 'مستخدم غير معروف';
        }
        
        $attendees[] = [
            'id' => $row->id,
            'user_id' => $row->user_id,
            'user_name' => $user_name,
            'user_email' => $user_email,
            'full_name' => $row->full_name,
            'phone' => $row->phone,
            'ticket_order_id' => $row->ticket_order_id,
            'status' => $row->status,
            'created_at' => $row->created_at,
            'status_text' => $row->status === 'ticket' ? 'تذكرة مدفوعة' : 'RSVP مجاني'
        ];
    }
    
    return [
        'event_id' => $event_id,
        'event_title' => get_the_title($event_id),
        'total_attendees' => count($attendees),
        'attendees' => $attendees
    ];
}

// Check if user has a valid ticket for a premium event
function ep_rest_event_has_ticket($request) {
    $event_id = intval($request['id']);
    $user_id = get_current_user_id();
    $has_ticket = false;
    $order_id = null;
    
    // Check if event exists
    $event = get_post($event_id);
    if (!$event || $event->post_type !== 'event') {
        return new WP_Error(
            'not_found', 
            'الحدث غير موجود أو تم حذفه.', 
            ['status' => 404]
        );
    }
    
    // Check if event is premium
    $is_premium = get_post_meta($event_id, '_is_premium', true) === '1';
    if (!$is_premium) {
        return [
            'has_ticket' => false,
            'is_premium' => false,
            'message' => 'هذا الحدث مجاني'
        ];
    }
    
    // Check for linked ticket product
    $linked_ticket_id = get_post_meta($event_id, 'linked_ticket_id', true);
    if (!$linked_ticket_id) {
        return [
            'has_ticket' => false,
            'is_premium' => true,
            'message' => 'لا يوجد منتج تذكرة مرتبط بهذا الحدث'
        ];
    }
    
    // Check WooCommerce orders
    if (function_exists('wc_get_orders')) {
        $orders = wc_get_orders([
            'customer_id' => $user_id,
            'status' => array('completed', 'processing'),
            'limit' => -1,
        ]);
        
        foreach ($orders as $order) {
            foreach ($order->get_items() as $item) {
                if (method_exists($item, 'get_product_id') && $item->get_product_id() == $linked_ticket_id) {
                    $has_ticket = true;
                    $order_id = $order->get_id();
                    break 2;
                }
            }
        }
    }
    
    return [
        'has_ticket' => $has_ticket,
        'is_premium' => true,
        'order_id' => $order_id,
        'message' => $has_ticket ? 'لديك تذكرة صالحة لهذا الحدث' : 'لا تملك تذكرة لهذا الحدث'
    ];
}

// GET /event/<id>/ticket-status: Return ticket status and order ID for current user
function ep_rest_event_ticket_status($request) {
    $event_id = intval($request['id']);
    $user_id = get_current_user_id();
    
    // Check if event exists
    $event = get_post($event_id);
    if (!$event || $event->post_type !== 'event') {
        return new WP_Error(
            'not_found', 
            'الحدث غير موجود أو تم حذفه.', 
            ['status' => 404]
        );
    }
    
    // Check if event is premium
    $is_premium = get_post_meta($event_id, '_is_premium', true) === '1';
    if (!$is_premium) {
        return [
            'has_ticket' => false,
            'is_premium' => false,
            'order_id' => null,
            'message' => 'هذا الحدث مجاني'
        ];
    }
    
    // Check for linked ticket product
    $linked_ticket_id = get_post_meta($event_id, 'linked_ticket_id', true);
    if (!$linked_ticket_id) {
        return [
            'has_ticket' => false,
            'is_premium' => true,
            'order_id' => null,
            'message' => 'لا يوجد منتج تذكرة مرتبط بهذا الحدث'
        ];
    }
    
    // Check WooCommerce orders
    if (function_exists('wc_get_orders')) {
        $orders = wc_get_orders([
            'customer_id' => $user_id,
            'status' => array('completed', 'processing'),
            'limit' => -1,
        ]);
        
        foreach ($orders as $order) {
            foreach ($order->get_items() as $item) {
                if (method_exists($item, 'get_product_id') && $item->get_product_id() == $linked_ticket_id) {
                    return [
                        'has_ticket' => true,
                        'is_premium' => true,
                        'order_id' => $order->get_id(),
                        'order_status' => $order->get_status(),
                        'message' => 'لديك تذكرة صالحة لهذا الحدث'
                    ];
                }
            }
        }
    }
    
    return [
        'has_ticket' => false,
        'is_premium' => true,
        'order_id' => null,
        'message' => 'لا تملك تذكرة لهذا الحدث'
    ];
} 

// Add security headers for REST API
add_action('rest_api_init', function() {
    // Add security headers
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
    
    // Rate limiting (basic implementation)
    $user_id = get_current_user_id();
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $rate_limit_key = 'ep_rate_limit_' . md5($ip . $user_id);
    $rate_limit_count = get_transient($rate_limit_key);
    
    if ($rate_limit_count === false) {
        set_transient($rate_limit_key, 1, 60); // 1 minute window
    } else if ($rate_limit_count > 100) { // 100 requests per minute
        wp_die('Rate limit exceeded. Please try again later.', 'Rate Limit Exceeded', ['response' => 429]);
    } else {
        set_transient($rate_limit_key, $rate_limit_count + 1, 60);
    }
});

// Add CORS headers for frontend
add_action('init', function() {
    if (isset($_SERVER['HTTP_ORIGIN'])) {
        $allowed_origins = [
            'http://localhost:5173',
            'http://localhost:3000',
            'http://driven-event-ticketingg.local',
            'https://driven-event-ticketingg.local'
        ];
        
        if (in_array($_SERVER['HTTP_ORIGIN'], $allowed_origins)) {
            header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
            header('Access-Control-Allow-Credentials: true');
            header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type, Authorization, X-WP-Nonce');
        }
    }
    
    // Handle preflight requests
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        status_header(200);
        exit();
    }
});

// Add nonce verification for authenticated endpoints
add_action('rest_api_init', function() {
    // Add nonce verification for POST requests (excluding RSVP endpoints)
    add_filter('rest_pre_dispatch', function($result, $server, $request) {
        if ($request->get_method() === 'POST') {
            // Skip nonce verification for RSVP endpoints
            $route = $request->get_route();
            if (strpos($route, '/events/v1/event/') !== false && strpos($route, '/rsvp') !== false) {
                return $result; // Allow RSVP without nonce
            }
            
            $nonce = $request->get_header('X-WP-Nonce');
            if (!$nonce || !wp_verify_nonce($nonce, 'wp_rest')) {
                return new WP_Error(
                    'rest_forbidden',
                    'Nonce verification failed.',
                    ['status' => 403]
                );
            }
        }
        return $result;
    }, 10, 3);
});

// Log API errors for debugging
add_action('rest_api_init', function() {
    add_filter('rest_pre_dispatch', function($result, $server, $request) {
        if (is_wp_error($result)) {
            error_log('Event Platform API Error: ' . $result->get_error_message() . ' - Endpoint: ' . $request->get_route());
        }
        return $result;
    }, 10, 3);
}); 