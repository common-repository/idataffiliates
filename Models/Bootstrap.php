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
 * Model that houses the logic of 'Bootstraping' the plugin.
 *
 * @since 3.0.0
 */
class Bootstrap implements Model_Interface {

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
     * @var Bootstrap
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
     * Array of models implementing the IDatAffiliates\Interfaces\Activatable_Interface.
     *
     * @since 3.0.0
     * @access private
     * @var array
     */
    private $_activatables;

    /**
     * Array of models implementing the IDatAffiliates\Interfaces\Initiable_Interface.
     *
     * @since 3.0.0
     * @access private
     * @var array
     */
    private $_initiables;




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
     * @param array                      $activatables     Array of models implementing IDatAffiliates\Interfaces\Activatable_Interface.
     * @param array                      $initiables       Array of models implementing IDatAffiliates\Interfaces\Initiable_Interface.
     */
    public function __construct( Abstract_Main_Plugin_Class $main_plugin , Plugin_Constants $constants , Helper_Functions $helper_functions , array $activatables = array() , array $initiables = array() ) {

        $this->_constants        = $constants;
        $this->_helper_functions = $helper_functions;
        $this->_activatables     = $activatables;
        $this->_initiables       = $initiables;

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
     * @param array                      $activatables     Array of models implementing IDatAffiliates\Interfaces\Activatable_Interface.
     * @param array                      $initiables       Array of models implementing IDatAffiliates\Interfaces\Initiable_Interface.
     * @return Bootstrap
     */
    public static function get_instance( Abstract_Main_Plugin_Class $main_plugin , Plugin_Constants $constants , Helper_Functions $helper_functions , array $activatables = array() , array $initiables = array() ) {

        if ( !self::$_instance instanceof self )
            self::$_instance = new self( $main_plugin , $constants , $helper_functions , $activatables , $initiables );

        return self::$_instance;

    }

    /**
     * Load plugin text domain.
     *
     * @since 3.0.0
     * @access public
     */
    public function load_plugin_textdomain() {

        load_plugin_textdomain( Plugin_Constants::TEXT_DOMAIN , false , $this->_constants->PLUGIN_DIRNAME() . '/languages' );

    }

    /**
     * Method that houses the logic relating to activating the plugin.
     *
     * @since 3.0.0
     * @access public
     *
     * @global wpdb $wpdb Object that contains a set of functions used to interact with a database.
     *
     * @param boolean $network_wide Flag that determines whether the plugin has been activated network wid ( on multi site environment ) or not.
     */
    public function activate_plugin( $network_wide ) {

        // Suppress any errors
        @deactivate_plugins( array(
            'idataffiliates-stats/idataffiliates-stats.php',
            'idataffiliates-htaccess-redirect/idataffiliates-htaccess-refactor.bootstrap.php',
            'idataffiliates-google-click-tracking/idataffiliates-google-click-tracking.php',
            'idataffiliates-geolocations/idataffiliates-geolocations.php',
            'idataffiliates-csv-importer/idataffiliates-csv-importer.php',
            'idataffiliates-autolinker/idataffiliates-autolinker.php',
            'idataffiliates-azon-add-on/azon-bootstrap.php',
            'idataffiliates-itunes/idataffiliates-itunes.bootstrap.php'
        ) , true , null ); // Deactivate on all sites in the network and do not fire deactivation hooks
        
        global $wpdb;

        if ( is_multisite() ) {

            if ( $network_wide ) {

                // get ids of all sites
                $blog_ids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );

                foreach ( $blog_ids as $blog_id ) {

                    switch_to_blog( $blog_id );
                    $this->_activate_plugin( $blog_id );

                }

                restore_current_blog();

            } else
                $this->_activate_plugin( $wpdb->blogid ); // activated on a single site, in a multi-site

        } else
            $this->_activate_plugin( $wpdb->blogid ); // activated on a single site

    }

    /**
     * Method to initialize a newly created site in a multi site set up.
     *
     * @since 3.0.0
     * @access public
     *
     * @param int    $blogid Blog ID of the created blog.
     * @param int    $user_id User ID of the user creating the blog.
     * @param string $domain Domain used for the new blog.
     * @param string $path Path to the new blog.
     * @param int    $site_id Site ID. Only relevant on multi-network installs.
     * @param array  $meta Meta data. Used to set initial site options.
     */
    public function new_mu_site_init( $blog_id , $user_id , $domain , $path , $site_id , $meta ) {

        if ( is_plugin_active_for_network( 'idataffiliates/idataffiliates.php' ) ) {

            switch_to_blog( $blog_id );
            $this->_activate_plugin( $blog_id );
            restore_current_blog();

        }

    }

    /**
     * Initialize plugin settings options.
     * This is a compromise to my idea of 'Modularity'. Ideally, bootstrap should not take care of plugin settings stuff.
     * However due to how WooCommerce do its thing, we need to do it this way. We can't separate settings on its own.
     *
     * @since 3.0.0
     * @access private
     */
    private function _initialize_plugin_settings_options() {

        // Help settings section options

        // Set initial value of 'no' for the option that sets the option that specify whether to delete the options on plugin uninstall. Optionception.
        if ( !get_option( Plugin_Constants::CLEAN_UP_PLUGIN_OPTIONS , false ) )
            update_option( Plugin_Constants::CLEAN_UP_PLUGIN_OPTIONS , 'no' );

    }

