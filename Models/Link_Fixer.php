<?php

namespace IDatAffiliates\Models;

use IDatAffiliates\Abstracts\Abstract_Main_Plugin_Class;

use IDatAffiliates\Interfaces\Model_Interface;
use IDatAffiliates\Interfaces\Initiable_Interface;

use IDatAffiliates\Helpers\Plugin_Constants;
use IDatAffiliates\Helpers\Helper_Functions;

use IDatAffiliates\Models\Affiliate_Link;

/**
 * Model that houses the link fixer logic.
 *
 * @since 3.0.0
 */
class Link_Fixer implements Model_Interface , Initiable_Interface {

    /*
    |--------------------------------------------------------------------------
    | Class Properties
    |--------------------------------------------------------------------------
    */

    /**
     * Property that holds the single main instance of Bootstrap.
     *
     * @since 3.0.0
     * @access private
     * @var Link_Fixer
     */
    private static $_instance;

    /**
     * Model that houses the main plugin object.
     *
     * @since 3.0.0
     * @access private
     * @var Abstract_Main_Plugin_Class
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
     * @return Link_Fixer
     */
    public static function get_instance( Abstract_Main_Plugin_Class $main_plugin , Plugin_Constants $constants , Helper_Functions $helper_functions ) {

        if ( !self::$_instance instanceof self )
            self::$_instance = new self( $main_plugin , $constants , $helper_functions );

        return self::$_instance;

    }

    /**
     * Get data of links to be fixed.
     *
     * @since 3.0.0
     * @access public
     *
     * @param array $links   List of affiliate links to fix.
     * @param int   $post_id ID of the post currently being viewed.
     * @param array $data    Affiliate Links data.
     * @return array Affiliate Links data.
     */
    public function get_link_fixer_data( $links , $post_id = 0 , $data = array() ) {

        if ( empty( $links ) )
            return $data;

        foreach( $links as $link ) {

            $href    = esc_url_raw( $link[ 'href' ] );
            $class   = isset( $link[ 'class' ] ) ? sanitize_text_field( $link[ 'class' ] ) : '';
            $key     = (int) sanitize_text_field( $link[ 'key' ] );
            $link_id = url_to_postid( $href );

            $idatlink = new Affiliate_Link( $link_id );

            if ( ! $idatlink->get_id() )
                continue;

            $class      = str_replace( 'idatlinkimg' , 'idatlink' , $class );
            $class     .= ( get_option( 'ta_disable_idat_link_class' ) !== "yes" && strpos( $class , 'idatlink' ) === false ) ? ' idatlink' : '';
            $nofollow   = $idatlink->get_prop( 'no_follow' ) == 'global' ? get_option( 'ta_no_follow' ) : $idatlink->get_prop( 'no_follow' );
            $new_window = $idatlink->get_prop( 'new_window' ) == 'global' ? get_option( 'ta_new_window' ) : $idatlink->get_prop( 'new_window' );
            $href       = ( $this->_helper_functions->is_uncloak_link( $idatlink ) ) ? apply_filters( 'ta_uncloak_link_url' , $idatlink->get_prop( 'destination_url' ) , $idatlink ) : $idatlink->get_prop( 'permalink' );
            $rel        = $nofollow == "yes" ? 'nofollow' : '';
            $rel       .= ' ' . $idatlink->get_prop( 'rel_tags' );
            $target     = $new_window == "yes" ? '_blank' : '';
            $title      = get_option( 'ta_disable_title_attribute' ) != 'yes' ? esc_attr( str_replace( '"' , '' , $idatlink->get_prop( 'name' ) ) ) : '';
            $title      = str_replace( '&#039;' , '\'' , $title );

            if ( $link[ 'is_image' ] )
                $class = str_replace( 'idatlink' , 'idatlinkimg' , $class );

            $data[] = array(
                'key'     => $key,
                'link_id' => $link_id,
                'class'   => esc_attr( trim( $class ) ),
                'href'    => esc_url( $href ),
                'rel'     => esc_attr( trim( $rel ) ),
                'target'  => esc_attr( $target ),
                'title'   => $title
            );
        }

        return $data;
    }

    /**
     * Ajax link fixer.
     *
     * @since 3.0.0
     * @access public
     */
    public function ajax_link_fixer() {

        if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX )
            $response = array( 'status' => 'fail' , 'error_msg' => __( 'Invalid AJAX call' , 'idataffiliates' ) );
        elseif ( ! isset( $_POST[ 'hrefs' ] ) || empty( $_POST[ 'hrefs' ] ) )
            $response = array( 'status' => 'fail' , 'error_msg' => __( 'Invalid AJAX call' , 'idataffiliates' ) );
        else {

            $links    = $_POST[ 'hrefs' ];
            $post_id  = isset( $_POST[ 'post_id' ] ) ? intval( $_POST[ 'post_id' ] ) : 0;
            $response = array(
                'status' => 'success',
                'data' => $this->get_link_fixer_data( $links , $post_id )
            );
        }

        @header( 'Content-Type: application/json; charset=' . get_option( 'blog_charset' ) );
        echo wp_json_encode( $response );
        wp_die();
    }




    /*
    |--------------------------------------------------------------------------
    | Fulfill implemented interface contracts
    |--------------------------------------------------------------------------
    */

    /**
     * Execute codes that needs to run on plugin initialization.
     *
     * @since 3.0.0
     * @access public
     * @implements IDatAffiliates\Interfaces\Initiable_Interface
     */
    public function initialize() {

        add_action( 'wp_ajax_ta_link_fixer' , array( $this , 'ajax_link_fixer' ) );
        add_action( 'wp_ajax_nopriv_ta_link_fixer' , array( $this , 'ajax_link_fixer' ) );
    }

    /**
     * Execute link picker.
     *
     * @since 3.0.0
     * @access public
     */
    public function run() {
    }
}
