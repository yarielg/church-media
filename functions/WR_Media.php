<?php


class WR_Media
{

   function add_media($name,$url,$status, $admins = array(),$blog_id){
       global $wpdb;
       $wpdb->query("INSERT INTO $wpdb->prefix" . "wrn_media (id,name,url,status,admins,blog_id) VALUES ('$name','$url','$status','$admins','$blog_id'))");
       if($wpdb->insert_id > 0){
           return true;
       }else{
           return false;
       }
   }
}