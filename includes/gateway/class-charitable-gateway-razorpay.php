<?php
/**
 * razorpay Gateway class
 *
 * @version     1.0.0
 * @package     Charitable/Classes/Charitable_Gateway_Razorpay
 * @author      Techbrise Solutions
 * @copyright   Copyright (c) 2018, techbrise
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */
if (! defined('ABSPATH')) {
    exit();
} // Exit if accessed directly

if (! class_exists('Charitable_Gateway_Razorpay')) :

    /**
     * razorpay Gateway
     *
     * @since 1.0.0
     */
    class Charitable_Gateway_Razorpay extends Charitable_Gateway
    {

        /**
         *
         * @var string
         */
        const ID = 'razorpay';

        /**
         * Instantiate the gateway class, defining its key values.
         *
         * @access public
         * @since 1.0.0
         */
        public function __construct()
        {
            $this->name = apply_filters('charitable_gateway_razorpay_name', __('razorpay', 'charitable-razorpay'));
            
            $this->defaults = array(
                'label' => __('Cards, Netbanking, UPI, Wallets (Processed by razorpay)', 'charitable-razorpay')
            );
            
            $this->supports = array(
                '1.3.0'
            );
            
            /**
             * Needed for backwards compatibility with Charitable < 1.3
             */
            $this->credit_card_form = false;

            add_action( 'wp_loaded', array( $this, 'rzpay_check_form' ), 1 );
        }

        /**
         * Returns the current gateway's ID.
         *
         * @return string
         * @access public
         * @static
         * @since 1.0.3
         */
        public static function get_gateway_id()
        {
            return self::ID;
        }

        /**
         * Register gateway settings.
         *
         * @param array $settings
         * @return array
         * @access public
         * @since 1.0.0
         */
        public function gateway_settings($settings)
        {
            $signup_url = "https://dashboard.razorpay.com/#/access/signup";
            
            // if ('INR' != charitable_get_option('currency', 'AUD')) {
            //     $settings['currency_notice'] = array(
            //         'type' => 'notice',
            //         'content' => '',
            //         'priority' => 1,
            //         'notice_type' => 'error'
            //     );
            // }
            
            if ($this->get_value('key_id') == null || $this->get_value('key_secret') == null) {
                $settings['setup_help'] = array(
                    'type' => 'content',
                    'content' => '<div class="charitable-settings-notice">' . '<p>' . __(
                        'Razorpay is the only payments solution in India which allows businesses to accept, process and disburse payments with its product suite. It gives you access to all payment modes including credit card, debit card, netbanking, UPI and popular wallets including JioMoney, Mobikwik, Airtel Money, FreeCharge, Ola Money and PayZapp.Manage Your marketplace, automate NEFT/RTGS/IMPS bank transfers, collect recurring payments, share invoices with customers - all from a single platform. Fast forward your business with Razorpay. For pricing do visit <a href="https://razorpay.com/pricing/" target="_blank">Here</a>', 'charitable') . '</p>' . '<p>' . __('<strong>Steps to Integrate Razorpay</strong>') . '</p>' .
                    
                    '<ol>' . '<li>Do Sign up, process will hardly
                    take 10-15 minutes.<br />
                    <br /> <a target="_new" href="' . $signup_url . 'help-signup"
                     role="button"><strong>Sign Up on Razorpay</strong></a>
                    </li>
                    <br />
                    <li>Go to the setting in dashboard and create API Key. </li>
                    
                    <li>Copy "Key Id" & "key secrate" and paste it in the
                    Charitable Razorpay extension settings</li>
                    
                    <li>Save the settings and its done.</li></ol>' . '<br />For more details about Razorpay services 
                    and details about transactions you need to access Razorpay dashboard. <br /> <a
                    target="_new" href="https://dashboard.razorpay.com/">Razorpay Dashboard</a>
                    <br><br>
                    <p style="text-align:center;">
                    Made with love WordPress by <a target="_new" href="https//techbrise.com">TechBrise Solutions </a></p>
                    </div>',
                    'priority' => 3
                );
            }
            
            $settings['key_id'] = array(
                'type' => 'text',
                'title' => __('Key ID', 'charitable-razorpay'),
                'priority' => 6
            );
            
            $settings['key_secret'] = array(
                'type' => 'text',
                'title' => __('Key Secret', 'charitable-razorpay'),
                'priority' => 8
            );
             

            $settings['merchant_name'] = array(
                'type' => 'text',
                'title' => __('Merchant Name', 'charitable-razorpay'),
                'priority' => 12
            );

            $settings['copyright'] = array(
                'type' => 'content',
                'content' => '<p>If you need any assistence/support please conatct to <a href="mailto:support@techbrise.com" title="Support forums">support</a>.</p>
                    <p class="copyright">Copyright ©2018 <a href="https://techbrise.com">TechBrise Solutions</a>.</p>',
                'priority' => 20
            );
            
            return $settings;
        }

        /**
         * Return the keys to use.
         *
         * This will return the test keys if test mode is enabled. Otherwise, returns
         * the production keys.
         *
         * @return string[]
         * @access public
         * @since 1.0.0
         */
        public function get_keys(){
            $keys = array();
            
            $keys['key_id'] = trim($this->get_value('key_id'));
            $keys['key_secret'] = trim($this->get_value('key_secret'));
                
            
            return $keys;
        }


      /**
      * Validate the submitted credit card details.
      *
      * @since  1.0.0
      *
      * @param  boolean $valid   Boolean value to be returned indicating whether the donation is valid.
      * @param  string  $gateway The donation gateway.
      * @param  mixed[] $values  Set of donation values.
      * @return boolean
     */
      public static function validate_donation( $valid, $gateway, $values ) {

        if ( 'razorpay' != $gateway ) {
          return $valid;
        }

      
        $settings = get_option('charitable_settings');

        $key_id = $settings['gateways_razorpay']['key_id'];
        $key_id = trim($key_id);

        $key_secret = $settings['gateways_razorpay']['key_secret'];
        $key_secret = trim($key_secret);

        /* Make sure that the key id and key secret is set. */
        if ( $key_id == '' ) {
          charitable_get_notices()->add_error( __( 'Missing RazorPay Key Id. Unable to proceed with payment.', 'charitable' ) );
          return false;
        }

        if ( $key_secret == '' ) {
          charitable_get_notices()->add_error( __( 'Missing RazorPay Key secret. Unable to proceed with payment.', 'charitable' ) );
          return false;
        }

        return $valid;
      }


      /**
      * Process the donation with RazorPay.
      *
      * @since  1.0.0
      *
      * @param  boolean|array                 $return      The value to be returned.
      * @param  int                           $donation_id The donation ID.
      * @param  Charitable_Donation_Processor $processor   The Donation Processor object.
      * @return array
      */

      public static function process_donation($return, $donation_id, $processor) {
        
        $gateway          = new Charitable_Gateway_Razorpay();
        $user_data        = $processor->get_donation_data_value( 'user' );

        $donation         = charitable_get_donation( $donation_id );
        $transaction_mode = $gateway->get_value( 'transaction_mode' );
        $donation_key     = $processor->get_donation_data_value( 'donation_key' );

        $donation_amount = $donation->get_total_donation_amount( true );

        $donation_amount = $donation_amount * 100;

        
        $redirect_url = charitable_get_permalink( 'campaign_donation_page', array( 'campaign_id' => $processor->get_campaign()->ID ) );

        $redirect_url = $redirect_url.'?payment=razorpay';

        if( $donation_key ) {
          unset($_SESSION['donation_id']);
          unset($_SESSION['donation_amount']);
          

          if(session_id() == '') {
            session_start();
          }
          $_SESSION['donation_id'] = $donation_id;
          $_SESSION['donation_amount'] = $donation_amount;
        }

        /* Redirect to Page */
        return array(
        'redirect' => $redirect_url,
        'safe'     => false,
        );   

        }
    }

endif; // End class_exists check
