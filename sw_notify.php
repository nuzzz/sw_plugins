    <?php
    /*
    Plugin Name: SW_Risk Notify
    Description: Plugin for detecting risk status
    Author: Linus Teo and Tanya Arora
    Version: 2.0
    */

    add_action('admin_menu', 'sw_admin_actions2');


    function sw_admin_actions2()
    {

        add_options_page('SW Risk Notify', 'SW Risk Notify', 'manage_options', 'SW Risk Notify', 'sw_risk_notify');

    }

    add_shortcode("sw_risknotify", "sw_notify");

    add_action('sw_notify_risk_events', 'sw_notify');

    function sw_notify()
    {
        //1. Create empty email array, which will contain "email@gmail.com":"messageContents"
        $email_array = [];

        //$email_array['email1@gmail.com'] = 'text1';
        //$email_array['email2@gmail.com'] = 'text2';
        //$email_array['email3@gmail.com'] = 'text3';

        //2. Create database connections
        try {
            $weatherdbconn = new wpdb('max4monash', 'max4monash', 'weatherdb', 'max4dbinstance.c2a829wujtim.ap-southeast-2.rds.amazonaws.com');
            $userdbconn = new wpdb('wordpressU', 'wordpressP', 'wordpress', 'localhost:3306');
            echo "<p>Connected</p>";
        } catch (Exception $e) {
            echo "<p>Error Connecting</p>";
        }


        //Check powdery mildew
        $sqlpm = "SELECT area_location, forecast_date, date_format(str_to_date(forecast_date, '%Y-%m-%d'),'%W') as forecast_day FROM vic_disease_analysis WHERE powdery_mildew_risk='HIGH';";

        //select all areas of high risk with date and location
        $resultpm = $weatherdbconn->get_results($sqlpm);

        //for each area in of occurrence high risk this week
        foreach ($resultpm as $rowpm) {
            //select emails which belong to these areas
            $sql1 = "select t1.meta_value as user_email,t2.meta_value as region from wp_usermeta t1 INNER JOIN (SELECT user_id, meta_value from wp_usermeta where meta_key='region') t2  ON t1.user_id=t2.user_id  where meta_key='user_email' and t2.meta_value='$rowpm->area_location'";
            $result1 = $userdbconn->get_results($sql1);

            //for each email relating to occurrence
            foreach ($result1 as $row1) {
                $email_array[$row1->user_email] .= "{$row1->region} has a high risk of <a href='http://13.54.13.233/wordpress/index.php/pm-management/'> Powdery Mildew </a> on {$rowpm->forecast_day}, {$rowpm->forecast_date}.<br/>";
            }
        }

        //Check downy mildew
        $sqldm = "SELECT area_location, forecast_date, date_format(str_to_date(forecast_date, '%Y-%m-%d'),'%W') as forecast_day FROM vic_disease_analysis WHERE downy_mildew_risk='HIGH';";
        //select all areas of high risk with date and location
        $resultdm = $weatherdbconn->get_results($sqldm);

        //for each area in of occurrence high risk this week
        foreach ($resultdm as $rowdm) {
            //select emails which belong to these areas
            $sql2 = "select t1.meta_value as user_email,t2.meta_value as region from wp_usermeta t1 INNER JOIN (SELECT user_id, meta_value from wp_usermeta where meta_key='region') t2  ON t1.user_id=t2.user_id  where meta_key='user_email' and t2.meta_value='$rowdm->area_location'";
            $result2 = $userdbconn->get_results($sql2);

            //for each email relating to occurrence
            foreach ($result2 as $row2) {
                $email_array[$row2->user_email] .= "{$row2->region} has a high risk of <a href='http://13.54.13.233/wordpress/index.php/dm-management/'> Downy Mildew </a> on {$rowdm->forecast_day}, {$rowdm->forecast_date}.<br/>";
            }
        }

        //Check grey_mould
        $sqlgm = "SELECT area_location, forecast_date, date_format(str_to_date(forecast_date, '%Y-%m-%d'),'%W') as forecast_day FROM vic_disease_analysis WHERE grey_mould_risk='HIGH';";

        //select all areas of high risk with date and location
        $resultgm = $weatherdbconn->get_results($sqlgm);

        //for each area in of occurrence high risk this week
        foreach ($resultgm as $rowgm) {
            //select emails which belong to these areas
            $sql3 = "select t1.meta_value as user_email,t2.meta_value as region from wp_usermeta t1 INNER JOIN (SELECT user_id, meta_value from wp_usermeta where meta_key='region') t2  ON t1.user_id=t2.user_id  where meta_key='user_email' and t2.meta_value='$rowgm->area_location'";
            $result3 = $userdbconn->get_results($sql3);

            //for each email relating to occurrence
            foreach ($result3 as $row3) {
                $email_array[$row3->user_email] .= "{$row3->region} has a high risk of <a href='http://13.54.13.233/wordpress/index.php/gm-management/'> Grey Mould </a> on {$rowgm->forecast_day}, {$rowgm->forecast_date}.<br/>";
            }
        }

        foreach($email_array as $key => $value) {
            send_email($key, $value);
        }
    }

    function send_email($to, $extratext){
        $today_date = date("d-m-Y");
        $subject = "Smart Winery Risk Notification: {$today_date}";
        $headers = array('Content-Type: text/html; charset=UTF-8');

        $footer = <<<EMAILFOOTER
    <br />
    Visit <a href="http://www.smartwinery.tk/">www.smartwinery.tk</a> to find more about <a href="http://54.206.122.93/wordpress/index.php/testing-amcharts/">
    Disease Analysis </a> and <a href="http://54.206.122.93/wordpress/index.php/diseases-management-draft-page/"> Disease Management</a>
        
    <h4>If you no longer wish to receive this type of email, you may unsubscribe <a href="http://13.54.13.233/wordpress/index.php/unsubscribe-user-2/">here</a></h4>
EMAILFOOTER;
        $body = $extratext . $footer;
    //    wp_mail($to, $subject, $body, $headers);
        wp_mail($to, $subject, $body, $headers);
        echo "<p>Email sent</p>";
    }

    ?>