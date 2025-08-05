<?php
/**
 * Modules API Endpoints
 * 
 * Additional API endpoints for module management
 * This file can be included for extended API functionality
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Extended Modules API Class
 */
class License_Manager_Modules_API {
    
    /**
     * API namespace
     */
    private $namespace = 'balkay-license/v1';
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_extended_routes'));
    }
    
    /**
     * Register extended API routes
     */
    public function register_extended_routes() {
        // Module validation endpoint
        register_rest_route($this->namespace, '/validate_module', array(
            'methods' => 'POST',
            'callback' => array($this, 'validate_module_access'),
            'permission_callback' => '__return_true',
            'args' => array(
                'license_key' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'module_or_view' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'domain' => array(
                    'required' => false,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
            ),
        ));
        
        // Get module by view parameter
        register_rest_route($this->namespace, '/module_by_view/(?P<view>[a-zA-Z0-9\-]+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_module_by_view'),
            'permission_callback' => '__return_true',
        ));
    }
    
    /**
     * Validate module access endpoint
     */
    public function validate_module_access($request) {
        $license_key = $request->get_param('license_key');
        $module_or_view = $request->get_param('module_or_view');
        $domain = $request->get_param('domain');
        
        // Get license data first
        $api = new License_Manager_API();
        $license_data = $api->get_license_data($license_key);
        
        if (!$license_data) {
            return new WP_REST_Response(array(
                'access_allowed' => false,
                'reason' => 'invalid_license',
                'message' => __('Geçersiz lisans anahtarı', 'license-manager')
            ), 200);
        }
        
        // Check domain if provided
        if (!empty($domain) && !$api->is_domain_allowed($license_data, $domain)) {
            return new WP_REST_Response(array(
                'access_allowed' => false,
                'reason' => 'domain_not_allowed',
                'message' => __('Bu lisans için alan adı yetkilendirilmemiş', 'license-manager')
            ), 200);
        }
        
        // Check module access
        $allowed_modules = $license_data['modules'] ?? array();
        $access_allowed = in_array($module_or_view, $allowed_modules);
        
        // If not directly allowed, check if it's a view parameter
        if (!$access_allowed) {
            $database = new License_Manager_Database();
            $module = $database->get_module_by_view_parameter($module_or_view);
            
            if ($module) {
                $access_allowed = in_array($module->slug, $allowed_modules);
            }
        }
        
        return new WP_REST_Response(array(
            'access_allowed' => $access_allowed,
            'module_or_view' => $module_or_view,
            'license_status' => $license_data['status'],
            'allowed_modules' => $allowed_modules,
            'message' => $access_allowed ? 
                __('Erişim izni verildi', 'license-manager') : 
                __('Bu modüle erişim izniniz yok', 'license-manager')
        ), 200);
    }
    
    /**
     * Get module by view parameter
     */
    public function get_module_by_view($request) {
        $view_parameter = $request->get_param('view');
        
        $database = new License_Manager_Database();
        $module = $database->get_module_by_view_parameter($view_parameter);
        
        if (!$module) {
            return new WP_REST_Response(array(
                'found' => false,
                'message' => __('Modül bulunamadı', 'license-manager')
            ), 404);
        }
        
        return new WP_REST_Response(array(
            'found' => true,
            'module' => array(
                'id' => $module->term_id,
                'name' => $module->name,
                'slug' => $module->slug,
                'view_parameter' => $module->view_parameter,
                'description' => $module->description,
                'category' => $module->category
            )
        ), 200);
    }
}

// Initialize the extended API if this file is included
if (class_exists('License_Manager_API')) {
    new License_Manager_Modules_API();
}