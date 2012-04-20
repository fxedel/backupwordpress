<?php

/**
 * Delete the backup and then redirect
 * back to the backups page
 */
function hmbkp_request_delete_backup() {

	if ( ! isset( $_GET['hmbkp_delete'] ) || empty( $_GET['hmbkp_delete'] ) )
		return;

	$schedule = new HMBKP_Scheduled_Backup( urldecode( $_GET['hmbkp_schedule'] ) );
	$schedule->delete_backup( base64_decode( urldecode( $_GET['hmbkp_delete'] ) ) );

	wp_redirect( remove_query_arg( 'hmbkp_delete' ), 303 );

	exit;

}
add_action( 'load-tools_page_' . HMBKP_PLUGIN_SLUG, 'hmbkp_request_delete_backup' );

/**
 * Perform a manual backup via ajax
 */
function hmbkp_ajax_request_do_backup() {

	ignore_user_abort( true );

	hmbkp_do_backup();

	exit;

}
add_action( 'wp_ajax_hmbkp_backup', 'hmbkp_ajax_request_do_backup' );

/**
 * Send the download file to the browser and
 * then redirect back to the backups page
 */
function hmbkp_request_download_backup() {

	if ( empty( $_GET['hmbkp_download'] ) )
		return;

	// Force the .htaccess to be rebuilt
	if ( file_exists( hmbkp_path() . '/.htaccess' ) )
		unlink( hmbkp_path() . '/.htaccess' );

	hmbkp_path();

	wp_redirect( add_query_arg( 'key', md5( HMBKP_SECURE_KEY ), str_replace( hmbkp_conform_dir( ABSPATH ), site_url(), base64_decode( $_GET['hmbkp_download'] ) ) ), 303 );

	exit;

}
add_action( 'load-tools_page_' . HMBKP_PLUGIN_SLUG, 'hmbkp_request_download_backup' );

function hmbkp_request_cancel_backup() {

	if ( ! isset( $_GET['action'] ) || $_GET['action'] !== 'hmbkp_cancel' )
		return;

	hmbkp_cleanup();

	wp_redirect( remove_query_arg( 'action' ), 303 );

	exit;

}
add_action( 'load-tools_page_' . HMBKP_PLUGIN_SLUG, 'hmbkp_request_cancel_backup' );

function hmbkp_dismiss_error() {

	if ( empty( $_GET['action'] ) || $_GET['action'] !== 'hmbkp_dismiss_error' )
		return;

	hmbkp_cleanup();

	wp_redirect( remove_query_arg( 'action' ), 303 );

	exit;

}
add_action( 'admin_init', 'hmbkp_dismiss_error' );

/**
 * Display the running status via ajax
 *
 * @return void
 */
function hmbkp_ajax_is_backup_in_progress() {

	if ( ! hmbkp_in_progress() )
		echo 0;

	else
		include( HMBKP_PLUGIN_PATH . '/admin.backup-button.php' );

	exit;

}
add_action( 'wp_ajax_hmbkp_is_in_progress', 'hmbkp_ajax_is_backup_in_progress' );

/**
 * Display the calculated size via ajax
 *
 * @return void
 */
function hmbkp_ajax_calculate_backup_size() {

	echo hmbkp_calculate();

	exit;

}
add_action( 'wp_ajax_hmbkp_calculate', 'hmbkp_ajax_calculate_backup_size' );

/**
 * Test the cron response and if it's not 200 show a warning message
 *
 * @return void
 */
function hmbkp_ajax_cron_test() {

	$response = wp_remote_get( site_url( 'wp-cron.php' ) );

	if ( ! is_wp_error( $response ) && $response['response']['code'] != '200' )
    	echo '<div id="hmbkp-warning" class="updated fade"><p><strong>' . __( 'BackUpWordPress has detected a problem.', 'hmbkp' ) . '</strong> ' . sprintf( __( '%s is returning a %s response which could mean cron jobs aren\'t getting fired properly. BackUpWordPress relies on wp-cron to run scheduled back ups. See the %s for more details.', 'hmbkp' ), '<code>wp-cron.php</code>', '<code>' . $response['response']['code'] . '</code>', '<a href="http://wordpress.org/extend/plugins/backupwordpress/faq/">FAQ</a>' ) . '</p></div>';

	else
		echo 1;

	exit;

}
add_action( 'wp_ajax_hmbkp_cron_test', 'hmbkp_ajax_cron_test' );

function hmbkp_edit_schedule_load() {

	$schedule = new HMBKP_Scheduled_Backup( esc_attr( $_GET['hmbkp_schedule_slug'] ) );

	require( HMBKP_PLUGIN_PATH . '/admin.schedule-form.php' );

	exit;

}
add_action( 'wp_ajax_hmbkp_edit_schedule_load', 'hmbkp_edit_schedule_load' );

/**
 * Catch the edit schedule form
 *
 * Validate and either return errors or update the schedule
 *
 * @access public
 * @return null
 */
