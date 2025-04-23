<?php
/**
 * Settings class for Failed Actions Monitor.
 *
 * @package FailedActionsMonitor
 */

namespace FailedActionsMonitor;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class FAM_Settings
 *
 * Handles the plugin settings and admin interface.
 */
final class FAM_Settings {
	/**
	 * Plugin options.
	 *
	 * @var array
	 */
	private $options;

	/**
	 * Option name in the database.
	 *
	 * @var string
	 */
	private $option_name = 'fam_options';

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
		add_action( 'admin_init', array( $this, 'page_init' ) );
	}

	/**
	 * Add plugin settings page.
	 *
	 * @return void
	 */
	public function add_plugin_page() {
		add_options_page(
			'Failed Actions Monitor Settings',
			'Failed Actions Monitor',
			'manage_options',
			'failed-actions-monitor',
			array( $this, 'create_admin_page' )
		);
	}

	/**
	 * Create the admin page.
	 *
	 * @return void
	 */
	public function create_admin_page() {
		// Check user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'failed-actions-monitor' ) );
		}

		$this->options = get_option( $this->option_name );
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form method="post" action="options.php">
				<?php
				settings_fields( 'fam_option_group' );
				do_settings_sections( 'failed-actions-monitor' );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Initialize the settings page.
	 *
	 * @return void
	 */
	public function page_init() {
		register_setting(
			'fam_option_group', // Option group name.
			$this->option_name, // Option name in the database.
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize' ),
				'default'           => array(),
			)
		);

		add_settings_section(
			'fam_setting_section',
			__( 'Email Settings', 'failed-actions-monitor' ),
			array( $this, 'section_info' ),
			'failed-actions-monitor'
		);

		add_settings_field(
			'notification_email',
			__( 'Notification Email', 'failed-actions-monitor' ),
			array( $this, 'notification_email_callback' ),
			'failed-actions-monitor',
			'fam_setting_section'
		);
	}

	/**
	 * Sanitize the input.
	 *
	 * @param array $input The input array.
	 * @return array
	 */
	public static function sanitize( $input ) {
		$sanitized = array();

		if ( isset( $input['notification_email'] ) ) {
			$email = sanitize_email( $input['notification_email'] );
			if ( is_email( $email ) ) {
				$sanitized['notification_email'] = $email;
			} else {
				add_settings_error(
					'notification_email',
					'invalid_email',
					__( 'Please enter a valid email address.', 'failed-actions-monitor' )
				);
			}
		}

		return $sanitized;
	}

	/**
	 * Display section info.
	 *
	 * @return void
	 */
	public function section_info() {
		echo '<p>' . esc_html__( 'Enter your email address to receive notifications about failed actions.', 'failed-actions-monitor' ) . '</p>';
	}

	/**
	 * Display notification email field.
	 *
	 * @return void
	 */
	public function notification_email_callback() {
		$value = isset( $this->options['notification_email'] ) ? $this->options['notification_email'] : '';
		printf(
			'<input type="email" id="notification_email" name="%s[notification_email]" value="%s" class="regular-text" />',
			esc_attr( $this->option_name ),
			esc_attr( $value )
		);
	}

	/**
	 * Get notification email address.
	 *
	 * @return string
	 */
	public static function get_notification_email() {
		$options = get_option( 'fam_options' );

		// First try to get the configured notification email.
		if ( ! empty( $options['notification_email'] ) && is_email( $options['notification_email'] ) ) {
			return $options['notification_email'];
		}

		// Then try to get the recovery mode email if defined.
		if ( defined( 'RECOVERY_MODE_EMAIL' ) && is_email( RECOVERY_MODE_EMAIL ) ) {
			return RECOVERY_MODE_EMAIL;
		}

		// Finally fall back to the admin email.
		return get_option( 'admin_email' );
	}
} 