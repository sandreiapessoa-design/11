<?php declare(strict_types=1); namespace IR\Utils\Output; if (!defined('IR_START')) exit('<pre>No direct script access allowed</pre>');
/**
 * @framework       iResponse Framework 
 * @version         1.0
 * @author          Amine Idrissi <contact@iresponse.tech>
 * @date            2024
 * @name            Printer.php	
 */

/**
 * @name Printer
 * @description objects utils class
 */
class Printer
{
    /**
     * @name getInstance
     * @description singleton access to constructor
     * @access public | static
     * @return Printer
     */
    public static function getInstance() : Printer
    {
        if(self::$_instance == null)
        {
            self::$_instance = new Printer();
        }
        
        return self::$_instance;
    }
    
    /**
     * @name printValue
     * @description prints Values in the screen
     * @access public
     * @param mixed $input
     * @param boolean $exit
     * @param string $wrapper
     * @param string $style
     * @return
     */  
    public function printValue($input,bool $exit = true,string $wrapper = 'pre',string $style = '')
    {
        echo $wrapper != null && $wrapper != '' ? '<'.$wrapper.' style="'.$style.'" >' : '';
        print_r($input);
        echo $wrapper != null && $wrapper != '' ? '</'.$wrapper.'>' : '';
        if($exit) exit;
    }  
    
    /**
     * @name __construct
     * @description class constructor
     * @access private
     * @return Printer
     */
    private function __construct() {}

    /**
     * @name __clone
     * @description preventing cloning object
     * @access public
     * @return Printer
     */
    public function __clone()
    {
        return (self::$_instance != null) ? self::$_instance : new Printer();  
    }
    
    /** 
     * @readwrite
     * @access private 
     * @var Printer
     */ 
    private static $_instance;
}


