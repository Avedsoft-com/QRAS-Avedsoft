<?php

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
    die;
}

if (!current_user_can('manage_options')) {
    return;
}

//settings_errors() to show errors and save messages
settings_errors();


?>
<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    <form method="post" action="options.php">
        <?php
        // Displays hidden form fields on settings pages.
        settings_fields('qras_avedsoft_options_group');
        do_settings_sections('qras_avedsoft');
        submit_button();
        ?>
    </form>
</div>
