<?php
/*
 * This file is the main entry point for WordPress functions.
 */
    // Misc WordPress functions
    include_once get_template_directory() . '/functions/wp-functions.php';

    // Misc Graph QL functions, mostly filters used to extend the Schema
    include_once get_template_directory() . '/functions/gql-functions.php';

    // Any changes to setup the theme (images, menus) go in here
    include_once get_template_directory() . '/functions/theme-config.php';

    // Handles the server side processing of WordPress shortcodes
    include_once get_template_directory() . '/functions/shortcodes.php';

    // Add additional ACF functionality
    include_once get_template_directory() . '/functions/acf-functions.php';

/*
 * Generally you don't have to edit any of the files below
 */
    // Handles plugin dependencies
    include_once get_template_directory() . '/functions/plugin-manifest.php';

    // Handles Developer role
    include_once get_template_directory() . '/functions/developer-role.php';
