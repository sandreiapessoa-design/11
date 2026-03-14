<?php declare(strict_types=1); namespace IR\Utils\System; if (!defined('IR_START')) exit('<pre>No direct script access allowed</pre>');
/**
 * @framework       iResponse Framework 
 * @version         1.0
 * @author          Amine Idrissi <contact@iresponse.tech>
 * @date            2024
 * @name            Terminal.php	
 */

# utilities
use IR\Utils\Types\Arrays as Arrays;
use IR\Utils\System\FileSystem as FileSystem;

/**
 * @name Terminal
 * @description objects utils class
 */
class Terminal
{
    /**
     * @name getInstance
     * @description singleton access to constructor
     * @access public | static
     * @return Terminal
     */
    public static function getInstance() : Terminal
    {
        if(self::$_instance == null)
        {
            self::$_instance = new Terminal();
        }
        
        return self::$_instance;
    }
    
    /**
     * @name cmd
     * @description executes a system command
     * @access public
     * @return array
     */
    public function cmd(string $command, int $return = Terminal::RETURN_OUTPUT,int $type = Terminal::STRING_OUTPUT) : array
    {
        $result = ['output' => '' , 'error' => ''];
        
        if(isset($command) && $command != '')
        {
            $descriptorspec = [
                    0 => ["pipe", "r"], 
                    1 => ["pipe", "w"],
                    2 => ["pipe", "w"],
            ];
            
            $pipes = [];
            $process = proc_open($command, $descriptorspec,$pipes, dirname(__FILE__), null);  

            if(is_resource($process))
            {
                if($return == Terminal::RETURN_OUTPUT)
                {
                    if($type == Terminal::STRING_OUTPUT)
                    {
                        $result['output'] = trim(stream_get_contents($pipes[1]));
                        $result['error'] = trim(stream_get_contents($pipes[2]));
                    }
                    else
                    {
                        $result['output'] = explode(PHP_EOL,trim(stream_get_contents($pipes[1])));
                        $result['error'] = explode(PHP_EOL,trim(stream_get_contents($pipes[2])));
                    }
                }
                
                # close all pipes
                fclose($pipes[1]);
                fclose($pipes[2]);

                # close the process
                proc_close($process);
            }
        }
        
        return $result;
    }

    /**
     * @name __construct
     * @description class constructor
     * @access private
     * @return Terminal
     */
    private function __construct() {}

    /**
     * @name __clone
     * @description preventing cloning object
     * @access public
     * @return Terminal
     */
    public function __clone()
    {
        return (self::$_instance != null) ? self::$_instance : new Terminal();  
    }
    
    /** 
     * @readwrite
     * @access private 
     * @var Terminal
     */ 
    private static $_instance;
    
    /** 
     * @read
     * @access protected 
     * @var integer
     */
    const RETURN_NOTHING = 0;
    
    /** 
     * @read
     * @access protected 
     * @var integer
     */
    const RETURN_OUTPUT = 1;
    
    /** 
     * @read
     * @access protected 
     * @var integer
     */
    const STRING_OUTPUT = 3;
    
    /** 
     * @read
     * @access protected 
     * @var integer
     */
    const ARRAY_OUTPUT = 4;
    
     /** 
     * @read
     * @access protected 
     * @var integer
     */
    const NULL_OUTPUT = 5;
}


