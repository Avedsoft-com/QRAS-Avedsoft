<?php

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
    die;
}

/**
 * Class QRAS_Avedsoft_Deactivator
 * This class handles deactivation of the QRAS Avedsoft plugin.
 */
class QRAS_Avedsoft_Deactivator {

    /**
     * Deactivate the plugin and clean up associated data.
     * This method deletes plugin options from the database and removes QR code-related data and files.
     */
    public static function deactivate() {
        global $wpdb;
        // Delete plugin options from the database
        delete_option('qras_avedsoft_options');

        // Direct SQL query to get all post IDs with the QR code path meta key
        $post_ids = $wpdb->get_col("SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_qr_code_path'");

        // Iterate over each post ID to delete meta fields and associated file
        foreach ($post_ids as $post_id) {
            // Retrieve the path to the QR code file from post meta
            $qr_code_path = get_post_meta($post_id, '_qr_code_path', true);

            // Delete all associated post meta
            $meta_keys = ['_qr_code_url', '_qr_code_id', '_qras_code_access', '_qr_code_last_access', '_qr_code_path'];
            foreach ($meta_keys as $meta_key) {
                $wpdb->delete($wpdb->postmeta, array('post_id' => $post_id, 'meta_key' => $meta_key), array('%d', '%s'));
            }

            // Delete the file if it exists
            if ($qr_code_path && file_exists($qr_code_path)) {
                unlink($qr_code_path);

                // Remove the directory if it's empty
                $directory = dirname($qr_code_path);
                if (is_dir($directory) && count(scandir($directory)) == 2) {
                    rmdir($directory);
                }
            }
        }
    }


}
