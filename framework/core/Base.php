<?php declare(strict_types=1); namespace IR\Core; if (!defined('IR_START')) exit('<pre>No direct script access allowed</pre>');
/**
 * @framework       iResponse Framework 
 * @version         1.0
 * @author          Amine Idrissi <contact@iresponse.tech>
 * @date            2024
 * @name            Base.php	
 */

# utilities
use IR\Utils\Meta\Inspector as Inspector;
use IR\Utils\Types\Strings as Strings;

# exceptions
use IR\Exceptions\Types\SystemException as SystemException;
use IR\Exceptions\Types\MethodNotFoundException as MethodNotFoundException;

/**
 * @name Base
 * @description core base class
 */
class Base
{
    /**
     * @name __construct
     * @description the class constructor
     * @access public
     * @param array $options
     * @return Base
     */
    public function __construct(array $options = []) 
    {
        foreach ($options as $key => $value) 
        {
            if(property_exists($this,"_{$key}"))
            {
                $key = str_replace(' ','',preg_replace( "/\r|\n/","",ucwords(str_replace('_',' ',$key))));
                $method = "set{$key}";
                $this->$method($value);
            }
        }
    }
    
    /**
     * @name __call
     * @description plays the role of a generic getter and setter
     * @access public
     * @param string $method
     * @param array $arguments
     * @return mixed
     * @throws IRException
     */
    public function __call(string $method,array $arguments)
    {   
        # getting the subclass name
        $class = get_called_class();

        # getters case
        $getMatches = Strings::getInstance()->match($method,'^get([a-zA-Z0-9_]+)$');

        if (sizeof($getMatches) > 0) 
        {
            $normalized = lcfirst($getMatches[0]);
            $property = "_{$normalized}";
            $found = false;

            if(property_exists($class, $property))
            {
                $found = true;
            }
            else
            {
                $pieces = preg_split('/(?=[A-Z])/',ucwords($normalized));
                array_shift($pieces);
                $property = strtolower('_' . implode('_',$pieces));

                if (property_exists($class, $property))
                {
                    $found = true;
                }
            }

            if ($found == true) 
            {
                $meta = Inspector::getInstance()->propertiesMeta($class,$property);   

                if (empty($meta['@readwrite']) && empty($meta['@read'])) 
                {
                    throw new SystemException('You cannot get this property " ' . $property . ' " it\'s not readable in ' . $class);
                }

                if (isset($this->$property)) 
                {
                    return $this->$property;
                }
                else
                {
                    return null;
                } 
            }
        }

        # setters case
        $setMatches = Strings::getInstance()->match($method,'^set([a-zA-Z0-9_]+)$');

        if (sizeof($setMatches) > 0) 
        {
            $normalized = lcfirst($setMatches[0]);
            $property = "_{$normalized}";
            $found = false;

            if(property_exists($class, $property))
            {
                $found = true;
            }
            else
            {
                $pieces = preg_split('/(?=[A-Z])/',ucwords($normalized));
                array_shift($pieces);
                $property = strtolower('_' . implode('_',$pieces));

                if (property_exists($class, $property))
                {
                    $found = true;
                }
            }

            if ($found == true) 
            {
                $meta = Inspector::getInstance()->propertiesMeta($class,$property);

                if (empty($meta['@readwrite']) && empty($meta['@write'])) 
                {
                    throw new SystemException('You cannot write on this property " ' . $property . ' " it\'s readonly in ' . $class);
                }

                $this->$property = $arguments[0];
                return true;
            }
        }

        $class = (get_called_class() != '' && get_called_class() != false) ? 'in ' . get_called_class() : '';
        throw new MethodNotFoundException($method . ' : Method is not implemented ' . $class . ' !',500);
    }
}


