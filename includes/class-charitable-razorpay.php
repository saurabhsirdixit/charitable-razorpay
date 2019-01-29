<?php
/**
 * The main Charitable razorpay class.
 * 
 * The responsibility of this class is to load all the plugin's functionality.
 *
 * @package     Charitable - razorpay
 * @copyright   Copyright (c) 2018, TechBrise
 * @license     http://opensource.org/licenses/gpl-1.0.0.php GNU Public License
 * @since       1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'Charitable_Razorpay' ) ) :

/**
 * Charitable_Razorpay
 *
 * @since   1.0.0
 */
class Charitable_Razorpay {

    /**
     * @var string
     */
    const VERSION = '1.0.0';

    /**
     * @var string  A date in the format: YYYYMMDD
     */
    const DB_VERSION = '20180712';  

    /**
     * @var string The product name. 
     */
    const NAME = 'Charitable razorpay'; 

    /**
     * @var string The product author.
     */
    const AUTHOR = 'TechBrise Solutions';

    /**
     * @var Charitable_Razorpay
     */
    private static $instance = null;

    /**
     * The root file of the plugin. 
     * 
     * @var     string
     * @access  private
     */
    private $plugin_file; 

    /**
     * The root directory of the plugin.  
     *
     * @var     string
     * @access  private
     */
    private $directory_path;

    /**
     * The root directory of the plugin as a URL.  
     *
     * @var     string
     * @access  private
     */
    private $directory_url;

    /**
     * @var     array       Store of registered objects.  
     * @access  private
     */
    private $registry;

    /**
     * Create class instance. 
     * 
     * @return  void
     * @since   1.0.0
     */
    public function __construct( $plugin_file ) {
        $this->plugin_file      = $plugin_file;
        $this->directory_path   = plugin_dir_path( $plugin_file );
        $this->directory_url    = plugin_dir_url( $plugin_file );

        add_action( 'charitable_start', array( $this, 'start' ), 1 );

        add_action( 'wp_loaded', array( $this, 'charitable_razorpay_get_form' ), 1 );

        add_action( 'init', array($this, 'razorpay_get_redirect_response'));

    }

    /**
     * Returns the original instance of this class. 
     * 
     * @return  Charitable
     * @since   1.0.0
     */
    public static function get_instance() {
        return self::$instance;
    }

    /**
     * Run the startup sequence on the charitable_start hook. 
     *
     * This is only ever executed once.  
     * 
     * @return  void
     * @access  public
     * @since   1.0.0
     */
    public function start() {
        // If we've already started (i.e. run this function once before), do not pass go. 
        if ( $this->started() ) {
            return;
        }

        // Set static instance
        self::$instance = $this;

        $this->load_dependencies();

        $this->maybe_upgrade();

        $this->attach_hooks_and_filters();

        // Hook in here to do something when the plugin is first loaded.
        do_action('charitable_razorpay_start', $this);
    }

    /**
     * Include necessary files.
     * 
     * @return  void
     * @access  private
     * @since   1.0.0
     */
    private function load_dependencies() {
        require_once( $this->get_path( 'includes' ) . 'gateway/class-charitable-gateway-razorpay.php' );
        require_once( $this->get_path( 'includes' ) . 'gateway/charitable-razorpay-gateway-hooks.php' );
        require_once( $this->get_path( 'includes' ) . 'lib/razorpay-php/Razorpay.php' );
    }

    /**
     * Set up callbacks. 
     *
     * @return  void
     * @access  private
     * @since   1.0.0
     */
    private function attach_hooks_and_filters() {
        if ( class_exists( 'Charitable_razorpay_i18n' ) ) {
            add_action( 'charitable_start', array( 'Charitable_razorpay_i18n', 'charitable_start' ) );    
        }
        
        add_filter( 'plugin_action_links_' . plugin_basename( $this->get_path() ), array( $this, 'add_plugin_action_links' ) );
        add_filter( 'charitable_payment_gateways', array( $this, 'register_gateway' ) );     
    }

    /**
     * Add a direct link to the Payment Gateways tab, or straight through to the razorpay settings if it's enabled. 
     *
     * @param   string[] $links
     * @return  string[]
     * @access  public
     * @since   1.0.0
     */
    public function add_plugin_action_links( $links ) {
        $link = add_query_arg( array( 
            'page'  => 'charitable-settings',
            'tab'   => 'gateways'
        ), admin_url( 'admin.php' ) );

        $link_text = __( 'Settings', 'charitable-razorpay' );

        if ( charitable_get_helper( 'gateways' )->is_active_gateway( 'razorpay' ) ) {
            
            $link = add_query_arg( array( 
                'group' => 'gateways_razorpay'
            ), $link );

        }

        $links[] = "<a href=\"$link\">$link_text</a>";

        return $links;
    }

