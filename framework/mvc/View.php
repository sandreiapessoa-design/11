<?php declare(strict_types=1); namespace IR\Mvc; if (!defined('IR_START')) exit('<pre>No direct script access allowed</pre>');
/**
 * @framework       iResponse Framework 
 * @version         1.0
 * @author          Amine Idrissi <contact@iresponse.tech>
 * @date            2024
 * @name            View.php	
 */

# core
use IR\Core\Base as Base;

# templating
use IR\Templating\Template as Template;
use IR\Templating\DefaultImplementation as DefaultImplementation;

# exceptions
use IR\Exceptions\Types\ArgumentException as ArgumentException;

/**
 * @name View
 * @description View class
 */
class View extends Base
{
    /**
     * @name __construct
     * @description the class constructor
     * @access public
     * @param array $options
     * @return View
     */
    public function __construct(array $options = [])
    {
        parent::__construct($options);
        
        $this->_template = new Template([
            "implementation" => new DefaultImplementation()
        ]);
    }
    
    /**
     * @name render
     * @description parses the template , replaces the tags and return the final page
     * @access public
     * @param array $options
     * @return string
     */
    public function render() : string
    {
        
        if (!file_exists($this->getFile())) 
        {
            return "";
        }
        
        return str_replace("\\'","'",$this->_template->process($this->getFile(),$this->_data));
    }

    /**
     * @name get
     * @description retrieves data form the template data array
     * @access public
     * @param string $key
     * @param string $default
     * @return mixed
     */
    public function get(string $key, $default = "") 
    {
        if (isset($this->_data[$key])) 
        {
            return $this->_data[$key];
        }
        
        return $default;
    }

    /**
     * @name set
     * @description A public representation of _set method that can take an array as the first argument , in that case we loop throw that array and stores every row of it Note : it should be an associative array
     * @access public
     * @param string $key
     * @param string $value
     * @return View
     */
    public function set($key, $value = null) : View
    {
        if (is_array($key)) 
        {
            foreach ($key as $_key => $value) 
            {
                $this->_set($_key, $value);
            }
            
            return $this;
        }
        else
        {
            $this->_set($key, $value);
        }

        return $this;
    }

    /**
     * @name erase
     * @description erases values from the template data array
     * @access public
     * @param string $key
     * @return View
     */
    public function erase(string $key) : View
    {
        unset($this->_data[$key]);
        return $this;
    }
    
    /**
     * @name _set
     * @description stores data inside the template data array
     * @access protected
     * @param string $key
     * @param string $value
     * @return
     * @throws ArgumentException
     */
    protected function _set($key, $value) 
    {
        if (!is_string($key) && !is_numeric($key)) 
        {
            throw new ArgumentException("Key must be a string or a number");
        }

        $this->_data[$key] = $value;
    }

    /**
     * @readwrite
     * @access protected 
     * @var string
     */
    protected $_file;

    /** 
     * @readwrite
     * @access protected 
     * @var string
     */ 
    protected $_template;

    /** 
     * @readwrite
     * @access protected 
     * @var array
     */ 
    protected $_data = [];
}


