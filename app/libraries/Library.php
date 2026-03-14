<?php declare(strict_types=1); namespace IR\App\Libraries; if (!defined('IR_START')) exit('<pre>No direct script access allowed</pre>');
/**
 * @framework       iResponse Framework 
 * @version         1.0
 * @author          Amine Idrissi <contact@iresponse.tech>
 * @date            2024
 * @name            Library.php	
 */

# core 
use IR\Core\Base as Base;

/**
 * @name Library
 * @description Library
 */
class Library extends Base
{
    /**
     * @name get
     * @description construct an API class from the prefix
     * @param string $prefix
     * @return Api
     */
    public static function get(string $prefix,$parameters = [])
    {
        $class = FW_ABBR . ANS . 'App' . ANS . 'Libraries' . ANS . ucfirst($prefix); 
        
        if(class_exists($class))
        {
            return new $class($parameters);
        }

        return null;
    }
}


