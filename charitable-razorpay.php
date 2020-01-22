<?php
/**
 * Plugin Name: 		Charitable - Razorpay Payment Gateway
 * Description: 		Collect donations in INR via Debit Cards, Credit Cards, Net Banking, UPI, Wallets, EMI by integrating razorpay Indian Payment Gateway.
 * Version: 			1.1.1
 * Author: 				Saurabh Dixit
 * Requires at least: 	4.0
 * Tested up to: 		5.3.2
 *
 * Text Domain: 		integrate-charitable-razorpay
 * Domain Path: 		/languages/
 *
 * @package 			Integrate Charitable Razorpay
 * @category 			Core
 * @author 				Saurabh Dixit
 */

/**
Charitable - Razorpay Payment Gateway
Copyright (C) 2019 Saurabh Dixit

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License along
with this program; if not, write to the Free Software Foundation, Inc.,
51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
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