<?php
/**
Plugin Name: SRS Simple hits Counter
Plugin URI: https://atif.rocks/srs-simple-hits-counter/
Description: This is a simple plugin to count and show a total number of hits (Unique visitors or page-views) to your WordPress website without using any third party code.
Author: Atif Rocks
Version: 2.2.1
Author URI: https://atif.rocks/
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
        srs_post_id INT UNSIGNED,
        srs_visitors_count INT UNSIGNED,
        srs_views_count INT UNSIGNED,
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

// Register default style options on activation
register_activation_hook(__FILE__, 'srs_register_style_defaults');
function srs_register_style_defaults() {
    $defaults = array(
        'counter_type'  => 'simple',
        'font_family'   => 'inherit',
        'font_size'     => '16',
        'color'         => '#000000',
        'background'    => '#ffffff',
        'padding'       => '0',
        'border_radius' => '0',
        'bold'          => 'no',
        'italic'        => 'no',
        'text_shadow'   => 'no',
        'text_align'    => 'left',
    );
    add_option('srs_counter_styles', $defaults);
}

// MIGRATION
add_action('init', 'srs_plugin_data_migration');
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
    <?php wp_enqueue_script( 'srs_simple_hits_counter_js', plugins_url( '/js/srs_simple_hits_counter_js.js', __FILE__ ), array('jquery'), '', true);
}

// Enqueue Google Font on frontend if a Google font is selected, and Roboto Mono for Meter type
add_action('wp_head', 'srs_maybe_enqueue_google_font');
function srs_maybe_enqueue_google_font() {
    $s = get_option('srs_counter_styles', array());
    $google_fonts = array('Roboto', 'Open Sans', 'Lato', 'Montserrat', 'Playfair Display');

    if (!empty($s['font_family']) && in_array($s['font_family'], $google_fonts)) {
        $font_url = 'https://fonts.googleapis.com/css2?family=' . urlencode($s['font_family']) . '&display=swap';
        wp_enqueue_style('srs-google-font', $font_url);
    }

    // Always load Roboto Mono for the Meter counter type
    if (!empty($s['counter_type']) && $s['counter_type'] === 'meter') {
        wp_enqueue_style('srs-roboto-mono', 'https://fonts.googleapis.com/css2?family=Roboto+Mono:wght@700&display=swap');
    }
}

// Build inline style string from saved options (used by Simple type)
function srs_get_counter_inline_style() {
    $s = get_option('srs_counter_styles', array());
    if (empty($s)) return '';

    $style  = 'font-family:'   . esc_attr($s['font_family'])   . ';';
    $style .= 'font-size:'     . intval($s['font_size'])        . 'px;';
    $style .= 'color:'         . esc_attr($s['color'])          . ';';
    
    // Handle transparent background
    if (!empty($s['background']) && $s['background'] !== 'transparent') {
        $style .= 'background:'    . esc_attr($s['background'])     . ';';
    } else {
        $style .= 'background:transparent;';
    }
    
    $style .= 'padding:'       . intval($s['padding'])          . 'px;';
    $style .= 'border-radius:' . intval($s['border_radius'])    . 'px;';
    $style .= 'font-weight:'   . ($s['bold']        === 'yes' ? 'bold'                       : 'normal') . ';';
    $style .= 'font-style:'    . ($s['italic']      === 'yes' ? 'italic'                     : 'normal') . ';';
    $style .= 'text-shadow:'   . ($s['text_shadow'] === 'yes' ? '1px 1px 3px rgba(0,0,0,0.4)' : 'none') . ';';

    return $style;
}

// Build the Meter-style counter HTML — individual digit tiles
function srs_render_meter_counter($number, $css_class) {
    $s      = get_option('srs_counter_styles', array());
    $digits = str_split((string) intval($number));

    $digit_color = !empty($s['color'])         ? esc_attr($s['color'])         : '#f0ede8';
    $tile_bg     = !empty($s['background']) && $s['background'] !== 'transparent'
        ? esc_attr($s['background'])
        : '#1a1a1a';
    $br          = !empty($s['border_radius']) ? intval($s['border_radius'])   : 6;
    $font_weight = (!empty($s['bold'])   && $s['bold']   === 'yes') ? '700'    : '700';
    $font_style  = (!empty($s['italic']) && $s['italic'] === 'yes') ? 'italic' : 'normal';
    $text_shadow = (!empty($s['text_shadow']) && $s['text_shadow'] === 'yes') ? '1px 1px 3px rgba(0,0,0,0.4)' : 'none';
    $text_align  = in_array($s['text_align'] ?? '', array('left','center','right')) ? $s['text_align'] : 'left';

    $outer_style = 'display:block;width:100%;text-align:' . $text_align . ';';
    $wrap_style  = 'display:inline-flex;align-items:center;justify-content:center;gap:6px;';

    $digit_style = sprintf(
        'width:28px;height:32px;background:%s;border-radius:%dpx;display:inline-flex;align-items:center;justify-content:center;',
        $tile_bg, $br
    );

    $span_style = sprintf(
        'font-family:Roboto Mono,monospace;font-size:1rem;font-weight:%s;font-style:%s;color:%s;line-height:1;text-shadow:%s;',
        $font_weight, $font_style, $digit_color, $text_shadow
    );

    $html = '<div style="' . $outer_style . '"><div class="' . esc_attr($css_class) . ' srs-meter-counter" style="' . $wrap_style . '">';
    foreach ($digits as $d) {
        $html .= '<div class="srs-meter-digit" style="' . $digit_style . '">';
        $html .= '<span style="' . $span_style . '">' . esc_html($d) . '</span>';
        $html .= '</div>';
    }
    $html .= '</div></div>';

    return $html;
}

// UPDATE COUNTER
add_action('wp_ajax_srs_update_counter','srs_simple_hits_counter');
add_action('wp_ajax_nopriv_srs_update_counter','srs_simple_hits_counter');
function srs_simple_hits_counter(){
    $post_id = absint($_GET['post_id']);
    $visitors = $views = 0;

    if(!isset($_COOKIE['srs_unique_visitor'])){
        setcookie("srs_unique_visitor", "1", 0 ,'/', parse_url(site_url(), PHP_URL_HOST));
        $visitors = 1;
    }

    $views = 1;
    srs_update_views_visitors($post_id, $visitors, $views);
    wp_die();
}

function srs_update_views_visitors($post_id, $visitors, $views){
    global $wpdb;
    $table_name = $wpdb->prefix.'srs_simple_hits_counter';
    $date = date("Y-m-d");
    $time = date("h:i:s");
    $post_data = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name WHERE (srs_post_id = %d AND srs_date = %s )", $post_id, $date));
    if ($post_data){
        $visitors = $post_data[0]->srs_visitors_count+$visitors;
        $views = $post_data[0]->srs_views_count+$views;
    }

    if($post_data){
        $wpdb->update($table_name, array('srs_visitors_count' => $visitors, 'srs_views_count' => $views), array('srs_post_id' => $post_id, 'srs_date' => "$date"));
    }else{
        $wpdb->insert(
            $table_name,
            array(
                'srs_id' => NULL,
                'srs_date' => $date,
                'srs_time' => $time,
                'srs_post_id' => $post_id,
                'srs_visitors_count' => $visitors,
                'srs_views_count' => $views
            )
        );
    }
}

function srs_reset_views_visitors($post_id, $visitors, $views){
    global $wpdb;
    $table_name = $wpdb->prefix.'srs_simple_hits_counter';
    $date = date("Y-m-d");
    $time = date("h:i:s");
    $post_data = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name WHERE srs_post_id = %d", $post_id));
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
            $views = 0;
        }
        $wpdb->insert(
            $table_name,
            array(
                'srs_id' => NULL,
                'srs_date' => $date,
                'srs_time' => $time,
                'srs_post_id' => $post_id,
                'srs_visitors_count' => $visitors,
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

// SHORTCODES
add_shortcode('srs_total_pageViews', 'srs_getTotal_pageViews');
function srs_getTotal_pageViews(){
    $data  = srs_count_total_visitors_views('views');
    $s     = get_option('srs_counter_styles', array());
    $type  = !empty($s['counter_type']) ? $s['counter_type'] : 'simple';
    $count = get_option('srs_pageViews_number_format_count') == 'yes'
        ? number_format(intval($data->total))
        : intval($data->total);
    if ($type === 'meter') {
        return srs_render_meter_counter(intval($data->total), 'page-views');
    }
    $style    = srs_get_counter_inline_style();
    $ta       = in_array($s['text_align'] ?? '', array('left','center','right')) ? $s['text_align'] : 'left';
    $wrap     = 'display:block;width:100%;text-align:' . $ta . ';';
    return "<div style='" . esc_attr($wrap) . "'><span class='page-views' style='" . esc_attr($style) . "'>" . $count . "</span></div>";
}

add_shortcode('srs_total_visitors', 'srs_getTotal_visitors');
function srs_getTotal_visitors(){
    $data  = srs_count_total_visitors_views('visitors');
    $s     = get_option('srs_counter_styles', array());
    $type  = !empty($s['counter_type']) ? $s['counter_type'] : 'simple';
    $count = get_option('srs_pageViews_number_format_count') == 'yes'
        ? number_format(intval($data->total))
        : intval($data->total);
    if ($type === 'meter') {
        return srs_render_meter_counter(intval($data->total), 'visitors');
    }
    $style    = srs_get_counter_inline_style();
    $ta       = in_array($s['text_align'] ?? '', array('left','center','right')) ? $s['text_align'] : 'left';
    $wrap     = 'display:block;width:100%;text-align:' . $ta . ';';
    return "<div style='" . esc_attr($wrap) . "'><span class='visitors' style='" . esc_attr($style) . "'>" . $count . "</span></div>";
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
        $s     = get_option('srs_counter_styles', array());
        $type  = !empty($s['counter_type']) ? $s['counter_type'] : 'simple';
        $style = srs_get_counter_inline_style();
        $ta    = in_array($s['text_align'] ?? '', array('left','center','right')) ? $s['text_align'] : 'left';
        $wrap  = 'display:block;width:100%;text-align:' . $ta . ';';
        echo $args['before_widget'];
        if ( ! empty( $instance['title'] ) ) {
            echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ) . $args['after_title'];
        }
        if( $instance['type'] == 'visitors' ){
            $data_return_visitors = srs_count_total_visitors_views('visitors');
            if ($type === 'meter') {
                echo srs_render_meter_counter(intval($data_return_visitors->total), 'visitors');
            } else {
                $srs_total_visitors = get_option('srs_pageViews_number_format_count') == 'yes'
                    ? number_format(intval($data_return_visitors->total))
                    : intval($data_return_visitors->total);
                echo "<div style='" . esc_attr($wrap) . "'><span class='visitors' style='" . esc_attr($style) . "'>" . esc_html($srs_total_visitors) . "</span></div>";
            }
        } elseif( $instance['type'] == 'pageviews' ){
            $data_return_views = srs_count_total_visitors_views('views');
            if ($type === 'meter') {
                echo srs_render_meter_counter(intval($data_return_views->total), 'page-views');
            } else {
                $srs_total_pageViews = get_option('srs_pageViews_number_format_count') == 'yes'
                    ? number_format(intval($data_return_views->total))
                    : intval($data_return_views->total);
                echo "<div style='" . esc_attr($wrap) . "'><span class='page-views' style='" . esc_attr($style) . "'>" . esc_html($srs_total_pageViews) . "</span></div>";
            }
        }
        echo $args['after_widget'];
    }

    public function form( $instance ) {
        $title = ! empty( $instance['title'] ) ? $instance['title'] : __( 'New title', 'text_domain' );
        $type  = ! empty( $instance['type'] )  ? $instance['type']  : __( 'visitors', 'text_domain' );
        ?>
        <p>
            <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
        </p>
        <p>
            <label for="<?php echo $this->get_field_id( 'type' ); ?>"><?php _e( 'Counter Type:' ); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id( 'type' ); ?>" name="<?php echo $this->get_field_name( 'type' ); ?>" type="radio" value="visitors" <?php echo esc_attr( $type ) == 'visitors' ? 'checked' : ''; ?>>Visitors
            <input class="widefat" id="<?php echo $this->get_field_id( 'type' ); ?>" name="<?php echo $this->get_field_name( 'type' ); ?>" type="radio" value="pageviews" <?php echo esc_attr( $type ) == 'pageviews' ? 'checked' : ''; ?>>Page Views
        </p>
        <p>Reset options have been moved to the options page under the settings menu.</p>
        <?php
    }

    public function update( $new_instance, $old_instance ) {
        $instance = array();
        $instance['title'] = ( ! empty( $new_instance['title'] ) ) ? sanitize_text_field( $new_instance['title'] ) : '';
        $instance['type']  = ( ! empty( $new_instance['type'] ) )  ? sanitize_text_field( $new_instance['type'] )  : '';
        return $instance;
    }
}

// Register and load Admin menus
add_action('admin_menu', 'srs_add_menu_function_call');
function srs_add_menu_function_call(){
    add_menu_page( 'SRS Simple Hits Counter Dashboard', 'Simple Counter', 'administrator', 'srs-simple-hits-counter', 'srs_hits_counter_graphs' );
    add_submenu_page( 'srs-simple-hits-counter', 'Dashboard', 'Dashboard', 'administrator', 'srs-simple-hits-counter', 'srs_hits_counter_graphs' );
    add_submenu_page( 'srs-simple-hits-counter', 'SRS Hits Counter Settings', 'Settings', 'administrator', 'srs-simple-hits-counter-settings', 'srs_admin_settings_page' );
}

// Generate graph and load JS libraries for graph
function srs_hits_counter_graphs(){
    global $wpdb;

    //load libraries for graph
    wp_enqueue_script( 'srs_hits_counter_Chart_bundle_js', plugins_url( '/js/Chart.bundle.min.js', __FILE__ ), array('jquery'), '', true);
    wp_enqueue_script( 'srs_hits_counter_Chart_js', plugins_url( '/js/Chart.min.js', __FILE__ ), array('jquery'), '', true);
    wp_enqueue_style( 'srs_hits_counter_Chart_css', plugins_url( '/css/style.css', __FILE__ ));

    //check if user select last month or last week option   .. default to last week
    $dates_range = array();
    $begin = new DateTime();
    if(isset($_GET['range_filter']) && sanitize_text_field($_GET['range_filter']) == 'month'){
        $xAxes_lable = "Month";
        $begin->sub(new DateInterval('P29D'));
        $end = new DateTime();
        $end->add(new DateInterval('P1D'));
        $interval = DateInterval::createFromDateString('1 day');
        $period = new DatePeriod($begin, $interval, $end);
        foreach ( $period as $dt ){
            $dates_range[] = $dt->format( "Y-m-d" );
        }
    }else{
        $xAxes_lable = "Week";
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

    //Get data for the graph
    $post_data = $wpdb->get_results("SELECT srs_id, srs_date, srs_time, srs_post_id,  sum(srs_visitors_count) srs_visitors_count, SUM(srs_views_count) srs_views_count FROM $table_name WHERE srs_date >= '".$begin->format( "Y-m-d" )."' GROUP BY srs_date ORDER BY srs_date DESC ");
    $total_count = $wpdb->get_results("SELECT sum(srs_visitors_count) srs_visitors_count, SUM(srs_views_count) srs_views_count FROM $table_name WHERE srs_date >= '".$begin->format( "Y-m-d" )."' ORDER BY srs_date DESC ");
    $lifetime_count = $wpdb->get_results("SELECT sum(srs_visitors_count) srs_visitors_count, SUM(srs_views_count) srs_views_count FROM $table_name ORDER BY srs_date DESC ");
    $popular_pages  = $wpdb->get_results("SELECT srs_id, srs_date, srs_time, srs_post_id, sum(srs_visitors_count) srs_visitors_count, SUM(srs_views_count) srs_views_count FROM $table_name WHERE srs_date >= '".$begin->format("Y-m-d")."' GROUP BY srs_post_id ORDER BY srs_views_count DESC ");
    ?>

    <div class="srs-simple-hits-counter">
        <div class="srs-base">
            <div class="srs-container">
                <!--Header-->
                <div class="srs-header">
                    <div class="srs-header-title">
                        <h2>Simple Hits Counter</h2>
                    </div>
                    <div class="srs-total-stats">
                        <div class="srs-total-title">Lifetime</div>
                        <div class="srs-total-visitors">
                            <div>Visitors:</div>
                            <div><?php echo number_format(intval($lifetime_count[0]->srs_visitors_count)) ?></div>
                        </div>
                        <div class="srs-total-views">
                            <div>Views:</div>
                            <div><?php echo number_format(intval($lifetime_count[0]->srs_views_count)) ?></div>
                        </div>
                    </div>
                </div>

                <!--Filter-->
                <div class="srs-filter srs-row">
                    <form action="" method="get" class="">
                        <select name="range_filter" style="">
                            <option value="week">Week to date</option>
                            <option value="month" <?php if(isset($_GET['range_filter']) && sanitize_text_field($_GET['range_filter']) == 'month'){ echo "selected"; } ?>>Month to date</option>
                        </select>
                        <input type="hidden" name="page" value="<?php echo esc_attr($_GET['page']) ?>">
                        <input type="submit" value="Apply" class="button button-primary">
                    </form>
                </div>

                <!--Stats Row-->
                <div class="srs-row">

                    <!--Stats-->
                    <div class="srs-stats">
                        <div class="srs-stats-visitors">
                            <div class="srs-stats-title">Visitors</div>
                            <div class="srs-stats-count"><?php echo intval($total_count[0]->srs_visitors_count) ?></div>
                        </div>
                        <div class="srs-stats-views">
                            <div class="srs-stats-title">Views</div>
                            <div class="srs-stats-count"><?php echo intval($total_count[0]->srs_views_count) ?></div>
                        </div>
                    </div>

                    <!--Graph-->
                    <div class="srs-graph">
                        <canvas id="srs_visitors_views_charts" width="100" height="35"></canvas>
                    </div>

                    <!--Top pages-->
                    <div class="srs-top-pages">
                        <div class="srs-top-pages-title">
                            <h3>Popular Content</h3>
                            <p>Views</p>
                        </div>
                        <div class="srs-top-pages-container">
                            <?php foreach ($popular_pages as $page){ ?>
                                <div class="top-pages-row">
                                    <div class="top-pages-cols page-id"><?php echo intval($page->srs_post_id); ?></div>
                                    <div class="top-pages-cols page-title"><?php echo "<a target='_blank' href='".esc_url(get_permalink($page->srs_post_id))."'>".esc_html(get_the_title($page->srs_post_id))."</a>"; ?></div>
                                    <div class="top-pages-cols page-views-count"><?php echo intval($page->srs_views_count); ?></div>
                                </div>
                            <?php } ?>
                        </div>
                    </div>

                    <!--Credits-->
                    <div class="srs-credit">
                        <div class="atif-rocks">
                            <p>Simple Hits Counter by <a href="https://atif.rocks/">Atif Rocks</a></p>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

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
                $srs_date = date("M j", strtotime($date));
                foreach($post_data as $post){
                    if(date("Y-m-d", strtotime($post->srs_date)) == $date){
                        $srs_visitors_count = $post->srs_visitors_count;
                        $srs_views_count = $post->srs_views_count;
                    }
                }
                ?>
                visitors[counter] = '<?php echo intval($srs_visitors_count); ?>';
                views[counter]    = '<?php echo intval($srs_views_count); ?>';
                date_labels[counter] = '<?php echo esc_js($srs_date); ?>';
                counter++;
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
                    backgroundColor: window.chartColors.green,
                    borderColor: window.chartColors.green,
                    fill: false,
                    data: visitors,
                }, {
                    label: "Views",
                    fill: false,
                    backgroundColor: window.chartColors.grey,
                    borderColor: window.chartColors.grey,
                    data: views,
                }]
            },
            options: {
                responsive: true,
                /*title:{
                    display:true,
                    text:'Graph'
                },*/
                tooltips: {
                    mode: 'index',
                    intersect: false,
                },
                hover: {
                    mode: 'nearest',
                    intersect: true
                },
                /*scales: {
                    xAxes: [{
                        display: true,
                        scaleLabel: {
                            display: true,
                            labelString: '',
                            fontSize: 24,
                            lineHeight: 1,
                            fontColor: '#333333'
                        }
                    }],
                    yAxes: [{
                        display: true,
                        scaleLabel: {
                            display: true,
                            labelString: 'Value'
                        }
                    }]
                }*/
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

    wp_enqueue_style( 'srs_hits_counter_Chart_css', plugins_url( '/css/style.css', __FILE__ ));

    $table_name = $wpdb->prefix.'srs_simple_hits_counter';

    if( isset($_POST['srs_shc_options_save']) && wp_verify_nonce($_POST['srs-form-nonce'], 'srs-form-9171') ){

        // Reset Unique Visitor Counter
        if (isset($_POST['unique_visitor_checkbox'])){
            if( $_POST['unique_visitor_reset_val'] != '' && $_POST['unique_visitor_checkbox'] == "yes" ){
                $wpdb->query("UPDATE $table_name SET `srs_visitors_count` = '0'");
                srs_reset_views_visitors(0, sanitize_text_field($_POST['unique_visitor_reset_val']), 'null');
            }
        }

        // Reset Page Views Counter
        if (isset($_POST['page_views_checkbox'])){
            if( $_POST['page_views_reset_val'] != '' && $_POST['page_views_checkbox'] == "yes" ){
                $wpdb->query("UPDATE $table_name SET `srs_views_count` = '0'");
                srs_reset_views_visitors(0, 'null', sanitize_text_field($_POST['page_views_reset_val']));
            }
        }

        // Change number format
        if(isset($_POST['page_views_number_format_checkbox']) && $_POST['page_views_number_format_checkbox'] == 'yes'){
            update_option('srs_pageViews_number_format_count', 'yes');
        }else{
            update_option('srs_pageViews_number_format_count', 'no');
        }

        // Reset plugin data
        if (isset($_POST['reset_data']) && $_POST['reset_data'] == 'yes'){
            $wpdb->query("TRUNCATE $table_name");
        }

        // Save counter styles
        $is_transparent = isset($_POST['srs_bg_transparent']) && $_POST['srs_bg_transparent'] == 'yes' ? 'yes' : 'no';
        $bg_color = $is_transparent === 'yes' ? 'transparent' : sanitize_hex_color($_POST['srs_background']);
        
        $styles = array(
            'counter_type'  => isset($_POST['srs_counter_type']) && $_POST['srs_counter_type'] === 'meter' ? 'meter' : 'simple',
            'font_family'   => sanitize_text_field($_POST['srs_font_family']),
            'font_size'     => max(8, min(120, absint($_POST['srs_font_size']))),
            'color'         => sanitize_hex_color($_POST['srs_color']),
            'background'    => $bg_color,
            'bg_transparent'=> $is_transparent,
            'padding'       => max(0, min(100, absint($_POST['srs_padding']))),
            'border_radius' => max(0, min(100, absint($_POST['srs_border_radius']))),
            'bold'          => isset($_POST['srs_bold'])        && $_POST['srs_bold']        == 'yes' ? 'yes' : 'no',
            'italic'        => isset($_POST['srs_italic'])      && $_POST['srs_italic']      == 'yes' ? 'yes' : 'no',
            'text_shadow'   => isset($_POST['srs_text_shadow']) && $_POST['srs_text_shadow'] == 'yes' ? 'yes' : 'no',
            'text_align'    => in_array($_POST['srs_text_align'] ?? '', array('left','center','right')) ? $_POST['srs_text_align'] : 'left',
        );
        update_option('srs_counter_styles', $styles);
    }

    $data_return_visitors            = srs_count_total_visitors_views('visitors');
    $data_return_views               = srs_count_total_visitors_views('views');
    $srs_shc_unique_visitors_count   = $data_return_visitors->total;
    $srs_shc_page_views_count        = $data_return_views->total;
    $page_views_number_format_checkbox = get_option('srs_pageViews_number_format_count');

    // Style settings
    $s = get_option('srs_counter_styles', array(
        'counter_type'  => 'simple',
        'font_family'   => 'inherit',
        'font_size'     => '16',
        'color'         => '#000000',
        'background'    => '#ffffff',
        'padding'       => '0',
        'border_radius' => '0',
        'bold'          => 'no',
        'italic'        => 'no',
        'text_shadow'   => 'no',
        'text_align'    => 'left',
    ));

    $font_families = array(
        'inherit'          => 'Default (inherit)',
        'Arial'            => 'Arial',
        'Georgia'          => 'Georgia',
        'Courier New'      => 'Courier New',
        'Verdana'          => 'Verdana',
        'Roboto'           => 'Roboto (Google)',
        'Open Sans'        => 'Open Sans (Google)',
        'Lato'             => 'Lato (Google)',
        'Montserrat'       => 'Montserrat (Google)',
        'Playfair Display' => 'Playfair Display (Google)',
    );

    $google_fonts = array('Roboto', 'Open Sans', 'Lato', 'Montserrat', 'Playfair Display');
    ?>

    <div class="srs-simple-hits-counter">
        <div class="srs-base">
            <form method="post" action="">
                <div class="srs-container">

                    <!--Header-->
                    <div class="srs-header">
                        <div class="srs-header-title">
                            <h2>Simple Hits Counter</h2>
                        </div>
                    </div>

                    <!--Shortcodes-->
                    <div class="srs-row">
                        <div class="srs-shortcodes">
                            <div class="srs-shortcodes-title">
                                <h3>Shortcodes</h3>
                            </div>
                            <div class="srs-shortcodes-container">
                                <div class="srs-shortcodes-row">
                                    <div class="srs-shortcode-type">Unique Visitors</div>
                                    <div class="srs-shortcode">[srs_total_visitors]</div>
                                </div>
                                <div class="srs-shortcodes-row">
                                    <div class="srs-shortcode-type">Page Views</div>
                                    <div class="srs-shortcode">[srs_total_pageViews]</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!--Reset Counters-->
                    <div class="srs-row">
                        <div class="srs-reset-counters">
                            <div class="srs-reset-counters-title">
                                <h3>Reset Counters</h3>
                            </div>
                            <div class="srs-reset-counters-container">
                                <div class="srs-reset-counters-row">
                                    <div class="srs-reset-counters-type">Unique Visitors</div>
                                    <div class="srs-shortcode">
                                        <input type="text" name="unique_visitor_reset_val" placeholder="00000" value="<?php echo esc_attr($srs_shc_unique_visitors_count) ?>">
                                        <br><span class="description">Are you sure you want to reset 'Unique Visitors Counter'? <input type="checkbox" name="unique_visitor_checkbox" value="yes"></span>
                                    </div>
                                </div>
                                <div class="srs-reset-counters-row">
                                    <div class="srs-reset-counters-type">Page Views</div>
                                    <div class="srs-shortcode">
                                        <input type="text" name="page_views_reset_val" placeholder="00000" value="<?php echo esc_attr($srs_shc_page_views_count) ?>">
                                        <br><span class="description">Are you sure you want to reset 'Page Views Counter'? <input type="checkbox" name="page_views_checkbox" value="yes"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!--Formatting-->
                    <div class="srs-row">
                        <div class="srs-reset-counters">
                            <div class="srs-formatting-title">
                                <h3>Formatting</h3>
                            </div>
                            <div class="srs-formatting-container">
                                <div class="srs-formatting-row">
                                    <div class="srs-formatting-type">Add Commas?</div>
                                    <div class="srs-shortcode">
                                        <span class="description">Yes? <input type="checkbox" name="page_views_number_format_checkbox" value="yes" <?php if(isset($page_views_number_format_checkbox) && $page_views_number_format_checkbox == 'yes'){ echo "checked"; } ?>> Example: (10,000,000)</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!--Counter Styling-->
                    <div class="srs-row">
                        <div class="srs-reset-counters">
                            <div class="srs-formatting-title">
                                <h3>Counter Style</h3>
                                <p>Applies to both the visitors and page views counters.</p>
                            </div>
                            <div class="srs-formatting-container">

                                <!-- Counter Type -->
                                <div class="srs-formatting-row">
                                    <div class="srs-formatting-type">Counter Type</div>
                                    <div class="srs-shortcode">
                                        <label style="margin-right:16px;">
                                            <input type="radio" name="srs_counter_type" id="srs_counter_type_simple" value="simple"
                                                <?php checked(!isset($s['counter_type']) || $s['counter_type'] !== 'meter', true); ?>>
                                            Simple
                                        </label>
                                        <label>
                                            <input type="radio" name="srs_counter_type" id="srs_counter_type_meter" value="meter"
                                                <?php checked(isset($s['counter_type']) && $s['counter_type'] === 'meter', true); ?>>
                                            Meter
                                        </label>
                                        <p class="description" style="margin-top:4px;">
                                            <em>Simple</em> renders a plain styled number. <em>Meter</em> renders individual digit tiles.
                                        </p>
                                    </div>
                                </div>

                                <!-- Live Preview -->
                                <div class="srs-formatting-row">
                                    <div class="srs-formatting-type"><strong>Preview</strong></div>
                                    <div class="srs-shortcode">
                                        <!-- Simple preview -->
                                        <span id="srs_style_preview" style="display:inline-block;">1,234,567</span>
                                        <!-- Meter preview -->
                                        <div id="srs_meter_preview" style="display:none;"></div>
                                    </div>
                                </div>

                                <!-- Font Family -->
                                <div class="srs-formatting-row">
                                    <div class="srs-formatting-type">Font Family</div>
                                    <div class="srs-shortcode">
                                        <select name="srs_font_family" id="srs_font_family">
                                            <?php foreach ($font_families as $value => $label) : ?>
                                                <option value="<?php echo esc_attr($value); ?>" <?php selected($s['font_family'], $value); ?>>
                                                    <?php echo esc_html($label); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>

                                <!-- Font Size -->
                                <div class="srs-formatting-row">
                                    <div class="srs-formatting-type">Font Size (px)</div>
                                    <div class="srs-shortcode">
                                        <input type="number" name="srs_font_size" id="srs_font_size"
                                               value="<?php echo esc_attr($s['font_size']); ?>"
                                               min="8" max="120" style="width:80px;"> px
                                    </div>
                                </div>

                                <!-- Text Color -->
                                <div class="srs-formatting-row">
                                    <div class="srs-formatting-type">Text Color</div>
                                    <div class="srs-shortcode">
                                        <input type="color" name="srs_color" id="srs_color"
                                               value="<?php echo esc_attr($s['color']); ?>">
                                    </div>
                                </div>

                                <!-- Background Color -->
                                <div class="srs-formatting-row">
                                    <div class="srs-formatting-type">Background Color</div>
                                    <div class="srs-shortcode">
                                        <input type="hidden" name="srs_bg_transparent" value="no">
                                        <input type="color" name="srs_background" id="srs_background"
                                               value="<?php echo esc_attr($s['background'] !== 'transparent' ? $s['background'] : '#ffffff'); ?>">
                                        <label style="margin-left:8px;">
                                            <input type="checkbox" name="srs_bg_transparent" id="srs_bg_transparent" value="yes" <?php checked($s['bg_transparent'] ?? 'no', 'yes'); ?>> Transparent
                                        </label>
                                    </div>
                                </div>

                                <!-- Padding -->
                                <div class="srs-formatting-row">
                                    <div class="srs-formatting-type">Padding (px)</div>
                                    <div class="srs-shortcode">
                                        <input type="number" name="srs_padding" id="srs_padding"
                                               value="<?php echo esc_attr($s['padding']); ?>"
                                               min="0" max="100" style="width:80px;"> px
                                    </div>
                                </div>

                                <!-- Border Radius -->
                                <div class="srs-formatting-row">
                                    <div class="srs-formatting-type">Border Radius (px)</div>
                                    <div class="srs-shortcode">
                                        <input type="number" name="srs_border_radius" id="srs_border_radius"
                                               value="<?php echo esc_attr($s['border_radius']); ?>"
                                               min="0" max="100" style="width:80px;"> px
                                    </div>
                                </div>

                                <!-- Bold -->
                                <div class="srs-formatting-row">
                                    <div class="srs-formatting-type">Bold</div>
                                    <div class="srs-shortcode">
                                        <input type="checkbox" name="srs_bold" id="srs_bold" value="yes"
                                               <?php checked($s['bold'], 'yes'); ?>>
                                    </div>
                                </div>

                                <!-- Italic -->
                                <div class="srs-formatting-row">
                                    <div class="srs-formatting-type">Italic</div>
                                    <div class="srs-shortcode">
                                        <input type="checkbox" name="srs_italic" id="srs_italic" value="yes"
                                               <?php checked($s['italic'], 'yes'); ?>>
                                    </div>
                                </div>

                                <!-- Text Shadow -->
                                <div class="srs-formatting-row">
                                    <div class="srs-formatting-type">Text Shadow</div>
                                    <div class="srs-shortcode">
                                        <input type="checkbox" name="srs_text_shadow" id="srs_text_shadow" value="yes"
                                               <?php checked($s['text_shadow'], 'yes'); ?>>
                                        <span class="description">Adds a subtle shadow to the text</span>
                                    </div>
                                </div>

                                <!-- Text Alignment -->
                                <div class="srs-formatting-row">
                                    <div class="srs-formatting-type">Text Alignment</div>
                                    <div class="srs-shortcode">
                                        <?php $ta = !empty($s['text_align']) ? $s['text_align'] : 'left'; ?>
                                        <label style="margin-right:12px;">
                                            <input type="radio" name="srs_text_align" id="srs_text_align_left" value="left" <?php checked($ta, 'left'); ?>> Left
                                        </label>
                                        <label style="margin-right:12px;">
                                            <input type="radio" name="srs_text_align" id="srs_text_align_center" value="center" <?php checked($ta, 'center'); ?>> Center
                                        </label>
                                        <label>
                                            <input type="radio" name="srs_text_align" id="srs_text_align_right" value="right" <?php checked($ta, 'right'); ?>> Right
                                        </label>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>

                    <!--Reset Plugin-->
                    <div class="srs-row">
                        <div class="srs-reset-plugin">
                            <div class="srs-reset-plugin-title">
                                <h3>Reset Plugin</h3>
                            </div>
                            <div class="srs-reset-plugin-container">
                                <div class="srs-reset-plugin-row">
                                    <div class="srs-reset-plugin-type">Reset</div>
                                    <div class="srs-shortcode">
                                        <span class="description">Yes? <input type="checkbox" name="reset_data" value="yes"> Deletes all the existing plugin data and starts fresh</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!--Submit-->
                    <?php $nonce = wp_create_nonce('srs-form-9171') ?>
                    <input type="hidden" name="srs-form-nonce" value="<?php echo $nonce ?>">
                    <p class="submit">
                        <input type="submit" name="srs_shc_options_save" class="button-primary" value="Save settings">
                    </p>

                </div>
            </form>
        </div>
    </div>

    <!-- Live preview script -->
    <script>
    (function() {
        var simplePreview = document.getElementById('srs_style_preview');
        var meterPreview  = document.getElementById('srs_meter_preview');
        var sampleDigits  = [1,2,3,4,5,6,7];

        function isMeter() {
            var el = document.getElementById('srs_counter_type_meter');
            return el && el.checked;
        }

        function buildMeterPreview(col, bg, br, fontWeight, fontStyle, textShadow) {
            var wrapStyle  = 'display:inline-flex;align-items:center;justify-content:center;gap:6px;';
            var digitStyle = 'width:28px;height:32px;background:' + bg + ';border-radius:' + br + 'px;display:inline-flex;align-items:center;justify-content:center;';
            var spanStyle  = 'font-family:\'Roboto Mono\',monospace;font-size:1rem;font-weight:' + fontWeight + ';font-style:' + fontStyle + ';color:' + col + ';line-height:1;text-shadow:' + textShadow + ';';
            var html = '<div style="' + wrapStyle + '">';
            sampleDigits.forEach(function(d) {
                html += '<div style="' + digitStyle + '"><span style="' + spanStyle + '">' + d + '</span></div>';
            });
            html += '</div>';
            return html;
        }

        function toggleMeterFields() {
            var meter      = isMeter();
            var fsEl       = document.getElementById('srs_font_size');
            var padEl      = document.getElementById('srs_padding');
            var fsRow      = fsEl  ? fsEl.closest('.srs-formatting-row')  : null;
            var padRow     = padEl ? padEl.closest('.srs-formatting-row') : null;
            if (fsRow)  { fsRow.style.opacity  = meter ? '0.4' : '1'; fsRow.style.pointerEvents  = meter ? 'none' : ''; }
            if (padRow) { padRow.style.opacity = meter ? '0.4' : '1'; padRow.style.pointerEvents = meter ? 'none' : ''; }
        }

        function updatePreview() {
            toggleMeterFields();
            var ff   = document.getElementById('srs_font_family').value;
            var fsRaw = document.getElementById('srs_font_size').value;
            var fs   = parseInt(fsRaw, 10) || 16;
            var col  = document.getElementById('srs_color').value;
            var bg   = document.getElementById('srs_background').value;
            var padRaw = document.getElementById('srs_padding').value;
            var pad  = padRaw !== '' ? padRaw : '0';
            var br   = document.getElementById('srs_border_radius').value;
            var bold = document.getElementById('srs_bold').checked;
            var ital = document.getElementById('srs_italic').checked;
            var shad = document.getElementById('srs_text_shadow').checked;
            var tran = document.getElementById('srs_bg_transparent').checked;
            var taEl = document.querySelector('input[name="srs_text_align"]:checked');
            var ta   = taEl ? taEl.value : 'left';

            var fontWeight  = bold ? 'bold' : 'normal';
            var fontStyle   = ital ? 'italic' : 'normal';
            var textShadow  = shad ? '1px 1px 3px rgba(0,0,0,0.4)' : 'none';
            var bgColor     = tran ? 'transparent' : bg;

            if (isMeter()) {
                simplePreview.style.display = 'none';
                meterPreview.style.display  = 'block';
                meterPreview.style.textAlign = ta;
                // Load Roboto Mono for preview if not already loaded
                if (!document.getElementById('srs-roboto-mono-preview')) {
                    var link  = document.createElement('link');
                    link.id   = 'srs-roboto-mono-preview';
                    link.rel  = 'stylesheet';
                    link.href = 'https://fonts.googleapis.com/css2?family=Roboto+Mono:wght@700&display=swap';
                    document.head.appendChild(link);
                }
                meterPreview.innerHTML = buildMeterPreview(col, bgColor, br, fontWeight, fontStyle, textShadow);
            } else {
                meterPreview.style.display  = 'none';
                simplePreview.style.display = 'inline-block';
                simplePreview.style.fontFamily   = ff;
                simplePreview.style.fontSize     = fs + 'px';
                simplePreview.style.color        = col;
                simplePreview.style.background   = bgColor;
                simplePreview.style.padding      = pad + 'px';
                simplePreview.style.borderRadius = br + 'px';
                simplePreview.style.fontWeight   = fontWeight;
                simplePreview.style.fontStyle    = fontStyle;
                simplePreview.style.textShadow   = textShadow;
                simplePreview.style.textAlign    = ta;

                // Load Google Font dynamically for preview
                var googleFonts = <?php echo json_encode($google_fonts); ?>;
                if (googleFonts.indexOf(ff) !== -1) {
                    var linkId   = 'srs-preview-font';
                    var existing = document.getElementById(linkId);
                    if (!existing) {
                        var link  = document.createElement('link');
                        link.id   = linkId;
                        link.rel  = 'stylesheet';
                        link.href = 'https://fonts.googleapis.com/css2?family=' + encodeURIComponent(ff) + '&display=swap';
                        document.head.appendChild(link);
                    } else {
                        existing.href = 'https://fonts.googleapis.com/css2?family=' + encodeURIComponent(ff) + '&display=swap';
                    }
                }
            }
        }

        var watchIds = ['srs_font_family','srs_font_size','srs_color','srs_background',
                        'srs_padding','srs_border_radius','srs_bold','srs_italic',
                        'srs_text_shadow','srs_bg_transparent',
                        'srs_counter_type_simple','srs_counter_type_meter',
                        'srs_text_align_left','srs_text_align_center','srs_text_align_right'];

        watchIds.forEach(function(id) {
            var el = document.getElementById(id);
            if (el) {
                el.addEventListener('change', updatePreview);
                el.addEventListener('input',  updatePreview);
            }
        });

        updatePreview(); // run on page load to reflect saved values
    })();
    </script>
    <?php
}