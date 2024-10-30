<?php

namespace IDatAffiliates\Models;

use IDatAffiliates\Abstracts\Abstract_Main_Plugin_Class;

use IDatAffiliates\Interfaces\Model_Interface;

use IDatAffiliates\Helpers\Plugin_Constants;
use IDatAffiliates\Helpers\Helper_Functions;

/**
 * Model that houses the data model of an affiliate link.
 *
 * @since 3.0.0
 */
class Affiliate_Link {


    /*
    |--------------------------------------------------------------------------
    | Class Properties
    |--------------------------------------------------------------------------
    */

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
     * Stores affiliate link ID.
     *
     * @since 3.0.0
     * @access private
     * @var array
     */
    protected $id;

    /**
     * Stores affiliate link data.
     *
     * @since 3.0.0
     * @access private
     * @var array
     */
    protected $data = array();

    /**
     * Stores affiliate link default data.
     *
     * @since 3.0.0
     * @access private
     * @var array
     */
    protected $default_data = array(
        'name'            => '',
        'slug'            => '',
        'date_created'    => '',
        'date_modified'   => '',
        'status'          => '',
        'permalink'       => '',
        'destination_url' => '',
        'rel_tags'        => '',
        'redirect_type'   => '',
        'no_follow'       => 'global',
        'new_window'      => 'global',
        'uncloak_link'    => 'global',
        'pass_query_str'  => 'global',
        'image_ids'       => array(),
        'categories'      => array(),
        'category_slug'   => '',
        'category_slug_id' => 0,
    );

    /**
     * Stores affiliate link default data.
     *
     * @since 3.0.0
     * @access private
     * @var array
     */
    protected $extend_data = array();

    /**
     * Stores affiliate link post data.
     *
     * @since 3.0.0
     * @access private
     * @var object
     */
    protected $post_data;

    /**
     * This is where changes to the $data will be saved.
     *
     * @since 3.0.0
     * @access private
     * @var object
     */
    protected $changes = array();

    /**
     * Stores boolean if the data has been read from the database or not.
     *
     * @since 3.0.0
     * @access private
     * @var object
     */
    protected $object_is_read = false;




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
    public function __construct( $id = null ) {
        
        $this->_constants        = IDatAffiliates()->helpers[ 'Plugin_Constants' ];
        $this->_helper_functions = IDatAffiliates()->helpers[ 'Helper_Functions' ];

        if ( filter_var( $id , FILTER_VALIDATE_INT ) && $id ) {

            $this->extend_data = apply_filters( 'ta_affiliate_link_extended_data' , $this->extend_data , $this->default_data );
            $this->data        = $this->get_merged_default_extended_data();
            $this->id          = absint( $id );

            $this->read();

        }

    }

    /**
     * Read data from DB and save on instance.
     *
     * @since 3.0.0
     * @access public
     */
    private function read() {

        $this->post_data   = get_post( $this->id );

        if ( ! is_a( $this->post_data , 'WP_Post' ) || $this->object_is_read )
            return;

        // set the affiliate link ID
        $this->id = $this->post_data->ID;

        foreach ( $this->get_merged_default_extended_data() as $prop => $value ) {

            switch ( $prop ) {

                case 'name' :
                case 'slug' :
                case 'status' :
                case 'date_created' :
                case 'permalink' :
                    $this->data[ $prop ] = $this->get_post_data_equivalent( $prop );
                    break;

                case 'rel_tags' :
                case 'no_follow' :
                case 'new_window' :
                case 'uncloak_link' :
                case 'redirect_type' :
                case 'pass_query_str' :
                    $raw_data            = get_post_meta( $this->id , Plugin_Constants::META_DATA_PREFIX . $prop , true );
                    $this->data[ $prop ] = ! empty( $raw_data ) ? $raw_data : $this->get_prop_global_option_value( $prop );
                    break;

                case 'image_ids' :
                    $raw_data            = get_post_meta( $this->id , Plugin_Constants::META_DATA_PREFIX . $prop , true );
                    $this->data[ $prop ] = ( is_array( $raw_data ) && ! empty( $raw_data ) ) ? $raw_data : $this->default_data[ $prop ];
                    break;

                case 'categories' :
                    $categories          = wp_get_post_terms( $this->id , Plugin_Constants::AFFILIATE_LINKS_TAX );
                    $this->data[ $prop ] = ! empty( $categories ) ? $categories : $this->default_data[ $prop ];
                    break;

                default :
                    $value               = get_post_meta( $this->id , Plugin_Constants::META_DATA_PREFIX . $prop , true );
                    $this->data[ $prop ] = apply_filters( 'ta_read_idatlink_property' , $value , $prop , $this->default_data );
                    break;

            }

        }

        $this->object_is_read = true;

    }




