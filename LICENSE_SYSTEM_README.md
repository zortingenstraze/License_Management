# Insurance CRM License Management System

## Overview

The Insurance CRM now includes a comprehensive monthly user-based licensing system that integrates with an external central license server. This system provides license validation, access control, and user management based on license status.

## Features Implemented

### 1. License Management Interface
- **Location**: Admin Menu → Insurance CRM → Lisans Bilgisi
- **Functionality**: 
  - License key input form
  - License status display
  - License details (type, expiry, user limits, modules)
  - Activation/deactivation controls

### 2. License Validation Mechanism
- **Automatic validation** on user login and periodic checks (daily)
- **Real-time validation** with central license server
- **Fallback handling** for server communication issues
- **Configurable server URL** (default: https://www.balkay.net/crm/lisans)

### 3. Access Control Based on License Status

#### Active License
- Full access to all authorized modules and data
- All features available

#### Expired License (Grace Period - 7 days)
- Warning notifications displayed
- Full system access maintained
- Grace period countdown shown
- Reminders to renew license

#### Expired License (Grace Period Ended)
- User can log in but cannot access any data
- All module pages show restriction messages
- Clear renewal instructions
- Redirect to license management page

### 4. User Limit Enforcement
- **Real-time monitoring** of active user count
- **Warnings** when approaching user limit
- **Prevention** of new user creation when limit exceeded
- **Visual indicators** in admin interface

### 5. Module-Based Authorization
- **Configurable module access** based on license
- **Automatic restriction** of unauthorized modules
- **Menu hiding** for inaccessible modules
- **Default modules**: customers, policies, tasks, reports

## Configuration

### License Server Settings
```php
// Default license server URL
$license_server_url = 'https://www.balkay.net/crm/lisans';

// Enable debug mode for detailed logging
update_option('insurance_crm_license_debug_mode', true);

// Bypass license for development (not recommended for production)
update_option('insurance_crm_bypass_license', true);
```

### Expected API Endpoints

The license server should implement these endpoints:

#### POST /api/validate_license
**Request:**
```json
{
    "license_key": "license-key-here",
    "domain": "example.com",
    "action": "validate"
}
```

**Response:**
```json
{
    "status": "active|expired|invalid",
    "license_type": "monthly|lifetime",
    "expires_on": "YYYY-MM-DD",
    "user_limit": 10,
    "modules": ["customers", "policies", "tasks", "reports"],
    "message": "Status message"
}
```

#### GET /api/license_info
**Parameters:** `license_key`

**Response:** Same as validate_license

#### POST /api/check_status
**Request:** Same as validate_license

**Response:** Same as validate_license

## Usage

### For Administrators

1. **License Activation**:
   - Go to Insurance CRM → Lisans Bilgisi
   - Enter license key
   - Click "Lisansı Etkinleştir"

2. **Monitor License Status**:
   - Check dashboard for license warnings
   - View user count vs. limit
   - Monitor expiry dates

3. **Handle Expiry**:
   - Renew license before expiry
   - Use grace period if needed
   - Update license key when renewed

### For Developers

1. **Check License Status**:
```php
global $insurance_crm_license_manager;
if ($insurance_crm_license_manager->is_license_valid()) {
    // License is active
}
```

2. **Check Data Access**:
```php
if (insurance_crm_can_access_data()) {
    // User can access CRM data
}
```

3. **Check Module Access**:
```php
if (insurance_crm_can_access_module('customers')) {
    // User can access customers module
}
```

## Database Tables

The system uses existing WordPress options table for license data:
- `insurance_crm_license_key`
- `insurance_crm_license_status`
- `insurance_crm_license_type`
- `insurance_crm_license_expiry`
- `insurance_crm_license_user_limit`
- `insurance_crm_license_modules`
- `insurance_crm_license_last_check`

## Security Features

- **Domain validation** prevents license sharing
- **Secure HTTPS communication** with license server
- **Nonce verification** for form submissions
- **Capability checks** for admin functions
- **Input sanitization** for all license data

## Troubleshooting

### License Not Activating
1. Check internet connection
2. Verify license key format
3. Enable debug mode to see detailed logs
4. Check server URL configuration

### User Limit Issues
1. Review active user count in license page
2. Deactivate unused users
3. Upgrade license for more users
4. Contact license provider

### Server Communication Errors
1. Check firewall settings
2. Verify SSL certificate
3. Test with different license server URL
4. Enable debug mode for detailed error logs

## Integration Notes

This implementation integrates with the existing Insurance CRM structure:
- Uses existing admin menu system
- Follows WordPress coding standards
- Maintains compatibility with existing features
- Provides hooks for future extensions

The system is designed to be minimally invasive while providing comprehensive license management functionality as specified in the requirements.