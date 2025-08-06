<?php
/**
 * Module Validator Class
 * 
 * Enhanced client-side module validation and access control
 * 
 * @package Insurance_CRM
 * @author  Anadolu Birlik
 * @since   1.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Insurance_CRM_Module_Validator {
    
    /**
     * License manager instance
     */
    private $license_manager;
    
    /**
     * Constructor
     */
    public function __construct() {
        // Get license manager instance
        global $insurance_crm_license_manager;
        $this->license_manager = $insurance_crm_license_manager;
        
        // Hook into WordPress
        add_action('wp', array($this, 'validate_current_page_access'));
        add_action('admin_init', array($this, 'validate_admin_page_access'));
    }
    
    /**
     * Validate access to current page based on view parameter
     */
    public function validate_current_page_access() {
        // Skip validation for admin pages
        if (is_admin()) {
            return;
        }
        
        // Get view parameter from URL
        $view_parameter = $this->get_current_view_parameter();
        
        if (!empty($view_parameter)) {
            if (!$this->is_module_access_allowed($view_parameter)) {
                $this->handle_unauthorized_access($view_parameter);
            }
        }
    }
    
    /**
     * Validate access to admin pages
     */
    public function validate_admin_page_access() {
        // Get current admin page
        $current_page = $this->get_current_admin_page();
        
        if (!empty($current_page)) {
            if (!$this->is_module_access_allowed($current_page)) {
                $this->handle_unauthorized_admin_access($current_page);
            }
        }
    }
    
    /**
     * Check if module access is allowed
     * 
     * @param string $module_or_view Module slug or view parameter
     * @return bool True if allowed
     */
    public function is_module_access_allowed($module_or_view) {
        if (!$this->license_manager) {
            return false;
        }
        
        // Always allow access to license management and user management pages
        $always_allowed_modules = array(
            'license-management',
            'license_management', 
            'user-management',
            'user_management',
            'all_personnel',
            'personnel',
            'users'
        );
        
        if (in_array($module_or_view, $always_allowed_modules)) {
            return true;
        }
        
        // Check user limit first - if exceeded, only allow license and user management
        if ($this->license_manager->is_user_limit_exceeded()) {
            return false;
        }
        
        return $this->license_manager->is_module_allowed($module_or_view);
    }
    
    /**
     * Get current view parameter from URL
     * 
     * @return string View parameter or empty string
     */
    private function get_current_view_parameter() {
        return isset($_GET['view']) ? sanitize_text_field($_GET['view']) : '';
    }
    
    /**
     * Get current admin page module
     * 
     * @return string Module identifier or empty string
     */
    private function get_current_admin_page() {
        $page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';
        
        // Map admin pages to modules
        $page_module_map = array(
            'insurance-crm-customers' => 'customers',
            'insurance-crm-policies' => 'policies',
            'insurance-crm-quotes' => 'quotes',
            'insurance-crm-tasks' => 'tasks',
            'insurance-crm-reports' => 'reports',
            'insurance-crm-data-transfer' => 'data_transfer',
        );
        
        return isset($page_module_map[$page]) ? $page_module_map[$page] : '';
    }
    
    /**
     * Handle unauthorized access to a module
     * 
     * @param string $module_or_view Module or view that was accessed
     */
    private function handle_unauthorized_access($module_or_view) {
        // Log the unauthorized access attempt
        $this->log_unauthorized_access($module_or_view, 'frontend');
        
        // Check if this is due to user limit exceeded
        if ($this->license_manager && $this->license_manager->is_user_limit_exceeded()) {
            $user_limit = get_option('insurance_crm_license_user_limit', 5);
            $current_users = $this->license_manager->get_current_user_count();
            
            // Store restriction details for the redirect page
            set_transient('insurance_crm_restriction_details_' . get_current_user_id(), array(
                'type' => 'user_limit',
                'current_users' => $current_users,
                'max_users' => $user_limit,
                'message' => sprintf(
                    'Kullanıcı sayısını aştınız! Mevcut kullanıcı sayısı: %d, Lisansınızın izin verdiği maksimum kullanıcı sayısı: %d. Sadece kullanıcı yönetimi ve lisans yönetimi sayfalarına erişebilirsiniz.',
                    $current_users,
                    $user_limit
                )
            ), 300); // 5 minutes
            
            // Redirect to license restriction page with user limit details
            wp_redirect(add_query_arg(array(
                'restriction' => 'user_limit',
                'current_users' => $current_users,
                'max_users' => $user_limit
            ), $this->get_license_restriction_url()));
            exit;
        }
        
        // Show regular access denied message for other restrictions
        wp_die(
            $this->get_access_denied_message($module_or_view),
            __('Erişim Reddedildi', 'insurance-crm'),
            array('response' => 403)
        );
    }
    
    /**
     * Handle unauthorized access to admin module
     * 
     * @param string $module Module that was accessed
     */
    private function handle_unauthorized_admin_access($module) {
        // Log the unauthorized access attempt
        $this->log_unauthorized_access($module, 'admin');
        
        // Check if this is due to user limit exceeded
        if ($this->license_manager && $this->license_manager->is_user_limit_exceeded()) {
            $user_limit = get_option('insurance_crm_license_user_limit', 5);
            $current_users = $this->license_manager->get_current_user_count();
            
            // Redirect to admin dashboard with user limit warning
            wp_redirect(add_query_arg(array(
                'page' => 'insurance-crm',
                'user_limit_exceeded' => '1',
                'current_users' => $current_users,
                'max_users' => $user_limit
            ), admin_url('admin.php')));
            exit;
        }
        
        // Redirect to dashboard with error message for other restrictions
        wp_redirect(add_query_arg(array(
            'page' => 'insurance-crm',
            'access_denied' => $module
        ), admin_url('admin.php')));
        exit;
    }
    
    /**
     * Get access denied message
     * 
     * @param string $module_or_view Module or view that was accessed
     * @return string Access denied message
     */
    private function get_access_denied_message($module_or_view) {
        $license_info = $this->license_manager ? $this->license_manager->get_license_info() : array();
        
        $message = sprintf(
            __('Bu modüle erişim izniniz bulunmamaktadır: %s', 'insurance-crm'),
            '<strong>' . esc_html($module_or_view) . '</strong>'
        );
        
        $message .= '<br><br>';
        
        if (empty($license_info['key'])) {
            $message .= __('Lütfen geçerli bir lisans anahtarı giriniz.', 'insurance-crm');
        } elseif ($license_info['status'] !== 'active') {
            $message .= __('Lisansınız aktif değil. Lütfen lisans durumunuzu kontrol ediniz.', 'insurance-crm');
        } else {
            $message .= __('Bu modül mevcut lisans paketinizde bulunmamaktadır. Lisansınızı yükseltmek için lütfen bizimle iletişime geçiniz.', 'insurance-crm');
        }
        
        return $message;
    }
    
    /**
     * Log unauthorized access attempt
     * 
     * @param string $module_or_view Module or view that was accessed
     * @param string $context Context (frontend, admin)
     */
    private function log_unauthorized_access($module_or_view, $context) {
        $user = wp_get_current_user();
        $user_info = $user->ID ? $user->user_login . ' (ID: ' . $user->ID . ')' : 'Guest';
        $ip_address = $this->get_client_ip();
        
        $log_data = array(
            'timestamp' => current_time('mysql'),
            'user' => $user_info,
            'ip' => $ip_address,
            'module' => $module_or_view,
            'context' => $context,
            'url' => $_SERVER['REQUEST_URI'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
        );
        
        error_log('Insurance CRM - Unauthorized access attempt: ' . json_encode($log_data));
        
        // Also store in database option for admin review
        $access_logs = get_option('insurance_crm_access_logs', array());
        $access_logs[] = $log_data;
        
        // Keep only last 100 entries
        if (count($access_logs) > 100) {
            $access_logs = array_slice($access_logs, -100);
        }
        
        update_option('insurance_crm_access_logs', $access_logs);
    }
    
    /**
     * Get client IP address
     * 
     * @return string Client IP address
     */
    private function get_client_ip() {
        $ip_keys = array('HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR');
        
        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                // Handle comma-separated IPs (from load balancers)
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    }
    
    /**
     * Check module access with detailed response
     * 
     * @param string $module_or_view Module slug or view parameter
     * @return array Detailed access check result
     */
    public function check_module_access($module_or_view) {
        $result = array(
            'allowed' => false,
            'module' => $module_or_view,
            'reason' => '',
            'license_status' => '',
            'suggestions' => array()
        );
        
        if (!$this->license_manager) {
            $result['reason'] = 'License manager not available';
            return $result;
        }
        
        $license_info = $this->license_manager->get_license_info();
        $result['license_status'] = $license_info['status'] ?? 'unknown';
        
        if (empty($license_info['key'])) {
            $result['reason'] = 'No license key';
            $result['suggestions'][] = 'Enter a valid license key';
        } elseif ($license_info['status'] !== 'active') {
            $result['reason'] = 'License not active: ' . $license_info['status'];
            $result['suggestions'][] = 'Check license status and renewal';
        } elseif (!$this->license_manager->is_module_allowed($module_or_view)) {
            $result['reason'] = 'Module not included in license package';
            $result['suggestions'][] = 'Upgrade license package';
            $result['suggestions'][] = 'Contact support for module access';
        } else {
            $result['allowed'] = true;
            $result['reason'] = 'Access granted';
        }
        
        return $result;
    }
    
    /**
     * Get access logs for admin review
     * 
     * @param int $limit Number of logs to retrieve
     * @return array Access logs
     */
    public function get_access_logs($limit = 50) {
        $logs = get_option('insurance_crm_access_logs', array());
        return array_slice($logs, -$limit);
    }
    
    /**
     * Clear access logs
     */
    public function clear_access_logs() {
        delete_option('insurance_crm_access_logs');
    }
    
    /**
     * Get license restriction page URL
     * 
     * @return string URL to license restriction page
     */
    private function get_license_restriction_url() {
        // Try to find the license restriction template or default page
        // This should point to where the license-restriction.php template is served
        return add_query_arg('view', 'license-restriction', home_url());
    }
}