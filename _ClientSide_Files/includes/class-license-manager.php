<?php
/**
 * License Manager Class
 * 
 * Main license management functionality
 * 
 * @package Insurance_CRM
 * @author  Anadolu Birlik
 * @since   1.1.3
 */

if (!defined('ABSPATH')) {
    exit;
}

class Insurance_CRM_License_Manager {
    
    /**
     * Plugin version
     */
    private $version;
    
    /**
     * License API instance
     */
    public $license_api;
    
    /**
     * Grace period in days
     */
    private $grace_period_days;

    /**
     * Constructor
     * 
     * @param string $version Plugin version
     */
    public function __construct($version) {
        $this->version = $version;
        $this->grace_period_days = 7; // 1 week grace period
        
        // Initialize license API
        if (class_exists('Insurance_CRM_License_API')) {
            $this->license_api = new Insurance_CRM_License_API();
        }
        
        // Hook into WordPress
        add_action('init', array($this, 'init'));
        add_action('admin_init', array($this, 'handle_license_form_submission'));
        add_action('admin_notices', array($this, 'show_license_notices'));
        
        // AJAX handlers
        add_action('wp_ajax_validate_license', array($this, 'ajax_validate_license'));
        add_action('wp_ajax_nopriv_validate_license', array($this, 'ajax_validate_license'));
        add_action('wp_login', array($this, 'validate_license_on_login'), 10, 2);
        
        // Periodic license check (every 60 minutes)
        add_action('insurance_crm_periodic_license_check', array($this, 'perform_periodic_license_check'));
        if (!wp_next_scheduled('insurance_crm_periodic_license_check')) {
            wp_schedule_event(time(), 'insurance_crm_60_minutes', 'insurance_crm_periodic_license_check');
        }
        
        // Daily license logging (every 24 hours)
        add_action('insurance_crm_daily_license_log', array($this, 'perform_daily_license_logging'));
        if (!wp_next_scheduled('insurance_crm_daily_license_log')) {
            wp_schedule_event(time(), 'daily', 'insurance_crm_daily_license_log');
        }
        
        // Add custom cron schedules
        add_filter('cron_schedules', array($this, 'add_custom_cron_schedules'));
    }

    /**
     * Initialize license manager
     */
    public function init() {
        // Check license status if needed
        $this->maybe_check_license_status();
        
        // Add user limit enforcement hooks
        add_action('wp', array($this, 'maybe_enforce_user_limit'));
        add_action('admin_notices', array($this, 'show_user_limit_warnings'));
    }

    /**
     * Maybe enforce user limit on specific pages
     */
    public function maybe_enforce_user_limit() {
        // Skip if in admin area - admin restrictions are handled by module validator
        if (is_admin()) {
            return;
        }
        
        // Skip if user limit is not exceeded
        if (!$this->is_user_limit_exceeded()) {
            return;
        }
        
        // Get current view parameter
        $current_view = isset($_GET['view']) ? sanitize_text_field($_GET['view']) : '';
        
        // Always allow license management, user management, and license restriction pages
        $always_allowed_views = array(
            'license-management',
            'license_management',
            'user-management', 
            'user_management',
            'all_personnel',
            'personnel',
            'users',
            'license-restriction'
        );
        
        // If accessing an allowed view or no specific view, allow access
        if (empty($current_view) || in_array($current_view, $always_allowed_views)) {
            return;
        }
        
        // For any other view when user limit is exceeded, enforce restriction
        $this->enforce_user_limit();
    }

    /**
     * Show user limit warnings in admin
     */
    public function show_user_limit_warnings() {
        if (is_admin() && current_user_can('manage_options')) {
            $warning = $this->get_user_limit_warning();
            if ($warning) {
                echo '<div class="notice notice-warning is-dismissible">';
                echo '<p><strong>Lisans Uyarısı:</strong> ' . esc_html($warning) . '</p>';
                echo '</div>';
            }
        }
    }

    /**
     * Check if license is valid
     * 
     * @return bool True if license is valid
     */
    public function is_license_valid() {
        // Check if license bypass is enabled
        if ($this->license_api && $this->license_api->is_license_bypassed()) {
            return true;
        }

        $license_key = get_option('insurance_crm_license_key', '');
        if (empty($license_key)) {
            return false;
        }

        $license_status = get_option('insurance_crm_license_status', 'inactive');
        return $license_status === 'active';
    }

    /**
     * Check if license is in grace period
     * 
     * @return bool True if in grace period
     */
    public function is_in_grace_period() {
        $license_status = get_option('insurance_crm_license_status', 'inactive');
        if ($license_status !== 'expired') {
            return false;
        }

        $license_expiry = get_option('insurance_crm_license_expiry', '');
        if (empty($license_expiry)) {
            return false;
        }

        $expiry_date = strtotime($license_expiry);
        $grace_period_end = $expiry_date + ($this->grace_period_days * 24 * 60 * 60);
        
        $is_in_grace = time() <= $grace_period_end;
        
        // Update can_access_data logic to include grace period
        return $is_in_grace;
    }

    /**
     * Get remaining grace period days
     * 
     * @return int Days remaining in grace period
     */
    public function get_grace_period_days_remaining() {
        if (!$this->is_in_grace_period()) {
            return 0;
        }

        $license_expiry = get_option('insurance_crm_license_expiry', '');
        $expiry_date = strtotime($license_expiry);
        $grace_period_end = $expiry_date + ($this->grace_period_days * 24 * 60 * 60);
        
        return ceil(($grace_period_end - time()) / (24 * 60 * 60));
    }

