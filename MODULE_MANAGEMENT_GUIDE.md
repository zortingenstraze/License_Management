# Module Management System Documentation

## Overview

The License Management system has been enhanced with a comprehensive module management feature that supports:

- Dynamic module creation and management
- View parameter support (`?view=sales-opportunities`)
- Client-side module access validation
- Admin interface for module management
- API endpoints for module data

## Key Features

### 1. View Parameter Support

Modules can now be accessed using URL view parameters:
```
https://yoursite.com/admin.php?page=crm&view=sales-opportunities
https://yoursite.com/crm/?view=customer-management
```

### 2. Module Categories

Modules are organized into categories:
- **Core**: Essential system modules
- **Management**: Customer and data management
- **Sales**: Sales and marketing tools
- **Analytics**: Reports and analytics
- **Productivity**: Task and workflow management
- **Tools**: Utilities and data tools
- **Custom**: User-defined modules

### 3. Enhanced Client-Side Validation

The system provides robust client-side validation with:
- Real-time module access checking
- View parameter mapping
- Unauthorized access logging
- Grace period handling

## Usage

### Admin Interface

#### Adding a New Module

1. Navigate to **License Manager > Modules**
2. Click **"Yeni Modül Ekle"** (Add New Module)
3. Fill in the module details:
   - **Module Name**: Display name (e.g., "Sales Opportunities")
   - **Slug**: Unique identifier (e.g., "sales-opportunities")
   - **View Parameter**: URL parameter (e.g., "sales-opportunities")
   - **Category**: Module category
   - **Description**: Module description

#### Editing Modules

1. Go to **License Manager > Modules**
2. Click **"Düzenle"** (Edit) next to the module
3. Update the module information
4. Save changes

#### Deleting Modules

1. Go to **License Manager > Modules**
2. Click **"Sil"** (Delete) next to the module
3. Confirm deletion

### Client-Side Usage

#### Checking Module Access

```php
// Get license manager instance
global $insurance_crm_license_manager;

// Check if a module is allowed
if ($insurance_crm_license_manager->is_module_allowed('sales-opportunities')) {
    // Module is allowed, show content
    echo "Access granted to Sales Opportunities module";
} else {
    // Module not allowed, show error or redirect
    echo "Access denied";
}

// Check view parameter access
if ($insurance_crm_license_manager->is_module_allowed($_GET['view'])) {
    // View parameter is allowed
    include_template($_GET['view']);
}
```

#### Using Module Validator

```php
// Create module validator instance
$validator = new Insurance_CRM_Module_Validator();

// Get detailed access information
$access_check = $validator->check_module_access('sales-opportunities');

if ($access_check['allowed']) {
    // Access granted
    echo "Welcome to " . $access_check['module'];
} else {
    // Access denied
    echo "Access denied: " . $access_check['reason'];
    echo "Suggestions: " . implode(', ', $access_check['suggestions']);
}
```

## API Endpoints

### Get All Modules

**Endpoint**: `GET /wp-json/balkay-license/v1/modules`

**Response**:
```json
{
  "modules": [
    {
      "id": 1,
      "name": "Sales Opportunities",
      "slug": "sales-opportunities",
      "view_parameter": "sales-opportunities",
      "description": "Satış fırsatları ve pipeline yönetimi",
      "category": "sales"
    }
  ]
}
```

### Validate Module Access

**Endpoint**: `POST /wp-json/balkay-license/v1/validate_module`

**Parameters**:
- `license_key` (required): License key
- `module_or_view` (required): Module slug or view parameter
- `domain` (optional): Domain to validate

**Response**:
```json
{
  "access_allowed": true,
  "module_or_view": "sales-opportunities",
  "license_status": "active",
  "allowed_modules": ["dashboard", "customers", "sales-opportunities"],
  "message": "Erişim izni verildi"
}
```

### Get Module by View Parameter

**Endpoint**: `GET /wp-json/balkay-license/v1/module_by_view/{view_parameter}`

**Response**:
```json
{
  "found": true,
  "module": {
    "id": 1,
    "name": "Sales Opportunities",
    "slug": "sales-opportunities",
    "view_parameter": "sales-opportunities",
    "description": "Satış fırsatları ve pipeline yönetimi",
    "category": "sales"
  }
}
```

## Database Schema

### Module Storage

Modules are stored as WordPress taxonomy terms in `lm_modules` taxonomy with the following meta fields:

- `view_parameter`: URL view parameter
- `description`: Module description
- `category`: Module category

### License Module Association

License-module relationships are stored in:
- WordPress taxonomy relationships (`wp_term_relationships`)
- Post meta as backup (`_modules` meta key)

## Security Features

### Access Control

1. **Permission Checks**: All module operations require `manage_license_manager` capability
2. **Nonce Verification**: All forms use WordPress nonces for CSRF protection
3. **Input Sanitization**: All user inputs are sanitized
4. **Access Logging**: Unauthorized access attempts are logged

### Client-Side Protection

1. **Real-time Validation**: Module access is checked on every page load
2. **Grace Period**: License expiration includes a grace period
3. **Automatic Deactivation**: Invalid licenses are automatically deactivated

## Best Practices

### Module Naming

- Use descriptive names for modules
- Keep slugs short and URL-friendly
- Use consistent naming conventions
- Avoid special characters in view parameters

### Performance

- Module data is cached for optimal performance
- API calls are minimized with intelligent caching
- Database queries are optimized

### Security

- Always validate module access before showing content
- Log unauthorized access attempts
- Use WordPress security best practices
- Sanitize all user inputs

## Troubleshooting

### Common Issues

1. **Module Not Appearing**: Check if module is created and assigned to license
2. **Access Denied**: Verify license status and module inclusion
3. **View Parameter Not Working**: Check view parameter format (alphanumeric + hyphens only)
4. **API Not Responding**: Verify rewrite rules are flushed

### Debug Mode

Enable debug mode in WordPress to see detailed logs:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

### Clearing Cache

Clear module cache if needed:
```php
delete_transient('insurance_crm_module_mappings');
```

## Migration

### From Previous Versions

The system is backward compatible. Existing modules will be automatically updated with meta fields when accessed.

### Updating Modules

When updating modules, the system maintains data integrity by:
- Preserving existing module assignments
- Updating meta data without breaking relationships
- Providing fallback mechanisms for missing data

## Support

For technical support or questions about the module management system:

1. Check the debug logs for error messages
2. Verify license status and module assignments
3. Test API endpoints manually
4. Contact support with specific error details

## Changelog

### Version 1.2.0
- Added view parameter support
- Created module management admin interface
- Enhanced client-side validation
- Added API endpoints for modules
- Improved security and logging
- Added comprehensive documentation