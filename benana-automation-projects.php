<?php
/*
Plugin Name: بنانا اتوماسیون پروژه‌ها
Plugin URI: https://github.com/aryahzz/banana-automation
Description: سیستم اتوماسیون مدیریت پروژه با Gravity Forms و WP-SMS.
Version: 1.2.7
Requires at least: 6.0
Requires PHP: 7.4
Author: Banana Automation
Author URI: https://github.com/aryahzz
Text Domain: benana-automation-projects
Domain Path: /languages
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'BENANA_AUTOMATION_PATH', plugin_dir_path( __FILE__ ) );
define( 'BENANA_AUTOMATION_URL', plugin_dir_url( __FILE__ ) );
define( 'BENANA_AUTOMATION_VERSION', '1.2.7' );

require_once BENANA_AUTOMATION_PATH . 'includes/class-address.php';
require_once BENANA_AUTOMATION_PATH . 'includes/class-cpt.php';
require_once BENANA_AUTOMATION_PATH . 'includes/class-settings.php';
require_once BENANA_AUTOMATION_PATH . 'includes/class-sms.php';
require_once BENANA_AUTOMATION_PATH . 'includes/class-gravity.php';
require_once BENANA_AUTOMATION_PATH . 'includes/class-shortcodes.php';
require_once BENANA_AUTOMATION_PATH . 'includes/class-user-profile.php';
require_once BENANA_AUTOMATION_PATH . 'includes/class-merge-tags.php';
require_once BENANA_AUTOMATION_PATH . 'includes/class-project-handler.php';
require_once BENANA_AUTOMATION_PATH . 'includes/class-updater.php';

class Benana_Automation_Projects {
    public function __construct() {
        add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
        add_action( 'init', array( $this, 'init_classes' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_front_assets' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
    }

    public function load_textdomain() {
        load_plugin_textdomain( 'benana-automation-projects', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
    }

    public function init_classes() {
        new Benana_Automation_CPT();
        new Benana_Automation_Settings();
        new Benana_Automation_SMS();
        new Benana_Automation_Gravity();
        new Benana_Automation_Shortcodes();
        new Benana_Automation_User_Profile();
        new Benana_Automation_Merge_Tags();
        new Benana_Automation_Project_Handler();
        new Benana_Automation_Updater();
    }

    public function enqueue_front_assets() {
        wp_enqueue_style( 'benana-automation-front', BENANA_AUTOMATION_URL . 'assets/css/frontend.css', array(), '1.0.0' );
        wp_enqueue_style( 'benana-automation-rtl', BENANA_AUTOMATION_URL . 'assets/css/rtl.css', array( 'benana-automation-front' ), '1.0.0' );
        wp_enqueue_script( 'benana-automation-front', BENANA_AUTOMATION_URL . 'assets/js/frontend.js', array( 'jquery' ), '1.0.0', true );
        wp_localize_script(
            'benana-automation-front',
            'benanaAddress',
            array(
                'provinces' => Benana_Automation_Address::get_provinces(),
                'cities'    => Benana_Automation_Address::get_cities(),
            )
        );
    }

    public function enqueue_admin_assets( $hook ) {
        wp_enqueue_style( 'benana-automation-admin', BENANA_AUTOMATION_URL . 'assets/css/admin.css', array(), '1.0.0' );
        wp_enqueue_style( 'benana-automation-rtl', BENANA_AUTOMATION_URL . 'assets/css/rtl.css', array( 'benana-automation-admin' ), '1.0.0' );
        wp_enqueue_script( 'benana-automation-admin', BENANA_AUTOMATION_URL . 'assets/js/admin.js', array( 'jquery' ), '1.0.0', true );
        wp_localize_script(
            'benana-automation-admin',
            'benanaAddress',
            array(
                'provinces' => Benana_Automation_Address::get_provinces(),
                'cities'    => Benana_Automation_Address::get_cities(),
            )
        );
    }
}

new Benana_Automation_Projects();
