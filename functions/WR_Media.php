<?php


class WR_Media
{

   function add_media($name,$url,$status, $admins = array()){
       global $wpdb;
       $wpdb->query("INSERT INTO $wpdb->prefix" . "wrn_media (name,url,status,admins) VALUES ('$name','$url','$status','$admins'))");
       if($wpdb->insert_id > 0){
           return true;
       }else{
           return false;
       }
   }
}