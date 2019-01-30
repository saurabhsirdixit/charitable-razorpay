<?php
/**
 * Plugin Name: 		Charitable - Razorpay Payment Gateway
 * Plugin URI: 			https://techbrise.com/
 * Description: 		Collect donations in INR via Debit Cards, Credit Cards, Net Banking, UPI, Wallets, EMI by integrating razorpay Indian Payment Gateway.
 * Version: 			1.0.0
 * Author: 				TechBrise Solutions
 * Author URI: 			https://www.techbrise.com
 * Requires at least: 	4.9
 * Tested up to: 		5.0
 *
 * Text Domain: 		integrate-charitable-razorpay
 * Domain Path: 		/languages/
 *
 * @package 			Integrate Charitable Razorpay
 * @category 			Core
 * @author 				Techbrise Solutions 
 */

if ( ! defined( 'ABSPATH' ) )
{
    exit; // Exit if accessed directly
}

use Razorpay\Api\Api;
/**
 * Load plugin class, but only if Charitable is found and activated.
 *
 * @return 	void
 * @since 	1.0.0
 */
function charitable_razorpay_load() {
	
	require_once( 'includes/class-charitable-razorpay.php' );
	
	$has_dependencies = true;

	/* Check for Charitable */
	if ( ! class_exists( 'Charitable' ) ) {

		if ( ! class_exists( 'Charitable_Extension_Activation' ) ) {

			require_once 'includes/class-charitable-extension-activation.php';

		}

		$activation = new Charitable_Extension_Activation( plugin_dir_path( __FILE__ ), basename( __FILE__ ) );
		$activation = $activation->run();

		$has_dependencies = false;
	} 
	else {

		new Charitable_Razorpay( __FILE__ );

	}	
}

add_action( 'plugins_loaded', 'charitable_razorpay_load', 1 );