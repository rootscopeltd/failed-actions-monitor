<?php
/**
 * Monitor class for Failed Actions Monitor.
 *
 * @package FailedActionsMonitor
 */

namespace FailedActionsMonitor;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class FAM_Monitor
 *
 * Handles monitoring of failed actions and sending notifications.
 */
final class FAM_Monitor {
	/**
	 * Number of actions to process in each batch.
	 *
	 * @var int
	 */
	private $batch_size = 100;

	/**
	 * Cache group for failed actions.
	 *
	 * @var string
	 */
	private $cache_group = 'fam_failed_actions';

	/**
	 * Cache expiration in seconds (1 hour).
	 *
	 * @var int
	 */
	private $cache_expiration = 3600;

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'fam_daily_check', array( $this, 'check_failed_actions' ) );
	}

	/**
	 * Check for failed actions from yesterday.
	 *
	 * @return void
	 */
	public function check_failed_actions() {
		// Get yesterday's date range.
		$yesterday_start = gmdate( 'Y-m-d 00:00:00', strtotime( '-1 day' ) );
		$yesterday_end   = gmdate( 'Y-m-d 23:59:59', strtotime( '-1 day' ) );

		// Generate cache key.
		$cache_key = 'failed_actions_count_' . md5( $yesterday_start . $yesterday_end );

		// Try to get from cache first.
		$total_failed = wp_cache_get( $cache_key, $this->cache_group );

		if ( false === $total_failed ) {
			// Use Action Scheduler's API to get failed actions.
			$args = array(
				'status'       => 'failed',
				'date'         => $yesterday_start,
				'date_compare' => '>=',
				'per_page'     => -1,
			);

			$all_failed_actions = as_get_scheduled_actions( $args );

			$filtered = array_filter(
				$all_failed_actions,
				function ( $action ) use ( $yesterday_end ) {
					$date = $action->get_schedule()->get_date()->format( 'Y-m-d H:i:s' );

					// Fix: Use the get_last_attempt_date() method instead of get_last_modified_date()
					// Some versions of ActionScheduler use different method names
					$modified = null;
					if ( method_exists( $action, 'get_last_modified_date' ) ) {
						$modified = $action->get_last_modified_date()->format( 'Y-m-d H:i:s' );
					} elseif ( method_exists( $action, 'get_last_attempt_date' ) ) {
						$modified = $action->get_last_attempt_date()->format( 'Y-m-d H:i:s' );
					} elseif ( method_exists( $action, 'get_timestamp' ) ) {
						// Fallback to timestamp if other methods don't exist
						$modified = gmdate( 'Y-m-d H:i:s', $action->get_timestamp() );
					} else {
						// If no methods are available, just use the scheduled date
						$modified = $date;
					}

					return $date <= $yesterday_end && $modified <= $yesterday_end;
				}
			);

			// Count the total number of failed actions.
			$total_failed = count( $filtered );

			// Cache the result.
			wp_cache_set( $cache_key, $total_failed, $this->cache_group, $this->cache_expiration );
		}

		if ( 0 === (int) $total_failed ) {
			return;
		}

		// Process in batches.
		$offset         = 0;
		$processed      = 0;
		$failed_actions = array();

		while ( $processed < $total_failed ) {
			// Generate cache key for this batch.
			$batch_cache_key = 'failed_actions_batch_' . md5( $yesterday_start . $yesterday_end . $offset );

			// Try to get batch from cache.
			$batch = wp_cache_get( $batch_cache_key, $this->cache_group );

			if ( false === $batch ) {
				$args = array(
					'status'       => 'failed',
					'date'         => $yesterday_start,
					'date_compare' => '>=',
					'per_page'     => $this->batch_size,
					'offset'       => $offset,
				);

				// Remove the modified parameter as it may be causing issues
				$batch = as_get_scheduled_actions( $args );

				// Cache the batch result.
				wp_cache_set( $batch_cache_key, $batch, $this->cache_group, $this->cache_expiration );
			}

			if ( empty( $batch ) ) {
				break;
			}

			// Filter the batch to ensure we're only getting actions from yesterday
			$filtered_batch = array_filter(
				$batch,
				function ( $action ) use ( $yesterday_start, $yesterday_end ) {
					$date = $action->get_schedule()->get_date()->format( 'Y-m-d H:i:s' );
					return $date >= $yesterday_start && $date <= $yesterday_end;
				}
			);

			// Preserve action IDs by using array_merge with array keys
			$failed_actions = $failed_actions + $filtered_batch;
			$processed     += count( $batch );
			$offset        += $this->batch_size;

			// Give the server a small break between batches.
			if ( $processed < $total_failed ) {
				sleep( 1 );
			}
		}

		if ( ! empty( $failed_actions ) ) {
			$this->send_notification( $failed_actions );
		}
	}

	/**
	 * Send notification email about failed actions.
	 *
	 * @param array $failed_actions Array of failed actions.
	 * @return void
	 */
	private function send_notification( $failed_actions ) {
		// Try to get notification email from settings, fallback to admin email.
		$to = FAM_Settings::get_notification_email();
		if ( empty( $to ) ) {
			$to = get_option( 'admin_email' );
		}

		$subject = sprintf(
			'[%s] Failed Actions Report - %s',
			get_bloginfo( 'name' ),
			gmdate( 'Y-m-d', strtotime( '-1 day' ) )
		);

		$message = '<html><body>';
		$message .= sprintf(
			'<h2>Failed Actions Report for %s</h2>',
			gmdate( 'Y-m-d', strtotime( '-1 day' ) )
		);
		$message .= sprintf(
			'<p>Total failed actions: %d</p>',
			count( $failed_actions )
		);

		$message .= '<table style="border-collapse: collapse; width: 100%; margin-top: 20px;">';
		$message .= '<thead><tr style="background-color: #f5f5f5;">';
		$message .= '<th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Action ID</th>';
		$message .= '<th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Hook</th>';
		$message .= '<th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Args</th>';
		$message .= '<th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Group</th>';
		$message .= '<th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Scheduled</th>';
		$message .= '</tr></thead><tbody>';

		foreach ( $failed_actions as $id => $action ) {
			$message .= '<tr style="background-color: #fff;">';
			$message .= sprintf(
				'<td style="border: 1px solid #ddd; padding: 8px;">%d</td>',
				$id ?? 0
			);
			$message .= sprintf(
				'<td style="border: 1px solid #ddd; padding: 8px;">%s</td>',
				$action->get_hook() ?? 'unknown'
			);
			$message .= sprintf(
				'<td style="border: 1px solid #ddd; padding: 8px;">%s</td>',
				json_encode( $action->get_args() ) ?? 'unknown'
			);
			$message .= sprintf(
				'<td style="border: 1px solid #ddd; padding: 8px;">%s</td>',
				$action->get_group() ?? 'unknown'
			);
			$message .= sprintf(
				'<td style="border: 1px solid #ddd; padding: 8px;">%s</td>',
				$action->get_schedule()->get_date()->format( 'Y-m-d H:i:s' ) ?? 'unknown'
			);
			$message .= '</tr>';
		}

		$message .= '</tbody></table>';
		$message .= '</body></html>';

		$headers = array(
			'Content-Type: text/html; charset=UTF-8',
		);

		wp_mail( $to, $subject, $message, $headers );
	}
}
