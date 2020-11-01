<?php

// if uninstall.php is not called by WordPress, die
if ( ! defined('WP_UNINSTALL_PLUGIN' ) ) {
    die;
}


// remove Recomendo options
delete_option( 'recomendo_auth' );
delete_option( 'recomendo_api' );
delete_option( 'recomendo_options' );
delete_option( 'recomendo_woo_options' );
delete_option( 'recomendo_data_saved_ok' );
delete_transient( 'recomendo_token' );
delete_post_meta_by_key( 'recomendo_exclude_metabox' );

// Delete user
$all_user_ids = get_users( 'fields=ID' );
foreach ( $all_user_ids as $user_id ) {
    delete_user_meta( $user_id, 'recomendo_events_per_page' );
    delete_user_meta( $user_id, 'recomendo_exclude_user' );
}
