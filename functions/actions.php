<?php
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

//Sending media to approval
function wrn_send_media_approval($actions, $media) {
    $user = wp_get_current_user();
    if( isset($_GET['actiona'])) {
        if($_GET['actiona'] ==  'wr_send_approval_media' && $_GET['media'] == $media->ID){
            update_post_meta($media->ID, 'media_sent', $user->ID);
            echo '<div class="updated notice"><p>Success! The request was sent to higher level</p></div>';
        }else if(($_GET['actiona'] ==  'wr_send_revoke_media') && $_GET['media'] == $media->ID){
            update_post_meta($media->ID, 'media_sent', 0);
            echo '<div class="updated notice"><p>Success! The request was removed</p></div>';
        }
        echo 'something';
    }
    $media_sent = get_post_meta($media->ID, 'media_sent', true);
    if(in_array( 'administrator', $user->roles)){
        if($media_sent == 0 ){
            $actions['wr_send_approval_media'] = "<a class='wr_send_approval_media' href='" . admin_url( "upload.php?actiona=wr_send_approval_media&amp;media=" . $media->ID ) . "'>" . __( 'Send Media' ) . "</a>";
        }else{
            $actions['wr_send_revoke_media'] = "<a class='wr_send_revoke_media' href='" . admin_url( "upload.php?actiona=wr_send_revoke_media&amp;media=" . $media->ID ) . "'>" . __( 'Revoke Media' ) . "</a>";
        }
    }

    return $actions;
}
add_filter('media_row_actions', 'wrn_send_media_approval', 11, 2);