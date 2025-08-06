# License Management System - Setup and Activation Guide

## Prerequisites

- WordPress 5.0 or higher
- PHP 7.4 or higher  
- MySQL 5.7 or higher
- Administrator access to WordPress

## Installation Steps

### 1. Backup Your System

```bash
# Backup your database
mysqldump -u username -p database_name > backup_$(date +%Y%m%d).sql

# Backup your WordPress files
tar -czf wordpress_backup_$(date +%Y%m%d).tar.gz /path/to/wordpress/
```

### 2. Upload Plugin Files

1. Upload all plugin files to `/wp-content/plugins/license-manager/`
2. Ensure proper file permissions (644 for files, 755 for directories)

### 3. Activate the Plugin

1. Go to WordPress Admin → Plugins
2. Find "BALKAy Lisans Yöneticisi" 
3. Click "Activate"

**Expected Results:**
- 6 new database tables will be created automatically
- Default modules will be populated
- Settings will be initialized
- Migration system will be ready

### 4. Verify Installation

#### Check Database Tables

Verify these tables exist in your database:
- `wp_icrm_license_management_customers`
- `wp_icrm_license_management_licenses`
- `wp_icrm_license_management_license_packages`
- `wp_icrm_license_management_payments`
- `wp_icrm_license_management_modules`
- `wp_icrm_license_management_settings`

#### Check Default Modules

Go to WordPress Admin and check that these modules are available:
- Dashboard
- Lisans Yönetimi
- Müşteri Temsilcileri
- Müşteriler
- Poliçeler
- Teklifler
- Satış Fırsatları
- Görevler
- Raporlar
- Veri Aktarımı

### 5. Configure Settings

#### Enable Debug Mode (Optional)

For troubleshooting, you can enable debug mode:

```sql
INSERT INTO wp_icrm_license_management_settings 
(setting_key, setting_value, setting_type, description) 
VALUES 
('debug_mode', 'true', 'bool', 'Enable debug logging');
```

#### Set User Limit Restrictions

Configure which modules are available when user limits are exceeded:

```sql
UPDATE wp_icrm_license_management_settings 
SET setting_value = '["license-management", "customer-representatives"]'
WHERE setting_key = 'restricted_modules_on_limit_exceeded';
```

## Migration from Old System

If you have existing data in the old WordPress post type system:

### 1. Automatic Migration

The migration will run automatically when:
- Plugin is activated for the first time
- Database version is less than 2.0.0

### 2. Manual Migration Trigger

If needed, you can trigger migration manually:

```php
// Add this to your functions.php temporarily
add_action('admin_init', function() {
    if (current_user_can('manage_options')) {
        $migration = new License_Manager_Migration();
        $migration->run_migration();
    }
});
```

### 3. Verify Migration

Check that data has been migrated:

```sql
-- Check migrated customers
SELECT COUNT(*) FROM wp_icrm_license_management_customers;

-- Check migrated licenses  
SELECT COUNT(*) FROM wp_icrm_license_management_licenses;

-- Check migrated modules
SELECT COUNT(*) FROM wp_icrm_license_management_modules;
```

## Testing the System

### 1. Basic Functionality Test

1. **Module Access**: Test that module access validation works
2. **User Limits**: Test user limit enforcement
3. **License Validation**: Test license key validation
4. **API Endpoints**: Test REST API endpoints

### 2. Debug Information

Enable debug mode and check logs:

```php
// Generate debug report
$license_manager = new Insurance_CRM_License_Manager('1.0.0');
$debug_report = $license_manager->generate_debug_report();
echo '<pre>' . $debug_report . '</pre>';
```

### 3. Test API Endpoints

Test the enhanced API endpoints:

```bash
# Test modules endpoint
curl -X GET "https://yoursite.com/wp-json/balkay-license/v1/modules"

# Test module validation
curl -X POST "https://yoursite.com/wp-json/balkay-license/v1/validate_module" \
  -H "Content-Type: application/json" \
  -d '{"license_key":"YOUR_KEY","module_or_view":"dashboard"}'
```

## Troubleshooting

### Common Issues

#### 1. Tables Not Created

**Symptoms**: Plugin activates but tables don't exist

**Solutions**:
- Check database permissions
- Verify MySQL version compatibility
- Check error logs for SQL errors

#### 2. Migration Fails

**Symptoms**: Old data not migrated to new tables

**Solutions**:
- Check database user permissions
- Verify foreign key constraints
- Use rollback if needed:

```php
$migration = new License_Manager_Migration();
$migration->rollback_migration();
```

#### 3. Module Access Issues

**Symptoms**: Users can't access modules they should

**Solutions**:
- Clear all caches
- Rebuild module system:

```php
$modules = new License_Manager_Modules();
$modules->rebuild_module_system();
```

#### 4. Debug Information

**Enable WordPress Debug Logging**:

```php
// Add to wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

**Check Debug Logs**:
- `/wp-content/debug.log`
- Plugin-specific debug table in database

### Performance Optimization

#### 1. Database Optimization

```sql
-- Optimize new tables
OPTIMIZE TABLE wp_icrm_license_management_customers;
OPTIMIZE TABLE wp_icrm_license_management_licenses;
OPTIMIZE TABLE wp_icrm_license_management_modules;
-- etc.
```

#### 2. Cache Configuration

- Enable object caching if available
- Configure proper cache expiration times
- Use transients for expensive operations

### Security Checklist

- [ ] All inputs are sanitized
- [ ] Database queries use prepared statements
- [ ] User capabilities are properly checked
- [ ] Nonces are verified for form submissions
- [ ] File permissions are correct
- [ ] No sensitive data in logs

## Maintenance

### Regular Tasks

1. **Weekly**:
   - Check debug logs for errors
   - Monitor database performance
   - Verify license validations

2. **Monthly**:
   - Database optimization
   - Clear old debug logs
   - Review system performance

3. **Quarterly**:
   - Full system backup
   - Security audit
   - Performance analysis

### Monitoring

Set up monitoring for:
- Database table sizes
- API response times
- License validation failures
- User limit violations
- System errors

## Support

For technical support or issues:

1. Check the debug logs first
2. Generate a debug report
3. Review the implementation summary
4. Check WordPress and PHP error logs

Remember to disable debug mode in production environments for optimal performance.

## Version Information

- Plugin Version: 1.0.0
- Database Version: 2.0.0
- WordPress Compatibility: 5.0+
- PHP Compatibility: 7.4+