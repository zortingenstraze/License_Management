<?php
/**
 * Plugin Name: BALKAy Lisans Yöneticisi
 * Plugin URI: https://balkay.net/crm
 * Description: Sigorta CRM sistemi için lisans yönetimi WordPress eklentisi. Merkezi lisans doğrulama, müşteri yönetimi ve lisans dağıtımı sağlar.
 * Version: 1.0.0
 * Author: BALKAy
 * Author URI: https://balkay.net
 * Text Domain: license-manager
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * Network: false
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('LICENSE_MANAGER_VERSION', '1.0.0');
define('BALKAY_LICENSE_VERSION', '1.0.1'); // For rewrite rule flushing
define('LICENSE_MANAGER_PLUGIN_FILE', __FILE__);
define('LICENSE_MANAGER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('LICENSE_MANAGER_PLUGIN_URL', plugin_dir_url(__FILE__));
define('LICENSE_MANAGER_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main License Manager Plugin Class
 */
class License_Manager_Plugin {
    
    /**
     * Instance of this class
     *
     * @var License_Manager_Plugin
     */
    private static $instance;
    
    /**
     * Get the singleton instance
     *
     * @return License_Manager_Plugin
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->init();
    }
    
    /**
     * Initialize the plugin
     */
    private function init() {
        // Load required files
        $this->load_dependencies();
        
        // Initialize hooks
        $this->init_hooks();
    }
    
