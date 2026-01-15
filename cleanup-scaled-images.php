<?php /*

************************************************************************
 
Plugin Name:  Cleanup Scaled Images
Description:  Delete original file for scaled images to make the project lighter.
Version:      1.0.0
Author:       Andrius Sokoklnikovas
Author URI:   https://andriussok.lt
License:      GPLv2
License URI:  https://www.gnu.org/licenses/gpl-2.0.html

*************************************************************************/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
if ( ! is_admin() ) return; // Exit if not admin dash


/**
 * Init Plugin.
 */


// Add admin menu
add_action( 'admin_menu', 'csi_add_admin_page');
function csi_add_admin_page() {
  
  add_submenu_page(
    'tools.php',              // $parent
    'Cleanup Scaled Images',  // $page_title
    'Cleanup Imges',          // $menu_title
    'manage_options',         // $capability
    'cleanup-scaled-images',  // $menu_slug
    'admin_page_html'         // $callback to print html
  );

}


// Include dependencies
require_once plugin_dir_path(__FILE__) . 'admin/admin-page.php';
require_once plugin_dir_path(__FILE__) . 'admin/admin-controller.php';
require_once plugin_dir_path(__FILE__) . 'admin/admin-settings-register.php';
require_once plugin_dir_path(__FILE__) . 'admin/admin-settings-callbacks.php';



// Default plugin options used for settings
function csi_options_default() {

  return array(
    'auto_delete'  => false,
    'limit_size'   => false,
    'file_size'    => '5',
  );

}


/**
 * Clean leftovers during plugin uninstall
 */

function csi_activation_callback() {
  register_uninstall_hook( __FILE__, 'csi_uninstall_callback' );
}

function csi_uninstall_callback() {
  delete_option('csi_options'); // Perform uninstall operations
}

register_activation_hook( __FILE__, 'csi_activation_callback' );
