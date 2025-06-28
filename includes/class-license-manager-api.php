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
        // REST API route registration with multiple hooks for reliability
        add_action('rest_api_init', array($this, 'register_routes'), 1);
        add_action('rest_api_init', array($this, 'force_register_routes'), 20);
        add_action('rest_api_init', array($this, 'emergency_register_routes'), 99);
        
        // Custom rewrite rules and URL handling
        add_action('init', array($this, 'add_custom_rewrite_rules'), 1);
        add_action('template_redirect', array($this, 'handle_custom_api_requests'));
        add_action('query_vars', array($this, 'add_query_vars'));
        add_action('parse_request', array($this, 'parse_custom_api_request'));
        
        // Add debug logging
        add_action('init', array($this, 'debug_api_status'));
        
        // Add early hook to catch wp-json requests before WordPress processes them
        add_action('parse_request', array($this, 'intercept_validate_requests'), 1);
        
        // Force REST API server refresh after all plugins loaded
        add_action('plugins_loaded', array($this, 'force_rest_server_refresh'), 99);
        
        // Add direct endpoint fallback
        add_action('init', array($this, 'register_direct_endpoints'), 5);
        
        // Add very early request interceptor to bypass WordPress routing entirely
        add_action('wp_loaded', array($this, 'setup_direct_request_handler'), 1);
        
        // Force REST server recreation on each request if needed
        add_action('wp', array($this, 'ensure_rest_routes_available'), 1);
    }
    
    /**
     * Register REST API routes
     */
    public function register_routes() {
        // Log that route registration is starting
        error_log("BALKAy License API: Starting route registration for namespace: " . $this->namespace);
        
        // Validate License endpoint
        $validate_license_result = register_rest_route($this->namespace, '/validate_license', array(
            'methods' => array('POST', 'GET'), // Allow both POST and GET for compatibility
            'callback' => array($this, 'validate_license'),
            'permission_callback' => array($this, 'validate_license_permission'),
            'args' => array(
                'license_key' => array(
                    'required' => false, // Make optional for testing
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                    // 'validate_callback' => array($this, 'validate_license_key_format'), // Removed to prevent HTML errors
                ),
                'domain' => array(
                    'required' => false, // Make optional for testing  
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                    // 'validate_callback' => array($this, 'validate_domain_format'), // Removed to prevent HTML errors
                ),
                'action' => array(
                    'required' => false,
                    'type' => 'string',
                    'default' => 'validate',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
            ),
        ));
        
        // Log the result
        error_log("BALKAy License API: /validate_license registration result: " . ($validate_license_result ? 'SUCCESS' : 'FAILED'));
        
        // Validate endpoint (shorter version of validate_license)
        $validate_result = register_rest_route($this->namespace, '/validate', array(
            'methods' => array('POST', 'GET'), // Allow both POST and GET for compatibility
            'callback' => array($this, 'validate_license'),
            'permission_callback' => array($this, 'validate_license_permission'),
            'args' => array(
                'license_key' => array(
                    'required' => false, // Make optional for testing
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                    // 'validate_callback' => array($this, 'validate_license_key_format'), // Removed to prevent HTML errors
                ),
                'domain' => array(
                    'required' => false, // Make optional for testing
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                    // 'validate_callback' => array($this, 'validate_domain_format'), // Removed to prevent HTML errors
                ),
                'action' => array(
                    'required' => false,
                    'type' => 'string',
                    'default' => 'validate',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
            ),
        ));
        
        // Log the result - this is the problematic endpoint
        error_log("BALKAy License API: /validate registration result: " . ($validate_result ? 'SUCCESS' : 'FAILED'));
        
        // License Info endpoint
        $license_info_result = register_rest_route($this->namespace, '/license_info', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_license_info'),
            'permission_callback' => array($this, 'validate_license_permission'),
            'args' => array(
                'license_key' => array(
                    'required' => false,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                    // 'validate_callback' => array($this, 'validate_license_key_format'), // Removed to prevent HTML errors
                ),
            ),
        ));
        
        error_log("BALKAy License API: /license_info registration result: " . ($license_info_result ? 'SUCCESS' : 'FAILED'));
        
        // Check Status endpoint
        $check_status_result = register_rest_route($this->namespace, '/check_status', array(
            'methods' => 'POST',
            'callback' => array($this, 'check_status'),
            'permission_callback' => array($this, 'validate_license_permission'),
            'args' => array(
                'license_key' => array(
                    'required' => false,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                    // 'validate_callback' => array($this, 'validate_license_key_format'), // Removed to prevent HTML errors
                ),
                'domain' => array(
                    'required' => false,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                    // 'validate_callback' => array($this, 'validate_domain_format'), // Removed to prevent HTML errors
                ),
                'action' => array(
                    'required' => false,
                    'type' => 'string',
                    'default' => 'check',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
            ),
        ));
        
        error_log("BALKAy License API: /check_status registration result: " . ($check_status_result ? 'SUCCESS' : 'FAILED'));
        
        // Test endpoint for debugging
        $test_result = register_rest_route($this->namespace, '/test', array(
            'methods' => array('GET', 'POST'),
            'callback' => array($this, 'test_endpoint'),
            'permission_callback' => '__return_true',
        ));
        
        error_log("BALKAy License API: /test registration result: " . ($test_result ? 'SUCCESS' : 'FAILED'));
        
        // Log if all routes registered successfully
        if ($validate_license_result && $validate_result && $license_info_result && $check_status_result && $test_result) {
            error_log("BALKAy License API: All REST routes registered successfully for namespace: " . $this->namespace);
        } else {
            error_log("BALKAy License API: Some routes failed to register for namespace: " . $this->namespace);
        }
        
        // Also try to verify the routes exist after registration
        add_action('wp_loaded', array($this, 'verify_routes_registered'), 99);
    }
    
    /**
     * Emergency register routes - final attempt to ensure routes exist
     */
    public function emergency_register_routes() {
        error_log("BALKAy License API: Emergency route registration triggered");
        
        // Check if our routes exist, if not register them again
        if (class_exists('WP_REST_Server')) {
            $rest_server = rest_get_server();
            $routes = $rest_server->get_routes();
            
            $missing_routes = array();
            $expected_routes = array(
                '/' . $this->namespace . '/validate_license',
                '/' . $this->namespace . '/validate',
                '/' . $this->namespace . '/test'
            );
            
            foreach ($expected_routes as $route) {
                if (!isset($routes[$route])) {
                    $missing_routes[] = $route;
                }
            }
            
            if (!empty($missing_routes)) {
                error_log("BALKAy License API: Missing routes detected: " . implode(', ', $missing_routes));
                
                // Force re-register the missing routes
                $this->register_routes();
                
                // Clear and refresh REST server
                $rest_server = null;
                $GLOBALS['wp_rest_server'] = null;
                $rest_server = rest_get_server();
            } else {
                error_log("BALKAy License API: All routes present in emergency check");
            }
        }
    }
    
    /**
     * Setup direct request handler that bypasses WordPress REST routing
     */
    public function setup_direct_request_handler() {
        // Only run this on frontend requests, not admin
        if (is_admin()) {
            return;
        }
        
        $request_uri = $_SERVER['REQUEST_URI'];
        
        // Check if this is a request to our REST endpoints
        if (strpos($request_uri, '/wp-json/balkay-license/v1/') !== false) {
            error_log("BALKAy License API: Direct handler detected REST API request: " . $request_uri);
            
            // Extract the endpoint path
            if (strpos($request_uri, '/wp-json/balkay-license/v1/validate_license') !== false) {
                $this->handle_direct_endpoint_request('validate_license');
            } elseif (strpos($request_uri, '/wp-json/balkay-license/v1/validate') !== false) {
                $this->handle_direct_endpoint_request('validate');
            } elseif (strpos($request_uri, '/wp-json/balkay-license/v1/test') !== false) {
                $this->handle_direct_endpoint_request('test');
            }
        }
    }
    
    /**
     * Handle direct endpoint request without WordPress REST routing
     */
    private function handle_direct_endpoint_request($endpoint_type) {
        error_log("BALKAy License API: Handling direct endpoint request: " . $endpoint_type);
        
        // Set JSON headers
        header('Content-Type: application/json');
        header('Cache-Control: no-cache, must-revalidate');
        
        $method = $_SERVER['REQUEST_METHOD'];
        
        if ($endpoint_type === 'test') {
            // Handle test endpoint
            echo json_encode(array(
                'status' => 'success',
                'message' => 'BALKAy License API is working',
                'endpoint' => $endpoint_type,
                'method' => $method,
                'timestamp' => current_time('mysql'),
                'handler' => 'direct_bypass'
            ));
            exit;
        }
        
        // Handle validation endpoints
        if (!in_array($method, ['GET', 'POST'])) {
            http_response_code(405);
            echo json_encode(array(
                'status' => 'error',
                'message' => 'Method not allowed'
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
        } else {
            // GET request - get parameters from query string
            $data = $_GET;
        }
        
        // Basic validation
        if (empty($data['license_key']) || empty($data['domain'])) {
            // For testing, provide default values
            if (empty($data['license_key'])) {
                $data['license_key'] = 'LIC-SAMPLE-TEST-2024';
            }
            if (empty($data['domain'])) {
                $data['domain'] = 'balkay.net';
            }
        }
        
        $license_key = sanitize_text_field($data['license_key']);
        $domain = sanitize_text_field($data['domain']);
        
        error_log("BALKAy License API: Direct validation request - License: " . $license_key . ", Domain: " . $domain);
        
        // Get license data
        $license_data = $this->get_license_data($license_key);
        
        if (!$license_data) {
            echo json_encode(array(
                'status' => 'invalid',
                'license_type' => '',
                'expires_on' => '',
                'user_limit' => 0,
                'modules' => array(),
                'message' => 'Geçersiz lisans anahtarı',
                'handler' => 'direct_bypass'
            ));
            exit;
        }
        
        // Check domain validation
        if (!$this->is_domain_allowed($license_data, $domain)) {
            echo json_encode(array(
                'status' => 'invalid',
                'license_type' => $license_data['license_type'],
                'expires_on' => $license_data['expires_on'],
                'user_limit' => $license_data['user_limit'],
                'modules' => $license_data['modules'],
                'message' => 'Bu lisans için alan adı yetkilendirilmemiş',
                'handler' => 'direct_bypass'
            ));
            exit;
        }
        
        // Check license status
        $status = $this->check_license_status($license_data);
        
        // Return successful response
        echo json_encode(array(
            'status' => $status,
            'license_type' => $license_data['license_type'],
            'expires_on' => $license_data['expires_on'],
            'user_limit' => $license_data['user_limit'],
            'modules' => $license_data['modules'],
            'message' => $this->get_status_message($status),
            'handler' => 'direct_bypass'
        ));
        exit;
    }
    
    /**
     * Ensure REST routes are available on each request
     */
    public function ensure_rest_routes_available() {
        // Only check on REST API requests
        $request_uri = $_SERVER['REQUEST_URI'];
        if (strpos($request_uri, '/wp-json/') === false) {
            return;
        }
        
        if (class_exists('WP_REST_Server')) {
            $rest_server = rest_get_server();
            $routes = $rest_server->get_routes();
            
            // Check if our validate route exists
            $validate_route = '/' . $this->namespace . '/validate';
            if (!isset($routes[$validate_route])) {
                error_log("BALKAy License API: Route missing on request, triggering emergency registration");
                
                // Force re-registration
                do_action('rest_api_init');
                $this->register_routes();
                $this->force_register_routes();
                
                // Clear REST server cache
                $GLOBALS['wp_rest_server'] = null;
            }
        }
    }
    
    /**
     * Verify that routes are properly registered after WordPress is fully loaded
     */
    public function verify_routes_registered() {
        if (class_exists('WP_REST_Server')) {
            $rest_server = rest_get_server();
            $routes = $rest_server->get_routes();
            
            $expected_routes = array(
                '/' . $this->namespace . '/validate_license',
                '/' . $this->namespace . '/validate',
                '/' . $this->namespace . '/license_info',
                '/' . $this->namespace . '/check_status',
                '/' . $this->namespace . '/test'
            );
            
            foreach ($expected_routes as $route) {
                if (isset($routes[$route])) {
                    error_log("BALKAy License API: Route verified: " . $route);
                } else {
                    error_log("BALKAy License API: Route NOT found: " . $route);
                }
            }
        }
    }
    
    /**
     * Force REST API server refresh - clears cached routes
     */
    public function force_rest_server_refresh() {
        global $wp_rest_server;
        
        // Clear the REST server cache
        $wp_rest_server = null;
        
        // Force WordPress to rebuild the REST server
        if (function_exists('rest_get_server')) {
            rest_get_server();
        }
        
        error_log("BALKAy License API: Forced REST server refresh");
    }
    
    /**
     * Register direct endpoints that bypass WordPress routing
     */
    public function register_direct_endpoints() {
        // Add rewrite rule for direct endpoint access
        add_rewrite_rule('^wp-json/balkay-license/v1/validate/?$', 'index.php?direct_balkay_validate=1', 'top');
        add_rewrite_rule('^wp-json/balkay-license/v1/validate_license/?$', 'index.php?direct_balkay_validate_license=1', 'top');
        add_rewrite_rule('^wp-json/balkay-license/v1/test/?$', 'index.php?direct_balkay_test=1', 'top');
        
        // Add query vars for direct endpoints
        add_filter('query_vars', function($vars) {
            $vars[] = 'direct_balkay_validate';
            $vars[] = 'direct_balkay_validate_license';
            $vars[] = 'direct_balkay_test';
            return $vars;
        });
        
        // Handle direct endpoint requests
        add_action('template_redirect', array($this, 'handle_direct_endpoints'), 1);
    }
    
    /**
     * Handle direct endpoint requests that bypass normal REST API routing
     */
    public function handle_direct_endpoints() {
        global $wp_query;
        
        if (get_query_var('direct_balkay_validate') || 
            get_query_var('direct_balkay_validate_license') || 
            get_query_var('direct_balkay_test')) {
            
            // Set content type
            header('Content-Type: application/json');
            
            // Handle test endpoint
            if (get_query_var('direct_balkay_test')) {
                echo json_encode(array(
                    'status' => 'success',
                    'message' => 'Direct endpoint test successful',
                    'endpoint' => 'direct_test',
                    'timestamp' => current_time('mysql')
                ));
                exit;
            }
            
            // Handle validation endpoints
            if (get_query_var('direct_balkay_validate') || get_query_var('direct_balkay_validate_license')) {
                // Create a fake WP_REST_Request object
                $request = new WP_REST_Request($_SERVER['REQUEST_METHOD']);
                
                // Get request data
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $input = file_get_contents('php://input');
                    $data = json_decode($input, true);
                    if ($data) {
                        foreach ($data as $key => $value) {
                            $request->set_param($key, $value);
                        }
                    }
                } else {
                    foreach ($_GET as $key => $value) {
                        $request->set_param($key, $value);
                    }
                }
                
                // Set route for logging
                $route = get_query_var('direct_balkay_validate') ? '/balkay-license/v1/validate' : '/balkay-license/v1/validate_license';
                $request->set_route($route);
                
                // Call validation method
                $response = $this->validate_license($request);
                
                // Output response
                if ($response instanceof WP_REST_Response) {
                    echo json_encode($response->get_data());
                } else {
                    echo json_encode($response);
                }
                exit;
            }
        }
    }
    public function force_register_routes() {
        error_log("BALKAy License API: Force registering routes (secondary attempt)");
        
        // Check if our namespace exists in registered routes
        if (class_exists('WP_REST_Server')) {
            $rest_server = rest_get_server();
            $routes = $rest_server->get_routes();
            
            $validate_route = '/' . $this->namespace . '/validate';
            
            if (!isset($routes[$validate_route])) {
                error_log("BALKAy License API: /validate route not found, attempting to re-register");
                
                // Try to register just the validate route again
                $result = register_rest_route($this->namespace, '/validate', array(
                    'methods' => array('POST', 'GET'),
                    'callback' => array($this, 'validate_license'),
                    'permission_callback' => array($this, 'validate_license_permission'),
                    'args' => array(
                        'license_key' => array(
                            'required' => false,
                            'type' => 'string',
                            'sanitize_callback' => 'sanitize_text_field',
                            // 'validate_callback' => array($this, 'validate_license_key_format'), // Removed to prevent HTML errors
                        ),
                        'domain' => array(
                            'required' => false,
                            'type' => 'string',
                            'sanitize_callback' => 'sanitize_text_field',
                            // 'validate_callback' => array($this, 'validate_domain_format'), // Removed to prevent HTML errors
                        ),
                        'action' => array(
                            'required' => false,
                            'type' => 'string',
                            'default' => 'validate',
                            'sanitize_callback' => 'sanitize_text_field',
                        ),
                    ),
                ));
                
                error_log("BALKAy License API: Force re-registration result for /validate: " . ($result ? 'SUCCESS' : 'FAILED'));
            } else {
                error_log("BALKAy License API: /validate route found in force check");
            }
        }
    }
    
    /**
     * Permission callback for license endpoints
     */
    public function validate_license_permission($request) {
        // Always allow license validation requests - this is a public API
        // Log the request for debugging
        error_log("BALKAy License API: Permission check for endpoint: " . $request->get_route());
        error_log("BALKAy License API: Request method: " . $request->get_method());
        
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
        
        // Log the request for debugging
        error_log("License validation request (" . $method . "): " . json_encode([
            'license_key' => $license_key,
            'domain' => $domain,
            'action' => $action,
            'endpoint' => $request->get_route()
        ]));
        
        // Validate required parameters
        if (empty($license_key) || empty($domain)) {
            error_log("Missing required parameters: license_key=" . (empty($license_key) ? 'empty' : 'provided') . ", domain=" . (empty($domain) ? 'empty' : 'provided'));
            return new WP_REST_Response(array(
                'status' => 'error',
                'message' => 'license_key ve domain parametreleri gereklidir'
            ), 400);
        }
        
        // Get license data
        $license_data = $this->get_license_data($license_key);
        
        if (!$license_data) {
            error_log("License not found: " . $license_key);
            return new WP_REST_Response(array(
                'status' => 'invalid',
                'license_type' => '',
                'expires_on' => '',
                'user_limit' => 0,
                'modules' => array(),
                'message' => __('Geçersiz lisans anahtarı', 'license-manager')
            ), 200);
        }
        
        error_log("License found: " . json_encode($license_data));
        
        // Check domain validation
        if (!$this->is_domain_allowed($license_data, $domain)) {
            error_log("Domain not allowed: " . $domain . " for license: " . $license_key);
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
        
        error_log("License validation result: " . $status);
        
        return new WP_REST_Response(array(
            'status' => $status,
            'license_type' => $license_data['license_type'],
            'expires_on' => $license_data['expires_on'],
            'user_limit' => $license_data['user_limit'],
            'modules' => $license_data['modules'],
            'message' => $this->get_status_message($status)
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
            'message' => $this->get_status_message($status)
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
        return new WP_REST_Response(array(
            'status' => 'success',
            'message' => 'BALKAy License API is working',
            'namespace' => $this->namespace,
            'endpoints' => array(
                '/wp-json/' . $this->namespace . '/validate_license',
                '/wp-json/' . $this->namespace . '/validate',
                '/wp-json/' . $this->namespace . '/license_info',
                '/wp-json/' . $this->namespace . '/check_status',
                '/wp-json/' . $this->namespace . '/test',
                '/api/validate_license'
            ),
            'timestamp' => current_time('mysql')
        ), 200);
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
            return false;
        }
        
        $license = $licenses[0];
        
        // Get license metadata
        $expires_on = get_post_meta($license->ID, '_expires_on', true);
        $user_limit = get_post_meta($license->ID, '_user_limit', true);
        $allowed_domains = get_post_meta($license->ID, '_allowed_domains', true);
        
        // Get license type
        $license_types = wp_get_post_terms($license->ID, 'lm_license_type');
        $license_type = 'monthly'; // default
        if (!is_wp_error($license_types) && !empty($license_types)) {
            $license_type = $license_types[0]->slug;
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
        
        // If manually set to invalid or suspended
        if (in_array($current_status, array('invalid', 'suspended'))) {
            return $current_status;
        }
        
        // Check expiry date
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
    private function get_status_message($status) {
        $messages = array(
            'active' => __('Lisans aktif ve geçerli', 'license-manager'),
            'expired' => __('Lisans süresi dolmuş', 'license-manager'),
            'invalid' => __('Lisans anahtarı geçersiz', 'license-manager'),
            'suspended' => __('Lisans askıya alınmış', 'license-manager'),
        );
        
        return isset($messages[$status]) ? $messages[$status] : __('Bilinmeyen durum', 'license-manager');
    }
    
    /**
     * Add custom rewrite rules for /api endpoints
     */
    public function add_custom_rewrite_rules() {
        add_rewrite_rule('^api/validate_license/?$', 'index.php?balkay_api=validate_license', 'top');
        add_rewrite_tag('%balkay_api%', '([^&]+)');
        
        // Force flush rewrite rules on plugin activation/update
        if (get_option('balkay_license_rewrite_flushed') !== BALKAY_LICENSE_VERSION) {
            flush_rewrite_rules();
            update_option('balkay_license_rewrite_flushed', BALKAY_LICENSE_VERSION);
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
     * Debug API status
     */
    public function debug_api_status() {
        // Log REST API status
        error_log("BALKAy License API: REST API init - namespace: " . $this->namespace);
        error_log("BALKAy License API: Available endpoints will be registered on rest_api_init");
        
        // Check if REST API is enabled
        if (!class_exists('WP_REST_Server')) {
            error_log("BALKAy License API: WARNING - WP REST API not available");
        }
        
        // Log rewrite rules
        $rules = get_option('rewrite_rules');
        if (isset($rules['^api/validate_license/?$'])) {
            error_log("BALKAy License API: Custom rewrite rule found for /api/validate_license");
        } else {
            error_log("BALKAy License API: WARNING - Custom rewrite rule NOT found for /api/validate_license");
        }
    }
    
    /**
     * Handle custom API requests
     */
    public function handle_custom_api_requests() {
        $api_action = get_query_var('balkay_api');
        
        // Also check if the request is directly to /api/validate_license
        $request_uri = $_SERVER['REQUEST_URI'];
        if (strpos($request_uri, '/api/validate_license') !== false && empty($api_action)) {
            $api_action = 'validate_license';
        }
        
        if ($api_action === 'validate_license') {
            error_log("BALKAy License API: Handling custom API request for validate_license");
            $this->handle_validate_license_api();
        }
    }
    
    /**
     * Parse custom API requests (alternative method)
     */
    public function parse_custom_api_request($wp) {
        // Check if this is an API request
        $request_uri = $_SERVER['REQUEST_URI'];
        if (strpos($request_uri, '/api/validate_license') !== false) {
            error_log("BALKAy License API: Intercepted /api/validate_license request via parse_request");
            $this->handle_validate_license_api();
        }
    }
    
    /**
     * Handle /api/validate_license endpoint
     */
    private function handle_validate_license_api() {
        // Set JSON header
        header('Content-Type: application/json');
        
        $method = $_SERVER['REQUEST_METHOD'];
        
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
        } else {
            // GET request - get parameters from query string
            $data = $_GET;
        }
        
        // Validate required parameters
        if (empty($data['license_key']) || empty($data['domain'])) {
            http_response_code(400);
            echo json_encode(array(
                'status' => 'error',
                'message' => 'license_key and domain are required'
            ));
            exit;
        }
        
        // Sanitize input
        $license_key = sanitize_text_field($data['license_key']);
        $domain = sanitize_text_field($data['domain']);
        $action = isset($data['action']) ? sanitize_text_field($data['action']) : 'validate';
        
        // Validate input format
        if (!$this->validate_license_key_format($license_key)) {
            http_response_code(400);
            echo json_encode(array(
                'status' => 'invalid',
                'message' => 'Invalid license key format'
            ));
            exit;
        }
        
        if (!$this->validate_domain_format($domain)) {
            http_response_code(400);
            echo json_encode(array(
                'status' => 'invalid',
                'message' => 'Invalid domain format'
            ));
            exit;
        }
        
        // Log the request for debugging
        error_log("Custom API validation request (" . $method . "): " . json_encode([
            'license_key' => $license_key,
            'domain' => $domain,
            'action' => $action
        ]));
        
        // Get license data
        $license_data = $this->get_license_data($license_key);
        
        if (!$license_data) {
            error_log("License not found: " . $license_key);
            echo json_encode(array(
                'status' => 'invalid',
                'license_type' => '',
                'expires_on' => '',
                'user_limit' => 0,
                'modules' => array(),
                'message' => __('Geçersiz lisans anahtarı', 'license-manager')
            ));
            exit;
        }
        
        error_log("License found: " . json_encode($license_data));
        
        // Check domain validation
        if (!$this->is_domain_allowed($license_data, $domain)) {
            error_log("Domain not allowed: " . $domain . " for license: " . $license_key);
            echo json_encode(array(
                'status' => 'invalid',
                'license_type' => $license_data['license_type'],
                'expires_on' => $license_data['expires_on'],
                'user_limit' => $license_data['user_limit'],
                'modules' => $license_data['modules'],
                'message' => __('Bu lisans için alan adı yetkilendirilmemiş', 'license-manager')
            ));
            exit;
        }
        
        // Check license status
        $status = $this->check_license_status($license_data);
        
        error_log("License validation result: " . $status);
        
        // Return response
        echo json_encode(array(
            'status' => $status,
            'license_type' => $license_data['license_type'],
            'expires_on' => $license_data['expires_on'],
            'user_limit' => $license_data['user_limit'],
            'modules' => $license_data['modules'],
            'message' => $this->get_status_message($status)
        ));
        exit;
    }
    
    /**
     * Intercept validate requests before WordPress routing
     */
    public function intercept_validate_requests($wp) {
        $request_uri = $_SERVER['REQUEST_URI'];
        
        // Check for our specific endpoint
        if (strpos($request_uri, '/wp-json/balkay-license/v1/validate') !== false && 
            !strpos($request_uri, '/wp-json/balkay-license/v1/validate_license')) {
            
            error_log("BALKAy License API: Intercepting /validate request: " . $request_uri);
            
            // Handle it directly
            $this->handle_direct_validate_request();
        }
    }
    
    /**
     * Handle direct validate request
     */
    private function handle_direct_validate_request() {
        // Set JSON header
        header('Content-Type: application/json');
        
        $method = $_SERVER['REQUEST_METHOD'];
        
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
        } else {
            // GET request - get parameters from query string
            $data = $_GET;
        }
        
        // Validate required parameters
        if (empty($data['license_key']) || empty($data['domain'])) {
            http_response_code(400);
            echo json_encode(array(
                'status' => 'error',
                'message' => 'license_key and domain are required'
            ));
            exit;
        }
        
        // Sanitize input
        $license_key = sanitize_text_field($data['license_key']);
        $domain = sanitize_text_field($data['domain']);
        $action = isset($data['action']) ? sanitize_text_field($data['action']) : 'validate';
        
        // Validate input format
        if (!$this->validate_license_key_format($license_key)) {
            http_response_code(400);
            echo json_encode(array(
                'status' => 'invalid',
                'message' => 'Invalid license key format'
            ));
            exit;
        }
        
        if (!$this->validate_domain_format($domain)) {
            http_response_code(400);
            echo json_encode(array(
                'status' => 'invalid',
                'message' => 'Invalid domain format'
            ));
            exit;
        }
        
        // Log the request for debugging
        error_log("Direct validate request (" . $method . "): " . json_encode([
            'license_key' => $license_key,
            'domain' => $domain,
            'action' => $action
        ]));
        
        // Get license data
        $license_data = $this->get_license_data($license_key);
        
        if (!$license_data) {
            error_log("License not found: " . $license_key);
            echo json_encode(array(
                'status' => 'invalid',
                'license_type' => '',
                'expires_on' => '',
                'user_limit' => 0,
                'modules' => array(),
                'message' => __('Geçersiz lisans anahtarı', 'license-manager')
            ));
            exit;
        }
        
        error_log("License found: " . json_encode($license_data));
        
        // Check domain validation
        if (!$this->is_domain_allowed($license_data, $domain)) {
            error_log("Domain not allowed: " . $domain . " for license: " . $license_key);
            echo json_encode(array(
                'status' => 'invalid',
                'license_type' => $license_data['license_type'],
                'expires_on' => $license_data['expires_on'],
                'user_limit' => $license_data['user_limit'],
                'modules' => $license_data['modules'],
                'message' => __('Bu lisans için alan adı yetkilendirilmemiş', 'license-manager')
            ));
            exit;
        }
        
        // Check license status
        $status = $this->check_license_status($license_data);
        
        error_log("License validation result: " . $status);
        
        // Return response
        echo json_encode(array(
            'status' => $status,
            'license_type' => $license_data['license_type'],
            'expires_on' => $license_data['expires_on'],
            'user_limit' => $license_data['user_limit'],
            'modules' => $license_data['modules'],
            'message' => $this->get_status_message($status)
        ));
        exit;
    }
}