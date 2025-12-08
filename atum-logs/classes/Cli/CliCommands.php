<?php
/**
 * Class CliCommands
 *
 * @package        AtumLogs
 * @subpackage     Cli
 * @author         BE REBEL - https://berebel.studio
 * @copyright      ©2025 Stock Management Labs™
 *
 * @since          1.2.0
 */

namespace AtumLogs\Cli;

use AtumLogs\Inc\Helpers;


final class CliCommands {

	/**
	 * Permanently removes logs from database.
	 * Provide 'all' as first parameter if you want to remove every logs.
	 * To remove every logs since a date to now, provide the date as first parameter.
	 * To remove every logs until a date, provide 'all' as first parameter and the date as second parameter.
	 * To remove every logs in a range, provide both dates.
	 *
	 * @since 1.2.0
	 *
	 * @param mixed $args
	 */
	public static function atum_tool_al_remove_logs( $args ) {
		if ( empty( $args ) ) {
			\WP_CLI::line( '' );
			\WP_CLI::line( __( 'Usage', ATUM_LOGS_TEXT_DOMAIN ) . ': wp atum ' . __FUNCTION__ . ' <date_from:YYYY/mm/dd|all> <date_to:YYYY/mm/dd|all>' );
			\WP_CLI::error( __( 'Missing parameter', ATUM_LOGS_TEXT_DOMAIN ), FALSE );
			\WP_CLI::line( '' );
			exit();
		}

		$date1 = 'all' === $args[0] ? FALSE : $args[0];
		$date2 = ( isset( $args[1] ) && 'all' === $args[1] ) || ! isset( $args[1] ) ? FALSE : $args[1];

		\WP_CLI::line( '' );

		/* Translators: %s date */
		$text1 = sprintf( __( 'since %s', ATUM_LOGS_TEXT_DOMAIN ), $date1 );
		/* Translators: %s date */
		$text2 = sprintf( __( 'until %s', ATUM_LOGS_TEXT_DOMAIN ), $date2 );
		/* Translators: %1$s remove since date, %2$s remove until date */
		\WP_CLI::line( trim( sprintf( __( 'You are going to remove every logs %1$s %2$s', ATUM_LOGS_TEXT_DOMAIN ), $text1, $text2 ) ) );
		\WP_CLI::confirm( __( 'Are you sure?', ATUM_LOGS_TEXT_DOMAIN ) );

		$response = Helpers::delete_range_logs( $date1, $date2 );

		\WP_CLI::line( '' );
		if ( $response['error'] ) {
			\WP_CLI::error( $response['text'], FALSE );
		}
		else {
			\WP_CLI::success( $response['text'] );
		}
		\WP_CLI::line( '' );
	}

}
