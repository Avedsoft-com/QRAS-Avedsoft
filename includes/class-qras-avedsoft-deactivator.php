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

    public static function deactivate() {
        // Delete plugin options from the database
        delete_option('qras_avedsoft_options');

        // Get all post IDs with QR code path using WP_Query
        $query = new WP_Query([
            'post_type'   => 'any',
            'meta_key'    => '_qr_code_path',
            'fields'      => 'ids',
            'nopaging'    => true
        ]);

        $post_ids = $query->posts;

        if (!empty($post_ids)) {
            foreach ($post_ids as $post_id) {
                // Delete post meta using WordPress functions
                $qr_code_path = get_post_meta($post_id, '_qr_code_path', true);
                delete_post_meta($post_id, '_qr_code_url');
                delete_post_meta($post_id, '_qr_code_id');
                delete_post_meta($post_id, '_qras_code_access');
                delete_post_meta($post_id, '_qr_code_last_access');
                delete_post_meta($post_id, '_qr_code_path');

                // File deletion process
                if ($qr_code_path && file_exists($qr_code_path)) {
                    wp_delete_file($qr_code_path);

                    // Remove the directory if it's empty
                    $directory = dirname($qr_code_path);
                    if (is_dir($directory) && count(scandir($directory)) == 2) {
                        rmdir($directory);
                    }
                }
            }
        }
    }
}

