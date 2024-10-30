<?php

namespace IDatAffiliates\Models;

use IDatAffiliates\Abstracts\Abstract_Main_Plugin_Class;

use IDatAffiliates\Interfaces\Model_Interface;
use IDatAffiliates\Interfaces\Initiable_Interface;

use IDatAffiliates\Helpers\Plugin_Constants;
use IDatAffiliates\Helpers\Helper_Functions;

use IDatAffiliates\Models\Affiliate_Link;

/**
 * Model that houses the logic for permalink rewrites and affiliate link redirections.
 *
 * @since 3.0.0
 */
class Stats_Reporting implements Model_Interface , Initiable_Interface {

    /*
    |--------------------------------------------------------------------------
    | Class Properties
    |--------------------------------------------------------------------------
    */

    /**
     * Property that holds the single main instance of Stats_Reporting.
     *
     * @since 3.0.0
     * @access private
     * @var Redirection
     */
    private static $_instance;

    /**
     * Model that houses the main plugin object.
     *
     * @since 3.0.0
     * @access private
     * @var Redirection
     */
    private $_main_plugin;

    /**
     * Model that houses all the plugin constants.
     *
     * @since 3.0.0
     * @access private
     * @var Plugin_Constants
     */
    private $_constants;

    /**
     * Property that houses all the helper functions of the plugin.
     *
     * @since 3.0.0
     * @access private
     * @var Helper_Functions
     */
    private $_helper_functions;




    /*
    |--------------------------------------------------------------------------
    | Class Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Class constructor.
     *
     * @since 3.0.0
     * @access public
     *
     * @param Abstract_Main_Plugin_Class $main_plugin      Main plugin object.
     * @param Plugin_Constants           $constants        Plugin constants object.
     * @param Helper_Functions           $helper_functions Helper functions object.
     */
    public function __construct( Abstract_Main_Plugin_Class $main_plugin , Plugin_Constants $constants , Helper_Functions $helper_functions ) {

        $this->_constants        = $constants;
        $this->_helper_functions = $helper_functions;

        $main_plugin->add_to_all_plugin_models( $this );
        $main_plugin->add_to_public_models( $this );

    }

    /**
     * Ensure that only one instance of this class is loaded or can be loaded ( Singleton Pattern ).
     *
     * @since 3.0.0
     * @access public
     *
     * @param Abstract_Main_Plugin_Class $main_plugin      Main plugin object.
     * @param Plugin_Constants           $constants        Plugin constants object.
     * @param Helper_Functions           $helper_functions Helper functions object.
     * @return Redirection
     */
    public static function get_instance( Abstract_Main_Plugin_Class $main_plugin , Plugin_Constants $constants , Helper_Functions $helper_functions ) {

        if ( !self::$_instance instanceof self )
            self::$_instance = new self( $main_plugin , $constants , $helper_functions );

        return self::$_instance;

    }




    /*
    |--------------------------------------------------------------------------
    | Data saving
    |--------------------------------------------------------------------------
    */

    /**
     * Save link click data to the database.
     *
     * @since 3.0.0
     * @access private
     *
     * @param int    $link_id      Affiliate link ID.
     * @param string $http_referer HTTP Referrer value.
     */
    private function save_click_data( $link_id , $http_referer = '' ) {

        global $wpdb;

        $link_click_db      = $wpdb->prefix . Plugin_Constants::LINK_CLICK_DB;
        $link_click_meta_db = $wpdb->prefix . Plugin_Constants::LINK_CLICK_META_DB;

        // insert click entry
        $wpdb->insert(
            $link_click_db,
            array(
                'link_id'      => $link_id,
                'date_clicked' => current_time( 'mysql' , true )
            )
        );

        // save click meta data
        if ( $click_id = $wpdb->insert_id ) {

            $meta_data = apply_filters( 'ta_save_click_data' , array(
                'user_ip_address' => $this->_helper_functions->get_user_ip_address(),
                'http_referer'    => $http_referer
            ) );

            foreach ( $meta_data as $key => $value ) {

                $wpdb->insert(
                    $link_click_meta_db,
                    array(
                        'click_id'    => $click_id,
                        'meta_key'   => $key,
                        'meta_value' => $value
                    )
                );
            }

        }
    }

