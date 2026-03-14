<?php declare(strict_types=1); namespace IR\Http; if (!defined('IR_START')) exit('<pre>No direct script access allowed</pre>');
/**
 * @framework       iResponse Framework 
 * @version         1.0
 * @author          Amine Idrissi <contact@iresponse.tech>
 * @date            2024
 * @name            Request.php	
 */

# utilities
use IR\Utils\Types\Arrays as Arrays;

# exceptions
use IR\Exceptions\Types\HTTPException as HTTPException;

/**
 * @name Request
 * @description http requests class
 */
class Request
{
    /**
     * @name getInstance
     * @description singleton access to constructor
     * @access public | static
     * @return Request
     */
    public static function getInstance() : Request
    {
        if(self::$_instance == null)
        {
            self::$_instance = new Request();
        }
        
        return self::$_instance;
    }
    
    /**
     * @name exists
     * @description check if a data exists by a key
     * @access public
     * @param string $key the key to check with
     * @return mixed
     */
    public function exists(string $key,string $method = self::GET) 
    {
        $data = null;

        switch ($method) 
        {
            case self::GET :
            {
                $data = $_GET;
                break;
            }
            case self::POST :
            {
                $data = $_POST;
                break;
            }
            case self::SERVER :
            {
                $data = $_SERVER;
                break;
            }
            case self::ENV :
            {
                $data = $_ENV;
                break;
            }
            case self::FILES :
            {
                $data = $_FILES;
                break;
            }
            case self::COOKIE :
            {
                $data = $_COOKIE;
                break;
            }
        }

        return is_array($data) && key_exists($key,$data) && $data[$key] != null;
    }
    
    /**
     * @name get
     * @description get data by a key
     * @access public
     * @param string $key the key to get data with
     * @param mixed $default the default data to get in case of empty results 
     * @return mixed
     */
    public function retrieve(string $key = self::ALL,string $method = self::GET,$default = null) 
    {
        $data = null;

        switch ($method) 
        {
            case self::GET :
            {
                $data = $_GET;
                break;
            }
            case self::POST :
            {
                $data = $_POST;
                break;
            }
            case self::SERVER :
            {
                $data = $_SERVER;
                break;
            }
            case self::ENV :
            {
                $data = $_ENV;
                break;
            }
            case self::FILES :
            {
                $data = $_FILES;
                break;
            }
            case self::COOKIE :
            {
                $data = $_COOKIE;
                break;
            }
        }

        if($key == self::ALL)
        {
            return $data;
        }

        if(is_array($data) && key_exists($key,$data))  
        {
            return $data[$key];
        }

        return $default;
    }

    /**
     * @name post
     * @description set data by a key from / inside $_POST
     * @access public
     * @param string $key the key to get data with
     * @param mixed $value 
     * @return mixed
     */
    public function insert(string $key,$value,string $method = self::GET) 
    {
        switch ($method) 
        {
            case self::GET :
            {
                $_GET[$key] = $value;
                break;
            }
            case self::POST :
            {
                $_POST[$key] = $value;
                break;
            }
            case self::FILES :
            {
                $_FILES[$key] = $value;
                break;
            }
            case self::SERVER :
            {
                $_SERVER[$key] = $value;
                break;
            }
            case self::ENV :
            {
                $_ENV[$key] = $value;
                break;
            }
            case self::COOKIE :
            {
                $_COOKIE[$key] = $value;
                break;
            }
        }
    }
    
    /**
     * @name getMethod
     * @description gets the request method either _GET or _POST or nothing in case of error 
     * @access public
     * @return string
     */
    public function getMethod() : string
    {
        $method = strtoupper($this->retrieve('REQUEST_METHOD',self::SERVER));

        if (!in_array($method, self::HTTP_METHODS)) 
        {
            throw new HTTPException('Unknown request method');
        }

        return $method;
    }
    
    /**
     * @name getBaseURL
     * @description gets the base URL
     * @access public 
     * @return string
     */
    public function getBaseURL() : string
    {
        $protocol = $this->retrieve('HTTPS',self::SERVER) != null && $this->retrieve('HTTPS',self::SERVER) != 'off' ? 'https://' : 'http://';
        $host = $this->retrieve('HTTP_HOST',self::SERVER);
        return $protocol . $host;
    }
    
    /**
     * @name getCurrentURL
     * @description gets the current URL
     * @access public 
     * @return string
     */
    public function getCurrentURL() : string
    {
        $protocol = $this->retrieve('HTTPS',self::SERVER) != null && $this->retrieve('HTTPS',self::SERVER) != 'off' ? 'https://' : 'http://';
        $host = $this->retrieve('HTTP_HOST',self::SERVER);
        return $protocol . $host .$this->retrieve('REQUEST_URI',self::SERVER);
    }

