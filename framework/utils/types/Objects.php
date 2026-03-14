<?php declare(strict_types=1); namespace IR\Utils\Types; if (!defined('IR_START')) exit('<pre>No direct script access allowed</pre>');
/**
 * @framework       iResponse Framework 
 * @version         1.0
 * @author          Amine Idrissi <contact@iresponse.tech>
 * @date            2024
 * @name            Objects.php	
 */

/**
 * @name Objects
 * @description objects utils class
 */
class Objects
{
    /**
     * @name getInstance
     * @description singleton access to constructor
     * @access public | static
     * @return Objects
     */
    public static function getInstance() : Objects
    {
        if(self::$_instance == null)
        {
            self::$_instance = new Objects();
        }
        
        return self::$_instance;
    }
    
    /**
     * @name toArray
     * @description converts an Object into an array
     * @access public
     * @param object $object the object to copy variables from
     * @param array $array  the array to copy variables to
     * @return array
     */
    public function toArray($object) : array
    {
        $array = [];  

        if (is_object($object) || $object instanceof \stdClass) 
        {
            $array = get_object_vars($object);
            
            if(count($array))
            {
                foreach ($array as $key => $value) 
                {
                    if(is_object($value) || $value instanceof \stdClass)
                    {
                        $array[$key] = $this->toArray($value);
                    }
                }
            }
        }
        
        return $array;
    }
    
    /**
     * @name getName
     * @description get class name without namespace in it  
     * @access public
     * @param mixed $object
     * @return mixed
     */
    public function getName($object) : string
    {
        $classname = get_class($object);
        $matches = [];
        
        if (preg_match('@\\\\([\w]+)$@', $classname, $matches)) 
        {
            $classname = $matches[1];
        }
        
        return $classname;
    }
    
    /**
     * @name removeNameSpaces
     * @description remove namespaces 
     * @access public
     * @param string $className
     * @return mixed
     */
    public function removeNameSpaces(string $className) : string
    {
        $matches = [];
        
        if (preg_match('@\\\\([\w]+)$@', $className, $matches)) 
        {
            $className = $matches[1];
        }
        
        return $className;
    }
    
    /**
     * @name unserialize
     * @description check if serialized to unserialize 
     * @access public
     * @param string $value
     * @param array $result
     * @return bool
     */
    function unserialize(string $value, &$result = null) : bool
    {
        if (!is_string($value))
        {
            return false;
        }

        if ($value === 'b:0;')
        {
            $result = false;
            return true;
        }

        $length = strlen($value);
        $end = '';

        switch ($value[0])
        {
            case 's':
            {
                if ($value[$length - 2] !== '"')
                {
                    return false;
                }
            }
            case 'b':
            case 'i':
            case 'd':
            {
                $end .= ';';
            }
            case 'a':
            case 'O':
            {
                $end .= '}';

                if ($value[1] !== ':')
                {
                    return false;
                }

                switch ($value[2])
                {
                    case 0:
                    case 1:
                    case 2:
                    case 3:
                    case 4:
                    case 5:
                    case 6:
                    case 7:
                    case 8:
                    case 9:
                    break;
                    default: return false;
                }
            } 
            case 'N':
            {
                $end .= ';';

                if ($value[$length - 1] !== $end[0])
                {
                    return false;
                }

                break;
            }
            default: return false;
        }

        if (($result = @unserialize($value)) === false)
        {
            $result = null;
            return false;
        }

        return true;
    }
    
    /**
     * @name __construct
     * @description class constructor
     * @access private
     * @return Objects
     */
    private function __construct() {}

    /**
     * @name __clone
     * @description preventing cloning object
     * @access public
     * @return Objects
     */
    public function __clone()
    {
        return self::getInstance();  
    }
    
    /** 
     * @readwrite
     * @access private 
     * @var Objects
     */ 
    private static $_instance;
}


