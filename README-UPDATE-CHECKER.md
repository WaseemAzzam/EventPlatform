# Event Platform - Update Checker

## Overview

The Event Platform plugin now includes a built-in update checker that allows administrators to check for new releases of the plugin. This feature provides a "Generate Button" that performs version checking and update notifications.

## Features

### Generate Button
- **Location**: Available in the WordPress admin under Events → Plugin Updates
- **Functionality**: Checks for new releases of the Event Platform plugin
- **Real-time**: Uses AJAX for seamless user experience
- **Security**: Includes nonce verification and capability checks

### Key Features

1. **Version Checking**: Compares current version with latest available version
2. **Update Notifications**: Shows detailed information about new releases
3. **Admin Interface**: Clean, modern interface with responsive design
4. **Security**: Proper WordPress security measures implemented
5. **Logging**: Tracks last check time and update history

## How to Use

### Accessing the Update Checker

1. **Via Admin Menu**:
   - Go to WordPress Admin → Events → Plugin Updates
   - Click the "Check for New Releases" button

2. **Via Admin Notice**:
   - When viewing Events in the admin, look for the "Event Platform Updates" notice
   - Click the "Check for Plugin Updates" button

### Using the Generate Button

1. **Click the Button**: The "Check for New Releases" button will initiate the update check
2. **Wait for Results**: The system will show a loading spinner while checking
3. **Review Results**: 
   - If updates are available, you'll see version information and download links
   - If no updates are available, you'll see a confirmation message

## Technical Details

### Files Added
- `update-checker.php` - Main update checker functionality
- `assets/update-checker.css` - Styling for the update interface
- `README-UPDATE-CHECKER.md` - This documentation

### API Integration
The update checker is configured to check GitHub releases by default:
- **API URL**: `https://api.github.com/repos/waseem-azzam/event-platform/releases/latest`
- **Fallback**: Includes demo functionality for testing purposes

### Security Features
- Nonce verification for all AJAX requests
- Capability checks (`manage_options`)
- Proper sanitization and escaping
- WordPress coding standards compliance

## Configuration

### Customizing the Update URL
To change the update source, modify the `$update_url` property in the `EventPlatformUpdateChecker` class:

```php
private $update_url = 'https://your-api-endpoint.com/latest';
```

### Version Management
The current version is stored in the `$current_version` property:

```php
private $current_version = '1.0';
```

## Troubleshooting

### Common Issues

1. **Button Not Working**:
   - Check browser console for JavaScript errors
   - Verify user has `manage_options` capability
   - Ensure AJAX is working properly

2. **No Updates Found**:
   - Check if the API endpoint is accessible
   - Verify the version comparison logic
   - Check network connectivity

3. **Styling Issues**:
   - Ensure the CSS file is loading properly
   - Check for conflicts with other plugins
   - Verify WordPress admin theme compatibility

### Debug Mode
To enable debug information, add this to your `wp-config.php`:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

## Future Enhancements

### Planned Features
- Automatic update notifications
- Scheduled update checks
- Update history tracking
- Multiple update sources support
- Email notifications for updates

### Customization Options
- Configurable check intervals
- Custom update sources
- Advanced filtering options
- Integration with external update services

## Support

For issues or questions regarding the update checker:
1. Check the WordPress error logs
2. Verify plugin compatibility
3. Test with default WordPress theme
4. Contact plugin support

## Changelog

### Version 1.0
- Initial release of update checker
- Generate Button functionality
- Admin interface implementation
- Security features implementation
- Responsive design 