    /*
    |--------------------------------------------------------------------------
    | Data getters
    |--------------------------------------------------------------------------
    */

    /**
     * Get merged $default_data and $extended_data class properties.
     *
     * @since 3.0.0
     * @access public
     *
     * @return array Data properties.
     */
    private function get_merged_default_extended_data() {

        return array_merge( $this->default_data , $this->extend_data );

    }

    /**
     * Return's the post data equivalent of a certain affiliate link data property.
     *
     * @since 3.0.0
     * @access private
     *
     * @param string $prop Affiliate link property name.
     * @return string WP Post property equivalent.
     */
    private function get_post_data_equivalent( $prop ) {

        $equivalents = apply_filters( 'ta_affiliate_link_post_data_equivalent' , array(
            'name'          => $this->post_data->post_title,
            'slug'          => $this->post_data->post_name,
            'permalink'     => get_permalink( $this->post_data->ID ),
            'status'        => $this->post_data->post_status,
            'date_created'  => $this->post_data->post_date,
            'date_modified' => $this->post_data->post_modified,
        ) , $this->post_data );

        if ( array_key_exists( $prop , $equivalents ) )
            return $equivalents[ $prop ];
        else
            return;

    }

    /**
     * Return data property.
     *
     * @since 3.0.0
     * @access public
     *
     * @param string $prop    Data property slug.
     * @param mixed  $default Set property default value (optional).
     * @return mixed Property data.
     */
    public function get_prop( $prop , $default = '' ) {

        $default_data = $this->get_merged_default_extended_data();

        if ( array_key_exists( $prop , $this->data ) && $this->data[ $prop ] )
            $return_value = $this->data[ $prop ];
        else
            $return_value = ( $default ) ? $default : $default_data[ $prop ];

        return $prop === 'destination_url' ? esc_url( $return_value ) : $return_value;

    }

    /**
     * Return Affiliate_Link ID.
     *
     * @since 3.0.0
     * @access public
     *
     * @return int Affiliate_Link ID.
     */
    public function get_id() {

        return absint( $this->id );

    }

    /**
     * Return changed data property.
     *
     * @since 3.0.0
     * @access public
     *
     * @param string $prop    Data property slug.
     * @param mixed  $default Set property default value (optional).
     * @return mixed Property data.
     */
    public function get_changed_prop( $prop , $default = '' ) {

        return isset( $this->changes[ $prop ] ) ? $this->changes[ $prop ] : $this->get_prop( $prop , $default );

    }

    /**
     * Return affiliate link's WP_Post data.
     *
     * @since 3.0.0
     * @access public
     *
     * @return object Post data object.
     */
    public function get_post_data() {

        return $this->post_data;

    }

    /**
     * Get the properties global option value.
     *
     * @since 3.0.0
     * @access public
     *
     * @param string $prop Name of property.
     * @return string Global option value.
     */
    public function get_prop_global_option_value( $prop ) {

        $default = '';

        switch( $prop ) {

            case 'rel_tags' :
                $option = 'ta_additional_rel_tags';
                break;

            case 'no_follow' :
                $option = 'ta_no_follow';
                break;

            case 'new_window' :
                $option = 'ta_new_window';
                break;

            case 'redirect_type' :
                $option  = 'ta_link_redirect_type';
                $default = '301';
                break;

            case 'pass_query_str' :
                $option = 'ta_pass_query_str';
                break;

            case 'uncloak_link' :
                return;
                break;
        }

        return get_option( $option , $default );
    }

    /**
     * Get the global value for the uncloak property.
     *
     * @since 3.0.0
     * @access public
     *
     * @return string Global option value.
     */
    public function get_global_uncloak_value() {

        $uncloak_cats = maybe_unserialize( get_option( 'ta_category_to_uncloak' , array() ) );

        if ( ! is_array( $uncloak_cats ) || empty( $uncloak_cats ) )
            return 'no';

        foreach ( $uncloak_cats as $cat_id ) {

            if ( has_term( intval( $cat_id ) , Plugin_Constants::AFFILIATE_LINKS_TAX , $this->id ) )
                return 'yes';
        }

        return 'no';
    }




    /*
    |--------------------------------------------------------------------------
    | Data setters
    |--------------------------------------------------------------------------
    */