    /**
     * Actual function that houses the code to execute on plugin activation.
     *
     * @since 3.0.0
     * @since 3.0.0 Added the ta_activation_date option
     * @access private
     *
     * @param int $blogid Blog ID of the created blog.
     */
    private function _activate_plugin( $blogid ) {

        // Initialize settings options
        $this->_initialize_plugin_settings_options();

        // Create database tables
        $this->_create_database_tables();

        // set flush rewrite rules transient so it can be flushed after the CPT and rewrite rules have been registered.
        set_transient( 'ta_flush_rewrite_rules' , 'true' , 5 * 60 );

        // Execute 'activate' contract of models implementing IDatAffiliates\Interfaces\Activatable_Interface
        foreach ( $this->_activatables as $activatable )
            if ( $activatable instanceof Activatable_Interface )
                $activatable->activate();

        update_option( Plugin_Constants::INSTALLED_VERSION , Plugin_Constants::VERSION ); // Update current installed plugin version
        update_option( 'ta_activation_code_triggered' , 'yes' ); // Mark activation code triggered

    }

    /**
     * Method that houses the logic relating to deactivating the plugin.
     *
     * @since 3.0.0
     * @access public
     *
     * @global wpdb $wpdb Object that contains a set of functions used to interact with a database.
     *
     * @param boolean $network_wide Flag that determines whether the plugin has been activated network wid ( on multi site environment ) or not.
     */
    public function deactivate_plugin( $network_wide ) {

        global $wpdb;

        // check if it is a multisite network
        if ( is_multisite() ) {

            // check if the plugin has been activated on the network or on a single site
            if ( $network_wide ) {

                // get ids of all sites
                $blog_ids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );

                foreach ( $blog_ids as $blog_id ) {

                    switch_to_blog( $blog_id );
                    $this->_deactivate_plugin( $wpdb->blogid );

                }

                restore_current_blog();

            } else
                $this->_deactivate_plugin( $wpdb->blogid ); // activated on a single site, in a multi-site

        } else
            $this->_deactivate_plugin( $wpdb->blogid ); // activated on a single site

    }

    /**
     * Actual method that houses the code to execute on plugin deactivation.
     *
     * @since 3.0.0
     * @access private
     *
     * @param int $blogid Blog ID of the created blog.
     */
    private function _deactivate_plugin( $blogid ) {

        flush_rewrite_rules();

    }

    /**
     * Create database tables.
     *
     * @since 3.0.0
     * @access private
     */
    private function _create_database_tables() {

        global $wpdb;

        if ( get_option( 'ta_database_tables_created' ) === 'yes' )
            return;

        $link_click_db      = $wpdb->prefix . Plugin_Constants::LINK_CLICK_DB;
        $link_click_meta_db = $wpdb->prefix . Plugin_Constants::LINK_CLICK_META_DB;
        $charset_collate    = $wpdb->get_charset_collate();

        // link clicks db sql command
        $sql  = "CREATE TABLE $link_click_db (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            link_id bigint(20) NOT NULL,
            date_clicked datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;\n";

        // link clicks meta db sql command
        $sql .= "CREATE TABLE $link_click_meta_db (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            click_id bigint(20) NOT NULL,
            meta_key varchar(255) NULL,
            meta_value longtext NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );

        update_option( 'ta_database_tables_created' , 'yes' );
    }

    /**
     * Add custom links to the plugin's action links.
     *
     * @since 3.0.0
     * @access public
     */
    public function plugin_action_links( $links ) {

        $settings_link = admin_url( 'edit.php?post_type=' . Plugin_Constants::AFFILIATE_LINKS_CPT . '&page=idat-settings' );

        $new_links = array(
            '<a href="' . $settings_link . '">' . __( 'Settings' , 'idataffiliates' ) . '</a>'
        );

        return array_merge( $new_links , $links );
    }

    /**
     * Method that houses codes to be executed on init hook.
     *
     * @since 3.0.0
     * @access public
     */
    public function initialize() {

        // Execute activation codebase if not yet executed on plugin activation ( Mostly due to plugin dependencies )
        if ( get_option( 'ta_activation_code_triggered' , false ) !== 'yes' ) {

            if ( ! function_exists( 'is_plugin_active_for_network' ) )
                require_once( ABSPATH . '/wp-admin/includes/plugin.php' );

            $network_wide = is_plugin_active_for_network( 'idataffiliates/idataffiliates.php' );
            $this->activate_plugin( $network_wide );

        }

        // Execute 'initialize' contract of models implementing IDatAffiliates\Interfaces\Initiable_Interface
        foreach ( $this->_initiables as $initiable )
            if ( $initiable instanceof Initiable_Interface )
                $initiable->initialize();

    }

    /**
     * Execute plugin bootstrap code.
     *
     * @since 3.0.0
     * @access public
     */
    public function run() {

        // Internationalization
        add_action( 'plugins_loaded' , array( $this , 'load_plugin_textdomain' ) );

        // Execute plugin activation/deactivation
        register_activation_hook( $this->_constants->MAIN_PLUGIN_FILE_PATH() , array( $this , 'activate_plugin' ) );
        register_deactivation_hook( $this->_constants->MAIN_PLUGIN_FILE_PATH() , array( $this , 'deactivate_plugin' ) );

        // Execute plugin initialization ( plugin activation ) on every newly created site in a multi site set up
        add_action( 'wpmu_new_blog' , array( $this , 'new_mu_site_init' ) , 10 , 6 );

        add_filter( 'plugin_action_links_' . $this->_constants->PLUGIN_BASENAME() , array( $this , 'plugin_action_links' ) );

        // Execute codes that need to run on 'init' hook
        add_action( 'init' , array( $this , 'initialize' ) );

    }

}