    /**
     * Load plugin dependencies
     */
    private function load_dependencies() {
        require_once LICENSE_MANAGER_PLUGIN_DIR . 'includes/class-license-manager-database.php';
        require_once LICENSE_MANAGER_PLUGIN_DIR . 'includes/class-license-manager-modules.php';
        require_once LICENSE_MANAGER_PLUGIN_DIR . 'includes/class-license-manager-admin.php';
        require_once LICENSE_MANAGER_PLUGIN_DIR . 'includes/class-license-manager-api.php';
        require_once LICENSE_MANAGER_PLUGIN_DIR . 'includes/class-license-manager-customer.php';
        require_once LICENSE_MANAGER_PLUGIN_DIR . 'includes/class-license-manager-license.php';
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Activation and deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // Initialize plugin after WordPress is loaded
        add_action('init', array($this, 'init_plugin'));
        
        // Load text domain for internationalization
        add_action('plugins_loaded', array($this, 'load_textdomain'));
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Create database tables and default data
        $database = new License_Manager_Database();
        $database->create_tables();
        $database->setup_default_data();
        
        // Force create modules to ensure they exist
        $created_modules = $database->force_create_default_modules();
        error_log("BALKAy License: Plugin activation - Created $created_modules modules");
        
        // Set default options
        $this->set_default_options();
        
        // Initialize API to add rewrite rules
        new License_Manager_API();
        
        // Force multiple rewrite rule flushes for reliability
        flush_rewrite_rules(false); // Soft flush first
        flush_rewrite_rules(true);  // Hard flush second
        
        // Clear any REST API caches
        global $wp_rest_server;
        $wp_rest_server = null;
        
        // Clear any WordPress object cache that might affect routing
        if (function_exists('wp_cache_flush')) {
            wp_cache_flush();
        }
        
        // Set rewrite rules flags to force refresh on next loads
        delete_option('license_manager_rewrite_rules_flushed');
        delete_option('balkay_license_rewrite_flushed');
        delete_option('rewrite_rules'); // Force WordPress to rebuild rewrite rules
        
        // Schedule delayed flush for additional reliability
        wp_schedule_single_event(time() + 5, 'balkay_delayed_flush_rewrite_rules');
        add_action('balkay_delayed_flush_rewrite_rules', 'flush_rewrite_rules');
        
        // Force another flush on shutdown
        add_action('shutdown', function() {
            flush_rewrite_rules();
        });
        
        error_log("BALKAy License: Plugin activated with enhanced rewrite rule flushing and module creation");
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Initialize plugin components
     */
    public function init_plugin() {
        // Initialize database
        $database = new License_Manager_Database();
        
        // Initialize modules manager
        new License_Manager_Modules();
        
        // Initialize admin interface
        if (is_admin()) {
            new License_Manager_Admin();
            
            // Ensure payment status terms exist on admin load
            add_action('admin_init', function() use ($database) {
                $database->force_create_default_payment_status();
            });
        }
        
        // Initialize API endpoints (with enhanced reliability)
        $api = new License_Manager_API();
        
        // Force aggressive rewrite rule management
        $this->manage_rewrite_rules();
        
        // Initialize custom post types
        new License_Manager_Customer();
        new License_Manager_License();
        
        // Add hook to verify routes are working after init
        add_action('wp_loaded', array($this, 'verify_and_fix_routes'), 99);
    }
    
    /**
     * Manage rewrite rules more aggressively
     */
    private function manage_rewrite_rules() {
        $needs_flush = false;
        
        // Check if we need to flush rewrite rules
        if (get_option('license_manager_rewrite_rules_flushed') !== '1') {
            $needs_flush = true;
        }
        
        if (get_option('balkay_license_rewrite_flushed') !== BALKAY_LICENSE_VERSION) {
            $needs_flush = true;
        }
        
        // Also check if our custom rewrite rules exist
        $rewrite_rules = get_option('rewrite_rules', array());
        if (!isset($rewrite_rules['^api/validate_license/?$'])) {
            $needs_flush = true;
        }
        
        if ($needs_flush) {
            error_log("BALKAy License: Flushing rewrite rules due to missing rules");
            flush_rewrite_rules();
            update_option('license_manager_rewrite_rules_flushed', '1');
            update_option('balkay_license_rewrite_flushed', BALKAY_LICENSE_VERSION);
        }
    }
    
    /**
     * Verify routes are working and attempt to fix if not
     */
    public function verify_and_fix_routes() {
        // Only run this verification occasionally to avoid performance issues
        $last_check = get_option('balkay_license_last_route_check', 0);
        if (time() - $last_check < 300) { // 5 minutes
            return;
        }
        
        update_option('balkay_license_last_route_check', time());
        
        // Check if REST routes are accessible
        if (class_exists('WP_REST_Server')) {
            $rest_server = rest_get_server();
            $routes = $rest_server->get_routes();
            
            $critical_routes = array(
                '/' . 'balkay-license/v1' . '/validate',
                '/' . 'balkay-license/v1' . '/validate_license'
            );
            
            $missing_routes = array();
            foreach ($critical_routes as $route) {
                if (!isset($routes[$route])) {
                    $missing_routes[] = $route;
                }
            }
            
            if (!empty($missing_routes)) {
                error_log("BALKAy License: Missing critical routes: " . implode(', ', $missing_routes));
                error_log("BALKAy License: Attempting to re-register routes");
                
                // Force route re-registration
                global $wp_rest_server;
                $wp_rest_server = null;
                
                // Re-initialize API
                new License_Manager_API();
                
                // Flush rewrite rules again
                flush_rewrite_rules();
            }
        }
    }
    
    /**
     * Load plugin text domain
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'license-manager',
            false,
            dirname(plugin_basename(__FILE__)) . '/languages/'
        );
    }
    
    /**
     * Set default plugin options
     */
    private function set_default_options() {
        $defaults = array(
            'license_manager_api_version' => 'v1',
            'license_manager_debug_mode' => false,
            'license_manager_default_license_duration' => 30, // days
            'license_manager_grace_period' => 7, // days
            'license_manager_default_user_limit' => 5,
            'license_manager_default_modules' => array('customers', 'policies', 'tasks', 'reports'),
        );
        
        foreach ($defaults as $key => $value) {
            if (get_option($key) === false) {
                add_option($key, $value);
            }
        }
    }
}

// Initialize the plugin
License_Manager_Plugin::get_instance();