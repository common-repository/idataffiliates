<?php

namespace IDatAffiliates\Models;

use IDatAffiliates\Abstracts\Abstract_Main_Plugin_Class;

use IDatAffiliates\Interfaces\Model_Interface;

use IDatAffiliates\Helpers\Plugin_Constants;
use IDatAffiliates\Helpers\Helper_Functions;

use IDatAffiliates\Models\Affiliate_Link;

/**
 * Model that houses the link picker logic.
 *
 * @since 3.0.0
 */
class Link_Picker implements Model_Interface {

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
     * @var Link_Picker
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
     * @return Link_Picker
     */
    public static function get_instance( Abstract_Main_Plugin_Class $main_plugin , Plugin_Constants $constants , Helper_Functions $helper_functions ) {

        if ( !self::$_instance instanceof self )
            self::$_instance = new self( $main_plugin , $constants , $helper_functions );

        return self::$_instance;

    }



    /*
    |--------------------------------------------------------------------------
    | Register tinymce buttons and scripts
    |--------------------------------------------------------------------------
    */

    /**
     * Initialize idat editor buttons.
     *
     * @since 3.0.0
     * @access public
     */
    public function init_idat_editor_buttons() {

        if ( ! current_user_can( 'edit_posts' ) || ! current_user_can( 'edit_pages' ) )
		    return;

        if ( get_option( 'ta_disable_visual_editor_buttons' ) == 'yes' || get_user_option( 'rich_editing' ) != 'true' )
            return;

        add_filter( 'mce_external_plugins' , array( $this , 'load_idat_mce_plugin' ) );
		add_filter( 'mce_buttons' , array( $this , 'register_mce_buttons' ) , 5 );

    }

    /**
     * Load IDat Affiliate MCE plugin to TinyMCE.
     *
     * @since 3.0.0
     * @access public
     *
     * @param array $mce_plugins Array of all MCE plugins.
     * @return array
     */
    public function load_idat_mce_plugin( $mce_plugins ) {

        $mce_plugins[ 'idataffiliates' ] = $this->_constants->JS_ROOT_URL() . 'lib/idatmce/editor-plugin.js';

	    return $mce_plugins;
    }

    /**
     * Register IDat Affiliate MCE buttons.
     *
     * @since 3.0.0
     * @access public
     *
     * @param array $buttons Array of all MCE buttons.
     * @return array
     */
    public function register_mce_buttons( $buttons ) {

        array_push( $buttons , 'separator' , 'idataffiliates_button' );
    	array_push( $buttons , 'separator' , 'idataffiliates_quickaddlink_button' );

    	return $buttons;
    }




    /*
    |--------------------------------------------------------------------------
    | Link Picker methods
    |--------------------------------------------------------------------------
    */

