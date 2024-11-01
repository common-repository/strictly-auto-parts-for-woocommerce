<?php

if (!defined('ABSPATH')) {
    exit;
}

class WPSAP {

    public $error = null;

    public function __construct()
    {
        if ($this->wpsap_check_dependencies() == false) {
            $translatedError = esc_html__('%s', 'Strtictly-Auto-Parts');
            $translatedErrorValue = sprintf($translatedError, $this->error);
            echo sprintf('<div class="error"><p>%s</p></div>', $translatedErrorValue);
            deactivate_plugins(plugin_basename(__FILE__)); 
        }
    }

    public function wpsap_check_dependencies()
    {
        global $wp_version;
        $plugin_headers = get_plugin_data(WP_PLUGIN_DIR . '/' . WPSAP_PLUGIN_BASENAME, true, false);
        $required_wp_version = $plugin_headers["RequiresWP"];
        if (version_compare($wp_version, $required_wp_version, '<')) {
            $this->error = strval("Your installed WordPress version ($wp_version) is not compatible with this plugin. Please upgrade to at least $required_wp_version.");
            return false;
        }
        if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
            $this->error = strval('Your plugin requires WooCommerce to be installed and active.');
            return false;
        }
        return true;
    }
}

$wpsap = new WPSAP();
