<?php

//Security to limit direcct access to the plugin file
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

require_once plugin_dir_path( __DIR__ ) . 'inc/libraries/recomendo-async-request.php';
require_once plugin_dir_path( __DIR__ ) . 'inc/libraries/recomendo-background-process.php';

class Recomendo_Background_User_Copy extends Recomendo_Background_Process {

	/**
	 * @var string
	 */
	protected $action = 'user_copy';

	/**
	 * Task
	 *
	 * Override this method to perform any actions required on each
	 * queue item. Return the modified item for further processing
	 * in the next pass through. Or, return false to remove the
	 * item from the queue.
	 *
	 * @param mixed $item Queue item to iterate over
	 *
	 * @return mixed
	 */
	protected function task( $item ) {
		// Actions to perform

		global $recomendo;
		$progress =  intVal(get_option('recomendo_progress_background_users'));
		$progress++;
		$wp_users = count_users();		
		$total_users = $wp_users['total_users'];
		$percentage = ($progress * 100) / $total_users;
		update_option('recomendo_progress_background_users', $progress);
		update_option('recomendo_users_background_completed', $percentage);
		error_log("------[RECOMENDO] PROGRESS BACKGROUND task running users---------".$progress);

		$response = $recomendo->client->set_user( $item, array(
                                        'user_agent' => '',
                                        'ip_address' => ''
                                        )
                             );


		// check the response
		if ( is_wp_error( $response ) ) {
			error_log( "[RECOMENDO] --- Error adding a user." );
			error_log( "[RECOMENDO] --- " . $response->get_error_message() );
		}

		return false;
	}

	/**
	 * Complete
	 *
	 * Override if applicable, but ensure that the below actions are
	 * performed, or, call parent::complete().
	 */
	protected function complete() {
		parent::complete();
		update_option('recomendo_users_background_completed', 100);
		// Show notice to user or perform some other arbitrary task...

	}

}
