<?php

//Security to limit direcct access to the plugin file
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

if( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}


class Recomendo_Data_Explorer extends WP_List_Table {

    public $client;

    function __construct() {

        parent::__construct( [
    			'singular' => 'Event', //singular name of the listed records
    			'plural'   => 'Events', //plural name of the listed records
    			'ajax'     => false //does this table support ajax?
    		] );

        $this->client = new Recomendo_Client();
        $this->register();
    }

    function register() {
        add_filter('set-screen-option', array( 'Recomendo_Data_Explorer', 'data_explorer_set_option', 10, 3) );
    }


    function data_explorer_set_option($status, $option, $value) {
        if ( 'events_per_page' == $option ) {
            return $value;
        } else {
            return $status;
        }
    }


    function column_default( $item, $column_name ) {
      switch( $column_name ) {
        case 'properties':
            if ( ! empty ( $item[ $column_name ] ) ) {
                return print_r( json_encode( $item[ $column_name ], JSON_UNESCAPED_SLASHES ) );
            } else {
                return '';
            }
        case 'eventId':
        case 'event':
        case 'entityType':
        case 'entityId':
        case 'targetEntityType':
        case 'targetEntityId':
        case 'eventTime':
        case 'creationTime':
            if ( isset( $item[ $column_name ] ) )
                return $item[ $column_name ];
        default:
          return '';
      }
    }

    function column_cb($item) {
        return sprintf(
            '<input type="checkbox" name="event[]" value="%s" />', $item['eventId']
        );
    }

    function column_eventId($item) {

        $delete_nonce = wp_create_nonce( 'recomendo_delete_event' );

        $actions = array(
            'delete'    => sprintf('<a href="?page=%s&action=%s&event=%s&_wpnonce=%s">Delete</a>',esc_attr( $_REQUEST['page'] ),'delete',$item['eventId'],$delete_nonce)
        );

        return sprintf('%1$s %2$s', $item['eventId'], $this->row_actions($actions) );
    }


    function get_columns(){
        $columns = array(
            'cb' => '<input type="checkbox" />',
            'eventId' => 'ID',
            'event' => 'Event',
            'entityType' => 'Entity Type',
            'entityId' => 'Entity ID',
            'targetEntityType' => 'Target Entity Type',
            'targetEntityId' => 'Target Entity ID',
            'properties' => 'Properties',
            'eventTime' => 'Event Time',
            'creationTime' => 'Creation Type'
        );
        return $columns;
    }


    function get_sortable_columns() {
        $sortable_columns = array(
            'entityId' => array('entityId', false),
            'event' => array('event',false),
            'eventTime'  => array('eventTime', false),
        );
        return $sortable_columns;
    }


    function usort_reorder( $a, $b ) {
        // If no sort, default to title
        $orderby = ( ! empty( $_GET['orderby'] ) ) ? $_GET['orderby'] : 'eventTime';
        // If no order, default to asc
        $order = ( ! empty($_GET['order'] ) ) ? $_GET['order'] : 'asc';
        // Determine sort order
        $result = strcmp( $a[$orderby], $b[$orderby] );
        // Send final sort direction to usort
        return ( $order === 'asc' ) ? $result : -$result;
    }


    function get_bulk_actions() {
        $actions = array(
            'delete' => 'Delete',
            'export' => 'Export'
        );
        return $actions;
    }



