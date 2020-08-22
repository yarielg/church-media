<?php

class Request_Media_Table extends WP_List_Table {

    function get_columns(){
        $columns = array(
      //      'cb' => '<input type="checkbox" />',
            'name' => 'Name',
            'url' => 'Url',
            'status' => 'Status',
            'admins' => 'Admins Signed',
            'blog_id' => 'Blog ID',
            'view' => 'View',
        );
        return $columns;
    }

    function prepare_items() {
        global $wpdb;
        $per_page = 10;
        $medias = wrn_get_all_requested_media();
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
            case 'id':
                return $item['id'];
            case 'name':
                return $item['name'];
            case 'url':
                return '<img width=50 height=50 src="'.$item['url'].'" alt="">';
            case 'status':
               return $item['status'];
           case 'admins':
               return implode(', ', unserialize($item['admins']));
            case 'blog_id':
                return $item['blog_id'];
            case 'view':
                return '<a href="'.$item['url'].'"> View </a>';
            default:
                return print_r( $item, true ) ; //Show the whole array for troubleshooting purposes
        }
    }

    function get_sortable_columns() {
        $sortable_columns = array(

            'id'  => array('id',false),
            'guid' => array('guid',false),
            'status'   => array('status',false)
        );
        return $sortable_columns;
    }

    function column_name($item) {
        $actions = array(
            'wrn_approve' => sprintf('<a href="?page=%s&action=%s&media=%s&blog_id=%s">Approve</a>',$_REQUEST['page'],'wrn_approve_media',$item['id'],$item['blog_id']),
            'wrn_remove' => sprintf('<a href="?page=%s&action=%s&media=%s">Remove</a>',$_REQUEST['page'],'wrn_remove_media',$item['id']),
        );
        return sprintf('%1$s %2$s', $item['name'], $this->row_actions($actions) );
    }


    /*function column_cb($item) {
        return sprintf(
            '<input type="checkbox" name="media[]" value="%s" />', $item['ID']
        );
    }*/

    /*function get_bulk_actions() {
        $actions = array(
            'Approve'    => 'Approve'
        );
        return $actions;
    }*/

}