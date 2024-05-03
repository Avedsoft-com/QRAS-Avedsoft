<?php

/**
 * Plugin Name:       QRAS Avedsoft
 * Plugin URI:        https://github.com/Avedsoft-com/QRAS-Avedsoft
 * Description:       Plugin for generating QR codes for custom post types in WordPress, with access to posts only through a QR code.
 * Tags:              QR Code, Generating QR codes
 * Version:           1.0
 * Author:            ss1919
 * Author URI:        https://github.com/ss1919
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       qras-avedsoft
 * Domain Path: /lang
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
    die;
}

/**

 * The core plugin path that is used to define internationalization

 */

define( 'QRAS_AVEDSOFT_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );


/**

 * The core plugin url that is used to define internationalization

 */

define( 'QRAS_AVEDSOFT_PLUGIN_URL', plugin_dir_url( __FILE__ ) );


/**

 * The core plugin name that is used to define internationalization

 */
define( 'QRAS_AVEDSOFT_PLUGIN_NAME', dirname( plugin_basename( __FILE__ ) ) );


// Activate and deactivate the plugin
function activate_qras_avedsoft() {
    require_once QRAS_AVEDSOFT_PLUGIN_DIR . 'includes/class-qras-avedsoft-activator.php';
    QRAS_Avedsoft_Activator::activate();
}

function deactivate_qras_avedsoft() {
    require_once QRAS_AVEDSOFT_PLUGIN_DIR . 'includes/class-qras-avedsoft-deactivator.php';
    if ( class_exists( 'QRAS_Avedsoft_Deactivator' ) ) {
        QRAS_Avedsoft_Deactivator::deactivate();
    }
}

register_activation_hook(__FILE__, 'activate_qras_avedsoft');
register_deactivation_hook(__FILE__, 'deactivate_qras_avedsoft');


// Load the main plugin class
require QRAS_AVEDSOFT_PLUGIN_DIR . 'includes/class-qras-avedsoft.php';
require QRAS_AVEDSOFT_PLUGIN_DIR . 'includes/class-qras-avedsoft-admin.php';

function run_qras_avedsoft() {
    if ( class_exists( 'QRAS_Avedsoft' ) ) {
        $qras_avedsoft_plugin = new QRAS_Avedsoft();
    }
    if ( class_exists( 'QRAS_Avedsoft_Admin' ) ) {
        $qras_avedsoft_plugin_admin = new QRAS_Avedsoft_Admin();
    }
}

run_qras_avedsoft();