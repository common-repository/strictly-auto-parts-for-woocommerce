<?php

/*
  Plugin Name: Strictly Auto Parts for WooCommerce
  Plugin URI: https://wordpress.org/plugins/strictly-auto-parts-for-wooCommerce
  Version: 1.0.2
  Description: Automate your order fulfillment process by syncing to Strictly Auto Parts software.
  Author: Strictly Auto Parts
  Author URI: https://www.strictlyautoparts.ca/
  Text Domain: Strtictly-Auto-Parts
  Requires at least: 5.8
  Requires PHP: 7.3
  License: GPL v2 or later
  License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

defined('ABSPATH') || exit;

/* THIS VARIABLE CAN BE CHANGED AUTOMATICALLY */

define('WPSAP_PLUGIN', __FILE__);
define('WPSAP_PLUGIN_BASENAME', plugin_basename(WPSAP_PLUGIN));
define('WPSAP_PLUGIN_NAME', trim(dirname(WPSAP_PLUGIN_BASENAME), '/'));
define('WPSAP_PLUGIN_DIR', untrailingslashit(dirname(WPSAP_PLUGIN)));
define('WPSAP_VERSION', '1.0.2');
load_plugin_textdomain( 'Strtictly-Auto-Parts', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
require_once WPSAP_PLUGIN_DIR . '/load.php';
require_once WPSAP_PLUGIN_DIR . '/classes/sap_hooks.php';
