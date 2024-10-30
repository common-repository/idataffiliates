<?php

namespace IDatAffiliates\Models;

use IDatAffiliates\Abstracts\Abstract_Main_Plugin_Class;

use IDatAffiliates\Interfaces\Model_Interface;
use IDatAffiliates\Interfaces\Initiable_Interface;

use IDatAffiliates\Helpers\Plugin_Constants;
use IDatAffiliates\Helpers\Helper_Functions;

// Data Models
use IDatAffiliates\Models\Affiliate_Link;

/**
 * Model that houses the logic of registering the 'idatlink' custom post type.
 *
 * @since 3.0.0
 */
class Affiliate_Links_CPT implements Model_Interface , Initiable_Interface {

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
     * @var Affiliate_Links_CPT
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
     * @return Affiliate_Links_CPT
     */
    public static function get_instance( Abstract_Main_Plugin_Class $main_plugin , Plugin_Constants $constants , Helper_Functions $helper_functions ) {

        if ( !self::$_instance instanceof self )
            self::$_instance = new self( $main_plugin , $constants , $helper_functions );

        return self::$_instance;

    }

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
     * Register the 'idatlink' custom post type.
     *
     * @since 3.0.0
     * @access private
     */
    private function register_idatlink_custom_post_type() {

        $link_prefix = $this->_helper_functions->get_idatlink_link_prefix();

        $labels = array(
            'name'                => __( 'Affiliate Links' , 'idataffiliates' ),
            'singular_name'       => __( 'Affiliate Link' , 'idataffiliates' ),
            'menu_name'           => __( 'IDatAffiliates' , 'idataffiliates' ),
            'parent_item_colon'   => __( 'Parent Affiliate Link' , 'idataffiliates' ),
            'all_items'           => __( 'Affiliate Links' , 'idataffiliates' ),
            'view_item'           => __( 'View Affiliate Link' , 'idataffiliates' ),
            'add_new_item'        => __( 'Add Affiliate Link' , 'idataffiliates' ),
            'add_new'             => __( 'New Affiliate Link' , 'idataffiliates' ),
            'edit_item'           => __( 'Edit Affiliate Link' , 'idataffiliates' ),
            'update_item'         => __( 'Update Affiliate Link' , 'idataffiliates' ),
            'search_items'        => __( 'Search Affiliate Links' , 'idataffiliates' ),
            'not_found'           => __( 'No Affiliate Link found' , 'idataffiliates' ),
            'not_found_in_trash'  => __( 'No Affiliate Links found in Trash' , 'idataffiliates' )
        );

        $args = array(
            'label'               => __( 'Affiliate Links' , 'idataffiliates' ),
            'description'         => __( 'IDatAffiliates affiliate links' , 'idataffiliates' ),
            'labels'              => $labels,
            'supports'            => array( 'title' ),
            'taxonomies'          => array(),
            'hierarchical'        => true,
            'public'              => true,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'show_in_json'        => false,
            'query_var'           => true,
            'rewrite'             => array(
                'slug'       => $link_prefix,
				'with_front' => false,
				'pages'      => false
            ),
            'show_in_nav_menus'   => true,
            'show_in_admin_bar'   => true,
            'menu_position'       => 26,
            'menu_icon' => 'dashicons-admin-links',
            'can_export'          => true,
            'has_archive'         => false,
            'exclude_from_search' => true,
            'publicly_queryable'  => true,
            'capability_type'     => 'post'
        );

        register_post_type( Plugin_Constants::AFFILIATE_LINKS_CPT , apply_filters( 'ta_affiliate_links_cpt_args' , $args , $labels ) );

        do_action( 'ta_after_register_idatlink_post_type' , $link_prefix );
    }

