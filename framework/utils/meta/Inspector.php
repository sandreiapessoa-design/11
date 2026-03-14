<?php declare(strict_types=1); namespace IR\Utils\Meta; if (!defined('IR_START')) exit('<pre>No direct script access allowed</pre>');
/**
 * @framework       iResponse Framework 
 * @version         1.0
 * @author          Amine Idrissi <contact@iresponse.tech>
 * @date            2024
 * @name            Inspector.php	
 */

# php defaults 
use \ReflectionClass;
use \ReflectionMethod;
use \ReflectionProperty;

# utilities
use IR\Utils\Types\Strings as Strings;
use IR\Utils\Types\Arrays as Arrays;

/**
 * @name Inspector
 * @description objects utils class
 */
class Inspector
{
    /**
     * @name getInstance
     * @description singleton access to constructor
     * @access public | static
     * @return Inspector
     */
    public static function getInstance() : Inspector
    {
        if(self::$_instance == null)
        {
            self::$_instance = new Inspector();
        }
        
        return self::$_instance;
    }
    
   /**
     * @name classMeta
     * @description retrieves class metadata
     * @access public
     * @param mixed $class 
     * @return array
     */
    public function classMeta($class) : array
    {
        $reflection = new ReflectionClass($class);
        $comment = $reflection->getDocComment();

        if (!empty($comment)) 
        {
            $metadata = $this->_parse($comment);
        } 
        else 
        {
            $metadata = [];
        }

        return $metadata;
    }

    /**
     * @name methodMeta
     * @description retrieves method metadata
     * @access public
     * @param mixed $class 
     * @param string $method
     * @return array
     */
    public function methodMeta($class,string $method) : array
    {
        $reflection = new ReflectionMethod($class,$method);
        $comment = $reflection->getDocComment();

        if (!empty($comment)) 
        {
            $metadata = $this->_parse($comment);
        } 
        else 
        {
            $metadata = [];
        }

        return $metadata;
    }

    /**
     * @name propertiesMeta
     * @description retrieves property metadata
     * @access public
     * @param mixed $class 
     * @param string $property 
     * @return array
     */
    public function propertiesMeta($class,string $property) : array
    {
        $reflection = new ReflectionProperty($class,$property);
        $comment = $reflection->getDocComment();

        if (!empty($comment)) 
        {
            $metadata = $this->_parse($comment);
        } 
        else 
        {
            $metadata = [];
        }

        return $metadata;
    }

    /**
     * @name classProperties
     * @description retrieves class properties
     * @access public
     * @param mixed $class 
     * @return array
     */
    public function classProperties($class) : array
    {
        $properties = [];
        $reflection = new ReflectionClass($class);
        $reflectionProperties = $reflection->getProperties();
        
        foreach ($reflectionProperties as $property) 
        {
            $properties[] = $property->getName();
        }
        
        return $properties;
    }

    /**
     * @name classMethods
     * @description retrieves class methods
     * @access public
     * @param string $class  
     * @return array
     */
    public function classMethods($class) : array
    {
        $methods = [];
        $reflection = new ReflectionClass($class);
        $reflectionMethods = $reflection->getMethods();
        
        foreach ($reflectionMethods as $method) 
        {
            $methods[] = $method->getName();
        }
        
        return $methods;
    }

    /**
     * @name _parse
     * @description parses a prologue of classes / methods / properties ....
     * @access protected
     * @param string $comment 
     * @return array
     */
    protected function _parse($comment) : array
    {
        $meta = [];
        $arraysUtils = Arrays::getInstance();
        $stringsUtils = Strings::getInstance();
        $pattern = '(@[a-zA-Z]+\s*[a-zA-Z0-9, ()_]*)';
        $matches = $stringsUtils->match($comment, $pattern);
        
        
        if ($matches != null) 
        {
            foreach ($matches as $match) 
            {
                $parts = $arraysUtils->clean($arraysUtils->trim($stringsUtils->split($match,'[\s]', 2)));
                $meta[$parts[0]] = true;

                if (sizeof($parts) > 1) 
                {
                    $meta[$parts[0]] = $arraysUtils->clean($arraysUtils->trim($stringsUtils->split($parts[1],',')));
                }
            }
        }
        return $meta;
    }
    
    /**
     * @name __construct
     * @description class constructor
     * @access private
     * @return Inspector
     */
    private function __construct() {}

    /**
     * @name __clone
     * @description preventing cloning object
     * @access public
     * @return Inspector
     */
    public function __clone()
    {
        return (self::$_instance != null) ? self::$_instance : new Inspector();  
    }
    
    /** 
     * @readwrite
     * @access private 
     * @var Inspector
     */ 
    private static $_instance;
}


