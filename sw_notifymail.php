<?php
/*
Plugin Name: SW_Mailer
Plugin URI: http://www.orangecreative.net
Description: Plugin for risk status
Author: C. Lupu
Version: 1.0
Author URI:
*/


add_action( 'admin_menu', 'sw_admin_actions2' );

function sw_admin_actions2() {

    add_options_page( 'SW_Mailer', 'SW_Mailer', 'manage_options', '__FILE__', 'sw_email' );

}

function sw_email() {
    $to      = 'fuzznuzz@gmail.com';
    $subject = 'Disease risk for Monash Caulfield';
    $body    = 'There is a <h1>high</h1> disease risk for Monash Caulfield! Watch out!!';
    $headers = array( 'Content-Type: text/html; charset=UTF-8' );

    wp_mail( $to, $subject, $body, $headers );


}

?>