function hmnkp_edit_schedule_submit() {

	if ( empty( $_GET['hmbkp_schedule_slug'] ) )
		return;

	$schedule = new HMBKP_Scheduled_Backup( esc_attr( $_GET['hmbkp_schedule_slug'] ) );

	$errors = array();

	// Schedule name
	if ( isset( $_GET['hmbkp_schedule_name'] ) ) {

		if ( ! trim( $_GET['hmbkp_schedule_name'] ) )
			$errors['hmbkp_schedule_name'] = __( 'Schedule name cannot be empty', 'hmbkp' );

		else
			$schedule->set_name( $_GET['hmbkp_schedule_name'] );

	}

	if ( isset( $_GET['hmbkp_schedule_type'] ) ) {

		if ( ! trim( $_GET['hmbkp_schedule_type'] ) )
			$errors['hmbkp_schedule_type'] = __( 'Backup type cannot be empty', 'hmbkp' );

		elseif ( ! in_array( $_GET['hmbkp_schedule_type'], array( 'complete', 'file', 'database' ) ) )
			$errors['hmbkp_schedule_type'] = __( 'Invalid backup type', 'hmbkp' );

		else
			$schedule->set_type( $_GET['hmbkp_schedule_type'] );

	}

	if ( isset( $_GET['hmbkp_schedule_reoccurrence'] ) ) {

		if ( ! trim( $_GET['hmbkp_schedule_reoccurrence'] ) )
			$errors['hmbkp_schedule_reoccurrence'] = __( 'Schedule cannot be empty', 'hmbkp' );

		elseif ( ! in_array( $_GET['hmbkp_schedule_reoccurrence'], array_keys( wp_get_schedules() ) ) )
			$errors['hmbkp_schedule_reoccurrence'] = __( 'Invalid schedule', 'hmbkp' );

		else
			$schedule->set_reoccurrence( $_GET['hmbkp_schedule_reoccurrence'] );

	}

	if ( isset( $_GET['hmbkp_schedule_max_backups'] ) ) {

		if ( empty( $_GET['hmbkp_schedule_max_backups'] ) )
			$errors['hmbkp_schedule_max_backups'] = __( 'Max backups must be more than 1', 'hmbkp' );

		elseif ( ! is_numeric( $_GET['hmbkp_schedule_max_backups'] ) )
			$errors['hmbkp_schedule_max_backups'] = __( 'Max backups must be a number', 'hmbkp' );

		else
			$schedule->set_max_backups( (int) $_GET['hmbkp_schedule_max_backups'] );

	}

	$schedule->save();

	if ( $errors )
		echo json_encode( $errors );

	exit;

}
add_action( 'wp_ajax_hmnkp_edit_schedule_submit', 'hmnkp_edit_schedule_submit' );

function hmbkp_add_exclude_rule() {

	$schedule = new HMBKP_Scheduled_Backup( esc_attr( $_POST['hmbkp_schedule_slug'] ) );

	$schedule->set_excludes( $_POST['hmbkp_exclude_rule'], true );

	$schedule->save();

	include( HMBKP_PLUGIN_PATH . '/admin.schedule-form-excludes.php' );

	exit;

}
add_action( 'wp_ajax_hmbkp_add_exclude_rule', 'hmbkp_add_exclude_rule' );

function hmbkp_delete_exclude_rule() {

	$schedule = new HMBKP_Scheduled_Backup( esc_attr( $_POST['hmbkp_schedule_slug'] ) );

	$excludes = $schedule->get_excludes();

	$schedule->set_excludes( array_diff( $excludes, (array) $_POST['hmbkp_exclude_rule'] ) );

	$schedule->save();

	include( HMBKP_PLUGIN_PATH . '/admin.schedule-form-excludes.php' );

	exit;

}
add_action( 'wp_ajax_hmbkp_delete_exclude_rule', 'hmbkp_delete_exclude_rule' );

function hmbkp_preview_exclude_rule() {

	if ( ! empty( $_POST['hmbkp_schedule_slug'] ) )
		$schedule = new HMBKP_Scheduled_Backup( $_POST['hmbkp_schedule_slug'] );

	if ( ! empty( $_POST['hmbkp_schedule_excludes'] ) )
		$excludes = explode( ',', $_POST['hmbkp_schedule_excludes'] );

	if ( ! empty( $_POST['hmbkp_file_method'] ) )
		$file_method = $_POST['hmbkp_file_method'];

	hmbkp_file_list( $schedule, $excludes, $file_method );

	foreach( $schedule->get_excluded_files() as $key => $excluded_file )
		if ( strpos( $excluded_file, $schedule->get_path() ) === false )
			$excluded_files[] = $excluded_file; ?>

	<p><?php printf( __( '%s matches %d files.', 'hmbkp' ), '<code>' . esc_attr( implode( '</code>, <code>', $excludes ) ) . '</code>', count( $excluded_files ) ); ?> <button type="button" class="button-primary hmbkp_save_exclude_rule">Exclude</button> <button type="button" class="button-secondary hmbkp_cancel_save_exclude_rule">Cancel</button></p>

	<?php exit;

}
add_action( 'wp_ajax_hmbkp_file_list', 'hmbkp_preview_exclude_rule', 10, 0 );

/**
 * Handles changes in the defined Constants
 * that users can define to control advanced
 * settings
 *
 * @return null
 */
function hmbkp_constant_changes() {

	// If a custom backup path has been set or changed
	if ( defined( 'HMBKP_PATH' ) && HMBKP_PATH && hmbkp_conform_dir( HMBKP_PATH ) != ( $from = hmbkp_conform_dir( get_option( 'hmbkp_path' ) ) ) )
		hmbkp_path_move( $from, HMBKP_PATH );

	// If a custom backup path has been removed
	if ( ( ( defined( 'HMBKP_PATH' ) && ! HMBKP_PATH ) || ! defined( 'HMBKP_PATH' ) && hmbkp_conform_dir( hmbkp_path_default() ) != ( $from = hmbkp_conform_dir( get_option( 'hmbkp_path' ) ) ) ) )
		hmbkp_path_move( $from, hmbkp_path_default() );

	// If the custom path has changed and the new directory isn't writable
	if ( defined( 'HMBKP_PATH' ) && HMBKP_PATH && hmbkp_conform_dir( HMBKP_PATH ) != ( $from = hmbkp_conform_dir( get_option( 'hmbkp_path' ) ) ) && $from != hmbkp_path_default() && !is_writable( HMBKP_PATH ) && is_dir( $from ) )
		hmbkp_path_move( $from, hmbkp_path_default() );

}