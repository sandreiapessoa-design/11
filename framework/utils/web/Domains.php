<?php declare(strict_types=1); namespace IR\Utils\Web; if (!defined('IR_START')) exit('<pre>No direct script access allowed</pre>');
/**
 * @framework       iResponse Framework 
 * @version         1.0
 * @author          Amine Idrissi <contact@iresponse.tech>
 * @date            2024
 * @name            Domains.php	
 */

/**
 * @name Domains
 * @description Domains web utils class
 */
class Domains
{
    /**
     * @name getInstance
     * @description singleton access to constructor
     * @access public | static
     * @return Domains
     */
    public static function getInstance() : Domains
    {
        if(self::$_instance == null)
        {
            self::$_instance = new Domains();
        }
        
        return self::$_instance;
    }
    
    /**
     * @name isValidDomain
     * @description checks if a given domain a valid one or not
     * @access public
     * @param string $domain
     * @return boolean
     */
    public function isValidDomain(string $domain) : bool
    {
        $domainLen = strlen($domain);
        
        if ($domainLen < 3 OR $domainLen > 253)
        {
            return false;
        }

        if(stripos($domain, 'http://') === 0)
        {
            $domain = substr($domain, 7);
        }
        elseif(stripos($domain, 'https://') === 0)
        {
            $domain = substr($domain, 8);
        }
                   
        if(stripos($domain, 'www.') === 0)
        {
            $domain = substr($domain, 4); 
        }

        if(strpos($domain, '.') === FALSE OR $domain[strlen($domain)-1] == '.' OR $domain[0] == '.')
        {
            return false;
        }

        return (filter_var ('http://' . $domain, FILTER_VALIDATE_URL) === false) ? false : true;
    }
    
    /**
     * @name getDomainFromURL
     * @description get domain from URL
     * @access public
     * @param string $url
     * @return mixed
     */
    public function getDomainFromURL(string $url) : string
    {
        $url = strpos($url,'http') > -1 ? $url : 'http://' . $url;
        $pieces = parse_url($url);
        $domain = isset($pieces['host']) ? $pieces['host'] : '';
        $regs = [];

        if (preg_match('/(?P<domain>[a-z0-9][a-z0-9\-]{1,63}\.[a-z\.]{2,6})$/i', $domain, $regs)) 
        {
            return str_replace(['https://','http://'],'',$regs['domain']);
        }

        return str_replace(['https://','http://'],'',$url);
    }

    /**
     * @name isTimedOut
     * @description checks if a domain is timed out or not
     * @access public
     * @param string $domain 
     * @param integer $timeout 
     * @return boolean
     */
    public function isTimedOut(string $domain, int $timeout = 10) : bool
    {
        //initialize curl
        $curlInit = curl_init($domain);
        
        curl_setopt($curlInit,CURLOPT_CONNECTTIMEOUT,$timeout);
        curl_setopt($curlInit,CURLOPT_HEADER,true);
        curl_setopt($curlInit,CURLOPT_NOBODY,true);
        curl_setopt($curlInit,CURLOPT_RETURNTRANSFER,true);

        //get answer
        $response = curl_exec($curlInit);

        curl_close($curlInit);

        if ($response) return false;

        return true;
    }
    
    /**
     * @name __construct
     * @description class constructor
     * @access private
     * @return Domains
     */
    private function __construct() {}

    /**
     * @name __clone
     * @description preventing cloning object
     * @access public
     * @return Domains
     */
    public function __clone()
    {
        return (self::$_instance != null) ? self::$_instance : new Domains();  
    }
    
    /** 
     * @readwrite
     * @access private 
     * @var Domains
     */ 
    private static $_instance;
}


