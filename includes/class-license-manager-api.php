<?php
/**
 * API Endpoints Class
 * Handles REST API endpoints for license validation
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class License_Manager_API {
    
    /**
     * API namespace
     */
    private $namespace = 'balkay-license/v1';
    
    /**
     * Constructor
     */
    public function __construct() {
        // Standard REST API route registration
        add_action('rest_api_init', array($this, 'register_routes'));
        
        // Add custom rewrite rules for /api endpoints
        add_action('init', array($this, 'add_custom_rewrite_rules'));
        add_action('query_vars', array($this, 'add_query_vars'));
        add_action('template_redirect', array($this, 'handle_custom_api_requests'));
        
        // Ensure license types and modules are created
        add_action('init', array($this, 'ensure_taxonomies_exist'), 11);
    }
    
    /**
     * Ensure taxonomies and terms exist
     */
    public function ensure_taxonomies_exist() {
        // Check if license types exist
        if (!term_exists('demo', 'lm_license_type')) {
            wp_insert_term(__('Demo', 'license-manager'), 'lm_license_type', array('slug' => 'demo'));
        }
        if (!term_exists('lifetime', 'lm_license_type')) {
            wp_insert_term(__('Yaşam Boyu', 'license-manager'), 'lm_license_type', array('slug' => 'lifetime'));
        }
        if (!term_exists('trial', 'lm_license_type')) {
            wp_insert_term(__('Deneme', 'license-manager'), 'lm_license_type', array('slug' => 'trial'));
        }
    }
    
    /**
     * Register REST API routes
     */
    public function register_routes() {
        // Validate License endpoint
        register_rest_route($this->namespace, '/validate_license', array(
            'methods' => array('POST', 'GET'),
            'callback' => array($this, 'validate_license'),
            'permission_callback' => array($this, 'validate_license_permission'),
            'args' => array(
                'license_key' => array(
                    'required' => false,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'domain' => array(
                    'required' => false,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'action' => array(
                    'required' => false,
                    'type' => 'string',
                    'default' => 'validate',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
            ),
        ));
        
        // Validate endpoint (shorter version)
        register_rest_route($this->namespace, '/validate', array(
            'methods' => array('POST', 'GET'),
            'callback' => array($this, 'validate_license'),
            'permission_callback' => array($this, 'validate_license_permission'),
            'args' => array(
                'license_key' => array(
                    'required' => false,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'domain' => array(
                    'required' => false,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'action' => array(
                    'required' => false,
                    'type' => 'string',
                    'default' => 'validate',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
            ),
        ));
        
        // License Info endpoint
        register_rest_route($this->namespace, '/license_info', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_license_info'),
            'permission_callback' => array($this, 'validate_license_permission'),
            'args' => array(
                'license_key' => array(
                    'required' => false,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
            ),
        ));
        
        // Check Status endpoint
        register_rest_route($this->namespace, '/check_status', array(
            'methods' => 'POST',
            'callback' => array($this, 'check_status'),
            'permission_callback' => array($this, 'validate_license_permission'),
            'args' => array(
                'license_key' => array(
                    'required' => false,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'domain' => array(
                    'required' => false,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'action' => array(
                    'required' => false,
                    'type' => 'string',
                    'default' => 'check',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
            ),
        ));
        
        // Test endpoint for debugging
        register_rest_route($this->namespace, '/test', array(
            'methods' => array('GET', 'POST'),
            'callback' => array($this, 'test_endpoint'),
            'permission_callback' => '__return_true',
        ));
    }
    
    /**
     * Permission callback for license endpoints
     */
    public function validate_license_permission($request) {
        // Always allow license validation requests - this is a public API
        return true;
    }
    
    /**
     * Validate license key format
     */
    public function validate_license_key_format($license_key) {
        // Basic validation - license key should be at least 3 characters (more lenient)
        // Log for debugging
        error_log("BALKAy License API: Validating license key: " . substr($license_key, 0, 10) . "...");
        return strlen($license_key) >= 3;
    }
    
    /**
     * Validate domain format
     */
    public function validate_domain_format($domain) {
        // Log for debugging
        error_log("BALKAy License API: Validating domain: " . $domain);
        
        // Allow both domain names and URLs
        if (filter_var($domain, FILTER_VALIDATE_URL) !== false) {
            return true;
        }
        
        // Clean domain (remove www, http, https)
        $clean_domain = preg_replace('/^(https?:\/\/)?(www\.)?/', '', $domain);
        $clean_domain = rtrim($clean_domain, '/');
        
        // Basic domain validation - be more lenient
        if (empty($clean_domain)) {
            return false;
        }
        
        // Allow localhost and IP addresses for testing
        if ($clean_domain === 'localhost' || 
            $clean_domain === '127.0.0.1' || 
            filter_var($clean_domain, FILTER_VALIDATE_IP) !== false) {
            return true;
        }
        
        // Basic domain validation with more lenient rules
        return (bool) preg_match('/^[a-zA-Z0-9][a-zA-Z0-9\-\.]{1,61}[a-zA-Z0-9]\.[a-zA-Z]{2,}$/', $clean_domain) ||
               filter_var($clean_domain, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) !== false;
    }
    
    /**
     * Validate License endpoint
     */
    public function validate_license($request) {
        // Handle both GET and POST requests
        $method = $request->get_method();
        
        if ($method === 'GET') {
            // For GET requests, parameters are in query string
            $license_key = $request->get_param('license_key');
            $domain = $request->get_param('domain');
            $action = $request->get_param('action') ?: 'validate';
        } else {
            // For POST requests, parameters are in body
            $license_key = $request->get_param('license_key');
            $domain = $request->get_param('domain');
            $action = $request->get_param('action') ?: 'validate';
        }
        
        // Validate required parameters
        if (empty($license_key) || empty($domain)) {
            return new WP_REST_Response(array(
                'status' => 'error',
                'message' => 'license_key ve domain parametreleri gereklidir'
            ), 400);
        }
        
        // Get license data
        $license_data = $this->get_license_data($license_key);
        
        if (!$license_data) {
            return new WP_REST_Response(array(
                'status' => 'invalid',
                'license_type' => '',
                'expires_on' => '',
                'user_limit' => 0,
                'modules' => array(),
                'message' => __('Geçersiz lisans anahtarı', 'license-manager')
            ), 200);
        }
        
        // Check domain validation
        if (!$this->is_domain_allowed($license_data, $domain)) {
            return new WP_REST_Response(array(
                'status' => 'invalid',
                'license_type' => $license_data['license_type'],
                'expires_on' => $license_data['expires_on'],
                'user_limit' => $license_data['user_limit'],
                'modules' => $license_data['modules'],
                'message' => __('Bu lisans için alan adı yetkilendirilmemiş', 'license-manager')
            ), 200);
        }
        
        // Check license status
        $status = $this->check_license_status($license_data);
        
        return new WP_REST_Response(array(
            'status' => $status,
            'license_type' => $license_data['license_type'],
            'expires_on' => $license_data['expires_on'],
            'user_limit' => $license_data['user_limit'],
            'modules' => $license_data['modules'],
            'message' => $this->get_status_message($status, $license_data)
        ), 200);
    }
    
    /**
     * Get License Info endpoint
     */
    public function get_license_info($request) {
        $license_key = $request->get_param('license_key');
        
        // Get license data
        $license_data = $this->get_license_data($license_key);
        
        if (!$license_data) {
            return new WP_REST_Response(array(
                'status' => 'invalid',
                'license_type' => '',
                'expires_on' => '',
                'user_limit' => 0,
                'modules' => array(),
                'message' => __('Geçersiz lisans anahtarı', 'license-manager')
            ), 200);
        }
        
        // Check license status
        $status = $this->check_license_status($license_data);
        
        return new WP_REST_Response(array(
            'status' => $status,
            'license_type' => $license_data['license_type'],
            'expires_on' => $license_data['expires_on'],
            'user_limit' => $license_data['user_limit'],
            'modules' => $license_data['modules'],
            'message' => $this->get_status_message($status, $license_data)
        ), 200);
    }
    
    /**
     * Check Status endpoint (same as validate_license)
     */
    public function check_status($request) {
        return $this->validate_license($request);
    }
    
    /**
     * Test endpoint for debugging
     */
    public function test_endpoint($request) {
        // Get some debug information
        $debug_info = array(
            'status' => 'success',
            'message' => 'BALKAy License API is working',
            'namespace' => $this->namespace,
            'endpoints' => array(
                '/wp-json/' . $this->namespace . '/validate_license',
                '/wp-json/' . $this->namespace . '/validate',
                '/wp-json/' . $this->namespace . '/license_info',
                '/wp-json/' . $this->namespace . '/check_status',
                '/wp-json/' . $this->namespace . '/test'
            ),
            'custom_endpoints' => array(
                '/api/validate_license',
                '/api/validate'
            ),
            'timestamp' => current_time('mysql'),
            'rewrite_rules_flushed' => get_option('balkay_license_rewrite_flushed'),
            'current_version' => BALKAY_LICENSE_VERSION
        );
        
        // Check if license types exist
        $license_types = get_terms(array(
            'taxonomy' => 'lm_license_type',
            'hide_empty' => false,
        ));
        
        if (!is_wp_error($license_types)) {
            $debug_info['license_types'] = array();
            foreach ($license_types as $type) {
                $debug_info['license_types'][] = array(
                    'slug' => $type->slug,
                    'name' => $type->name
                );
            }
        } else {
            $debug_info['license_types_error'] = $license_types->get_error_message();
        }
        
        // Check if modules exist
        $modules = get_terms(array(
            'taxonomy' => 'lm_modules',
            'hide_empty' => false,
        ));
        
        if (!is_wp_error($modules)) {
            $debug_info['modules'] = array();
            foreach ($modules as $module) {
                $debug_info['modules'][] = array(
                    'slug' => $module->slug,
                    'name' => $module->name
                );
            }
        } else {
            $debug_info['modules_error'] = $modules->get_error_message();
        }
        
        return new WP_REST_Response($debug_info, 200);
    }
    
    /**
     * Get license data from database
     */
    private function get_license_data($license_key) {
        // Query license by meta key
        $licenses = get_posts(array(
            'post_type' => 'lm_license',
            'post_status' => 'publish',
            'meta_query' => array(
                array(
                    'key' => '_license_key',
                    'value' => $license_key,
                    'compare' => '='
                )
            ),
            'posts_per_page' => 1
        ));
        
        if (empty($licenses)) {
            error_log("BALKAy License API: No license found for key: " . substr($license_key, 0, 10) . "...");
            return false;
        }
        
        $license = $licenses[0];
        error_log("BALKAy License API: Found license ID: " . $license->ID . " for key: " . substr($license_key, 0, 10) . "...");
        
        // Get license metadata
        $expires_on = get_post_meta($license->ID, '_expires_on', true);
        $user_limit = get_post_meta($license->ID, '_user_limit', true);
        $allowed_domains = get_post_meta($license->ID, '_allowed_domains', true);
        
        // Get license type
        $license_types = wp_get_post_terms($license->ID, 'lm_license_type');
        $license_type = 'monthly'; // default
        if (!is_wp_error($license_types) && !empty($license_types)) {
            $license_type = $license_types[0]->slug;
            error_log("BALKAy License API: License type from taxonomy: " . $license_type);
        } else {
            error_log("BALKAy License API: No license type found in taxonomy, using default: " . $license_type);
            // Try to get from meta as fallback
            $license_type_meta = get_post_meta($license->ID, '_license_type', true);
            if (!empty($license_type_meta)) {
                $license_type = $license_type_meta;
                error_log("BALKAy License API: License type from meta: " . $license_type);
            }
        }
        
        // Get modules - check both taxonomy and meta for consistency
        $modules = wp_get_post_terms($license->ID, 'lm_modules');
        $module_slugs = array();
        if (!is_wp_error($modules) && !empty($modules)) {
            foreach ($modules as $module) {
                $module_slugs[] = $module->slug;
            }
        }
        
        // If no modules from taxonomy, try meta fallback
        if (empty($module_slugs)) {
            $modules_meta = get_post_meta($license->ID, '_modules', true);
            if (is_array($modules_meta)) {
                $module_slugs = $modules_meta;
            }
        }
        
        // If still no modules assigned, use default
        if (empty($module_slugs)) {
            $module_slugs = get_option('license_manager_default_modules', array('dashboard', 'customers', 'policies', 'quotes', 'tasks', 'reports', 'data_transfer'));
        }
        
        error_log("BALKAy License API: License data - Type: " . $license_type . ", Expires: " . $expires_on . ", Modules: " . implode(',', $module_slugs));
        
        return array(
            'id' => $license->ID,
            'license_key' => $license_key,
            'license_type' => $license_type,
            'expires_on' => $expires_on ? date('Y-m-d', strtotime($expires_on)) : '',
            'user_limit' => intval($user_limit) ?: get_option('license_manager_default_user_limit', 5),
            'modules' => $module_slugs,
            'allowed_domains' => $allowed_domains ? explode(',', $allowed_domains) : array(),
        );
    }
    
    /**
     * Check if domain is allowed for license
     */
    private function is_domain_allowed($license_data, $domain) {
        // If no domains specified, allow any domain
        if (empty($license_data['allowed_domains'])) {
            return true;
        }
        
        // Clean domain (remove www, http, https)
        $clean_domain = preg_replace('/^(https?:\/\/)?(www\.)?/', '', $domain);
        $clean_domain = rtrim($clean_domain, '/');
        
        // Also try with original domain
        $domains_to_check = array($clean_domain, $domain);
        
        foreach ($license_data['allowed_domains'] as $allowed_domain) {
            $clean_allowed = preg_replace('/^(https?:\/\/)?(www\.)?/', '', trim($allowed_domain));
            $clean_allowed = rtrim($clean_allowed, '/');
            
            foreach ($domains_to_check as $check_domain) {
                if ($check_domain === $clean_allowed || $check_domain === trim($allowed_domain)) {
                    return true;
                }
                
                // Also check if it's a subdomain
                if (strpos($check_domain, $clean_allowed) !== false || strpos($clean_allowed, $check_domain) !== false) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Check license status based on expiry date and other factors
     */
    private function check_license_status($license_data) {
        // Get license status from meta field
        $current_status = get_post_meta($license_data['id'], '_status', true);
        if (empty($current_status)) {
            $current_status = 'active'; // Default status
        }
        
        error_log("BALKAy License API: License status check - Current status: " . $current_status . ", Type: " . $license_data['license_type'] . ", Expires: " . $license_data['expires_on']);
        
        // If manually set to invalid or suspended
        if (in_array($current_status, array('invalid', 'suspended'))) {
            return $current_status;
        }
        
        // Handle special license types
        if ($license_data['license_type'] === 'lifetime') {
            // Lifetime licenses never expire
            return 'active';
        }
        
        if ($license_data['license_type'] === 'demo') {
            // Demo licenses have a special status message but check expiry normally
            $status = 'active';
            if (!empty($license_data['expires_on']) && $license_data['expires_on'] !== 'lifetime') {
                $expiry_date = strtotime($license_data['expires_on']);
                $current_date = current_time('timestamp');
                
                if ($current_date > $expiry_date) {
                    $status = 'expired';
                }
            }
            return $status;
        }
        
        // Check expiry date for all other types
        if (!empty($license_data['expires_on']) && $license_data['expires_on'] !== 'lifetime') {
            $expiry_date = strtotime($license_data['expires_on']);
            $current_date = current_time('timestamp');
            
            if ($current_date > $expiry_date) {
                return 'expired';
            }
        }
        
        return 'active';
    }
    
    /**
     * Get status message
     */
    private function get_status_message($status, $license_data = null) {
        $messages = array(
            'active' => __('Lisans aktif ve geçerli', 'license-manager'),
            'expired' => __('Lisans süresi dolmuş', 'license-manager'),
            'invalid' => __('Lisans anahtarı geçersiz', 'license-manager'),
            'suspended' => __('Lisans askıya alınmış', 'license-manager'),
        );
        
        // Special messages for different license types
        if ($license_data && $status === 'active') {
            if ($license_data['license_type'] === 'lifetime') {
                return __('Yaşam boyu lisans - süresiz aktif', 'license-manager');
            }
            if ($license_data['license_type'] === 'demo') {
                if (!empty($license_data['expires_on'])) {
                    return __('Demo lisans aktif - Bitiş: ', 'license-manager') . $license_data['expires_on'];
                }
                return __('Demo lisans aktif', 'license-manager');
            }
            if ($license_data['license_type'] === 'trial') {
                if (!empty($license_data['expires_on'])) {
                    return __('Deneme lisansı aktif - Bitiş: ', 'license-manager') . $license_data['expires_on'];
                }
                return __('Deneme lisansı aktif', 'license-manager');
            }
        }
        
        return isset($messages[$status]) ? $messages[$status] : __('Bilinmeyen durum', 'license-manager');
    }
    
    /**
     * Add custom rewrite rules for /api endpoints
     */
    public function add_custom_rewrite_rules() {
        // Add multiple rewrite rules to handle different cases
        add_rewrite_rule('^api/validate_license/?$', 'index.php?balkay_api=validate_license', 'top');
        add_rewrite_rule('^api/validate/?$', 'index.php?balkay_api=validate_license', 'top');
        add_rewrite_tag('%balkay_api%', '([^&]+)');
        
        // Force flush rewrite rules on plugin activation/update
        if (get_option('balkay_license_rewrite_flushed') !== BALKAY_LICENSE_VERSION) {
            flush_rewrite_rules();
            update_option('balkay_license_rewrite_flushed', BALKAY_LICENSE_VERSION);
        }
        
        // Also ensure rewrite rules are flushed on first load after changes
        add_action('wp_loaded', array($this, 'maybe_flush_rewrite_rules'), 1);
    }
    
    /**
     * Maybe flush rewrite rules if needed
     */
    public function maybe_flush_rewrite_rules() {
        // Check if our rewrite rules exist
        $rules = get_option('rewrite_rules');
        if (!$rules || !isset($rules['^api/validate_license/?$'])) {
            flush_rewrite_rules();
            error_log('BALKAy License: Flushed rewrite rules - API rules were missing');
        }
    }
    
    /**
     * Add custom query vars
     */
    public function add_query_vars($vars) {
        $vars[] = 'balkay_api';
        return $vars;
    }
    
    /**
     * Handle custom API requests
     */
    public function handle_custom_api_requests() {
        $api_action = get_query_var('balkay_api');
        
        error_log("BALKAy License API: Handling custom API request - Action: " . $api_action);
        
        if ($api_action === 'validate_license') {
            $this->handle_validate_license_api();
        }
    }
    
    /**
     * Handle /api/validate_license endpoint
     */
    private function handle_validate_license_api() {
        error_log("BALKAy License API: Processing /api/validate_license request");
        
        // Set JSON header
        header('Content-Type: application/json');
        
        $method = $_SERVER['REQUEST_METHOD'];
        error_log("BALKAy License API: Request method: " . $method);
        
        // Support both GET and POST methods
        if (!in_array($method, ['GET', 'POST'])) {
            http_response_code(405);
            echo json_encode(array(
                'status' => 'error',
                'message' => 'Only GET and POST methods allowed'
            ));
            exit;
        }
        
        $data = array();
        
        if ($method === 'POST') {
            // Get POST data
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);
            
            // Also check $_POST for form data
            if (!$data && !empty($_POST)) {
                $data = $_POST;
            }
            
            error_log("BALKAy License API: POST data received: " . json_encode($data));
        } else {
            // GET request - get parameters from query string
            $data = $_GET;
            error_log("BALKAy License API: GET data received: " . json_encode($data));
        }
        
        // Validate required parameters
        if (empty($data['license_key']) || empty($data['domain'])) {
            http_response_code(400);
            $error_response = array(
                'status' => 'error',
                'message' => 'license_key and domain are required',
                'debug' => array(
                    'received_data' => $data,
                    'missing' => array()
                )
            );
            
            if (empty($data['license_key'])) {
                $error_response['debug']['missing'][] = 'license_key';
            }
            if (empty($data['domain'])) {
                $error_response['debug']['missing'][] = 'domain';
            }
            
            echo json_encode($error_response);
            exit;
        }
        
        // Sanitize input
        $license_key = sanitize_text_field($data['license_key']);
        $domain = sanitize_text_field($data['domain']);
        $action = isset($data['action']) ? sanitize_text_field($data['action']) : 'validate';
        
        error_log("BALKAy License API: Processing validation for license: " . substr($license_key, 0, 10) . "... domain: " . $domain);
        
        // Create a WP_REST_Request object to use the existing validation logic
        $request = new WP_REST_Request('POST');
        $request->set_param('license_key', $license_key);
        $request->set_param('domain', $domain);
        $request->set_param('action', $action);
        
        // Use the existing validate_license method
        $response = $this->validate_license($request);
        
        // Set the HTTP status code
        http_response_code($response->get_status());
        
        $response_data = $response->get_data();
        error_log("BALKAy License API: Response data: " . json_encode($response_data));
        
        // Output the JSON response
        echo json_encode($response_data);
        exit;
    }
}