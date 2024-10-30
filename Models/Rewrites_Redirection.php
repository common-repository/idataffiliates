<?php

namespace IDatAffiliates\Models;

use IDatAffiliates\Abstracts\Abstract_Main_Plugin_Class;

use IDatAffiliates\Interfaces\Model_Interface;

use IDatAffiliates\Helpers\Plugin_Constants;
use IDatAffiliates\Helpers\Helper_Functions;

/**
 * Model that houses the logic for permalink rewrites and affiliate link redirections.
 *
 * @since 3.0.0
 */
class Rewrites_Redirection implements Model_Interface {

    /*
    |--------------------------------------------------------------------------
    | Class Properties
    |--------------------------------------------------------------------------
    */

    /**
     * Property that holds the single main instance of Rewrites_Redirection.
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

    /**
     * Property that holds the currently loaded idatlink post.
     *
     * @since 3.0.0
     * @access private
     */
    private $_idatlink;




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
    | Flush Rewrite Rules
    |--------------------------------------------------------------------------
    */

    /**
     * Get idatlink Affiliate_Link object.
     *
     * @since 3.0.0
     * @access private
     *
     * @param int $post_id IDatlink post id.
     * @return Affiliate_Link object.
     */
    private function get_idatlink_post( $post_id ) {

        if ( is_object( $this->_idatlink ) && $this->_idatlink->get_id() == $post_id )
            return $this->_idatlink;

        return $this->_idatlink = new Affiliate_Link( $post_id );

    }

    /**
     * Set ta_flush_rewrite_rules transient value to true if the link prefix value has changed.
     *
     * @since 3.0.0
     * @access public
     *
     * @param string $new_value Option new value.
     * @param string $old_value Option old value.
     */
    public function set_flush_rewrite_rules_transient( $new_value , $old_value ) {

        if ( $new_value != $old_value )
            set_transient( 'ta_flush_rewrite_rules' , 'true' , 5 * 60 );

        return $new_value;

    }

    /**
     * Set rewrite tags and rules.
     *
     * @since 3.0.0
     * @access private
     *
     * @param string $link_prefix IDatlink post type slug.
     */
    public function set_rewrites( $link_prefix ) {

        add_rewrite_tag( '%' . $link_prefix . '%' , '([^&]+)' );
        add_rewrite_rule( "$link_prefix/([^/]+)?/?$" , 'index.php?idatlink=$matches[1]' , 'top' );

        if ( get_option( 'ta_show_cat_in_slug' ) === 'yes' ) {

            add_rewrite_tag( '%idatlink-category%' , '([^&]+)');
    		add_rewrite_rule( "$link_prefix/([^/]+)?/?([^/]+)?/?" , 'index.php?idatlink=$matches[2]&idatlink-category=$matches[1]' , 'top' );
        }
    }

    /**
     * Flush rewrite rules (soft) when the ta_flush_rewrite_rules transient is set to 'true'.
     *
     * @since 3.0.0
     * @access public
     */
    public function flush_rewrite_rules() {

        if ( 'true' !== get_transient( 'ta_flush_rewrite_rules' ) )
            return;

        flush_rewrite_rules( false );
        delete_transient( 'ta_flush_rewrite_rules' );
    }




    /*
    |--------------------------------------------------------------------------
    | Redirection Handler
    |--------------------------------------------------------------------------
    */

    /**
     * Handles redirect for idatlink link urls.
     *
     * @since 3.0.0
     * @access public
     */
    public function redirect_url() {

        global $post;

        if ( ! is_object( $post ) || $post->post_type != Plugin_Constants::AFFILIATE_LINKS_CPT )
            return;

        $idatlink   = $this->get_idatlink_post( $post->ID );
        $redirect_url  = html_entity_decode( $idatlink->get_prop( 'destination_url' ) );
        $redirect_type = $idatlink->get_prop( 'redirect_type' , get_option( 'ta_link_redirect_type' ) );

        // Apply any filters to the url and redirect type before redirecting
        $redirect_url  = apply_filters( 'ta_filter_redirect_url' , $redirect_url , $idatlink );
        $redirect_type = apply_filters( 'ta_filter_redirect_type' , $redirect_type , $idatlink );

        // perform actions before redirecting
        do_action( 'ta_before_link_redirect' , $idatlink , $redirect_url , $redirect_type );

        if ( $redirect_url && $redirect_type ) {

            wp_redirect( $redirect_url , intval( $redirect_type ) );
		    exit;
        }

    }

    /**
     * Pass query strings to destination url when option is enabled on settings.
     *
     * @since 3.0.0
     * @access public
     *
     * @param string $redirect_url Affiliate link destination url.
     */
    public function pass_query_string_to_destination_url( $redirect_url , $idatlink ) {

        $query_string   = isset( $_SERVER[ 'QUERY_STRING' ] ) ? $_SERVER[ 'QUERY_STRING' ] : '';
        $pass_query_str = $idatlink->get_prop( 'pass_query_str' ) == 'global' ? get_option( 'ta_pass_query_str' ) : $idatlink->get_prop( 'pass_query_str' );

        if ( ! $query_string || $pass_query_str !== 'yes' )
            return $redirect_url;

        $connector  = ( strpos( $redirect_url , '?' ) === false ) ? '?' : '&';

        return $redirect_url . $connector . $query_string;
    }




    /*
    |--------------------------------------------------------------------------
    | Fulfill implemented interface contracts
    |--------------------------------------------------------------------------
    */

    /**
     * Execute ajax handler.
     *
     * @since 3.0.0
     * @access public
     * @inherit IDatAffiliates\Interfaces\Model_Interface
     */
    public function run() {

        // flush rewrite rules
        add_filter( 'pre_update_option_ta_link_prefix' , array( $this , 'set_flush_rewrite_rules_transient' ) , 10 , 2 );
        add_filter( 'pre_update_option_ta_link_prefix_custom' , array( $this , 'set_flush_rewrite_rules_transient' ) , 10 , 2 );
        add_filter( 'pre_update_option_ta_show_cat_in_slug' , array( $this , 'set_flush_rewrite_rules_transient' ) , 10 , 2 );
        add_action( 'ta_after_register_idatlink_post_type' , array( $this , 'set_rewrites' ) , 1 , 1 );
        add_action( 'ta_after_register_idatlink_post_type' , array( $this , 'flush_rewrite_rules' ) );

        // redirection handler
        add_action( 'template_redirect' , array( $this , 'redirect_url' ) , 1 );

        // filter redirect url before redirecting
        add_filter( 'ta_filter_redirect_url' , array( $this , 'pass_query_string_to_destination_url' ) , 10 , 2 );
    }
}
