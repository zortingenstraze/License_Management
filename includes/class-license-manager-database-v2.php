<?php
/**
 * New Database Layer Class
 * Handles database operations using the new table structure
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class License_Manager_Database_V2 {
    
    /**
     * Database instance
     */
    private $wpdb;
    
    /**
     * Table names
     */
    private $customers_table;
    private $licenses_table;
    private $packages_table;
    private $payments_table;
    private $modules_table;
    private $settings_table;
    private $license_modules_table;
    
    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        
        // Set table names
        $this->customers_table = $wpdb->prefix . 'icrm_license_management_customers';
        $this->licenses_table = $wpdb->prefix . 'icrm_license_management_licenses';
        $this->packages_table = $wpdb->prefix . 'icrm_license_management_license_packages';
        $this->payments_table = $wpdb->prefix . 'icrm_license_management_payments';
        $this->modules_table = $wpdb->prefix . 'icrm_license_management_modules';
        $this->settings_table = $wpdb->prefix . 'icrm_license_management_settings';
        $this->license_modules_table = $wpdb->prefix . 'icrm_license_management_license_modules';
    }
    
    /**
     * Check if new database structure is available
     */
    public function is_new_structure_available() {
        $table_exists = $this->wpdb->get_var(
            "SHOW TABLES LIKE '{$this->customers_table}'"
        );
        return $table_exists === $this->customers_table;
    }
    
    // =====================================
    // MODULE MANAGEMENT METHODS
    // =====================================
    
    /**
     * Get all available modules
     */
    public function get_available_modules() {
        if (!$this->is_new_structure_available()) {
            error_log('License Manager V2: New structure not available, falling back to old method');
            return array();
        }
        
        $modules = $this->wpdb->get_results(
            "SELECT * FROM {$this->modules_table} WHERE is_active = 1 ORDER BY is_core DESC, name ASC"
        );
        
        error_log('License Manager V2: Retrieved ' . count($modules) . ' modules from new database structure');
        return $modules ?: array();
    }
    
    /**
     * Add new module
     */
    public function add_module($name, $slug, $view_parameter = '', $description = '', $category = 'custom') {
        if (!$this->is_new_structure_available()) {
            return new WP_Error('structure_unavailable', 'New database structure not available');
        }
        
        // Validate required fields
        if (empty($name) || empty($slug)) {
            return new WP_Error('missing_fields', 'Module name and slug are required');
        }
        
        // Check if module already exists
        $existing = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT id FROM {$this->modules_table} WHERE slug = %s OR view_parameter = %s",
            $slug, $view_parameter
        ));
        
        if ($existing) {
            return new WP_Error('module_exists', 'Module with this slug or view parameter already exists');
        }
        
        // Insert module
        $result = $this->wpdb->insert(
            $this->modules_table,
            array(
                'name' => sanitize_text_field($name),
                'slug' => sanitize_title($slug),
                'view_parameter' => sanitize_text_field($view_parameter),
                'description' => sanitize_textarea_field($description),
                'category' => sanitize_text_field($category),
                'is_core' => false,
                'is_active' => true
            ),
            array('%s', '%s', '%s', '%s', '%s', '%d', '%d')
        );
        
        if ($result === false) {
            return new WP_Error('insert_failed', 'Failed to insert module: ' . $this->wpdb->last_error);
        }
        
        $module_id = $this->wpdb->insert_id;
        error_log("License Manager V2: Successfully added module: $name (ID: $module_id)");
        
        return $module_id;
    }
    
    /**
     * Update existing module
     */
    public function update_module($module_id, $name = '', $view_parameter = '', $description = '', $category = '') {
        if (!$this->is_new_structure_available()) {
            return new WP_Error('structure_unavailable', 'New database structure not available');
        }
        
        // Check if module exists
        $existing = $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT * FROM {$this->modules_table} WHERE id = %d",
            $module_id
        ));
        
        if (!$existing) {
            return new WP_Error('module_not_found', 'Module not found');
        }
        
        // Prepare update data
        $update_data = array();
        $update_format = array();
        
        if (!empty($name)) {
            $update_data['name'] = sanitize_text_field($name);
            $update_format[] = '%s';
        }
        if ($view_parameter !== '') {
            $update_data['view_parameter'] = sanitize_text_field($view_parameter);
            $update_format[] = '%s';
        }
        if ($description !== '') {
            $update_data['description'] = sanitize_textarea_field($description);
            $update_format[] = '%s';
        }
        if ($category !== '') {
            $update_data['category'] = sanitize_text_field($category);
            $update_format[] = '%s';
        }
        
        if (empty($update_data)) {
            return new WP_Error('no_data', 'No data to update');
        }
        
        $update_data['updated_at'] = current_time('mysql');
        $update_format[] = '%s';
        
        $result = $this->wpdb->update(
            $this->modules_table,
            $update_data,
            array('id' => $module_id),
            $update_format,
            array('%d')
        );
        
        if ($result === false) {
            return new WP_Error('update_failed', 'Failed to update module: ' . $this->wpdb->last_error);
        }
        
        error_log("License Manager V2: Successfully updated module ID: $module_id");
        return $module_id;
    }
    
    /**
     * Delete module
     */
    public function delete_module($module_id) {
        if (!$this->is_new_structure_available()) {
            return new WP_Error('structure_unavailable', 'New database structure not available');
        }
        
        // Check if module exists and is not core
        $module = $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT * FROM {$this->modules_table} WHERE id = %d",
            $module_id
        ));
        
        if (!$module) {
            return new WP_Error('module_not_found', 'Module not found');
        }
        
        if ($module->is_core) {
            return new WP_Error('cannot_delete_core', 'Cannot delete core modules');
        }
        
        // Delete module (foreign key constraints will handle license_modules cleanup)
        $result = $this->wpdb->delete(
            $this->modules_table,
            array('id' => $module_id),
            array('%d')
        );
        
        if ($result === false) {
            return new WP_Error('delete_failed', 'Failed to delete module: ' . $this->wpdb->last_error);
        }
        
        error_log("License Manager V2: Successfully deleted module ID: $module_id");
        return true;
    }
    
    /**
     * Get module by ID
     */
    public function get_module($module_id) {
        if (!$this->is_new_structure_available()) {
            return null;
        }
        
        return $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT * FROM {$this->modules_table} WHERE id = %d",
            $module_id
        ));
    }
    
    /**
     * Get module by view parameter
     */
    public function get_module_by_view_parameter($view_parameter) {
        if (!$this->is_new_structure_available()) {
            return null;
        }
        
        return $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT * FROM {$this->modules_table} WHERE view_parameter = %s AND is_active = 1",
            $view_parameter
        ));
    }
    
    /**
     * Get module by slug
     */
    public function get_module_by_slug($slug) {
        if (!$this->is_new_structure_available()) {
            return null;
        }
        
        return $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT * FROM {$this->modules_table} WHERE slug = %s AND is_active = 1",
            $slug
        ));
    }
    
    // =====================================
    // LICENSE MANAGEMENT METHODS
    // =====================================
    
    /**
     * Get license by license key
     */
    public function get_license_by_key($license_key) {
        if (!$this->is_new_structure_available()) {
            return null;
        }
        
        return $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT l.*, c.name as customer_name, c.email as customer_email
             FROM {$this->licenses_table} l
             LEFT JOIN {$this->customers_table} c ON l.customer_id = c.id
             WHERE l.license_key = %s",
            $license_key
        ));
    }
    
    /**
     * Get license modules
     */
    public function get_license_modules($license_id) {
        if (!$this->is_new_structure_available()) {
            return array();
        }
        
        return $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT m.* FROM {$this->modules_table} m
             INNER JOIN {$this->license_modules_table} lm ON m.id = lm.module_id
             WHERE lm.license_id = %d AND m.is_active = 1",
            $license_id
        ));
    }
    
    /**
     * Check if license has access to module
     */
    public function license_has_module_access($license_id, $module_identifier) {
        if (!$this->is_new_structure_available()) {
            return false;
        }
        
        // Check by module slug first
        $count = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->license_modules_table} lm
             INNER JOIN {$this->modules_table} m ON lm.module_id = m.id
             WHERE lm.license_id = %d AND (m.slug = %s OR m.view_parameter = %s) AND m.is_active = 1",
            $license_id, $module_identifier, $module_identifier
        ));
        
        return $count > 0;
    }
    
    /**
     * Update license last check time
     */
    public function update_license_last_check($license_id) {
        if (!$this->is_new_structure_available()) {
            return false;
        }
        
        return $this->wpdb->update(
            $this->licenses_table,
            array('last_check' => current_time('mysql')),
            array('id' => $license_id),
            array('%s'),
            array('%d')
        );
    }
    
    // =====================================
    // SETTINGS METHODS
    // =====================================
    
    /**
     * Get setting value
     */
    public function get_setting($key, $default = null) {
        if (!$this->is_new_structure_available()) {
            return $default;
        }
        
        $setting = $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT setting_value, setting_type FROM {$this->settings_table} WHERE setting_key = %s",
            $key
        ));
        
        if (!$setting) {
            return $default;
        }
        
        // Convert based on type
        switch ($setting->setting_type) {
            case 'bool':
                return $setting->setting_value === 'true';
            case 'int':
                return intval($setting->setting_value);
            case 'json':
                return json_decode($setting->setting_value, true);
            default:
                return $setting->setting_value;
        }
    }
    
    /**
     * Set setting value
     */
    public function set_setting($key, $value, $type = 'string', $description = '') {
        if (!$this->is_new_structure_available()) {
            return false;
        }
        
        // Convert value based on type
        switch ($type) {
            case 'bool':
                $value = $value ? 'true' : 'false';
                break;
            case 'int':
                $value = strval(intval($value));
                break;
            case 'json':
                $value = json_encode($value);
                break;
        }
        
        // Try to update first
        $updated = $this->wpdb->update(
            $this->settings_table,
            array(
                'setting_value' => $value,
                'setting_type' => $type,
                'description' => $description,
                'updated_at' => current_time('mysql')
            ),
            array('setting_key' => $key),
            array('%s', '%s', '%s', '%s'),
            array('%s')
        );
        
        // If no rows affected, insert new setting
        if ($updated === 0) {
            $result = $this->wpdb->insert(
                $this->settings_table,
                array(
                    'setting_key' => $key,
                    'setting_value' => $value,
                    'setting_type' => $type,
                    'description' => $description
                ),
                array('%s', '%s', '%s', '%s')
            );
            
            return $result !== false;
        }
        
        return $updated !== false;
    }
    
    // =====================================
    // CUSTOMER METHODS
    // =====================================
    
    /**
     * Get customer by ID
     */
    public function get_customer($customer_id) {
        if (!$this->is_new_structure_available()) {
            return null;
        }
        
        return $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT * FROM {$this->customers_table} WHERE id = %d",
            $customer_id
        ));
    }
    
    /**
     * Get customer by email
     */
    public function get_customer_by_email($email) {
        if (!$this->is_new_structure_available()) {
            return null;
        }
        
        return $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT * FROM {$this->customers_table} WHERE email = %s",
            $email
        ));
    }
    
    // =====================================
    // STATISTICS METHODS
    // =====================================
    
    /**
     * Get dashboard statistics
     */
    public function get_dashboard_stats() {
        if (!$this->is_new_structure_available()) {
            return array(
                'total_customers' => 0,
                'total_licenses' => 0,
                'active_licenses' => 0,
                'expired_licenses' => 0,
                'total_modules' => 0
            );
        }
        
        $stats = array();
        
        $stats['total_customers'] = $this->wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->customers_table}"
        );
        
        $stats['total_licenses'] = $this->wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->licenses_table}"
        );
        
        $stats['active_licenses'] = $this->wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->licenses_table} WHERE status = 'active'"
        );
        
        $stats['expired_licenses'] = $this->wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->licenses_table} WHERE status = 'expired'"
        );
        
        $stats['total_modules'] = $this->wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->modules_table} WHERE is_active = 1"
        );
        
        return $stats;
    }
    
    /**
     * Get restricted modules when user limit is exceeded
     */
    public function get_restricted_modules_on_limit_exceeded() {
        $modules = $this->get_setting('restricted_modules_on_limit_exceeded', array('license-management', 'customer-representatives'));
        
        if (is_array($modules)) {
            return $modules;
        }
        
        // Fallback to default
        return array('license-management', 'customer-representatives');
    }
}