    /**
     * Register the 'idatlink-category' custom taxonomy.
     *
     * @since 3.0.0
     * @access private
     */
    private function register_idatlink_category_custom_taxonomy() {

        $labels = array(
    		'name'                       => __( 'Link Categories', 'idataffiliates' ),
    		'singular_name'              => __( 'Link Category', 'idataffiliates' ),
    		'menu_name'                  => __( 'Link Categories', 'idataffiliates' ),
    		'all_items'                  => __( 'All Categories', 'idataffiliates' ),
    		'parent_item'                => __( 'Parent Category', 'idataffiliates' ),
    		'parent_item_colon'          => __( 'Parent Category:', 'idataffiliates' ),
    		'new_item_name'              => __( 'New Category Name', 'idataffiliates' ),
    		'add_new_item'               => __( 'Add New Category', 'idataffiliates' ),
    		'edit_item'                  => __( 'Edit Category', 'idataffiliates' ),
    		'update_item'                => __( 'Update Category', 'idataffiliates' ),
    		'view_item'                  => __( 'View Category', 'idataffiliates' ),
    		'separate_items_with_commas' => __( 'Separate items with commas', 'idataffiliates' ),
    		'add_or_remove_items'        => __( 'Add or remove items', 'idataffiliates' ),
    		'choose_from_most_used'      => __( 'Choose from the most used', 'idataffiliates' ),
    		'popular_items'              => __( 'Popular Categories', 'idataffiliates' ),
    		'search_items'               => __( 'Search Categories', 'idataffiliates' ),
    		'not_found'                  => __( 'Not Found', 'idataffiliates' ),
    		'no_terms'                   => __( 'No items', 'idataffiliates' ),
    		'items_list'                 => __( 'Category list', 'idataffiliates' ),
    		'items_list_navigation'      => __( 'Category list navigation', 'idataffiliates' )
    	);

    	$args = array(
    		'labels'                     => $labels,
    		'hierarchical'               => true,
    		'public'                     => true,
    		'show_ui'                    => true,
    		'show_admin_column'          => true,
    		'show_in_nav_menus'          => true,
    		'show_tagcloud'              => false,
            'rewrite'                    => false
    	);

    	register_taxonomy( Plugin_Constants::AFFILIATE_LINKS_TAX , Plugin_Constants::AFFILIATE_LINKS_CPT , apply_filters( 'ta_affiliate_link_taxonomy_args' , $args , $labels ) );

    }

    /**
     * Replace default post type permalink html with affiliate link ID.
     *
     * @since 3.0.0
     * @access public
     *
     * @param string $html    Permalink html.
     * @param int    $post_id Affiliate Link post id.
     * @return string Link ID html.
     */
    public function replace_permalink_with_id( $html , $post_id ) {

        if ( get_post_type( $post_id ) == Plugin_Constants::AFFILIATE_LINKS_CPT )
            return '<span id="link_id">' . __( 'Link ID:' , 'idataffiliates' ) . ' <strong>' . $post_id . '</strong></span>';

        return $html;
    }

    /**
     * Register metaboxes
     *
     * @since 3.0.0
     * @access public
     */
    public function register_metaboxes() {

        // normal
        add_meta_box( 'ta-urls-metabox', __( 'URL (s)', 'idataffiliates' ), array( $this , 'urls_metabox' ) , Plugin_Constants::AFFILIATE_LINKS_CPT , 'normal' );
        add_meta_box( 'ta-link-options-metabox', __( 'Link Options', 'idataffiliates' ), array( $this , 'link_options_metabox' ) , Plugin_Constants::AFFILIATE_LINKS_CPT , 'normal' );
        add_meta_box( 'ta-attach-images-metabox', __( 'Attach Featured Image', 'idataffiliates' ), array( $this , 'attach_images_metabox' ) , Plugin_Constants::AFFILIATE_LINKS_CPT , 'normal' );

        // side
        add_meta_box( 'ta-save-affiliate-link-metabox-side', __( 'Save Affiliate Link', 'idataffiliates' ), array( $this , 'save_affiliate_link_metabox' ) , Plugin_Constants::AFFILIATE_LINKS_CPT , 'side' , 'high' );
       // add_meta_box( 'ta-link-options-metabox', __( 'Link Options', 'idataffiliates' ), array( $this , 'link_options_metabox' ) , Plugin_Constants::AFFILIATE_LINKS_CPT , 'side' );

        // remove
        remove_meta_box( 'submitdiv', Plugin_Constants::AFFILIATE_LINKS_CPT, 'side' );

    }

