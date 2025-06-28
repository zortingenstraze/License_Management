# BALKAy License Manager

A comprehensive WordPress plugin for managing licenses for the Insurance CRM system. This plugin provides centralized license validation, customer management, and license distribution capabilities.

## Features

### Core Functionality
- **Customer Management**: Complete customer database with contact information and domain management
- **License Management**: Full license lifecycle management with expiry tracking
- **License Packages**: Predefined license types with different features and durations
- **API Endpoints**: RESTful API for license validation and status checking
- **Admin Interface**: Comprehensive WordPress admin interface
- **Dashboard Widgets**: Real-time statistics and monitoring

### API Endpoints
The plugin provides the following REST API endpoints compatible with the Insurance CRM license system:

- `POST /wp-json/balkay-license/v1/validate_license` - Validate license key with domain
- `POST /wp-json/balkay-license/v1/validate` - Shorter validate endpoint
- `GET /wp-json/balkay-license/v1/license_info` - Get license information by key
- `POST /wp-json/balkay-license/v1/check_status` - Check license status
- `POST /api/validate_license` - Alternative validation endpoint

### Database Structure
The plugin uses WordPress custom post types and taxonomies:

#### Custom Post Types
- `lm_customer` - Customer records
- `lm_license` - License records  
- `lm_license_package` - License package templates

#### Taxonomies
- `lm_license_status` - License status (active, expired, invalid, suspended)
- `lm_license_type` - License types (monthly, yearly, lifetime, trial)
- `lm_modules` - Available modules (customers, policies, tasks, reports)

## Installation

1. Upload the plugin files to `/wp-content/plugins/license-manager/`
2. Activate the plugin through the WordPress admin interface
3. Go to **License Manager > Settings** to configure the plugin
4. Start adding customers and licenses through the admin interface

## Configuration

### Basic Settings
Navigate to **License Manager > Settings** to configure:

- **Debug Mode**: Enable detailed logging for troubleshooting
- **Default License Duration**: Default duration for new licenses (days)
- **Grace Period**: Grace period after license expiry (days)
- **Default User Limit**: Default user limit for new licenses

### API Usage
The API endpoints follow the specifications outlined in `LICENSE_SYSTEM_README.md`:

```json
{
    "status": "active|expired|invalid",
    "license_type": "monthly|yearly|lifetime",
    "expires_on": "YYYY-MM-DD",
    "user_limit": 10,
    "modules": ["customers", "policies", "tasks", "reports"],
    "message": "Status message"
}
```

## Usage

### Managing Customers
1. Go to **License Manager > Customers**
2. Click **Add New Customer**
3. Fill in customer details including allowed domains
4. Save the customer record

### Managing Licenses
1. Go to **License Manager > Licenses**
2. Click **Add New License**
3. Set license details and assign to a customer
4. Configure expiry date, user limits, and modules
5. Save the license

### License Packages
1. Go to **License Manager > License Packages**
2. Create predefined license templates
3. Set default duration, user limits, and included modules
4. Use packages when creating new licenses

### API Integration
The Insurance CRM plugin should be configured to use these endpoints:
- Server URL: `https://yourdomain.com/wp-json/license-manager/v1/`
- Endpoints: `validate_license`, `license_info`, `check_status`

## Security Features

- **Domain Validation**: Prevents license sharing across unauthorized domains
- **Capability Checks**: Proper WordPress capability management
- **Nonce Verification**: CSRF protection for all admin actions
- **Input Sanitization**: All inputs are properly sanitized
- **Output Escaping**: All outputs are properly escaped

## Developer Hooks

The plugin provides several hooks for customization:

### Actions
- `license_manager_license_activated` - Fired when a license is activated
- `license_manager_license_expired` - Fired when a license expires
- `license_manager_customer_created` - Fired when a customer is created

### Filters
- `license_manager_validate_license` - Filter license validation logic
- `license_manager_api_response` - Filter API response data
- `license_manager_default_modules` - Filter default modules list

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- MySQL 5.6 or higher

## Support

For support and documentation, visit:
- Plugin Repository: https://github.com/anadolubirlik/BALKAynetCRM_Lisans_Management
- Website: https://balkay.net/crm

## License

This plugin is licensed under the GPL v2 or later.

## Changelog

### Version 1.0.0
- Initial release
- Customer management system
- License management system
- API endpoints for license validation
- Admin dashboard and interfaces
- Security features and validation