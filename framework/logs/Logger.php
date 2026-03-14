<?php declare(strict_types=1); namespace IR\Logs; if (!defined('IR_START')) exit('<pre>No direct script access allowed</pre>');
/**
 * @framework       iResponse Framework 
 * @version         1.0
 * @author          Amine Idrissi <contact@iresponse.tech>
 * @date            2024
 * @name            Logger.php	
 */

# utilities
use IR\Utils\Types\Arrays as Arrays;

# exceptions
use Monolog\Logger as MonologLogger;
use Monolog\Handler\StreamHandler as StreamHandler;

/**
 * @name Logger
 * @description logging system class
 */
class Logger
{
    /**
     * @name getInstance
     * @description singleton access to constructor
     * @access public | static
     * @return  Logger
     */
    public static function getInstance() : Logger
    {
        if(self::$_instance == null)
        {
            self::$_instance = new Logger();
        }
        
        return self::$_instance;
    }
    
    /**
     * @name error
     * @description logs an error mssage
     * @access static
     */  
    public function error($loggedObject,bool $isCritical = true)
    {
       if($loggedObject != null)
       {
            if(is_string($loggedObject))
            {
                $message = $loggedObject;
            }
            else
            {
                 $line = $loggedObject->getLine();
                 $file = $loggedObject->getFile();
                 $message = str_replace('\\n','', '"' . $loggedObject->getMessage()).'" in : '.$file.' at line : '.$line;                    
                 $message = trim(preg_replace('/\s+/', ' ', $message));
            }

            if($isCritical == true)
            {
                $this->_errorLogger->critical($message);
            }
            else
            {
                $this->_errorLogger->error($message);
            }   
       }
    }  

    /**
     * @name __construct
     * @description class constructor
     * @access private
     * @return Logger
     */
    private function __construct() 
    {
        # create the logger objects and add handlers 
        $this->_errorLogger = new MonologLogger('iResponse Framework');
        $this->_errorLogger->pushHandler(new StreamHandler(LOGS_PATH . DS . 'frontend_errors.log',MonologLogger::ERROR));
    }

    /**
     * @name __clone
     * @description preventing cloning object
     * @access public
     * @return Logger
     */
    public function __clone()
    {
        return (self::$_instance != null) ? self::$_instance : new Logger();  
    }
    
    /** 
     * @readwrite
     * @access private 
     * @var Logger
     */ 
    private static $_instance;
    
    /** 
     * @readwrite
     * @access private 
     * @var MonologLogger
     */ 
    private $_criticalLogger;
    
    /** 
     * @readwrite
     * @access private 
     * @var MonologLogger
     */ 
    private $_errorLogger;
    
    /** 
     * @readwrite
     * @access private 
     * @var MonologLogger
     */ 
    private $_warningLogger;
    
    /** 
     * @readwrite
     * @access private 
     * @var MonologLogger
     */ 
    private $_debugLogger;
    
    /** 
     * @readwrite
     * @access private 
     * @var MonologLogger
     */ 
    private $_infoLogger;
    
    /** 
     * @readwrite
     * @access private 
     * @var MonologLogger
     */ 
    private $_noticeLogger;
}