    /**
     * Display "URls" metabox
     *
     * @since 3.0.0
     * @access public
     *
     * @param WP_Post $post Affiliate link WP_Post object.
     */
    public function urls_metabox( $post ) {

        $screen           = get_current_screen();
        $idatlink      = $this->get_idatlink_post( $post->ID );
        $home_link_prefix = home_url( user_trailingslashit( $this->_helper_functions->get_idatlink_link_prefix() ) );
        $default_cat_slug = $this->_helper_functions->get_default_category_slug( $post->ID );

        include_once( $this->_constants->VIEWS_ROOT_PATH() . 'cpt/view-urls-metabox.php' );

    }

    /**
     * Display "Attach Images" metabox
     *
     * @since 3.0.0
     * @access public
     *
     * @param WP_Post $post Affiliate link WP_Post object.
     */
    public function attach_images_metabox( $post ) {

        $idatlink     = $this->get_idatlink_post( $post->ID );
        $legacy_uploader = get_option( 'ta_legacy_uploader', 'no' );
        $attachments     = $idatlink->get_prop( 'image_ids' );

        include_once( $this->_constants->VIEWS_ROOT_PATH() . 'cpt/view-attach-images-metabox.php' );

    }

    /**
     * Display "Redirect Type" metabox
     *
     * @since 3.0.0
     * @access public
     *
     * @param WP_Post $post Affiliate link WP_Post object.
     */
    public function link_options_metabox( $post ) {

        $idatlink           = $this->get_idatlink_post( $post->ID );
        $default_redirect_type = get_option( 'ta_link_redirect_type' , '301' );
        $post_redirect_type    = $idatlink->get_prop( 'redirect_type' , $default_redirect_type );
        $redirect_types        = $this->_constants->REDIRECT_TYPES();
        $global_no_follow      = get_option( 'ta_no_follow' ) == 'yes' ? 'yes' : 'no';
        $global_new_window     = get_option( 'ta_new_window' ) == 'yes' ? 'yes' : 'no';
        $global_pass_query_str = get_option( 'ta_pass_query_str' ) == 'yes' ? 'yes' : 'no';
        $global_uncloak        = $idatlink->get_global_uncloak_value();
        $rel_tags              = get_post_meta( $post->ID , Plugin_Constants::META_DATA_PREFIX . 'rel_tags' , true );
        $global_rel_tags       = get_option( 'ta_additional_rel_tags' );

        include_once( $this->_constants->VIEWS_ROOT_PATH() . 'cpt/view-link-options-metabox.php' );

    }

    /**
     * Display "Save Affiliate Link" metabox
     *
     * @since 3.0.0
     * @access public
     *
     * @param WP_Post $post Affiliate link WP_Post object.
     */
    public function save_affiliate_link_metabox( $post ) {

        include( $this->_constants->VIEWS_ROOT_PATH() . 'cpt/view-save-affiliate-link-metabox.php' );

    }

