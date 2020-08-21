<?php

if( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}
include 'functions/Request_Media_Table.php';

add_action('admin_init','wrn_set_default_settings');
function wrn_set_default_settings(){
    global $wpdb;
    //DB run once
    if(get_option('wrn_db_setup') != 1){
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'wrn_media';
        $sql = "CREATE TABLE $table_name (
          id mediumint(9) NOT NULL AUTO_INCREMENT,
          name varchar(100) NOT NULL,
          url varchar(11) NOT NULL,
          status varchar(11) NOT NULL,
          admins varchar(300) NOT NULL,
          blog_id INT(10) NOT NULL,
        
          PRIMARY KEY  (id)
        ) $charset_collate;";
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
        update_option('wrn_db_setup', 1);
    }

    //Set a new role for user that are expecting approval
    add_role(
        'pending_contributor',
            __( 'Pending Contributor'),
            array()
    );
    $contributor = get_role('contributor');
    $contributor->add_cap('upload_files');

    //Handing the approval action
    if(isset($_GET['action']) && $_GET['action'] == 'wrn_approve_media'){
        update_post_meta($_GET['media'], '_media_status','approved');
    }

}

//Set a new page under Media menu in Parent Dashboard Website
add_action('admin_menu', 'wrn_add_request_page');
function wrn_add_request_page(){
    $user = wp_get_current_user();
    if(get_current_blog_id() == 1  && in_array( 'administrator', $user->roles )){
        add_submenu_page( 'upload.php', 'Requests', 'Requests', 'manage_options', 'wrn_request_media', function(){
                $myListTable = new Request_Media_Table();
                echo '<div class="wrap"><h2>Media Requests</h2>';
                $myListTable->prepare_items();
                $myListTable->display();
                echo '</div>';
        } );
    }
}

// Rejecting and Approving contributors / Sending emails
function wrn_send_rejection_link($actions, $user) {

    if( isset($_GET['action']) && ($_GET['user'] == $user->user_email) ) {
        $user->remove_role('pending_contributor');
        $user->add_role('subscriber');
        $sendto = $_GET['user'];
        $sendsub = "Your registration has been rejected.";
        $sendmess = "Your registration for has been rejected.";
        $headers = array('From: The Company <email@domain.com>');
        wp_mail($sendto, $sendsub, $sendmess, $headers);
        echo '<div class="updated notice"><p>Success! The rejection email has been sent to ' . $_GET['user'] . '.</p></div>';
    }
    if(isset($_GET['action']) && ($_GET['action'] == 'wr_approve_contribution') && ($_GET['user'] == $user->user_email)){
        $user->remove_role('pending_contributor');
        $user->remove_role('subscriber');
        $user->add_role('contributor');
        $sendto = $_GET['user'];
        $sendsub = "Your registration was approved";
        $sendmess = "Congratulation, Your registration was approved";
        $headers = array('From: The Company <email@domain.com>');
        wp_mail($sendto, $sendsub, $sendmess, $headers);
        echo '<div class="updated notice"><p>Success! The approval email has been sent to ' . $_GET['user'] . '.</p></div>';
    }
    if(in_array( 'pending_contributor', $user->roles )){
        $actions['approve_contribution'] = "<a class='wr_approve_contribution' href='" . admin_url( "users.php?action=wr_approve_contribution&amp;user=" . $user->user_email ) . "'>" . __( 'Approve Contribution' ) . "</a>";
        $actions['send_rejection'] = "<a class='wr_send_rejection' href='" . admin_url( "users.php?action=wr_send_rejection&amp;user=" . $user->user_email ) . "'>" . __( 'Send Rejection' ) . "</a>";
    }
    return $actions;
}
add_filter('user_row_actions', 'wrn_send_rejection_link', 10, 2);

