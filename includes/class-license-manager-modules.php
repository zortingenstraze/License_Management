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
     * Database instance
     */
    private $database;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->database = new License_Manager_Database();
        
        // Add admin hooks
        add_action('admin_post_license_manager_add_module', array($this, 'handle_add_module'));
        add_action('admin_post_license_manager_edit_module', array($this, 'handle_edit_module'));
        add_action('admin_post_license_manager_delete_module', array($this, 'handle_delete_module'));
    }
    
    /**
     * Get all available modules
     */
    public function get_modules() {
        return $this->database->get_available_modules();
    }
    
    /**
     * Get module by ID
     */
    public function get_module($term_id) {
        $term = get_term($term_id, 'lm_modules');
        if (is_wp_error($term) || !$term) {
            return null;
        }
        
        // Add meta data
        $term->view_parameter = get_term_meta($term->term_id, 'view_parameter', true);
        $term->description = get_term_meta($term->term_id, 'description', true);
        $term->category = get_term_meta($term->term_id, 'category', true);
        
        return $term;
    }
    
    /**
     * Get module by view parameter
     */
    public function get_module_by_view_parameter($view_parameter) {
        return $this->database->get_module_by_view_parameter($view_parameter);
    }
    
    /**
     * Add new module
     */
    public function add_module($name, $slug, $view_parameter = '', $description = '', $category = '') {
        return $this->database->add_module($name, $slug, $view_parameter, $description, $category);
    }
    
    /**
     * Update module
     */
    public function update_module($term_id, $name = '', $view_parameter = '', $description = '', $category = '') {
        return $this->database->update_module($term_id, $name, $view_parameter, $description, $category);
    }
    
    /**
     * Delete module
     */
    public function delete_module($term_id) {
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
}