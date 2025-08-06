<?php
/**
 * Emergency Module Migration Script
 * 
 * This script will migrate modules from the old taxonomy system to the new database tables.
 * Run this if you have the new tables created but modules are not showing up.
 * 
 * USAGE: Access this file via browser at: yoursite.com/wp-content/plugins/license-manager/fix_modules.php
 * Or run via WP-CLI: wp eval-file fix_modules.php
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    // Try to load WordPress if running directly
    $wp_load_paths = array(
        '../../../wp-load.php',
        '../../../../wp-load.php',
        '../../../../../wp-load.php'
    );
    
    foreach ($wp_load_paths as $path) {
        if (file_exists(__DIR__ . '/' . $path)) {
            require_once __DIR__ . '/' . $path;
            break;
        }
    }
    
    if (!defined('ABSPATH')) {
        die('WordPress not found. Please place this file in the correct location or run via WP-CLI.');
    }
}

// Security check
if (!current_user_can('manage_options') && !defined('WP_CLI')) {
    die('You do not have permission to run this script.');
}

echo "<h2>License Manager - Emergency Module Migration</h2>\n";

// Load required classes
require_once plugin_dir_path(__FILE__) . 'includes/class-license-manager-database-v2.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-license-manager-migration.php';

try {
    $migration = new License_Manager_Migration();
    
    echo "<h3>Current Status Check</h3>\n";
    $status = $migration->get_migration_status();
    
    echo "<pre>";
    echo "New Structure Available: " . ($status['new_structure_available'] ? 'YES' : 'NO') . "\n";
    echo "Database Version: " . $status['current_db_version'] . " (Target: " . $status['target_db_version'] . ")\n";
    echo "Migration Needed: " . ($status['migration_needed'] ? 'YES' : 'NO') . "\n\n";
    
    echo "Legacy System Counts:\n";
    echo "- Modules: " . $status['legacy_counts']['modules'] . "\n";
    echo "- Customers: " . $status['legacy_counts']['customers'] . "\n";
    echo "- Licenses: " . $status['legacy_counts']['licenses'] . "\n\n";
    
    if ($status['new_structure_available']) {
        echo "New System Counts:\n";
        echo "- Modules: " . $status['new_table_counts']['modules'] . "\n";
        echo "- Customers: " . $status['new_table_counts']['customers'] . "\n";
        echo "- Licenses: " . $status['new_table_counts']['licenses'] . "\n\n";
    }
    echo "</pre>";
    
    // Check if modules need to be migrated
    if ($status['new_structure_available'] && $status['new_table_counts']['modules'] == 0 && $status['legacy_counts']['modules'] > 0) {
        echo "<h3>🔧 Fixing Module Migration Issue</h3>\n";
        echo "<p>Found: New tables exist but no modules are migrated. Migrating modules now...</p>\n";
        
        $result = $migration->migrate_modules_only();
        
        if ($result) {
            echo "<p style='color: green;'><strong>✅ SUCCESS:</strong> Modules have been migrated to the new database structure!</p>\n";
            
            // Check counts again
            $status_after = $migration->get_migration_status();
            echo "<p>Modules migrated: " . $status_after['new_table_counts']['modules'] . "</p>\n";
            
            echo "<h4>Next Steps:</h4>\n";
            echo "<ul>\n";
            echo "<li>Clear any caches on your site</li>\n";
            echo "<li>Check your admin panel to see if modules are now visible</li>\n";
            echo "<li>Test adding new modules</li>\n";
            echo "<li>Check client-side to see if all 8 modules are visible again</li>\n";
            echo "</ul>\n";
        } else {
            echo "<p style='color: red;'><strong>❌ ERROR:</strong> Module migration failed. Check error logs for details.</p>\n";
        }
        
    } elseif (!$status['new_structure_available']) {
        echo "<h3>ℹ️ Information</h3>\n";
        echo "<p>New database structure is not available. Tables may not be created yet.</p>\n";
        echo "<p>The system will continue using the legacy taxonomy system.</p>\n";
        
    } elseif ($status['new_table_counts']['modules'] > 0) {
        echo "<h3>✅ Status: OK</h3>\n";
        echo "<p>Modules are already present in the new database structure.</p>\n";
        echo "<p>If you're still experiencing issues, the problem may be elsewhere in the routing system.</p>\n";
        
    } else {
        echo "<h3>⚠️ No Legacy Modules Found</h3>\n";
        echo "<p>No modules found in the legacy taxonomy system to migrate.</p>\n";
        echo "<p>You may need to create default modules first.</p>\n";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>ERROR:</strong> " . $e->getMessage() . "</p>\n";
} catch (Error $e) {
    echo "<p style='color: red;'><strong>ERROR:</strong> " . $e->getMessage() . "</p>\n";
}

echo "\n<hr>\n";
echo "<p><em>Emergency Module Migration Script - License Management System</em></p>\n";