function wr_custom_media_add_media_custom_field( $form_fields, $post ) {
    $user = wp_get_current_user();
    $admins = unserialize(get_post_meta($post->ID,'_admin_signed',true));

    $flag = get_post_meta($post->ID,'media_approved',true) ? true : false;

    $isApproved = $flag ? 'on' : '';
    $checked = $flag ? 'checked' : '';
    $value = $flag ? 'Yes' : 'No';

    //$string_admins = $admins ? (count($admins) > 0 ? implode(', ', $admins) : '') : '';

    if ( in_array( 'administrator', $user->roles) ){
        $disabled = "";
        $form_fields['media_approved'] = array(
            'html' => "<input type='checkbox' {$checked}  name='attachments[{$post->ID}][isApproved]' id='attachments[{$post->ID}][isApproved]' />",
            'value' => $isApproved,
            'label' => __( 'Approve this' ),
            'input'  => 'html'
        );
    }else{
        $form_fields['media_approved'] = array(
            'html' => "<span>" .  $value ."</span>",
            'value' => $isApproved,
            'label' => __( 'Approved: ' ),
            'input'  => 'html'
        );
    }

    /*$form_fields['media_admins'] = array(
        'html' => "<strong>".$string_admins."</strong>",
        'value' => $string_admins,
        'label' => __( 'Approved By: ' ),
        'input'  => 'html'
    );*/
    return $form_fields;
}
add_filter( 'attachment_fields_to_edit', 'wr_custom_media_add_media_custom_field', null, 10,2 );

//save your custom media field
function wr_saving_custom_media_fields ( $post, $attachments ) {
    $user = wp_get_current_user();
    if(isset($attachments['isApproved'] ) && $attachments['isApproved']  == 'on')
        update_post_meta( $post['ID'], 'media_approved', 1);
    else
        update_post_meta( $post['ID'], 'media_approved', 0);


    $admins = (get_post_meta($post['ID'],'_admin_signed',true) == null || get_post_meta($post['ID'],'_admin_signed',true) == '')
                    ? array()
                    : unserialize(get_post_meta($post['ID'],'media_approved',true));

   /* if($isApproved && !in_array($user->ID, $admins)){
        //$admins[$user->ID] = $user->user_login;
    }else {
        //unset($admins[$user->ID]);
    }*/
    //update_post_meta( $post['ID'], 'media_approved', serialize($admins));
    return $post;
}
add_filter( 'attachment_fields_to_save', 'wr_saving_custom_media_fields', 10, 2 );


//Media Table
// Add the column
function wrn_approved_column( $cols ) {
    $cols["media_approved"] = "Approved";
    return $cols;
}

// Display media
function wrn_approved_value( $column_name, $id ) {
    $approved = get_post_meta( $id , 'media_approved', true );
    //$admins = unserialize(get_post_meta($id,'media_approved',true));
    //$string_admins = $admins ? (count($admins) > 0 ? implode(', ', $admins) : '') : '';
    echo $approved >0 ? 'Yes' : 'No';
}

// Register the column as sortable & sort by name
function wrn_approved_column_sortable( $cols ) {
    $cols["media_approved"] = "name";
    return $cols;
}

// Hook actions to admin_init
function wrn_hook_new_media_columns() {
    add_filter( 'manage_media_columns', 'wrn_approved_column' );
    add_action( 'manage_media_custom_column', 'wrn_approved_value', 10, 2 );
    add_filter( 'manage_upload_sortable_columns', 'wrn_approved_column_sortable' );
}
add_action( 'admin_init', 'wrn_hook_new_media_columns' );

//Restrict Media
add_filter( 'ajax_query_attachments_args', 'wpb_show_current_user_attachments' );
function wpb_show_current_user_attachments( $query ) {
    $user_id = get_current_user_id();
    if ( $user_id &&
        !current_user_can('activate_plugins') &&
        !current_user_can('edit_others_posts') ) {
        $query['meta_query'] = array(
            'relation' => 'AND', // Optional, defaults to "AND"
            array(
                'key'     => 'media_approved',
                'value'   => '1',
                'compare' => '='
            )
        );
    }
    return $query;
}

//Approving attachment by default  uploaded by admin
function wrn_adding_attachment($attachment_ID)
{
    $user = wp_get_current_user();
    if ( in_array( 'administrator', $user->roles) ){
        update_post_meta($attachment_ID, 'media_approved', 1);
    }
}
add_action("add_attachment", 'wrn_adding_attachment');

?>
