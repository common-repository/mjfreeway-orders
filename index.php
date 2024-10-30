<?php
/*
  Plugin Name: MJFreeway Orders
  Description: Creates an order reservation experience using MJ Freeway's API
  Version: 1.0.2
  Author: MJ Freeway
  Author URI: https://mjfreeway.com
  */

if ( ! defined( 'WPINC' ) ) {
  die;
}

require_once plugin_dir_path( __FILE__ ) .'class.mjfreeway-product.php';
require_once plugin_dir_path( __FILE__ ) .'class.mjfreeway-product-category.php';
require_once plugin_dir_path( __FILE__ ) .'class.mjfreeway-cart-item.php';
require_once plugin_dir_path( __FILE__ ) .'mjfreeway-plugin.php';

function run_mjfreeway_plugin() {
  $MJFplugin = MJFreeway_Shopping_Cart::get_instance();
  register_activation_hook( __FILE__, array( $MJFplugin, 'activate_plugin') );
  register_deactivation_hook( __FILE__, array( $MJFplugin, 'deactivate_plugin') );

}
run_mjfreeway_plugin();
