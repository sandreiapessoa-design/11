<?php declare(strict_types=1); namespace IR\Core; if (!defined('IR_START')) exit('<pre>No direct script access allowed</pre>');
/**
 * @framework       iResponse Framework 
 * @version         1.0
 * @author          Amine Idrissi <contact@iresponse.tech>
 * @date            2024
 * @name            Http.php	
 */

# http 
use IR\Http\Request as Request;
use IR\Http\Response as Response;
use IR\Http\SessionManager as SessionManager;
use IR\Http\Client as Client;

/**
 * @name Http
 * @description core http class
 */
class Http
{
    /**
     * @name getInstance
     * @description singleton access to constructor
     * @access public | static
     * @return Http
     */
    public static function getInstance() : Http
    {
        if(self::$_instance == null)
        {
            self::$_instance = new Http();
        }
        
        return self::$_instance;
    }

    /**
     * @name __construct
     * @description class constructor
     * @access private
     * @return Http
     */
    private function __construct() 
    {
        $this->request = Request::getInstance();
        $this->response = Response::getInstance();
        $this->session = SessionManager::getInstance();
        $this->client = Client::getInstance();
    }

    /**
     * @name __clone
     * @description preventing cloning object
     * @access public
     * @return Http
     */
    public function __clone()
    {
        return (self::$_instance != null) ? self::$_instance : new Http();  
    }
    
    /** 
     * @readwrite
     * @access private 
     * @var Http
     */ 
    private static $_instance;
    
    /** 
     * @read
     * @access public 
     * @var Request
     */ 
    public $request; 
    
    /** 
     * @read
     * @access public 
     * @var Response
     */ 
    public $response; 
    
    /** 
     * @read
     * @access public 
     * @var Client
     */ 
    public $client; 
    
    /** 
     * @read
     * @access public 
     * @var SessionManager
     */ 
    public $session; 
}


