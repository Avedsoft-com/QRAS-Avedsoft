<?php

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
    die;
}

class QRAS_Avedsoft_Admin {
    /**
     * Start up
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'qras_add_plugin_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'qras_enqueue_styles'));
        add_action('admin_enqueue_scripts', array($this, 'qras_enqueue_scripts'));
        add_action('admin_init', array($this, 'qras_avedsoft_setup_settings'));
    }


    /**
     *  Register an option to store plugin settings
     */
    public function qras_avedsoft_setup_settings() {
        register_setting(
            'qras_avedsoft_options_group', // Settings group
            'qras_avedsoft_options', // Option name
            array($this, 'qras_avedsoft_sanitize_settings') // Function for clearing saved settings
        );

        // Adding a new section to the plugin settings page
        add_settings_section(
            'qras_avedsoft_settings_section', // Section ID
            esc_html__('QRAS code Settings', 'qras-avedsoft'), // Section title
            array($this, 'qras_avedsoft_settings_section_callback'), // Callback function to display the section description
            'qras_avedsoft' // The page on which the section should be shown
        );

        // Adding a new field to the plugin settings page
        add_settings_field(
            'qras_avedsoft_post_types', // Field ID
            esc_html__('Post types', 'qras-avedsoft'), // Field title
            array($this, 'qras_avedsoft_post_types_callback'), // Callback function to display the HTML code of the field
            'qras_avedsoft', // Page on which the field should be shown
            'qras_avedsoft_settings_section' // Section to which the field should be attached
        );
    }


    /**
     * Here you must clear each setting before saving
     */
    public function qras_avedsoft_sanitize_settings($inputs) {
        if (!isset($_POST['qras_avedsoft_settings_nonce']) || !wp_verify_nonce( sanitize_text_field( wp_unslash ( $_POST['qras_avedsoft_settings_nonce'] ) ), 'qras_avedsoft_update_settings_action') ) {
            return get_option('qras_avedsoft_options');
        }

        $new_input = array();
        if (isset($inputs['post_types'])) {
            foreach ($inputs['post_types'] as $post_type => $value) {
                $new_input['post_types'][$post_type] = ($value === '1' ? true : false);
            }
        }
        return $new_input;
    }


    /**
     * Callback function to display the section description
     */
    public function qras_avedsoft_settings_section_callback() {
        echo  esc_html__('Select the post types for which you want to generate QR codes.', 'qras-avedsoft');
    }


    /**
     * Get current or default settings
     */
    public function qras_avedsoft_post_types_callback() {
        $options = get_option('qras_avedsoft_options');
        $post_types = get_post_types(array('public' => true), 'objects');

        wp_nonce_field('qras_avedsoft_update_settings_action', 'qras_avedsoft_settings_nonce');

        foreach ($post_types as $post_type) {
            $checked = isset($options['post_types'][$post_type->name]) && $options['post_types'][$post_type->name] ? 'checked' : '';
            printf(
                '<input type="checkbox" id="post_types_%1$s" name="qras_avedsoft_options[post_types][%1$s]" value="1" %2$s />' .
                '<label for="post_types_%1$s"> %3$s</label><br />',
                esc_attr($post_type->name),
                esc_attr($checked),
                esc_html($post_type->labels->singular_name)
            );
        }
    }


    /**
     * Adding the main menu of the plugin to the admin panel
     */
    public function qras_add_plugin_admin_menu() {
        add_menu_page(
            esc_html__('QRAS Avedsoft Settings', 'qras-avedsoft'),
            esc_html__('QRAS Avedsoft', 'qras-avedsoft'),
            'manage_options',
            'qras-avedsoft-settings',
            array($this, 'qras_display_plugin_setup_page'), // Function to display the contents of the menu page
            'dashicons-forms',
            25
        );
    }


    /**
     * Display functions for menus and submenus
     */
    public function qras_display_plugin_setup_page() {
        include_once('partials/qras-avedsoft-admin-display.php');
    }


    /**
     * Register the stylesheets for the admin-facing side of the site
     */
    public function qras_enqueue_styles() {
        wp_enqueue_style(QRAS_AVEDSOFT_PLUGIN_NAME . '-admin', QRAS_AVEDSOFT_PLUGIN_URL . 'assets/css/qras-avedsoft-admin.css', array(), '1.0', 'all');
    }


    /**
     * Register the JavaScript for the admin-facing side of the site.
     */
    public function qras_enqueue_scripts() {
        wp_enqueue_script(QRAS_AVEDSOFT_PLUGIN_NAME . '-admin', QRAS_AVEDSOFT_PLUGIN_URL . 'assets/js/qras-avedsoft-admin.js', array('jquery'), '1.0', true);

        // Localize script for dynamic JS use
        wp_localize_script(QRAS_AVEDSOFT_PLUGIN_NAME . '-admin', 'ajax_object', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'security' => wp_create_nonce('secure_nonce_qras'),
            'messages' => array(
                'qrGenerated' => esc_html__('QR Code generated successfully.', 'qras-avedsoft'),
                'qrGenerateError' => esc_html__('Failed to generate QR code.', 'qras-avedsoft'),
                'deleteConfirm' => esc_html__('Are you sure you want to delete this QR Code?', 'qras-avedsoft'),
                'qrDeleted' => esc_html__('QR Code deleted successfully.', 'qras-avedsoft'),
                'qrDeleteError' => esc_html__('Failed to delete QR code.', 'qras-avedsoft'),
                'accessUpdated' => esc_html__('Access settings updated.', 'qras-avedsoft'),
                'accessUpdateFailed' => esc_html__('Failed to update access settings.', 'qras-avedsoft'),
                'confirmAccessChange' => esc_html__('Are you sure you want to change access settings?', 'qras-avedsoft'),
                'downloadPNG' => esc_html__('Download PNG', 'qras-avedsoft'),
                'delete' => esc_html__('Delete', 'qras-avedsoft'),
                'loadingError' => esc_html__('Error loading data. Please try again.', 'qras-avedsoft'),
                'savingError' => esc_html__('Error saving data. Please try again.', 'qras-avedsoft')
            )
        ));
    }

}