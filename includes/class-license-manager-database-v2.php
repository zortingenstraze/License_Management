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
    
    /**
     * Get all customers with pagination
     */
    public function get_customers($limit = 20, $offset = 0, $search = '') {
        if (!$this->is_new_structure_available()) {
            return array();
        }
        
        $where = '';
        $params = array();
        
        if (!empty($search)) {
            $where = "WHERE name LIKE %s OR email LIKE %s";
            $search_term = '%' . $this->wpdb->esc_like($search) . '%';
            $params = array($search_term, $search_term);
        }
        
        $sql = "SELECT * FROM {$this->customers_table} $where ORDER BY created_at DESC LIMIT %d OFFSET %d";
        $params[] = $limit;
        $params[] = $offset;
        
        return $this->wpdb->get_results($this->wpdb->prepare($sql, $params));
    }
    
    /**
     * Add new customer
     */
    public function add_customer($name, $email = '', $phone = '', $website = '', $address = '', $allowed_domains = '', $notes = '') {
        if (!$this->is_new_structure_available()) {
            return new WP_Error('structure_unavailable', 'New database structure not available');
        }
        
        // Validate required fields
        if (empty($name)) {
            return new WP_Error('missing_name', 'Customer name is required');
        }
        
        // Check if email already exists (if provided)
        if (!empty($email)) {
            $existing = $this->get_customer_by_email($email);
            if ($existing) {
                return new WP_Error('email_exists', 'Customer with this email already exists');
            }
        }
        
        // Insert customer
        $result = $this->wpdb->insert(
            $this->customers_table,
            array(
                'name' => sanitize_text_field($name),
                'email' => sanitize_email($email),
                'phone' => sanitize_text_field($phone),
                'website' => esc_url_raw($website),
                'address' => sanitize_textarea_field($address),
                'allowed_domains' => sanitize_textarea_field($allowed_domains),
                'notes' => sanitize_textarea_field($notes)
            ),
            array('%s', '%s', '%s', '%s', '%s', '%s', '%s')
        );
        
        if ($result === false) {
            return new WP_Error('insert_failed', 'Failed to insert customer: ' . $this->wpdb->last_error);
        }
        
        return $this->wpdb->insert_id;
    }
    
    /**
     * Update existing customer
     */
    public function update_customer($customer_id, $data = array()) {
        if (!$this->is_new_structure_available()) {
            return new WP_Error('structure_unavailable', 'New database structure not available');
        }
        
        // Check if customer exists
        $existing = $this->get_customer($customer_id);
        if (!$existing) {
            return new WP_Error('customer_not_found', 'Customer not found');
        }
        
        // Prepare update data
        $update_data = array();
        $update_format = array();
        
        $allowed_fields = array('name', 'email', 'phone', 'website', 'address', 'allowed_domains', 'notes');
        
        foreach ($allowed_fields as $field) {
            if (isset($data[$field])) {
                switch ($field) {
                    case 'name':
                        $update_data[$field] = sanitize_text_field($data[$field]);
                        break;
                    case 'email':
                        $update_data[$field] = sanitize_email($data[$field]);
                        break;
                    case 'website':
                        $update_data[$field] = esc_url_raw($data[$field]);
                        break;
                    case 'address':
                    case 'allowed_domains':
                    case 'notes':
                        $update_data[$field] = sanitize_textarea_field($data[$field]);
                        break;
                    default:
                        $update_data[$field] = sanitize_text_field($data[$field]);
                }
                $update_format[] = '%s';
            }
        }
        
        if (empty($update_data)) {
            return new WP_Error('no_data', 'No data to update');
        }
        
        $update_data['updated_at'] = current_time('mysql');
        $update_format[] = '%s';
        
        // Check email uniqueness if updating email
        if (isset($update_data['email']) && !empty($update_data['email'])) {
            $email_check = $this->wpdb->get_var($this->wpdb->prepare(
                "SELECT id FROM {$this->customers_table} WHERE email = %s AND id != %d",
                $update_data['email'], $customer_id
            ));
            if ($email_check) {
                return new WP_Error('email_exists', 'Another customer with this email already exists');
            }
        }
        
        $result = $this->wpdb->update(
            $this->customers_table,
            $update_data,
            array('id' => $customer_id),
            $update_format,
            array('%d')
        );
        
        if ($result === false) {
            return new WP_Error('update_failed', 'Failed to update customer: ' . $this->wpdb->last_error);
        }
        
        return $customer_id;
    }
    
    /**
     * Delete customer
     */
    public function delete_customer($customer_id) {
        if (!$this->is_new_structure_available()) {
            return new WP_Error('structure_unavailable', 'New database structure not available');
        }
        
        // Check if customer exists
        $customer = $this->get_customer($customer_id);
        if (!$customer) {
            return new WP_Error('customer_not_found', 'Customer not found');
        }
        
        // Check if customer has licenses (prevent deletion if has active licenses)
        $license_count = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->licenses_table} WHERE customer_id = %d AND status = 'active'",
            $customer_id
        ));
        
        if ($license_count > 0) {
            return new WP_Error('has_active_licenses', 'Cannot delete customer with active licenses');
        }
        
        // Delete customer
        $result = $this->wpdb->delete(
            $this->customers_table,
            array('id' => $customer_id),
            array('%d')
        );
        
        if ($result === false) {
            return new WP_Error('delete_failed', 'Failed to delete customer: ' . $this->wpdb->last_error);
        }
        
        return true;
    }
    
    // =====================================
    // LICENSE CRUD METHODS
    // =====================================
    
    /**
     * Get all licenses with pagination
     */
    public function get_licenses($limit = 20, $offset = 0, $search = '', $status = '') {
        if (!$this->is_new_structure_available()) {
            return array();
        }
        
        $where_conditions = array();
        $params = array();
        
        if (!empty($search)) {
            $where_conditions[] = "(l.license_key LIKE %s OR c.name LIKE %s OR c.email LIKE %s)";
            $search_term = '%' . $this->wpdb->esc_like($search) . '%';
            $params[] = $search_term;
            $params[] = $search_term;
            $params[] = $search_term;
        }
        
        if (!empty($status)) {
            $where_conditions[] = "l.status = %s";
            $params[] = $status;
        }
        
        $where = '';
        if (!empty($where_conditions)) {
            $where = 'WHERE ' . implode(' AND ', $where_conditions);
        }
        
        $sql = "SELECT l.*, c.name as customer_name, c.email as customer_email 
                FROM {$this->licenses_table} l
                LEFT JOIN {$this->customers_table} c ON l.customer_id = c.id
                $where 
                ORDER BY l.created_at DESC 
                LIMIT %d OFFSET %d";
        
        $params[] = $limit;
        $params[] = $offset;
        
        return $this->wpdb->get_results($this->wpdb->prepare($sql, $params));
    }
    
    /**
     * Get license by ID
     */
    public function get_license($license_id) {
        if (!$this->is_new_structure_available()) {
            return null;
        }
        
        return $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT l.*, c.name as customer_name, c.email as customer_email
             FROM {$this->licenses_table} l
             LEFT JOIN {$this->customers_table} c ON l.customer_id = c.id
             WHERE l.id = %d",
            $license_id
        ));
    }
    
    /**
     * Add new license
     */
    public function add_license($customer_id, $license_key, $status = 'active', $license_type = 'yearly', $package_id = null, $user_limit = 5, $expires_on = null, $allowed_domains = '', $notes = '') {
        if (!$this->is_new_structure_available()) {
            return new WP_Error('structure_unavailable', 'New database structure not available');
        }
        
        // Validate required fields
        if (empty($customer_id) || empty($license_key)) {
            return new WP_Error('missing_fields', 'Customer ID and license key are required');
        }
        
        // Check if customer exists
        $customer = $this->get_customer($customer_id);
        if (!$customer) {
            return new WP_Error('customer_not_found', 'Customer not found');
        }
        
        // Check if license key already exists
        $existing = $this->get_license_by_key($license_key);
        if ($existing) {
            return new WP_Error('license_exists', 'License key already exists');
        }
        
        // Generate expiry date if not provided
        if (empty($expires_on) && $license_type !== 'lifetime') {
            $days = ($license_type === 'monthly') ? 30 : 365;
            $expires_on = date('Y-m-d', strtotime("+$days days"));
        }
        
        // Insert license
        $result = $this->wpdb->insert(
            $this->licenses_table,
            array(
                'customer_id' => intval($customer_id),
                'license_key' => sanitize_text_field($license_key),
                'status' => sanitize_text_field($status),
                'license_type' => sanitize_text_field($license_type),
                'package_id' => $package_id ? intval($package_id) : null,
                'user_limit' => intval($user_limit),
                'expires_on' => $expires_on,
                'allowed_domains' => sanitize_textarea_field($allowed_domains),
                'notes' => sanitize_textarea_field($notes)
            ),
            array('%d', '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%s')
        );
        
        if ($result === false) {
            return new WP_Error('insert_failed', 'Failed to insert license: ' . $this->wpdb->last_error);
        }
        
        return $this->wpdb->insert_id;
    }
    
    /**
     * Update existing license
     */
    public function update_license($license_id, $data = array()) {
        if (!$this->is_new_structure_available()) {
            return new WP_Error('structure_unavailable', 'New database structure not available');
        }
        
        // Check if license exists
        $existing = $this->get_license($license_id);
        if (!$existing) {
            return new WP_Error('license_not_found', 'License not found');
        }
        
        // Prepare update data
        $update_data = array();
        $update_format = array();
        
        $allowed_fields = array('customer_id', 'license_key', 'status', 'license_type', 'package_id', 'user_limit', 'expires_on', 'allowed_domains', 'notes');
        
        foreach ($allowed_fields as $field) {
            if (isset($data[$field])) {
                switch ($field) {
                    case 'customer_id':
                    case 'package_id':
                    case 'user_limit':
                        $update_data[$field] = intval($data[$field]);
                        $update_format[] = '%d';
                        break;
                    case 'expires_on':
                        $update_data[$field] = $data[$field]; // Should be in Y-m-d format
                        $update_format[] = '%s';
                        break;
                    case 'allowed_domains':
                    case 'notes':
                        $update_data[$field] = sanitize_textarea_field($data[$field]);
                        $update_format[] = '%s';
                        break;
                    default:
                        $update_data[$field] = sanitize_text_field($data[$field]);
                        $update_format[] = '%s';
                }
            }
        }
        
        if (empty($update_data)) {
            return new WP_Error('no_data', 'No data to update');
        }
        
        $update_data['updated_at'] = current_time('mysql');
        $update_format[] = '%s';
        
        // Check license key uniqueness if updating license key
        if (isset($update_data['license_key'])) {
            $key_check = $this->wpdb->get_var($this->wpdb->prepare(
                "SELECT id FROM {$this->licenses_table} WHERE license_key = %s AND id != %d",
                $update_data['license_key'], $license_id
            ));
            if ($key_check) {
                return new WP_Error('license_key_exists', 'Another license with this key already exists');
            }
        }
        
        $result = $this->wpdb->update(
            $this->licenses_table,
            $update_data,
            array('id' => $license_id),
            $update_format,
            array('%d')
        );
        
        if ($result === false) {
            return new WP_Error('update_failed', 'Failed to update license: ' . $this->wpdb->last_error);
        }
        
        return $license_id;
    }
    
    /**
     * Delete license
     */
    public function delete_license($license_id) {
        if (!$this->is_new_structure_available()) {
            return new WP_Error('structure_unavailable', 'New database structure not available');
        }
        
        // Check if license exists
        $license = $this->get_license($license_id);
        if (!$license) {
            return new WP_Error('license_not_found', 'License not found');
        }
        
        // Delete license modules associations first
        $this->wpdb->delete(
            $this->license_modules_table,
            array('license_id' => $license_id),
            array('%d')
        );
        
        // Delete license
        $result = $this->wpdb->delete(
            $this->licenses_table,
            array('id' => $license_id),
            array('%d')
        );
        
        if ($result === false) {
            return new WP_Error('delete_failed', 'Failed to delete license: ' . $this->wpdb->last_error);
        }
        
        return true;
    }
    
    /**
     * Add module to license
     */
    public function add_license_module($license_id, $module_id) {
        if (!$this->is_new_structure_available()) {
            return new WP_Error('structure_unavailable', 'New database structure not available');
        }
        
        // Check if association already exists
        $exists = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->license_modules_table} WHERE license_id = %d AND module_id = %d",
            $license_id, $module_id
        ));
        
        if ($exists > 0) {
            return true; // Already exists
        }
        
        $result = $this->wpdb->insert(
            $this->license_modules_table,
            array(
                'license_id' => intval($license_id),
                'module_id' => intval($module_id)
            ),
            array('%d', '%d')
        );
        
        return $result !== false;
    }
    
    /**
     * Remove module from license
     */
    public function remove_license_module($license_id, $module_id) {
        if (!$this->is_new_structure_available()) {
            return new WP_Error('structure_unavailable', 'New database structure not available');
        }
        
        $result = $this->wpdb->delete(
            $this->license_modules_table,
            array(
                'license_id' => $license_id,
                'module_id' => $module_id
            ),
            array('%d', '%d')
        );
        
        return $result !== false;
    }
    
    // =====================================
    // PACKAGE CRUD METHODS
    // =====================================
    
    /**
     * Get all packages with pagination
     */
    public function get_packages($limit = 20, $offset = 0, $search = '') {
        if (!$this->is_new_structure_available()) {
            return array();
        }
        
        $where = '';
        $params = array();
        
        if (!empty($search)) {
            $where = "WHERE name LIKE %s OR description LIKE %s";
            $search_term = '%' . $this->wpdb->esc_like($search) . '%';
            $params = array($search_term, $search_term);
        }
        
        $sql = "SELECT * FROM {$this->packages_table} $where ORDER BY created_at DESC LIMIT %d OFFSET %d";
        $params[] = $limit;
        $params[] = $offset;
        
        return $this->wpdb->get_results($this->wpdb->prepare($sql, $params));
    }
    
    /**
     * Get package by ID
     */
    public function get_package($package_id) {
        if (!$this->is_new_structure_available()) {
            return null;
        }
        
        return $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT * FROM {$this->packages_table} WHERE id = %d",
            $package_id
        ));
    }
    
    /**
     * Add new package
     */
    public function add_package($name, $description = '', $price = 0.00, $duration_days = 365, $user_limit = 5, $features = '', $is_active = true) {
        if (!$this->is_new_structure_available()) {
            return new WP_Error('structure_unavailable', 'New database structure not available');
        }
        
        if (empty($name)) {
            return new WP_Error('missing_name', 'Package name is required');
        }
        
        $result = $this->wpdb->insert(
            $this->packages_table,
            array(
                'name' => sanitize_text_field($name),
                'description' => sanitize_textarea_field($description),
                'price' => floatval($price),
                'duration_days' => intval($duration_days),
                'user_limit' => intval($user_limit),
                'features' => sanitize_textarea_field($features),
                'is_active' => $is_active ? 1 : 0
            ),
            array('%s', '%s', '%f', '%d', '%d', '%s', '%d')
        );
        
        if ($result === false) {
            return new WP_Error('insert_failed', 'Failed to insert package: ' . $this->wpdb->last_error);
        }
        
        return $this->wpdb->insert_id;
    }
    
    /**
     * Update existing package
     */
    public function update_package($package_id, $data = array()) {
        if (!$this->is_new_structure_available()) {
            return new WP_Error('structure_unavailable', 'New database structure not available');
        }
        
        $existing = $this->get_package($package_id);
        if (!$existing) {
            return new WP_Error('package_not_found', 'Package not found');
        }
        
        $update_data = array();
        $update_format = array();
        
        $allowed_fields = array('name', 'description', 'price', 'duration_days', 'user_limit', 'features', 'is_active');
        
        foreach ($allowed_fields as $field) {
            if (isset($data[$field])) {
                switch ($field) {
                    case 'price':
                        $update_data[$field] = floatval($data[$field]);
                        $update_format[] = '%f';
                        break;
                    case 'duration_days':
                    case 'user_limit':
                        $update_data[$field] = intval($data[$field]);
                        $update_format[] = '%d';
                        break;
                    case 'is_active':
                        $update_data[$field] = $data[$field] ? 1 : 0;
                        $update_format[] = '%d';
                        break;
                    case 'description':
                    case 'features':
                        $update_data[$field] = sanitize_textarea_field($data[$field]);
                        $update_format[] = '%s';
                        break;
                    default:
                        $update_data[$field] = sanitize_text_field($data[$field]);
                        $update_format[] = '%s';
                }
            }
        }
        
        if (empty($update_data)) {
            return new WP_Error('no_data', 'No data to update');
        }
        
        $update_data['updated_at'] = current_time('mysql');
        $update_format[] = '%s';
        
        $result = $this->wpdb->update(
            $this->packages_table,
            $update_data,
            array('id' => $package_id),
            $update_format,
            array('%d')
        );
        
        if ($result === false) {
            return new WP_Error('update_failed', 'Failed to update package: ' . $this->wpdb->last_error);
        }
        
        return $package_id;
    }
    
    /**
     * Delete package
     */
    public function delete_package($package_id) {
        if (!$this->is_new_structure_available()) {
            return new WP_Error('structure_unavailable', 'New database structure not available');
        }
        
        $package = $this->get_package($package_id);
        if (!$package) {
            return new WP_Error('package_not_found', 'Package not found');
        }
        
        // Check if package is used by any licenses
        $license_count = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->licenses_table} WHERE package_id = %d",
            $package_id
        ));
        
        if ($license_count > 0) {
            return new WP_Error('package_in_use', 'Cannot delete package that is used by licenses');
        }
        
        $result = $this->wpdb->delete(
            $this->packages_table,
            array('id' => $package_id),
            array('%d')
        );
        
        if ($result === false) {
            return new WP_Error('delete_failed', 'Failed to delete package: ' . $this->wpdb->last_error);
        }
        
        return true;
    }
    
    // =====================================
    // PAYMENT CRUD METHODS
    // =====================================
    
    /**
     * Get all payments with pagination
     */
    public function get_payments($limit = 20, $offset = 0, $search = '', $status = '') {
        if (!$this->is_new_structure_available()) {
            return array();
        }
        
        $where_conditions = array();
        $params = array();
        
        if (!empty($search)) {
            $where_conditions[] = "(p.transaction_id LIKE %s OR c.name LIKE %s OR c.email LIKE %s)";
            $search_term = '%' . $this->wpdb->esc_like($search) . '%';
            $params[] = $search_term;
            $params[] = $search_term;
            $params[] = $search_term;
        }
        
        if (!empty($status)) {
            $where_conditions[] = "p.status = %s";
            $params[] = $status;
        }
        
        $where = '';
        if (!empty($where_conditions)) {
            $where = 'WHERE ' . implode(' AND ', $where_conditions);
        }
        
        $sql = "SELECT p.*, c.name as customer_name, c.email as customer_email, l.license_key
                FROM {$this->payments_table} p
                LEFT JOIN {$this->customers_table} c ON p.customer_id = c.id
                LEFT JOIN {$this->licenses_table} l ON p.license_id = l.id
                $where 
                ORDER BY p.created_at DESC 
                LIMIT %d OFFSET %d";
        
        $params[] = $limit;
        $params[] = $offset;
        
        return $this->wpdb->get_results($this->wpdb->prepare($sql, $params));
    }
    
    /**
     * Get payment by ID
     */
    public function get_payment($payment_id) {
        if (!$this->is_new_structure_available()) {
            return null;
        }
        
        return $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT p.*, c.name as customer_name, c.email as customer_email, l.license_key
             FROM {$this->payments_table} p
             LEFT JOIN {$this->customers_table} c ON p.customer_id = c.id
             LEFT JOIN {$this->licenses_table} l ON p.license_id = l.id
             WHERE p.id = %d",
            $payment_id
        ));
    }
    
    /**
     * Add new payment
     */
    public function add_payment($customer_id, $license_id = null, $amount, $currency = 'USD', $status = 'pending', $payment_method = '', $transaction_id = '', $notes = '') {
        if (!$this->is_new_structure_available()) {
            return new WP_Error('structure_unavailable', 'New database structure not available');
        }
        
        if (empty($customer_id) || empty($amount)) {
            return new WP_Error('missing_fields', 'Customer ID and amount are required');
        }
        
        $customer = $this->get_customer($customer_id);
        if (!$customer) {
            return new WP_Error('customer_not_found', 'Customer not found');
        }
        
        $result = $this->wpdb->insert(
            $this->payments_table,
            array(
                'customer_id' => intval($customer_id),
                'license_id' => $license_id ? intval($license_id) : null,
                'amount' => floatval($amount),
                'currency' => sanitize_text_field($currency),
                'status' => sanitize_text_field($status),
                'payment_method' => sanitize_text_field($payment_method),
                'transaction_id' => sanitize_text_field($transaction_id),
                'notes' => sanitize_textarea_field($notes)
            ),
            array('%d', '%d', '%f', '%s', '%s', '%s', '%s', '%s')
        );
        
        if ($result === false) {
            return new WP_Error('insert_failed', 'Failed to insert payment: ' . $this->wpdb->last_error);
        }
        
        return $this->wpdb->insert_id;
    }
    
    /**
     * Update existing payment
     */
    public function update_payment($payment_id, $data = array()) {
        if (!$this->is_new_structure_available()) {
            return new WP_Error('structure_unavailable', 'New database structure not available');
        }
        
        $existing = $this->get_payment($payment_id);
        if (!$existing) {
            return new WP_Error('payment_not_found', 'Payment not found');
        }
        
        $update_data = array();
        $update_format = array();
        
        $allowed_fields = array('customer_id', 'license_id', 'amount', 'currency', 'status', 'payment_method', 'transaction_id', 'notes');
        
        foreach ($allowed_fields as $field) {
            if (isset($data[$field])) {
                switch ($field) {
                    case 'customer_id':
                    case 'license_id':
                        $update_data[$field] = intval($data[$field]);
                        $update_format[] = '%d';
                        break;
                    case 'amount':
                        $update_data[$field] = floatval($data[$field]);
                        $update_format[] = '%f';
                        break;
                    case 'notes':
                        $update_data[$field] = sanitize_textarea_field($data[$field]);
                        $update_format[] = '%s';
                        break;
                    default:
                        $update_data[$field] = sanitize_text_field($data[$field]);
                        $update_format[] = '%s';
                }
            }
        }
        
        if (empty($update_data)) {
            return new WP_Error('no_data', 'No data to update');
        }
        
        $update_data['updated_at'] = current_time('mysql');
        $update_format[] = '%s';
        
        $result = $this->wpdb->update(
            $this->payments_table,
            $update_data,
            array('id' => $payment_id),
            $update_format,
            array('%d')
        );
        
        if ($result === false) {
            return new WP_Error('update_failed', 'Failed to update payment: ' . $this->wpdb->last_error);
        }
        
        return $payment_id;
    }
    
    /**
     * Delete payment
     */
    public function delete_payment($payment_id) {
        if (!$this->is_new_structure_available()) {
            return new WP_Error('structure_unavailable', 'New database structure not available');
        }
        
        $payment = $this->get_payment($payment_id);
        if (!$payment) {
            return new WP_Error('payment_not_found', 'Payment not found');
        }
        
        $result = $this->wpdb->delete(
            $this->payments_table,
            array('id' => $payment_id),
            array('%d')
        );
        
        if ($result === false) {
            return new WP_Error('delete_failed', 'Failed to delete payment: ' . $this->wpdb->last_error);
        }
        
        return true;
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