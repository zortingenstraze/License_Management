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
        register_rest_route($this->namespace, '/module_by_view/(?P<view>[a-zA-Z0-9\-_]+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_module_by_view'),
            'permission_callback' => '__return_true',
        ));
        
        // Get all available modules
        register_rest_route($this->namespace, '/modules', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_all_modules'),
            'permission_callback' => '__return_true',
        ));
        
        // Get restricted modules for user limit exceeded scenarios
        register_rest_route($this->namespace, '/restricted_modules', array(
            'methods' => 'POST',
            'callback' => array($this, 'get_restricted_modules'),
            'permission_callback' => '__return_true',
            'args' => array(
                'license_key' => array(
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
        
        // Check module access using both database structures
        $allowed_modules = $license_data['modules'] ?? array();
        $access_allowed = in_array($module_or_view, $allowed_modules);
        
        // If not directly allowed, check if it's a view parameter
        if (!$access_allowed) {
            // Try new database structure first
            $database_v2 = new License_Manager_Database_V2();
            $module = null;
            
            if ($database_v2->is_new_structure_available()) {
                $module = $database_v2->get_module_by_view_parameter($module_or_view);
                if (!$module) {
                    $module = $database_v2->get_module_by_slug($module_or_view);
                }
            }
            
            // Fallback to old database structure
            if (!$module) {
                $database = new License_Manager_Database();
                $module = $database->get_module_by_view_parameter($module_or_view);
            }
            
            if ($module) {
                $module_slug = isset($module->slug) ? $module->slug : $module_or_view;
                $access_allowed = in_array($module_slug, $allowed_modules);
            }
        }
        
        return new WP_REST_Response(array(
            'access_allowed' => $access_allowed,
            'module_or_view' => $module_or_view,
            'license_status' => $license_data['status'],
            'allowed_modules' => $allowed_modules,
            'message' => $access_allowed ? 
                __('Erişim izni verildi', 'license-manager') : 
                __('Bu modüle erişim izniniz yok', 'license-manager'),
            'debug_info' => array(
                'using_new_db' => $database_v2->is_new_structure_available(),
                'module_found' => isset($module),
                'checked_modules' => $allowed_modules
            )
        ), 200);
    }
    
    /**
     * Get module by view parameter
     */
    public function get_module_by_view($request) {
        $view_parameter = $request->get_param('view');
        
        // Try new database structure first
        $database_v2 = new License_Manager_Database_V2();
        $module = null;
        $using_new_db = false;
        
        if ($database_v2->is_new_structure_available()) {
            $module = $database_v2->get_module_by_view_parameter($view_parameter);
            if (!$module) {
                $module = $database_v2->get_module_by_slug($view_parameter);
            }
            $using_new_db = true;
        }
        
        // Fallback to old database structure
        if (!$module) {
            $database = new License_Manager_Database();
            $module = $database->get_module_by_view_parameter($view_parameter);
            $using_new_db = false;
        }
        
        if (!$module) {
            return new WP_REST_Response(array(
                'found' => false,
                'message' => __('Modül bulunamadı', 'license-manager'),
                'searched_view' => $view_parameter,
                'using_new_db' => $using_new_db
            ), 404);
        }
        
        // Format response based on database structure
        if ($using_new_db) {
            $response_data = array(
                'id' => $module->id,
                'name' => $module->name,
                'slug' => $module->slug,
                'view_parameter' => $module->view_parameter,
                'description' => $module->description,
                'category' => $module->category,
                'is_core' => $module->is_core,
                'is_active' => $module->is_active
            );
        } else {
            $response_data = array(
                'id' => $module->term_id,
                'name' => $module->name,
                'slug' => $module->slug,
                'view_parameter' => $module->view_parameter ?? '',
                'description' => $module->description ?? '',
                'category' => $module->category ?? 'general'
            );
        }
        
        return new WP_REST_Response(array(
            'found' => true,
            'module' => $response_data,
            'using_new_db' => $using_new_db
        ), 200);
    }
    
    /**
     * Get all available modules endpoint
     */
    public function get_all_modules($request) {
        // Try new database structure first
        $database_v2 = new License_Manager_Database_V2();
        $modules = array();
        $using_new_db = false;
        
        if ($database_v2->is_new_structure_available()) {
            $modules = $database_v2->get_available_modules();
            $using_new_db = true;
        }
        
        // Fallback to old database structure if needed
        if (empty($modules)) {
            $database = new License_Manager_Database();
            $modules = $database->get_available_modules();
            $using_new_db = false;
        }
        
        return new WP_REST_Response(array(
            'success' => true,
            'modules' => $modules,
            'count' => count($modules),
            'using_new_db' => $using_new_db
        ), 200);
    }
    
    /**
     * Get restricted modules for user limit exceeded scenarios
     */
    public function get_restricted_modules($request) {
        $license_key = $request->get_param('license_key');
        $domain = $request->get_param('domain');
        
        // Validate license first
        $api = new License_Manager_API();
        $license_data = $api->get_license_data($license_key);
        
        if (!$license_data) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => __('Geçersiz lisans anahtarı', 'license-manager')
            ), 400);
        }
        
        // Check domain if provided
        if (!empty($domain) && !$api->is_domain_allowed($license_data, $domain)) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => __('Bu lisans için alan adı yetkilendirilmemiş', 'license-manager')
            ), 403);
        }
        
        // Get restricted modules from new database structure first
        $database_v2 = new License_Manager_Database_V2();
        $restricted_modules = array();
        
        if ($database_v2->is_new_structure_available()) {
            $restricted_modules = $database_v2->get_setting('restricted_modules_on_limit_exceeded', null);
        }
        
        // Fallback to default restricted modules
        if (empty($restricted_modules) || !is_array($restricted_modules)) {
            $restricted_modules = array(
                'license-management',
                'customer-representatives'
            );
        }
        
        return new WP_REST_Response(array(
            'success' => true,
            'restricted_modules' => $restricted_modules,
            'message' => __('Kullanıcı limiti aşıldığında erişilebilir modüller', 'license-manager')
        ), 200);
    }
}

// Initialize the extended API if this file is included
if (class_exists('License_Manager_API')) {
    new License_Manager_Modules_API();
}