    /**
     * @name parseURL
     * @description parses the give url
     * @param string $url
     * @access public 
     * @return array
     */
    public function parseURL(string $url) : array
    {
        $result = [];
        
        if(filter_var($url,FILTER_VALIDATE_URL))
        {
            $result = parse_url($url);
        }
        
        return $result == false ? [] : $result;
    }
    
    /**
     * @name getRequestURL
     * @description get the current request url
     * @access public
     * @return string
     */
    public function getRequestURL()
    {
        $url = $this->exists('request_url',self::GET) ? explode(RDS,$this->retrieve('request_url',self::GET)) : [DEFAULT_CONTROLLER,DEFAULT_ACTION];
        $controller = count($url) < 1 || empty($url[0]) ? DEFAULT_CONTROLLER : $url[0];
        $action = count($url) < 2 || empty($url[1]) ? DEFAULT_ACTION : $url[1];
        $final = $controller . RDS . $action;
        
        if(count($url) > 2)
        {
            for ($index = 2; $index < count($url); $index++) 
            {
                $final .= RDS . $url[$index];
            }
        }
        
        return $final;
    }

    /**
     * @name getRequestExtension
     * @description get the current request extension
     * @access public
     * @return string
     */
    public function getRequestExtension()
    {
        return self::exists('extension',self::GET) ? $this->retrieve('extension',self::GET) : DEFAULT_EXTENSION;
    }

    /**
     * @name soap
     * @description soap query executor
     * @access public
     * @return mixed
     */
    public function soap(string $wsdl,string $function,array $parameters = [])
    {
        $client = new \soapclient($wsdl);
        $result = $client->{$function}($parameters);
        return Arrays::getInstance()->get(json_decode(json_encode($result),true),'return',[]);
    }
    
    /**
     * @name rest
     * @description rest query executor
     * @access public
     * @return mixed
     */
    public function rest(string $url,$parameters = [],string $method = Request::GET,string $username = '',$password = '',$headers = []) : string
    {
        # construct the URL
        if($method == Request::GET && count($parameters))
        {
            $url .= '?' . http_build_query($parameters);
        }
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (X11; Ubuntu; Linux i686; rv:26.0) Gecko/20100101 Firefox/26.0");
        curl_setopt($ch, CURLOPT_FAILONERROR, true);
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_VERBOSE, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
        
        // Optional Authentication:
        if(strlen($username) > 0 && strlen($password) > 0)
        {
            curl_setopt($ch, CURLOPT_HTTPAUTH,CURLAUTH_BASIC);
            curl_setopt($ch, CURLOPT_USERPWD,"{$username}:{$password}");
        }
        
        switch ($method)
        {
            case Request::POST :
            {
                curl_setopt($ch,CURLOPT_POST,true);
                
                # check for parameters if any ( and if it's an array or json
                if(is_array($parameters))
                {
                    if(count($parameters)) curl_setopt($ch,CURLOPT_POSTFIELDS,$parameters);
                }
                else
                {
                    if(is_string($parameters) && strlen($parameters) > 0) curl_setopt($ch,CURLOPT_POSTFIELDS,$parameters);
                }
                    
                break;
            }
            case Request::PUT :
            {
                curl_setopt($ch,CURLOPT_PUT,true);
                
                # check for parameters if any ( and if it's an array or json
                if(is_array($parameters))
                {
                    if(count($parameters)) curl_setopt($ch,CURLOPT_POSTFIELDS,$parameters);
                }
                else
                {
                    if(is_string($parameters) && strlen($parameters) > 0) curl_setopt($ch,CURLOPT_POSTFIELDS,$parameters);
                }
                    
                break;
            }
        }
        
        if(count($headers))
        {
            curl_setopt($ch,CURLOPT_HTTPHEADER,$headers);
        }
        
        # Execute the REST call
        $result = curl_exec($ch);
        
        ob_start();
        
        # free resources
        curl_close($ch);
         
        ob_end_clean();
        
        # send back the data
        return strval($result);
    }
            
