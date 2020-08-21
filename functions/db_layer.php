<?php
    //Set the environment needed
    function wrn_set_default_settings(){
        global $wpdb;
        //DB run once
        if(wrn_get_option_parent('wrn_db_setup') != 1){

            $charset_collate = $wpdb->get_charset_collate();
            $table_name = $wpdb->base_prefix . 'wrn_media';
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
            wrn_update_option_parent('wrn_db_setup', 1);
            wrn_update_option_parent('wrn_max_admin_approval', 1);
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

    //Add media to be shared between levels
   function wrn_add_media($name,$url,$status, $admins = array(),$blog_id){
       global $wpdb;
       $wpdb->query("INSERT INTO $wpdb->base_prefix" . "wrn_media (id,name,url,status,admins,blog_id) VALUES ('$name','$url','$status','$admins','$blog_id'))");
       if($wpdb->insert_id > 0){
           return true;
       }else{
           return false;
       }
   }

    //Create my own get_option but another level (get_site and get_network not working properly)
    function wrn_get_option_parent($option_name){
        global $wpdb;
        $options = $wpdb->get_results("SELECT * FROM $wpdb->base_prefix" . "options WHERE option_name='{$option_name}'", ARRAY_A);
        if(count($options)>0){
            return $options[0]['option_value'];
        }
        return false;
    }

    function wrn_update_option_parent($option_name,$option_value){
        global $wpdb;
        if(wrn_get_option_parent($option_name)){
            $wpdb->query("UPDATE $wpdb->base_prefix" . "options SET option_value='$option_value' WHERE option_name='$option_name'");
            return true;
        }else{
            $wpdb->query("INSERT INTO $wpdb->base_prefix" . "options (option_name,option_value) VALUES ('$option_name','$option_value')");
            if($wpdb->insert_id > 0){
                return true;
            }else{
                return false;
            }
        }
    }