<?php
/*
Plugin Name: Smartwinery Risk Visualisation
Plugin URI: http://www.smartwinery.tk
Description: Plugin for risk status
Author: Linus Teo and Tanya Arora
Version: 4.0
Author URI:
*/


add_action( 'admin_menu', 'sw_risk_visualisation' );


function sw_risk_visualisation() {
    //add_options_page( $page_title, $menu_title, $capability, $menu_slug, $function);
    add_options_page( 'SW Risk Analysis', 'SW Risk Analysis', 'manage_options', 'SW Risk Analysis', 'sw_risk_status' );
}

add_shortcode( "sw_risk_visualisation", "sw_risk_dots" );

/**
 *  Function to get user meta single value defined field
 *  e.g. get_current_user_meta('region') if such a custom field exists
 * @Returns field value or empty if not found
 */
function get_current_user_meta( $field ) {
    $current_user = wp_get_current_user();
    $field_value  = get_user_meta( $current_user->ID, 'region', true );

    return $field_value;
}


function sw_risk_dots() {
    ob_start();
    $region = get_current_user_meta( 'region' );

    echo <<<STYLE
        <style type="text/css">
            div.bubble{
                display: block;
                white-space: nowrap;
                border-radius: 50%;
                z-index: 1;
            }   
            
            a.tooltip{
                position: relative;
                text-decoration: none;
                color: blue;
                opacity: 1;
                z-index: 1;
            }
            
            a.tooltip span{
                display: none;
            }
            
            a.tooltip:hover span {
                position: absolute;
                top: 40px;
                display: block;
                border-radius: 5%;
                color: #fff;
                background-color: #534190;
                border: 1px solid black;
                padding: 5px;
                z-index: 2;
            }
            
            .VLOW{ 
                width: 25px;
                height: 25px;
                background: lightgreen;
            }
            .LOW{ 
                width: 50px;
                height: 50px;
                background: gold;
            }
            .MEDIUM{ 
                width: 75px;
                height: 75px;
                background: orange;
            }
            .HIGH{ 
                width: 100px;
                height: 100px;
                background: red;
            }
            .legend{
                display: block;
                width: 50px;
                height: 50px;
                margin-right:20px;
                list-style: none;
            }
            
            /* basic positioning */
            .legend li { float: left; margin-right: 10px; white-space: nowrap;}
            .legend span { border: 1px solid #ccc; float: left; width: 12px; height: 12px; margin: 2px; }
            /* your colors */
            .legend .VLOW { background-color: lightgreen; }
            .legend .LOW { background-color: gold; }
            .legend .MEDIUM { background-color: orange; }
            .legend .HIGH { background-color: red; }
    
        </style>
STYLE;

    if ( isset( $_POST['submit_btn'] ) ) {
        if ( isset( $_POST['area_location'] ) and isset( $_POST['disease'] ) ) {
            $area_location = $_POST['area_location'];
            $disease_name  = $_POST['disease'];
        } else {
            $area_location = '';
            $disease_name  = '';
        }
    } else {
        $area_location = '';
        $disease_name  = '';
    }

    //dont display form

    try {
        $weatherdb = new wpdb( 'max4monash', 'max4monash', 'weatherdb', 'max4dbinstance.c2a829wujtim.ap-southeast-2.rds.amazonaws.com' );
    } catch ( Exception $e ) {
        echo "<p>Error Connecting</p>";
    }
    if ( is_user_logged_in() && $region ) {
        //render region specific data
        $area_location = $region;

        draw_disease_visualisation( 'Downy Mildew', $area_location, $weatherdb );
        draw_disease_visualisation( 'Powdery Mildew', $area_location, $weatherdb );
        draw_disease_visualisation( 'Grey Mould', $area_location, $weatherdb );
        draw_risk_legend();
    } else {
        render_region_disease_selector();
        if ( $area_location === "" and $disease_name === "" ) {
            echo "<h3>No region or disease selected.</h3>";
        } else {
            draw_disease_visualisation( $disease_name, $area_location, $weatherdb );
            draw_risk_legend();
        }
    }
    echo "<br/>";
    $output = ob_get_clean();

    return $output;
}

?>


<?php
function render_region_disease_selector() {
    ?>

    <form method="post" action="#">
        <h5 class="title-bg">Analyse My Area </h5>

        <hr/>

        <span>
                <select name="area_location" id="regionddl"" >
                    <option selected disabled> Choose region </option>
                    <option value="Yarra Glen"> Yarra Glen </option>
                    <option value="Ballarat"> Ballarat</option>
                    <option value="Geelong"> Geelong</option>
                    <option value="Stawell"> Stawell</option>
            </select >

                </span>
        <span>

                <select name="disease" id="diseasetypeddl">
                    <option selected disabled> Choose disease </option>
                    <option value="Downy Mildew"> Downy Mildew </option>
                    <option value="Powdery Mildew"> Powdery Mildew </option>
                    <option value="Grey Mould"> Grey Mould </option>
                </select>

                </span>

        <input style="background-color: #4d4d4d; color: #f2f2f2; width:20em;" type="submit" name="submit_btn"
               value="View Disease Risk"><br/>

    </form>

    <br/>
    <?php
}

?>

<?php
function draw_disease_visualisation( $disease_name, $area_location, $weatherdb ) {
    $sqlbegin = "SELECT date_format(str_to_date(forecast_date, '%Y-%m-%d'), '%W') as forecast_date,
                       rain_range_min, rain_range_max, CAST(rain_chance*100 as UNSIGNED INT) as rain_chance,
                       air_temp_min, air_temp_max, ";

    $sqlmiddle = '';
    switch ( $disease_name ) {
        case "Downy Mildew":
            $sqlmiddle = "downy_mildew_risk AS risk";
            break;
        case "Powdery Mildew":
            $sqlmiddle = "powdery_mildew_risk AS risk";
            break;
        case "Grey Mould":
            $sqlmiddle = "grey_mould_risk AS risk";
            break;
    }

    $sqlend = " FROM vic_disease_analysis WHERE area_location='$area_location'";

    $sql    = $sqlbegin . $sqlmiddle . $sqlend;
    $result = $weatherdb->get_results( $sql );
    echo "<h3>Does {$area_location} have <i>{$disease_name}</i>?</h3>";
    ?>
    <table>
        <tr>
            <th>Date</th>
            <?php
            if ( is_array( $result ) || is_object( $result ) ) {
                foreach ( $result as $row ) {
                    echo "<th>{$row->forecast_date}</th>";
                }
            }
            echo "</tr>";
            echo "<tr><td>Risk Level</td>";

            if ( is_array( $result ) || is_object( $result ) ) {
                foreach ( $result as $row ) {
                    echo "<td>";
                    echo "<a href='#' class='tooltip'>";
                    echo "<div class='bubble {$row->risk}'>";

                    echo "<span>";
                    echo "Risk: {$row->risk}</br>";
                    echo "Minimum rain: {$row->rain_range_min}mm</br>";
                    echo "Maximum rain: {$row->rain_range_max}mm</br>";
                    echo "Chance of rain: {$row->rain_chance}%</br>";
                    echo "Minimum temperature: {$row->air_temp_min}°C</br>";
                    echo "Maximum temperature: {$row->air_temp_max}°C";
                    echo "</span>";
                    echo "</div>";
                    echo "</a>";
                    echo "</td>";
                }
            }
            ?>
    </table>
    <?php
}

?>

<?php
function draw_risk_legend() {
    ?>
    <ul class="legend">
        <li><span class="VLOW"></span>VLOW - Very Low risk</li>
        <li><span class="LOW"></span>LOW - Low Risk</li>
        <li><span class="MEDIUM"></span>MEDIUM - Medium Risk</li>
        <li><span class="HIGH"></span> HIGH - High Risk</li>
    </ul>
    <?php
}

?>