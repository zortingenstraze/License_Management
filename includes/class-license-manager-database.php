<?php
/**
 * Database Management Class
 * Handles custom post types, taxonomies, and database operations
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class License_Manager_Database {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', array($this, 'register_post_types'));
        add_action('init', array($this, 'register_taxonomies'));
    }
    
    /**
     * Create custom tables if needed (currently using WordPress post system)
     */
    public function create_tables() {
        // We're using WordPress custom post types instead of custom tables
        // This method is for future use if custom tables are needed
    }
    
    /**
     * Register custom post types
     */
    public function register_post_types() {
        $this->register_customer_post_type();
        $this->register_license_post_type();
        $this->register_license_package_post_type();
        $this->register_payment_post_type();
    }
    
    /**
     * Register Customer post type
     */
    private function register_customer_post_type() {
        $labels = array(
            'name' => __('Müşteriler', 'license-manager'),
            'singular_name' => __('Müşteri', 'license-manager'),
            'menu_name' => __('Müşteriler', 'license-manager'),
            'add_new' => __('Yeni Müşteri Ekle', 'license-manager'),
            'add_new_item' => __('Yeni Müşteri Ekle', 'license-manager'),
            'edit_item' => __('Müşteri Düzenle', 'license-manager'),
            'new_item' => __('Yeni Müşteri', 'license-manager'),
            'view_item' => __('Müşteri Görüntüle', 'license-manager'),
            'search_items' => __('Müşteri Ara', 'license-manager'),
            'not_found' => __('Müşteri bulunamadı', 'license-manager'),
            'not_found_in_trash' => __('Çöp kutusunda müşteri bulunamadı', 'license-manager'),
        );
        
        $args = array(
            'labels' => $labels,
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => false, // We'll add this to our custom menu
            'show_in_admin_bar' => false,
            'capability_type' => 'post',
            'capabilities' => array(
                'edit_post' => 'manage_license_manager',
                'read_post' => 'manage_license_manager',
                'delete_post' => 'manage_license_manager',
                'edit_posts' => 'manage_license_manager',
                'edit_others_posts' => 'manage_license_manager',
                'publish_posts' => 'manage_license_manager',
                'read_private_posts' => 'manage_license_manager',
            ),
            'hierarchical' => false,
            'supports' => array('title', 'editor', 'custom-fields'),
            'has_archive' => false,
            'rewrite' => false,
            'query_var' => false,
        );
        
        register_post_type('lm_customer', $args);
    }
    
    /**
     * Register License post type
     */
    private function register_license_post_type() {
        $labels = array(
            'name' => __('Lisanslar', 'license-manager'),
            'singular_name' => __('Lisans', 'license-manager'),
            'menu_name' => __('Lisanslar', 'license-manager'),
            'add_new' => __('Add New License', 'license-manager'),
            'add_new_item' => __('Add New License', 'license-manager'),
            'edit_item' => __('Edit License', 'license-manager'),
            'new_item' => __('New License', 'license-manager'),
            'view_item' => __('View License', 'license-manager'),
            'search_items' => __('Search Licenses', 'license-manager'),
            'not_found' => __('Lisans bulunamadı', 'license-manager'),
            'not_found_in_trash' => __('Çöp kutusunda lisans bulunamadı', 'license-manager'),
        );
        
        $args = array(
            'labels' => $labels,
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => false, // We'll add this to our custom menu
            'show_in_admin_bar' => false,
            'capability_type' => 'post',
            'capabilities' => array(
                'edit_post' => 'manage_license_manager',
                'read_post' => 'manage_license_manager',
                'delete_post' => 'manage_license_manager',
                'edit_posts' => 'manage_license_manager',
                'edit_others_posts' => 'manage_license_manager',
                'publish_posts' => 'manage_license_manager',
                'read_private_posts' => 'manage_license_manager',
            ),
            'hierarchical' => false,
            'supports' => array('title', 'custom-fields'),
            'has_archive' => false,
            'rewrite' => false,
            'query_var' => false,
        );
        
        register_post_type('lm_license', $args);
    }
    
    /**
     * Register License Package post type
     */
    private function register_license_package_post_type() {
        $labels = array(
            'name' => __('License Packages', 'license-manager'),
            'singular_name' => __('License Package', 'license-manager'),
            'menu_name' => __('License Packages', 'license-manager'),
            'add_new' => __('Yeni Paket Ekle', 'license-manager'),
            'add_new_item' => __('Add New License Package', 'license-manager'),
            'edit_item' => __('Edit License Package', 'license-manager'),
            'new_item' => __('New License Package', 'license-manager'),
            'view_item' => __('View License Package', 'license-manager'),
            'search_items' => __('Search License Packages', 'license-manager'),
            'not_found' => __('Lisans paketi bulunamadı', 'license-manager'),
            'not_found_in_trash' => __('Çöp kutusunda lisans paketi bulunamadı', 'license-manager'),
        );
        
        $args = array(
            'labels' => $labels,
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => false, // We'll add this to our custom menu
            'show_in_admin_bar' => false,
            'capability_type' => 'post',
            'capabilities' => array(
                'edit_post' => 'manage_license_manager',
                'read_post' => 'manage_license_manager',
                'delete_post' => 'manage_license_manager',
                'edit_posts' => 'manage_license_manager',
                'edit_others_posts' => 'manage_license_manager',
                'publish_posts' => 'manage_license_manager',
                'read_private_posts' => 'manage_license_manager',
            ),
            'hierarchical' => false,
            'supports' => array('title', 'editor', 'custom-fields'),
            'has_archive' => false,
            'rewrite' => false,
            'query_var' => false,
        );
        
        register_post_type('lm_license_package', $args);
    }
    
    /**
     * Register Payment post type
     */
    private function register_payment_post_type() {
        $labels = array(
            'name' => __('Ödemeler', 'license-manager'),
            'singular_name' => __('Ödeme', 'license-manager'),
            'menu_name' => __('Ödemeler', 'license-manager'),
            'add_new' => __('Yeni Ödeme Ekle', 'license-manager'),
            'add_new_item' => __('Yeni Ödeme Ekle', 'license-manager'),
            'edit_item' => __('Ödeme Düzenle', 'license-manager'),
            'new_item' => __('Yeni Ödeme', 'license-manager'),
            'view_item' => __('Ödeme Görüntüle', 'license-manager'),
            'search_items' => __('Ödeme Ara', 'license-manager'),
            'not_found' => __('Ödeme bulunamadı', 'license-manager'),
            'not_found_in_trash' => __('Çöp kutusunda ödeme bulunamadı', 'license-manager'),
        );
        
        $args = array(
            'labels' => $labels,
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => false, // We'll add this to our custom menu
            'show_in_admin_bar' => false,
            'capability_type' => 'post',
            'capabilities' => array(
                'edit_post' => 'manage_license_manager',
                'read_post' => 'manage_license_manager',
                'delete_post' => 'manage_license_manager',
                'edit_posts' => 'manage_license_manager',
                'edit_others_posts' => 'manage_license_manager',
                'publish_posts' => 'manage_license_manager',
                'read_private_posts' => 'manage_license_manager',
            ),
            'hierarchical' => false,
            'supports' => array('title', 'editor', 'custom-fields', 'thumbnail'),
            'has_archive' => false,
            'rewrite' => false,
            'query_var' => false,
        );
        
        register_post_type('lm_payment', $args);
    }
    
    /**
     * Register taxonomies
     */
    public function register_taxonomies() {
        $this->register_license_status_taxonomy();
        $this->register_license_type_taxonomy();
        $this->register_modules_taxonomy();
        $this->register_payment_status_taxonomy();
    }
    
    /**
     * Register License Status taxonomy
     */
    private function register_license_status_taxonomy() {
        $labels = array(
            'name' => __('License Status', 'license-manager'),
            'singular_name' => __('License Status', 'license-manager'),
            'menu_name' => __('License Status', 'license-manager'),
            'all_items' => __('Tüm Durumlar', 'license-manager'),
            'edit_item' => __('Durum Düzenle', 'license-manager'),
            'view_item' => __('Durum Görüntüle', 'license-manager'),
            'update_item' => __('Durum Güncelle', 'license-manager'),
            'add_new_item' => __('Yeni Durum Ekle', 'license-manager'),
            'new_item_name' => __('Yeni Durum Adı', 'license-manager'),
            'search_items' => __('Durum Ara', 'license-manager'),
        );
        
        $args = array(
            'labels' => $labels,
            'hierarchical' => false,
            'public' => false,
            'show_ui' => true,
            'show_admin_column' => true,
            'show_in_nav_menus' => false,
            'show_tagcloud' => false,
            'capabilities' => array(
                'manage_terms' => 'manage_license_manager',
                'edit_terms' => 'manage_license_manager',
                'delete_terms' => 'manage_license_manager',
                'assign_terms' => 'manage_license_manager',
            ),
        );
        
        register_taxonomy('lm_license_status', array('lm_license'), $args);
    }
    
    /**
     * Register License Type taxonomy
     */
    private function register_license_type_taxonomy() {
        $labels = array(
            'name' => __('License Types', 'license-manager'),
            'singular_name' => __('License Type', 'license-manager'),
            'menu_name' => __('License Types', 'license-manager'),
            'all_items' => __('Tüm Türler', 'license-manager'),
            'edit_item' => __('Tür Düzenle', 'license-manager'),
            'view_item' => __('Tür Görüntüle', 'license-manager'),
            'update_item' => __('Tür Güncelle', 'license-manager'),
            'add_new_item' => __('Yeni Tür Ekle', 'license-manager'),
            'new_item_name' => __('Yeni Tür Adı', 'license-manager'),
            'search_items' => __('Tür Ara', 'license-manager'),
        );
        
        $args = array(
            'labels' => $labels,
            'hierarchical' => true,
            'public' => false,
            'show_ui' => true,
            'show_admin_column' => true,
            'show_in_nav_menus' => false,
            'show_tagcloud' => false,
            'capabilities' => array(
                'manage_terms' => 'manage_license_manager',
                'edit_terms' => 'manage_license_manager',
                'delete_terms' => 'manage_license_manager',
                'assign_terms' => 'manage_license_manager',
            ),
        );
        
        register_taxonomy('lm_license_type', array('lm_license'), $args);
    }
    
    /**
     * Register Modules taxonomy
     */
    private function register_modules_taxonomy() {
        $labels = array(
            'name' => __('Modüller', 'license-manager'),
            'singular_name' => __('Modül', 'license-manager'),
            'menu_name' => __('Modüller', 'license-manager'),
            'all_items' => __('All Modules', 'license-manager'),
            'edit_item' => __('Edit Module', 'license-manager'),
            'view_item' => __('View Module', 'license-manager'),
            'update_item' => __('Update Module', 'license-manager'),
            'add_new_item' => __('Add New Module', 'license-manager'),
            'new_item_name' => __('New Module Name', 'license-manager'),
            'search_items' => __('Search Modules', 'license-manager'),
        );
        
        $args = array(
            'labels' => $labels,
            'hierarchical' => false,
            'public' => false,
            'show_ui' => true,
            'show_admin_column' => true,
            'show_in_nav_menus' => false,
            'show_tagcloud' => false,
            'capabilities' => array(
                'manage_terms' => 'manage_license_manager',
                'edit_terms' => 'manage_license_manager',
                'delete_terms' => 'manage_license_manager',
                'assign_terms' => 'manage_license_manager',
            ),
        );
        
        register_taxonomy('lm_modules', array('lm_license', 'lm_license_package'), $args);
    }
    
    /**
     * Register Payment Status taxonomy
     */
    private function register_payment_status_taxonomy() {
        $labels = array(
            'name' => __('Ödeme Durumu', 'license-manager'),
            'singular_name' => __('Ödeme Durumu', 'license-manager'),
            'menu_name' => __('Ödeme Durumları', 'license-manager'),
            'all_items' => __('Tüm Durumlar', 'license-manager'),
            'edit_item' => __('Durum Düzenle', 'license-manager'),
            'view_item' => __('Durum Görüntüle', 'license-manager'),
            'update_item' => __('Durum Güncelle', 'license-manager'),
            'add_new_item' => __('Yeni Durum Ekle', 'license-manager'),
            'new_item_name' => __('Yeni Durum Adı', 'license-manager'),
            'search_items' => __('Durum Ara', 'license-manager'),
        );
        
        $args = array(
            'labels' => $labels,
            'hierarchical' => false,
            'public' => false,
            'show_ui' => false,
            'show_admin_column' => true,
            'query_var' => false,
            'rewrite' => false,
        );
        
        register_taxonomy('lm_payment_status', array('lm_payment'), $args);
    }
    
    /**
     * Setup default data
     */
    public function setup_default_data() {
        $this->create_default_license_status();
        $this->create_default_license_types();
        $this->create_default_modules();
        $this->create_default_payment_status();
    }
    
    /**
     * Create default license status terms
     */
    private function create_default_license_status() {
        $statuses = array(
            'active' => __('Aktif', 'license-manager'),
            'expired' => __('Süresi Dolmuş', 'license-manager'),
            'invalid' => __('Geçersiz', 'license-manager'),
            'suspended' => __('Askıya Alınmış', 'license-manager'),
        );
        
        foreach ($statuses as $slug => $name) {
            if (!term_exists($slug, 'lm_license_status')) {
                wp_insert_term($name, 'lm_license_status', array('slug' => $slug));
            }
        }
    }
    
    /**
     * Create default license types
     */
    private function create_default_license_types() {
        $types = array(
            'monthly' => __('Aylık', 'license-manager'),
            'yearly' => __('Yıllık', 'license-manager'),
            'lifetime' => __('Yaşam Boyu', 'license-manager'),
            'trial' => __('Deneme', 'license-manager'),
        );
        
        foreach ($types as $slug => $name) {
            if (!term_exists($slug, 'lm_license_type')) {
                wp_insert_term($name, 'lm_license_type', array('slug' => $slug));
            }
        }
    }
    
    /**
     * Create default modules
     */
    private function create_default_modules() {
        $modules = array(
            'dashboard' => __('Dashboard', 'license-manager'),
            'customers' => __('Müşteriler', 'license-manager'),
            'policies' => __('Poliçeler', 'license-manager'),
            'quotes' => __('Teklifler', 'license-manager'),
            'tasks' => __('Görevler', 'license-manager'),
            'reports' => __('Raporlar', 'license-manager'),
            'data_transfer' => __('Veri Aktarımı', 'license-manager'),
        );
        
        foreach ($modules as $slug => $name) {
            if (!term_exists($slug, 'lm_modules')) {
                $result = wp_insert_term($name, 'lm_modules', array('slug' => $slug));
                if (is_wp_error($result)) {
                    error_log('License Manager: Failed to create module ' . $slug . ': ' . $result->get_error_message());
                } else {
                    error_log('License Manager: Created module ' . $slug . ' - ' . $name);
                }
            } else {
                error_log('License Manager: Module already exists - ' . $slug);
            }
        }
    }
    
    /**
     * Force create default modules - use when needed to ensure modules exist
     */
    public function force_create_default_modules() {
        // First ensure the taxonomy is registered
        $this->register_modules_taxonomy();
        
        // Flush rewrite rules to ensure taxonomy is properly registered
        flush_rewrite_rules();
        
        $modules = array(
            'dashboard' => array(
                'name' => __('Dashboard', 'license-manager'),
                'view_parameter' => 'dashboard',
                'description' => __('Ana kontrol paneli ve genel bakış', 'license-manager'),
                'category' => 'core'
            ),
            'customers' => array(
                'name' => __('Müşteriler', 'license-manager'),
                'view_parameter' => 'customers',
                'description' => __('Müşteri yönetimi ve bilgileri', 'license-manager'),
                'category' => 'management'
            ),
            'policies' => array(
                'name' => __('Poliçeler', 'license-manager'),
                'view_parameter' => 'policies',
                'description' => __('Poliçe yönetimi ve takibi', 'license-manager'),
                'category' => 'management'
            ),
            'quotes' => array(
                'name' => __('Teklifler', 'license-manager'),
                'view_parameter' => 'quotes',
                'description' => __('Teklif hazırlama ve yönetimi', 'license-manager'),
                'category' => 'management'
            ),
            'sale_opportunities' => array(
                'name' => __('Satış Fırsatları', 'license-manager'),
                'view_parameter' => 'sale_opportunities',
                'description' => __('Satış fırsatları ve pipeline yönetimi', 'license-manager'),
                'category' => 'sales'
            ),
            'tasks' => array(
                'name' => __('Görevler', 'license-manager'),
                'view_parameter' => 'tasks',
                'description' => __('Görev yönetimi ve takibi', 'license-manager'),
                'category' => 'productivity'
            ),
            'reports' => array(
                'name' => __('Raporlar', 'license-manager'),
                'view_parameter' => 'reports',
                'description' => __('Raporlama ve analiz', 'license-manager'),
                'category' => 'analytics'
            ),
            'data_transfer' => array(
                'name' => __('Veri Aktarımı', 'license-manager'),
                'view_parameter' => 'data-transfer',
                'description' => __('Veri içe/dışa aktarım işlemleri', 'license-manager'),
                'category' => 'tools'
            ),
        );
        
        $created_count = 0;
        foreach ($modules as $slug => $module_data) {
            $existing_term = term_exists($slug, 'lm_modules');
            if (!$existing_term) {
                $result = wp_insert_term($module_data['name'], 'lm_modules', array('slug' => $slug));
                if (!is_wp_error($result)) {
                    $term_id = $result['term_id'];
                    // Add meta data
                    update_term_meta($term_id, 'view_parameter', $module_data['view_parameter']);
                    update_term_meta($term_id, 'description', $module_data['description']);
                    update_term_meta($term_id, 'category', $module_data['category']);
                    
                    $created_count++;
                    error_log('License Manager: Force created module ' . $slug . ' - ' . $module_data['name']);
                } else {
                    error_log('License Manager: Failed to force create module ' . $slug . ': ' . $result->get_error_message());
                }
            } else {
                // Update existing modules with meta data if not present
                $term_id = is_array($existing_term) ? $existing_term['term_id'] : $existing_term;
                $term = get_term($term_id, 'lm_modules');
                
                if (!is_wp_error($term)) {
                    if (empty(get_term_meta($term_id, 'view_parameter', true))) {
                        update_term_meta($term_id, 'view_parameter', $module_data['view_parameter']);
                        error_log('License Manager: Updated view_parameter for existing module: ' . $slug);
                    }
                    if (empty(get_term_meta($term_id, 'description', true))) {
                        update_term_meta($term_id, 'description', $module_data['description']);
                        error_log('License Manager: Updated description for existing module: ' . $slug);
                    }
                    if (empty(get_term_meta($term_id, 'category', true))) {
                        update_term_meta($term_id, 'category', $module_data['category']);
                        error_log('License Manager: Updated category for existing module: ' . $slug);
                    }
                }
            }
        }
        
        // Clean any orphaned object relationships
        $this->clean_orphaned_module_relationships();
        
        error_log('License Manager: Force create modules completed. Created: ' . $created_count);
        return $created_count;
    }
    
    /**
     * Reset default modules creation flag
     */
    public function reset_defaults_flag() {
        delete_option('license_manager_defaults_created');
        error_log('License Manager: Reset defaults creation flag');
    }
    
    /**
     * Force refresh all default modules and fix any mapping issues
     */
    public function refresh_default_modules() {
        error_log('License Manager: Refreshing default modules');
        
        // Reset the flag first to allow recreation
        $this->reset_defaults_flag();
        
        // Clear all caches
        wp_cache_flush();
        delete_transient('insurance_crm_module_mappings');
        clean_taxonomy_cache('lm_modules');
        
        // Force recreation
        $created = $this->force_create_default_modules();
        
        error_log('License Manager: Refreshed default modules, created/updated: ' . $created);
        return $created;
    }
    
    /**
     * Clean orphaned module relationships (terms that exist but aren't properly linked)
     */
    private function clean_orphaned_module_relationships() {
        global $wpdb;
        
        // This helps ensure data consistency
        $wpdb->query("DELETE FROM {$wpdb->term_relationships} WHERE term_taxonomy_id NOT IN (SELECT term_taxonomy_id FROM {$wpdb->term_taxonomy})");
        
        error_log('License Manager: Cleaned orphaned module relationships');
    }
    
    /**
     * Create default payment status terms
     */
    private function create_default_payment_status() {
        $statuses = array(
            'pending' => __('Beklemede', 'license-manager'),
            'completed' => __('Tamamlandı', 'license-manager'),
            'failed' => __('Başarısız', 'license-manager'),
            'cancelled' => __('İptal Edildi', 'license-manager'),
            'refunded' => __('İade Edildi', 'license-manager'),
        );
        
        foreach ($statuses as $slug => $name) {
            if (!term_exists($slug, 'lm_payment_status')) {
                $result = wp_insert_term($name, 'lm_payment_status', array('slug' => $slug));
                if (is_wp_error($result)) {
                    error_log('License Manager: Failed to create payment status ' . $slug . ': ' . $result->get_error_message());
                } else {
                    error_log('License Manager: Created payment status ' . $slug . ' - ' . $name);
                }
            }
        }
    }
    
    /**
     * Force create default payment status terms (public method)
     */
    public function force_create_default_payment_status() {
        // Ensure taxonomy is registered first
        if (!taxonomy_exists('lm_payment_status')) {
            $this->register_payment_status_taxonomy();
        }
        
        $statuses = array(
            'pending' => __('Beklemede', 'license-manager'),
            'completed' => __('Tamamlandı', 'license-manager'),
            'failed' => __('Başarısız', 'license-manager'),
            'cancelled' => __('İptal Edildi', 'license-manager'),
            'refunded' => __('İade Edildi', 'license-manager'),
        );
        
        $created_count = 0;
        foreach ($statuses as $slug => $name) {
            if (!term_exists($slug, 'lm_payment_status')) {
                $result = wp_insert_term($name, 'lm_payment_status', array('slug' => $slug));
                if (is_wp_error($result)) {
                    error_log('License Manager: Failed to create payment status ' . $slug . ': ' . $result->get_error_message());
                } else {
                    error_log('License Manager: Force created payment status ' . $slug . ' - ' . $name);
                    $created_count++;
                }
            }
        }
        
        return $created_count;
    }
    
    /**
     * Get all available modules 
     */
    public function get_available_modules() {
        // Ensure taxonomy is registered
        if (!taxonomy_exists('lm_modules')) {
            error_log('License Manager: Taxonomy not registered, registering now');
            $this->register_modules_taxonomy();
            // Force flush rewrite rules to ensure taxonomy is available
            flush_rewrite_rules(false);
            // Allow some time for WordPress to process the taxonomy registration
            usleep(100000); // 0.1 seconds
        }
        
        // Try to get existing modules with retry logic and fresh cache
        $retry_count = 0;
        $max_retries = 3;
        $modules = false;
        
        while ($retry_count < $max_retries && (is_wp_error($modules) || $modules === false)) {
            // Force fresh cache on each retry
            if ($retry_count > 0) {
                wp_cache_flush();
                clean_taxonomy_cache('lm_modules');
                wp_cache_delete('lm_modules', 'terms');
                wp_cache_delete('license_manager_modules', 'terms');
            }
            
            $modules = get_terms(array(
                'taxonomy' => 'lm_modules',
                'hide_empty' => false,
                'cache_domain' => 'license_manager_modules_fresh_' . time(), // Force fresh cache
                'update_term_meta_cache' => true // Ensure meta is loaded
            ));
            
            if (is_wp_error($modules)) {
                error_log('License Manager: Error getting modules (attempt ' . ($retry_count + 1) . '): ' . $modules->get_error_message());
                if ($retry_count < $max_retries - 1) {
                    usleep(200000); // 0.2 seconds delay between retries
                }
            }
            $retry_count++;
        }
        
        // If we still have an error, return empty array
        if (is_wp_error($modules)) {
            error_log('License Manager: Final error getting modules: ' . $modules->get_error_message());
            return array();
        }
        
        // Enhanced logic: Handle empty modules more intelligently
        if (empty($modules)) {
            $defaults_created = get_option('license_manager_defaults_created', false);
            
            if (!$defaults_created) {
                error_log('License Manager: No modules found and defaults not created yet, creating defaults');
                $this->force_create_default_modules();
                update_option('license_manager_defaults_created', true);
            } else {
                error_log('License Manager: No modules found but defaults already created - attempting recovery');
                // If defaults were created but no modules found, this indicates a serious issue
                // Try to fix by recreating defaults and clearing the flag
                $this->force_create_default_modules();
                error_log('License Manager: Recreated default modules for recovery');
            }
            
            // Clear comprehensive caches after creating/recreating defaults
            wp_cache_flush();
            clean_taxonomy_cache('lm_modules');
            wp_cache_delete('lm_modules', 'terms');
            wp_cache_delete('license_manager_modules', 'terms');
            
            // Try again after creating defaults with a small delay
            usleep(100000); // 0.1 seconds
            $modules = get_terms(array(
                'taxonomy' => 'lm_modules',
                'hide_empty' => false,
                'cache_domain' => 'license_manager_modules_recovery_' . time(),
                'update_term_meta_cache' => true
            ));
            
            if (is_wp_error($modules)) {
                error_log('License Manager: Error getting modules after creating defaults: ' . $modules->get_error_message());
                return array();
            } else if (empty($modules)) {
                error_log('License Manager: Still no modules after recreation - major system issue');
                return array();
            } else {
                error_log('License Manager: Successfully recovered ' . count($modules) . ' modules');
            }
        }
        
        // Ensure we have an array
        if (!is_array($modules)) {
            error_log('License Manager: Modules is not an array, returning empty array');
            return array();
        }
        
        // Add meta data for each module (view parameters, description, etc.)
        foreach ($modules as $module) {
            if (is_object($module) && isset($module->term_id)) {
                $module->view_parameter = get_term_meta($module->term_id, 'view_parameter', true);
                $module->description = get_term_meta($module->term_id, 'description', true);
                $module->category = get_term_meta($module->term_id, 'category', true);
            }
        }
        
        error_log('License Manager: Successfully retrieved ' . count($modules) . ' modules');
        foreach ($modules as $module) {
            if (is_object($module)) {
                error_log('License Manager: Module - ' . $module->name . ' (slug: ' . $module->slug . ', view: ' . ($module->view_parameter ?? 'none') . ')');
            }
        }
        
        return $modules;
    }
    
    /**
     * Add new module with view parameter support
     */
    public function add_module($name, $slug, $view_parameter = '', $description = '', $category = '') {
        // Ensure the taxonomy is registered
        if (!taxonomy_exists('lm_modules')) {
            error_log("License Manager: Taxonomy not registered during addition, registering now");
            $this->register_modules_taxonomy();
            // Force flush rewrite rules to ensure taxonomy is available
            flush_rewrite_rules(false);
            // Small delay to ensure taxonomy is fully registered
            usleep(100000); // 0.1 seconds
        }
        
        // Sanitize inputs
        $name = sanitize_text_field($name);
        $slug = sanitize_title($slug);
        $view_parameter = sanitize_text_field($view_parameter);
        $description = sanitize_textarea_field($description);
        $category = sanitize_text_field($category);
        
        // Validate required fields
        if (empty($name) || empty($slug)) {
            error_log("License Manager: Cannot add module - missing required fields (name: '$name', slug: '$slug')");
            return new WP_Error('missing_fields', __('Module name and slug are required', 'license-manager'));
        }
        
        // Check if module already exists
        $existing = term_exists($slug, 'lm_modules');
        if ($existing) {
            error_log("License Manager: Module already exists: $slug (term ID: " . $existing['term_id'] . ")");
            return new WP_Error('module_exists', __('Module already exists', 'license-manager'));
        }
        
        error_log("License Manager: Creating new module - Name: '$name', Slug: '$slug', View: '$view_parameter'");
        
        // Create the module term
        $result = wp_insert_term($name, 'lm_modules', array('slug' => $slug));
        
        if (is_wp_error($result)) {
            error_log("License Manager: Failed to create module $slug: " . $result->get_error_message());
            return $result;
        }
        
        $term_id = $result['term_id'];
        error_log("License Manager: Successfully created module $slug with ID: $term_id");
        
        // Add meta data
        if (!empty($view_parameter)) {
            $meta_result = update_term_meta($term_id, 'view_parameter', $view_parameter);
            error_log("License Manager: Added view_parameter: $view_parameter (result: " . ($meta_result ? 'success' : 'failed') . ")");
        }
        if (!empty($description)) {
            $meta_result = update_term_meta($term_id, 'description', $description);
            error_log("License Manager: Added description (result: " . ($meta_result ? 'success' : 'failed') . ")");
        }
        if (!empty($category)) {
            $meta_result = update_term_meta($term_id, 'category', $category);
            error_log("License Manager: Added category: $category (result: " . ($meta_result ? 'success' : 'failed') . ")");
        }
        
        // Clear comprehensive caches to ensure new module is visible immediately
        wp_cache_flush(); // Clear all WordPress cache
        delete_transient('insurance_crm_module_mappings'); // Clear client-side cache
        clean_term_cache($term_id, 'lm_modules'); // Clear specific term cache
        clean_taxonomy_cache('lm_modules'); // Clear taxonomy cache
        
        // Ensure taxonomy cache is cleared
        wp_cache_delete('all_ids', 'lm_modules');
        wp_cache_delete('get', 'lm_modules');
        
        // Force clear any get_terms caches
        wp_cache_delete('lm_modules', 'terms');
        wp_cache_delete('license_manager_modules', 'terms');
        
        // Clear object cache for module queries
        wp_cache_flush_group('terms');
        
        // Clear any cached module lists
        delete_option('_transient_timeout_lm_modules_cache');
        delete_option('_transient_lm_modules_cache');
        
        error_log("License Manager: Cleared comprehensive caches for new module: $slug (ID: $term_id)");
        
        return $term_id;
    }
    
    /**
     * Update existing module
     */
    public function update_module($term_id, $name = '', $view_parameter = '', $description = '', $category = '') {
        // Check if term exists
        $term = get_term($term_id, 'lm_modules');
        if (is_wp_error($term) || !$term) {
            error_log("License Manager: Module not found for update: $term_id");
            return new WP_Error('module_not_found', __('Module not found', 'license-manager'));
        }
        
        error_log("License Manager: Updating module ID: $term_id");
        
        // Update term name if provided
        if (!empty($name)) {
            $result = wp_update_term($term_id, 'lm_modules', array('name' => $name));
            if (is_wp_error($result)) {
                error_log("License Manager: Failed to update module name: " . $result->get_error_message());
                return $result;
            }
        }
        
        // Update meta data
        if ($view_parameter !== '') {
            update_term_meta($term_id, 'view_parameter', sanitize_text_field($view_parameter));
            error_log("License Manager: Updated view_parameter: $view_parameter");
        }
        if ($description !== '') {
            update_term_meta($term_id, 'description', sanitize_textarea_field($description));
            error_log("License Manager: Updated description");
        }
        if ($category !== '') {
            update_term_meta($term_id, 'category', sanitize_text_field($category));
            error_log("License Manager: Updated category: $category");
        }
        
        // Clear comprehensive caches
        wp_cache_flush(); // Clear all WordPress cache
        delete_transient('insurance_crm_module_mappings'); // Clear client-side cache
        clean_term_cache($term_id, 'lm_modules'); // Clear specific term cache
        
        // Ensure taxonomy cache is cleared
        wp_cache_delete('all_ids', 'lm_modules');
        wp_cache_delete('get', 'lm_modules');
        
        error_log("License Manager: Successfully updated module ID: $term_id");
        return $term_id;
    }
    
    /**
     * Delete module
     */
    public function delete_module($term_id) {
        // Ensure the taxonomy is registered first
        if (!taxonomy_exists('lm_modules')) {
            error_log("License Manager: Taxonomy not registered during deletion, registering now");
            $this->register_modules_taxonomy();
            flush_rewrite_rules(false);
        }
        
        // Check if term exists with retry logic (similar to other operations)
        $retry_count = 0;
        $max_retries = 3;
        $term = false;
        
        while ($retry_count < $max_retries && (is_wp_error($term) || !$term)) {
            $term = get_term($term_id, 'lm_modules');
            if (is_wp_error($term) || !$term) {
                if ($retry_count < $max_retries - 1) {
                    error_log("License Manager: Module lookup failed (attempt " . ($retry_count + 1) . "), retrying...");
                    usleep(200000); // 0.2 seconds delay
                }
            }
            $retry_count++;
        }
        
        if (is_wp_error($term) || !$term) {
            error_log("License Manager: Module not found for deletion after retries: $term_id");
            return new WP_Error('module_not_found', __('Module not found', 'license-manager'));
        }
        
        error_log("License Manager: Deleting module: " . $term->name . " (ID: $term_id)");
        
        // Delete the term (this will also delete meta data)
        $result = wp_delete_term($term_id, 'lm_modules');
        
        if (is_wp_error($result)) {
            error_log("License Manager: Failed to delete module: " . $result->get_error_message());
            return $result;
        }
        
        // Clear comprehensive caches
        wp_cache_flush(); // Clear all WordPress cache
        delete_transient('insurance_crm_module_mappings'); // Clear client-side cache
        clean_term_cache($term_id, 'lm_modules'); // Clear specific term cache
        
        // Ensure taxonomy cache is cleared
        wp_cache_delete('all_ids', 'lm_modules');
        wp_cache_delete('get', 'lm_modules');
        
        error_log("License Manager: Successfully deleted module ID: $term_id");
        return $result;
    }
    
    /**
     * Refresh default modules - utility for fixing module issues
     */
    public function refresh_default_modules() {
        error_log('License Manager: Refreshing default modules for troubleshooting');
        
        // Clear all caches first
        wp_cache_flush();
        clean_taxonomy_cache('lm_modules');
        delete_transient('insurance_crm_module_mappings');
        
        // Clear specific module caches
        wp_cache_delete('lm_modules', 'terms');
        wp_cache_delete('license_manager_modules', 'terms');
        wp_cache_delete('all_ids', 'lm_modules');
        wp_cache_delete('get', 'lm_modules');
        
        // Reset the defaults created flag to force recreation if needed
        delete_option('license_manager_defaults_created');
        
        // Force recreation of default modules
        $this->force_create_default_modules();
        
        // Set the flag back
        update_option('license_manager_defaults_created', true);
        
        error_log('License Manager: Default modules refresh completed');
    }
    
    /**
     * Get module by view parameter
     */
    public function get_module_by_view_parameter($view_parameter) {
        $modules = $this->get_available_modules();
        
        error_log("License Manager: Looking for module with view parameter: $view_parameter");
        error_log("License Manager: Total modules available: " . count($modules));
        
        foreach ($modules as $module) {
            error_log("License Manager: Checking module: " . $module->name . " with view_parameter: " . $module->view_parameter);
            if ($module->view_parameter === $view_parameter) {
                error_log("License Manager: Found matching module: " . $module->name);
                return $module;
            }
        }
        
        error_log("License Manager: No module found with view parameter: $view_parameter");
        return null;
    }
    
    /**
     * Create sample license for testing
     */
    private function create_sample_license() {
        // Check if sample license already exists
        $existing_licenses = get_posts(array(
            'post_type' => 'lm_license',
            'meta_query' => array(
                array(
                    'key' => '_license_key',
                    'value' => 'LIC-SAMPLE-TEST-2024',
                    'compare' => '='
                )
            ),
            'posts_per_page' => 1
        ));
        
        if (!empty($existing_licenses)) {
            error_log("BALKAy License: Sample license already exists");
            return;
        }
        
        // Create sample license
        $license_id = wp_insert_post(array(
            'post_type' => 'lm_license',
            'post_title' => 'Test Lisansı - BALKAy CRM',
            'post_status' => 'publish',
            'post_content' => 'Test amaçlı örnek lisans'
        ));
        
        if ($license_id) {
            // Set license metadata
            update_post_meta($license_id, '_license_key', 'LIC-SAMPLE-TEST-2024');
            update_post_meta($license_id, '_expires_on', date('Y-m-d', strtotime('+1 year')));
            update_post_meta($license_id, '_user_limit', 10);
            update_post_meta($license_id, '_allowed_domains', 'localhost,127.0.0.1,*.local,*.test,balkay.net,*.balkay.net');
            update_post_meta($license_id, '_status', 'active');
            
            // Set license type
            wp_set_object_terms($license_id, 'yearly', 'lm_license_type');
            
            // Set modules
            wp_set_object_terms($license_id, array('dashboard', 'customers', 'policies', 'quotes', 'tasks', 'reports', 'data_transfer'), 'lm_modules');
            
            error_log("BALKAy License: Sample license created with ID: " . $license_id);
        } else {
            error_log("BALKAy License: Failed to create sample license");
        }
    }
}