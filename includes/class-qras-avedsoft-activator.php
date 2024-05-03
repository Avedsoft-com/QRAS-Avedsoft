<?php

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
    die;
}

/**
 * Class QRAS_Avedsoft_Activator
 *
 * Handles the initial setup when the plugin is activated. It ensures that necessary
 * options are created in the database to facilitate the plugin's operation.
 */
class QRAS_Avedsoft_Activator {

    /**
     * Activate the plugin.
     *
     * This method is called when the plugin is activated. It initializes necessary
     * options in the database without setting any default values.
     */
    public static function activate() {
        // Initialize the option with an empty array or minimal structure if it doesn't exist
        if (!get_option('qras_avedsoft_options')) {
            add_option('qras_avedsoft_options', array());  // Initialize with an empty array
        }
    }
}

