<?php declare(strict_types=1); namespace IR\Http; if (!defined('IR_START')) exit('<pre>No direct script access allowed</pre>');
/**
 * @framework       iResponse Framework 
 * @version         1.0
 * @author          Amine Idrissi <contact@iresponse.tech>
 * @date            2024
 * @name            Response.php	
 */
# defaults 
use ErrorException;

# exceptions 
use IR\Exceptions\Types\HTTPException as HTTPException;

/**
 * @name Response
 * @description http responses class
 */
class Response
{
    /**
     * @name getInstance
     * @description singleton access to constructor
     * @access public | static
     * @return Response
     */
    public static function getInstance() : Response
    {
        if(self::$_instance == null)
        {
            self::$_instance = new Response();
        }
        
        return self::$_instance;
    }
    
    /**
     * @name redirect
     * @description redirecting to a given url
     * @access static
     * @param string $url
     * @param integer $code
     * @param boolean $force
     * @throws HTTPException
     * @return
     */
    public static function redirect(string $url, int $code = 301, bool $force = true)
    {
        try
        {
            $url = ($url !='') ? $url : Request::getInstance()->getBaseURL();
            header("Location: $url", $force, $code);
            exit;
        }
        catch (ErrorException $e)
        {
            throw new HTTPException($e->getMessage(),$e->getCode(),$e);
        }
    }


    /**
     * @name redirect
     * @description redirecting to a given url
     * @access static
     * @param string $url
     * @param integer $code
     * @param boolean $force
     * @throws HTTPException
     * @return
     */
    public static function redirectToPreviousPage()
    {
        try
        {
            $url = Request::getInstance()->retrieve('HTTP_REFERER',Request::SERVER);
            header("Location: $url",true,301);
            exit;
        }
        catch (ErrorException $e)
        {
            throw new HTTPException($e->getMessage(),$e->getCode(),$e);
        }
    }

    /**
     * @name header
     * @description set a header to the output page
     * @access static
     * @param integer $code
     * @param boolean $force
     * @param boolean $exit
     * @return
     * @throws HTTPException
     */
    public static function header(int $code, bool $force = true, bool $exit = false)
    {
        try
        {
            if (!headers_sent())
            {
                if (is_numeric($code))
                {
                    header("HTTP/1.1 $code ".(self::RESPONSE_MESSAGES[$code]), true, $code);
                }   
                else
                {
                    header($code, $force);
                }    
            }

            if ($exit !== FALSE)
            {
                die(self::RESPONSE_MESSAGES[$code]);
            }
        }
        catch (ErrorException $e)
        {
            throw new HTTPException($e->getMessage(),$e->getCode(),$e);
        }
    }
    
    /**
     * @name __construct
     * @description class constructor
     * @access private
     * @return Response
     */
    private function __construct() {}

    /**
     * @name __clone
     * @description preventing cloning object
     * @access public
     * @return Response
     */
    public function __clone()
    {
        return (self::$_instance != null) ? self::$_instance : new Response();  
    }
    
    /** 
     * @readwrite
     * @access private 
     * @var Response
     */ 
    private static $_instance;
    
    /** 
     * @access  static 
     * @var array
     */ 
    const RESPONSE_MESSAGES = [
        100 => 'Continue',
        101 => 'Switching Protocols',

        # Success 
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',

        # Redirection 
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found', 
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        307 => 'Temporary Redirect',

        # Client Error 
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested Range Not Satisfiable',
        417 => 'Expectation Failed',

        # Server Error 
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        509 => 'Bandwidth Limit Exceeded'
    ];
}


