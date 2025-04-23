<?php
/**
 * Plugin Name: Failed Actions Monitor for Action Scheduler
 * Plugin URI: https://rootscope.dev/
 * Description: Monitors failed Action Scheduled jobs and sends email notifications. Provides a daily report of failed actions and allows configuration of notification recipients.
 * Version: 1.0.0
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Author: Rootscope
 * Author URI: https://rootscope.dev/
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: failed-actions-monitor
 *
 * @package FailedActionsMonitor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants.
define( 'FAM_VERSION', '1.0.0' );
define( 'FAM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'FAM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'FAM_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Check if Action Scheduler is active.
 *
 * @return bool
 */
function fam_check_action_scheduler() {
	return class_exists( 'ActionScheduler' );
}

/**
 * Include required files.
 *
 * @return void
 */
function fam_include_files() {
	$required_files = array(
		'includes/class-fam-settings.php',
		'includes/class-fam-monitor.php',
	);

	foreach ( $required_files as $file ) {
		$file_path = FAM_PLUGIN_DIR . $file;
		if ( file_exists( $file_path ) ) {
			require_once $file_path;
		} else {
			wp_die(
				sprintf(
					/* translators: %s: Missing file path */
					esc_html__( 'Failed Actions Monitor: Required file %s is missing.', 'failed-actions-monitor' ),
					esc_html( $file )
				)
			);
		}
	}
}

/**
 * Initialize the plugin.
 *
 * @return void
 */
function fam_init() {
	// Check if Action Scheduler is active.
	if ( ! fam_check_action_scheduler() ) {
		add_action(
			'admin_notices',
			function () {
				?>
				<div class="notice notice-error">
					<p>
						<?php
						printf(
							/* translators: %s: Plugin name */
							esc_html__( '%s requires Action Scheduler to be installed and activated.', 'failed-actions-monitor' ),
							'<strong>Failed Actions Monitor</strong>'
						);
						?>
					</p>
				</div>
				<?php
			}
		);
		return;
	}

	// Load plugin text domain.
	load_plugin_textdomain( 'failed-actions-monitor', false, dirname( FAM_PLUGIN_BASENAME ) );

	// Include required files.
	fam_include_files();

	// Initialize settings.
	new FailedActionsMonitor\FAM_Settings();

	// Initialize monitor.
	new FailedActionsMonitor\FAM_Monitor();
}
add_action( 'plugins_loaded', 'fam_init' );

/**
 * Plugin activation hook.
 *
 * @return void
 */
function fam_activate() {
	// Check if Action Scheduler is active.
	if ( ! fam_check_action_scheduler() ) {
		wp_die(
			esc_html__( 'Failed Actions Monitor requires Action Scheduler to be installed and activated.', 'failed-actions-monitor' ),
			esc_html__( 'Plugin Activation Error', 'failed-actions-monitor' ),
			array(
				'back_link' => true,
			)
		);
	}

	// Schedule the daily check.
	if ( ! wp_next_scheduled( 'fam_daily_check' ) ) {
		wp_schedule_event( strtotime( '5:00:00' ), 'daily', 'fam_daily_check' );
	}
}
register_activation_hook( __FILE__, 'fam_activate' );

/**
 * Plugin deactivation hook.
 *
 * @return void
 */
function fam_deactivate() {
	// Clear the scheduled event.
	wp_clear_scheduled_hook( 'fam_daily_check' );
}
register_deactivation_hook( __FILE__, 'fam_deactivate' );

/**
 * Plugin uninstall hook.
 *
 * @return void
 */
function fam_uninstall() {
	// Delete plugin options.
	delete_option( 'fam_options' );

	// Clear any remaining scheduled events.
	wp_clear_scheduled_hook( 'fam_daily_check' );
}
register_uninstall_hook( __FILE__, 'fam_uninstall' ); 