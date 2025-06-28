# Installation and Usage Guide

## Quick Start Guide

### Installation Steps

1. **Upload Plugin Files**
   - Upload all plugin files to `/wp-content/plugins/license-manager/`
   - Ensure all file permissions are correctly set (644 for files, 755 for directories)

2. **Activate Plugin**
   - Go to WordPress Admin → Plugins
   - Find "BALKAy License Manager" in the list
   - Click "Activate"

3. **Configure Settings**
   - Go to **License Manager → Settings**
   - Configure default license duration, user limits, and other options
   - Save settings

4. **Initial Setup**
   - The plugin automatically creates necessary database tables and default data
   - Default license statuses, types, and modules are created automatically

### Basic Usage

#### Step 1: Add Customers
1. Go to **License Manager → Customers**
2. Click **Add New Customer**
3. Fill in customer information:
   - Customer name
   - Company name
   - Contact details
   - Allowed domains (one per line)
4. Save customer

#### Step 2: Create License Packages (Optional)
1. Go to **License Manager → License Packages**
2. Click **Add New Package**
3. Define package details:
   - Package name and description
   - Default duration and user limits
   - Included modules
4. Save package

#### Step 3: Create Licenses
1. Go to **License Manager → Licenses**
2. Click **Add New License**
3. Configure license:
   - License key (auto-generated)
   - Assign to customer
   - Set expiry date
   - Set user limit
   - Select license type and status
   - Choose allowed modules
   - Add allowed domains
4. Save license

#### Step 4: Test API Integration
1. Use the API endpoints to validate licenses:
   ```
   POST /wp-json/balkay-license/v1/validate_license
   POST /wp-json/balkay-license/v1/validate
   GET /wp-json/balkay-license/v1/license_info?license_key=YOUR_KEY
   POST /wp-json/balkay-license/v1/check_status
   POST /api/validate_license
   ```

### API Integration for Insurance CRM

Configure the Insurance CRM plugin to use these settings:

```php
// In Insurance CRM configuration
$license_server_url = 'https://yourdomain.com/wp-json/balkay-license/v1';

// API Endpoints:
// - validate_license (POST)
// - validate (POST) - shorter alias  
// - license_info (GET)  
// - check_status (POST)

// Alternative API endpoint:
$alternative_api_url = 'https://yourdomain.com/api/validate_license';
```

### Admin Interface Overview

#### Dashboard
- **Statistics Overview**: Total licenses, active licenses, expired licenses, total customers
- **Recent Licenses**: Latest created licenses
- **Expiring Soon**: Licenses expiring in the next 30 days

#### Customer Management
- **List View**: See all customers with contact info and license count
- **Edit Customer**: Update customer details and view assigned licenses
- **Domain Management**: Configure allowed domains per customer

#### License Management
- **List View**: See all licenses with status, expiry, and customer info
- **Edit License**: Modify license details, extend expiry, regenerate keys
- **Bulk Actions**: Extend multiple licenses, change status in bulk

#### License Packages
- **Template System**: Create reusable license configurations
- **Default Settings**: Set standard duration, limits, and modules per package

### Common Tasks

#### Extending a License
1. Go to license edit page
2. Click "Extend License" in the License Actions box
3. Enter number of days to extend
4. License expiry date will be automatically updated

#### Regenerating License Key
1. Go to license edit page
2. Click "Regenerate" next to the license key field
3. Confirm regeneration (this will invalidate the old key)
4. New key is generated and saved

#### Bulk License Management
1. Go to **License Manager → Licenses**
2. Select multiple licenses using checkboxes
3. Choose bulk action from dropdown
4. Click "Apply" to perform action on selected licenses

### Troubleshooting

#### Common Issues

**License Not Validating**
- Check if license status is set to "Active"
- Verify expiry date is in the future
- Ensure domain is in allowed domains list
- Check if customer is assigned to license

**API Endpoints Not Working**
- Verify WordPress REST API is enabled
- Check permalink structure is set (not default)
- Ensure no caching plugins are interfering
- Test with WordPress REST API authentication if needed

**Permission Issues**
- Verify user has "manage_license_manager" capability
- Check if administrator role has proper permissions
- Ensure WordPress user permissions are correctly configured

#### Debug Mode

Enable debug mode in **License Manager → Settings** to:
- See detailed API request/response logs
- Track license validation attempts
- Monitor system performance
- Troubleshoot integration issues

### Security Considerations

#### Domain Validation
- Always set allowed domains for licenses
- Use specific domains rather than wildcards
- Regularly audit domain assignments

#### Access Control
- Only administrators should access License Manager
- Use strong passwords for admin accounts
- Regularly review user permissions

#### API Security
- Consider implementing API key authentication for production
- Use HTTPS for all API communications
- Monitor API usage for unusual patterns

### Maintenance

#### Regular Tasks
- **Weekly**: Review expiring licenses and notify customers
- **Monthly**: Clean up expired licenses and update customer records
- **Quarterly**: Audit license usage and customer domain assignments

#### Backup Considerations
- Include custom post types in WordPress backups
- Back up license manager options and settings
- Document API configurations for disaster recovery

### Integration Notes

This plugin is designed to work seamlessly with the Insurance CRM system. The API endpoints exactly match the specifications in `LICENSE_SYSTEM_README.md`, ensuring full compatibility.

Key integration points:
- REST API endpoints for real-time license validation
- WordPress admin interface for license management
- Automatic license status checking and expiry handling
- Domain-based license restrictions
- Module-based feature access control

For additional support or customization needs, refer to the developer documentation in `README.md`.