    /**
     * Return
     *
     * @since 3.0.0
     * @access public
     *
     * @param array  $affiliate_links List of affiliate link IDs.
     * @param bool   $advanced        Boolean check if its advanced or not.
     * @param int    $post_id         ID of the post currently being edited.
     * @param string $result_markup   Search Affiliate Links result markup.
     * @return Search Affiliate Links result markup
     */
    public function search_affiliate_links_result_markup( $affiliate_links , $advance = false , $post_id = 0 ,  $result_markup = '' ) {

        if ( is_array( $affiliate_links ) && ! empty( $affiliate_links ) ) {

            foreach( $affiliate_links as $link_id ) {

                $idatlink = new Affiliate_Link( $link_id );
                $nofollow    = $idatlink->get_prop( 'no_follow' ) == 'global' ? get_option( 'ta_no_follow' ) : $idatlink->get_prop( 'no_follow' );
                $new_window  = $idatlink->get_prop( 'new_window' ) == 'global' ? get_option( 'ta_new_window' ) : $idatlink->get_prop( 'new_window' );
                $rel         = $nofollow == 'yes' ? 'nofollow' : '';
                $rel        .= ' ' . $idatlink->get_prop( 'rel_tags' );
                $target      = $new_window == 'yes' ? '_blank' : '';
                $class       = ( get_option( 'ta_disable_idat_link_class' ) !== "yes" ) ? 'idatlink' : '';
                $title       = ( get_option( 'ta_disable_title_attribute' ) !== "yes" ) ? $idatlink->get_prop( 'name' ) : '';
                $other_atts  = esc_attr( json_encode( apply_filters( 'ta_link_insert_extend_data_attributes' , array() , $idatlink , $post_id ) ) );


                if ( $advance ) {

                    $images        = $idatlink->get_prop( 'image_ids' );
                    $images_markup = '<span class="images-block">';

                    if ( is_array( $images ) && ! empty( $images ) ) {

                        $images_markup .= '<span class="label">' . __( 'Select image:' , 'idataffiliates' ) . '</span>';
                        $images_markup .= '<span class="images">';

                        foreach( $images as $image )
                            $images_markup .= wp_get_attachment_image( $image , array( 75 , 75 ) , false , array( 'data-imgid' => $image , 'data-type' => 'image' ) );

                        $images_markup .= '</span>';
                    } else {

                        $images_markup .= '<span class="no-images">' . __( 'No images found' , 'idataffiliates' ) . '</span>';
                    }

                    $images_markup .= '</span>';

                    $result_markup .= '<li class="idatlink"
                                            data-linkid="' . $idatlink->get_id() . '"
                                            data-class="' . esc_attr( $class ) . '"
                                            data-title="' . esc_attr( str_replace( '"' , '' , $title ) ) . '"
                                            data-href="' . esc_url( $idatlink->get_prop( 'permalink' ) ) . '"
                                            data-rel="' . trim( esc_attr( $rel ) ) . '"
                                            data-target="' . esc_attr( $target ) . '"
                                            data-other-atts="' . esc_attr( $other_atts ) . '">
                                            <span class="name">' . $idatlink->get_prop( 'name' ) . '</span>
                                            <span class="slug">[' . $idatlink->get_prop( 'slug' ) . ']</span>
                                            <span class="actions">
                                                <button type="button" data-type="normal" class="button insert-link-button dashicons dashicons-admin-links" data-tip="' . __( 'Insert link' , 'idataffiliates' ) . '"></button>
                                                <button type="button" data-type="shortcode" class="button insert-shortcode-button dashicons dashicons-editor-code" data-tip="' . __( 'Insert shortcode' , 'idataffiliates' ) . '"></button>
                                                <button type="button" data-type="image" class="button insert-image-button dashicons dashicons-format-image" data-tip="' . __( 'Insert image' , 'idataffiliates' ) . '"></button>
                                            </span>
                                            ' . $images_markup . '
                                        </li>';
                } else {

                    $result_markup .= '<li data-class="' . esc_attr( $class ) . '"
                                           data-title="' . esc_attr( str_replace( '"' , '' , $title ) ) . '"
                                           data-href="' . esc_attr( $idatlink->get_prop( 'permalink' ) ) . '"
                                           data-rel="' . esc_attr( $rel ) . '"
                                           data-target="' . esc_attr( $target ) . '"
                                           data-link-id="' . esc_attr( $idatlink->get_id() ) . '"
                                           data-link-insertion-type="' . esc_attr( get_option( 'ta_link_insertion_type' , 'link' ) ) . '"
                                           data-other-atts="' . $other_atts . '">';
                    $result_markup .= '<strong>' . $link_id . '</strong> : <span>' . $idatlink->get_prop( 'name' ) . '</span></li>';

                }

            }

        } else
            $result_markup .= '<li class="no-links-found">' . __( 'No affiliate links found' , 'idataffiliates' ) . '</li>';

        return $result_markup;
    }

    /**
     * Search Affiliate Links Query AJAX function
     *
     * @since 3.0.0
     * @access public
     */
    public function ajax_search_affiliate_links_query() {

        if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX )
            $response = array( 'status' => 'fail' , 'error_msg' => __( 'Invalid AJAX call' , 'idataffiliates' ) );
        elseif ( ! isset( $_POST[ 'keyword' ] ) )
            $response = array( 'status' => 'fail' , 'error_msg' => __( 'Missing required post data' , 'idataffiliates' ) );
        else {

            $paged           = ( isset( $_POST[ 'paged' ] ) && $_POST[ 'paged' ] ) ? $_POST[ 'paged' ] : 1;
            $exclude         = ( isset( $_POST[ 'exclude' ] ) && is_array( $_POST[ 'exclude' ] ) && ! empty( $_POST[ 'exclude' ] ) ) ? $_POST[ 'exclude' ] : array();
            $affiliate_links = $this->_helper_functions->search_affiliate_links_query( $_POST[ 'keyword' ] , $paged , '' , $exclude );
            $advance         = ( isset( $_POST[ 'advance' ] ) && $_POST[ 'advance' ] ) ? true : false;
            $post_id         = isset( $_POST[ 'post_id' ] ) ? intval( $_POST[ 'post_id' ] ) : 0;
            $result_markup   = $this->search_affiliate_links_result_markup( $affiliate_links , $advance , $post_id );

            $response = array( 'status' => 'success' , 'search_query_markup' => $result_markup , 'count' => count( $affiliate_links ) );
        }

