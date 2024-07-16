<?php
/**
 * Plugin Name: Broken Links Manager
 * Plugin URI: https://www.agence-digitalink.fr/
 * Description: Scans and manages broken links on your WordPress site.
 * Version: 1.0.0
 * Author: Agence Digitalink
 * Author URI: https://www.agence-digitalink.fr/
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: broken-links-manager
 * Domain Path: /languages
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

define('BROKEN_LINKS_MANAGER_VERSION', '1.0.0');
define('BROKEN_LINKS_MANAGER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('BROKEN_LINKS_MANAGER_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * The code that runs during plugin activation.
 */
function activate_broken_links_manager() {
    require_once BROKEN_LINKS_MANAGER_PLUGIN_DIR . 'includes/class-broken-links-manager-activator.php';
    Broken_Links_Manager_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_broken_links_manager() {
    require_once BROKEN_LINKS_MANAGER_PLUGIN_DIR . 'includes/class-broken-links-manager-deactivator.php';
    Broken_Links_Manager_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_broken_links_manager');
register_deactivation_hook(__FILE__, 'deactivate_broken_links_manager');

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require BROKEN_LINKS_MANAGER_PLUGIN_DIR . 'includes/class-broken-links-manager.php';

/**
 * Begins execution of the plugin.
 */
function run_broken_links_manager() {
    $plugin = new Broken_Links_Manager();
    $plugin->run();
}
run_broken_links_manager();