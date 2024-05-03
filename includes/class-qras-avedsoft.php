<?php
// If this file is called directly, abort.
if (!defined('ABSPATH')) {
    die;
}

class QRAS_Avedsoft
{
    /**
     * Start up
     */
    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'qras_enqueue_styles'));
        add_action('wp_enqueue_scripts', array($this, 'qras_enqueue_scripts'));
        add_action('plugins_loaded', array($this, 'qras_load_textdomain'));
        add_action('add_meta_boxes', array($this, 'qras_avedsoft_add_qr_code_meta_box'));
        add_action('wp_ajax_generate_qr_code', array($this, 'qras_avedsoft_handle_generate_qr_code'));
        add_action('wp_ajax_delete_qr_code', array($this, 'qras_avedsoft_delete_qr_code'));
        add_action('wp_ajax_qras_toggle_access', array($this, 'qras_avedsoft_toggle_access'));
        add_action('template_redirect', array($this, 'qras_avedsoft_restrict_access_by_qr'));
        add_action('wp_ajax_get_saved_qr_code_url', array($this, 'qras_avedsoft_handle_get_saved_qr_code_url'));
    }


    /**
     * Handles the AJAX request to retrieve the saved QR code URL and access setting for a specific WordPress post.
     */
    public function qras_avedsoft_handle_get_saved_qr_code_url() {
        // Retrieve and sanitize the nonce value from POST data
        $nonce = isset($_POST['security']) ? sanitize_text_field(wp_unslash($_POST['security'])) : '';

        // Verify the nonce to ensure the request is secure
        if (!wp_verify_nonce($nonce, 'secure_nonce_qras')) {
            wp_send_json_error(array('message' => esc_html__('Nonce verification failed, please refresh the page and try again.', 'qras-avedsoft')));
            return;
        }

        // Retrieve and sanitize the post ID from POST data
        $post_id = isset($_POST['post_id']) ? intval(sanitize_text_field(wp_unslash($_POST['post_id']))) : 0;

        // Check if the current user has the capability to edit the post
        if (!$post_id || !current_user_can('edit_post', $post_id)) {
            wp_send_json_error(array('message' => esc_html__('Unauthorized access.', 'qras-avedsoft')));
            return;
        }

        // Retrieve the stored QR code URL and access meta from the post meta
        $qr_code_url = get_post_meta($post_id, '_qr_code_url', true);
        $access = get_post_meta($post_id, '_qras_code_access', true);

        // Set default access value if it is not set
        if ($access === '') {
            $access = '0'; // Default to unrestricted access if not specified
        }

        // Respond with the QR code URL and access information if available
        if (!empty($qr_code_url)) {
            wp_send_json_success(array(
                'url' => $qr_code_url,
                'access' => $access
            ));
        } else {
            // Provide default values and a message if no QR code has been generated yet
            wp_send_json_success(array(
                'url' => '',
                'access' => $access,
                'message' => esc_html__('QR code not generated yet.', 'qras-avedsoft')
            ));
        }
    }


    /**
     * Restricts direct access to the post if it's configured to allow access via QR code only.
     *
     * This method checks if a post is set to be accessible only through QR code scan.
     * It sanitizes and checks a specific GET parameter indicating the post was accessed through a QR scan.
     * If the parameter is not present or not valid, it redirects the user to the home page.
     */
    public function qras_avedsoft_restrict_access_by_qr() {
        if (is_singular()) {
            global $post;
            // Check if the post type is enabled for QR code access control
            if (!$this->qras_avedsoft_is_post_type_enabled($post->post_type)) {
                return;
            }

            $restrict_access = get_post_meta($post->ID, '_qras_code_access', true);
            if ('1' === $restrict_access) {
                $qr_id = isset($_GET['qr_id']) ? sanitize_text_field(wp_unslash($_GET['qr_id'])) : '';
                $saved_qr_id = get_post_meta($post->ID, '_qr_code_id', true);

                if ($saved_qr_id === $qr_id) {
                    $transient_key = 'qr_access_' . $post->ID;
                    // Check if the transient exists
                    if (false === get_transient($transient_key)) {
                        // Grant access for this request and set a transient to block further access
                        set_transient($transient_key, 'accessed', 10); // Blocks further access for 10 seconds
                    } else {
                        // If access was already granted once, redirect to home page
                        wp_redirect(home_url());
                        exit;
                    }
                } else {
                    // If the QR code ID does not match, redirect to home page
                    wp_redirect(home_url());
                    exit;
                }
            }
        }
    }


    /**
     * Adds a meta box for QRAS code to eligible post types in the admin edit screen.
     *
     * This method first retrieves the current screen to determine the post type being edited.
     * It then checks whether the QRAS code functionality is enabled for this post type using
     * the qras_avedsoft_is_post_type_enabled method. If the post type is not enabled,
     * the function returns early without adding the meta box. Otherwise, it proceeds to add
     * a meta box that will allow users to interact with the QRAS code features specific
     * to that post type. The meta box is added to the side context of the edit screen.
     *
     * @return void This function does not return a value and is used for its side effect of
     * adding a meta box.
     */
    public function qras_avedsoft_add_qr_code_meta_box()
    {
        $screen = get_current_screen();
        if (!$this->qras_avedsoft_is_post_type_enabled($screen->post_type)) {
            return;
        }

        add_meta_box(
            'qras_code_meta_box',               // Unique ID
            esc_html__('QRAS Code', 'qras-avedsoft'),                        // Box title
            array($this, 'qras_code_meta_box_html'), // Content callback, must be of type callable
            $screen->post_type,               // Post type
            'side'                            // Context
        );
    }


    /**
     * Checks if a given post type is enabled in the plugin settings.
     *
     * This method retrieves the plugin options from the WordPress database
     * and checks if the specified post type is enabled (i.e., selected by the user
     * in the plugin settings page).
     *
     * @param string $post_type The post type to check.
     * @return bool Returns true if the post type is enabled, false otherwise.
     */
    private function qras_avedsoft_is_post_type_enabled($post_type)
    {
        $post_type = sanitize_key($post_type);
        $options = get_option('qras_avedsoft_options');
        return isset($options['post_types'][$post_type]);
    }


    /**
     * Renders the HTML for the QRAS Code meta box in the post editor.
     *
     * This method outputs the HTML necessary for the QRAS Code meta box. It includes a textarea
     * for inputting the content to be encoded into a QR code, a button to trigger QR code generation,
     * and an image tag to display the generated QR code. It also provides links to download the QR code
     * in various formats. The textarea and button are wrapped within a div container, and the generated QR
     * code is retrieved from post meta, allowing it to be persistent across edits.
     *
     * @param WP_Post $post The current post object, which provides context for generating and displaying the QR code.
     * @return void This function outputs HTML directly and does not return any value.
     */
    public function qras_code_meta_box_html($post) {
        $saved_qr_code = get_post_meta($post->ID, '_qras_code', true);
        $qr_code_access = get_post_meta($post->ID, '_qras_code_access', true);

        echo '<div class="qras-code">
    <h1>' . esc_html__('QRAS Avedsoft Code', 'qras-avedsoft') . '</h1>
    <input type="hidden" id="post_id" value="' . esc_attr($post->ID) . '" />';

        if (!empty($saved_qr_code)) {
            echo '<img id="qras_code" src="' . esc_url($saved_qr_code) . '">
        <div class="qras-code-download-link">
            <a href="' . esc_url($saved_qr_code) . '" target="_blank">' . esc_html__('Download PNG', 'qras-avedsoft') . '</a>
            <button id="delete_qras_code" class="button button-secondary">' . esc_html__('Delete', 'qras-avedsoft') . '</button>
        </div>
        <div class="qras-code-access-control">
            <label for="qras_code_access">' . esc_html__('Restrict access by QR only', 'qras-avedsoft') . ':</label>';
            printf('<input type="checkbox" id="qras_code_access" %s />', checked($qr_code_access, '1', false));
            echo '</div>';
        } else {
            echo '<div class="actions">
            <button id="create_qras_code" class="button button-primary">' . esc_html__('Create', 'qras-avedsoft') . '</button>
        </div>
        <img id="qras_code" style="display:none;">
        <div class="qras-code-download-link" style="display:none;"></div>
        <div class="qras-code-access-control" style="display:none;">
            <label for="qras_code_access">' . esc_html__('Restrict access by QR only', 'qras-avedsoft') . ':</label>
            <input type="checkbox" id="qras_code_access"/>';
            echo '</div>';
        }

        echo '</div>';
    }


    /**
     * Handles AJAX requests to toggle access settings for a post based on QR code.
     *
     * This method verifies the nonce passed in the request for security, checks the user's permissions,
     * and updates the post meta based on whether access should be restricted to QR code only.
     * Responds with JSON indicating the outcome of the operation.
     *
     * @return void Sends a JSON response indicating success or failure.
     */
    public function qras_avedsoft_toggle_access() {
        // Sanitize and verify the nonce for security
        $nonce = isset($_POST['security']) ? sanitize_text_field(wp_unslash($_POST['security'])) : '';
        if (!wp_verify_nonce($nonce, 'secure_nonce_qras')) {
            wp_send_json_error(array('message' => esc_html__('Nonce verification failed', 'qras-avedsoft')));
            return;
        }

        // Sanitize and validate the post ID from the AJAX request
        $post_id = isset($_POST['post_id']) ? intval(sanitize_text_field(wp_unslash($_POST['post_id']))) : 0;
        if (!$post_id || !current_user_can('edit_post', $post_id)) {
            wp_send_json_error(array('message' => esc_html__('Unauthorized', 'qras-avedsoft')));
            return;
        }

        // Sanitize the access value to ensure it's safe for database insertion
        $access = isset($_POST['access']) ? sanitize_text_field(wp_unslash($_POST['access'])) : '';
        if ($access !== '1' && $access !== '0') {
            wp_send_json_error(array('message' => esc_html__('Invalid access value', 'qras-avedsoft')));
            return;
        }

        // Update the post meta with the new access value
        update_post_meta($post_id, '_qras_code_access', $access);

        // Send a success response to the client
        wp_send_json_success(array('message' => esc_html__('Access settings updated', 'qras-avedsoft')));
    }


    /**
     * Handles the AJAX request to generate a QR code for a specific WordPress post.
     *
     * Validates the AJAX request, checks user permissions, generates a QR code from the post URL with an access query parameter,
     * saves the QR code image locally, and sends the URL of the generated image back to the client.
     */
    public function qras_avedsoft_handle_generate_qr_code() {
        // Sanitize and verify the nonce for security
        $nonce = isset($_POST['security']) ? sanitize_text_field(wp_unslash($_POST['security'])) : '';
        if (!wp_verify_nonce($nonce, 'secure_nonce_qras')) {
            wp_send_json_error(array('message' => esc_html__('Nonce verification failed', 'qras-avedsoft')));
            return;
        }

        $post_id = isset($_POST['post_id']) ? intval(sanitize_text_field(wp_unslash($_POST['post_id']))) : 0;
        if (!$post_id || !current_user_can('edit_post', $post_id)) {
            wp_send_json_error(array('message' => esc_html__('Unauthorized', 'qras-avedsoft')));
            return;
        }

        $unique_token = wp_generate_password(20, false);
        $access_time = time();

        update_post_meta($post_id, '_qr_code_id', $unique_token);
        update_post_meta($post_id, '_qr_code_last_access', $access_time);

        $qr_code_url = add_query_arg('qr_id', $unique_token, get_permalink($post_id));
        $qr_url = 'https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=' . urlencode($qr_code_url);

        $response = wp_remote_get($qr_url);
        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) != 200) {
            wp_send_json_error(array('message' => esc_html__('Failed to download QR code', 'qras-avedsoft')));
            return;
        }

        $image_data = wp_remote_retrieve_body($response);

        $upload_dir = wp_upload_dir();
        $qr_code_path = $upload_dir['path'] . '/qr_code_' . $post_id . '.png';

        global $wp_filesystem;
        WP_Filesystem();
        if (!$wp_filesystem->put_contents($qr_code_path, $image_data)) {
            wp_send_json_error(array('message' => esc_html__('Failed to save QR code file', 'qras-avedsoft')));
            return;
        }

        // Save the URL and the path of the QR code image to post meta for future retrieval
        $qr_code_url = $upload_dir['url'] . '/qr_code_' . $post_id . '.png';
        update_post_meta($post_id, '_qr_code_url', $qr_code_url);
        update_post_meta($post_id, '_qr_code_path', $qr_code_path);

        // Return the URL of the saved QR code image
        wp_send_json_success(array('url' => $qr_code_url));
    }



    /**
     * Handles the AJAX request to delete a QR code associated with a WordPress post.
     *
     * Validates the AJAX request, checks user permissions, and attempts to delete the QR code file and metadata.
     * Returns a JSON response indicating success or failure.
     *
     * @return void Outputs a JSON response.
     */
    public function qras_avedsoft_delete_qr_code() {
        $nonce = isset($_POST['security']) ? sanitize_text_field(wp_unslash($_POST['security'])) : '';

        // Verify the nonce for security
        if (!wp_verify_nonce($nonce, 'secure_nonce_qras')) {
            wp_send_json_error(array('message' => esc_html__('Nonce verification failed', 'qras-avedsoft')));
            return;
        }

        // Sanitize and validate the post ID
        $post_id = isset($_POST['post_id']) ? intval(sanitize_text_field(wp_unslash($_POST['post_id']))) : 0;
        if (!$post_id || !current_user_can('delete_post', $post_id)) {
            wp_send_json_error(array('message' => esc_html__('Unauthorized', 'qras-avedsoft')));
            return;
        }

        // Retrieve the path to the QR code file from post meta
        $qr_code_path = get_post_meta($post_id, '_qr_code_path', true);
        $qr_code_url = get_post_meta($post_id, '_qr_code_url', true);

        if ($qr_code_path && file_exists($qr_code_path)) {
            // Attempt to delete the QR code file
            if (!wp_delete_file($qr_code_path)) {
                wp_send_json_error(array('message' => esc_html__('Failed to delete QR code file', 'qras-avedsoft')));
                return;
            }
        }

        // Delete QR code metadata
        delete_post_meta($post_id, '_qr_code_path');
        delete_post_meta($post_id, '_qr_code_url'); // Ensure URL metadata is also deleted
        delete_post_meta($post_id, '_qras_code_access');
        delete_post_meta($post_id, '_qr_code_last_access');

        // Success response
        wp_send_json_success(array('message' => esc_html__('QR Code deleted successfully', 'qras-avedsoft')));
    }


    /**
     * Initializes the textdomain for internationalization of the plugin.
     *
     * This method is responsible for loading the plugin's textdomain, which allows the plugin
     * to support internationalization and localization. The textdomain corresponds to the unique
     * identifier used in translation files, facilitating the translation of the plugin's strings.
     * The method uses the load_plugin_textdomain function, which defines the directory
     * where translation files are located relative to the plugin directory.
     *
     * @return void This function does not return a value and is used for its side effect of
     * loading the textdomain.
     */
    public function qras_load_textdomain()
    {
        load_plugin_textdomain('qras-avedsoft', false, QRAS_AVEDSOFT_PLUGIN_NAME . '/lang');
    }


    /**
     * Register the stylesheets for the public-facing side of the site
     */
    public function qras_enqueue_styles()
    {
        wp_enqueue_style(QRAS_AVEDSOFT_PLUGIN_NAME . '-public', QRAS_AVEDSOFT_PLUGIN_URL . 'assets/css/qras-avedsoft-public.css', array(), '1.0', 'all');
    }


    /**
     * Register the JavaScript for the public-facing side of the site.
     */
    public function qras_enqueue_scripts()
    {
        wp_enqueue_script(QRAS_AVEDSOFT_PLUGIN_NAME . '-public', QRAS_AVEDSOFT_PLUGIN_URL . 'assets/js/qras-avedsoft-public.js', array('jquery'), '1.0', true);
    }

}