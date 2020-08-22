<?php

function wrn_get_num_admin_signed($id){
    $admins = get_post_meta($id,'admin_signed',true) ? unserialize(get_post_meta($id,'admin_signed',true)) : array();
    return is_array($admins )  ? count($admins) : 0;
}

function wrn_get_admin_signed($id){
    return get_post_meta($id,'admin_signed',true) ? unserialize(get_post_meta($id,'admin_signed',true)) : array();
}