    /**
     * Perform upgrade routine if necessary. 
     *
     * @return  void
     * @access  private
     * @since   1.0.0
     */
    private function maybe_upgrade() {
        $db_version = get_option( 'charitable_razorpay_version' );
        
        if ( $db_version !== self::VERSION ) {

            require_once( charitable()->get_path( 'includes' ) . 'class-charitable-upgrade.php' );
            require_once( $this->get_path( 'includes' ) . 'class-charitable-razorpay-upgrade.php' );

            Charitable_razorpay_Upgrade::upgrade_from( $db_version, self::VERSION );
        }
    }

    /**
     * Register the razorpay payment gateway class. 
     *
     * @param   string[]
     * @return  string[]
     * @access  public
     * @since   1.0.0
     */
    public function register_gateway( $gateways ) {
        $gateways[ 'razorpay' ] = 'Charitable_Gateway_Razorpay';
        return $gateways;
    }

    /**
     * Returns whether we are currently in the start phase of the plugin. 
     *
     * @return  bool
     * @access  public
     * @since   1.0.0
     */
    public function is_start() {
        return current_filter() == 'charitable_razorpay_start';
    }

    /**
     * Returns whether the plugin has already started.
     * 
     * @return  bool
     * @access  public
     * @since   1.0.0
     */
    public function started() {
        return did_action( 'charitable_razorpay_start' ) || current_filter() == 'charitable_razorpay_start';
    }

    /**
     * Returns the plugin's version number. 
     *
     * @return  string
     * @access  public
     * @since   1.0.0
     */
    public function get_version() {
        return self::VERSION;
    }

    /**
     * Returns plugin paths. 
     *
     * @param   string $path            // If empty, returns the path to the plugin.
     * @param   bool $absolute_path     // If true, returns the file system path. If false, returns it as a URL.
     * @return  string
     * @since   1.0.0
     */
    public function get_path($type = '', $absolute_path = true ) {      
        $base = $absolute_path ? $this->directory_path : $this->directory_url;

        switch( $type ) {
            case 'includes' : 
                $path = $base . 'includes/';
                break;

            case 'templates' : 
                $path = $base . 'templates/';
                break;

            case 'directory' : 
                $path = $base;
                break;

            default :
                $path = $this->plugin_file;
        }

        return $path;
    }

    /**
     * Stores an object in the plugin's registry.
     *
     * @param   mixed       $object
     * @return  void
     * @access  public
     * @since   1.0.0
     */
    public function register_object( $object ) {
        if ( ! is_object( $object ) ) {
            return;
        }

        $class = get_class( $object );

        $this->registry[ $class ] = $object;
    }

    /**
     * Returns a registered object.
     * 
     * @param   string      $class  The type of class you want to retrieve.
     * @return  mixed               The object if its registered. Otherwise false.
     * @access  public
     * @since   1.0.0
     */
    public function get_object( $class ) {
        return isset( $this->registry[ $class ] ) ? $this->registry[ $class ] : false;
    }

