<?php
/**
 * Plugin Name: Bulk SMS – SMSNet24 Unified
 * Plugin URI: https://digitallabbd.com
 * Description: Unified SMS via SMSNET24 for WooCommerce, Dokan, LMS, MemberPress, Bookly, and operational alerts. Role-based and attendance notices included. BD numbers only (8801XXXXXXXXX). Async sending with WP-Cron.
 * Version: 2.0.0
 * Author: DigitalLab
 * Author URI: https://digitallabbd.com
 * Text Domain: bulk-sms-smsnet24-by-dl-unified
 * Domain Path: /languages
 * Requires at least: 6.4
 * Tested up to: 6.6
 * Requires PHP: 8.1
 * License: GPLv2 or later
 */

defined('ABSPATH') || exit;

define('SMSNET24U_FILE', __FILE__);
define('SMSNET24U_DIR', __DIR__);
define('SMSNET24U_VER', '2.0.0');

require_once __DIR__ . '/includes/Autoloader.php';
BulkSMS\SMSNET24\Unified\Autoloader::register('BulkSMS\\SMSNET24\\Unified', __DIR__ . '/includes');

add_action('plugins_loaded', static function () {
    load_plugin_textdomain('bulk-sms-smsnet24-by-dl-unified', false, dirname(plugin_basename(SMSNET24U_FILE)) . '/languages');
    (new BulkSMS\SMSNET24\Unified\Plugin())->init();
});