    /**
     * @name curl
     * @description curl query executor
     * @access public
     * @return mixed
     */
    public function curl(string $url,$parameters = [],string $method = Request::GET,$headers = false, bool $followRedirects = true,string $cookiesFile = '',$userpass = '') 
    {
        # construct the URL
        if($method == Request::GET && count($parameters))
        {
            $url .= '?' . http_build_query($parameters);
        }
        
        $properties = [
            CURLOPT_USERAGENT => "Mozilla/5.0 (X11; Ubuntu; Linux i686; rv:26.0) Gecko/20100101 Firefox/26.0",
            CURLOPT_FAILONERROR => true,
            CURLOPT_AUTOREFERER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_RETURNTRANSFER => true
        ];
        
        if($userpass != null && $userpass != '' && \IR\Utils\Types\Strings::getInstance()->contains($userpass,':'))
        {
            $properties[CURLOPT_USERPWD] = $userpass;
        }
        
        if($cookiesFile != '')
        {
            $properties[CURLOPT_COOKIEJAR] = $cookiesFile;
            $properties[CURLOPT_COOKIEFILE] = $cookiesFile;
        }

        switch ($method)
        {
            case Request::GET :
            {
                $properties[CURLOPT_CUSTOMREQUEST] = 'GET';
                break;
            }
            case Request::POST :
            {
                $properties[CURLOPT_CUSTOMREQUEST] = 'POST';
                
                if(is_array($parameters))
                {
                    $properties[CURLOPT_POSTFIELDS] = http_build_query($parameters);
                }
                else
                {
                    if(json_decode($parameters) !== FALSE || json_decode($parameters) !== null)
                    {
                        $properties[CURLOPT_POSTFIELDS] = $parameters;
                    }
                    else
                    {
                        $properties[CURLOPT_POSTFIELDS] = http_build_query($parameters);
                    }
                }
                    
                break;
            }
            case Request::PUT :
            {
                $properties[CURLOPT_CUSTOMREQUEST] = 'PUT';
                
                if(is_array($parameters))
                {
                    $properties[CURLOPT_POSTFIELDS] = http_build_query($parameters);
                }
                else
                {
                    if(json_decode($parameters) !== FALSE || json_decode($parameters) !== null)
                    {
                        $properties[CURLOPT_POSTFIELDS] = $parameters;
                    }
                    else
                    {
                        $properties[CURLOPT_POSTFIELDS] = http_build_query($parameters);
                    }
                }
                    
                break;
            }
        }
        
        if(is_array($headers))
        {
            $properties[CURLOPT_HTTPHEADER] = $headers;
        }
        
        # return headers as requested
        elseif ($headers == true) 
        {
            $properties[CURLOPT_HEADER] = true;
        }
        
        # only return headers
        elseif ($headers == 'headers only') 
        {
            $properties[CURLOPT_NOBODY] = true;
        }
        
        # follow redirects 
        if ($followRedirects == true) 
        {
            $properties[CURLOPT_FOLLOWLOCATION] = true;
        }
        
        # Execute the REST call
        $ch = curl_init($url);
        curl_setopt_array($ch,$properties);
        $response = curl_exec($ch);
        
        if ($response === false)
        {
            $response = curl_error($ch);
        }

        ob_start();
        
        # free resources
        curl_close($ch);
         
        ob_end_clean();
        
        # send back the data
        return $response;
    }
    
    /**
     * @name download
     * @description downloads a file into a path
     * @access public
     * @param string $request
     * @param string $fileName
     * @return bool
     */
    public function download(string $request, string $fileName) : bool
    {
        $result = 0;

        $fh = fopen($fileName, 'w');
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $request); 
        curl_setopt($ch, CURLOPT_FILE, $fh); 
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        $result = curl_exec($ch);
        curl_close($ch);
        fclose($fh);

