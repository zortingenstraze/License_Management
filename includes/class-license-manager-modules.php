<?php
/**
 * Module Management Class
 * Handles module management functionality
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class License_Manager_Modules {
    
    /**
     * Database instance (legacy)
     */
    private $database;
    
    /**
     * Database V2 instance (new structure)
     */
    private $database_v2;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->database = new License_Manager_Database();
        $this->database_v2 = new License_Manager_Database_V2();
        
        // Add admin hooks
        add_action('admin_post_license_manager_add_module', array($this, 'handle_add_module'));
        add_action('admin_post_license_manager_edit_module', array($this, 'handle_edit_module'));
        add_action('admin_post_license_manager_delete_module', array($this, 'handle_delete_module'));
        add_action('admin_post_license_manager_debug_modules', array($this, 'handle_debug_modules'));
        add_action('admin_post_license_manager_fix_modules', array($this, 'handle_fix_modules'));
        add_action('admin_post_license_manager_rebuild_modules', array($this, 'handle_rebuild_modules'));
    }
    
    /**
     * Get all available modules
     */
    public function get_modules() {
        // Use new database structure if available
        if ($this->database_v2->is_new_structure_available()) {
            error_log('License Manager Modules: Using new database structure for module retrieval');
            return $this->database_v2->get_available_modules();
        }
        
        // Fallback to legacy method
        error_log('License Manager Modules: Using legacy database structure for module retrieval');
        return $this->database->get_available_modules();
    }
    
    /**
     * Get module by ID
     */
    public function get_module($term_id) {
        // Use new database structure if available
        if ($this->database_v2->is_new_structure_available()) {
            error_log("License Manager Modules: Getting module by ID using new structure: $term_id");
            return $this->database_v2->get_module($term_id);
        }
        
        // Fallback to legacy method
        error_log("License Manager Modules: Getting module by ID using legacy structure: $term_id");
        
        // Ensure taxonomy is registered first
        if (!taxonomy_exists('lm_modules')) {
            error_log("License Manager Modules: Taxonomy not registered, registering now");
            $this->database->register_modules_taxonomy();
            // Force flush to ensure taxonomy is available
            flush_rewrite_rules(false);
            // Small delay to ensure registration is complete
            usleep(100000); // 0.1 seconds
        }
        
        // Try with retry logic for robustness
        $retry_count = 0;
        $max_retries = 3;
        $term = false;
        
        while ($retry_count < $max_retries && (is_wp_error($term) || !$term)) {
            // Clear relevant caches before retry if needed
            if ($retry_count > 0) {
                clean_term_cache($term_id, 'lm_modules');
                wp_cache_delete($term_id, 'lm_modules');
                error_log("License Manager Modules: Retry $retry_count - cleared caches for term: $term_id");
            }
            
            $term = get_term($term_id, 'lm_modules');
            
            if (is_wp_error($term)) {
                error_log("License Manager Modules: Error getting module (attempt " . ($retry_count + 1) . "): " . $term->get_error_message());
                if ($retry_count < $max_retries - 1) {
                    usleep(200000); // 0.2 seconds delay between retries
                }
            } elseif (!$term) {
                error_log("License Manager Modules: Module not found (attempt " . ($retry_count + 1) . ") for ID: $term_id");
                if ($retry_count < $max_retries - 1) {
                    usleep(200000); // 0.2 seconds delay between retries
                }
            }
            $retry_count++;
        }
        
        if (is_wp_error($term) || !$term) {
            error_log("License Manager Modules: Final failure - Module not found for ID: $term_id after $max_retries attempts");
            return null;
        }
        
        // Add meta data
        $term->view_parameter = get_term_meta($term->term_id, 'view_parameter', true);
        $term->description = get_term_meta($term->term_id, 'description', true);
        $term->category = get_term_meta($term->term_id, 'category', true);
        
        error_log("License Manager Modules: Successfully retrieved module: " . $term->name . " (ID: $term_id, view: " . $term->view_parameter . ")");
        return $term;
    }
    
    /**
     * Get module by view parameter
     */
    public function get_module_by_view_parameter($view_parameter) {
        // Use new database structure if available
        if ($this->database_v2->is_new_structure_available()) {
            return $this->database_v2->get_module_by_view_parameter($view_parameter);
        }
        
        // Fallback to legacy method
        return $this->database->get_module_by_view_parameter($view_parameter);
    }
    
    /**
     * Add new module
     */
    public function add_module($name, $slug, $view_parameter = '', $description = '', $category = '') {
        // Use new database structure if available
        if ($this->database_v2->is_new_structure_available()) {
            error_log("License Manager Modules: Adding module using new database structure");
            return $this->database_v2->add_module($name, $slug, $view_parameter, $description, $category);
        }
        
        // Fallback to legacy method
        error_log("License Manager Modules: Adding module using legacy database structure");
        return $this->database->add_module($name, $slug, $view_parameter, $description, $category);
    }
    
    /**
     * Update module
     */
    public function update_module($term_id, $name = '', $view_parameter = '', $description = '', $category = '') {
        // Use new database structure if available
        if ($this->database_v2->is_new_structure_available()) {
            error_log("License Manager Modules: Updating module using new database structure");
            return $this->database_v2->update_module($term_id, $name, $view_parameter, $description, $category);
        }
        
        // Fallback to legacy method
        error_log("License Manager Modules: Updating module using legacy database structure");
        return $this->database->update_module($term_id, $name, $view_parameter, $description, $category);
    }
    
    /**
     * Delete module
     */
    public function delete_module($term_id) {
        // Use new database structure if available
        if ($this->database_v2->is_new_structure_available()) {
            error_log("License Manager Modules: Deleting module using new database structure");
            return $this->database_v2->delete_module($term_id);
        }
        
        // Fallback to legacy method
        error_log("License Manager Modules: Deleting module using legacy database structure");
        return $this->database->delete_module($term_id);
    }
    
    /**
     * Validate view parameter format
     */
    public function validate_view_parameter($view_parameter) {
        // View parameter should be alphanumeric with hyphens, used for ?view= parameter
        return preg_match('/^[a-z0-9\-]+$/i', $view_parameter);
    }
    
    /**
     * Get module categories
     */
    public function get_module_categories() {
        $categories = array(
            'core' => __('Core Modules', 'license-manager'),
            'management' => __('Management', 'license-manager'),
            'sales' => __('Sales & Marketing', 'license-manager'),
            'analytics' => __('Analytics & Reports', 'license-manager'),
            'productivity' => __('Productivity', 'license-manager'),
            'tools' => __('Tools & Utilities', 'license-manager'),
            'custom' => __('Custom Modules', 'license-manager'),
        );
        
        return apply_filters('license_manager_module_categories', $categories);
    }
    
    /**
     * Handle add module form submission
     */
    public function handle_add_module() {
        // Check permissions
        if (!current_user_can('manage_license_manager')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        
        // Verify nonce
        if (!wp_verify_nonce($_POST['_wpnonce'], 'license_manager_add_module')) {
            wp_die(__('Security check failed.'));
        }
        
        // Sanitize input
        $name = sanitize_text_field($_POST['name']);
        $slug = sanitize_title($_POST['slug']);
        $view_parameter = sanitize_text_field($_POST['view_parameter']);
        $description = sanitize_textarea_field($_POST['description']);
        $category = sanitize_text_field($_POST['category']);
        
        // Validate required fields
        if (empty($name) || empty($slug)) {
            wp_redirect(add_query_arg(array(
                'page' => 'license-manager-modules',
                'action' => 'add',
                'error' => 'missing_fields'
            ), admin_url('admin.php')));
            exit;
        }
        
        // Validate view parameter format if provided
        if (!empty($view_parameter) && !$this->validate_view_parameter($view_parameter)) {
            wp_redirect(add_query_arg(array(
                'page' => 'license-manager-modules',
                'action' => 'add',
                'error' => 'invalid_view_parameter'
            ), admin_url('admin.php')));
            exit;
        }
        
        // Add module
        $result = $this->add_module($name, $slug, $view_parameter, $description, $category);
        
        if (is_wp_error($result)) {
            wp_redirect(add_query_arg(array(
                'page' => 'license-manager-modules',
                'action' => 'add',
                'error' => $result->get_error_code()
            ), admin_url('admin.php')));
            exit;
        }
        
        // Success
        wp_redirect(add_query_arg(array(
            'page' => 'license-manager-modules',
            'message' => 'module_added'
        ), admin_url('admin.php')));
        exit;
    }
    
    /**
     * Handle edit module form submission
     */
    public function handle_edit_module() {
        // Check permissions
        if (!current_user_can('manage_license_manager')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        
        // Verify nonce
        if (!wp_verify_nonce($_POST['_wpnonce'], 'license_manager_edit_module')) {
            wp_die(__('Security check failed.'));
        }
        
        // Get module ID
        $term_id = intval($_POST['term_id']);
        if (empty($term_id)) {
            wp_die(__('Invalid module ID.'));
        }
        
        // Sanitize input
        $name = sanitize_text_field($_POST['name']);
        $view_parameter = sanitize_text_field($_POST['view_parameter']);
        $description = sanitize_textarea_field($_POST['description']);
        $category = sanitize_text_field($_POST['category']);
        
        // Validate view parameter format if provided
        if (!empty($view_parameter) && !$this->validate_view_parameter($view_parameter)) {
            wp_redirect(add_query_arg(array(
                'page' => 'license-manager-modules',
                'action' => 'edit',
                'id' => $term_id,
                'error' => 'invalid_view_parameter'
            ), admin_url('admin.php')));
            exit;
        }
        
        // Update module
        $result = $this->update_module($term_id, $name, $view_parameter, $description, $category);
        
        if (is_wp_error($result)) {
            wp_redirect(add_query_arg(array(
                'page' => 'license-manager-modules',
                'action' => 'edit',
                'id' => $term_id,
                'error' => $result->get_error_code()
            ), admin_url('admin.php')));
            exit;
        }
        
        // Success
        wp_redirect(add_query_arg(array(
            'page' => 'license-manager-modules',
            'message' => 'module_updated'
        ), admin_url('admin.php')));
        exit;
    }
    
    /**
     * Handle delete module
     */
    public function handle_delete_module() {
        // Check permissions
        if (!current_user_can('manage_license_manager')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        
        // Verify nonce
        if (!wp_verify_nonce($_GET['_wpnonce'], 'license_manager_delete_module_' . $_GET['id'])) {
            wp_die(__('Security check failed.'));
        }
        
        // Get module ID
        $term_id = intval($_GET['id']);
        if (empty($term_id)) {
            wp_die(__('Invalid module ID.'));
        }
        
        // Delete module
        $result = $this->delete_module($term_id);
        
        if (is_wp_error($result)) {
            wp_redirect(add_query_arg(array(
                'page' => 'license-manager-modules',
                'error' => $result->get_error_code()
            ), admin_url('admin.php')));
            exit;
        }
        
        // Success
        wp_redirect(add_query_arg(array(
            'page' => 'license-manager-modules',
            'message' => 'module_deleted'
        ), admin_url('admin.php')));
        exit;
    }
    
    /**
     * Handle debug modules request
     */
    public function handle_debug_modules() {
        // Check permissions
        if (!current_user_can('manage_license_manager')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        
        // Get modules for debugging
        $modules = $this->get_modules();
        $debug_info = array();
        
        $debug_info[] = "=== Module Debug Information ===";
        $debug_info[] = "Taxonomy exists: " . (taxonomy_exists('lm_modules') ? 'Yes' : 'No');
        $debug_info[] = "Total modules found: " . count($modules);
        $debug_info[] = "Defaults created flag: " . (get_option('license_manager_defaults_created', false) ? 'Yes' : 'No');
        $debug_info[] = "";
        
        if (!empty($modules)) {
            $debug_info[] = "=== Module List ===";
            foreach ($modules as $module) {
                $debug_info[] = "ID: {$module->term_id} | Name: {$module->name} | Slug: {$module->slug}";
                $debug_info[] = "  View Parameter: " . get_term_meta($module->term_id, 'view_parameter', true);
                $debug_info[] = "  Description: " . get_term_meta($module->term_id, 'description', true);
                $debug_info[] = "  Category: " . get_term_meta($module->term_id, 'category', true);
                $debug_info[] = "";
            }
        } else {
            $debug_info[] = "No modules found!";
        }
        
        // Return debug info
        wp_redirect(add_query_arg(array(
            'page' => 'license-manager-modules',
            'debug_info' => base64_encode(implode("\n", $debug_info))
        ), admin_url('admin.php')));
        exit;
    }
    
    /**
     * Handle fix modules request  
     */
    public function handle_fix_modules() {
        // Check permissions
        if (!current_user_can('manage_license_manager')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        
        // Clear all module-related caches
        wp_cache_flush();
        clean_taxonomy_cache('lm_modules');
        delete_transient('insurance_crm_module_mappings');
        
        // Clear any cached module lists
        wp_cache_delete('lm_modules', 'terms');
        wp_cache_delete('license_manager_modules', 'terms');
        wp_cache_delete('all_ids', 'lm_modules');
        wp_cache_delete('get', 'lm_modules');
        
        // Force refresh default modules if needed
        $this->database->refresh_default_modules();
        
        // Success
        wp_redirect(add_query_arg(array(
            'page' => 'license-manager-modules',
            'message' => 'modules_fixed'
        ), admin_url('admin.php')));
        exit;
    }
    
    /**
     * Comprehensive module system test
     */
    public function test_module_system() {
        $results = array();
        
        // Test 1: Taxonomy registration
        $results[] = "=== Module System Test Results ===";
        $results[] = "1. Taxonomy Check";
        $results[] = "   lm_modules exists: " . (taxonomy_exists('lm_modules') ? 'YES' : 'NO');
        
        // Test 2: Direct term retrieval
        $results[] = "2. Direct Term Retrieval";
        $terms = get_terms(array('taxonomy' => 'lm_modules', 'hide_empty' => false));
        if (is_wp_error($terms)) {
            $results[] = "   ERROR: " . $terms->get_error_message();
        } else {
            $results[] = "   Found " . count($terms) . " terms directly";
            foreach ($terms as $term) {
                $results[] = "   - " . $term->name . " (ID: " . $term->term_id . ", slug: " . $term->slug . ")";
            }
        }
        
        // Test 3: Database class method
        $results[] = "3. Database Class Method";
        $db_modules = $this->database->get_available_modules();
        $results[] = "   Database class returned " . count($db_modules) . " modules";
        foreach ($db_modules as $module) {
            $view_param = isset($module->view_parameter) ? $module->view_parameter : 'none';
            $results[] = "   - " . $module->name . " (view: " . $view_param . ")";
        }
        
        // Test 4: Modules manager method
        $results[] = "4. Modules Manager Method";
        $manager_modules = $this->get_modules();
        $results[] = "   Manager returned " . count($manager_modules) . " modules";
        
        // Test 5: Options check
        $results[] = "5. Options Check";
        $results[] = "   license_manager_defaults_created: " . (get_option('license_manager_defaults_created', false) ? 'YES' : 'NO');
        
        // Test 6: Cache status
        $results[] = "6. Cache Status";
        $results[] = "   WP Object Cache: " . (wp_using_ext_object_cache() ? 'External' : 'Built-in');
        
        return $results;
    }
    
    /**
     * Force complete module system rebuild
     */
    public function rebuild_module_system() {
        error_log('License Manager: Starting complete module system rebuild');
        
        // Step 1: Clear all caches aggressively
        wp_cache_flush();
        clean_taxonomy_cache('lm_modules');
        delete_transient('insurance_crm_module_mappings');
        
        // Clear specific module caches
        wp_cache_delete('lm_modules', 'terms');
        wp_cache_delete('license_manager_modules', 'terms');
        wp_cache_delete('all_ids', 'lm_modules');
        wp_cache_delete('get', 'lm_modules');
        wp_cache_flush_group('terms');
        
        // Step 2: Force re-register taxonomy
        $this->database->register_modules_taxonomy();
        flush_rewrite_rules(false);
        
        // Step 3: Reset defaults flag
        delete_option('license_manager_defaults_created');
        
        // Step 4: Force recreate all default modules
        $this->database->force_create_default_modules();
        
        // Step 5: Update flag
        update_option('license_manager_defaults_created', true);
        
        // Step 6: Final cache clear
        wp_cache_flush();
        
        error_log('License Manager: Module system rebuild completed');
        
        return true;
    }
    
    /**
     * Handle rebuild modules request
     */
    public function handle_rebuild_modules() {
        // Check permissions
        if (!current_user_can('manage_license_manager')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        
        // Perform rebuild
        $this->rebuild_module_system();
        
        // Success
        wp_redirect(add_query_arg(array(
            'page' => 'license-manager-modules',
            'message' => 'modules_rebuilt'
        ), admin_url('admin.php')));
        exit;
    }
}