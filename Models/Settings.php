<?php
namespace IDatAffiliates\Models;

use IDatAffiliates\Abstracts\Abstract_Main_Plugin_Class;

use IDatAffiliates\Interfaces\Model_Interface;
use IDatAffiliates\Interfaces\Activatable_Interface;
use IDatAffiliates\Interfaces\Initiable_Interface;

use IDatAffiliates\Helpers\Plugin_Constants;
use IDatAffiliates\Helpers\Helper_Functions;

if ( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Model that houses the logic of plugin Settings.
 * General Information:
 * The Ultimate Settings API ( Of course there will always be room for improvements ).
 * Basically we are using parts of the WordPress Settings API ( Only the backend processes )
 * But we are using our own custom render codebase.
 * The issue with WordPress Settings API is that we need to supply callbacks for each option field we add, so its not really extensible.
 * The data supplied on those callbacks are not ideal or not complete too to make a very extensible Settings API.
 * So what we did is, Register the settings and settings options in a way that we can utilize WordPress Settings API to handle them on the backend.
 * But use our own render codebase so we can make the Settings API very easy to extend.
 *
 * Important Note:
 * Be careful with default values. Default values only take effect if you haven't set the option yet. Meaning the option is not yet registered yet to the options db. ( get_option ).
 * Now if you hit save on a settings section with a field that have a default value, and you haven't changed that default value, Alas! it will still not register that option to the options db.
 * The reason is the default value and the current value of the options is the same.
 * Bug if you modify the value of the option, and hit save, this time, that option will be registered to the options db.
 * Then if you set back the value of that option, same as its default, it will still updated that option that is registered to the options db with that value.
 * Again remember, default value only kicks in if the option you are trying to get via get_option function is not yet registered to the options db.
 *
 * Important Note:
 * If the option can contain multiple values ( in array form , ex. checkbox and multiselect option types ), the default value must be in array form even if it only contains one value.
 *
 * Private Model.
 *
 * @since 3.0.0
 */
class Settings implements Model_Interface , Activatable_Interface , Initiable_Interface {

    /*
    |--------------------------------------------------------------------------
    | Class Properties
    |--------------------------------------------------------------------------
    */

    /**
     * Property that holds the single main instance of Settings.
     *
     * @since 3.0.0
     * @access private
     * @var Settings
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
     * Property that houses all the helper functions of the plugin.
     *
     * @since 3.0.0
     * @access private
     * @var Helper_Functions
     */
    private $_helper_functions;

    /**
     * Property that houses all the supported option field types.
     *
     * @since 3.0.0
     * @access private
     * @var array
     */
    private $_supported_field_types;

    /**
     * Property that houses all the supported option field types that do not needed to be registered to the WP Settings API.
     * Ex. of this are field types that are for decorative purposes only and has no underlying option data to save.
     * Another is type of option fields that perform specialized task and does not need any underlying data to be saved.
     *
     * @since 3.0.0
     * @access public
     */
    private $_skip_wp_settings_registration;

    /**
     * Property that houses all the registered settings sections.
     *
     *
     * @since 3.0.0
     * @access private
     * @var array
     */
    private $_settings_sections;

    /**
     * Property that houses all the registered options of the registered settings sections.
     *
     * @since 3.0.0
     * @access private
     * @var array
     */
    private $_settings_section_options;

    /**
     * Property that holds all plugin options that can be exported.
     *
     * @since 3.0.0
     * @access private
     * @var array
     */
    private $_exportable_options;

    /**
     * Property that holds list of post update function callbacks per option if there are any.
     *
     * @since 3.0.0
     * @access private
     * @var array
     */
    private $_post_update_option_cbs = array();




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
     * @return Settings
     */
    public static function get_instance( Abstract_Main_Plugin_Class $main_plugin , Plugin_Constants $constants , Helper_Functions $helper_functions ) {

        if ( !self::$_instance instanceof self )
            self::$_instance = new self( $main_plugin , $constants , $helper_functions );

        return self::$_instance;

    }




    /*
    |--------------------------------------------------------------------------
    | Initialize Settings
    |--------------------------------------------------------------------------
    */

    /**
     * Initialize the list of plugin built-in settings sections and its corresponding options.
     *
     * @since 3.0.0
     * @access public
     */
    public function init_settings_sections_and_options() {

        $this->_supported_field_types = apply_filters( 'ta_supported_field_types' , array(
            'text'                   => array( $this , 'render_text_option_field' ),
            'number'                 => array( $this , 'render_number_option_field' ),
            'textarea'               => array( $this , 'render_textarea_option_field' ),
            'checkbox'               => array( $this , 'render_checkbox_option_field' ),
            'radio'                  => array( $this , 'render_radio_option_field' ),
            'select'                 => array( $this , 'render_select_option_field' ),
            'multiselect'            => array( $this , 'render_multiselect_option_field' ),
            'toggle'                 => array( $this , 'render_toggle_option_field' ),
            'editor'                 => array( $this , 'render_editor_option_field' ),
            'csv'                    => array( $this , 'render_csv_option_field' ),
            'key_value'              => array( $this , 'render_key_value_option_field' ),
            'link'                   => array( $this , 'render_link_option_field' ),
            'option_divider'         => array( $this , 'render_option_divider_option_field' ),
            'migration_controls'     => array( $this , 'render_migration_controls_option_field' ),
            'export_global_settings' => array( $this , 'render_export_global_settings_option_field' ),
            'import_global_settings' => array( $this , 'render_import_global_settings_option_field' )
        ) );

        $this->_skip_wp_settings_registration = apply_filters( 'ta_skip_wp_settings_registration' , array(
            'link',
            'option_divider',
            'migration_controls',
            'export_global_settings',
            'import_global_settings'
        ) );

        $this->_settings_sections = apply_filters( 'ta_settings_option_sections' , array(
            'ta_general_settings'       => array(
                                                'title' => __( 'General' , 'idataffiliates' ) ,
                                                'desc'  => __( 'Settings that change the general behaviour of IDatAffiliates.' , 'idataffiliates' )
                                            ),
            'ta_links_settings'         => array(
                                                'title' => __( 'Link Appearance' , 'idataffiliates' ) ,
                                                'desc'  => __( 'Settings that specifically affect the behaviour & appearance of your affiliate links.' , 'idataffiliates' )
                                            )
        ) );

        $this->_settings_section_options = apply_filters( 'ta_settings_section_options' , array(
            'ta_general_settings'       => apply_filters( 'ta_general_settings_options' , array(

                                                    array(
                                                        'id'        => 'ta_link_insertion_type',
                                                        'title'     => __( 'Default Link Insertion Type' , 'idataffiliates' ),
                                                        'desc'      => __( "Determines the default link type when inserting a link using the quick search." , 'idataffiliates' ),
                                                        'type'      => 'select',
                                                        'default'   => 'link',
                                                        'options'   => array(
                                                            'link'      => __( 'Link' , 'idataffiliates' ),
                                                            'shortcode' => __( 'Shortcode' , 'idataffiliates' ),
                                                        )
                                                    ),

                                                    array(
                                                        'id'        =>  'ta_disable_cat_auto_select',
                                                        'title'     =>  __( 'Disable "uncategorized" category on save?' , 'idataffiliates' ),
                                                        'desc'      =>  __( 'If links are including categories in the URL then by default IDatAffiliates will add an "uncategorized" category to apply to non-categorised links during save. If you disable this, it allows you to have some links with categories in the URL and some without.' , 'idataffiliates' ),
                                                        'type'      =>  'toggle'
                                                    ),

                                                    array(
                                                        'id'        =>  'ta_disable_visual_editor_buttons',
                                                        'title'     =>  __( 'Disable buttons on the Visual editor?' , 'idataffiliates' ),
                                                        'desc'      =>  __( "Hide the IDatAffiliates buttons on the Visual editor." , 'idataffiliates' ),
                                                        'type'      =>  'toggle'
                                                    ),

                                                    array(
                                                        'id'        =>  'ta_disable_text_editor_buttons',
                                                        'title'     =>  __( 'Disable buttons on the Text/Quicktags editor?' , 'idataffiliates' ),
                                                        'desc'      =>  __( "Hide the IDatAffiliates buttons on the Text editor." , 'idataffiliates' ),
                                                        'type'      =>  'toggle'
                                                    )
                                                    
                                              ) ),
            'ta_links_settings'         => apply_filters( 'ta_links_settings_options' , array(

                                                    array(
                                                        'id'      => 'ta_link_prefix',
                                                        'title'   => __( 'Link Prefix' , 'idataffiliates' ),
                                                        'desc'    => sprintf( __( "The prefix that comes before your cloaked link's slug. <br>eg. %s/<strong>recommends</strong>/your-affiliate-link-name.<br><br><b>Warning :</b> Changing this setting after you've used links in a post could break those links. Be careful!" , 'idataffiliates' ) , home_url() ),
                                                        'type'    => 'select',
                                                        'default' => 'recommends',
                                                        'options' => array(
                                                            'custom'     => '-- custom --',
                                                            'recommends' => 'recommends',
                                                            'link'       => 'link',
                                                            'go'         => 'go',
                                                            'review'     => 'review',
                                                            'product'    => 'product',
                                                            'suggests'   => 'suggests',
                                                            'follow'     => 'follow',
                                                            'endorses'   => 'endorses',
                                                            'proceed'    => 'proceed',
                                                            'fly'        => 'fly',
                                                            'goto'       => 'goto',
                                                            'get'        => 'get',
                                                            'find'       => 'find',
                                                            'act'        => 'act',
                                                            'click'      => 'click',
                                                            'move'       => 'move',
                                                            'offer'      => 'offer',
                                                            'run'        => 'run'
                                                        )
                                                    ),

                                                    array(
                                                        'id'             => 'ta_link_prefix_custom',
                                                        'title'          => __( 'Custom Link Prefix' , 'idataffiliates' ),
                                                        'desc'           => __( 'Enter your preferred link prefix.' , 'idataffiliates' ),
                                                        'type'           => 'text'
                                                    ),

                                                    array(
                                                        'id'        =>  'ta_show_cat_in_slug',
                                                        'title'     =>  __( 'Link Category in URL?' , 'idataffiliates' ),
                                                        'desc'      =>  sprintf( __( "Shows the primary selected category in the url. eg. %s/recommends/<strong>link-category</strong>/your-affiliate-link-name.<br><br><b>Warning :</b> Changing this setting after you've used links in a post could break those links. Be careful!"  , 'idataffiliates' ) , home_url() ),
                                                        'type'      =>  'toggle'
                                                    ),

                                                    array(
                                                        'id'        =>  'ta_link_redirect_type',
                                                        'title'     =>  __( 'Link Redirect Type' , 'idataffiliates' ),
                                                        'desc'      =>  __( "This is the type of redirect IDatAffiliates will use to redirect the user to your affiliate link." , 'idataffiliates' ),
                                                        'type'      =>  'radio',
                                                        'options'   =>  $this->_constants->REDIRECT_TYPES(),
                                                    ),

                                                    array(
                                                        'id'        =>  'ta_no_follow',
                                                        'title'     =>  __( 'Use no follow on links?' , 'idataffiliates' ),
                                                        'desc'      =>  __( "Add the nofollow attribute to links so search engines don't index them." , 'idataffiliates' ),
                                                        'type'      =>  'toggle',
                                                        'default'   => 'no'
                                                    ),

                                                    array(
                                                        'id'        =>  'ta_new_window',
                                                        'title'     =>  __( 'Open links in new window?' , 'idataffiliates' ),
                                                        'desc'      =>  __( "Make links open in a new browser tab by default." , 'idataffiliates' ),
                                                        'type'      =>  'toggle',
                                                        'default'   => 'no'
                                                    ),

                                                    array(
                                                        'id'        =>  'ta_pass_query_str',
                                                        'title'     =>  __( 'Pass query strings to destination url?' , 'idataffiliates' ),
                                                        'desc'      =>  __( "Enabling this option will pass all of the query strings present after the cloaked url to the destination url automatically when redirecting." , 'idataffiliates' ),
                                                        'type'      =>  'toggle',
                                                        'default'   => 'no'
                                                    ),

                                                    array(
                                                        'id'        =>  'ta_additional_rel_tags',
                                                        'title'     =>  __( 'Additional rel attribute tags' , 'idataffiliates' ),
                                                        'desc'      =>  __( "Allows you to add extra tags into the rel= attribute when links are inserted." , 'idataffiliates' ),
                                                        'type'      =>  'text'
                                                    ),

                                                    array(
                                                        'id'        =>  'ta_disable_idat_link_class',
                                                        'title'     =>  __( 'Disable IDatAffiliates CSS classes?' , 'idataffiliates' ),
                                                        'desc'      =>  __( 'To help with styling a CSS class called "idatlink" is added links on insertion.<br>Likewise the "idatlinkimg" class is added to images when using the image insertion type. This option disables the addition these CSS classes.' , 'idataffiliates' ),
                                                        'type'      =>  'toggle'
                                                    ),

                                                    array(
                                                        'id'        =>  'ta_disable_title_attribute',
                                                        'title'     =>  __( 'Disable title attribute on link insertion?' , 'idataffiliates' ),
                                                        'desc'      =>  __( "Links are automatically output with a title html attribute (by default this shows the title of the affiliate link).<br>This option disables the output of the title attribute on your links." , 'idataffiliates' ),
                                                        'type'      =>  'toggle'
                                                    ),

                                                    array(
                                                        'id'           =>  'ta_category_to_uncloak',
                                                        'title'        =>  __( 'Select Category to Uncloak' , 'idataffiliates' ),
                                                        'desc'         =>  __( "The links assigned to the selected category will be uncloaked." , 'idataffiliates' ),
                                                        'type'         =>  'multiselect',
                                                        'options'      =>  $this->_helper_functions->get_all_category_as_options(),
                                                        'default'      =>  array(),
                                                        'condition_cb' => function() { return get_option( 'ta_uncloak_link_per_link' ) === 'yes'; },
                                                        'placeholder'  => __( 'Select category...' , 'idataffiliates' )
                                                    )

                                              ) )

        ) );

        // Get all the exportable options
        foreach ( $this->_settings_section_options as $section_id => $section_options ) {

            foreach ( $section_options as $option ) {

                if ( in_array( $option[ 'type' ] , $this->_skip_wp_settings_registration )  )
                    continue;

                $this->_exportable_options[ $option[ 'id' ] ] = isset( $option[ 'default' ] ) ? $option[ 'default' ] : '';

                if ( isset( $option[ 'post_update_cb' ] ) && is_callable( $option[ 'post_update_cb' ] ) )
                    add_action( 'update_option_' . $option[ 'id' ] , $option[ 'post_update_cb' ] , 10 , 3 );

            }

        }

    }

    /**
     * Register Settings Section and Options Group to WordPress Settings API.
     *
     * @since 3.0.0
     * @access public
     */
    public function register_settings_section_and_options_group() {

        foreach ( $this->_settings_sections as $section_id => $section_data ) {

            add_settings_section(
                $section_id,                   // Settings Section ID
                $section_data[ 'title' ],      // Settings Section Title
                function() {},                 // Callback. Intentionally Left Empty. We Will Handle UI Rendering.
                $section_id . '_options_group' // Options Group
            );

        }

    }

    /**
     * Register Settings Section Options to WordPress Settings API.
     *
     * @since 3.0.0
     * @access public
     */
    public function register_settings_section_options() {

        foreach ( $this->_settings_section_options as $section_id => $section_options ) {

            foreach ( $section_options as $option ) {

                if ( !array_key_exists( $option[ 'type' ] , $this->_supported_field_types ) || in_array( $option[ 'type' ] , $this->_skip_wp_settings_registration ) )
                    continue;

                // Register The Option To The Options Group It Is Scoped With
                add_settings_field(
                    $option[ 'id' ],                // Option ID
                    $option[ 'title' ],             // Option Title
                    function() {},                  // Render Callback. Intentionally Left Empty. We Will Handle UI Rendering.
                    $section_id . '_options_group', // Options Group
                    $section_id                     // Settings Section ID
                );

                // Register The Actual Settings Option
                $args = array();

                if ( isset( $option[ 'data_type' ] ) )
                    $args[ 'type' ] = $option[ 'data_type' ];

                if ( isset( $option[ 'desc' ] ) )
                    $args[ 'description' ] = $option[ 'desc' ];

                if ( isset( $option[ 'sanitation_cb' ] ) && is_callable( $option[ 'sanitation_cb' ] ) )
                    $args[ 'sanitize_callback' ] = $option[ 'sanitation_cb' ];

                if ( isset( $option[ 'show_in_rest' ] ) )
                    $args[ 'show_in_rest' ] = $option[ 'show_in_rest' ];

                if ( isset( $option[ 'default' ] ) )
                    $args[ 'default' ] = $option[ 'default' ]; // Important Note: This will be used on "get_option" function automatically if the current option is not registered yet to the options db.

                register_setting( $section_id . '_options_group' , $option[ 'id' ] , $args );

            }

        }

    }

    /**
     * Initialize Plugin Settings API.
     * We register the plugin settings section and settings section options to WordPress Settings API.
     * We let WordPress Settings API handle the backend stuff.
     * We will handle the UI rendering.
     *
     * @since 3.0.0
     * @access public
     */
    public function init_plugin_settings() {

        $this->init_settings_sections_and_options();
        $this->register_settings_section_and_options_group();
        $this->register_settings_section_options();

    }

    /**
     * Add settings page.
     *
     * @since 3.0.0
     * @access public
     */
    public function add_settings_page() {

        add_submenu_page(
            'edit.php?post_type=idatlink',
            __( 'IDatAffiliates Settings' , 'idataffiliates' ),
            __( 'Settings' , 'idataffiliates' ),
            'manage_options',
            'idat-settings',
            array( $this, 'view_settings_page' )
        );

    }

    /**
     * Settings page view.
     *
     * @since 3.0.0
     * @access public
     */
    public function view_settings_page() {
        ?>

        <div class="wrap ta-settings">

            <h2><?php _e( 'IDatAffiliates Settings' , 'idataffiliates' ); ?></h2>

            <?php
            settings_errors(); // Show notices based on the outcome of the settings save action
            $active_tab = isset( $_GET[ 'tab' ] ) ? $_GET[ 'tab' ] : 'ta_general_settings';
            ?>

            <h2 class="nav-tab-wrapper">
                <?php foreach ( $this->_settings_sections as $section_key => $section_data ) { ?>
                    <a href="?post_type=idatlink&page=idat-settings&tab=<?php echo $section_key; ?>" class="nav-tab <?php echo $active_tab == $section_key ? 'nav-tab-active' : ''; ?> <?php echo $section_key; ?>"><?php echo $section_data[ 'title' ]; ?></a>
                <?php }  ?>
            </h2>

            <?php do_action( 'ta_before_settings_form' ); ?>

            <form method="post" action="options.php" enctype="multipart/form-data">

                <?php
                $this->render_settings_section_nonces( $active_tab );
                $this->render_settings_section_header( $active_tab );
                $this->render_settings_section_fields( $active_tab );
                ?>

            </form>

        </div><!--wrap-->

        <?php
    }

    /**
     * Render all necessary nonces for the current settings section.
     *
     * @since 3.0.0
     * @access public
     *
     * @param string $active_tab Currently active settings section.
     */
    public function render_settings_section_nonces( $active_tab ) {

        settings_fields( $active_tab . '_options_group' );

    }

    /**
     * Render the current settings section header markup.
     *
     * @since 3.0.0
     * @access public
     *
     * @param string $active_tab Currently active settings section.
     */
    public function render_settings_section_header( $active_tab ) {

        if ( ! isset( $this->_settings_sections[ $active_tab ] ) )
            return;

        ?>

        <h2><?php echo $this->_settings_sections[ $active_tab ][ 'title' ]; ?></h2>
        <p class="desc"><?php echo $this->_settings_sections[ $active_tab ][ 'desc' ]; ?></p>

        <?php

    }

    /**
     * Render an option as a hidden field.
     * We do this if that option's condition callback failed.
     * We don't show it for the end user, but we still need to pass on the form the current data so we don't lost it.
     *
     * @since 3.0.0
     * @access public
     *
     * @param array $option Array of options data. May vary depending on option type.
     */
    public function render_option_as_hidden_field( $option ) {

        if ( in_array( $option[ 'type' ] , $this->_skip_wp_settings_registration ) )
            return; // This is a decorative option type, no need to render this as a hidden field

        ?>

        <input type="hidden" name="<?php echo esc_attr( $option[ 'id' ] ); ?>" value="<?php echo get_option( $option[ 'id' ] , '' ); ?>">

        <?php

    }

    /**
     * Render settings section option fields.
     *
     * @since 3.0.0
     * @access public
     *
     * @param string $active_tab Currently active settings section.
     */
    public function render_settings_section_fields( $active_tab ) {

        ?>

        <table class="form-table">
            <tbody>
                <?php
                foreach ( $this->_settings_section_options as $section_id => $section_options ) {

                    if ( $section_id !== $active_tab )
                        continue;

                    foreach ( $section_options as $option ) {

                        if ( isset( $option[ 'condition_cb' ] ) && is_callable( $option[ 'condition_cb' ] ) && !$option[ 'condition_cb' ]() )
                            $this->render_option_as_hidden_field( $option ); // Option condition failed. Render it as a hidden field so its value is preserved
                        else
                            $this->_supported_field_types[ $option[ 'type' ] ]( $option );

                        // if ( $option[ 'type' ] === 'editor' )
                        //     add_filter(  );

                    }

                }
                ?>
            </tbody>
        </table>

        <p class="submit">
            <input name="submit" id="submit" class="button button-primary" value="<?php _e( 'Save Changes' , 'idataffiliates' ); ?>" type="submit">
        </p>

        <?php

    }




    /*
    |--------------------------------------------------------------------------
    | Option Field Views
    |--------------------------------------------------------------------------
    */

    /**
     * Render 'text' type option field.
     *
     * @since 3.0.0
     * @access public
     *
     * @param array $option Array of options data. May vary depending on option type.
     */
    public function render_text_option_field( $option ) {

        ?>

        <tr valign="top" class="<?php echo esc_attr( $option[ 'id' ] ) . '-row'; ?>">

            <th scope="row"><?php echo sanitize_text_field( $option[ 'title' ] ); ?></th>

            <td class="forminp forminp-<?php echo sanitize_title( $option[ 'type' ] ) ?>">
                <input
                    type  = "text"
                    name  = "<?php echo esc_attr( $option[ 'id' ] ); ?>"
                    id    = "<?php echo esc_attr( $option[ 'id' ] ); ?>"
                    class = "option-field <?php echo isset( $option[ 'class' ] ) ? esc_attr( $option[ 'class' ] ) : ''; ?>"
                    style = "<?php echo isset( $option[ 'style' ] ) ? $option[ 'style' ] : 'width: 360px;'; ?>"
                    value = "<?php echo get_option( $option[ 'id' ] ); ?>" >
                <br>
                <p class="desc"><?php echo isset( $option[ 'desc' ] ) ? $option[ 'desc' ] : ''; ?></p>
            </td>

        </tr>

        <?php

    }

    /**
     * Render 'text' type option field.
     *
     * @since 3.0.0
     * @access public
     *
     * @param array $option Array of options data. May vary depending on option type.
     */
    public function render_number_option_field( $option ) {

        ?>

        <tr valign="top" class="<?php echo esc_attr( $option[ 'id' ] ) . '-row'; ?>">

            <th scope="row"><?php echo sanitize_text_field( $option[ 'title' ] ); ?></th>

            <td class="forminp forminp-<?php echo sanitize_title( $option[ 'type' ] ) ?>">
                <input
                    type  = "number"
                    name  = "<?php echo esc_attr( $option[ 'id' ] ); ?>"
                    id    = "<?php echo esc_attr( $option[ 'id' ] ); ?>"
                    class = "option-field <?php echo isset( $option[ 'class' ] ) ? esc_attr( $option[ 'class' ] ) : ''; ?>"
                    style = "<?php echo isset( $option[ 'style' ] ) ? $option[ 'style' ] : 'width: 100px;'; ?>"
                    value = "<?php echo get_option( $option[ 'id' ] ); ?>"
                    min   = "<?php echo isset( $option[ 'min' ] )  ? $option[ 'min' ] : 0; ?>"
                    max   = "<?php echo isset( $option[ 'max' ] )  ? $option[ 'max' ] : ''; ?>" >
                <span><?php echo isset( $option[ 'desc' ] ) ? $option[ 'desc' ] : ''; ?></span>
            </td>

        </tr>

        <?php

    }

    /**
     * Render 'textarea' type option field.
     *
     * @since 3.0.0
     * @access public
     *
     * @param array $option Array of options data. May vary depending on option type.
     */
    public function render_textarea_option_field( $option ) {

        ?>

        <tr valign="top" class="<?php echo esc_attr( $option[ 'id' ] ) . '-row'; ?>">

            <th scope="row"><?php echo sanitize_text_field( $option[ 'title' ] ); ?></th>

            <td class="forminp forminp-<?php echo sanitize_title( $option[ 'type' ] ) ?>">
                <textarea
                    name  = "<?php echo esc_attr( $option[ 'id' ] ); ?>"
                    id    = "<?php echo esc_attr( $option[ 'id' ] ); ?>"
                    class = "option-field <?php echo isset( $option[ 'class' ] ) ? esc_attr( $option[ 'class' ] ) : ''; ?>"
                    cols  = "60"
                    rows  = "8"
                    style = "<?php echo isset( $option[ 'style' ] ) ? $option[ 'style' ] : 'width: 360px;'; ?>"><?php echo get_option( $option[ 'id' ] ); ?></textarea>
                <br />
                <p class="desc"><?php echo isset( $option[ 'desc' ] ) ? $option[ 'desc' ] : ''; ?></p>
            </td>

        </tr>

        <?php

    }

    /**
     * Render 'checkbox' type option field.
     *
     * @since 3.0.0
     * @access public
     *
     * @param array $option Array of options data. May vary depending on option type.
     */
    public function render_checkbox_option_field( $option ) {

        $option_val = get_option( $option[ 'id' ] );
        if ( !is_array( $option_val ) )
            $option_val = array(); ?>

        <tr valign="top" class="<?php echo esc_attr( $option[ 'id' ] ) . '-row'; ?>">
            <th scope="row"><?php echo sanitize_text_field( $option[ 'title' ] ); ?></th>

            <td class="forminp forminp-<?php echo sanitize_title( $option[ 'type' ] ) ?>">
                <?php foreach ( $option[ 'options' ] as $opt_key => $opt_text  ) {

                    $opt_key_class = str_replace( " " , "-" , $opt_key ); ?>

                    <input
                        type  = "checkbox"
                        name  = "<?php echo esc_attr( $option[ 'id' ] ); ?>[]"
                        id    = "<?php echo esc_attr( $option[ 'id' ] ); ?>"
                        class = "option-field <?php echo isset( $option[ 'class' ] ) ? esc_attr( $option[ 'class' ] ) : ''; ?>"
                        style = "<?php echo isset( $option[ 'style' ] ) ? $option[ 'style' ] : ''; ?>"
                        value = "<?php echo $opt_key; ?>"
                        <?php echo in_array( $opt_key , $option_val ) ? 'checked' : ''; ?>>

                    <label class="<?php echo esc_attr( $option[ 'id' ] ); ?>"><?php echo $opt_text; ?></label>
                    <br>

                <?php } ?>

                <p class="desc"><?php echo isset( $option[ 'desc' ] ) ? $option[ 'desc' ] : ''; ?></p>
            </td>

            <script>
                jQuery( document ).ready( function( $ ) {

                    $( "label.<?php echo esc_attr( $option[ 'id' ] ); ?>" ).on( "click" , function() {

                        $( this ).prev( "input[type='checkbox']" ).trigger( "click" );

                    } );

                } );
            </script>
        </tr>

        <?php

    }

    /**
     * Render 'radio' type option field.
     *
     * @since 3.0.0
     * @access public
     *
     * @param array $option Array of options data. May vary depending on option type.
     */
    public function render_radio_option_field( $option ) {

        $option_val = get_option( $option[ 'id' ] ); ?>


        <tr valign="top" class="<?php echo esc_attr( $option[ 'id' ] ) . '-row'; ?>">
            <th scope="row"><?php echo sanitize_text_field( $option[ 'title' ] ); ?></th>

            <td class="forminp forminp-<?php echo sanitize_title( $option[ 'type' ] ) ?>">
                <?php foreach ( $option[ 'options' ] as $opt_key => $opt_text ) { ?>

                    <input
                        type  = "radio"
                        name  = "<?php echo esc_attr( $option[ 'id' ] ); ?>"
                        id    = "<?php echo esc_attr( $option[ 'id' ] ); ?>"
                        class = "option-field <?php echo isset( $option[ 'class' ] ) ? esc_attr( $option[ 'class' ] ) : ''; ?>"
                        style = "<?php echo isset( $option[ 'style' ] ) ? $option[ 'style' ] : ''; ?>"
                        value = "<?php echo $opt_key; ?>"
                        <?php echo $opt_key == $option_val ? 'checked' : ''; ?>>

                    <label class="<?php echo esc_attr( $option[ 'id' ] ); ?>"><?php echo $opt_text; ?></label>
                    <br>

                <?php } ?>

                <p class="desc"><?php echo isset( $option[ 'desc' ] ) ? $option[ 'desc' ] : ''; ?></p>
            </td>

            <script>
                jQuery( document ).ready( function( $ ) {

                    $( "label.<?php echo esc_attr( $option[ 'id' ] ); ?>" ).on( "click" , function() {

                        $( this ).prev( "input[type='radio']" ).trigger( "click" );

                    } );

                } );
            </script>
        </tr>

        <?php

    }

    /**
     * Render 'select' type option field.
     *
     * @since 3.0.0
     * @access public
     *
     * @param array $option Array of options data. May vary depending on option type.
     */
    public function render_select_option_field( $option ) {

        $allow_deselect = isset( $option[ 'allow_deselect' ] ) && $option[ 'allow_deselect' ]; ?>

        <tr valign="top" class="<?php echo esc_attr( $option[ 'id' ] ) . '-row'; ?>">
            <th scope="row"><?php echo sanitize_text_field( $option[ 'title' ] ); ?></th>

            <td class="forminp forminp-<?php echo sanitize_title( $option[ 'type' ] ) ?>">
                <select
                    data-placeholder = "<?php echo isset( $option[ 'placeholder' ] ) ? $option[ 'placeholder' ] : 'Choose an option...' ; ?>"
                    name  = "<?php echo esc_attr( $option[ 'id' ] ); ?>"
                    id    = "<?php echo esc_attr( $option[ 'id' ] ); ?>"
                    class = "option-field chosen-select <?php echo isset( $option[ 'class' ] ) ? esc_attr( $option[ 'class' ] ) : ''; ?>"
                    style = "<?php echo isset( $option[ 'style' ] ) ? $option[ 'style' ] : 'width:360px;'; ?>">

                    <?php if ( $allow_deselect ) { ?>
                        <option value=""></option>
                    <?php } ?>

                    <?php foreach ( $option[ 'options' ] as $opt_key => $opt_text ) { ?>

                        <option value="<?php echo $opt_key; ?>" <?php echo get_option( $option[ 'id' ] ) === $opt_key ? 'selected' : ''; ?>><?php echo $opt_text; ?></option>

                    <?php } ?>
                </select>
                <br>
                <p class="desc"><?php echo isset( $option[ 'desc' ] ) ? $option[ 'desc' ] : ''; ?></p>
            </td>

            <script>
                jQuery( document ).ready( function( $ ) {

                    <?php echo $allow_deselect ? 'var allow_deselect = true;' : 'var allow_deselect = false;'; ?>

                    $( '#<?php echo esc_attr( $option[ 'id' ] ); ?>' ).chosen( { allow_single_deselect : allow_deselect } );

                } );
            </script>
        </tr>

        <?php

    }

    /**
     * Render 'multiselect' type option field.
     *
     * @since 3.0.0
     * @access public
     *
     * @param array $option Array of options data. May vary depending on option type.
     */
    public function render_multiselect_option_field( $option ) {

        $option_val = get_option( $option[ 'id' ] );
        if ( !is_array( $option_val ) )
            $option_val = array(); ?>

        <tr valign="top" class="<?php echo esc_attr( $option[ 'id' ] ) . '-row'; ?>">
            <th scope="row"><?php echo sanitize_text_field( $option[ 'title' ] ); ?></th>

            <td class="forminp forminp-<?php echo sanitize_title( $option[ 'type' ] ) ?>">
                <select
                    multiple
                    data-placeholder = "<?php echo isset( $option[ 'placeholder' ] ) ? $option[ 'placeholder' ] : 'Choose an option...' ; ?>"
                    name  = "<?php echo esc_attr( $option[ 'id' ] ); ?>[]"
                    id    = "<?php echo esc_attr( $option[ 'id' ] ); ?>"
                    class = "option-field chosen-select <?php echo isset( $option[ 'class' ] ) ? esc_attr( $option[ 'class' ] ) : ''; ?>"
                    style = "<?php echo isset( $option[ 'style' ] ) ? $option[ 'style' ] : 'width:360px;'; ?>">

                    <?php foreach ( $option[ 'options' ] as $opt_key => $opt_text ) { ?>

                        <option value="<?php echo $opt_key; ?>" <?php echo in_array( $opt_key , $option_val ) ? 'selected="selected"' : ''; ?>><?php echo $opt_text; ?></option>

                    <?php } ?>
                </select>
                <br>
                <p class="desc"><?php echo isset( $option[ 'desc' ] ) ? $option[ 'desc' ] : ''; ?></p>
            </td>

            <script>
                jQuery( document ).ready( function( $ ) {

                    $( '#<?php echo esc_attr( $option[ 'id' ] ); ?>' ).chosen();

                } );
            </script>
        </tr>

        <?php

    }

    /**
     * Render 'toggle' type option field.
     * Basically a single check box style option field.
     *
     * @since 3.0.0
     * @access public
     *
     * @param array $option Array of options data. May vary depending on option type.
     */
    public function render_toggle_option_field( $option ) {

        ?>

        <tr valign="top" class="<?php echo esc_attr( $option[ 'id' ] ) . '-row'; ?>">
            <th scope="row"><?php echo sanitize_text_field( $option[ 'title' ] ); ?></th>

            <td class="forminp forminp-<?php echo sanitize_title( $option[ 'type' ] ) ?>">
                <input
                    type  = "checkbox"
                    name  = "<?php echo esc_attr( $option[ 'id' ] ); ?>"
                    id    = "<?php echo esc_attr( $option[ 'id' ] ); ?>"
                    class = "option-field <?php echo isset( $option[ 'class' ] ) ? esc_attr( $option[ 'class' ] ) : ''; ?>"
                    style = "<?php echo isset( $option[ 'style' ] ) ? $option[ 'style' ] : ''; ?>"
                    value = "yes"
                    <?php echo get_option( $option[ 'id' ] ) === "yes" ? 'checked' : ''; ?>>
                <label class="<?php echo esc_attr( $option[ 'id' ] ); ?>"><?php echo isset( $option[ 'desc' ] ) ? $option[ 'desc' ] : ''; ?></label>
            </td>

            <script>
                jQuery( document ).ready( function( $ ) {

                    $( "label.<?php echo esc_attr( $option[ 'id' ] ); ?>" ).on( "click" , function() {

                        $( this ).prev( "input[type='checkbox']" ).trigger( "click" );

                    } );

                } );
            </script>
        </tr>

        <?php

    }

    /**
     * Render 'editor' type option field.
     *
     * @since 3.0.0
     * @access public
     *
     * @param array $option Array of options data. May vary depending on option type.
     */
    public function render_editor_option_field( $option ) {

        $editor_value = html_entity_decode( get_option( $option[ 'id' ] ) ); ?>

        <tr valign="top" class="<?php echo esc_attr( $option[ 'id' ] ) . '-row'; ?>">
            <th scope="row"><?php echo sanitize_text_field( $option[ 'title' ] ); ?></th>

            <td class="forminp forminp-<?php echo sanitize_title( $option[ 'type' ] ) ?>">
                <style type="text/css"><?php echo "div#wp-" . $option[ 'id' ] . "-wrap{ width: 70% !important; }"; ?></style>

                <?php wp_editor( $editor_value , $option[ 'id' ] , array(
                    'wpautop' 		=> true,
                    'textarea_name'	=> $option[ 'id' ],
                    'editor_height' => '300'
                ) ); ?>
                <br>
                <p class="desc"><?php echo isset( $option[ 'desc' ] ) ? $option[ 'desc' ] : ''; ?></p>
            </td>
        </tr>

        <?php

    }

    /**
     * Render 'csv' type option field.
     *
     * @since 3.0.0
     * @access public
     *
     * @param array $option Array of options data. May vary depending on option type.
     */
    public function render_csv_option_field( $option ) {

        ?>

        <tr valign="top" class="<?php echo esc_attr( $option[ 'id' ] ) . '-row'; ?>">
            <th scope="row"><?php echo $option[ 'title' ]; ?></th>

            <td class="forminp forminp-<?php echo sanitize_title( $option[ 'type' ] ) ?>">
                <input
                    type  = "text"
                    name  = "<?php echo $option[ 'id' ]; ?>"
                    id    = "<?php echo $option[ 'id' ]; ?>"
                    class = "option-field <?php echo isset( $option[ 'class' ] ) ? $option[ 'class' ] : ''; ?>"
                    style = "<?php echo isset( $option[ 'style' ] ) ? $option[ 'style' ] : 'width: 360px;'; ?>"
                    value = "<?php echo get_option( $option[ 'id' ] ); ?>" >
                <br>
                <p class="desc"><?php echo isset( $option[ 'desc' ] ) ? $option[ 'desc' ] : ''; ?></p>
            </td>

            <script>
                jQuery( document ).ready( function( $ ) {

                    $( '#<?php echo $option[ 'id' ]; ?>' ).selectize( {
                        plugins   : [ 'restore_on_backspace' , 'remove_button' , 'drag_drop' ],
                        delimiter : ',',
                        persist   : false,
                        create    : function( input ) {
                            return {
                                value: input,
                                text: input
                            }
                        }
                    } );

                } );
            </script>
        </tr>

        <?php

    }

    /**
     * Render 'key_value' type option field. Do not need to be registered to WP Settings API.
     *
     * @since 3.0.0
     * @access public
     *
     * @param array $option Array of options data. May vary depending on option type.
     */
    public function render_key_value_option_field( $option ) {

        $option_value = get_option( $option[ 'id' ] );
        if ( !is_array( $option_value ) )
            $option_value =  array(); ?>

        <tr valign="top" class="<?php echo esc_attr( $option[ 'id' ] ) . '-row'; ?>">
            <th scope="row"><?php echo $option[ 'title' ]; ?></th>

            <td class="forminp forminp-<?php echo sanitize_title( $option[ 'type' ] ) ?>">

                <div class="key-value-fields-container" data-field-id="<?php echo $option[ 'id' ]; ?>">

                    <header>
                        <span class="key"><?php _e( 'Key' , 'idataffiliates' ); ?></span>
                        <span class="value"><?php _e( 'Value' , 'idataffiliates' ); ?></span>
                    </header>

                    <div class="fields">

                        <?php if ( empty( $option_value ) ) { ?>

                            <div class="data-set">
                                <input type="text" class="field key-field">
                                <input type="text" class="field value-field">
                                <div class="controls">
                                    <span class="control add dashicons dashicons-plus-alt" autocomplete="off"></span>
                                    <span class="control delete dashicons dashicons-dismiss" autocomplete="off"></span>
                                </div>
                            </div>

                        <?php } else {

                            foreach ( $option_value as $key => $val ) { ?>

                                <div class="data-set">
                                    <input type="text" class="field key-field" value="<?php echo $key; ?>">
                                    <input type="text" class="field value-field" value="<?php echo $val; ?>">
                                    <div class="controls">
                                        <span class="control add dashicons dashicons-plus-alt" autocomplete="off"></span>
                                        <span class="control delete dashicons dashicons-dismiss" autocomplete="off"></span>
                                    </div>
                                </div>

                            <?php }

                        } ?>

                    </div>

                </div>

                <p class="desc"><?php echo isset( $option[ 'desc' ] ) ? $option[ 'desc' ] : ''; ?></p>

            </td>
        </tr>

        <?php

    }

    /**
     * Render 'link' type option field. Do not need to be registered to WP Settings API.
     *
     * @since 3.0.0
     * @access public
     *
     * @param array $option Array of options data. May vary depending on option type.
     */
    public function render_link_option_field( $option ) {

        ?>

        <tr valign="top" class="<?php echo esc_attr( $option[ 'id' ] ) . '-row'; ?>">
            <th scope="row"><?php echo sanitize_text_field( $option[ 'title' ] ); ?></th>
            <td>
                <a id="<?php echo esc_attr( $option[ 'id' ] ); ?>" href="<?php echo $option[ 'link_url' ]; ?>" target="_blank"><?php echo $option[ 'link_text' ]; ?></a>
                <br>
                <p class="desc"><?php echo isset( $option[ 'desc' ] ) ? $option[ 'desc' ] : ''; ?></p>
            </td>
        </tr>

        <?php

    }

    /**
     * Render option divider. Decorative field. Do not need to be registered to WP Settings API.
     *
     * @since 3.0.0
     * @access public
     *
     * @param array $option Array of options data. May vary depending on option type.
     */
    public function render_option_divider_option_field( $option ) {

        ?>

        <tr valign="top" class="<?php echo esc_attr( $option[ 'id' ] ) . '-row'; ?>">
            <th scope="row" colspan="2">
                <h3><?php echo sanitize_text_field( $option[ 'title' ] ); ?></h3>
                <?php echo isset( $option[ 'markup' ] ) ? $option[ 'markup' ] : ''; ?>
            </th>
        </tr>

        <?php

    }

    /**
     * Render custom "migration_controls" field. Do not need to be registered to WP Settings API.
     *
     * @since 3.0.0
     * @access public
     *
     * @param array $option Array of options data. May vary depending on option type.
     */
    public function render_migration_controls_option_field( $option ) {

        $database_processing = apply_filters( 'ta_database_processing' , true ); // Flag to determine if another application is processing the db. ex. data downgrade.
        $processing          = "";
        $disabled            = false;

        if ( get_option( Plugin_Constants::MIGRATION_COMPLETE_FLAG ) === 'no' ) {

            $processing = "-processing";
            $disabled   = true;

        } ?>

        <tr valign="top" class="<?php echo esc_attr( $option[ 'id' ] ) . '-row'; ?>">

            <th scope="row" class="title_desc"><?php echo sanitize_text_field( $option[ 'title' ] ); ?></th>

            <td class="forminp forminp-<?php echo sanitize_title( $option[ 'type' ] ) ?> <?php echo $processing; ?>">

                <?php if ( !$database_processing ) { ?>

                    <p><?php _e( 'Another application is currently processing the database. Please wait for this to complete.' , 'idataffiliates' ); ?></p>

                <?php } else { ?>

                    <input
                        <?php echo $disabled ? "disabled" : ""; ?>
                        type="button"
                        id="<?php echo esc_attr( $option[ 'id' ] ); ?>"
                        class="button button-primary"
                        style="<?php echo isset( $option[ 'style' ] ) ? esc_attr( $option[ 'style' ] ) : ''; ?>"
                        value="<?php _e( 'Migrate' , 'idataffiliates' ); ?>">

                    <span class="spinner"></span>
                    <p class="status"><?php _e( 'Migrating data. Please wait...' , 'idataffiliates' ); ?></p>

                <?php } ?>

                <br /><br />
                <p class="desc"><?php echo isset( $option[ 'desc' ] ) ? $option[ 'desc' ] : ''; ?></p>

            </td>

        </tr>

        <?php

    }

    /**
     * Render custom "export_global_settings" field. Do not need to be registered to WP Settings API.
     *
     * @since 3.0.0
     * @access public
     *
     * @param array $option Array of options data. May vary depending on option type.
     */
    public function render_export_global_settings_option_field( $option ) {

        $global_settings_string = $this->get_global_settings_string(); ?>

        <tr valign="top" class="<?php echo esc_attr( $option[ 'id' ] ) . '-row'; ?>">
            <th scope="row" class="title_desc"><?php echo sanitize_text_field( $option[ 'title' ] ); ?></th>
            <td class="forminp forminp-<?php echo sanitize_title( $option[ 'type' ] ) ?>">
                <textarea
                    name="<?php echo esc_attr( $option[ 'id' ] ); ?>"
                    id="<?php echo esc_attr( $option[ 'id' ] ); ?>"
                    style="<?php echo isset( $option[ 'style' ] ) ? esc_attr( $option[ 'style' ] ) : ''; ?>"
                    class="<?php echo isset( $option[ 'class' ] ) ? esc_attr( $option[ 'class' ] ) : ''; ?>"
                    placeholder="<?php echo isset( $option[ 'placeholder' ] ) ? esc_attr( $option[ 'placeholder' ] ) : ''; ?>"
                    autocomplete="off"
                    readonly
                    rows="10"><?php echo $global_settings_string; ?></textarea>
                <div class="controls">
                    <a id="copy-settings-string" data-clipboard-target="#<?php echo esc_attr( $option[ 'id' ] ); ?>"><?php _e( 'Copy' , 'idataffiliates' ); ?></a>
                </div>
            </td>
        </tr>

        <?php

    }

    /**
     * Render custom "import_global_settings" field. Do not need to be registered to WP Settings API.
     *
     * @since 3.0.0
     * @access public
     *
     * @param array $option Array of options data. May vary depending on option type.
     */
    public function render_import_global_settings_option_field( $option ) {

        ?>

        <tr valign="top" class="<?php echo esc_attr( $option[ 'id' ] ) . '-row'; ?>">
            <th scope="row" class="title_desc">
                <label for="<?php echo esc_attr( $option[ 'id' ] ); ?>"><?php echo sanitize_text_field( $option[ 'title' ] ); ?></label>
            </th>
            <td class="forminp forminp-<?php echo sanitize_title( $option[ 'type' ] ) ?>">
                <textarea
                    name="<?php echo esc_attr( $option[ 'id' ] ); ?>"
                    id="<?php echo esc_attr( $option[ 'id' ] ); ?>"
                    style="<?php echo isset( $option[ 'style' ] ) ? esc_attr( $option[ 'style' ] ) : ''; ?>"
                    class="<?php echo isset( $option[ 'class' ] ) ? esc_attr( $option[ 'class' ] ) : ''; ?>"
                    placeholder="<?php echo esc_attr( $option[ 'placeholder' ] ); ?>"
                    autocomplete="off"
                    rows="10"></textarea>
                <p class="desc"><?php echo isset( $option[ 'description' ] ) ? $option[ 'description' ] : ''; ?></p>
                <div class="controls">
                    <span class="spinner"></span>
                    <input type="button" id="import-setting-button" class="button button-primary" value="<?php _e( 'Import Settings' , 'idataffiliates' ); ?>">
                </di>
            </td>
        </tr>

        <?php

    }




    /*
    |--------------------------------------------------------------------------
    | "key_value" option field type helpers
    |--------------------------------------------------------------------------
    */

    /**
     * Load styling relating to 'key_value' field type.
     *
     * @since 3.0.0
     * @access public
     */
    public function load_key_value_option_field_type_styling() {

        ?>

        <style>
            .key-value-fields-container header span {
                display: inline-block;
                font-weight: 600;
                margin-bottom: 8px;
            }
            .key-value-fields-container header .key {
                width: 144px;
            }
            .key-value-fields-container header .value {
                width: 214px;
            }
            .key-value-fields-container .fields .data-set {
                margin-bottom: 8px;
            }
            .key-value-fields-container .fields .data-set:last-child {
                margin-bottom: 0;
            }
            .key-value-fields-container .fields .data-set .key-field {
                width: 140px;
                margin-left: 0;
            }
            .key-value-fields-container .fields .data-set .value-field {
                width: 215px;
            }
            .key-value-fields-container .fields .data-set .controls {
                display: none;
            }
            .key-value-fields-container .fields .data-set:hover .controls {
                display: inline-block;
            }
            .key-value-fields-container .fields .data-set .controls .control {
                cursor: pointer;
            }
            .key-value-fields-container .fields .data-set .controls .add {
                color: green;
            }
            .key-value-fields-container .fields .data-set .controls .delete {
                color: red;
            }
        </style>

        <?php

    }

    /**
     * Load scripts relating to 'key_value' field type.
     *
     * @since 3.0.0
     * @access public
     */
    public function load_key_value_option_field_type_script() {

        ?>

        <script>

            jQuery( document ).ready( function( $ ) {

                // Hide the delete button if only 1 data set is available
                function init_data_set_controls() {

                    $( ".key-value-fields-container" ).each( function() {

                        if ( $( this ).find( ".data-set" ).length === 1 )
                            $( this ).find( ".data-set .controls .delete" ).css( "display" , "none" );
                        else
                            $( this ).find( ".data-set .controls .delete" ).removeAttr( "style" );

                    } );

                }

                init_data_set_controls();


                // Attach "add" and "delete" events
                $( ".key-value-fields-container" ).on( "click" , ".controls .add" , function() {

                    let $data_set = $( this ).closest( '.data-set' );

                    $data_set.after( "<div class='data-set'>" +
                                        "<input type='text' class='field key-field' autocomplete='off'> " +
                                        "<input type='text' class='field value-field' autocomplete='off'>" +
                                        "<div class='controls'>" +
                                            "<span class='control add dashicons dashicons-plus-alt'></span>" +
                                            "<span class='control delete dashicons dashicons-dismiss'></span>" +
                                        "</div>" +
                                    "</div>" );

                    init_data_set_controls();

                } );

                $( ".key-value-fields-container" ).on( "click" , ".controls .delete" , function() {

                    let $data_set = $( this ).closest( '.data-set' );

                    $data_set.remove();

                    init_data_set_controls();

                } );


                // Construct hidden fields for each of "key_value" option field types upon form submission
                $( "form" ).submit( function() {

                    $( ".key-value-fields-container" ).each( function() {

                        var $this        = $( this ),
                            field_id     = $this.attr( "data-field-id" ),
                            field_inputs = "";

                        $this.find( ".data-set" ).each( function() {

                            var $this       = $( this ),
                                key_field   = $.trim( $this.find( ".key-field" ).val() ),
                                value_field = $.trim( $this.find( ".value-field" ).val() );

                            if ( key_field !== "" && value_field !== "" )
                                field_inputs += "<input type='hidden' name='" + field_id + "[" + key_field + "]' value='" + value_field + "'>";

                        } );

                        $this.append( field_inputs );

                    } );

                } );

            } );

        </script>

        <?php

    }




    /*
    |--------------------------------------------------------------------------
    | Settings helper
    |--------------------------------------------------------------------------
    */

    /**
     * Get global settings string via ajax.
     *
     * @since 3.0.0
     * @access public
     */
    public function ajax_get_global_settings_string() {

        if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX )
            $response = array( 'status' => 'fail' , 'error_msg' => __( 'Invalid AJAX call' , 'idataffiliates' ) );
        else {

            $global_settings_string = $this->get_global_settings_string();

            if ( is_wp_error( $global_settings_string ) )
                $response = array( 'status' => 'fail' , 'error_msg' => $global_settings_string->get_error_message() );
            else
                $response = array( 'status' => 'success' , 'global_settings_string' => $global_settings_string );

        }

        @header( 'Content-Type: application/json; charset=' . get_option( 'blog_charset' ) );
        echo wp_json_encode( $response );
        wp_die();

    }

    /**
     * Get global settings string.
     *
     * @since 3.0.0
     * @access public
     *
     * @return WP_Error|string WP_Error on error, Base 64 encoded serialized global plugin settings otherwise.
     */
    public function get_global_settings_string() {

        if ( !$this->_helper_functions->current_user_authorized() )
            return new \WP_Error( 'ta_unauthorized_operation_export_settings' , __( 'Unauthorized operation. Only authorized accounts can access global plugin settings string' , 'idataffiliates' )  );

        $global_settings_arr = array();
        foreach ( $this->_exportable_options as $key => $default )
            $global_settings_arr[ $key ] = get_option( $key , $default );

        return base64_encode( serialize( $global_settings_arr ) );

    }

    /**
     * Import settings via ajax.
     *
     * @access public
     * @since 3.0.0
     */
    public function ajax_import_settings() {

        if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX )
            $response = array( 'status' => 'fail' , 'error_msg' => __( 'Invalid AJAX call' , 'idataffiliates' ) );
        elseif ( !isset( $_POST[ 'ta_settings_string' ] ) )
            $response = array( 'status' => 'fail' , 'error_msg' => __( 'Required parameter not passed' , 'idataffiliates' ) );
        else {

            $result = $this->import_settings( filter_var( $_POST[ 'ta_settings_string' ] , FILTER_SANITIZE_STRING ) );

            if ( is_wp_error( $result ) )
                $response = array( 'status' => 'fail' , 'error_msg' => $result->get_error_message() );
            else
                $response = array( 'status' => 'success' , 'success_msg' => __( 'Settings successfully imported' , 'idataffiliates' ) );

        }

        @header( 'Content-Type: application/json; charset=' . get_option( 'blog_charset' ) );
        echo wp_json_encode( $response );
        wp_die();

    }

    /**
     * Import settings from external global settings string.
     *
     * @since 3.0.0
     * @access public
     *
     * @param string $global_settings_string Settings string.
     * @return WP_Error | boolean WP_Error instance on failure, boolean true otherwise.
     */
    public function import_settings( $global_settings_string ) {

        if ( !$this->_helper_functions->current_user_authorized() )
            return new \WP_Error( 'ta_unauthorized_operation_import_settings' , __( 'Unauthorized operation. Only authorized accounts can import settings' , 'idataffiliates' )  );

        $settings_arr = @unserialize( base64_decode( $global_settings_string ) );

        if ( !is_array( $settings_arr ) )
            return new \WP_Error( 'ta_invalid_global_settings_string' , __( 'Invalid global settings string' , 'idataffiliates' ) , array( 'global_settings_string' => $global_settings_string ) );
        else {

            foreach ( $settings_arr as $key => $val ) {

                if ( !array_key_exists( $key , $this->_exportable_options ) )
                    continue;

                update_option( $key , $val );

            }

            return true;

        }

    }

    /**
     * Post update option callback for link prefix options.
     *
     * @since 3.0.0
     * @access public
     *
     * @param string $old_value Old option value before the update.
     * @param string $value     New value saved.
     * @param string $option    Option id.
     */
    public function link_prefix_post_update_callback( $value , $old_value , $option ) {

        if ( $option === 'ta_link_prefix' && $value === 'custom' )
            return $value;

        if ( $option === 'ta_link_prefix_custom' && get_option( 'ta_link_prefix' ) !== 'custom' )
            return $value;

        $used_link_prefixes = maybe_unserialize( get_option( 'ta_used_link_prefixes' , array() ) );
        $check_duplicate    = array_search( $value , $used_link_prefixes );

        if ( $check_duplicate !== false )
            unset( $used_link_prefixes[ $check_duplicate ] );

        $used_link_prefixes[] = sanitize_text_field( $value );
        $count                = count( $used_link_prefixes );

        if ( $count > 10 )
            $used_link_prefixes = array_slice( $used_link_prefixes , $count - 10 , 10 , false );

        update_option( 'ta_used_link_prefixes' , array_unique( $used_link_prefixes ) );

        return $value;
    }




    /*
    |--------------------------------------------------------------------------
    | Implemented Interface Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Execute codes that needs to run plugin activation.
     *
     * @since 3.0.0
     * @access public
     * @implements IDatAffiliates\Interfaces\Activatable_Interface
     */
    public function activate() {

        if ( get_option( 'ta_settings_initialized' ) !== 'yes' ) {

            update_option( 'ta_link_prefix' , 'recommends' );
            update_option( 'ta_link_prefix_custom' , '' );
            update_option( 'ta_used_link_prefixes' , array( 'recommends' ) );
            update_option( 'ta_settings_initialized' , 'yes' );
        }
    }

    /**
     * Execute codes that needs to run on plugin initialization.
     *
     * @since 3.0.0
     * @access public
     * @implements IDatAffiliates\Interfaces\Initiable_Interface
     */
    public function initialize() {

        add_action( 'wp_ajax_ta_get_global_settings_string' , array( $this , 'ajax_get_global_settings_string' ) );
        add_action( 'wp_ajax_ta_import_settings' , array( $this , 'ajax_import_settings' ) );

    }

    /**
     * Execute model.
     *
     * @implements WordPress_Plugin_Boilerplate\Interfaces\Model_Interface
     *
     * @since 3.0.0
     * @access public
     */
    public function run() {

        add_action( 'admin_init' , array( $this , 'init_plugin_settings' ) );
        add_action( 'admin_menu' , array( $this , 'add_settings_page' ) );

        add_action( 'ta_before_settings_form' , array( $this , 'load_key_value_option_field_type_styling' ) );
        add_action( 'ta_before_settings_form' , array( $this , 'load_key_value_option_field_type_script' ) );
        add_action( 'pre_update_option_ta_link_prefix' , array( $this , 'link_prefix_post_update_callback' ) , 10 , 3 );
        add_action( 'pre_update_option_ta_link_prefix_custom' , array( $this , 'link_prefix_post_update_callback' ) , 10 , 3 );
    }

}