    /**
     * Save idatlink post.
     *
     * @since 3.0.0
     * @access public
     *
     * @param int $post_id Affiliate link post ID.
     */
    public function save_post( $post_id ) {

        if ( ! isset( $_POST[ '_idataffiliates_nonce' ] ) || ! wp_verify_nonce( $_POST['_idataffiliates_nonce'], 'idat_affiliates_cpt_nonce' ) )
            return;

        // remove save_post hooked action to prevent infinite loop
        remove_action( 'save_post' , array( $this , 'save_post' ) );

        $idatlink = $this->get_idatlink_post( $post_id );

        // set Properties
        $idatlink->set_prop( 'destination_url' , esc_url_raw( $_POST[ 'ta_destination_url' ] ) );
        $idatlink->set_prop( 'no_follow' , sanitize_text_field( $_POST[ 'ta_no_follow' ] ) );
        $idatlink->set_prop( 'new_window' , sanitize_text_field( $_POST[ 'ta_new_window' ] ) );
        $idatlink->set_prop( 'pass_query_str' , sanitize_text_field( $_POST[ 'ta_pass_query_str' ] ) );
        $idatlink->set_prop( 'redirect_type' , sanitize_text_field( $_POST[ 'ta_redirect_type' ] ) );
        $idatlink->set_prop( 'rel_tags' , sanitize_text_field( $_POST[ 'ta_rel_tags' ] ) );

        if ( isset( $_POST[ 'ta_uncloak_link' ] ) )
            $idatlink->set_prop( 'uncloak_link' , sanitize_text_field( $_POST[ 'ta_uncloak_link' ] ) );

        if ( isset( $_POST[ 'ta_category_slug' ] ) && $_POST[ 'ta_category_slug' ] ) {

            $category_slug_id = (int) sanitize_text_field( $_POST[ 'ta_category_slug' ] );
            $category_slug    = get_term( $category_slug_id , Plugin_Constants::AFFILIATE_LINKS_TAX );
            $idatlink->set_prop( 'category_slug_id' , $category_slug_id );
            $idatlink->set_prop( 'category_slug' , $category_slug->slug );

        } else {

            $idatlink->set_prop( 'category_slug_id' , 0 );
            $idatlink->set_prop( 'category_slug' , '' );
        }

        do_action( 'ta_save_affiliate_link_post' , $idatlink , $post_id );

        // save affiliate link
        $idatlink->save();

        // set default term
        $this->save_default_affiliate_link_category( $post_id );

        // add back save_post hooked action after saving
        add_action( 'save_post' , array( $this , 'save_post' ) );

        do_action( 'ta_after_save_affiliate_link_post' , $post_id , $idatlink );
    }

    /**
     * Set default term when affiliate link is saved.
     *
     * @since 3.0.0
     * @access public
     *
     * @param int $post_id Affiliate link post ID.
     */
    public function save_default_affiliate_link_category( $post_id ) {

        $default_category = Plugin_Constants::DEFAULT_LINK_CATEGORY;
        $taxonomy_slug    = Plugin_Constants::AFFILIATE_LINKS_TAX;

        if ( get_option( 'ta_disable_cat_auto_select' ) == 'yes' || get_the_terms( $post_id , $taxonomy_slug ) )
            return;

        // create the default term if it doesn't exist
        if ( ! term_exists( $default_category , $taxonomy_slug ) )
            wp_insert_term( $default_category , $taxonomy_slug );

        $default_term = get_term_by( 'name' , $default_category , $taxonomy_slug );

        wp_set_post_terms( $post_id , $default_term->term_id , $taxonomy_slug );
    }

    /**
     * Add custom column to idat link listings (Link ID).
     *
     * @since 3.0.0
     * @access public
     *
     * @param array $columns Post type listing columns.
     * @return array
     */
    public function custom_post_listing_column( $columns ) {

        $updated_columns = array();

        foreach ( $columns as $key => $column ) {

            // add link_id and link_destination column before link categories column
            if ( $key == 'taxonomy-idatlink-category' ) {

                $updated_columns[ 'link_id' ]          = __( 'Link ID' , 'idataffiliates' );
                $updated_columns[ 'redirect_type' ]    = __( 'Redirect Type' , 'idataffiliates' );
                $updated_columns[ 'cloaked_url' ]      = __( 'Cloaked URL' , 'idataffiliates' );
                $updated_columns[ 'link_destination' ] = __( 'Link Destination' , 'idataffiliates' );
            }


            $updated_columns[ $key ] = $column;
        }

        return apply_filters( 'ta_post_listing_custom_columns' , $updated_columns );

    }

