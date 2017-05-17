<?php
/*
Plugin Name: SW_Risk Unsubscription
Plugin URI: http://www.orangecreative.net
Description: Plugin for risk status
Author: C. Lupu
Version: 1.0
Author URI:
*/

add_action( 'admin_menu', 'sw_admin_actions3' );


function sw_admin_actions3() {

    add_options_page( 'SW_UnSubscription', 'SW_UnSubscription', 'SW_UnSubscription', 'SWUnSubscription', 'sw_unsubscribe' );

}

add_shortcode( "sw_risk_unsubscribe", "sw_unsubscribe" );

function sw_unsubscribe() {
    global $wpdb;

    echo '<form method="post" action="">';
    echo 'Email: <input type="text" name="user"  style="height:40px;width:200px"><br>';
    echo 'Password: <input type="password" name="password"  style="height:40px;width:200px"><br><br><br>';
//    echo 'Email: <input type="text" name="user" ><br/>';
//    echo 'Password: <input type="password" name="password"><br/>';


    echo '<input type="submit" name="submit" value="Unsubscribe"  ><br>';
    echo '</form>';
    echo '<br/>';
    echo '<hr/>';

    $user_email    = "";
    $user_password = "";
    $userdbconn    = "";
    //submit happened, validate user
    if ( isset( $_POST['submit'] ) ) {
        $user_email    = $_POST['user'];
        $user_password = $_POST['password'];

        try {
            $userdbconn = new wpdb( 'wordpressU', 'wordpressP', 'wordpress', 'localhost:3306' );
        } catch ( Exception $e ) {
            echo "<p>Error Connecting</p>";
        }

        $sql = "SELECT user_pass FROM wp_users where user_email='{$user_email}';";
        echo $sql;
        $hash = "";
        $res  = $wpdb->get_results( $sql );
        foreach ( $res as $row ) {
            $hashed = $row->user_pass;
        }


        $user = get_user_by( 'email', $user_email );
        echo "<br/>";
        //if authenticated
        if ( wp_check_password( $user_password, $hashed, $user ) ) {
            $sql1 = "Delete from wp_users where user_email='$user_email' and user_pass='$user_password'";
            $sql2 = 'DELETE FROM wp_usermeta WHERE NOT EXISTS (SELECT * FROM wp_users WHERE wp_usermeta.user_id = wp_users.ID)';


            $userdbconn->get_results( $sql2 );
            print "<script type=\"text/javascript\">";
            print "alert('Unsubscribed successfully')";
            print "</script>";

            //fail authentication
        } else {
            print "<script type=\"text/javascript\">";
            print "alert('Either email or password is incorrect')";
            print "</script>";
        }
    }
}

?>