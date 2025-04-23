# Failed Actions Monitor

A WordPress plugin that monitors failed actions from Action Scheduler and sends daily email notifications.

## Description

Failed Actions Monitor is a WordPress plugin that helps you keep track of failed actions in your Action Scheduler queue. It sends daily email notifications with a detailed report of all failed actions from the previous day.

### Features

- Monitors failed actions from Action Scheduler
- Sends daily email notifications
- Configurable notification email address
- HTML-formatted email reports with detailed action information
- Caches results to improve performance
- Processes actions in batches to handle large queues

## Installation

1. Upload the `failed-actions-monitor` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Configure the notification email address in the plugin settings

## Configuration

The plugin can be configured through the WordPress admin interface:

1. Go to Settings > Failed Actions Monitor
2. Enter the email address where you want to receive notifications
3. Save the settings

## Requirements

- WordPress 5.0 or higher
- PHP 7.2 or higher
- Action Scheduler plugin or WooCommerce (which includes Action Scheduler)

## Frequently Asked Questions

### What information is included in the email notifications?

The email notifications include:
- Total number of failed actions
- Action ID
- Hook name
- Arguments passed to the action
- Action group
- Scheduled date and time

### Can I change the email notification format?

Currently, the plugin sends HTML-formatted emails with a table layout. The format is not configurable through the settings, but you can modify it by editing the plugin code.

### How often are the notifications sent?

Notifications are sent once per day, typically in the early morning, for actions that failed the previous day.

## Changelog

### 1.0.0
- Initial release
- Basic monitoring functionality
- Email notifications
- Configuration options

## License

This plugin is licensed under the GPL v2 or later.

## Credits

Developed by [Your Name/Company]

## Support

For support, please [create an issue](https://github.com/yourusername/failed-actions-monitor/issues) on GitHub. 