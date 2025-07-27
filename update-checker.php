<?php
/*
Plugin Update Checker for Event Platform
Handles checking for new releases and update notifications
*/

if ( ! defined( 'ABSPATH' ) ) exit;

class EventPlatformUpdateChecker {
    
    private $plugin_slug = 'event-platform';
    private $plugin_name = 'Event Platform';
    private $current_version = '1.0';
    private $update_url = 'https://api.github.com/repos/octocat/Hello-World/releases/latest';
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_update_checker_menu'));
        add_action('admin_init', array($this, 'handle_update_check'));
        add_action('admin_notices', array($this, 'show_update_notices'));
        add_action('wp_ajax_check_plugin_updates', array($this, 'ajax_check_updates'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_styles'));
    }
    
    public function enqueue_styles($hook) {
        if (strpos($hook, 'event-platform') !== false || $hook === 'plugins_page_event-platform-updates') {
            wp_enqueue_style(
                'event-platform-update-checker',
                plugin_dir_url(__FILE__) . 'assets/update-checker.css',
                array(),
                '1.0.0'
            );
        }
    }
    
    public function add_update_checker_menu() {
        // Add to Plugins submenu only
        add_submenu_page(
            'plugins.php',
            'Event Platform Updates',
            'Event Platform Updates',
            'manage_options',
            'event-platform-updates',
            array($this, 'render_update_page')
        );
    }
    
    public function render_update_page() {
        ?>
        <div class="wrap event-platform-updates">
            <h1>Event Platform - Plugin Updates</h1>
            
            <div class="card">
                <h2>Check for Updates</h2>
                <p>Click the button below to check for new releases of the Event Platform plugin.</p>
                
                <div id="update-checker-container">
                    <button id="generate-update-check-btn" class="button button-primary button-large">
                        <span class="dashicons dashicons-update"></span>
                        Check for New Releases
                    </button>
                    
                    <div id="update-status" style="margin-top: 15px; display: none;">
                        <div class="notice notice-info">
                            <p id="status-message">Checking for updates...</p>
                        </div>
                    </div>
                    
                    <div id="update-results" style="margin-top: 15px; display: none;">
                        <div class="notice notice-success">
                            <h3>Update Information</h3>
                            <div id="update-details"></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card" style="margin-top: 20px;">
                <h2>Current Version Information</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">Current Version:</th>
                        <td><strong><?php echo esc_html($this->current_version); ?></strong></td>
                    </tr>
                    <tr>
                        <th scope="row">Plugin Name:</th>
                        <td><?php echo esc_html($this->plugin_name); ?></td>
                    </tr>
                    <tr>
                        <th scope="row">Last Check:</th>
                        <td id="last-check-time">
                            <?php 
                            $last_check = get_option('event_platform_last_update_check');
                            echo $last_check ? date('Y-m-d H:i:s', $last_check) : 'Never';
                            ?>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('#generate-update-check-btn').on('click', function() {
                var $btn = $(this);
                var $status = $('#update-status');
                var $results = $('#update-results');
                var $statusMessage = $('#status-message');
                
                // Disable button and show loading
                $btn.prop('disabled', true).text('Checking...');
                $status.show();
                $results.hide();
                $statusMessage.html('<span class="spinner is-active"></span> Checking for updates...');
                
                // Make AJAX request
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'check_plugin_updates',
                        nonce: '<?php echo wp_create_nonce("event_platform_update_check"); ?>'
                    },
                    success: function(response) {
                        $btn.prop('disabled', false).html('<span class="dashicons dashicons-update"></span> Check for New Releases');
                        
                        if (response.success) {
                            $statusMessage.html('<span class="dashicons dashicons-yes-alt"></span> ' + response.data.message);
                            
                            if (response.data.has_update) {
                                $results.show();
                                $('#update-details').html(response.data.update_info);
                            } else {
                                $results.show();
                                $('#update-details').html('<p>✅ You are using the latest version!</p>');
                            }
                            
                            // Update last check time
                            $('#last-check-time').text(new Date().toLocaleString());
                        } else {
                            $statusMessage.html('<span class="dashicons dashicons-warning"></span> ' + response.data.message);
                        }
                    },
                    error: function() {
                        $btn.prop('disabled', false).html('<span class="dashicons dashicons-update"></span> Check for New Releases');
                        $statusMessage.html('<span class="dashicons dashicons-no-alt"></span> Error checking for updates. Please try again.');
                    }
                });
            });
        });
        </script>
        

        <?php
    }
    
    public function render_settings_page() {
        ?>
        <div class="wrap event-platform-settings">
            <h1>Event Platform Settings</h1>
            
            <div class="card">
                <h2>Plugin Information</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">Plugin Name:</th>
                        <td><strong><?php echo esc_html($this->plugin_name); ?></strong></td>
                    </tr>
                    <tr>
                        <th scope="row">Current Version:</th>
                        <td><strong><?php echo esc_html($this->current_version); ?></strong></td>
                    </tr>
                    <tr>
                        <th scope="row">Plugin Status:</th>
                        <td><span class="dashicons dashicons-yes-alt" style="color: #28a745;"></span> Active</td>
                    </tr>
                </table>
            </div>
            
            <div class="card">
                <h2>Quick Actions</h2>
                <p>Use the buttons below to manage your Event Platform plugin:</p>
                
                <div class="quick-actions">
                    <a href="<?php echo admin_url('admin.php?page=event-platform-updates'); ?>" class="button button-primary">
                        <span class="dashicons dashicons-update"></span>
                        Check for Updates
                    </a>
                    
                    <a href="<?php echo admin_url('edit.php?post_type=event'); ?>" class="button button-secondary">
                        <span class="dashicons dashicons-calendar-alt"></span>
                        Manage Events
                    </a>
                    
                    <a href="<?php echo admin_url('edit.php?post_type=event&page=event-db-status'); ?>" class="button button-secondary">
                        <span class="dashicons dashicons-database"></span>
                        Database Status
                    </a>
                </div>
            </div>
        </div>
        <?php
    }
    
    public function ajax_check_updates() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'event_platform_update_check')) {
            wp_die('Security check failed');
        }
        
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $result = $this->check_for_updates();
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
    
    public function check_for_updates() {
        // Update last check time
        update_option('event_platform_last_update_check', time());
        
        // Check GitHub API for updates
        $response = wp_remote_get($this->update_url, array(
            'timeout' => 15,
            'user-agent' => 'WordPress/' . get_bloginfo('version') . '; ' . get_bloginfo('url'),
            'headers' => array(
                'Accept' => 'application/vnd.github.v3+json'
            )
        ));
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => 'Failed to check for updates: ' . $response->get_error_message()
            );
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code !== 200) {
            return array(
                'success' => false,
                'message' => 'GitHub API returned error code: ' . $response_code
            );
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (!$data || !isset($data['tag_name'])) {
            return array(
                'success' => false,
                'message' => 'No release data found or invalid response from GitHub API'
            );
        }
        
        $latest_version = ltrim($data['tag_name'], 'v');
        $has_update = version_compare($latest_version, $this->current_version, '>');
        
        // Add debug info for testing
        $debug_info = array(
            'current_version' => $this->current_version,
            'latest_version' => $latest_version,
            'has_update' => $has_update,
            'github_response' => $data
        );
        
        if ($has_update) {
            return array(
                'success' => true,
                'message' => 'Update check completed successfully',
                'has_update' => true,
                'update_info' => $this->format_update_info($latest_version, $data['tag_name'], $data['body']),
                'debug' => $debug_info
            );
        } else {
            return array(
                'success' => true,
                'message' => 'Update check completed successfully',
                'has_update' => false,
                'update_info' => 'You are using the latest version!',
                'debug' => $debug_info
            );
        }
    }
    
    private function format_update_info($version, $tag, $description) {
        $html = '<div class="update-info">';
        $html .= '<h4>New Version Available: ' . esc_html($version) . '</h4>';
        $html .= '<p><strong>Version:</strong> ' . esc_html($tag) . '</p>';
        $html .= '<p><strong>Description:</strong></p>';
        $html .= '<div class="update-description">' . wp_kses_post(nl2br($description)) . '</div>';
        $html .= '<p><a href="#" class="button button-primary">Download Update</a></p>';
        $html .= '</div>';
        
        return $html;
    }
    
    public function handle_update_check() {
        // Handle manual update check from admin
        if (isset($_GET['check_updates']) && isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'event_platform_update_check')) {
            $result = $this->check_for_updates();
            
            if ($result['success']) {
                $message = $result['has_update'] ? 'New version available!' : 'You are using the latest version.';
                $type = $result['has_update'] ? 'success' : 'info';
            } else {
                $message = $result['message'];
                $type = 'error';
            }
            
            wp_redirect(add_query_arg(array(
                'page' => 'event-platform-updates',
                'update_check' => $type,
                'message' => urlencode($message)
            ), admin_url('edit.php?post_type=event')));
            exit;
        }
    }
    
    public function show_update_notices() {
        if (isset($_GET['page']) && $_GET['page'] === 'event-platform-updates' && isset($_GET['update_check'])) {
            $type = $_GET['update_check'];
            $message = isset($_GET['message']) ? urldecode($_GET['message']) : '';
            
            if ($message) {
                echo '<div class="notice notice-' . esc_attr($type) . ' is-dismissible">';
                echo '<p>' . esc_html($message) . '</p>';
                echo '</div>';
            }
        }
    }
}

// Initialize the update checker
new EventPlatformUpdateChecker(); 