    /**
     * Set new value to properties and save it to $changes property.
     * This stores changes in a special array so we can track what needs to be saved on the DB later.
     *
     * @since 3.0.0
     * @access public
     *
     * @param string $prop Data property slug.
     * @param string $value New property value.
     */
    public function set_prop( $prop , $value ) {

        $default_data = $this->get_merged_default_extended_data();

        if ( array_key_exists( $prop , $this->data ) ) {

            // permalink property must not be changed
            if ( $prop == 'permalink' )
                return;

            if ( gettype( $value ) == gettype( $default_data[ $prop ] ) )
                $this->changes[ $prop ] = $value;
            else {

                // TODO: handle error here.

            }

        } else {

            $this->data[ $prop ]    = $value;
            $this->changes[ $prop ] = $value;

        }

    }




    /*
    |--------------------------------------------------------------------------
    | Save (Create / Update) data to DB
    |--------------------------------------------------------------------------
    */

    /**
     * Save data in $changes to the database.
     *
     * @since 3.0.0
     * @access public
     *
     * @return WP_Error | int On success will return the post ID, otherwise it will return a WP_Error object.
     */
    public function save() {

        if ( ! empty( $this->changes ) ) {

            $post_metas = array();
            $post_data  = array(
                'post_title'    => $this->get_changed_prop( 'name' ),
                'post_name'     => $this->get_changed_prop( 'slug' ),
                'post_status'   => $this->get_changed_prop( 'status' , 'publish' ),
                'post_date'     => $this->get_changed_prop( 'date_created' , current_time( 'mysql' ) ),
                'post_modified' => $this->get_changed_prop( 'date_modified' , current_time( 'mysql' ) )
            );

            foreach ( $this->changes as $prop => $value ) {

                // make sure that property is registered in default data
                if ( ! array_key_exists( $prop , $this->get_merged_default_extended_data() ) )
                    continue;

                if ( in_array( $prop , array( 'permalink' , 'name' , 'slug' , 'status' , 'date_created' , 'date_modified' ) ) )
                    continue;

                $post_metas[ $prop ] = $value;
            }

            // create or update post
            if ( $this->id )
                $post_id = $this->update( $post_data );
            else
                $post_id = $this->create( $post_data );

            if ( ! is_wp_error( $post_id ) )
                $this->update_metas( $post_id , $post_metas );
            else
                return $post_id; // Return WP_Error object on error

            do_action( 'ta_save_affiliate_link' , $this->changes , $this );

            // update instance with new changes.
            $this->object_is_read = false;
            $this->read();

        } else
            return new \WP_Error( 'ta_affiliate_link_no_changes' , __( 'Unable to save affiliate link as there are no changes registered on the object yet.' , 'idataffiliates' ) , array( 'changes' => $this->changes , 'affiliate_link' => $this ) );

        return $post_id;
    }

    /**
     * Create the affiliate link post.
     *
     * @since 3.0.0
     * @access private
     *
     * @param array $post_data Affiliate link post data.
     * @param WP_Error|int WP_Error on error, ID of newly created post otherwise.
     */
    private function create( $post_data ) {

        $post_data = array_merge( array( 'post_type' => Plugin_Constants::AFFILIATE_LINKS_CPT ) , $post_data );
        $this->id  = wp_insert_post( $post_data );

        return $this->id;

    }

    /**
     * Update the affiliate link post.
     *
     * @since 3.0.0
     * @access private
     *
     * @param array $post_data Affiliate link post data.
     * @return int ID of the updated post upon success. 0 on failure.
     */
    private function update( $post_data ) {

        $post_data = array_merge( array( 'ID' => $this->id ) , $post_data );
        return wp_update_post( $post_data , true );

    }

    /**
     * Update/add the affiliate link meta data.
     *
     * @since 3.0.0
     * @access private
     *
     * @param int $post_id Affiliate link post ID.
     * @param array $post_metas Affiliate link meta data.
     */
    private function update_metas( $post_id , $post_metas ) {

        foreach ( $post_metas as $key => $value )
            update_post_meta( $post_id , Plugin_Constants::META_DATA_PREFIX . $key , $value );

    }

    /**
     * Count affiliate link clicks.
     *
     * @since 3.0.0
     * @access public
     *
     * @return int Total number of clicks.
     */
    public function count_clicks() {

        global $wpdb;

        $table_name = $wpdb->prefix . Plugin_Constants::LINK_CLICK_DB;
        $link_id    = $this->get_id();
        $query      = "SELECT count(*) from $table_name WHERE link_id = $link_id";
        $clicks     = $wpdb->get_var( $query );

        return (int) $clicks;
    }

}
