<?php
namespace IDatAffiliates\Helpers;

use IDatAffiliates\Abstracts\Abstract_Main_Plugin_Class;

use IDatAffiliates\Models\Affiliate_Link;

if ( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Model that houses all the helper functions of the plugin.
 *
 * 3.0.0
 */
class Helper_Functions {

    /*
    |--------------------------------------------------------------------------
    | Class Properties
    |--------------------------------------------------------------------------
    */

    /**
     * Property that holds the single main instance of Helper_Functions.
     *
     * @since 3.0.0
     * @access private
     * @var Helper_Functions
     */
    private static $_instance;

    /**
     * Model that houses all the plugin constants.
     *
     * @since 3.0.0
     * @access private
     * @var Plugin_Constants
     */
    private $_constants;

    /**
     * Property that houses all the saved settings.
     *
     * @since 3.0.0
     * @access private
     */
    private $_settings = array();




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
     * @param Plugin_Constants 			 $constants Plugin constants object.
     */
    public function __construct( Abstract_Main_Plugin_Class $main_plugin , Plugin_Constants $constants ) {

        $this->_constants = $constants;

        $main_plugin->add_to_public_helpers( $this );

    }

    /**
     * Ensure that only one instance of this class is loaded or can be loaded ( Singleton Pattern ).
     *
     * @since 3.0.0
     * @access public
     *
     * @param Abstract_Main_Plugin_Class $main_plugin      Main plugin object.
     * @param Plugin_Constants 			 $constants Plugin constants object.
     * @return Helper_Functions
     */
    public static function get_instance( Abstract_Main_Plugin_Class $main_plugin , Plugin_Constants $constants ) {

        if ( !self::$_instance instanceof self )
            self::$_instance = new self( $main_plugin , $constants );

        return self::$_instance;

    }




    /*
    |--------------------------------------------------------------------------
    | Helper Functions
    |--------------------------------------------------------------------------
    */

    /**
     * Write data to plugin log file.
     *
     * @since 3.0.0
     * @access public
     *
     * @param mixed Data to log.
     */
    public function write_debug_log( $log )  {

        error_log( "\n[" . current_time( 'mysql' ) . "]\n" . $log . "\n--------------------------------------------------\n" , 3 , $this->_constants->LOGS_ROOT_PATH() . 'debug.log' );

    }

    /**
     * Check if current user is authorized to manage the plugin on the backend.
     *
     * @since 3.0.0
     * @access public
     *
     * @param WP_User $user WP_User object.
     * @return boolean True if authorized, False otherwise.
     */
    public function current_user_authorized( $user = null ) {

        // Array of roles allowed to access/utilize the plugin
        $admin_roles = apply_filters( 'ucfw_admin_roles' , array( 'administrator' ) );

        if ( is_null( $user ) )
            $user = wp_get_current_user();

        if ( $user->ID )
            return count( array_intersect( ( array ) $user->roles , $admin_roles ) ) ? true : false;
        else
            return false;

    }

    /**
     * Returns the timezone string for a site, even if it's set to a UTC offset
     *
     * Adapted from http://www.php.net/manual/en/function.timezone-name-from-abbr.php#89155
     *
     * Reference:
     * http://www.skyverge.com/blog/down-the-rabbit-hole-wordpress-and-timezones/
     *
     * @since 3.0.0
     * @access public
     *
     * @return string Valid PHP timezone string
     */
    public function get_site_current_timezone() {

        // if site timezone string exists, return it
        if ( $timezone = get_option( 'timezone_string' ) )
            return $timezone;

        // get UTC offset, if it isn't set then return UTC
        if ( 0 === ( $utc_offset = get_option( 'gmt_offset', 0 ) ) )
            return 'UTC';

        return $this->convert_utc_offset_to_timezone( $utc_offset );

    }

    /**
     * Conver UTC offset to timezone.
     *
     * @since 1.2.0
     * @access public
     *
     * @param float|int|string $utc_offset UTC offset.
     * @return string valid PHP timezone string
     */
    public function convert_utc_offset_to_timezone( $utc_offset ) {

        // adjust UTC offset from hours to seconds
        $utc_offset *= 3600;

        // attempt to guess the timezone string from the UTC offset
        if ( $timezone = timezone_name_from_abbr( '' , $utc_offset , 0 ) )
            return $timezone;

        // last try, guess timezone string manually
        $is_dst = date( 'I' );

        foreach ( timezone_abbreviations_list() as $abbr )
            foreach ( $abbr as $city )
                if ( $city[ 'dst' ] == $is_dst && $city[ 'offset' ] == $utc_offset )
                    return $city[ 'timezone_id' ];

        // fallback to UTC
        return 'UTC';

    }

    /**
     * Get all user roles.
     *
     * @since 3.0.0
     * @access public
     *
     * @global WP_Roles $wp_roles Core class used to implement a user roles API.
     *
     * @return array Array of all site registered user roles. User role key as the key and value is user role text.
     */
    public function get_all_user_roles() {

        global $wp_roles;
        return $wp_roles->get_names();

    }

    /**
     * Check validity of a save post action.
     *
     * @since 3.0.0
     * @access private
     *
     * @param int    $post_id   Id of the coupon post.
     * @param string $post_type Post type to check.
     * @return bool True if valid save post action, False otherwise.
     */
    public function check_if_valid_save_post_action( $post_id , $post_type ) {

        if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) || !current_user_can( 'edit_page' , $post_id ) || get_post_type() != $post_type || empty( $_POST ) )
            return false;
        else
            return true;

    }

    /**
     * Get user IP address.
     *
     * @since 3.0.0
     * @access public
     *
     * @return string User's IP address.
     */
    public function get_user_ip_address() {

        if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) )
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) )
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        else
            $ip = $_SERVER['REMOTE_ADDR'];

        return apply_filters( 'ta_get_user_ip_address', $ip );
    }

    /**
     * Get the idatlink slug set on the settings.
     *
     * @since 3.0.0
     * @access public
     *
     * @return string $link_prefix IDatling link prefix.
     */
    public function get_idatlink_link_prefix() {

        $link_prefix = get_option( 'ta_link_prefix' , 'recommends' );

        if ( $link_prefix === 'custom' )
            $link_prefix = get_option( 'ta_link_prefix_custom' , 'recommends' );

        return $link_prefix;
    }

    /**
     * Get the affiliate link post default category slug.
     *
     * @since 3.0.0
     * @access public
     *
     * @param int $link_id Affiliate Link ID.
     * @return string Affiliate link default category slug.
     */
    public function get_default_category_slug( $link_id ) {

        $terms = get_the_terms( $link_id , Plugin_Constants::AFFILIATE_LINKS_TAX );

        if ( is_wp_error( $terms ) || empty( $terms ) )
            return;

        $link_cat_obj = array_shift( $terms );

        return $link_cat_obj->slug;
    }

    /**
     * Search affiliate links query
     *
     * @since 3.0.0
     * @access public
     *
     * @param string $keyword  Search keyword.
     * @param int    $paged    WP_Query paged value.
     * @param string $category Affiliate link category to search.
     * @param array  $exclude  List of posts to be excluded.
     * @return array List of affiliate link IDs.
     */
    public function search_affiliate_links_query( $keyword = '' , $paged = 1 , $category = '' , $exclude = array() ) {

        $args = array(
            'post_type'    => Plugin_Constants::AFFILIATE_LINKS_CPT,
            'post_status'  => 'publish',
            's'            => $keyword,
            'fields'       => 'ids',
            'paged'        => $paged,
            'post__not_in' => $exclude
        );

        if ( $category ) {

            $args[ 'tax_query' ] = array(
                array(
                    'taxonomy' => Plugin_Constants::AFFILIATE_LINKS_TAX,
                    'field'    => 'slug',
                    'terms'    => $category
                )
            );
        }

        $query = new \WP_Query( $args );

        return $query->posts;
    }

    /**
     * Check if affiliate link needs to be uncloaked.
     *
     * @since 3.0.0
     * @access public
     *
     * @param Affiliate_Link $idatlink IDat affiliate link object.
     * @return boolean Sets to true when affiliate link needs to be uncloaked.
     */
    public function is_uncloak_link( $idatlink ) {

        // check if global setting for uncloak link is enabled.
        if ( get_option( 'ta_uncloak_link_per_link' ) !== 'yes' )
            return;

        // return (true) when uncloak link is enabled on a per post basis.
        if ( $idatlink->get_prop( 'uncloak_link' ) == 'yes' )
            return true;
        elseif ( $idatlink->get_prop( 'uncloak_link' ) == 'no' )
            return;

        $uncloak_categories = maybe_unserialize( get_option( 'ta_category_to_uncloak' ) );
        $link_categories    = $idatlink->get_prop( 'categories' );

        // skip when there are no categories to uncloak (false)
        if ( empty( $uncloak_categories ) || empty( $link_categories ) )
            return;

        foreach ( $link_categories as $category ) {

            if ( in_array( $category->term_id , $uncloak_categories ) )
                return true;
        }

        return;
    }

    /**
     * Error log with a trace.
     *
     * @since 3.0.0
     * @access public
     */
    public function ta_error_log( $msg ) {

        $trace  = debug_backtrace();
        $caller = array_shift( $trace );

        error_log( $msg . ' | Trace: ' . $caller[ 'file' ] . ' on line ' . $caller[ 'line' ] );

    }

    /**
     * Utility function that determines if a plugin is active or not.
     *
     * @since 3.0.0
     * @access public
     *
     * @param string $plugin_basename Plugin base name. Ex. woocommerce/woocommerce.php
     * @return boolean True if active, false otherwise.
     */
    public function is_plugin_active( $plugin_basename ) {

        // Makes sure the plugin is defined before trying to use it
        if ( !function_exists( 'is_plugin_active' ) )
            include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

        return is_plugin_active( $plugin_basename );

    }

    /**
     * Send email.
     *
     * @since 3.0.0
     * @access public
     *
     * @param array  $recipients  Array of recipients emails.
     * @param string $subject     Email subject.
     * @param string $message     Email message.
     * @param array  $headers     Array of email headers.
     * @param array  $attachments Array of email attachments.
     * @return boolean True if email sending is triggered, note it does not mean that the email was received, it just denotes that the email sending is triggered. False if email sending is not triggered.
     */
    public function send_email( $recipients , $subject , $message , $headers = array() , $attachments = array() ) {

        $from_name  = apply_filters( 'ta_email_from_name' , get_bloginfo( 'name' ) );
        $from_email = apply_filters( 'ta_email_from_email' , get_option( 'admin_email' ) );

        $headers[] = 'From: ' . $from_name  . ' <' . $from_email . '>';
        $headers[] = 'Content-Type: text/html; charset=' . get_option( 'blog_charset' );

        return wp_mail( $recipients , $subject , $message , $headers , $attachments );

    }

    /**
     * Get Affiliate_Link data object.
     *
     * @since 3.0.0
     * @access public
     *
     * @param int $id Affiliate_Link post ID.
     * @return Affiliate_Link Affiliate Link object.
     */
    public function get_affiliate_link( $id = 0 ) {

        return new Affiliate_Link( $id );
    }

    /**
     * Retrieve all categories as an option array list.
     *
     * @since 3.0.0
     * @access public
     *
     * @return array List of category options.
     */
    public function get_all_category_as_options() {

        $options = array();

        $categories = get_terms( array(
            'taxonomy'   => Plugin_Constants::AFFILIATE_LINKS_TAX,
            'hide_empty' => false,
        ) );

        if ( ! is_wp_error( $categories ) ) {

            foreach( $categories as $category )
                $options[ $category->term_id ] = $category->name;

        } else {

            // TODO: Handle error

        }

        return $options;
    }

}
