=== Failed Actions Monitor for Action Scheduler ===
Contributors: rootscope
Tags: action scheduler, failed actions, monitoring, notifications, email
Requires at least: 5.8
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Monitors failed Action Scheduler jobs and sends daily email notifications about them.

== Description ==

Failed Actions Monitor is a WordPress plugin that helps you keep track of failed Action Scheduler jobs. It sends daily email notifications about any failed actions, making it easier to monitor and debug issues in your WordPress site.

= Features =

* Daily email notifications about failed actions
* Configurable notification email address
* Fallback to admin email if no specific email is set
* Support for recovery mode email
* Easy to use settings page
* Compatible with WordPress 5.8 and higher
* Requires PHP 7.4 or higher

= Why Use This Plugin? =

If you're using Action Scheduler in your WordPress site, you know how important it is to monitor failed actions. This plugin makes it easy to:

* Stay informed about failed actions
* Configure who receives notifications
* Debug issues quickly
* Keep your site running smoothly

== Installation ==

1. Upload the `failed-actions-monitor` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Settings > Failed Actions Monitor to configure your notification email
4. The plugin will automatically start monitoring failed actions

== Frequently Asked Questions ==

= Do I need Action Scheduler installed? =

Yes, this plugin requires Action Scheduler to be installed and activated. It works as an add-on to monitor failed actions.

= How often are notifications sent? =

Notifications are sent once per day, at 5:00 AM (server time). This gives you a daily summary of any failed actions.

= Can I change the notification email? =

Yes, you can set a specific email address in the plugin settings. If no email is set, it will fall back to the admin email.

= What information is included in the notifications? =

Each notification includes:
* The number of failed actions
* Action IDs
* Hook names
* Scheduled dates

== Screenshots ==

1. Settings page where you can configure the notification email

== Changelog ==

= 1.0.0 =
* Initial release

== Upgrade Notice ==

= 1.0.0 =
Initial release of Failed Actions Monitor.