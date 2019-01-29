<?php
/**
 * razorpay
 * used to manage razorpay API calls
 * 
 */

// Include Requests only if not already defined
if (class_exists('Requests') === false)
{
    require_once __DIR__.'/libs/Requests-1.6.1/library/Requests.php';
}

try
{
    Requests::register_autoloader();  

    if (version_compare(Requests::VERSION, '1.6.0') === -1)
    {
        throw new Exception('Requests class found but did not match');
    }
}
catch (\Exception $e)
{
    throw new Exception('Requests class found but did not match');
}

spl_autoload_register(function ($class)
{
    // project-specific namespace prefix
    $prefix = 'Razorpay\Api';

    // base directory for the namespace prefix
    $base_dir = __DIR__ . '/src/';

    // does the class use the namespace prefix?
    $len = strlen($prefix);

    if (strncmp($prefix, $class, $len) !== 0)
    {
        // no, move to the next registered autoloader
        return;
    }

    // get the relative class name
    $relative_class = substr($class, $len);

    //
    // replace the namespace prefix with the base directory,
    // replace namespace separators with directory separators
    // in the relative class name, append with .php
    //
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    // if the file exists, require it
    if (file_exists($file))
    {
        require $file;
    }
});

//Class for the the rozar pay key authantications.

include __DIR__ . DIRECTORY_SEPARATOR . "ValidationException.php";

class razorpay
{

    private $api_endpoint;

    private $auth_endpoint;

    private $auth_headers;

    private $access_token;

    private $key_id;

    private $key_secret;

    function __construct( $key_id, $key_secret )
    {
        $this->key_id = $key_id;
        $this->key_secret = $key_secret;
        
            $this->api_endpoint = "https://www.razorpay.com/v2/";

            $this->auth_endpoint = "https://www.razorpay.com/oauth2/token/";
        
        $this->getAccessToken();
    }

    private function getResponseBody($response)
    {
        if (is_array($response) && isset($response['body'])) {
            return $response['body'];
        }
        throw new Exception("Something went wrong.");
    }

    public function getAccessToken()
    {
        $data = array();
        $data['key_id'] = $this->key_id;
        $data['key_secret'] = $this->key_secret;
        $data['scopes'] = "all";
        $data['grant_type'] = "client_credentials";
        
        $response = wp_remote_post($this->auth_endpoint, array(
            'body' => $data
        ));
        $result = wp_remote_retrieve_body($response);
        if ($result) {
            $result = json_decode($result);
            if (isset($result->error)) {
                throw new ValidationException("The Authorization request failed with message '$result->error'",
                    array(
                        "Payment Gateway Authorization Failed."
                    ), $result);
            } else
                $this->access_token = $result->access_token;
        }
        if ($result->access_token == "") {
            throw new Exception("Something went wrong. Please try again later.");
        }
        $this->auth_headers = "Authorization:Bearer $this->access_token";
    }

    public function createOrderPayment($data)
    {
        $endpoint = $this->api_endpoint . "gateway/orders/";
        $response = wp_remote_post($endpoint,
            array(
                'body' => $data,
                'headers' => $this->auth_headers
            ));
        $result = wp_remote_retrieve_body($response);
        $result = json_decode($result);
        if (isset($result->order)) {
            return $result;
        } else {
            $errors = array();
            if (isset($result->message))
                throw new ValidationException("Validation Error with message: $result->message",
                    array(
                        $result->message
                    ), $result);
            
            foreach ($result as $k => $v) {
                if (is_array($v))
                    $errors[] = $v[0];
            }
            if ($errors)
                throw new ValidationException("Validation Error Occured with following Errors : ", $errors, $result);
        }
    }

    public function createPaymentRequest($data)
    {
        $endpoint = $this->api_endpoint . "payment_requests/";
        $response = wp_remote_post($endpoint,
            array(
                'body' => $data,
                'headers' => $this->auth_headers
            ));
        $result = wp_remote_retrieve_body($response);
        
        $result = json_decode($result);
        if (isset($result->id)) {
            return $result;
        } else if (isset($result)) {
            $errors = array();
            if (isset($result->message))
                throw new ValidationException("Validation Error with message: $result->message",
                    array(
                        $result->message
                    ), $result);
            
            foreach ($result as $k => $v) {
                if (is_array($v) && isset($v[0]))
                    $errors[] = $v[0];
            }
            if ($errors)
                throw new ValidationException("Validation Error Occured with following Errors : ", $errors, $result);
        }
    }

    public function getOrderById($id)
    {
        $endpoint = $this->api_endpoint . "gateway/orders/id:$id/";
        
        $response = wp_remote_get($endpoint, array(
            'headers' => $this->auth_headers
        ));
        $result = wp_remote_retrieve_body($response);
        
        $result = json_decode($result);
        if (isset($result->id) and $result->id)
            return $result;
        else
            throw new Exception("Unable to Fetch Payment Request id:'$id' Server Responds " . print_R($result, true));
    }

    public function getPaymentRequestById($id)
    {
        $endpoint = $this->api_endpoint . "payment_requests/$id/";
        $response = wp_remote_get($endpoint, array(
            'headers' => $this->auth_headers
        ));
        $result = wp_remote_retrieve_body($response);
        
        $result = json_decode($result);
        if (isset($result->id) and $result->id)
            return $result;
        else
            throw new Exception("Unable to Fetch Payment Request id:'$id' Server Responds " . print_R($result, true));
    }

    public function getPaymentById($id)
    {
        $endpoint = $this->api_endpoint . "payments/$id/";
        
        $response = wp_remote_get($endpoint, array(
            'headers' => $this->auth_headers
        ));
        $result = wp_remote_retrieve_body($response);
        
        $result = json_decode($result);
        if (isset($result->id) and $result->id)
            return $result;
        else
            throw new Exception("Unable to Fetch Payment id:'$id' Server Responds " . print_R($result, true));
    }

    public function getPaymentStatus($payment_id, $payments)
    {
        foreach ($payments as $payment) {
            if ($payment->id == $payment_id) {
                return $payment->status;
            }
        }
    }
}