    //Detect when a bulk action is being triggered...
    function process_bulk_action() {
        // security check!
        if ( isset( $_REQUEST['_wpnonce'] ) && ! empty( $_REQUEST['_wpnonce'] ) ) { 
            $nonce = esc_attr( $_REQUEST['_wpnonce'] );

            if ( 'delete'===$this->current_action() )  {
                    $action = 'bulk-' . $this->_args['plural'];

                    if ( wp_verify_nonce( $nonce, 'recomendo_delete_event' ) ) {
                        $response = $this->client->delete_event( $_REQUEST['event'] );
                        $status = wp_remote_retrieve_response_code( $response );
                        if  ( $status == 200 ) {
                            echo '<div class="updated notice">';
                            echo '<p>Event ' . $_REQUEST['event'] . ' deleted</p>';
                            echo '</div>';
                        } else {
                            echo '<div class="error notice">';
                            echo '<p>There has been an error. Bummer</p>';
                            echo '</div>';
                        }
                    } elseif ( wp_verify_nonce( $nonce, $action ) ) {
                        foreach( $_REQUEST['event'] as $e ) {
                            $response = $this->client->delete_event( $e );
                            $status = wp_remote_retrieve_response_code( $response );
                            if  ( $status == 200 ) {
                                echo '<div class="updated notice">';
                                echo '<p>Event ' . $e . ' deleted</p>';
                                echo '</div>';
                            } else {
                                echo '<div class="error notice">';
                                echo '<p>There has been an error. Bummer</p>';
                                echo '</div>';
                            }
                        }
                        wp_safe_redirect( remove_query_arg( array( 'action', 'action2', 'event', ), stripslashes( $_SERVER['REQUEST_URI'] ) ) );
                    } else {
                        wp_die( 'Nope! Security check failed!' );
                    }
            } elseif ( 'export'===$this->current_action() ) {

                $export = '[';
                foreach( $_REQUEST['event'] as $e ) {
                    $response = $this->client->get_event( $e );
                    $json = wp_remote_retrieve_body( $response );
                    $export .= $json . ",\n" ;
                }
                $export = substr( $export , 0, -2) . ']' ;

                ob_end_clean();
                header("Content-Type: application/octet-stream; ");
                header("Content-Transfer-Encoding: binary");
                header('Content-Disposition: attachment; filename="recomendo.json"');
                header('Content-Length: ' . strlen($export));
                header('Connection: close');

                echo $export;
                exit;


            }
        }

    }




    function prepare_items( $args = null ) {
		
        $columns = $this->get_columns();
        $hidden = array( 'creationTime' );
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = $this->get_column_info();
        $this->process_bulk_action();

        if( ! empty( $args['start_date'] ) ) {
            $datetime = new DateTime( $args['start_date'] );
            $start_date = $datetime->format('Y-m-d\TH:i:s.u') . 'Z';
        } else {
            $start_date = null;
        }

        if( ! empty( $args['end_date'] ) ) {
            $datetime = new DateTime( $args['end_date'] . ' 23:59:59.999999' );
            $end_date = $datetime->format('Y-m-d\TH:i:s.u') . 'Z';
        } else {
            $end_date = null;
        }

        if( ! empty( $args['entity_id'] ) ) {
            $entity_id = trim( $args['entity_id'] );
        } else {
            $entity_id = null;
        }

        if( ! empty( $args['entity_type'] ) ) {
            $entity_type = $args['entity_type'];
        } else {
            $entity_type = null;
        }

        if ( ! empty( $entity_id ) and isset( $entity_type ) ) {
            $reversed = 'true';
        } else {
            $reversed = 'false';
        }

        $data = $this->client->get_json( $this->client->get_events( $start_date, $end_date, $entity_type, $entity_id, null, null, null, -1, $reversed ) );

        if ( isset( $data['message'] ) ) {
            if ( $data['message'] === 'Not Found' ) return;
        }

        usort( $data, array( &$this, 'usort_reorder' ) );


        if ( isset( $_REQUEST["wp_screen_options"]["value"] ) ) {
            $per_page = intval( $_REQUEST["wp_screen_options"]["value"] );
            update_user_meta( get_current_user_id(), 'recomendo_events_per_page', $per_page );
            ?>
                <script type="text/javascript">
                    jQuery("#recomendo_events_per_page").val( "<?php echo $per_page; ?>" );
                </script>
            <?php
		} else {
            $per_page = intval(get_user_meta( get_current_user_id(), 'recomendo_events_per_page', true ));
            if ( empty( $per_page ) ) {
                $per_page = 20;
                update_user_meta( get_current_user_id(), 'recomendo_events_per_page', $per_page );
            }
		}


        $current_page = $this->get_pagenum();
        $total_items = count( $data );

        $found_data = array_slice( $data,( ( $current_page-1 )* $per_page ), $per_page );

        $this->set_pagination_args(
            array(
                'total_items' => $total_items,                  //WE have to calculate the total number of items
                    'per_page'    => $per_page                     //WE have to determine how many items to show on a page
            )
        );

        $this->items = $found_data;

    }
	
}
