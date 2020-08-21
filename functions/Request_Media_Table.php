<?php

class Request_Media_Table extends WP_List_Table {

    function get_columns(){
        $columns = array(
            'cb'        => '<input type="checkbox" />',
            'ID' => 'ID',
            'guid'  => 'Url',
            'status'      => 'Status'
        );
        return $columns;
    }

    function prepare_items() {
        global $wpdb;
        $per_page = 10;
        $medias = $wpdb->get_results("SELECT * FROM $wpdb->prefix" . "posts WHERE post_type='attachment' ORDER BY id",ARRAY_A );
        $total_items = count($medias);
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = array();
        $this->set_pagination_args( array(
            'total_items' => $total_items,
            'per_page'    => $per_page
        ) );
        $this->_column_headers = array($columns, $hidden, $sortable);
        $this->items = $medias;
    }

    function column_default( $item, $column_name ) {
        switch( $column_name ) {
            case 'ID':
                return $item['ID'];
            case 'guid':
                return '<h1>zxczxc</h1>';
            case 'status':
                return get_post_meta('_media_status', true) == 'approved' ? 'Approved' : 'Not Approved';
            default:
                return print_r( $item, true ) ; //Show the whole array for troubleshooting purposes
        }
    }

    function get_sortable_columns() {
        $sortable_columns = array(

            'ID'  => array('ID',false),
            'guid' => array('guid',false),
            'status'   => array('status',false)
        );
        return $sortable_columns;
    }

    function column_guid($item) {
        $actions = array(

            'View'      => sprintf('<a href="'.$item['guid'].'">View</a>',$_REQUEST['page'],'view',$item['ID']),
            'Approve'      => sprintf('<a href="?page=%s&action=%s&media=%s">Approve</a>',$_REQUEST['page'],'wrn_approve_media',$item['ID']),
        );

        return sprintf('%1$s %2$s', $item['guid'], $this->row_actions($actions) );
    }

    function column_cb($item) {
        return sprintf(
            '<input type="checkbox" name="media[]" value="%s" />', $item['ID']
        );
    }

    function get_bulk_actions() {
        $actions = array(
            'Approve'    => 'Approve'
        );
        return $actions;
    }


}