    /**
     * Throw error on object clone. 
     *
     * This class is specifically designed to be instantiated once. You can retrieve the instance using charitable()
     *
     * @since   1.0.0
     * @access  public
     * @return  void
     */
    public function __clone() {
        _doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'charitable-razorpay' ), '1.0.0' );
    }

    /**
     * Disable unserializing of the class. 
     *
     * @since   1.0.0
     * @access  public
     * @return  void
     */
    public function __wakeup() {
        _doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'charitable-razorpay' ), '1.0.0' );
    }

    /**
     * Makes the donation process and shows razorpay form popup
     *
     * @since   1.0.0
     * @access  public
     * @return  html
     */
    public function charitable_razorpay_get_form() {
      if( isset($_GET['payment']) && $_GET['payment'] == 'razorpay' ) {
        
        session_start();

        $donation_id = isset($_SESSION['donation_id']) ? $_SESSION['donation_id'] : '' ;

        if( $donation_id ) :

          $gateway = new Charitable_Gateway_Razorpay();
          $donation = charitable_get_donation($donation_id);
        
          $campaign_donations = $donation->get_campaign_donations();

          foreach ($campaign_donations as $key => $value) {
            if (!empty($value->campaign_id)) {
              $post_id = $value->campaign_id;
              $campaign_name = $value->campaign_name;
              $post = get_post((int) $post_id);
              $campaign = new Charitable_Campaign($post);
              break;
            }
          }

          $donor = $donation->get_donor();
          $first_name = $donor->get_donor_meta('first_name');
          $last_name = $donor->get_donor_meta('last_name');
          $name = $first_name . ' ' . $last_name;
          $email = $donor->get_donor_meta('email');
          $mobile = $donor->get_donor_meta('phone');
          $amount = $donation->get_total_donation_amount(true);

          $keys = $gateway->get_keys();

          /**
          * If the admin has set Custom Description, use it
          */
          if (!empty($keys['description'])) {
            $raw_description = $keys['description'];
          } else {
            $raw_description = $campaign_name;
          }

          $donation_data = $this->get_donation_data($donation_id, $post_id);

          $donation         = charitable_get_donation( $donation_id );

          $donation_amount = $donation->get_total_donation_amount( true );

          $donation_amount = $donation_amount * 100;

          $donor = $donation->get_donor();
          $first_name = $donor->get_donor_meta('first_name');
          $last_name = $donor->get_donor_meta('last_name');
          $name = $first_name . ' ' . $last_name;
          $email = $donor->get_donor_meta('email');
          $mobile = $donor->get_donor_meta('phone');


          if( $donation_id !== '' ) :
            $html = '
            <!doctype html>
            <html>
              <head>
                <title>Razorpay</title>
                <meta name="viewport" content="user-scalable=no,width=device-width,initial-scale=1,maximum-scale=1">
                <meta http-equiv="pragma" content="no-cache">
                <meta http-equiv="cache-control" content="no-cache">
                <meta http-equiv="expires" content="0">
                <style>
                  img{max-width: 100%; height: auto;}
                  body{font-family: ubuntu,helvetica,verdana,sans-serif; font-size: 14px; text-align: center; color: #414141; padding-top: 40px; line-height: 24px;background:#fff;}
                    label{top: 0; left: 0; right: 0; height: 100%; line-height: 32px; padding-left: 30px;}
                  input[type=button]{ font-family: inherit; padding: 12px 20px; text-decoration: none; border-radius: 2px; border: 0; width: 124px; background: none; margin: 0 5px; color: #fff; cursor: pointer; -webkit-appearance: none;
                  }
                  input[type=button]:hover{background-image: linear-gradient(transparent,rgba(0,0,0,.05) 40%,rgba(0,0,0,.1))}
                    .grey{color: #777; margin-top: 20px; font-size: 12px; line-height: 18px;}
                    .danger{background-color: #EF6050!important}
                    .success{background-color: #61BC6D!important}
                </style>
                <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
                <script>
                  var options = {
                    "key"       : "'.$donation_data['razorpay_key'].'",
                    "currency"  : "INR",
                    "amount"    : '.$donation_amount.',
                    "name"      : "'.$donation_data['merchant_name'].'",
                    "description": "'.$raw_description.'",
                    "handler"   : function (response) {
                      document.getElementById("razorpay_id").value = response.razorpay_payment_id;
                      document.getElementById("razorpay").submit();
                    },
                    "modal": {
                      "ondismiss": function() {
                        window.location.href = "'.$donation_data['error_return_url'].'";
                      }
                    },
                    "prefill": {
                      "name": "'.$name.'",
                      "email": "'.$email.'",
                      "contact": "'.$mobile.'"
                    },
                    "notes": {
                      "charitable_order_id": "'.$donation_id.'"
                    }
                  };
                  
                  var rzp = new Razorpay(options);
                  rzp.open();

                  function openRazorpay() {
                    rzp.open();
                  }

                  function cancel(e) {
                    window.location.href = "'.$donation_data['error_return_url'].'";
                  }
                </script>
              </head>
              <body>
                <form action="'.$donation_data['return_url'].'" method="POST" id="razorpay">
                  <input type="hidden" name="merchant_order_id" value="' . $donation_id  . '">
                  <input type="hidden" name="razorpay_payment_id" id="razorpay_id">
                  <input type="hidden" name="gateway" value="razorpay_gateway">
                </form>
              </body>
            </form>';
            echo $html;
          
          endif;
        endif;
      }
    }

    /**
     * Get charitable settings data in an array
     *
     * @since   1.0.0
     * @access  public
     * @return  array
     */
    public function get_donation_data($donation_id, $campaign_id) {
      if( $donation_id ) {
        $settings = get_option('charitable_settings');

        $donation_data = array(
          'razorpay_key'     => $settings['gateways_razorpay']['key_id'],
          'merchant_name'    => $settings['gateways_razorpay']['merchant_name'],
          'error_return_url'  => charitable_get_permalink( 'campaign_donation_page', array( 'campaign_id' => $campaign_id ) ),
          'return_url'  => charitable_get_permalink('donation_receipt_page', array('donation_id' => $donation_id)),
        );
        
        return $donation_data;
      }
    }

    /**
     * Gets the RazorPay response
     *
     * @since   1.0.0
     * @access  public
     * @return  array
     */
    public function razorpay_get_redirect_response() {

      $redirect_response = $_POST;

      if ( isset($redirect_response['gateway']) 
        && $redirect_response['gateway'] === 'razorpay_gateway' 
        && isset($redirect_response['merchant_order_id']) ) {
          $this->razorpay_check_response($redirect_response, $redirect_response['merchant_order_id']);
      }
      else {
        return false;
      }

    }


    /**
     * Makes curl to razorpay with data and order number
     *
     * @since   1.0.0
     * @access  public
     * @return  mixed
     */
    public function razorpay_check_response($response, $order_no) {

      $currency = 'INR';

      $success = false;
      $error_message = __('Payment failed. Please try again.', 'Charitable-razorpay');

      if ( !empty($response['razorpay_payment_id']) ) {
        try {
          $url =  "https://api.razorpay.com/v1/payments/{$response['razorpay_payment_id']}/capture";

          session_start();
        
          $amount = $_SESSION['donation_amount'];
        
          $fields_string="amount={$amount}&currency={$currency}";

          $settings = get_option('charitable_settings');
          $key_id = $settings['gateways_razorpay']['key_id'];
          $key_id = trim($key_id);

          $key_secret = $settings['gateways_razorpay']['key_secret'];
          $key_secret = trim($key_secret);
        
          //cURL Request
          $ch = curl_init();

          //set the url, number of POST vars, POST data
          curl_setopt($ch,CURLOPT_URL, $url);
          curl_setopt($ch,CURLOPT_USERPWD, $key_id . ":" . $key_secret);
          curl_setopt($ch,CURLOPT_TIMEOUT, 60);
          curl_setopt($ch,CURLOPT_POST, 1);
          curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);
          curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);
          curl_setopt($ch,CURLOPT_SSL_VERIFYPEER, true);
          curl_setopt($ch,CURLOPT_CAINFO, plugin_dir_path(__FILE__) . 'ca-bundle.crt');

          //execute post
          $result = curl_exec($ch);
          $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        
          if ($result === false) {
            $success = false;
            $error = 'Curl error: ' . curl_error($ch);
          }
          else {
            $response_array = json_decode($result, true);

            //Check success response
            if ( $http_status === 200 
              && isset($response_array['error_code']) == ''
            ) {
              $success = true;
            }
            else {
              $success = false;

              if (!empty($response_array['error']['code'])) {
                $error = $response_array['error']['code']." : ".$response_array['error']['description'];
              }
              else {
                $error = "RAZORPAY_ERROR: Invalid Response <br/>".$result;
              }
            }
          }
          //close connection
          curl_close($ch);
        }
      
        catch (Exception $e) {
          $success = false;
          $error = "ERROR: Request to Razorpay Failed";
        }
      }

      $data = array(
        'id' => isset($response_array['id']) ? $response_array['id'] : '',
        'amount' => isset($response_array['amount']) ? $response_array['amount'] : '',
        'status' => isset($response_array['status']) ? $response_array['status'] : '',
        'note'   => isset($response_array['description']) ? $response_array['description'] : '',
        'donation_id' => $order_no,
      );

      if ($success === true) {
        $status = 'completed';
        $this->process_web_accept( $data, $status );

      }
      else {
        $status = 'failed';
        $this->process_web_accept( $data, $status );
      }
  }

  /**
  * Makes the payment process as per the response
  *
  * @since   1.0.0
  * @access  public
  * @return  string
  */
  public function process_web_accept( $data, $status ) {
    if( isset($data['donation_id']) ) {
      $donation_id = $data['donation_id'];
      $donation = charitable_get_donation((int)  $donation_id);

      if( $status == 'completed' ) {
        $message = sprintf( '%s: %s', __( 'RazorPay Transaction ID', 'Charitable-razorpay' ), $data['id'] );
        $donation->log()->add( $message );
        $donation->update_status('charitable-completed');
      }

      if( $status == 'failed' ) {
        $payment_status = 'Payment Failed';
        $message = sprintf( '%s: %s', __( 'The donation has failed with the following status', 'Charitable-razorpay' ), $payment_status );
        $donation->log()->add( $message );
        $donation->update_status('charitable-failed');
      }
      
      unset($_SESSION['donation_id']);
      unset($_SESSION['donation_amount']);
    }
    
  }
}

endif; // End if class_exists check