    /**
     * Check if license expires within specified days
     * 
     * @param int $days Number of days to check (default: 3)
     * @return bool True if license expires within specified days
     */
    public function is_license_expiring_soon($days = 3) {
        $license_status = get_option('insurance_crm_license_status', 'inactive');
        
        // Only check for active licenses
        if ($license_status !== 'active') {
            return false;
        }

        $license_expiry = get_option('insurance_crm_license_expiry', '');
        if (empty($license_expiry)) {
            return false;
        }

        $expiry_date = strtotime($license_expiry);
        $current_time = time();
        $days_until_expiry = ceil(($expiry_date - $current_time) / (24 * 60 * 60));
        
        // Check if license expires within the specified days but hasn't expired yet
        return $days_until_expiry <= $days && $days_until_expiry > 0;
    }

    /**
     * Get days remaining until license expires
     * 
     * @return int Days remaining until expiry (0 if expired or no expiry date)
     */
    public function get_days_until_expiry() {
        $license_expiry = get_option('insurance_crm_license_expiry', '');
        if (empty($license_expiry)) {
            return 0;
        }

        $expiry_date = strtotime($license_expiry);
        $current_time = time();
        $days_until_expiry = ceil(($expiry_date - $current_time) / (24 * 60 * 60));
        
        return max(0, $days_until_expiry);
    }

    /**
     * Check if license allows access to data
     * 
     * @return bool True if data access is allowed
     */
    public function can_access_data() {
        // Check if access is explicitly restricted (for expired licenses past grace period)
        if (get_option('insurance_crm_license_access_restricted', false)) {
            return false;
        }
        
        // Allow access for valid licenses or those in grace period
        return $this->is_license_valid() || $this->is_in_grace_period();
    }

    /**
     * Get current user count
     * 
     * @return int Number of active users
     */
    public function get_current_user_count() {
        global $wpdb;
        
        // Sadece aktif temsilcileri say
        $active_representatives = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) 
            FROM {$wpdb->prefix}insurance_crm_representatives 
            WHERE status = %s
        ", 'active'));
        
        // Sadece CRM kullanıcıları olan admin'leri say
        $admin_users = get_users(array(
            'role' => 'administrator',
            'meta_query' => array(
                array(
                    'key' => 'wp_capabilities',
                    'value' => 'insurance_representative',
                    'compare' => 'LIKE'
                )
            )
        ));
        
        $total_active_users = intval($active_representatives) + count($admin_users);
        
