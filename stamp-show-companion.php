<?php
/**
 * Plugin Name: Stamp.Show Companion
 * Description: Show your Stamp.Show expo (palmares, sponsors, prizes, judges, commissioners, catalog and the application form) on your WordPress site.
 * Plugin URI: https://stamp.show/developers/wordpress/
 * Author: Stamp.Show
 * Author URI: https://stamp.show/
 * Version: 0.1.3
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * License: GPL-2.0-or-later
 * Text Domain: stamp-show-companion
 */

defined('ABSPATH') || exit;

define('SSC_VERSION', '0.1.3');
define('SSC_DIR', plugin_dir_path(__FILE__));

require_once SSC_DIR . 'includes/class-ssc-client.php';
require_once SSC_DIR . 'includes/class-ssc-settings.php';
require_once SSC_DIR . 'includes/class-ssc-webhook.php';
require_once SSC_DIR . 'includes/class-ssc-modules.php';
require_once SSC_DIR . 'includes/class-ssc-blocks.php';

add_action('plugins_loaded', static function () {
    SSC_Settings::init();
    SSC_Webhook::init();
    SSC_Modules::init();
    SSC_Blocks::init();
});
