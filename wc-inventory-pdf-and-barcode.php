<?php
/**
 * Plugin Name: Inventory PDF & Barcode Manager for WooCommerce
 * Description: Generate a printable PDF of physical products (incl. variations), bulk update inventory, and import counts via barcode scans (_global_unique_id).
 * Version: 1.0.2
 * Author: Michael Patrick
 * License: GPL2+
 * Text Domain: wc-inventory-pdf-barcode
 * Requires: WooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'WCIPB_VERSION', '1.0.2' );
define( 'WCIPB_PATH', plugin_dir_path( __FILE__ ) );
define( 'WCIPB_URL', plugin_dir_url( __FILE__ ) );

// Ensure WooCommerce
add_action( 'plugins_loaded', function() {
    if ( ! class_exists( 'WooCommerce' ) ) {
        add_action( 'admin_notices', function(){
            echo '<div class="notice notice-error"><p><strong>WooCommerce Inventory PDF & Barcode Manager</strong> requires WooCommerce to be active.</p></div>';
        });
        return;
    }
    require_once WCIPB_PATH . 'includes/AdminPages.php';
    require_once WCIPB_PATH . 'includes/ProductQuery.php';
    require_once WCIPB_PATH . 'includes/PDFGenerator.php';
    new WCIPB\AdminPages();
});

// Basic CSS
add_action('admin_enqueue_scripts', function($hook){
    if ( strpos($hook, 'wcipb') !== false ) {
        wp_enqueue_style('wcipb-admin', WCIPB_URL . 'assets/admin.css', [], WCIPB_VERSION);
    }
});
