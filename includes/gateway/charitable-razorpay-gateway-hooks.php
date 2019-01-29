<?php 
/**
 * Charitable razorpay Gateway Hooks. 
 *
 * Action/filter hooks used for handling payments through the razorpay gateway.
 * 
 * @package     Charitable razorpay/Hooks/Gateway
 * @version     1.0.0
 * @author      TechBrise solutions
 * @copyright   Copyright (c) 2018, techbrise
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License  
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly


/**
 * Render the razorpay donation processing page content. 
 *
 * This is the page that users are redirected to after filling out the donation form. 
 * It automatically redirects them to razorpay's website.
 *
 * @see Charitable_Gateway_Razorpay::process_donation()
 */
 add_filter( 'charitable_process_donation_razorpay', array( 'Charitable_Gateway_Razorpay', 'process_donation' ), 10, 3 );


/**
 * Change the default gateway to razorpay
 *
 * @see Charitable_Gateway_Razorpay::change_gateway_to_razorpay()
 */

add_filter( 'charitable_validate_donation_form_submission_gateway', array( 'Charitable_Gateway_Razorpay', 'validate_donation' ), 10, 3 );
