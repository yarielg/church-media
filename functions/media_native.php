<?php

//Media Table
// Add the column
function wrn_approved_column( $cols ) {
    $cols["media_approved"] = "Approved";
    return $cols;
}

// Display media
function wrn_approved_value( $column_name, $id ) {
    $approved = get_post_meta( $id , 'media_approved', true );
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

function wr_custom_media_add_media_custom_field( $form_fields, $post ) {
    $user = wp_get_current_user();
    $admins = get_post_meta($post->ID,'admin_signed',true) ? unserialize(get_post_meta($post->ID,'admin_signed',true)) : array();

    $flag = get_post_meta($post->ID,'media_approved',true) ? true : false;

    $isApproved = $flag ? 'on' : '';
    $isSigned = (array_key_exists($user->ID, $admins)) ? 'on' : '';
    $checked = $flag ? 'checked' : '';
    $checkedSigned = $isSigned == 'on' ? 'checked' : '';
    $value = $flag ? 'Yes' : 'No';


    if ( in_array( 'administrator', $user->roles) ){
        $disabled = "";
        $form_fields['media_approved'] = array(
            'html' => "<input type='checkbox' {$checked}  name='attachments[{$post->ID}][isApproved]' id='attachments[{$post->ID}][isApproved]' />",
            'value' => $isApproved,
            'label' => __( 'Approved for local use' ),
            'input'  => 'html'
        );
        $form_fields['signed_by'] = array(
            'html' => "<input type='checkbox' {$checkedSigned}  name='attachments[{$post->ID}][isSigned]' id='attachments[{$post->ID}][isSigned]' />",
            'value' => $isSigned,
            'label' => __( 'I have approved this for external use' ),
            'input'  => 'html'
        );
    }else{
        $form_fields['media_approved'] = array(
            'html' => "<span>" .  $value ."</span>",
            'value' => $isApproved,
            'label' => __( 'Approved for local use' ),
            'input'  => 'html'
        );
    }
    $string_admins = $admins ? (count($admins) > 0 ? implode(', ', $admins) : '') : '';
    $form_fields['admin_signed'] = array(
        'html' => "<strong>".$string_admins."</strong>",
        'value' => $string_admins,
        'label' => __( 'Signed By: ' ),
        'input'  => 'html'
    );
    return $form_fields;
}
add_filter( 'attachment_fields_to_edit', 'wr_custom_media_add_media_custom_field', null, 10,2 );

//save your custom media field
function wr_saving_custom_media_fields ( $post, $attachments ) {
    $user = wp_get_current_user();
    $admins = get_post_meta($post['ID'],'admin_signed',true)  ? unserialize(get_post_meta($post['ID'],'admin_signed',true)) : array();

    //Approve media for local use
    if($attachments['isApproved']  == 'on'){
        update_post_meta( $post['ID'], 'media_approved', 1);
    } else {
        update_post_meta( $post['ID'], 'media_approved', 0);
    }
    //Approve media for external use
    if(!array_key_exists($user->ID, $admins) && $attachments['isSigned']  == 'on'){
        $admins[$user->ID] = $user->user_login;
    }else if(array_key_exists($user->ID, $admins) && $attachments['isSigned']  != 'on'){
        unset($admins[$user->ID]);
    }


    update_post_meta( $post['ID'], 'admin_signed', serialize($admins));
    return $post;
}
add_filter( 'attachment_fields_to_save', 'wr_saving_custom_media_fields', 10, 2 );

//Approving attachment by default  uploaded by admin
function wrn_adding_attachment($attachment_ID)
{
    $user = wp_get_current_user();
    if ( in_array( 'administrator', $user->roles) ){
        update_post_meta($attachment_ID, 'media_approved', 1);
    }
}
add_action("add_attachment", 'wrn_adding_attachment');