<?php

use \WP_CLI\Utils;

/**
 * Perform basic database operations.
 *
 * ## OPTIONS
 *
 * --yes
 * : Answer yes to the confirmation message.
 *
 * <file>
 * : The name of the export file. If omitted, it will be '{dbname}.sql'
 *
 * <SQL>
 * : A SQL query.
 *
 * ## EXAMPLES
 *
 *     # execute a query stored in a file
 *     wp db query < debug.sql
 */
class DB_Command extends WP_CLI_Command {

	/**
	 * Create the database, as specified in wp-config.php
	 */
	function create( $_, $assoc_args ) {
		self::run_query( sprintf( 'CREATE DATABASE `%s`', DB_NAME ) );

		WP_CLI::success( "Database created." );
	}

	/**
	 * Delete the database.
	 *
	 * @synopsis [--yes]
	 */
	function drop( $_, $assoc_args ) {
		WP_CLI::confirm( "Are you sure you want to drop the database?", $assoc_args );

		self::run_query( sprintf( 'DROP DATABASE `%s`', DB_NAME ) );

		WP_CLI::success( "Database dropped." );
	}

	/**
	 * Remove all tables from the database.
	 *
	 * @synopsis [--yes]
	 */
	function reset( $_, $assoc_args ) {
		WP_CLI::confirm( "Are you sure you want to reset the database?", $assoc_args );

		self::run_query( sprintf( 'DROP DATABASE IF EXISTS `%s`', DB_NAME ) );
		self::run_query( sprintf( 'CREATE DATABASE `%s`', DB_NAME ) );

		WP_CLI::success( "Database reset." );
	}

	/**
	 * Optimize the database.
	 */
	function optimize() {
		self::run( Utils\esc_cmd( 'mysqlcheck %s', DB_NAME ), array(
			'optimize' => true,
		) );

		WP_CLI::success( "Database optimized." );
	}

	/**
	 * Repair the database.
	 */
	function repair() {
		self::run( Utils\esc_cmd( 'mysqlcheck %s', DB_NAME ), array(
			'repair' => true,
		) );

		WP_CLI::success( "Database repaired." );
	}

	/**
	 * Open a mysql console using the WordPress credentials.
	 *
	 * @alias connect
	 */
	function cli() {
		self::run( 'mysql --no-defaults', array(
			'database' => DB_NAME
		) );
	}

	/**
	 * Execute a query against the database.
	 *
	 * @synopsis [<sql>]
	 */
	function query( $args ) {
		$assoc_args = array(
			'database' => DB_NAME
		);

		// The query might come from STDIN
		if ( !empty( $args ) ) {
			$assoc_args['execute'] = $args[0];
		}

		self::run( 'mysql --no-defaults', $assoc_args );
	}

	/**
	 * Exports the database using mysqldump.
	 *
	 * @alias dump
	 *
	 * @synopsis [<file>]
	 */
	function export( $args, $assoc_args ) {
		$result_file = $this->get_file_name( $args );

		self::run( Utils\esc_cmd( 'mysqldump %s', DB_NAME ), array(
			'result-file' => $result_file
		) );

		WP_CLI::success( sprintf( 'Exported to %s', $result_file ) );
	}

	/**
	 * Import database from a file.
	 *
	 * @synopsis [<file>]
	 */
	function import( $args, $assoc_args ) {
		$result_file = $this->get_file_name( $args );
		if ( !file_exists( $result_file ) ) {
			WP_CLI::error( sprintf( 'Import file missing: %s', $result_file ) );
		}

		$descriptors = array(
			array( 'file', $result_file, 'r' ),
			STDOUT,
			STDERR,
		);

		self::run( 'mysql --no-defaults', array(
			'database' => DB_NAME
		), $descriptors );

		WP_CLI::success( sprintf( 'Imported from %s', $result_file ) );
	}

	private function get_file_name( $args ) {
		if ( empty( $args ) )
			return sprintf( '%s.sql', DB_NAME );

		return $args[0];
	}

	private static function run_query( $query ) {
		self::run( 'mysql --no-defaults', array( 'execute' => $query ) );
	}

	private static function run( $cmd, $assoc_args = array(), $descriptors = null ) {
		$final_args = array_merge( $assoc_args, array(
			'host' => DB_HOST,
			'user' => DB_USER,
			'pass' => DB_PASSWORD,
		) );

		Utils\run_mysql_command( $cmd, $final_args, $descriptors );
	}
}

WP_CLI::add_command( 'db', 'DB_Command' );

