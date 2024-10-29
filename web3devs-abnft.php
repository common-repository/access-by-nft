<?php

/**
 *
 * @link              https://accessbynft.com
 * @since             1.0.0
 * @package           Web3devs_ABNFT
 *
 * @wordpress-plugin
 * Plugin Name:       Access by NFT
 * Plugin URI:        https://accessbynft.com/
 * Description:       Restrict accesss to pages for users holding specified tokens (NFTs)
 * Version:           1.0.0
 * Author:            Web3devs
 * Author URI:        https://web3devs.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       web3devs-abnft
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 */
define( 'WEB3DEVS_ABNFT_VERSION', '1.0.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-web3devs-abnft-activator.php
 */
function activate_web3devs_abnft() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-web3devs-abnft-activator.php';
	Web3devs_ABNFT_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-web3devs-abnft-deactivator.php
 */
function deactivate_web3devs_abnft() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-web3devs-abnft-deactivator.php';
	Web3devs_ABNFT_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_web3devs_abnft' );
register_deactivation_hook( __FILE__, 'deactivate_web3devs_abnft' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-web3devs-abnft.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_web3devs_abnft() {

	$plugin = new Web3devs_ABNFT();
	$plugin->run();

}
run_web3devs_abnft();