    /**
     * Add custom column to idat link listings (Link ID).
     *
     * @since 3.0.0
     * @access public
     *
     * @param string $column  Current column name.
     * @param int    $post_id IDatlink ID.
     * @return array
     */
    public function custom_post_listing_column_value( $column , $post_id ) {

        $idatlink = $this->get_idatlink_post( $post_id );

        switch ( $column ) {

            case 'link_id' :
                echo '<span>' . $post_id . '</span>';
                break;

            case 'redirect_type' :
                echo $idatlink->get_prop( 'redirect_type' );
                break;

            case 'cloaked_url' :
                echo '<input style="width:100%;" type="text" value="' . $idatlink->get_prop( 'permalink' ) . '" readonly>';
                break;

            case 'link_destination' :
                echo '<input style="width:100%;" type="text" value="' . $idatlink->get_prop( 'destination_url' ) . '" readonly>';

                break;

        }

        do_action( 'ta_post_listing_custom_columns_value' , $column , $idatlink );

    }

    /**
     * Add category slug to the permalink.
     *
     * @since 3.0.0
     * @access public
     *
     * @param string  $post_link  IDatlink permalink.
     * @param WP_Post $post       IDatlink WP_Post object.
     * @return string IDatlink permalink.
     */
    public function add_category_slug_to_permalink( $post_link , $post ) {

        $link_prefix = $this->_helper_functions->get_idatlink_link_prefix();

        if ( get_option( 'ta_show_cat_in_slug' ) !== 'yes' || is_wp_error( $post ) || $post->post_type != 'idatlink' )
            return $post_link;

        $link_cat_id = get_post_meta( $post->ID , '_ta_category_slug_id' , true );
        $link_cat    = get_post_meta( $post->ID , '_ta_category_slug' , true );

        if ( ! $link_cat && $link_cat_id ) {

            $link_cat_obj = get_term( $link_cat_id , Plugin_Constants::AFFILIATE_LINKS_TAX );
            $link_cat     = $link_cat_obj->slug;

        } elseif ( ! $link_cat && ! $link_cat_id ) {

            $link_cat = $this->_helper_functions->get_default_category_slug( $post->ID );
        }

        if ( ! $link_cat )
            return $post_link;

        return home_url( user_trailingslashit( $link_prefix . '/' . $link_cat . '/' . $post->post_name ) );
    }

    /**
     * Ajax get category slug.
     *
     * @since 3.0.0
     * @access public
     */
    public function ajax_get_category_slug() {

        if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX )
            $response = array( 'status' => 'fail' , 'error_msg' => __( 'Invalid AJAX call' , 'idataffiliates' ) );
        elseif ( ! isset( $_POST[ 'term_id' ] ) )
            $response = array( 'status' => 'fail' , 'error_msg' => __( 'Missing required post data' , 'idataffiliates' ) );
        else {

            $link_cat_id = (int) sanitize_text_field( $_POST[ 'term_id' ] );
            $category    = get_term( $link_cat_id , Plugin_Constants::AFFILIATE_LINKS_TAX );

            $response = array( 'status' => 'success' , 'category_slug' => $category->slug );
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
     * Method that houses codes to be executed on init hook.
     *
     * @since 3.0.0
     * @access public
     * @inherit IDatAffiliates\Interfaces\Initiable_Interface
     */
    public function initialize() {

        // cpt and taxonomy
        $this->register_idatlink_custom_post_type();
        $this->register_idatlink_category_custom_taxonomy();

        add_action( 'wp_ajax_ta_get_category_slug' , array( $this , 'ajax_get_category_slug' ) );

    }

    /**
     * Execute 'idatlink' custom post type code.
     *
     * @since 3.0.0
     * @access public
     * @inherit IDatAffiliates\Interfaces\Model_Interface
     */
    public function run() {

        // replace permalink with link ID
        add_filter( 'get_sample_permalink_html', array( $this , 'replace_permalink_with_id' ), 10 , 2 );

        // metaboxes
        add_action( 'add_meta_boxes' , array( $this , 'register_metaboxes' ) );
        add_action( 'save_post' , array( $this , 'save_post' ) );

        // custom column
        add_filter( 'manage_edit-idatlink_columns' , array( $this , 'custom_post_listing_column' ) );
        add_action( 'manage_idatlink_posts_custom_column', array( $this  , 'custom_post_listing_column_value' ) , 10 , 2 );

        // filter to add category on permalink
        add_filter( 'post_type_link' , array( $this , 'add_category_slug_to_permalink' ) , 10 , 2 );

    }

}
