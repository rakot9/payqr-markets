<?php
/**
 * Обертка для cURL
 */
 
class PayqrCurl extends PayqrRequest
{
    /**
     * The file to read and write cookies to for requests
     *
     * @var string
     **/
    public $cookie_file;

    /**
     * Determines whether or not requests should follow redirects
     *
     * @var boolean
     **/
    public $follow_redirects = true;

    /**
     * An associative array of headers to send along with requests
     *
     * @var array
     **/
    public $headers = array();

    /**
     * An associative array of CURLOPT options to send along with requests
     *
     * @var array
     **/
    public $options = array();

    /**
     * The referer header to send along with requests
     *
     * @var string
     **/
    public $referer;

    /**
     * The user agent to send along with requests
     *
     * @var string
     **/
    public $user_agent;

    /**
     * Stores an error string for the last request if one occurred
     *
     * @var string
     * @access protected
     **/
    protected $error = '';

    /**
     * Stores resource handle for the current CURL request
     *
     * @var resource
     * @access protected
     **/
    protected $request;

    /**
     * Initializes a Curl object
     *
     * Sets the $cookie_file to "curl_cookie.txt" in the current directory
     * Also sets the $user_agent to $_SERVER['HTTP_USER_AGENT'] if it exists, 'Curl/PHP '.PHP_VERSION otherwise
     **/
    function __construct()
    {
        $this->cookie_file = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'curl_cookie.txt';
        $this->user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'Curl/PHP ' . PHP_VERSION ;
    }

    /**
     * Makes an HTTP DELETE request to the specified $url with an optional array or string of $vars
     *
     * Returns a PayqrCurl object if the request was successful, false otherwise
     *
     * @param string $url
     * @param array|string $vars
     * @return PayqrCurl object
     **/
    function delete($url, $vars = array())
    {
        return $this->request('DELETE', $url, $vars);
    }

    /**
     * Returns the error string of the current request if one occurred
     *
     * @return string
     **/
    function error()
    {
        return $this->error;
    }

    /**
     * Makes an HTTP GET request to the specified $url with an optional array or string of $vars
     *
     * Returns a PayqrCurl object if the request was successful, false otherwise
     *
     * @param string $url
     * @param array|string $vars
     * @return PayqrCurl
     **/
    function get($url, $vars = array())
    {
        if (!empty($vars)) {
          $url .= (stripos($url, '?') !== false) ? '&' : '?';
          $url .= (is_string($vars)) ? $vars : http_build_query($vars, '', '&');
        }
        return $this->request('GET', $url);
    }

    /**
     * Makes an HTTP HEAD request to the specified $url with an optional array or string of $vars
     *
     * Returns a PayqrCurl object if the request was successful, false otherwise
     *
     * @param string $url
     * @param array|string $vars
     * @return PayqrCurl
     **/
    function head($url, $vars = array())
    {
        return $this->request('HEAD', $url, $vars);
    }

    /**
     * Makes an HTTP POST request to the specified $url with an optional array or string of $vars
     *
     * @param string $url
     * @param array|string $vars
     * @return PayqrCurl|boolean
     **/
    function post($url, $vars = array(), $data = "")
    {
      return $this->request('POST', $url, $vars, $data);
    }

    /**
     * Makes an HTTP PUT request to the specified $url with an optional array or string of $vars
     *
     * Returns a PayqrCurl object if the request was successful, false otherwise
     *
     * @param string $url
     * @param array|string $vars
     * @return PayqrCurl|boolean
     **/
    function put($url, $vars = array())
    {
        return $this->request('PUT', $url, $vars);
    }

    /**
     * Makes an HTTP request of the specified $method to a $url with an optional array or string of $vars
     *
     * Returns a PayqrCurl object if the request was successful, false otherwise
     *
     * @param string $method
     * @param string $url
     * @param array|string $vars
     * @return PayqrCurl|boolean
     **/
    function request($method, $url, $vars = array(), $data = "")
    {
        $this->error = '';
        $this->request = curl_init();
//        if (is_array($vars)) {
//            $vars = http_build_query($vars, '', '&');
//        }

        $this->set_request_method($method);
        $this->set_request_options($url, $vars, $data);
        $this->set_request_headers($vars);

        $rawResponse = curl_exec($this->request);
        $response = $this->check_response($rawResponse, $method, $url, $vars);

        curl_close($this->request);
        return $response;
    }

    /**
     * Formats and adds custom headers to the current request
     *
     * @return void
     * @access protected
     **/
    protected function set_request_headers($vars)
    {      
        $this->headers['Content-Length'] = strlen($vars);
        $headers = array();
//        foreach ($this->headers as $key => $value) {
//            $headers[] = $key . ': ' . $value;
//        }
        //curl_setopt($this->request, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($this->request, CURLOPT_HTTPHEADER, $vars);
    }

    /**
     * Set the associated CURL options for a request method
     *
     * @param string $method
     * @return void
     * @access protected
     **/
    protected function set_request_method($method)
    {
        switch (strtoupper($method)) {
            case 'HEAD':
                curl_setopt($this->request, CURLOPT_NOBODY, true);
                break;
            case 'GET':
                curl_setopt($this->request, CURLOPT_HTTPGET, true);
                break;
            case 'POST':
                curl_setopt($this->request, CURLOPT_POST, true);
                break;
            default:
                curl_setopt($this->request, CURLOPT_CUSTOMREQUEST, $method);
        }
    }

    /**
     * Sets the CURLOPT options for the current request
     *
     * @param string $url
     * @param string $vars
     * @return void
     * @access protected
     **/
    protected function set_request_options($url, $vars, $data="")
    {
        curl_setopt($this->request, CURLOPT_URL, $url);
        if (!empty($data)) {
            curl_setopt($this->request, CURLOPT_POSTFIELDS, $data);
        }

        # Set some default CURL options
        curl_setopt($this->request, CURLOPT_HEADER, true);
        curl_setopt($this->request, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->request, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($this->request, CURLOPT_CONNECTTIMEOUT, intval(PayqrConfig::$maxTimeOut));
        curl_setopt($this->request, CURLOPT_USERAGENT, $this->user_agent);
        if ($this->follow_redirects) {
            curl_setopt($this->request, CURLOPT_FOLLOWLOCATION, true);
        }
        if ($this->referer) {
            curl_setopt($this->request, CURLOPT_REFERER, $this->referer);
        }

        # Set any custom CURL options
        foreach ($this->options as $option => $value) {
            curl_setopt($this->request, constant('CURLOPT_' . str_replace('CURLOPT_', '', strtoupper($option))), $value);
        }
    }
    
    public function sendPOSTXMLFile($url, $xml_data)
    {
        $this->request = curl_init();
        curl_setopt($this->request, CURLOPT_URL, $url);
        curl_setopt($this->request, CURLOPT_MUTE, 1);
        curl_setopt($this->request, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($this->request, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($this->request, CURLOPT_POST, 1);
        curl_setopt($this->request, CURLOPT_HTTPHEADER, array('Content-Type: application/xml'));
        curl_setopt($this->request, CURLOPT_POSTFIELDS, "$xml_data");
        curl_setopt($this->request, CURLOPT_RETURNTRANSFER, 1);
        
        $rawResponse = curl_exec($this->request);
        $response = $this->check_response($rawResponse, 'POST', $url, array());

        curl_close($this->request);
        return $response;
    }
}