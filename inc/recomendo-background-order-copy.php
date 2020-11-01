<?php

//Security to limit direcct access to the plugin file
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

require_once plugin_dir_path( __DIR__ ) . 'inc/libraries/recomendo-async-request.php';
require_once plugin_dir_path( __DIR__ ) . 'inc/libraries/recomendo-background-process.php';
// Load Recomendo plugin
//require_once plugin_dir_path( __DIR__ ) . 'recomendo-plugin.php';

class Recomendo_Background_Order_Copy extends Recomendo_Background_Process {

	/**
	* @var string
	*/

	protected $action = 'order_copy';



	public function get_total_order_items(){
        $count = 0;
        $query_args = array(
            'fields'         => 'ids',
            'post_type'      => 'shop_order',
            'post_status'    => 'any',
            'posts_per_page' => -1,
        );

        $order_ids = get_posts( $query_args );
        if ( sizeof( $order_ids ) > 0 ) {
            foreach ( $order_ids as $id ) {
                $order = wc_get_order( $id );
                $line_items = $order->get_items();
                foreach ( $line_items as $item ) {
                    $count++;
                }
            }
        }
        return $count;
	}

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
		$total_products = $this->get_total_order_items();

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
					
					$progress =  intVal(get_option('recomendo_progress_background_orders'));
					$progress++;
					$percentage = ($progress * 100) / $total_products;
					update_option('recomendo_progress_background_orders', $progress);
					update_option('recomendo_orders_background_completed', $percentage);
					error_log("------[RECOMENDO] PROGRESS BACKGROUND task running orders-".$progress." total product". $total_products);


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
		update_option('recomendo_orders_background_completed', 100);
		// Show notice to user or perform some other arbitrary task...
	}

}
