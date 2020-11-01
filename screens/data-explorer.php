<?php
// File Security Check.
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

global $myListTable;

?>

<script type="text/javascript">
    jQuery(document).ready(function($) {
        $("#start_date").change(function() {
            $("#end_date").attr({"min" : $(this).val() });
        });
        $("#end_date").change(function() {
            $("#start_date").attr({"max" : $(this).val() });
        });
    });
</script>

<div class="wrap">
    <h2>Data Explorer</h2>
    <?php

        if ( ! isset( $_REQUEST['entity_id'] ) )  {
            $_REQUEST['entity_id'] = null;
        }

        if ( ! isset( $_REQUEST['start_date'] ) ) {
            $_REQUEST['start_date'] = strftime("%F");
        }

        if ( ! isset( $_REQUEST['end_date'] ) ) {
            $_REQUEST['end_date'] = strftime("%F");
        }

        if ( ! isset( $_REQUEST['entity_type'] ) ) {
            $_REQUEST['entity_type'] = 'user';
        }

        if ( ! empty( $_GET['_wp_http_referer'] ) ) {
            //wp_redirect( remove_query_arg( array( '_wp_http_referer', '_wpnonce' ), stripslashes( $_SERVER['REQUEST_URI'] ) ) );
			 wp_redirect( remove_query_arg( array( '_wp_http_referer' ), stripslashes( $_SERVER['REQUEST_URI'] ) ) );
            exit;
        }

        $myListTable->prepare_items( $_REQUEST );

    ?>
    <form method="get">
    <div class="wp-filter" >
        <div class="filter-items" style="padding-top:10px;">
            <label for="start_date"><span>Start Date:</span></label>
            <input type="date" id="start_date" name="start_date" value="<?php echo $_REQUEST['start_date'] ?>" />
            <label for="end_date"><span>End Date:</span></label>
            <input type="date" id="end_date" name="end_date" value="<?php echo $_REQUEST['end_date'] ?>" />
            <label for="entity_type"><span>Entity Type:</span></label>
            <select id="entity_type" name="entity_type">
                <option <?php selected( $_REQUEST['entity_type'], 'user' ); ?>>user</option>
                <option <?php selected( $_REQUEST['entity_type'], 'item' ); ?>>item</option>
            </select>
        </div>
        <div class="search-form">
            <input type="search" id="entity_id" name="entity_id" value="<?php echo $_REQUEST['entity_id'] ?>" placeholder="Search by Entity ID..." />
            <input type="submit" name="submit" id="submit" class="button button-secondary" value="Search"  />
        </div>
        <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
    </div>
    <?php  $myListTable->display(); ?>
    </form>
</div>
