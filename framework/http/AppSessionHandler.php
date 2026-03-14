<?php declare(strict_types=1); namespace IR\Http; if (!defined('IR_START')) exit('<pre>No direct script access allowed</pre>');
/**
 * @framework       iResponse Framework 
 * @version         1.0
 * @author          Amine Idrissi <contact@iresponse.tech>
 * @date            2024
 * @name            AppSessionHandler.php	
 */

# defaults
use \SessionHandler;

# core 
use IR\Core\Application as Application;

/**
 * @name AppSessionHandler
 * @description AppSessionHandler class
 */
class AppSessionHandler extends SessionHandler
{
    /**
     * @name __construct
     * @description class constructor
     * @access public
     * @return AppSessionHandler
     */
    public function __construct()
    {
        # set some server parameters 
        ini_set('session.use_cookies','1');
        ini_set('session.use_only_cookies','1');
        ini_set('session.use_trans_sid','0');
        ini_set('session.save_handler','files');
        ini_set('session.gc_maxlifetime',strval($this->_sessionMaxLifeTime));
        
        # set session name 
        session_name($this->_sessionName);
        
        # set session save path 
        session_save_path($this->_sessionSavePath);
        
        # get current domain
        $domain = '';
        $parsed = Request::getInstance()->parseURL(Request::getInstance()->getBaseURL());
        
        if(count($parsed) && key_exists('host',$parsed))
        {
            $domain = filter_var($parsed['host'],FILTER_VALIDATE_IP) ? $parsed['host'] : '.' . $parsed['host'];
        }

        # set session cookies parameters 
        session_set_cookie_params($this->_sessionMaxLifeTime,$this->_sessionPath,$domain,$this->_sessionSSL,$this->_sessionHTTPOnly);
        
        # set session handler 
        session_set_save_handler($this,true);
    }
    
    /**
     * @name read
     * @description read data from the session
     * @access public
     * @return mixed
     */
    public function read($id)
    {
        return Application::getCurrent()->utils->encryptor->decrypt(parent::read($id),AppSessionHandler::$_SESSION_KEY);
    }
    
    /**
     * @name write
     * @description write data into the session
     * @access public
     * @return mixed
     */
    public function write($id,$data) 
    {
        return parent::write($id,Application::getCurrent()->utils->encryptor->encrypt($data,AppSessionHandler::$_SESSION_KEY));
    }

    /**
     * @name connect
     * @description connect to the session
     * @access public
     * @return mixed
     */
    public function connect() 
    {
        if('' == session_id())
        {
            if(!headers_sent()) session_start();
        }
        else
        {
            if(!headers_sent())
            {
                session_write_close();
                session_start();
            }
        }
    }
     
    /**
     * @name release
     * @description release the session
     * @access public
     * @return mixed
     */
    public function release() 
    {
        session_write_close();
    }
    
    /**
     * @name disconnect
     * @description disconnect from the session
     * @access public
     * @return mixed
     */
    public function disconnect() 
    {
        session_destroy();
        unset($_SESSION);
        $_SESSION = [];
    }
    
    /** 
     * @readwrite
     * @access private 
     * @var string
     */ 
    private $_sessionName = 'iresponse_session';
    
    /** 
     * @readwrite
     * @access private 
     * @var integer
     */ 
    private $_sessionMaxLifeTime = 3600 * 24 * 30;
    
    /** 
     * @readwrite
     * @access private 
     * @var bool
     */ 
    private $_sessionSSL = false;
    
    /** 
     * @readwrite
     * @access private 
     * @var bool
     */ 
    private $_sessionHTTPOnly = true;
    
    /** 
     * @readwrite
     * @access private 
     * @var string
     */ 
    private $_sessionPath = RDS;
    
    /** 
     * @readwrite
     * @access private 
     * @var string
     */ 
    private $_sessionSavePath = SESSIONS_PATH;
    
    /** 
     * @readwrite
     * @access private 
     * @var string
     */ 
    public static $_SESSION_KEY = 'z#SAsZb#@yPf5Jrzz$9Ug%V$V^_zx!68U5HBfK-&r!gNF559@qJzbxP4aVHT7em#';
}