        return $total_active_users;
    }

    /**
     * Check if user limit is exceeded
     * 
     * @return bool True if limit is exceeded
     */
    public function is_user_limit_exceeded() {
        $user_limit = get_option('insurance_crm_license_user_limit', 5);
        $current_users = $this->get_current_user_count();
        
        return $current_users > $user_limit;
    }

    /**
     * Enforce user limit with warning and redirect
     * Should be called on pages that need user limit enforcement
     * 
     * @return void Redirects if limit exceeded
     */
    public function enforce_user_limit() {
        if ($this->is_user_limit_exceeded()) {
            $user_limit = get_option('insurance_crm_license_user_limit', 5);
            $current_users = $this->get_current_user_count();
            
            // Store restriction details for the page
            set_transient('insurance_crm_restriction_details_' . get_current_user_id(), array(
                'type' => 'user_limit',
                'current_users' => $current_users,
                'max_users' => $user_limit,
                'message' => sprintf(
                    'Kullanıcı sayısını aştınız! Mevcut kullanıcı sayısı: %d, Lisansınızın izin verdiği maksimum kullanıcı sayısı: %d. Sadece kullanıcı yönetimi ve lisans yönetimi sayfalarına erişebilirsiniz. Lütfen kullanıcı sayısını azaltın veya yeni lisans satın alın.',
                    $current_users,
                    $user_limit
                )
            ), 300); // 5 minutes
            
            // Redirect to license restriction page with user limit details
            $redirect_url = add_query_arg(array(
                'view' => 'license-restriction',
                'restriction' => 'user_limit',
                'current_users' => $current_users,
                'max_users' => $user_limit
            ), home_url());
            
            wp_redirect($redirect_url);
            exit;
        }
    }

    /**
     * Check if a specific view/page exists
     * 
     * @param string $view_name View parameter name
     * @return bool True if page exists
     */
    private function page_exists($view_name) {
        // Simple check - in a real implementation you'd check against your route handlers
        return in_array($view_name, array('all_personnel', 'personnel', 'users'));
    }

    /**
     * Get user limit warning message
     * 
     * @return string|false Warning message if limit exceeded, false otherwise
     */
    public function get_user_limit_warning() {
        if (!$this->is_user_limit_exceeded()) {
            return false;
        }
        
        $user_limit = get_option('insurance_crm_license_user_limit', 5);
        $current_users = $this->get_current_user_count();
        
        return sprintf(
            'Uyarı: Kullanıcı sayısını aştınız! Mevcut: %d kullanıcı, Lisans limiti: %d kullanıcı. Lütfen kullanıcı sayısını azaltın veya lisansınızı yükseltin.',
            $current_users,
            $user_limit
        );
    }

    /**
     * Validate license (without saving)
     * 
     * @param string $license_key License key to validate
     * @return bool True if license is valid
     */
    public function validate_license($license_key) {
        if (!$this->license_api) {
            return false;
        }

        $response = $this->license_api->validate_license($license_key);
        
        // Handle WP_Error objects
        if (is_object($response) && get_class($response) === 'WP_Error') {
            return false;
        }
        
        return is_array($response) && $response['status'] === 'active';
    }

    /**
     * Activate license
     * 
     * @param string $license_key License key to activate
     * @return array Result of activation
     */
    public function activate_license($license_key) {
        if (!$this->license_api) {
            return array(
                'success' => false,
                'message' => 'Lisans API sınıfı yüklenemedi'
            );
        }

        $response = $this->license_api->validate_license($license_key);
        
        // Handle WP_Error objects
        if (is_object($response) && get_class($response) === 'WP_Error') {
            // Deactivate any existing license on validation failure
            $this->deactivate_license_completely();
            return array(
                'success' => false,
                'message' => 'Lisans doğrulaması başarısız: ' . $response->get_error_message()
            );
        }
        
        if (is_array($response) && $response['status'] === 'active') {
            // Save license information
            update_option('insurance_crm_license_key', $license_key);
            update_option('insurance_crm_license_status', 'active');
            update_option('insurance_crm_license_type', $response['license_type'] ?? 'monthly');
            update_option('insurance_crm_license_package', $response['license_package'] ?? '');
            update_option('insurance_crm_license_type_description', $response['license_type_description'] ?? '');
            update_option('insurance_crm_license_expiry', $response['expires_on'] ?? '');
            update_option('insurance_crm_license_user_limit', $response['user_limit'] ?? 5);
            update_option('insurance_crm_license_modules', $response['modules'] ?? array());
            update_option('insurance_crm_license_access_restricted', false);
            update_option('insurance_crm_license_last_check', current_time('mysql'));
            
            error_log('[LISANS DEBUG] License activated successfully: ' . $license_key);
            
            return array(
                'success' => true,
                'message' => 'Lisans başarıyla etkinleştirildi'
            );
        } else {
            // Deactivate any existing license on validation failure
            $this->deactivate_license_completely();
            
            $message = 'Lisans etkinleştirilemedi - lisans anahtarı geçersiz veya sunucuda bulunamadı';
            if (is_array($response) && isset($response['message'])) {
                $message = $response['message'];
            }
            
            error_log('[LISANS DEBUG] License activation failed: ' . $message);
            
            return array(
                'success' => false,
                'message' => $message
            );
        }
    }

    /**
     * Deactivate license
     * 
     * @return array Result of deactivation
     */
    public function deactivate_license() {
        update_option('insurance_crm_license_status', 'inactive');
        
        return array(
            'success' => true,
            'message' => 'Lisans devre dışı bırakıldı'
        );
    }

    /**
     * Maybe check license status (if not checked recently)
     */
    private function maybe_check_license_status() {
        $last_check = get_option('insurance_crm_license_last_check', '');
        
        // Check every 4 hours
        if (empty($last_check) || 
            strtotime($last_check) < (time() - 4 * 60 * 60)) {
            $this->perform_license_check();
        }
    }

    /**
     * Perform license status check with enhanced validation
     */
    public function perform_license_check() {
        if (!$this->license_api) {
            return;
        }

        $license_key = get_option('insurance_crm_license_key', '');
        if (empty($license_key)) {
            // No license key, deactivate any existing license
            $this->deactivate_license_completely();
            return;
        }

        $response = $this->license_api->check_license_status($license_key);
        
        // Handle WP_Error objects (communication errors)
        if (is_object($response) && get_class($response) === 'WP_Error') {
            error_log('[LISANS DEBUG] License check communication error: ' . $response->get_error_message());
            // Don't update status on communication error, keep last known status
            return; 
        }
        
        // Handle successful server response
        if (is_array($response) && !isset($response['offline'])) {
            $server_status = $response['status'] ?? 'invalid';
            
            // If server says license is invalid, deleted, or expired, deactivate immediately
            if (in_array($server_status, array('invalid', 'deleted', 'not_found', 'inactive'))) {
                error_log('[LISANS DEBUG] Server reported license as invalid/deleted: ' . $server_status);
                $this->deactivate_license_completely();
                return;
            }
            
            // If server says expired, update status but keep some data for grace period
            if ($server_status === 'expired') {
                update_option('insurance_crm_license_status', 'expired');
                if (isset($response['expires_on'])) {
                    update_option('insurance_crm_license_expiry', $response['expires_on']);
                }
                // Check if grace period has ended
                if (!$this->is_in_grace_period()) {
                    error_log('[LISANS DEBUG] License expired and grace period ended');
                    // Don't completely deactivate, but restrict access
                    update_option('insurance_crm_license_access_restricted', true);
                }
            } else {
                // License is active, update all information
                update_option('insurance_crm_license_status', $server_status);
                update_option('insurance_crm_license_access_restricted', false);
                
                if (isset($response['expires_on'])) {
                    update_option('insurance_crm_license_expiry', $response['expires_on']);
                }
                if (isset($response['user_limit'])) {
                    update_option('insurance_crm_license_user_limit', $response['user_limit']);
                }
                if (isset($response['modules'])) {
                    update_option('insurance_crm_license_modules', $response['modules']);
                }
                if (isset($response['license_type'])) {
                    update_option('insurance_crm_license_type', $response['license_type']);
                }
                if (isset($response['license_package'])) {
                    update_option('insurance_crm_license_package', $response['license_package']);
                }
                if (isset($response['license_type_description'])) {
                    update_option('insurance_crm_license_type_description', $response['license_type_description']);
                }
            }
        }
        
        update_option('insurance_crm_license_last_check', current_time('mysql'));
    }

    /**
     * Daily license check (deprecated, use periodic check)
     */
    public function perform_daily_license_check() {
        $this->perform_license_check();
    }
    
    /**
     * Periodic license check (every 4 hours)
     */
    public function perform_periodic_license_check() {
        error_log('[LISANS DEBUG] Performing periodic license check (4-hour interval)');
        $this->perform_license_check();
    }

    /**
     * Completely deactivate license and clear all data
     */
    public function deactivate_license_completely() {
        error_log('[LISANS DEBUG] Completely deactivating license - clearing all data');
        
        update_option('insurance_crm_license_status', 'inactive');
        update_option('insurance_crm_license_key', '');
        update_option('insurance_crm_license_type', '');
        update_option('insurance_crm_license_package', '');
        update_option('insurance_crm_license_type_description', '');
        update_option('insurance_crm_license_expiry', '');
        update_option('insurance_crm_license_user_limit', 5);
        update_option('insurance_crm_license_modules', array());
        update_option('insurance_crm_license_access_restricted', true);
        update_option('insurance_crm_license_last_check', current_time('mysql'));
    }

    /**
     * Add custom cron schedules
     */
    public function add_custom_cron_schedules($schedules) {
        $schedules['insurance_crm_60_minutes'] = array(
            'interval' => 60 * MINUTE_IN_SECONDS,
            'display' => __('Every 60 Minutes (Insurance CRM License Check)')
        );
        return $schedules;
    }

    /**
     * Validate license on user login
     * 
     * @param string $user_login Username
     * @param WP_User $user User object
     */
    public function validate_license_on_login($user_login, $user) {
        // Only check for insurance users
        if (!in_array('insurance_representative', $user->roles) && 
            !in_array('administrator', $user->roles)) {
            return;
        }

        error_log('[LISANS DEBUG] User login detected, performing license validation: ' . $user_login);
        
        // Perform immediate license check on every login
        $this->perform_license_check();
        
        // Get license details for logging
        $license_status = get_option('insurance_crm_license_status', 'inactive');
        $license_key = get_option('insurance_crm_license_key', '');
        $license_expiry = get_option('insurance_crm_license_expiry', '');
        $is_restricted = get_option('insurance_crm_license_access_restricted', false);
        $is_bypassed = $this->license_api ? $this->license_api->is_license_bypassed() : false;
        
        // Log license validation result to database
        $this->log_license_validation_result($user->ID, array(
            'user_login' => $user_login,
            'license_status' => $license_status,
            'license_key_partial' => !empty($license_key) ? substr($license_key, 0, 8) . '...' : 'None',
            'license_expiry' => $license_expiry,
            'is_restricted' => $is_restricted,
            'is_bypassed' => $is_bypassed,
            'validation_time' => current_time('mysql'),
            'ip_address' => $this->get_client_ip()
        ));
        
        // If license is invalid or access is restricted, log them out
        if (!$this->can_access_data()) {
            error_log('[LISANS DEBUG] License check failed on login - Status: ' . $license_status . ', Restricted: ' . ($is_restricted ? 'Yes' : 'No'));
            
            // Allow access only to license management for expired/invalid licenses
            if ($license_status !== 'active' && !$this->is_in_grace_period()) {
                // Don't log them out, but they'll be redirected to license page by access control
                error_log('[LISANS DEBUG] User will be restricted to license management only');
            }
        } else {
            error_log('[LISANS DEBUG] License validation successful for user: ' . $user_login);
        }
    }

    /**
     * Handle license form submission
     */
    public function handle_license_form_submission() {
        if (!isset($_POST['insurance_crm_license_nonce']) || 
            !wp_verify_nonce($_POST['insurance_crm_license_nonce'], 'insurance_crm_license')) {
            return;
        }

        if (!current_user_can('manage_options')) {
            return;
        }

        $action = sanitize_text_field($_POST['insurance_crm_license_action'] ?? '');
        
        if ($action === 'activate') {
            $license_key = sanitize_text_field($_POST['insurance_crm_license_key'] ?? '');
            $result = $this->activate_license($license_key);
            
            if ($result['success']) {
                add_settings_error('insurance_crm_license', 'license_activated', 
                    $result['message'], 'updated');
            } else {
                add_settings_error('insurance_crm_license', 'license_activation_failed', 
                    $result['message'], 'error');
            }
        } elseif ($action === 'deactivate') {
            $result = $this->deactivate_license();
            add_settings_error('insurance_crm_license', 'license_deactivated', 
                $result['message'], 'updated');
        }
    }

    /**
     * Get license notices for frontend display
     * 
     * @return array Array of notices
     */
    public function get_frontend_license_notices() {
        $notices = array();
        $license_status = get_option('insurance_crm_license_status', 'inactive');
        
        if ($license_status === 'inactive' || $license_status === 'invalid') {
            $notices[] = array(
                'type' => 'warning',
                'message' => '<strong>Insurance CRM:</strong> Lisansınız etkin değil. Lütfen lisansınızı etkinleştirin.'
            );
        } elseif ($license_status === 'expired') {
            if ($this->is_in_grace_period()) {
                $days_remaining = $this->get_grace_period_days_remaining();
                $notices[] = array(
                    'type' => 'error',
                    'message' => '<strong>Insurance CRM:</strong> Lisansınızın süresi dolmuştur. Lütfen ' . $days_remaining . ' gün içinde ödemenizi yaparak yenileyin.'
                );
            } else {
                $notices[] = array(
                    'type' => 'error',
                    'message' => '<strong>Insurance CRM:</strong> Lisansınızın süresi dolmuştur ve ek kullanım süreniz sona ermiştir. Uygulamamızı kullanabilmek için lütfen ödemenizi yapın ve lisansınızı yenileyin.'
                );
            }
        }

        // Check user limit
        if ($this->is_user_limit_exceeded()) {
            $notices[] = array(
                'type' => 'warning',
                'message' => '<strong>Insurance CRM:</strong> Kullanıcı sayısı sınırı aşıldı. Mevcut: ' . $this->get_current_user_count() . ', Limit: ' . get_option('insurance_crm_license_user_limit', 5) . '. Lütfen lisansınızı yükseltin.'
            );
        }

        return $notices;
    }

    /**
     * Show license notices in admin
     */
    public function show_license_notices() {
        // Only show on CRM pages
        if (!isset($_GET['page']) || strpos($_GET['page'], 'insurance-crm') === false) {
            return;
        }

        // Skip on license settings page to avoid duplicate notices
        if ($_GET['page'] === 'insurance-crm-license') {
            return;
        }

        $license_status = get_option('insurance_crm_license_status', 'inactive');
        
        if ($license_status === 'inactive' || $license_status === 'invalid') {
            echo '<div class="notice notice-warning">';
            echo '<p><strong>Insurance CRM:</strong> Lisansınız etkin değil. ';
            echo '<a href="' . admin_url('admin.php?page=insurance-crm-license') . '">Lisans yönetimi sayfasına gidin</a>';
            echo ' ve lisansınızı etkinleştirin.</p>';
            echo '</div>';
        } elseif ($license_status === 'expired') {
            if ($this->is_in_grace_period()) {
                $days_remaining = $this->get_grace_period_days_remaining();
                echo '<div class="notice notice-error">';
                echo '<p><strong>Insurance CRM:</strong> Lisansınızın süresi dolmuştur. ';
                echo 'Ek kullanım süreniz ' . $days_remaining . ' gün. ';
                echo 'Lütfen <a href="' . admin_url('admin.php?page=insurance-crm-license') . '">lisansınızı yenileyin</a>.</p>';
                echo '</div>';
            } else {
                echo '<div class="notice notice-error">';
                echo '<p><strong>Insurance CRM:</strong> Lisansınızın süresi dolmuştur ve ek kullanım süreniz sona ermiştir. ';
                echo 'Uygulamayı kullanabilmek için <a href="' . admin_url('admin.php?page=insurance-crm-license') . '">lisansınızı yenileyin</a>.</p>';
                echo '</div>';
            }
        }

        // Check user limit
        if ($this->is_user_limit_exceeded()) {
            echo '<div class="notice notice-warning">';
            echo '<p><strong>Insurance CRM:</strong> Kullanıcı sayısı sınırı aşıldı. ';
            echo 'Mevcut: ' . $this->get_current_user_count() . ', ';
            echo 'Limit: ' . get_option('insurance_crm_license_user_limit', 5) . '. ';
            echo '<a href="' . admin_url('admin.php?page=insurance-crm-license') . '">Lisansınızı yükseltin</a>.</p>';
            echo '</div>';
        }
    }

    /**
     * Check if module is allowed by license
     * 
     * @param string $module Module name or view parameter
     * @return bool True if module is allowed
     */
    public function is_module_allowed($module) {
        error_log("License Manager: Checking if module is allowed: $module");
        
        // If license is bypassed, allow all modules
        if ($this->license_api && $this->license_api->is_license_bypassed()) {
            error_log("License Manager: License is bypassed, allowing module: $module");
            return true;
        }

        // If no valid license, deny access
        if (!$this->can_access_data()) {
            error_log("License Manager: No valid license, denying module access: $module");
            return false;
        }

        $allowed_modules = get_option('insurance_crm_license_modules', array());
        
        error_log("License Manager: Allowed modules from license: " . implode(', ', $allowed_modules));
        
        // If no specific modules defined, allow all
        if (empty($allowed_modules)) {
            error_log("License Manager: No specific modules defined, allowing all. Module: $module");
            return true;
        }

        // Check direct module slug match first
        if (in_array($module, $allowed_modules)) {
            error_log("License Manager: Direct module match found for: $module");
            return true;
        }
        
        // Check if it's a view parameter that maps to an allowed module
        error_log("License Manager: No direct match, checking view parameter mappings: $module");
        
        // Get module mappings (with fallbacks)
        $module_mappings = $this->get_module_view_mappings();
        
        // Check if any allowed module has this view parameter
        foreach ($allowed_modules as $module_slug) {
            if (isset($module_mappings[$module_slug]) && 
                $module_mappings[$module_slug] === $module) {
                error_log("License Manager: View parameter '$module' allowed via module slug: $module_slug");
                return true;
            }
        }
        
        // Check reverse mapping - if the input is a module slug that maps to a view parameter
        if (isset($module_mappings[$module])) {
            $view_param = $module_mappings[$module];
            error_log("License Manager: Module '$module' maps to view parameter '$view_param'");
            
            // Check if any allowed module has the same view parameter
            foreach ($allowed_modules as $allowed_slug) {
                if (isset($module_mappings[$allowed_slug]) && 
                    $module_mappings[$allowed_slug] === $view_param) {
                    error_log("License Manager: Module '$module' allowed via view parameter mapping to '$allowed_slug'");
                    return true;
                }
                // Also check direct slug match for the view parameter
                if ($allowed_slug === $view_param) {
                    error_log("License Manager: Module '$module' allowed via direct view parameter match: $allowed_slug");
                    return true;
                }
            }
        }
        
        // Special case handling for common module variations
        $module_variations = array(
            'sale_opportunities' => array('sales-opportunities', 'sales_opportunities', 'sale-opportunities'),
            'sales-opportunities' => array('sale_opportunities', 'sales_opportunities', 'sale-opportunities'),
            'sales_opportunities' => array('sale_opportunities', 'sales-opportunities', 'sale-opportunities'),
            'data_transfer' => array('data-transfer', 'data_transfer'),
            'data-transfer' => array('data_transfer', 'data_transfer')
        );
        
        if (isset($module_variations[$module])) {
            foreach ($module_variations[$module] as $variation) {
                if (in_array($variation, $allowed_modules)) {
                    error_log("License Manager: Module '$module' allowed via variation match: $variation");
                    return true;
                }
            }
        }
        
        error_log("License Manager: Module access DENIED for: $module");
        return false;
    }
    
    /**
     * Check if view parameter is allowed
     * 
     * @param string $view_parameter View parameter to check
     * @return bool True if allowed
     */
    public function is_view_parameter_allowed($view_parameter) {
        $allowed_modules = get_option('insurance_crm_license_modules', array());
        
        error_log('License Manager: Checking view parameter: ' . $view_parameter);
        error_log('License Manager: Allowed modules: ' . implode(', ', $allowed_modules));
        
        // Get module mappings from server (cached)
        $module_mappings = $this->get_module_view_mappings();
        
        error_log('License Manager: Available module mappings: ' . print_r($module_mappings, true));
        
        // Check if any allowed module has this view parameter
        foreach ($allowed_modules as $module_slug) {
            if (isset($module_mappings[$module_slug]) && 
                $module_mappings[$module_slug] === $view_parameter) {
                error_log('License Manager: View parameter allowed via module: ' . $module_slug);
                return true;
            }
        }
        
        error_log('License Manager: View parameter not allowed: ' . $view_parameter);
        return false;
    }
    
    /**
     * Force refresh module mappings cache
     */
    public function refresh_module_mappings() {
        delete_transient('insurance_crm_module_mappings');
        error_log('License Manager: Manually refreshing module mappings cache');
        return $this->get_module_view_mappings();
    }
    
    /**
     * Get current module mappings for debugging
     */
    public function get_current_module_mappings() {
        return $this->get_module_view_mappings();
    }
    
    /**
     * Get module to view parameter mappings from server
     * 
     * @return array Mapping of module slug to view parameter
     */
    private function get_module_view_mappings() {
        // Check cache first (cache for 1 hour)
        $cache_key = 'insurance_crm_module_mappings';
        $cached_mappings = get_transient($cache_key);
        
        if ($cached_mappings !== false) {
            error_log('License Manager: Using cached module mappings: ' . print_r($cached_mappings, true));
            return $cached_mappings;
        }
        
        // Fetch from server
        $mappings = array();
        
        if ($this->license_api) {
            error_log('License Manager: Fetching fresh module mappings from server');
            $modules_data = $this->license_api->get_modules();
            
            if (is_array($modules_data)) {
                // Handle both success response and legacy format
                $modules_array = isset($modules_data['modules']) ? $modules_data['modules'] : array();
                
                if (!empty($modules_array) && is_array($modules_array)) {
                    error_log('License Manager: Processing ' . count($modules_array) . ' modules from server');
                    foreach ($modules_array as $module) {
                        if (!empty($module['slug']) && !empty($module['view_parameter'])) {
                            $mappings[$module['slug']] = $module['view_parameter'];
                            error_log('License Manager: Mapped ' . $module['slug'] . ' -> ' . $module['view_parameter']);
                        } else {
                            error_log('License Manager: Skipping module with missing data: ' . print_r($module, true));
                        }
                    }
                } else {
                    error_log('License Manager: No modules array found in response');
                    if (isset($modules_data['error'])) {
                        error_log('License Manager: Modules API error: ' . $modules_data['error']);
                    }
                }
            } else {
                error_log('License Manager: Invalid modules data received (not array)');
            }
        } else {
            error_log('License Manager: No license API instance available');
        }
        
        // Add fallback mappings for common modules if server doesn't provide them
        $fallback_mappings = array(
            'sale_opportunities' => 'sale_opportunities',
            'sales-opportunities' => 'sale_opportunities', // Handle legacy format
            'dashboard' => 'dashboard',
            'customers' => 'customers',
            'policies' => 'policies',
            'quotes' => 'quotes',
            'tasks' => 'tasks',
            'reports' => 'reports',
            'data_transfer' => 'data-transfer'
        );
        
        // Merge server mappings with fallback mappings (server takes precedence)
        $mappings = array_merge($fallback_mappings, $mappings);
        
        error_log('License Manager: Final mappings (including fallbacks): ' . print_r($mappings, true));
        
        // Cache the mappings for 1 hour (even if empty to prevent repeated failed requests)
        set_transient($cache_key, $mappings, HOUR_IN_SECONDS);
        error_log('License Manager: Cached ' . count($mappings) . ' module mappings');
        
        return $mappings;
    }
    


    /**
     * Get license information for display
     * 
     * @return array License information
     */
    public function get_license_info() {
        return array(
            'key' => get_option('insurance_crm_license_key', ''),
            'status' => get_option('insurance_crm_license_status', 'inactive'),
            'type' => get_option('insurance_crm_license_type', ''),
            'package' => get_option('insurance_crm_license_package', ''),
            'type_description' => get_option('insurance_crm_license_type_description', ''),
            'expiry' => get_option('insurance_crm_license_expiry', ''),
            'user_limit' => get_option('insurance_crm_license_user_limit', 5),
            'modules' => get_option('insurance_crm_license_modules', array()),
            'licensed_modules' => $this->get_licensed_modules(), // Add detailed modules
            'last_check' => get_option('insurance_crm_license_last_check', ''),
            'current_users' => $this->get_current_user_count(),
            'in_grace_period' => $this->is_in_grace_period(),
            'grace_days_remaining' => $this->get_grace_period_days_remaining(),
            'expiring_soon' => $this->is_license_expiring_soon(3),
            'days_until_expiry' => $this->get_days_until_expiry()
        );
    }

    /**
     * Get licensed modules with detailed information
     * 
     * @return array Licensed modules with details
     */
    public function get_licensed_modules() {
        // Get module slugs from license
        $module_slugs = get_option('insurance_crm_license_modules', array());
        
        error_log('License Manager: Raw licensed modules option: ' . print_r($module_slugs, true));
        
        if (empty($module_slugs) || !is_array($module_slugs)) {
            error_log('License Manager: No licensed module slugs found or invalid format');
            
            // Try to refresh license data to get latest modules
            if ($this->license_api) {
                error_log('License Manager: Attempting to refresh license data to get modules');
                $license_key = get_option('insurance_crm_license_key', '');
                if (!empty($license_key)) {
                    $validation_result = $this->license_api->validate_license($license_key);
                    if (isset($validation_result['modules']) && is_array($validation_result['modules'])) {
                        $module_slugs = $validation_result['modules'];
                        update_option('insurance_crm_license_modules', $module_slugs);
                        error_log('License Manager: Retrieved modules from license validation: ' . implode(', ', $module_slugs));
                    }
                }
            }
            
            // If still no modules, try to get from backend if available
            if (empty($module_slugs) || !is_array($module_slugs)) {
                error_log('License Manager: Attempting to get modules from backend admin interface');
                $backend_modules = $this->get_backend_licensed_modules();
                if (!empty($backend_modules)) {
                    $module_slugs = array_column($backend_modules, 'slug');
                    update_option('insurance_crm_license_modules', $module_slugs);
                    error_log('License Manager: Retrieved modules from backend: ' . implode(', ', $module_slugs));
                }
            }
            
            // Enhanced fallback: try to get modules from server API directly
            if (empty($module_slugs) || !is_array($module_slugs)) {
                error_log('License Manager: Attempting direct API call to get licensed modules');
                if ($this->license_api) {
                    $license_key = get_option('insurance_crm_license_key', '');
                    if (!empty($license_key)) {
                        // Try direct license info call
                        $license_info = $this->license_api->get_license_info($license_key);
                        if (isset($license_info['data']['modules']) && is_array($license_info['data']['modules'])) {
                            $module_slugs = $license_info['data']['modules'];
                            update_option('insurance_crm_license_modules', $module_slugs);
                            error_log('License Manager: Retrieved modules from direct license info: ' . implode(', ', $module_slugs));
                        }
                    }
                }
            }
            
            // If still no modules, return empty array
            if (empty($module_slugs) || !is_array($module_slugs)) {
                error_log('License Manager: Still no licensed modules after all refresh attempts');
                return array();
            }
        }
        
        error_log('License Manager: Licensed module slugs: ' . implode(', ', $module_slugs));
        
        // Get module mappings (view parameters) with retry logic
        $module_mappings = $this->get_module_view_mappings();
        
        // Get available modules from server for detailed information with error handling
        $available_modules = array();
        if ($this->license_api) {
            error_log('License Manager: Fetching detailed module information from server');
            $modules_response = $this->license_api->get_modules();
            
            if (isset($modules_response['error'])) {
                error_log('License Manager: Server API error for modules: ' . $modules_response['error']);
            } else if (isset($modules_response['modules']) && is_array($modules_response['modules'])) {
                foreach ($modules_response['modules'] as $module) {
                    if (isset($module['slug'])) {
                        $available_modules[$module['slug']] = $module;
                        error_log('License Manager: Server module available: ' . $module['slug'] . ' (' . ($module['name'] ?? 'Unknown') . ')');
                    }
                }
                error_log('License Manager: Retrieved ' . count($available_modules) . ' modules from server');
            } else {
                error_log('License Manager: Invalid or empty modules response from server: ' . print_r($modules_response, true));
            }
        } else {
            error_log('License Manager: No license API instance available for fetching module details');
        }
        
        // Build licensed modules with details and fallback data
        $licensed_modules = array();
        foreach ($module_slugs as $slug) {
            // Enhanced default module names
            $default_names = array(
                'dashboard' => 'Dashboard',
                'customers' => 'Müşteri Yönetimi',
                'policies' => 'Poliçe Yönetimi',
                'quotes' => 'Teklif Yönetimi',
                'tasks' => 'Görev Yönetimi',
                'reports' => 'Raporlar',
                'data_transfer' => 'Veri Aktarımı',
                'sale_opportunities' => 'Satış Fırsatları',
                'sales_opportunities' => 'Satış Fırsatları',
                'accounting' => 'Muhasebe',
                'hr' => 'İnsan Kaynakları'
            );
            
            $default_descriptions = array(
                'dashboard' => 'Ana kontrol paneli ve genel bakış',
                'customers' => 'Müşteri bilgilerini yönetme ve takip etme',
                'policies' => 'Sigorta poliçelerini yönetme',
                'quotes' => 'Sigorta tekliflerini hazırlama ve yönetme',
                'tasks' => 'Görevleri takip etme ve yönetme',
                'reports' => 'Detaylı raporlar ve analizler',
                'data_transfer' => 'Veri içe/dışa aktarım işlemleri',
                'sale_opportunities' => 'Satış fırsatlarını takip ve yönetme',
                'sales_opportunities' => 'Satış fırsatlarını takip ve yönetme',
                'accounting' => 'Muhasebe işlemleri ve mali raporlar',
                'hr' => 'İnsan kaynakları yönetimi'
            );
            
            $module_info = array(
                'slug' => $slug,
                'name' => isset($default_names[$slug]) ? $default_names[$slug] : ucfirst(str_replace(array('-', '_'), ' ', $slug)),
                'view_parameter' => isset($module_mappings[$slug]) ? $module_mappings[$slug] : $slug,
                'description' => isset($default_descriptions[$slug]) ? $default_descriptions[$slug] : '',
                'category' => 'general',
                'status' => 'active'
            );
            
            // Enhance with server data if available
            if (isset($available_modules[$slug])) {
                $server_module = $available_modules[$slug];
                $module_info['name'] = $server_module['name'] ?? $module_info['name'];
                $module_info['description'] = $server_module['description'] ?? $module_info['description'];
                $module_info['category'] = $server_module['category'] ?? 'general';
                $module_info['id'] = $server_module['id'] ?? null;
                error_log('License Manager: Enhanced module with server data: ' . $slug);
            } else {
                error_log('License Manager: Using fallback data for module: ' . $slug);
            }
            
            $licensed_modules[] = $module_info;
            error_log('License Manager: Licensed module details - ' . $module_info['name'] . ' (' . $slug . ')');
        }
        
        error_log('License Manager: Retrieved ' . count($licensed_modules) . ' licensed modules with details');
        return $licensed_modules;
    }

    /**
     * Get licensed modules from backend admin interface
     * This method is specifically for client-side backend admin panel integration
     * 
     * @return array Licensed modules from backend
     */
    public function get_backend_licensed_modules() {
        error_log('License Manager: Attempting to retrieve modules from backend admin interface');
        
        // Check if we're in admin context and can access backend functionality
        if (!is_admin()) {
            error_log('License Manager: Not in admin context, cannot access backend modules');
            return array();
        }
        
        // Try to get modules from the current license validation
        $license_key = get_option('insurance_crm_license_key', '');
        if (empty($license_key)) {
            error_log('License Manager: No license key available for backend module retrieval');
            return array();
        }
        
        // Get all available modules from server and match with license
        $backend_modules = array();
        if ($this->license_api) {
            $modules_response = $this->license_api->get_modules();
            
            if (isset($modules_response['modules']) && is_array($modules_response['modules'])) {
                $available_modules = $modules_response['modules'];
                
                // Get current license info to see which modules are allowed
                $license_info = $this->license_api->get_license_info($license_key);
                $allowed_module_slugs = array();
                
                if (isset($license_info['modules']) && is_array($license_info['modules'])) {
                    $allowed_module_slugs = $license_info['modules'];
                } else if (isset($license_info['success']) && $license_info['success'] && isset($license_info['data']['modules'])) {
                    $allowed_module_slugs = $license_info['data']['modules'];
                }
                
                error_log('License Manager: Backend allowed modules: ' . implode(', ', $allowed_module_slugs));
                
                // Filter available modules to only licensed ones
                foreach ($available_modules as $module) {
                    if (isset($module['slug']) && in_array($module['slug'], $allowed_module_slugs)) {
                        $backend_modules[] = array(
                            'slug' => $module['slug'],
                            'name' => $module['name'] ?? ucfirst(str_replace(array('-', '_'), ' ', $module['slug'])),
                            'description' => $module['description'] ?? '',
                            'view_parameter' => $module['view_parameter'] ?? $module['slug'],
                            'category' => $module['category'] ?? 'general',
                            'status' => 'active'
                        );
                        error_log('License Manager: Added backend licensed module: ' . $module['slug']);
                    }
                }
            } else {
                error_log('License Manager: Invalid or empty modules response from backend API');
            }
        } else {
            error_log('License Manager: No license API instance available for backend module retrieval');
        }
        
        error_log('License Manager: Retrieved ' . count($backend_modules) . ' modules from backend admin interface');
        return $backend_modules;
    }

    /**
     * AJAX handler for license validation
     */
    public function ajax_validate_license() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'validate_license')) {
            wp_send_json_error(array('message' => 'Invalid nonce'));
            return;
        }
        
        // Get license key
        $license_key = isset($_POST['license_key']) ? sanitize_text_field($_POST['license_key']) : '';
        
        if (empty($license_key)) {
            wp_send_json_error(array('message' => 'License key is required'));
            return;
        }
        
        // Validate license
        $validation_result = $this->validate_license($license_key);
        
        if ($validation_result) {
            wp_send_json_success(array('message' => 'License validated successfully'));
        } else {
            wp_send_json_error(array('message' => 'License validation failed'));
        }
    }

    /**
     * Cleanup on plugin deactivation
     */
    public static function deactivation_cleanup() {
        wp_clear_scheduled_hook('insurance_crm_daily_license_check');
        wp_clear_scheduled_hook('insurance_crm_periodic_license_check');
        wp_clear_scheduled_hook('insurance_crm_daily_license_log');
    }
    
    /**
     * Perform daily license logging to debug.log
     */
    public function perform_daily_license_logging() {
        $license_info = $this->get_license_info();
        $timestamp = current_time('Y-m-d H:i:s');
        
        $log_entry = "\n[{$timestamp}] INSURANCE CRM DAILY LICENSE STATUS:\n";
        $log_entry .= "Status: " . $license_info['status'] . "\n";
        $log_entry .= "License Key: " . (!empty($license_info['key']) ? substr($license_info['key'], 0, 8) . '...' : 'None') . "\n";
        $log_entry .= "Type: " . $license_info['type'] . "\n";
        $log_entry .= "Package: " . $license_info['package'] . "\n";
        $log_entry .= "Expiry: " . $license_info['expiry'] . "\n";
        $log_entry .= "User Limit: " . $license_info['user_limit'] . "\n";
        $log_entry .= "Current Users: " . $license_info['current_users'] . "\n";
        $log_entry .= "In Grace Period: " . ($license_info['in_grace_period'] ? 'Yes' : 'No') . "\n";
        if ($license_info['in_grace_period']) {
            $log_entry .= "Grace Days Remaining: " . $license_info['grace_days_remaining'] . "\n";
        }
        $log_entry .= "Last Check: " . $license_info['last_check'] . "\n";
        $log_entry .= "Days Until Expiry: " . $license_info['days_until_expiry'] . "\n";
        $log_entry .= "----------------------------------------\n";
        
        // Write to debug.log
        $debug_log_path = WP_CONTENT_DIR . '/debug.log';
        error_log($log_entry, 3, $debug_log_path);
    }
    
    /**
     * Log license validation result to database
     */
    private function log_license_validation_result($user_id, $validation_data) {
        global $wpdb;
        
        // Create license validation logs table if it doesn't exist
        $this->create_license_logs_table();
        
        $table_name = $wpdb->prefix . 'insurance_license_logs';
        
        $wpdb->insert(
            $table_name,
            array(
                'user_id' => $user_id,
                'user_login' => $validation_data['user_login'],
                'license_status' => $validation_data['license_status'],
                'license_key_partial' => $validation_data['license_key_partial'],
                'license_expiry' => $validation_data['license_expiry'],
                'is_restricted' => $validation_data['is_restricted'] ? 1 : 0,
                'is_bypassed' => $validation_data['is_bypassed'] ? 1 : 0,
                'validation_result' => $validation_data['license_status'] === 'active' ? 'success' : 'failed',
                'ip_address' => $validation_data['ip_address'],
                'created_at' => $validation_data['validation_time']
            ),
            array('%d', '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%s')
        );
    }
    
    /**
     * Create license validation logs table
     */
    private function create_license_logs_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'insurance_license_logs';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            user_id int(11) NOT NULL,
            user_login varchar(60) NOT NULL,
            license_status varchar(20) NOT NULL,
            license_key_partial varchar(50) DEFAULT NULL,
            license_expiry datetime DEFAULT NULL,
            is_restricted tinyint(1) DEFAULT 0,
            is_bypassed tinyint(1) DEFAULT 0,
            validation_result varchar(20) NOT NULL,
            ip_address varchar(45) DEFAULT NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY license_status (license_status),
            KEY validation_result (validation_result),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Get client IP address
     */
    private function get_client_ip() {
        $ip_keys = array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR');
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (array_map('trim', explode(',', $_SERVER[$key])) as $ip) {
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
}