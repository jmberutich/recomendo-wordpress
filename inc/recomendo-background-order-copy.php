<?php

//Security to limit direcct access to the plugin file
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

require_once plugin_dir_path( __DIR__ ) . 'inc/libraries/recomendo-async-request.php';
require_once plugin_dir_path( __DIR__ ) . 'inc/libraries/recomendo-background-process.php';

class Recomendo_Background_Order_Copy extends Recomendo_Background_Process {

	/**
	* @var string
	*/

	protected $action = 'order_copy';

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

		$order = wc_get_order( $item );
		$line_items = $order->get_items();

		// Send the order
		$userid = $order->get_user_id();

		if ( $userid == 0 ) {
			// If the purchase was from unregistered user
			// we need to create the user
			$userid = 'order_' . strval( $order->get_id() );
		}

		$response = $recomendo->client->set_user( $userid );

		if ( is_wp_error( $response )) {
			error_log( "[RECOMENDO] --- Error adding user " . strval( $userid ) . " when recording all orders." );
			error_log( "[RECOMENDO] --- " . $response->get_error_message() );
		} else {

			// This loops over line items
			foreach ( $line_items as $item ) {
				// This will be a product
				$productid = $item->get_product_id();

				// check product has not been deleted
				if ( $productid != 0 ) {
					$response = $recomendo->client->record_user_action( 'buy',  $userid , $productid );

					if ( is_wp_error( $response ) ) {
						error_log( "[RECOMENDO] --- Error recording buy event for order " . strval($order->get_id()) );
						error_log( "[RECOMENDO] --- " . $response->get_error_message() );
					}
				}
			}
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
		// Show notice to user or perform some other arbitrary task...
	}

}
