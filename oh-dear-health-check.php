<?php
/*
Plugin Name: Wordpress Oh Dear Health Check
Description: Wordpress plugin that adds ohDear functionality to project
Version: 1.0
Author: Jan Vodila & Denys Yefimenko
*/
if ( file_exists( realpath( __DIR__ ) . '/' . 'vendor/autoload.php' ) ) {
    require realpath( __DIR__ ) . '/' . 'vendor/autoload.php';
}
include_once dirname(__FILE__) . '/includes/Handler/oh-dear-health-check-rewrite.php';
include_once dirname(__FILE__) . '/includes/Controller/oh-dear-health-check-controller.php';

add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'oh_dear_health_check_settings_link');

function oh_dear_health_check_settings_link($links) {
    $settings_link = '<a href="options-general.php?page=oh-dear-health-check-settings">Settings</a>';
    array_unshift($links, $settings_link);
    return $links;
}

function oh_dear_health_check_menu() {
    add_menu_page(
        'Oh Dear Health Check',
        'Health Check',
        'manage_options',
        'oh-dear-health-check-settings',
        'oh_dear_health_check_settings_page',
        'dashicons-shield',
        80
    );
}
add_action('admin_menu', 'oh_dear_health_check_menu');

function oh_dear_health_check_settings_page() {
    if (!current_user_can('manage_options')) {
        wp_die('Sorry, you do not have permission to access this page.');
    }

    $secret_key = get_option('oh_dear_health_check_secret_key', '');

    if (isset($_POST['oh_dear_health_check_save'])) {
        $secret_key = sanitize_text_field($_POST['secret_key']);
        update_option('oh_dear_health_check_secret_key', $secret_key);
    }

    ?>
    <div class="wrap">
        <h2>Oh Dear Health Check Settings</h2>
        <form method="post">
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="secret_key">Secret Key</label></th>
                    <td><input type="text" id="secret_key" name="secret_key" value="<?php echo esc_attr($secret_key); ?>" class="regular-text" /></td>
                </tr>
            </table>
            <p class="submit">
                <input type="submit" name="oh_dear_health_check_save" id="submit" class="button button-primary" value="Save Changes">
            </p>
        </form>
    </div>
    <?php
}
