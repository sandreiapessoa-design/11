<?php declare(strict_types=1); namespace IR\Core; if (!defined('IR_START')) exit('<pre>No direct script access allowed</pre>');
/**
 * @framework       iResponse Framework 
 * @version         1.0
 * @author          Amine Idrissi <contact@iresponse.tech>
 * @date            2024
 * @name            Registry.php	
 */

# utilities 
use IR\Utils\Types\Arrays as Arrays;

/**
 * @name Registry
 * @description core registry class
 */
class Registry
{
    /**
     * @name getInstance
     * @description singleton access to constructor
     * @access public | static
     * @return Registry
     */
    public static function getInstance() : Registry
    {
        if(self::$_instance == null)
        {
            self::$_instance = new Registry();
        }
        
        return self::$_instance;
    }
    
    /**
     * @name set
     * @description stores an instance ( could be anything , objects , settings , arrays .... ) inside our registry
     * @access public
     * @param string $key  
     * @param mixed $instance 
     * @return
     */
    public function set(string $key,$instance)  
    {  
        $this->elements[$key] = $instance;  
    }  

    /**
     * @name get
     * @description gets an instance from our registry 
     * @access public
     * @param string $key
     * @param mixed $default  
     * @return mixed
     */
    public function get(string $key,$default = null)  
    {  
        if(array_key_exists($key,$this->elements))  
        {  
            return $this->elements[$key];  
        }  
        
        return $default;
    } 

    /**
     * @name erase
     * @description erases an instance from our registry 
     * @access public
     * @param string $key  
     * @return
     */
    public function erase(string $key)  
    {   
        unset($this->elements[$key]);  
    }
    
    /**
     * @name getAll
     * @description gets the whole registry 
     * @access public  
     * @return mixed
     */
    public function getAll() : array
    {  
        return $this->elements;
    } 
    
    /**
     * @name contains
     * @description check if a key is existed
     * @access public
     * @param string $key  
     * @return
     */
    public function contains(string $key) : bool
    {   
        return Arrays::getInstance()->containsKey($this->elements,$key); 
    }
    
    /**
     * @name __construct
     * @description class constructor
     * @access private
     * @return Registry
     */
    private function __construct() {}

    /**
     * @name __clone
     * @description preventing cloning object
     * @access public
     * @return Registry
     */
    public function __clone()
    {
        return (self::$_instance != null) ? self::$_instance : new Registry();  
    }
    
    /** 
     * @readwrite
     * @access private 
     * @var Registry
     */ 
    private static $_instance;
    
    /** 
     * @read
     * @access private 
     * @var array
     */ 
    protected $elements = [];  
}


