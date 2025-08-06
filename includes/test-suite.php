<?php
/**
 * License Manager Test Script
 * 
 * This script tests the new database structure and module management functionality
 * 
 * Usage: Visit /wp-admin/admin.php?page=license-manager&run_test=1
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * License Manager Test Suite
 */
class License_Manager_Test_Suite {
    
    private $results = array();
    private $database_v2;
    private $modules_manager;
    
    public function __construct() {
        $this->database_v2 = new License_Manager_Database_V2();
        $this->modules_manager = new License_Manager_Modules();
    }
    
    /**
     * Run all tests
     */
    public function run_all_tests() {
        $this->log("=== License Manager Test Suite ===");
        
        // Test 1: Database structure
        $this->test_database_structure();
        
        // Test 2: Module management
        $this->test_module_management();
        
        // Test 3: API endpoints
        $this->test_api_endpoints();
        
        // Test 4: Client-side access control
        $this->test_access_control();
        
        $this->log("=== Test Suite Complete ===");
        
        return $this->results;
    }
    
    /**
     * Test database structure
     */
    private function test_database_structure() {
        $this->log("--- Testing Database Structure ---");
        
        // Test if new structure is available
        $new_structure = $this->database_v2->is_new_structure_available();
        $this->log("New database structure available: " . ($new_structure ? 'YES' : 'NO'));
        
        if ($new_structure) {
            // Test statistics
            $stats = $this->database_v2->get_dashboard_stats();
            $this->log("Dashboard stats retrieved successfully");
            $this->log("- Total customers: " . $stats['total_customers']);
            $this->log("- Total licenses: " . $stats['total_licenses']);
            $this->log("- Total modules: " . $stats['total_modules']);
            
            // Test settings
            $setting_test = $this->database_v2->set_setting('test_setting', 'test_value', 'string', 'Test setting');
            $retrieved_value = $this->database_v2->get_setting('test_setting');
            
            if ($setting_test && $retrieved_value === 'test_value') {
                $this->log("Settings management: PASSED");
            } else {
                $this->log("Settings management: FAILED");
            }
        }
    }
    
    /**
     * Test module management
     */
    private function test_module_management() {
        $this->log("--- Testing Module Management ---");
        
        // Get modules count before
        $modules_before = $this->modules_manager->get_modules();
        $count_before = count($modules_before);
        $this->log("Modules before test: $count_before");
        
        // Test adding a module
        $test_module_name = 'Test Module ' . time();
        $test_module_slug = 'test-module-' . time();
        $test_view_param = 'test_view_' . time();
        
        $add_result = $this->modules_manager->add_module(
            $test_module_name,
            $test_module_slug,
            $test_view_param,
            'Test module for validation',
            'custom'
        );
        
        if (is_wp_error($add_result)) {
            $this->log("Module addition: FAILED - " . $add_result->get_error_message());
        } else {
            $this->log("Module addition: PASSED - ID: $add_result");
            
            // Test retrieving the module
            $retrieved_module = $this->modules_manager->get_module($add_result);
            if ($retrieved_module && $retrieved_module->name === $test_module_name) {
                $this->log("Module retrieval: PASSED");
                
                // Test updating the module
                $update_result = $this->modules_manager->update_module(
                    $add_result,
                    $test_module_name . ' (Updated)',
                    $test_view_param . '_updated',
                    'Updated test module',
                    'custom'
                );
                
                if (is_wp_error($update_result)) {
                    $this->log("Module update: FAILED - " . $update_result->get_error_message());
                } else {
                    $this->log("Module update: PASSED");
                }
                
                // Test deleting the module (cleanup)
                $delete_result = $this->modules_manager->delete_module($add_result);
                if (is_wp_error($delete_result)) {
                    $this->log("Module deletion: FAILED - " . $delete_result->get_error_message());
                } else {
                    $this->log("Module deletion: PASSED");
                }
            } else {
                $this->log("Module retrieval: FAILED");
            }
        }
        
        // Final count check
        $modules_after = $this->modules_manager->get_modules();
        $count_after = count($modules_after);
        $this->log("Modules after test: $count_after");
    }
    
    /**
     * Test API endpoints
     */
    private function test_api_endpoints() {
        $this->log("--- Testing API Endpoints ---");
        
        // Test modules endpoint
        $modules_url = home_url('/wp-json/balkay-license/v1/modules');
        $response = wp_remote_get($modules_url);
        
        if (is_wp_error($response)) {
            $this->log("Modules API: FAILED - " . $response->get_error_message());
        } else {
            $status_code = wp_remote_retrieve_response_code($response);
            if ($status_code === 200) {
                $body = wp_remote_retrieve_body($response);
                $data = json_decode($body, true);
                
                if (isset($data['success']) && $data['success']) {
                    $this->log("Modules API: PASSED - " . count($data['modules']) . " modules returned");
                } else {
                    $this->log("Modules API: FAILED - Invalid response format");
                }
            } else {
                $this->log("Modules API: FAILED - Status code: $status_code");
            }
        }
        
        // Test restricted modules endpoint
        $restricted_url = home_url('/wp-json/balkay-license/v1/get_restricted_modules');
        $restricted_response = wp_remote_post($restricted_url, array(
            'body' => array(
                'license_key' => 'test-key',
                'domain' => parse_url(home_url(), PHP_URL_HOST)
            )
        ));
        
        if (is_wp_error($restricted_response)) {
            $this->log("Restricted Modules API: FAILED - " . $restricted_response->get_error_message());
        } else {
            $status_code = wp_remote_retrieve_response_code($restricted_response);
            $this->log("Restricted Modules API: Response code $status_code (expected 404 for test key)");
        }
    }
    
    /**
     * Test access control
     */
    private function test_access_control() {
        $this->log("--- Testing Access Control ---");
        
        // Test restricted modules retrieval
        if ($this->database_v2->is_new_structure_available()) {
            $restricted_modules = $this->database_v2->get_restricted_modules_on_limit_exceeded();
            $this->log("Restricted modules from new DB: " . implode(', ', $restricted_modules));
        }
        
        // Test module validator
        $validator = new Insurance_CRM_Module_Validator();
        
        // Test core modules (should always be allowed)
        $core_modules = array('license-management', 'customer-representatives', 'all_personnel');
        foreach ($core_modules as $module) {
            $allowed = $validator->is_module_access_allowed($module);
            $this->log("Core module '$module' access: " . ($allowed ? 'ALLOWED' : 'DENIED'));
        }
    }
    
    /**
     * Log test result
     */
    private function log($message) {
        $this->results[] = $message;
        error_log("License Manager Test: $message");
    }
}

// Run tests if requested
if (is_admin() && isset($_GET['run_test']) && $_GET['run_test'] === '1' && current_user_can('manage_options')) {
    $test_suite = new License_Manager_Test_Suite();
    $results = $test_suite->run_all_tests();
    
    echo '<div class="wrap">';
    echo '<h1>License Manager Test Results</h1>';
    echo '<div style="background: #f1f1f1; padding: 20px; margin: 20px 0; font-family: monospace; white-space: pre-line;">';
    foreach ($results as $result) {
        echo esc_html($result) . "\n";
    }
    echo '</div>';
    echo '<p><a href="' . admin_url('admin.php?page=license-manager') . '" class="button">Back to License Manager</a></p>';
    echo '</div>';
    
    return; // Don't show normal page
}