        @header( 'Content-Type: application/json; charset=' . get_option( 'blog_charset' ) );
        echo wp_json_encode( $response );
        wp_die();
    }

    /**
     * AJAX function to display the advance add affiliate link thickbox content.
     *
     * @since 3.0.0
     * @access public
     */
    public function ajax_display_advanced_add_affiliate_link() {

        if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX || ! current_user_can( apply_filters( 'ta_ajax_access_capability' , 'edit_posts' ) ) )
            wp_die();

        $post_id         = isset( $_REQUEST[ 'post_id' ] ) ? intval( $_REQUEST[ 'post_id' ] ) : 0;
        $affiliate_links = $this->_helper_functions->search_affiliate_links_query();
        $result_markup   = $this->search_affiliate_links_result_markup( $affiliate_links , true , $post_id );
        $html_editor     = isset( $_REQUEST[ 'html_editor' ] ) ? sanitize_text_field( $_REQUEST[ 'html_editor' ] ) : false;

        wp_enqueue_script('editor');
		wp_dequeue_script('jquery-ui-sortable');
		wp_dequeue_script('admin-scripts');
        wp_enqueue_style( 'jquery_tiptip' , $this->_constants->CSS_ROOT_URL() . 'lib/jquery-tiptip/jquery-tiptip.css' , array() , $this->_constants->VERSION() , 'all' );
        wp_enqueue_style( 'ta_advance_link_picker_css' , $this->_constants->JS_ROOT_URL() . 'app/advance_link_picker/dist/advance-link-picker.css' , array( 'dashicons' ) , $this->_constants->VERSION() , 'all' );
        wp_enqueue_script( 'jquery_tiptip' , $this->_constants->JS_ROOT_URL() . 'lib/jquery-tiptip/jquery.tipTip.min.js' , array() , $this->_constants->VERSION() );
        wp_enqueue_script( 'ta_advance_link_picker_js' , $this->_constants->JS_ROOT_URL() . 'app/advance_link_picker/dist/advance-link-picker.js' , array( 'jquery_tiptip' ) , $this->_constants->VERSION() );

        include( $this->_constants->VIEWS_ROOT_PATH() . 'linkpicker/advance-link-picker.php' );

        wp_die();
    }

    /**
     * Get image markup by ID.
     *
     * @since 3.0.0
     * @access public
     */
    public function ajax_get_image_markup_by_id() {

        if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX )
            $response = array( 'status' => 'fail' , 'error_msg' => __( 'Invalid AJAX call' , 'idataffiliates' ) );
        elseif ( ! isset( $_REQUEST[ 'imgid' ] ) )
            $response = array( 'status' => 'fail' , 'error_msg' => __( 'Missing required post data' , 'idataffiliates' ) );
        else {

            $image_id = (int) sanitize_text_field( $_REQUEST[ 'imgid' ] );
            $image_markup = wp_get_attachment_image( $image_id , 'full' );

            $response = array( 'status' => 'success' , 'image_markup' => $image_markup );
        }

        @header( 'Content-Type: application/json; charset=' . get_option( 'blog_charset' ) );
        echo wp_json_encode( $response );
        wp_die();
    }





    /*
    |--------------------------------------------------------------------------
    | Quick Add Affiliate Link methods
    |--------------------------------------------------------------------------
    */

    /**
     * Display the quick add affiliate link content on the thickbox popup.
     *
     * @since 3.0.0
     * @access public
     */
    public function ajax_display_quick_add_affiliate_link_thickbox() {

        if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX || ! current_user_can( apply_filters( 'ta_ajax_access_capability' , 'edit_posts' ) ) )
            wp_die();

        $post_id               = isset( $_REQUEST[ 'post_id' ] ) ? intval( $_REQUEST[ 'post_id' ] ) : 0;
        $redirect_types        = $this->_constants->REDIRECT_TYPES();
        $selection             = sanitize_text_field( $_REQUEST[ 'selection' ] );
        $default_redirect_type = get_option( 'ta_link_redirect_type' , '301' );
        $global_no_follow      = get_option( 'ta_no_follow' ) == 'yes' ? 'yes' : 'no';
        $global_new_window     = get_option( 'ta_new_window' ) == 'yes' ? 'yes' : 'no';
        $html_editor           = isset( $_REQUEST[ 'html_editor' ] ) ? sanitize_text_field( $_REQUEST[ 'html_editor' ] ) : false;

        wp_enqueue_script('editor');
		wp_dequeue_script('jquery-ui-sortable');
		wp_dequeue_script('admin-scripts');
        wp_enqueue_script( 'ta_quick_add_affiliate_link_js' , $this->_constants->JS_ROOT_URL() . 'app/quick_add_affiliate_link/dist/quick-add-affiliate-link.js' , array() , $this->_constants->VERSION() );
        wp_enqueue_style( 'ta_quick_add_affiliate_link_css' , $this->_constants->JS_ROOT_URL() . 'app/quick_add_affiliate_link/dist/quick-add-affiliate-link.css' , array( 'dashicons' ) , $this->_constants->VERSION() , 'all' );

        include( $this->_constants->VIEWS_ROOT_PATH() . 'linkpicker/quick-add-affiliate-link.php' );

        wp_die();

    }

    /**
     * Process quick add affiliate link. Create Affiliate link post.
     *
     * @since 3.0.0
     * @access public
     */
    public function process_quick_add_affiliate_link() {

        $idatlink = new Affiliate_Link();

        // set Properties
        $idatlink->set_prop( 'name' , sanitize_text_field( $_POST[ 'ta_link_name' ] ) );
        $idatlink->set_prop( 'destination_url' , esc_url_raw( $_POST[ 'ta_destination_url' ] ) );
        $idatlink->set_prop( 'no_follow' , sanitize_text_field( $_POST[ 'ta_no_follow' ] ) );
        $idatlink->set_prop( 'new_window' , sanitize_text_field( $_POST[ 'ta_new_window' ] ) );
        $idatlink->set_prop( 'redirect_type' , sanitize_text_field( $_POST[ 'ta_redirect_type' ] ) );

        add_action( 'ta_save_quick_add_affiliate_link' , $idatlink );

        // save affiliate link
        $idatlink->save();

        return $idatlink;
    }

    /**
     * AJAX function to process quick add affiliate link.
     *
     * @since 3.0.0
     * @access public
     */
    public function ajax_process_quick_add_affiliate_link() {

        if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX )
            $response = array( 'status' => 'fail' , 'error_msg' => __( 'Invalid AJAX call' , 'idataffiliates' ) );
        elseif ( ! isset( $_REQUEST[ 'ta_link_name' ] ) || ! isset( $_REQUEST[ 'ta_destination_url' ] ) || ! isset( $_REQUEST[ 'ta_redirect_type' ] ) )
            $response = array( 'status' => 'fail' , 'error_msg' => __( 'Missing required post data' , 'idataffiliates' ) );
        else {

            $idatlink = $this->process_quick_add_affiliate_link();
            $post_id     = isset( $_POST[ 'post_id' ] ) ? intval( sanitize_text_field( $_POST[ 'post_id' ] ) ) : 0;
            $nofollow    = $idatlink->get_prop( 'no_follow' ) == 'global' ? get_option( 'ta_no_follow' ) : $idatlink->get_prop( 'no_follow' );
            $new_window  = $idatlink->get_prop( 'new_window' ) == 'global' ? get_option( 'ta_new_window' ) : $idatlink->get_prop( 'new_window' );
            $rel         = $nofollow == 'yes' ? 'nofollow' : '';
            $rel        .= ' ' . $idatlink->get_prop( 'rel_tags' );
            $target      = $new_window == 'yes' ? '_blank' : '';
            $class       = ( get_option( 'ta_disable_idat_link_class' ) !== "yes" ) ? 'idatlink' : '';
            $title       = ( get_option( 'ta_disable_title_attribute' ) !== "yes" ) ? $idatlink->get_prop( 'name' ) : '';

            $response = array(
                'status'              => 'success',
                'link_id'             => $idatlink->get_id(),
                'content'             => $idatlink->get_prop( 'name' ),
                'href'                => $idatlink->get_prop( 'permalink' ),
                'class'               => $class,
                'title'               => str_replace( '"' , '' , $title ),
                'rel'                 => $rel,
                'target'              => $target,
                'link_insertion_type' => get_option( 'ta_link_insertion_type' , 'link' ),
                'other_atts'          => apply_filters( 'ta_link_insert_extend_data_attributes' , array() , $idatlink , $post_id )
            );

        }

        @header( 'Content-Type: application/json; charset=' . get_option( 'blog_charset' ) );
        echo wp_json_encode( $response );
        wp_die();
    }

    /**
     * Execute link picker.
     *
     * @since 3.0.0
     * @access public
     */
    public function run() {

        // TinyMCE buttons
        add_action( 'init' , array( $this , 'init_idat_editor_buttons' ) );

        // Advanced Link Picker hooks
        add_action( 'wp_ajax_search_affiliate_links_query' , array( $this , 'ajax_search_affiliate_links_query' ) );
        add_action( 'wp_ajax_ta_advanced_add_affiliate_link' , array( $this , 'ajax_display_advanced_add_affiliate_link' ) );
        add_action( 'wp_ajax_ta_get_image_markup_by_id' , array( $this , 'ajax_get_image_markup_by_id' ) );

        // Quick Add Affiliate Link hooks
        add_action( 'wp_ajax_ta_quick_add_affiliate_link_thickbox' , array( $this , 'ajax_display_quick_add_affiliate_link_thickbox' ) );
        add_action( 'wp_ajax_ta_process_quick_add_affiliate_link' , array( $this , 'ajax_process_quick_add_affiliate_link' ) );

    }
}