    /**
     * Save click data on redirect
     *
     * @since 3.0.0
     * @access public
     *
     * @param Affiliate_Link $idatlink Affiliate link object.
     */
    public function save_click_data_on_redirect( $idatlink ) {

        $link_id      = $idatlink->get_id();
        $http_referer = isset( $_SERVER[ 'HTTP_REFERER' ] ) ? $_SERVER[ 'HTTP_REFERER' ] : '';

        // if the refferer is from an external site, then record stat.
        if ( ! $http_referer || ! strrpos( 'x' . $http_referer , home_url() ) )
            $this->save_click_data( $link_id , $http_referer );
    }

    /**
     * AJAX save click data on redirect
     *
     * @since 3.0.0
     * @access public
     */
    public function ajax_save_click_data_on_redirect() {

        if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX )
            wp_die();

        $link_id      = isset( $_REQUEST[ 'link_id' ] ) ? (int) sanitize_text_field( $_REQUEST[ 'link_id' ] ) : 0;
        $http_referer = isset( $_REQUEST[ 'page' ] ) ? sanitize_text_field( $_REQUEST[ 'page' ] ) : '';

        if ( ! $link_id ) {

            $link_href = sanitize_text_field( $_REQUEST[ 'href' ] );
            $link_id   = url_to_postid( $link_href );
        }

        if ( $link_id )
            $this->save_click_data( $link_id , $http_referer );

