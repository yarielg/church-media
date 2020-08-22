<?php

if( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

include 'functions/helpers.php';
include 'functions/db_layer.php';
include 'functions/Request_Media_Table.php';
include 'functions/pages.php';
include 'functions/actions.php';
include 'functions/media_native.php';

add_action('admin_init','wrn_set_default_settings');




?>
