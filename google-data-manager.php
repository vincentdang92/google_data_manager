<?php
/**
 * Plugin Name: Google Data Manager
 * Description: Sync Google Sheet with smart search.
 * Version: 1.0.0
 * Requires PHP: 8.0
 * Author: Vincent Dang
 * Text Domain: google-data-manager
 */

if (!defined('ABSPATH')) exit;

define('GDM_VERSION', '1.0.0');
define('GDM_PATH', plugin_dir_path(__FILE__));
define('GDM_URL',  plugin_dir_url(__FILE__));

require_once GDM_PATH . 'includes/class-gdm-activator.php';
require_once GDM_PATH . 'includes/class-gdm-deactivator.php';
require_once GDM_PATH . 'includes/class-gdm.php';

register_activation_hook(__FILE__, ['GDM_Activator', 'activate']);
register_deactivation_hook(__FILE__, ['GDM_Deactivator', 'deactivate']);

add_action('plugins_loaded', function () {
    (new GDM())->run();
});