        wp_die();
    }




    /*
    |--------------------------------------------------------------------------
    | Fetch Report Data
    |--------------------------------------------------------------------------
    */

    /**
     * Fetch link performance data by date range.
     *
     * @since 3.0.0
     * @access public
     *
     * @global wpdb $wpdb Object that contains a set of functions used to interact with a database.
     *
     * @param string $start_date Report start date. Format: YYYY-MM-DD hh:mm:ss
     * @param string $end_date   Report end date. Format: YYYY-MM-DD hh:mm:ss
     * @param array  $link_ids    Affiliate Link post ID
     * @return string/array Link click meta data value.
     */
    public function get_link_performance_data( $start_date , $end_date , $link_ids ) {

        global $wpdb;

        if ( ! is_array( $link_ids ) || empty( $link_ids ) )
            return array();

        $link_clicks_db = $wpdb->prefix . Plugin_Constants::LINK_CLICK_DB;
        $link_ids_str   = implode( ', ' , $link_ids );
        $query          = "SELECT * FROM $link_clicks_db WHERE date_clicked between '$start_date' and '$end_date' and link_id IN ( $link_ids_str )";

        return $wpdb->get_results( $query );
    }

    /**
     * Get link click meta by id and key.
     *
     * @since 3.0.0
     * @access public
     *
     * @param int     $click_id Link click ID.
     * @param string  $meta_key Meta key column value.
     * @param boolean $single   Return single result or array.
     * @return array Link performance data.
     */
    private function get_click_meta( $click_id , $meta_key , $single = false ) {

        global $wpdb;

        $links_click_meta_db = $wpdb->prefix . Plugin_Constants::LINK_CLICK_META_DB;

        if ( $single ){

            $meta = $wpdb->get_row( "SELECT meta_value FROM $links_click_meta_db WHERE click_id = '$click_id' and meta_key = '$meta_key'" , ARRAY_A );
            return array_shift( $meta );

        } else {

            $meta     = array();
            $raw_data = $wpdb->get_results( "SELECT meta_value FROM $links_click_meta_db WHERE click_id = '$click_id' and meta_key = '$meta_key'" , ARRAY_N );

            foreach ( $raw_data as $data )
                $meta[] = array_shift( $data );

            return $meta;
        }
    }

    /**
     * AJAX fetch report by linkid.
     *
     * @since 3.0.0
     * @access public
     */
    public function ajax_fetch_report_by_linkid() {

        if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX )
            $response = array( 'status' => 'fail' , 'error_msg' => __( 'Invalid AJAX call' , 'idataffiliates' ) );
        elseif ( ! isset( $_POST[ 'link_id' ] ) )
            $response = array( 'status' => 'fail' , 'error_msg' => __( 'Missing required post data' , 'idataffiliates' ) );
        else {

            $link_id     = (int) sanitize_text_field( $_POST[ 'link_id' ] );
            $idatlink = new Affiliate_Link( $link_id );
            $range_txt   = sanitize_text_field( $_POST[ 'range' ] );
            $start_date  = sanitize_text_field( $_POST[ 'start_date' ] );
            $end_date    = sanitize_text_field( $_POST[ 'end_date' ] );

            if ( ! $idatlink->get_id() )
                $response = array( 'status' => 'fail' , 'error_msg' => __( 'Selected affiliate link is invalid' , 'idataffiliates' ) );
            else {

                $range   = $this->get_report_range_details( $range_txt , $start_date , $end_date );
                $data    = $this->prepare_data_for_flot( $range , array( $link_id ) );

                $response = array(
                    'status'      => 'success',
                    'label'       => $idatlink->get_prop( 'name' ),
                    'slug'        => $idatlink->get_prop( 'slug' ),
                    'report_data' => $data
                );
            }
        }

        @header( 'Content-Type: application/json; charset=' . get_option( 'blog_charset' ) );
        echo wp_json_encode( $response );
        wp_die();
    }




    /*
    |--------------------------------------------------------------------------
    | Reports Structure
    |--------------------------------------------------------------------------
    */

    /**
     * Get all registered reports.
     *
     * @since 3.0.0
     * @access public
     *
     * @return array Settings sections.
     */
    public function get_all_reports() {

        return apply_filters( 'ta_register_reports' , array() );
    }

    /**
     * Get current loaded report.
     *
     * @since 3.0.0
     * @access public
     *
     * @param string $tab Current report tab.
     * @return array Current loaded report.
     */
    public function get_current_report( $tab = '' ) {

        if ( ! $tab )
            $tab = isset( $_GET[ 'tab' ] ) ? esc_attr( $_GET[ 'tab' ] ) : 'link_performance';

        // get all registered sections and fields
        $reports = $this->get_all_reports();

        return isset( $reports[ $tab ] ) ? $reports[ $tab ] : array();
    }

    /**
     * Register link performance report.
     *
     * @since 3.0.0
     * @access public
     *
     * @param array $reports Array list of all registered reports.
     * @return array Array list of all registered reports.
     */
    public function register_link_performance_report( $reports ) {

        $reports[ 'link_performance' ] = array(
            'id'      => 'ta_link_performance_report',
            'tab'     => 'link_performance',
            'name'    => __( 'Link Performance' , 'idataffiliates' ),
            'title'   => __( 'Link Performance Report' , 'idataffiliates' ),
            'desc'    => __( 'Total clicks on affiliate links over a given period.' , 'idataffiliates' ),
            'content' => $this->get_link_performance_report_content()
        );

        return $reports;
    }




    /*
    |--------------------------------------------------------------------------
    | Display Report
    |--------------------------------------------------------------------------
    */

    /**
     * Register reports menu page.
     *
     * @since 3.0.0
     * @access public
     */
    public function add_reports_submenu() {

        add_submenu_page(
            'edit.php?post_type=idatlink',
            __( 'IDatAffiliates Reports' , 'idataffiliates' ),
            __( 'Reports' , 'idataffiliates' ),
            'manage_options',
            'idat-reports',
            array( $this, 'render_reports' )
        );
    }

    /**
     * Render reports page.
     *
     * @since 3.0.0
     * @access public
     */
    public function render_reports() {

        // fetch current section
        $current_report = $this->get_current_report();

        // skip if section data is empty
        if ( empty( $current_report ) ) return; ?>

        <div class="ta-settings ta-settings-<?php echo $current_report[ 'tab' ]; ?> wrap">

            <?php $this->render_reports_nav(); ?>

            <h1><?php echo $current_report[ 'title' ]; ?></h1>
            <p class="desc"><?php echo $current_report[ 'desc' ]; ?></p>

            <?php echo $current_report[ 'content' ]; ?>
        </div>
        <?php
    }

    /**
     * Render the settings navigation.
     *
     * @since 3.0.0
     * @access public
     */
    public function render_reports_nav() {

        $reports  = $this->get_all_reports();
        $current  = $this->get_current_report();
        $base_url = admin_url( 'edit.php?post_type=idatlink&page=idat-reports' );

        if ( empty( $reports ) ) return; ?>

        <nav class="idat-nav-tab">
            <?php foreach ( $reports as $report ) : ?>

                <a href="<?php echo $base_url . '&tab=' . $report[ 'tab' ]; ?>" class="tab <?php echo ( $current[ 'tab' ] === $report[ 'tab' ] ) ? 'tab-active' : ''; ?>">
                    <?php echo $report[ 'name' ]; ?>
                </a>

            <?php endforeach; ?>
        </nav>

        <?php
    }

    /**
     * Get Link performance report content.
     *
     * @since 3.0.0
     * @access public
     *
     * @return string Link performance report content.
     */
    public function get_link_performance_report_content() {

        $cpt_slug      = Plugin_Constants::AFFILIATE_LINKS_CPT;
        $current_range = isset( $_GET[ 'range' ] ) ? sanitize_text_field( $_GET[ 'range' ] ) : '7day';
        $start_date    = isset( $_GET[ 'start_date' ] ) ? sanitize_text_field( $_GET[ 'start_date' ] ) : '';
        $end_date      = isset( $_GET[ 'end_date' ] ) ? sanitize_text_field( $_GET[ 'end_date' ] ) : '';
        $link_id       = isset( $_GET[ 'link_id' ] ) ? sanitize_text_field( $_GET[ 'link_id' ] ) : '';
        $range         = $this->get_report_range_details( $current_range , $start_date , $end_date );
        $range_nav     = apply_filters( 'ta_link_performances_report_nav' , array(
            'year'       => __( 'Year' , 'idataffiliates' ),
            'last_month' => __( 'Last Month' , 'idataffiliates' ),
            'month'      => __( 'This Month' , 'idataffiliates' ),
            '7day'       => __( 'Last 7 Days' , 'idataffiliates' )
        ) );

        // make sure link_id is an affiliate link (published).
        // NOTE: when false, this needs to return an empty string as it is used for display.
        if ( $link_id ) $link_id = ( get_post_type( $link_id ) == $cpt_slug && get_post_status( $link_id ) == 'publish' ) ? $link_id : '';

        // get all published affiliate link ids
        $query = new \WP_Query( array(
            'post_type'      => $cpt_slug,
            'post_status'    => 'publish',
            'fields'         => 'ids',
            'posts_per_page' => -1
        ) );

        $data = $this->prepare_data_for_flot( $range , $query->posts );

        ob_start();
        include( $this->_constants->VIEWS_ROOT_PATH() . 'reports/link-performance-report.php' );

        return ob_get_clean();
    }




    /*
    |--------------------------------------------------------------------------
    | Helper methods
    |--------------------------------------------------------------------------
    */

    /**
     * Get report range details.
     *
     * @since 3.0.0
     * @access public
     *
     * @param string $range      Report range type.
     * @param string $start_date Starting date of range.
     * @param string $end_date   Ending date of range.
     * @return array Report range details.
     */
    public function get_report_range_details( $range = '7day' , $start_date = 'now -6 days' , $end_date = 'now' ) {

        $data       = array();
        $zone_str   = $this->_helper_functions->get_site_current_timezone();
        $timezone   = new \DateTimeZone( $zone_str );
        $now        = new \DateTime( 'now' , $timezone );

        switch ( $range ) {

            case 'year' :
                $data[ 'type' ]       = 'year';
                $data[ 'start_date' ] = new \DateTime( 'first day of January' . date( 'Y' ) , $timezone );
                $data[ 'end_date' ]   = $now;
                break;

            case 'last_month' :
                $data[ 'type' ]       = 'last_month';
                $data[ 'start_date' ] = new \DateTime( 'first day of last month' , $timezone );
                $data[ 'end_date' ]   = new \DateTime( 'last day of last month' , $timezone );
                break;

            case 'month' :
                $data[ 'type' ]       = 'month';
                $data[ 'start_date' ] = new \DateTime( 'first day of this month' , $timezone );
                $data[ 'end_date' ]   = $now;
                break;

            case 'custom' :
                $data[ 'type' ]       = 'custom';
                $data[ 'start_date' ] = new \DateTime( $start_date , $timezone );
                $data[ 'end_date' ]   = new \DateTime( $end_date . ' 23:59:59' , $timezone );
                break;

            case '7day' :
            default :
                $data[ 'type' ]       = '7day';
                $data[ 'start_date' ] = new \DateTime( 'now -6 days' , $timezone );
                $data[ 'end_date' ]   = $now;
                break;
        }

        return apply_filters( 'ta_report_range_data' , $data , $range );
    }

    /**
     * Prepare data to feed for jQuery flot.
     *
     * @since 3.0.0
     * @access public
     *
     * @param array $range    Report range details
     * @param array $link_ids Affiliate Link post ID
     * @return array Processed data for jQuery flot.
     */
    public function prepare_data_for_flot( $range , $link_ids ) {

        $start_date = $range[ 'start_date' ];
        $end_date   = $range[ 'end_date' ];
        $zone_str   = $this->_helper_functions->get_site_current_timezone();
        $timezone   = new \DateTimeZone( $zone_str );
        $flot_data  = array();

        if ( apply_filters( 'ta_report_set_start_date_time_to_zero' , true , $range ) )
            $start_date->setTime( 0 , 0 );

        $raw_data = $this->get_link_performance_data( $start_date->format( 'Y-m-d H:i:s' ) , $end_date->format( 'Y-m-d H:i:s' ) , $link_ids );

        // get number of days difference between start and end
        $incrementor    = apply_filters( 'ta_report_flot_data_incrementor' , ( 60 * 60 * 24 ) , $range );
        $timestamp_diff = ( $start_date->getTimestamp() - $end_date->getTimestamp() );
        $days_diff      = abs( floor( $timestamp_diff / $incrementor ) );

        // save the timestamp for first day
        $timestamp      = $start_date->format( 'U' );
        $month_time     = $this->get_month_first_day_datetime_obj( 'February' );
        $next_timestamp = ( $range[ 'type' ] == 'year' ) ? $month_time->format( 'U' ) : $timestamp + $incrementor;
        $flot_data[]    = array(
            'timestamp'      => (int) $timestamp,
            'count'          => 0,
            'next_timestamp' => $next_timestamp
        );

        if ( $range[ 'type' ] == 'year' ) {

            $months = array( 'February' , 'March' , 'April' , 'May' , 'June' , 'July' , 'August' , 'September' , 'October' , 'November' , 'December' );

            foreach ( $months as $key => $month ) {

                $month_time = $this->get_month_first_day_datetime_obj( $month );
                $next_month = $this->get_month_first_day_datetime_obj( $months[ $key + 1 ] );

                $flot_data[] = array(
                    'timestamp'      => $month_time->format( 'U' ),
                    'count'          => 0,
                    'next_timestamp' => $next_month->format( 'U' )
                );

                if ( $end_date->format( 'F' ) == $month )
                    break;
            }

        } else {

            // determine timestamps for succeeding days
            for ( $x = 1; $x < $days_diff; $x++ ) {

                $timestamp      = $next_timestamp;
                $next_timestamp = $timestamp + $incrementor;

                $flot_data[] = array(
                    'timestamp'      => (int) $timestamp,
                    'count'          => 0,
                    'next_timestamp' => $next_timestamp
                );

            }
        }

        // count each click data and assign to appropriate day.
        foreach ( $raw_data as $click_entry ) {

            $click_date = new \DateTime( $click_entry->date_clicked , new \DateTimeZone( 'UTC' ) );
            $click_date->setTimezone( $timezone );

            $click_timestamp = (int) $click_date->format( 'U' );

            foreach ( $flot_data as $key => $day_data ) {

                if ( $click_timestamp >= $day_data[ 'timestamp' ] && $click_timestamp < $day_data[ 'next_timestamp' ] ) {
                    $flot_data[ $key ][ 'count' ] += 1;
                    continue;
                }
            }
        }

        // convert $flot_data array into non-associative array
        foreach ( $flot_data as $key => $day_data ) {

            unset( $day_data[ 'next_timestamp' ] );

            $day_data[ 'timestamp' ] = $day_data[ 'timestamp' ] * 1000;
            $flot_data[ $key ] = array_values( $day_data );
        }

        return $flot_data;
    }

    /**
     * Get the DateTime object of the first day of a given month.
     *
     * @since 3.0.0
     * @access public
     *
     * @param string $month Month full textual representation.
     * @return DateTime First day of the given month DateTime object.
     */
    public function get_month_first_day_datetime_obj( $month ) {

        $zone_str  = $this->_helper_functions->get_site_current_timezone();
        $timezone  = new \DateTimeZone( $zone_str );

        return new \DateTime( 'First day of ' . $month . ' ' . date( 'Y' ) , $timezone );
    }

    /**
     * Delete stats data when an affiliate link is deleted permanently.
     *
     * @since 3.0.1
     * @access public
     *
     * @global wpdb $wpdb Object that contains a set of functions used to interact with a database.
     *
     * @param int $link_id Affiliate link ID.
     */
    public function delete_stats_data_on_affiliate_link_deletion( $link_id ) {

        global $wpdb;

        if ( Plugin_Constants::AFFILIATE_LINKS_CPT !== get_post_type( $link_id ) )
            return;

        $link_click_db      = $wpdb->prefix . Plugin_Constants::LINK_CLICK_DB;
        $link_click_meta_db = $wpdb->prefix . Plugin_Constants::LINK_CLICK_META_DB;
        $click_ids          = $wpdb->get_col( "SELECT id FROM $link_click_db WHERE link_id = $link_id" );

        if ( ! is_array( $click_ids ) || empty( $click_ids ) )
            return;

        $click_ids_str = implode( ',' , $click_ids );

        // delete click meta records.
        $wpdb->query( "DELETE FROM $link_click_meta_db WHERE click_id IN ( $click_ids_str )" );

        // delete click records.
        $wpdb->query( "DELETE FROM $link_click_db WHERE id IN ( $click_ids_str )" );
    }




    /*
    |--------------------------------------------------------------------------
    | Fulfill implemented interface contracts
    |--------------------------------------------------------------------------
    */

    /**
     * Method that houses codes to be executed on init hook.
     *
     * @since 3.0.0
     * @access public
     * @inherit IDatAffiliates\Interfaces\Initiable_Interface
     */
    public function initialize() {

        // When module is disabled in the settings, then it shouldn't run the whole class.
        if ( get_option( 'ta_enable_stats_reporting_module' , 'yes' ) !== 'yes' )
            return;

        add_action( 'wp_ajax_ta_click_data_redirect' , array( $this , 'ajax_save_click_data_on_redirect' ) , 10 );
        add_action( 'wp_ajax_ta_fetch_report_by_linkid' , array( $this , 'ajax_fetch_report_by_linkid' ) , 10 );
        add_action( 'wp_ajax_nopriv_ta_click_data_redirect' , array( $this , 'ajax_save_click_data_on_redirect' ) , 10 );
    }

    /**
     * Execute ajax handler.
     *
     * @since 3.0.0
     * @access public
     * @inherit IDatAffiliates\Interfaces\Model_Interface
     */
    public function run() {

        // When module is disabled in the settings, then it shouldn't run the whole class.
        if ( get_option( 'ta_enable_stats_reporting_module' , 'yes' ) !== 'yes' )
            return;

        add_action( 'ta_before_link_redirect' , array( $this , 'save_click_data_on_redirect' ) , 10 , 1 );
//        add_action( 'admin_menu' , array( $this , 'add_reports_submenu' ) , 10 );
        add_action( 'ta_register_reports' , array( $this , 'register_link_performance_report' ) , 10 );
        add_action( 'before_delete_post' , array( $this , 'delete_stats_data_on_affiliate_link_deletion' ) , 10 );
    }
}
