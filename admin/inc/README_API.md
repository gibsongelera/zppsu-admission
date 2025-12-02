# ZPPSU Admission System - API Configuration

## Overview
This directory contains centralized API configuration and services to avoid code duplication across the system.

## Files

### `api_config.php`
Centralized configuration for all external APIs and services.

**Features:**
- SMS API configuration (Textbee)
- Phone number validation and formatting
- API endpoint management
- Configuration constants

**Usage:**
```php
require_once 'inc/api_config.php';

// Get SMS configuration
$config = ApiConfig::getSmsConfig();

// Validate phone number
$validPhone = ApiConfig::validatePhoneNumber('09123456789');

// Format phone for display
$displayPhone = ApiConfig::formatPhoneForDisplay('+639123456789');
```

### `sms_service.php`
Centralized SMS service for sending various types of messages.

**Features:**
- OTP sending
- Approval notifications
- Rejection notifications
- Custom SMS messages
- Error handling and retry logic

**Usage:**
```php
require_once 'inc/sms_service.php';

$smsService = new SmsService();

// Send OTP
$result = $smsService->sendOtp('+639123456789', '123456');

// Send approval notification
$result = $smsService->sendApprovalNotification($phone, $name, $ref, $campus, $date);

// Send rejection notification
$result = $smsService->sendRejectionNotification($phone, $name, $ref, $campus);
```

## Updated Files

### `send_otp.php`
- Now uses centralized SMS service
- Improved error handling
- Better phone number validation

### `verify_otp.php`
- Returns reference number in response
- Improved success flow
- Better JSON response structure

### `register.php`
- Added success modal with reference number
- Copy to clipboard functionality
- Improved user experience
- Direct redirect to login page

### `update_status.php`
- Uses centralized SMS service
- Better notification messages
- Improved error handling

### `test_sms.php`
- Simplified using SMS service
- Better test functionality
- Configuration display

## Benefits

1. **No Code Duplication**: All API configurations in one place
2. **Easy Maintenance**: Change API settings in one file
3. **Consistent Error Handling**: Standardized across all services
4. **Better Testing**: Centralized test functionality
5. **Improved UX**: Better success flow with reference number modal

## Configuration

To update API settings, modify the constants in `api_config.php`:

```php
class ApiConfig {
    const SMS_DEVICE_ID = 'your_device_id';
    const SMS_API_KEY = 'your_api_key';
    const SMS_SENDER_NAME = 'YOUR_SENDER_NAME';
    // ... other settings
}
```

## Testing

Use `test_sms.php` to test SMS functionality:
- Access: `/admin/test_sms.php`
- Updates test phone number in the file
- Shows API configuration and test results