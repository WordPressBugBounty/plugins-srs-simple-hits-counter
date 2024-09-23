<?php
/**
Plugin Name: SRS Simple hits Counter
Plugin URI: http://sandyrig.com/srs-simple-hits-counter/
Description: Simple plugin to count and show a total number of hits (Unique visitors or page-views) to the site without using any third party code.
Author: Atif N
Version: 1.1.1
Author URI: https://atif.rocks
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

//create table
register_activation_hook(__FILE__, 'srs_hits_counter_installNewTables');
function srs_hits_counter_installNewTables() {
    global $wpdb;
    $tableName = $wpdb->prefix . "srs_simple_hits_counter";

    $sqlCmd = "CREATE TABLE IF NOT EXISTS " . $tableName . "(
	srs_id mediumint(9) UNSIGNED AUTO_INCREMENT NOT NULL,
	srs_date date,
	srs_time time,
	srs_post_id mediumint(9),
	srs_visitors_count mediumint(9),
	srs_views_count mediumint(9),
	PRIMARY KEY (srs_id)
	)DEFAULT CHARSET=utf8;";
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sqlCmd);
}
register_activation_hook(__FILE__, 'srs_get_previous_visitors_views');
function srs_get_previous_visitors_views(){
    if(get_option('migrated_to_version') != 1){
        $srs_pageViews_count = get_option('srs_pageViews_count');
        $srs_visitors_count = get_option('srs_visitors_count');
        srs_reset_views_visitors(0, $srs_visitors_count, $srs_pageViews_count);
        update_option('migrated_to_version', '1' );
        update_option('srs_update_ran' ,1);
    }
}

// MIGRATION
add_action('wp_head', 'srs_plugin_data_migration');
add_action('admin_head', 'srs_plugin_data_migration');
function srs_plugin_data_migration(){
    if(get_option('srs_simple_hits_counter_version')!='1.0.2'){
        srs_hits_counter_installNewTables();
        srs_get_previous_visitors_views();
        update_option('srs_simple_hits_counter_version', '1.0.2');
        update_option('srs_update_ran', intval(get_option('srs_update_ran'))+1 );
    }
}


// ENQUEUE SCRIPTS
add_action('wp_footer','srs_simple_hits_counter_js');
function srs_simple_hits_counter_js() { ?>
    <script type="text/javascript">
        var templateUrl = '<?php echo get_site_url(); ?>';
        var post_id = '<?php echo get_the_ID(); ?>';
    </script>
    <?php  wp_enqueue_script( 'srs_simple_hits_counter_js', plugins_url( '/js/srs_simple_hits_counter_js.js', __FILE__ ), array('jquery'), '', true);
}

// UPDATE COUNTER
add_action('wp_ajax_srs_update_counter','srs_simple_hits_counter');
add_action('wp_ajax_nopriv_srs_update_counter','srs_simple_hits_counter');
function srs_simple_hits_counter(){
    $post_id = sanitize_text_field($_GET['post_id']);
    $visitors = $views = 0;

    if(!isset($_COOKIE['srs_unique_visitor'])){
        setcookie("srs_unique_visitor", "1", 0 ,'/', parse_url(site_url(), PHP_URL_HOST));
        $visitors = 1;
    }

    $views = 1;
    srs_update_views_visitors($post_id, $visitors, $views);
}
function srs_update_views_visitors($post_id, $visitors, $views){
    global $wpdb;
    $table_name = $wpdb->prefix.'srs_simple_hits_counter';
    $date = Date("Y-m-d");
    $time = Date("h:i:s");
    $post_data = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name WHERE (srs_post_id = %d AND srs_date = %s )", $post_id, $date));
    $visitors = $post_data[0]->srs_visitors_count+$visitors;
    $views = $post_data[0]->srs_views_count+$views;
    if($post_data){
        $wpdb->update($table_name, array('srs_visitors_count' => $visitors, 'srs_views_count' => $views), array('srs_post_id' => $post_id, 'srs_date' => "$date"));
    }else{
        $sql = "INSERT INTO $table_name (`srs_id`, `srs_date`, `srs_time`, `srs_post_id`, `srs_visitors_count`, `srs_views_count`) VALUES (NULL, '".$date."', '".$time."', $post_id, $visitors, $views)";
        $wpdb->insert(
            $table_name,
            array(
                'srs_id' => NULL,
                'srs_date' => $date,
                'srs_time' => $time,
                'srs_post_id' => $post_id,
                'srs_visitors_count' =>$visitors,
                'srs_views_count' => $views
            )
        );
    }
}
function srs_reset_views_visitors($post_id, $visitors, $views){
    global $wpdb;
    $table_name = $wpdb->prefix.'srs_simple_hits_counter';
    $date = Date("Y-m-d");
    $time = Date("h:i:s");
    $post_data = $wpdb->get_results("SELECT * FROM $table_name WHERE srs_post_id = $post_id");
    if($post_data){
        if($visitors == 'null'){
            $visitors = $post_data[0]->srs_visitors_count;
        }
        if($views == 'null'){
            $views = $post_data[0]->srs_views_count;
        }
        $wpdb->update($table_name, array('srs_visitors_count' => $visitors, 'srs_views_count' => $views), array('srs_post_id' => $post_id));
    }else{
        if($visitors == 'null'){
            $visitors = 0;
        }
        if($views == 'null'){
            $views == 0;
        }
        $wpdb->insert(
            $table_name,
            array(
                'srs_id' => NULL,
                'srs_date' => $date,
                'srs_time' => $time,
                'srs_post_id' => $post_id,
                'srs_visitors_count' =>$visitors,
                'srs_views_count' => $views
            )
        );
    }
}
function srs_count_total_visitors_views($return){
    global $wpdb;
    $table_name = $wpdb->prefix . 'srs_simple_hits_counter';
    if ($return == 'views') {
        return $wpdb->get_results("SELECT SUM(srs_views_count) as total FROM $table_name ")[0];
    } else {
        return $wpdb->get_results("SELECT SUM(srs_visitors_count) as total FROM $table_name ")[0];
    }
}

// SHORTCODE
add_shortcode('srs_total_pageViews', 'srs_getTotal_pageViews');
function srs_getTotal_pageViews(){
    $data_return_views = srs_count_total_visitors_views('views');
    if(get_option('srs_pageViews_number_format_count') == 'yes'){
        return "<span class='page-views'>".number_format(intval($data_return_views->total))."</span>";
    }else{
        return "<span class='page-views'>".intval($data_return_views->total)."</span>";
    }
}
add_shortcode('srs_total_visitors', 'srs_getTotal_visitors');
function srs_getTotal_visitors(){
    $data_return_visitors = srs_count_total_visitors_views('visitors');
    if(get_option('srs_pageViews_number_format_count') == 'yes'){
        return "<span class='visitors'>".number_format(intval($data_return_visitors->total))."</span>";
    }else{
        return "<span class='visitors'>".intval($data_return_visitors->total)."</span>";
    }
}


// WIDGET
add_action( 'widgets_init', 'srs_shc_register_widget' );
function srs_shc_register_widget() {
    register_widget( 'SRS_SHC_Widget' );
}
class SRS_SHC_Widget extends WP_Widget {

    function __construct() {
        parent::__construct(
            'srs_shc_widget', // Base ID
            __( 'SRS Simple Hits Counter', 'text_domain' ), // Name
            array( 'description' => __( 'Add this widget to the sidebar or any other widget area available on your theme where you would like to display the Total Hits Count for your whole site.', 'text_domain' ), ) // Args
        );
    }

    public function widget( $args, $instance ) {
        echo $args['before_widget'];
        if ( ! empty( $instance['title'] ) ) {
            echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ). $args['after_title'];
        }
        if( $instance['type']=='visitors' ){
            $data_return_visitors = srs_count_total_visitors_views('visitors');
            if(get_option('srs_pageViews_number_format_count') == 'yes'){
                $srs_total_visitors =  number_format(intval( $data_return_visitors->total ));
            }else{
                $srs_total_visitors =  intval( $data_return_visitors->total );
            }
            echo "<span class='visitors'>" . __( $srs_total_visitors, 'text_domain' ) . "</span>";
        }elseif( $instance['type']=='pageviews' ){
            $data_return_views = srs_count_total_visitors_views('views');
            if(get_option('srs_pageViews_number_format_count') == 'yes'){
                $srs_total_pageViews =  number_format(intval( $data_return_views->total ));
            }else{
                $srs_total_pageViews =  intval( $data_return_views->total );
            }
            echo "<span class='page-views'>" . __( $srs_total_pageViews, 'text_domain' ) . "</span>";
        }

        echo $args['after_widget'];
    }

    public function form( $instance ) {
        $title = ! empty( $instance['title'] ) ? $instance['title'] : __( 'New title', 'text_domain' );
        $visitors_count = ! empty( $instance['visitors_count'] ) ? $instance['visitors_count'] : __( '00000', 'text_domain' );
        $pageViews_count = ! empty( $instance['pageViews_count'] ) ? $instance['pageViews_count'] : __( '00000', 'text_domain' );
        $type = ! empty( $instance['type'] ) ? $instance['type'] : __( 'visitors', 'text_domain' );
        ?>
        <p>
            <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
        </p>

        <p>
            <label for="<?php echo $this->get_field_id( 'type' ); ?>"><?php _e( 'Counter Type:' ); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id( 'type' ); ?>" name="<?php echo $this->get_field_name( 'type' ); ?>" type="radio" value="visitors" <?php echo esc_attr( $type )=='visitors'?'checked':'' ; ?>>Visitors
            <input class="widefat" id="<?php echo $this->get_field_id( 'type' ); ?>" name="<?php echo $this->get_field_name( 'type' ); ?>" type="radio" value="pageviews" <?php echo esc_attr( $type )=='pageviews'?'checked':'' ; ?>>Page Views
        </p>

        <p>
            Reset options have been moved to the options page under the settings menu.
        </p>
        <?php
    }

    public function update( $new_instance, $old_instance ) {
        $instance = array();

        $instance['title'] = ( ! empty( $new_instance['title'] ) ) ? sanitize_text_field( $new_instance['title'] ) : '';
        $instance['type'] = ( ! empty( $new_instance['type'] ) ) ? sanitize_text_field( $new_instance['type'] ) : '';
        $instance['visitors_count_reset_check'] = ( ! empty( $new_instance['visitors_count_reset_check'] ) ) ? sanitize_text_field( $new_instance['visitors_count_reset_check'] ) : '';
        $instance['pageViews_count_reset_check'] = ( ! empty( $new_instance['pageViews_count_reset_check'] ) ) ? sanitize_text_field( $new_instance['pageViews_count_reset_check'] ) : '';

        return $instance;
    }
}

// Register and load Admin menus
add_action('admin_menu', 'srs_add_menu_function_call' );
function srs_add_menu_function_call(){
    $settings_page  = add_menu_page( 'SRS Simple Hits Counter Dashboard', 'Simple Counter', 'administrator', 'srs-simple-hits-counter', 'srs_hits_counter_graphs' );
    $page           = add_submenu_page( 'srs-simple-hits-counter', 'Dashboard', 'Dashboard', 'administrator', 'srs-simple-hits-counter', 'srs_hits_counter_graphs' );
    $page           = add_submenu_page( 'srs-simple-hits-counter', 'SRS Hits Counter Settings', 'Settings', 'administrator',  'srs-simple-hits-counter-settings','srs_admin_settings_page');
}

//generate graph and load js libraries  for graph
function srs_hits_counter_graphs(){
    global $wpdb;
    //load libraries for graph
    wp_enqueue_script( 'srs_hits_counter_Chart_bundle_js', plugins_url( '/js/Chart.bundle.min.js', __FILE__ ), array('jquery'), '', true);
    wp_enqueue_script( 'srs_hits_counter_Chart_js', plugins_url( '/js/Chart.min.js', __FILE__ ), array('jquery'), '', true);
    //check if user select last month or last week option   .. default to last week
    $dates_range = array();
    $begin = new DateTime();
    if(isset($_GET['range_filter']) && sanitize_text_field($_GET['range_filter']) == 'month'){

        $begin->sub(new DateInterval('P29D'));
        $end = new DateTime();
        $end->add(new DateInterval('P1D'));
        $interval = DateInterval::createFromDateString('1 day');
        $period = new DatePeriod($begin, $interval, $end);
        foreach ( $period as $dt ){
            $dates_range[] = $dt->format( "Y-m-d" );
        }
    }else{
        $begin->sub(new DateInterval('P6D'));
        $end = new DateTime();
        $end->add(new DateInterval('P1D'));
        $interval = DateInterval::createFromDateString('1 day');
        $period = new DatePeriod($begin, $interval, $end);
        foreach ( $period as $dt ){
            $dates_range[] = $dt->format( "Y-m-d" );
        }
    }
    $table_name = $wpdb->prefix . 'srs_simple_hits_counter';

    //retrieve data from database
    $post_data = $wpdb->get_results("SELECT srs_id, srs_date, srs_time, srs_post_id,  sum(srs_visitors_count) srs_visitors_count, SUM(srs_views_count) srs_views_count FROM $table_name WHERE srs_date >= '".$begin->format( "Y-m-d" )."' GROUP BY srs_date ORDER BY srs_date DESC ");
    ?>
    <!--- generating html for filters -->
    <div class="filter" style="margin-top: 30px; width: 50%;">
        <form action="" method="get" class="">
            <select name="range_filter" style="width: 20%">
                <option value="week"> Last Week</option>
                <option value="month" <?php if(isset($_GET['range_filter']) && sanitize_text_field($_GET['range_filter']) == 'month'){ echo "selected"; } ?>>Last Month</option>
            </select>
            <input type="hidden" name="page" value="<?php echo esc_attr($_GET['page']) ?>">
            <input type="submit" value="Apply" style="width: 10%" class="button button-primary">
        </form>

    </div>
    <!-- Add canvas in which we will show graph   -->
    <canvas id="srs_visitors_views_charts" style="width: 75% !important;"></canvas>
    <script>
        //javascript code for graph
        var visitors = [];
        var views = [];
        var date_labels = [];
        var counter = 0;
        <?php
        $srs_visitors_count = 0;
        $srs_views_count = 0;
        $srs_date = 0;
        //$dates_range = array_reverse($dates_range);
        foreach ($dates_range as $date){
        $srs_date = date("F j, Y", strtotime($date));
        foreach($post_data as $post){
            if(date("Y-m-d", strtotime($post->srs_date)) == $date){
                $srs_visitors_count = $post->srs_visitors_count;
                $srs_views_count = $post->srs_views_count;
            }
        }
        ?>
        visitors[counter] = '<?php echo $srs_visitors_count; ?>';
        views[counter]  = '<?php echo $srs_views_count; ?>';
        date_labels[counter] = '<?php echo $srs_date; ?>';
        counter ++;
        <?php
        $srs_visitors_count = 0;
        $srs_views_count = 0;
        }
        ?>
        window.chartColors = {
            red: 'rgb(255, 99, 132)',
            orange: 'rgb(255, 159, 64)',
            yellow: 'rgb(255, 205, 86)',
            green: 'rgb(75, 192, 192)',
            blue: 'rgb(54, 162, 235)',
            purple: 'rgb(153, 102, 255)',
            grey: 'rgb(201, 203, 207)'
        };
        var config = {
            type: 'line',
            data: {
                labels: date_labels,
                datasets: [{
                    label: "Visitors",
                    backgroundColor: window.chartColors.red,
                    borderColor: window.chartColors.red,
                    data: visitors,
                    fill: false,
                }, {
                    label: "Views",
                    fill: false,
                    backgroundColor: window.chartColors.blue,
                    borderColor: window.chartColors.blue,
                    data: views,
                }]
            },
            options: {
                responsive: true,
                title:{
                    display:true,
                    text:'Graph'
                },
                tooltips: {
                    mode: 'index',
                    intersect: false,
                },
                hover: {
                    mode: 'nearest',
                    intersect: true
                },
                scales: {
                    xAxes: [{
                        display: true,
                        scaleLabel: {
                            display: true,
                            labelString: 'Month'
                        }
                    }],
                    yAxes: [{
                        display: true,
                        scaleLabel: {
                            display: true,
                            labelString: 'Value'
                        }
                    }]
                }
            }
        };
        window.onload = function() {
            var ctx = document.getElementById("srs_visitors_views_charts").getContext("2d");
            window.myLine = new Chart(ctx, config);
        };
    </script>
    <?php
}

function srs_admin_settings_page(){
    global $wpdb;
    $table_name = $wpdb->prefix.'srs_simple_hits_counter';
    echo '<h1>SRS Simple Hits Counter</h1>';

    if( isset($_POST['srs_shc_options_save']) && wp_verify_nonce($_POST['srs-form-nonce'], 'srs-form-9171') ){
        // Reset Unique Visitor Counter
        if( $_POST['unique_visitor_reset_val']!='' && $_POST['unique_visitor_checkbox'] == "yes" ){
            $sql = "UPDATE $table_name SET `srs_visitors_count` = '0'";
            $wpdb->query($sql);
            $views = 'null';
            if( $_POST['page_views_reset_val']!='' &&$_POST['page_views_checkbox'] == "yes" ){
                $views = sanitize_text_field($_POST['page_views_reset_val']);
            }
            srs_reset_views_visitors(0, sanitize_text_field($_POST['unique_visitor_reset_val']), $views);
        }

        // Reset Page Views Counter
        if( $_POST['page_views_reset_val']!='' &&$_POST['page_views_checkbox'] == "yes" ){
            $visitors = 'null';
            if( $_POST['unique_visitor_reset_val']!='' && $_POST['unique_visitor_checkbox'] == "yes" ){
                $visitors = sanitize_text_field($_POST['unique_visitor_reset_val']);
            }
            $sql = "UPDATE $table_name SET `srs_views_count` = '0' ";
            $wpdb->query($sql);
            srs_reset_views_visitors(0, $visitors, sanitize_text_field($_POST['page_views_reset_val']));
        }

        // Change number format
        if($_POST['page_views_number_format_checkbox']!='' && $_POST['page_views_number_format_checkbox'] == 'yes'){
            update_option('srs_pageViews_number_format_count', 'yes' );
        }else{
            update_option('srs_pageViews_number_format_count', 'no' );
        }

        // Reset plugin data
        if($_POST['reset_data']!='' && $_POST['reset_data'] == 'yes'){
            $wpdb->query("TRUNCATE $table_name");
        }
    }
    $data_return_visitors = srs_count_total_visitors_views('visitors');
    $data_return_views = srs_count_total_visitors_views('views');
    $srs_shc_unique_visitors_count = $data_return_visitors->total;
    $srs_shc_page_views_count = $data_return_views->total;
    $page_views_number_format_checkbox = get_option('srs_pageViews_number_format_count');
    ?>
    <div class="metabox-holder">
        <div class="postbox">
            <h3 class="hndle">
                <span>Short Codes</span>
            </h3><?php //print_r($_POST) ?>
            <div class="inside">
                <div class="main">
                    <table class="form-table">
                        <tbody>
                        <tr>
                            <th>Unique Visitors </th>
                            <td>
                                [srs_total_visitors]
                            </td>
                        </tr>
                        <tr>
                            <th>Page Views</th>
                            <td>
                                [srs_total_pageViews]
                            </td>
                        </tr>
                        </tbody>
                    </table>

                </div>
            </div>
        </div>
    </div>
    <form method="post" action="">

        <div class="metabox-holder">
            <div class="postbox">
                <h3 class="hndle">
                    <span>Reset Counters</span>
                </h3><?php //print_r($_POST) ?>
                <div class="inside">
                    <div class="main">

                        <p>Textfields below show the current counter values. To reset the counter, change the value and tick the checkbox below the textfield to verify that you really want to reset that counter. </p>
                        <table class="form-table">
                            <tbody>
                            <tr>
                                <th>Unique Visitors:</th>
                                <td>
                                    <input type="text" name="unique_visitor_reset_val" placeholder="00000" value="<?php echo $srs_shc_unique_visitors_count ?>">
                                    <br><span class="description">Are you sure you want to reset 'Unique Visitors Counter'? <input type="checkbox" name="unique_visitor_checkbox" value="yes"></span>
                                </td>
                            </tr>
                            <tr>
                                <th>Page Views:</th>
                                <td>
                                    <input type="text" name="page_views_reset_val" placeholder="00000" value="<?php echo $srs_shc_page_views_count ?>">
                                    <br><span class="description">Are you sure you want to reset 'Page Views Counter'? <input type="checkbox" name="page_views_checkbox" value="yes"></span>
                                </td>
                            </tr>
                            </tbody>
                        </table>

                    </div>
                </div>
            </div>
        </div>

        <div class="metabox-holder">
            <div class="postbox">
                <h3 class="hndle">
                    <span>Formatting</span>
                </h3><?php //print_r($_POST) ?>
                <div class="inside">
                    <div class="main">
                        <table class="form-table">
                            <tbody>
                            <tr>
                                <th>Add Commas:</th>
                                <td>
                                    <span class="description">Yes? <input type="checkbox" name="page_views_number_format_checkbox" value="yes" <?php if(isset($page_views_number_format_checkbox) && $page_views_number_format_checkbox == 'yes' ){ echo "checked"; } ?> > Example:(10,000,000) </span>
                                </td>
                            </tr>
                            </tbody>
                        </table>

                    </div>
                </div>
            </div>
        </div>

        <div class="metabox-holder">
            <div class="postbox">
                <h3 class="hndle">
                    <span>Reset Plugin</span>
                </h3>
                <div class="inside">
                    <div class="main">
                        <table class="form-table">
                            <tbody>
                            <tr>
                                <th>Reset:</th>
                                <td>
                                    <span class="description">Yes? <input type="checkbox" name="reset_data" value="yes" > Deletes all the existing plugin data and starts fresh </span>
                                </td>
                            </tr>
                            </tbody>
                        </table>

                    </div>
                </div>
            </div>
        </div>

        <?php $nonce = wp_create_nonce('srs-form-9171') ?>
        <input type="hidden" name="srs-form-nonce" value="<?php echo $nonce ?>" />
        <p class="submit">
            <input type="submit" name="srs_shc_options_save" class="button-primary" value="Save settings">
        </p>
    </form>
    <?php
}