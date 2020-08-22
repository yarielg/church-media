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
                if(wrn_get_num_admin_signed($media->ID) >= wrn_get_option_parent('wrn_max_admin_approval') ){
                    update_post_meta($media->ID, 'media_sent', $user->ID);
                    $current_site = get_current_blog_id();
                    wrn_add_media($media->ID,$media->post_name, $media->guid,'requested', serialize(wrn_get_admin_signed($media->ID)),$current_site);
                    echo '<div class="updated notice"><p>Success! The request was sent to higher level</p></div>';
                }else{
                    $missing = intval(wrn_get_option_parent('wrn_max_admin_approval')) - intval(wrn_get_num_admin_signed($media->ID));
                    echo '<div class="updated notice"><p>Error! It look like the condition changed in the last minute. To send this request you need '. $missing .' approvals for admins more</p></div>';
                }

            }else if(($_GET['actiona'] ==  'wr_send_revoke_media') && $_GET['media'] == $media->ID){
                update_post_meta($media->ID, 'media_sent', 0);
                echo '<div class="updated notice"><p>Success! The request was removed</p></div>';
            }
        }
        $media_sent = get_post_meta($media->ID, 'media_sent', true);
        if(in_array( 'administrator', $user->roles)){
            if(wrn_get_num_admin_signed($media->ID) >= wrn_get_option_parent('wrn_max_admin_approval') ){
                if($media_sent == 0 ){
                    $actions['wr_send_approval_media'] = "<a class='wr_send_approval_media' href='" . admin_url( "upload.php?actiona=wr_send_approval_media&amp;media=" . $media->ID ) . "'>" . __( 'Send Media' ) . "</a>";
                }else{
                    $actions['wr_send_revoke_media'] = "<a class='wr_send_revoke_media' href='" . admin_url( "upload.php?actiona=wr_send_revoke_media&amp;media=" . $media->ID ) . "'>" . __( 'Revoke Request' ) . "</a>";
                }
            }
        }
        return $actions;
}
add_filter('media_row_actions', 'wrn_send_media_approval', 11, 2);

//Processing delete request
add_action( 'admin_init', 'wrn_processing_request' );
function wrn_processing_request(){
    global $wpdb;
    if(isset($_GET['action']) && isset($_GET['media'])) {
        if ($_GET['action'] == 'wrn_remove_media') {
            $removed = wrn_remove_media($_GET['media']);
            echo $removed
                ? '<div class="updated notice"><p>This media was successfully removed</p></div>'
                : '<div class="updated notice"><p>This media was successfully removed</p></div>';

        } else if ($_GET['action'] == 'wrn_approve_media') {

            if (!isset($_GET['blog_id'])) {
                echo '<div class="error notice"><p>Error, We cant find the blog id to copy the post</p></div>';
                wp_redirect($_SERVER['HTTP_REFERER']);
                exit();
            }
            /*
             * get the original media id
             */
            $post_id = (isset($_GET['media']) ? absint($_GET['media']) : absint($_POST['media']));

            switch_to_blog($_GET['blog_id']);

            $post = get_post($post_id, ARRAY_A); // get the original post

            /*$meta = get_post_meta($post_id);
            $post_thumbnail_id = get_post_thumbnail_id($post_id);*/

            $image_url = wp_get_attachment_image_src($post_id, 'full');

            $image_url = $image_url[0];

            $post['ID'] = ''; // empty id field, to tell wordpress that this will be a new post

            restore_current_blog();
          //  $inserted_post_id = wp_insert_post($post); // insert the post
            /*foreach ($meta as $key => $value) {
                update_post_meta($inserted_post_id, $key, $value[0]);
            }*/

            // Add Featured Image to Post
            $upload_dir = wp_upload_dir(); // Set upload folder
            $image_data = file_get_contents($image_url); // Get image data
            $filename = basename($image_url); // Create image file name

            // Check folder permission and define file location
            if (wp_mkdir_p($upload_dir['path'])) {
                $file = $upload_dir['path'] . '/' . $filename;
            } else {
                $file = $upload_dir['basedir'] . '/' . $filename;
            }

            // Create the image  file on the server
            file_put_contents($file, $image_data);

            // Check image file type
            $wp_filetype = wp_check_filetype($filename, null);

            // Set attachment data
            $attachment = array(
                'post_mime_type' => $wp_filetype['type'],
                'post_title' => sanitize_file_name($filename),
                'post_content' => '',
                'post_status' => 'inherit'
            );

            // Create the attachment
            $attach_id = wp_insert_attachment($attachment, $file, $post_id);

            // Include image.php
            require_once(ABSPATH . 'wp-admin/includes/image.php');

            // Define attachment metadata
            $attach_data = wp_generate_attachment_metadata($attach_id, $file);

            // Assign metadata to attachment
            wp_update_attachment_metadata($attach_id, $attach_data);

            wrn_remove_media($post_id);
            wp_redirect(admin_url() . '/upload.php');
            exit();
            wrn_remove_media($post_id);

        }
        wp_redirect($_SERVER['HTTP_REFERER']);
        exit();
    }

}
