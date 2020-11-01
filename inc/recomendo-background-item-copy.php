<?php

//Security to limit direcct access to the plugin file
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

require_once plugin_dir_path( __DIR__ ) . 'inc/libraries/recomendo-async-request.php';
require_once plugin_dir_path( __DIR__ ) . 'inc/libraries/recomendo-background-process.php';

class Recomendo_Background_Item_Copy extends Recomendo_Background_Process {

	/**
	* @var string
	*/
	protected $action = 'item_copy';

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

		// Check if WPML is installed and get the id of the original language post (not translation)
		if ( function_exists('icl_object_id') ) {
			global $sitepress;
			$item = icl_object_id( $item, $recomendo->options['post_type'], true, $sitepress->get_default_language() );
		}

		if ( class_exists( 'woocommerce' ) ) {
			$terms = get_the_terms( $item, 'product_cat' );
			$taglist = get_the_terms( $item, 'product_tag' );
			$product = wc_get_product( $item );
			// item on sale !
			$is_on_sale = array($product->is_on_sale() ? "yes" : "no" );
			// Featured item
			$is_featured = array($product->is_featured() ? "yes" : "no" );
		} else {
			$terms = get_the_terms( $item, 'category' );
			$taglist = get_the_tags( $item );
			$is_on_sale = array("no"); //false
			$is_featured = array("no");    //false
		}

		$title =  wp_filter_nohtml_kses( get_the_title( $item ) );

		$categories = array();
		if (is_array($terms) or is_object($terms)) {
			foreach ($terms as $term) {
				$categories[] = (string) $term->term_id;
			}
		}

		$tags = array();
		if (is_array($taglist) or is_object($taglist)) {
			foreach ($taglist as $tagitem) {
				$tags[] = (string) $tagitem->term_id;
			}
		}

		$published_date = get_the_date( 'c', $item );

		$properties = compact(
			'title',
			'categories',
			'tags',
			'is_on_sale',
			'is_featured',
			'published_date'
		);

		$response = $recomendo->client->set_item($item, $properties);


		if ( is_wp_error( $response ) ) {
			error_log( "[RECOMENDO] --- Error adding an item.", $item );
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
		// Show notice to user or perform some other arbitrary task...
	}

}
