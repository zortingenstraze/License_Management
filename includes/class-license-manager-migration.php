<?php
/**
 * Database Migration Class
 * Handles migration from WordPress post types to custom database tables
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class License_Manager_Migration {
    
    /**
     * Database version for tracking migrations
     */
    const DB_VERSION = '2.0.0';
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', array($this, 'check_and_run_migration'));
    }
    
    /**
     * Check if migration is needed and run it
     */
    public function check_and_run_migration() {
        $current_db_version = get_option('license_manager_db_version', '1.0.0');
        
        if (version_compare($current_db_version, self::DB_VERSION, '<')) {
            $this->run_migration();
        }
    }
    
    /**
     * Run the complete migration process
     */
    public function run_migration() {
        global $wpdb;
        
        error_log('License Manager: Starting database migration to version ' . self::DB_VERSION);
        
        // Create new tables
        $this->create_new_tables();
        
        // Migrate existing data
        $this->migrate_existing_data();
        
        // Update database version
        update_option('license_manager_db_version', self::DB_VERSION);
        
        error_log('License Manager: Database migration completed successfully');
    }
    
    /**
     * Create new database tables according to specification
     */
    public function create_new_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // 1. Customers table
        $customers_table = $wpdb->prefix . 'icrm_license_management_customers';
        $customers_sql = "CREATE TABLE $customers_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            email varchar(255) DEFAULT NULL,
            phone varchar(50) DEFAULT NULL,
            website varchar(255) DEFAULT NULL,
            address text DEFAULT NULL,
            allowed_domains text DEFAULT NULL,
            notes text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY email (email),
            KEY name_index (name)
        ) $charset_collate;";
        
        // 2. Licenses table
        $licenses_table = $wpdb->prefix . 'icrm_license_management_licenses';
        $licenses_sql = "CREATE TABLE $licenses_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            customer_id bigint(20) unsigned NOT NULL,
            license_key varchar(255) NOT NULL,
            status enum('active','expired','suspended','invalid') DEFAULT 'active',
            license_type enum('monthly','yearly','lifetime') DEFAULT 'yearly',
            package_id bigint(20) unsigned DEFAULT NULL,
            user_limit int unsigned DEFAULT 5,
            expires_on date DEFAULT NULL,
            allowed_domains text DEFAULT NULL,
            last_check datetime DEFAULT NULL,
            notes text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY license_key (license_key),
            KEY customer_id (customer_id),
            KEY package_id (package_id),
            KEY status (status),
            KEY expires_on (expires_on),
            FOREIGN KEY (customer_id) REFERENCES {$wpdb->prefix}icrm_license_management_customers(id) ON DELETE CASCADE
        ) $charset_collate;";
        
        // 3. License Packages table
        $packages_table = $wpdb->prefix . 'icrm_license_management_license_packages';
        $packages_sql = "CREATE TABLE $packages_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            description text DEFAULT NULL,
            license_type enum('monthly','yearly','lifetime') DEFAULT 'yearly',
            user_limit int unsigned DEFAULT 5,
            price decimal(10,2) DEFAULT 0.00,
            currency varchar(3) DEFAULT 'USD',
            features json DEFAULT NULL,
            is_active boolean DEFAULT true,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY name_index (name),
            KEY license_type (license_type),
            KEY is_active (is_active)
        ) $charset_collate;";
        
        // 4. Payments table
        $payments_table = $wpdb->prefix . 'icrm_license_management_payments';
        $payments_sql = "CREATE TABLE $payments_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            license_id bigint(20) unsigned NOT NULL,
            customer_id bigint(20) unsigned NOT NULL,
            amount decimal(10,2) NOT NULL,
            currency varchar(3) DEFAULT 'USD',
            payment_method varchar(50) DEFAULT NULL,
            transaction_id varchar(255) DEFAULT NULL,
            status enum('completed','pending','failed','refunded') DEFAULT 'pending',
            payment_date datetime DEFAULT NULL,
            notes text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY license_id (license_id),
            KEY customer_id (customer_id),
            KEY status (status),
            KEY payment_date (payment_date),
            KEY transaction_id (transaction_id),
            FOREIGN KEY (license_id) REFERENCES {$wpdb->prefix}icrm_license_management_licenses(id) ON DELETE CASCADE,
            FOREIGN KEY (customer_id) REFERENCES {$wpdb->prefix}icrm_license_management_customers(id) ON DELETE CASCADE
        ) $charset_collate;";
        
        // 5. Modules table
        $modules_table = $wpdb->prefix . 'icrm_license_management_modules';
        $modules_sql = "CREATE TABLE $modules_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            slug varchar(255) NOT NULL,
            view_parameter varchar(255) DEFAULT NULL,
            description text DEFAULT NULL,
            category varchar(100) DEFAULT 'custom',
            is_core boolean DEFAULT false,
            is_active boolean DEFAULT true,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug),
            UNIQUE KEY view_parameter (view_parameter),
            KEY name_index (name),
            KEY category (category),
            KEY is_active (is_active),
            KEY is_core (is_core)
        ) $charset_collate;";
        
        // 6. Settings table
        $settings_table = $wpdb->prefix . 'icrm_license_management_settings';
        $settings_sql = "CREATE TABLE $settings_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            setting_key varchar(255) NOT NULL,
            setting_value longtext DEFAULT NULL,
            setting_type enum('string','int','bool','json') DEFAULT 'string',
            description text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY setting_key (setting_key),
            KEY setting_type (setting_type)
        ) $charset_collate;";
        
        // Create license-module relationship table
        $license_modules_table = $wpdb->prefix . 'icrm_license_management_license_modules';
        $license_modules_sql = "CREATE TABLE $license_modules_table (
            license_id bigint(20) unsigned NOT NULL,
            module_id bigint(20) unsigned NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (license_id, module_id),
            KEY license_id (license_id),
            KEY module_id (module_id),
            FOREIGN KEY (license_id) REFERENCES {$wpdb->prefix}icrm_license_management_licenses(id) ON DELETE CASCADE,
            FOREIGN KEY (module_id) REFERENCES {$wpdb->prefix}icrm_license_management_modules(id) ON DELETE CASCADE
        ) $charset_collate;";
        
        // Execute table creation
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        $tables = array(
            'customers' => $customers_sql,
            'packages' => $packages_sql, // Create packages first (no dependencies)
            'licenses' => $licenses_sql,
            'payments' => $payments_sql,
            'modules' => $modules_sql,
            'settings' => $settings_sql,
            'license_modules' => $license_modules_sql
        );
        
        foreach ($tables as $table_name => $sql) {
            error_log("License Manager: Creating table $table_name");
            $result = dbDelta($sql);
            error_log("License Manager: Table $table_name creation result: " . print_r($result, true));
        }
        
        // Create default settings
        $this->create_default_settings();
        
        // Create default modules
        $this->create_default_modules();
    }
    
    /**
     * Create default settings
     */
    private function create_default_settings() {
        global $wpdb;
        
        $settings_table = $wpdb->prefix . 'icrm_license_management_settings';
        
        $default_settings = array(
            array(
                'setting_key' => 'default_user_limit',
                'setting_value' => '5',
                'setting_type' => 'int',
                'description' => 'Default user limit for new licenses'
            ),
            array(
                'setting_key' => 'grace_period_days',
                'setting_value' => '7',
                'setting_type' => 'int',
                'description' => 'Grace period in days after license expiry'
            ),
            array(
                'setting_key' => 'debug_mode',
                'setting_value' => 'false',
                'setting_type' => 'bool',
                'description' => 'Enable debug logging'
            ),
            array(
                'setting_key' => 'restricted_modules_on_limit_exceeded',
                'setting_value' => '["license-management", "customer-representatives", "license-restriction", "all_personnel", "personnel", "users"]',
                'setting_type' => 'json',
                'description' => 'Modules available when user limit is exceeded'
            )
        );
        
        foreach ($default_settings as $setting) {
            $wpdb->insert(
                $settings_table,
                $setting,
                array('%s', '%s', '%s', '%s')
            );
        }
        
        error_log('License Manager: Created default settings');
    }
    
    /**
     * Create default modules in new table
     */
    private function create_default_modules() {
        global $wpdb;
        
        $modules_table = $wpdb->prefix . 'icrm_license_management_modules';
        
        $default_modules = array(
            array(
                'name' => 'Dashboard',
                'slug' => 'dashboard',
                'view_parameter' => 'dashboard',
                'description' => 'Ana kontrol paneli ve genel bakış',
                'category' => 'core',
                'is_core' => true,
                'is_active' => true
            ),
            array(
                'name' => 'Lisans Yönetimi',
                'slug' => 'license-management',
                'view_parameter' => 'license-management',
                'description' => 'Lisans yönetimi ve kontrol paneli',
                'category' => 'core',
                'is_core' => true,
                'is_active' => true
            ),
            array(
                'name' => 'Müşteri Temsilcileri',
                'slug' => 'customer-representatives',
                'view_parameter' => 'all_personnel',
                'description' => 'Müşteri temsilcileri ve personel yönetimi',
                'category' => 'core',
                'is_core' => true,
                'is_active' => true
            ),
            array(
                'name' => 'Müşteriler',
                'slug' => 'customers',
                'view_parameter' => 'customers',
                'description' => 'Müşteri yönetimi ve bilgileri',
                'category' => 'management',
                'is_core' => false,
                'is_active' => true
            ),
            array(
                'name' => 'Poliçeler',
                'slug' => 'policies',
                'view_parameter' => 'policies',
                'description' => 'Poliçe yönetimi ve takibi',
                'category' => 'management',
                'is_core' => false,
                'is_active' => true
            ),
            array(
                'name' => 'Teklifler',
                'slug' => 'quotes',
                'view_parameter' => 'quotes',
                'description' => 'Teklif hazırlama ve yönetimi',
                'category' => 'management',
                'is_core' => false,
                'is_active' => true
            ),
            array(
                'name' => 'Satış Fırsatları',
                'slug' => 'sale-opportunities',
                'view_parameter' => 'sale_opportunities',
                'description' => 'Satış fırsatları ve pipeline yönetimi',
                'category' => 'sales',
                'is_core' => false,
                'is_active' => true
            ),
            array(
                'name' => 'Görevler',
                'slug' => 'tasks',
                'view_parameter' => 'tasks',
                'description' => 'Görev yönetimi ve takibi',
                'category' => 'productivity',
                'is_core' => false,
                'is_active' => true
            ),
            array(
                'name' => 'Raporlar',
                'slug' => 'reports',
                'view_parameter' => 'reports',
                'description' => 'Raporlama ve analiz',
                'category' => 'analytics',
                'is_core' => false,
                'is_active' => true
            ),
            array(
                'name' => 'Veri Aktarımı',
                'slug' => 'data-transfer',
                'view_parameter' => 'data-transfer',
                'description' => 'Veri içe/dışa aktarım işlemleri',
                'category' => 'tools',
                'is_core' => false,
                'is_active' => true
            )
        );
        
        foreach ($default_modules as $module) {
            $wpdb->insert(
                $modules_table,
                $module,
                array('%s', '%s', '%s', '%s', '%s', '%d', '%d')
            );
        }
        
        error_log('License Manager: Created ' . count($default_modules) . ' default modules');
    }
    
    /**
     * Migrate existing data from WordPress post types to new tables
     */
    public function migrate_existing_data() {
        error_log('License Manager: Starting data migration from WordPress post types');
        
        $this->migrate_customers();
        $this->migrate_license_packages();
        $this->migrate_licenses();
        $this->migrate_payments();
        
        error_log('License Manager: Data migration completed');
    }
    
    /**
     * Migrate customers from lm_customer posts
     */
    private function migrate_customers() {
        global $wpdb;
        
        $customers_table = $wpdb->prefix . 'icrm_license_management_customers';
        
        $customers = get_posts(array(
            'post_type' => 'lm_customer',
            'posts_per_page' => -1,
            'post_status' => 'any'
        ));
        
        $migrated = 0;
        foreach ($customers as $customer) {
            $data = array(
                'name' => $customer->post_title,
                'email' => get_post_meta($customer->ID, '_email', true),
                'phone' => get_post_meta($customer->ID, '_phone', true),
                'website' => get_post_meta($customer->ID, '_website', true),
                'address' => get_post_meta($customer->ID, '_address', true),
                'allowed_domains' => get_post_meta($customer->ID, '_allowed_domains', true),
                'notes' => $customer->post_content,
                'created_at' => $customer->post_date,
                'updated_at' => $customer->post_modified
            );
            
            $result = $wpdb->insert($customers_table, $data);
            if ($result) {
                $migrated++;
                // Store mapping for license migration
                update_post_meta($customer->ID, '_migrated_id', $wpdb->insert_id);
            }
        }
        
        error_log("License Manager: Migrated $migrated customers");
    }
    
    /**
     * Migrate license packages
     */
    private function migrate_license_packages() {
        global $wpdb;
        
        $packages_table = $wpdb->prefix . 'icrm_license_management_license_packages';
        
        $packages = get_posts(array(
            'post_type' => 'lm_license_package',
            'posts_per_page' => -1,
            'post_status' => 'any'
        ));
        
        $migrated = 0;
        foreach ($packages as $package) {
            $license_types = wp_get_object_terms($package->ID, 'lm_license_type');
            $license_type = !empty($license_types) ? $license_types[0]->slug : 'yearly';
            
            $features = get_post_meta($package->ID, '_features', true);
            if (!empty($features) && is_array($features)) {
                $features = json_encode($features);
            } else {
                $features = null;
            }
            
            $data = array(
                'name' => $package->post_title,
                'description' => $package->post_content,
                'license_type' => $license_type,
                'user_limit' => intval(get_post_meta($package->ID, '_user_limit', true)) ?: 5,
                'price' => floatval(get_post_meta($package->ID, '_price', true)) ?: 0.00,
                'currency' => get_post_meta($package->ID, '_currency', true) ?: 'USD',
                'features' => $features,
                'is_active' => $package->post_status === 'publish',
                'created_at' => $package->post_date,
                'updated_at' => $package->post_modified
            );
            
            $result = $wpdb->insert($packages_table, $data);
            if ($result) {
                $migrated++;
                update_post_meta($package->ID, '_migrated_id', $wpdb->insert_id);
            }
        }
        
        error_log("License Manager: Migrated $migrated license packages");
    }
    
    /**
     * Migrate licenses
     */
    private function migrate_licenses() {
        global $wpdb;
        
        $licenses_table = $wpdb->prefix . 'icrm_license_management_licenses';
        $license_modules_table = $wpdb->prefix . 'icrm_license_management_license_modules';
        
        $licenses = get_posts(array(
            'post_type' => 'lm_license',
            'posts_per_page' => -1,
            'post_status' => 'any'
        ));
        
        $migrated = 0;
        foreach ($licenses as $license) {
            // Get customer ID from migration
            $customer_post_id = get_post_meta($license->ID, '_customer_id', true);
            $customer_id = null;
            if ($customer_post_id) {
                $customer_id = get_post_meta($customer_post_id, '_migrated_id', true);
            }
            
            // Skip if no customer mapping found
            if (!$customer_id) {
                error_log("License Manager: Skipping license {$license->ID} - no customer mapping found");
                continue;
            }
            
            // Get package ID from migration
            $package_post_id = get_post_meta($license->ID, '_package_id', true);
            $package_id = null;
            if ($package_post_id) {
                $package_id = get_post_meta($package_post_id, '_migrated_id', true);
            }
            
            // Get license status and type
            $status_terms = wp_get_object_terms($license->ID, 'lm_license_status');
            $status = !empty($status_terms) ? $status_terms[0]->slug : 'active';
            
            $type_terms = wp_get_object_terms($license->ID, 'lm_license_type');
            $license_type = !empty($type_terms) ? $type_terms[0]->slug : 'yearly';
            
            $data = array(
                'customer_id' => $customer_id,
                'license_key' => get_post_meta($license->ID, '_license_key', true),
                'status' => $status,
                'license_type' => $license_type,
                'package_id' => $package_id,
                'user_limit' => intval(get_post_meta($license->ID, '_user_limit', true)) ?: 5,
                'expires_on' => get_post_meta($license->ID, '_expires_on', true),
                'allowed_domains' => get_post_meta($license->ID, '_allowed_domains', true),
                'last_check' => get_post_meta($license->ID, '_last_check', true),
                'notes' => $license->post_content,
                'created_at' => $license->post_date,
                'updated_at' => $license->post_modified
            );
            
            $result = $wpdb->insert($licenses_table, $data);
            if ($result) {
                $new_license_id = $wpdb->insert_id;
                $migrated++;
                update_post_meta($license->ID, '_migrated_id', $new_license_id);
                
                // Migrate license modules
                $modules = wp_get_object_terms($license->ID, 'lm_modules');
                foreach ($modules as $module_term) {
                    // Find corresponding module in new table
                    $module_row = $wpdb->get_row($wpdb->prepare(
                        "SELECT id FROM {$wpdb->prefix}icrm_license_management_modules WHERE slug = %s",
                        $module_term->slug
                    ));
                    
                    if ($module_row) {
                        $wpdb->insert(
                            $license_modules_table,
                            array(
                                'license_id' => $new_license_id,
                                'module_id' => $module_row->id
                            )
                        );
                    }
                }
            }
        }
        
        error_log("License Manager: Migrated $migrated licenses");
    }
    
    /**
     * Migrate payments
     */
    private function migrate_payments() {
        global $wpdb;
        
        $payments_table = $wpdb->prefix . 'icrm_license_management_payments';
        
        $payments = get_posts(array(
            'post_type' => 'lm_payment',
            'posts_per_page' => -1,
            'post_status' => 'any'
        ));
        
        $migrated = 0;
        foreach ($payments as $payment) {
            // Get license and customer IDs from migration
            $license_post_id = get_post_meta($payment->ID, '_license_id', true);
            $license_id = null;
            if ($license_post_id) {
                $license_id = get_post_meta($license_post_id, '_migrated_id', true);
            }
            
            $customer_post_id = get_post_meta($payment->ID, '_customer_id', true);
            $customer_id = null;
            if ($customer_post_id) {
                $customer_id = get_post_meta($customer_post_id, '_migrated_id', true);
            }
            
            // Skip if no mappings found
            if (!$license_id || !$customer_id) {
                error_log("License Manager: Skipping payment {$payment->ID} - missing mappings");
                continue;
            }
            
            // Get payment status
            $status_terms = wp_get_object_terms($payment->ID, 'lm_payment_status');
            $status = !empty($status_terms) ? $status_terms[0]->slug : 'pending';
            
            $data = array(
                'license_id' => $license_id,
                'customer_id' => $customer_id,
                'amount' => floatval(get_post_meta($payment->ID, '_amount', true)) ?: 0.00,
                'currency' => get_post_meta($payment->ID, '_currency', true) ?: 'USD',
                'payment_method' => get_post_meta($payment->ID, '_payment_method', true),
                'transaction_id' => get_post_meta($payment->ID, '_transaction_id', true),
                'status' => $status,
                'payment_date' => get_post_meta($payment->ID, '_payment_date', true),
                'notes' => $payment->post_content,
                'created_at' => $payment->post_date,
                'updated_at' => $payment->post_modified
            );
            
            $result = $wpdb->insert($payments_table, $data);
            if ($result) {
                $migrated++;
                update_post_meta($payment->ID, '_migrated_id', $wpdb->insert_id);
            }
        }
        
        error_log("License Manager: Migrated $migrated payments");
    }
    
    /**
     * Rollback migration (for testing/emergency use)
     */
    public function rollback_migration() {
        global $wpdb;
        
        error_log('License Manager: Starting migration rollback');
        
        // Drop new tables
        $tables = array(
            'icrm_license_management_license_modules',
            'icrm_license_management_payments',
            'icrm_license_management_licenses',
            'icrm_license_management_license_packages',
            'icrm_license_management_customers',
            'icrm_license_management_modules',
            'icrm_license_management_settings'
        );
        
        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}$table");
        }
        
        // Reset database version
        update_option('license_manager_db_version', '1.0.0');
        
        error_log('License Manager: Migration rollback completed');
    }
}