        return $result;
    }
    
    /**
     * @name parseCookieJar
     * @description parse a cookie jar file
     * @access public
     * @param string $fileName
     * @return array
     */
    public function parseCookieJar(string $fileName) : array
    {
        $cookie = [];
        $lines = explode(PHP_EOL,FileSystem::readFile($fileName));

        foreach ($lines as $line) 
        {
            if (substr($line, 0, 10) == '#HttpOnly_') 
            {
                $line = substr($line, 10);
                $cookie['httponly'] = true;
            }
            else 
            {
                $cookie['httponly'] = false;
            } 
            
            if( strlen( $line ) > 0 && $line[0] != '#' && substr_count($line, "\t") == 6) 
            {
                $tokens = explode("\t", $line);
                $tokens = array_map('trim', $tokens);

                # Extract the data
                $cookie['domain'] = $tokens[0]; # The domain that created AND can read the variable.
                $cookie['flag'] = $tokens[1];   # A TRUE/FALSE value indicating if all machines within a given domain can access the variable. 
                $cookie['path'] = $tokens[2];   # The path within the domain that the variable is valid for.
                $cookie['secure'] = $tokens[3]; # A TRUE/FALSE value indicating if a secure connection with the domain is needed to access the variable.

                $cookie['expiration-epoch'] = $tokens[4];  # The UNIX time that the variable will expire on.   
                $cookie['name'] = urldecode($tokens[5]);   # The name of the variable.
                $cookie['value'] = urldecode($tokens[6]);  # The value of the variable.

                # Convert date to a readable format
                $cookie['expiration'] = intval($tokens[4]);

                # Record the cookie.
                $cookies[] = $cookie;
            }
        }

        return $cookies;
    }
    
    /**
     * @name checkConnectivity
     * @description gets browser default language
     * @access public
     * @return bool
     */
    public function checkConnectivity(string $host,int $port = 22,int $timeOut = 30) : bool
    { 
        $connected = false;
        
        $errorCode = null;
        $errorMessage = null;
        
        # open a socket
        $socket = \fsockopen($host,$port,$errorCode,$errorMessage,$timeOut);

        if(!empty($socket) && $socket !== false)
        {
            $connected = true;
            fclose($socket);
        }
        else
        {
            throw new HTTPException('Error trying to connect to : ' . $host . ' with port : ' . $port . ' , error (' . $errorCode . ') : ' . $errorMessage,500);
        }
        
        return $connected;
    }
    
    /**
     * @name getURLRedirects
     * @description get URL redirects
     * @access public
     * @return array
     */
    public function getURLRedirects(string $url,int $timeOut = 10,int $maxRedirects = 10) : array
    {
        $redirects = [];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; rv:1.7.3) Gecko/20041001 Firefox/0.10.1");
        curl_setopt($ch, CURLOPT_URL,str_replace("&amp;", "&", urldecode(trim($url))));
        //curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeOut);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeOut);
        curl_setopt($ch, CURLOPT_MAXREDIRS,$maxRedirects);
        curl_setopt($ch,CURLOPT_HEADER,true);
        //curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        //curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        $response = curl_exec($ch);
        //$response = curl_getinfo($ch);
        curl_close($ch); 
        \IR\Core\Application::getCurrent()->utils->printer->printValue($response);
        if ($response['http_code'] == 301 || $response['http_code'] == 302) 
        {
            ini_set("user_agent", "Mozilla/5.0 (Windows; U; Windows NT 5.1; rv:1.7.3) Gecko/20041001 Firefox/0.10.1");
            $headers = get_headers($response['url']);

            foreach ($headers as $value) 
            {
                if (substr(strtolower($value), 0, 9) == "location:")
                {
                    $redirects[] = trim(substr($value, 9, strlen($value)));
                }
            }
        }

        return $redirects;
    }
    
    /**
     * @name getBrowserLanguage
     * @description gets browser default language
     * @access static
     * @return string
     */
    public function getBrowserLanguage() : string
    { 
        $lang = $this->retrieve('HTTP_ACCEPT_LANGUAGE',self::SERVER);
        return $lang != null && strlen($lang) > 0 ? strtoupper(substr($lang, 0, 2)) : 'en';
    }

    /**
     * @name getBrowserLanguage
     * @description gets browser default language
     * @access public
     * @return string
     */
    public function getUserAgent() : string
    { 
        $agent = $this->retrieve('HTTP_USER_AGENT',self::SERVER);
        return $agent == null ? '' : $agent;
    }
    
    /**
     * @name __construct
     * @description class constructor
     * @access private
     * @return Request
     */
    private function __construct() {}

    /**
     * @name __clone
     * @description preventing cloning object
     * @access public
     * @return Request
     */
    public function __clone()
    {
        return (self::$_instance != null) ? self::$_instance : new Request();  
    }
    
    /** 
     * @readwrite
     * @access private 
     * @var Request
     */ 
    private static $_instance;
    
    /** 
     * @access  static 
     * @var array
     */ 
    const HTTP_METHODS = ['GET','POST','COOKIE','HEAD','PUT','DELETE'];

    /** 
     * @access  static 
     * @var string
     */ 
    const GET = 'GET';

    /** 
     * @access  static 
     * @var string
     */ 
    const POST = 'POST';

    /** 
     * @access  static 
     * @var string
     */ 
    const FILES = 'FILES';

    /** 
     * @access  static 
     * @var string
     */ 
    const COOKIE = 'COOKIE';

    /** 
     * @access  static 
     * @var string
     */ 
    const SERVER = 'SERVER';

    /** 
     * @access  static 
     * @var string
     */ 
    const ENV = 'ENV';

    /** 
     * @access  static 
     * @var string
     */ 
    const HEAD = 'HEAD';

    /** 
     * @access  static 
     * @var string
     */ 
    const PUT = 'PUT';

    /** 
     * @access  static 
     * @var string
     */ 
    const DELETE = 'DELETE';
    
    /** 
     * @access  static 
     * @var string
     */ 
    const ALL = 'ALL_RECORDS';
}