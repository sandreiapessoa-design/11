<?php declare(strict_types=1); namespace IR\Core; if (!defined('IR_START')) exit('<pre>No direct script access allowed</pre>');
/**
 * @framework       iResponse Framework 
 * @version         1.0
 * @author          Amine Idrissi <contact@iresponse.tech>
 * @date            2024
 * @name            Utils.php	
 */

# utilities 
use IR\Utils\Types\Arrays as Arrays;
use IR\Utils\Types\Strings as Strings;
use IR\Utils\Types\Objects as Objects;
use IR\Utils\Types\XML as XML;

# os 
use IR\Utils\System\FileSystem as FileSystem;
use IR\Utils\System\Terminal as Terminal;

# security
use IR\Utils\Security\Encryptor as Encryptor;

# output 
use IR\Utils\Output\Printer as Printer;

# compression
use IR\Utils\Compression\Zip as Zip;

# meta
use IR\Utils\Meta\Inspector as Inspector;

# web
use IR\Utils\Web\Domains as Domains;

/**
 * @name Utils
 * @description core utils class
 */
class Utils
{
    /**
     * @name getInstance
     * @description singleton access to constructor
     * @access public | static
     * @return Utils
     */
    public static function getInstance() : Utils
    {
        if(self::$_instance == null)
        {
            self::$_instance = new Utils();
        }
        
        return self::$_instance;
    }

    /**
     * @name __construct
     * @description class constructor
     * @access private
     * @return Utils
     */
    private function __construct() 
    {
        $this->arrays = Arrays::getInstance();
        $this->strings = Strings::getInstance();
        $this->objects = Objects::getInstance();
        $this->xml = XML::getInstance();
        
        $this->fileSystem = FileSystem::getInstance();
        $this->terminal = Terminal::getInstance();
        
        $this->encryptor = Encryptor::getInstance();
        $this->printer = Printer::getInstance();
        $this->zip = Zip::getInstance();
        $this->inspector = Inspector::getInstance();
        $this->domains = Domains::getInstance();
    }

    /**
     * @name __clone
     * @description preventing cloning object
     * @access public
     * @return Utils
     */
    public function __clone()
    {
        return (self::$_instance != null) ? self::$_instance : new Utils();  
    }
    
    /** 
     * @readwrite
     * @access private 
     * @var Utils
     */ 
    private static $_instance;
    
    /** 
     * @read
     * @access public 
     * @var Arrays
     */ 
    public $arrays; 
    
    /** 
     * @read
     * @access public 
     * @var Strings
     */ 
    public $strings; 
    
    /** 
     * @read
     * @access public 
     * @var Objects
     */ 
    public $objects; 
    
    /** 
     * @read
     * @access public 
     * @var XML
     */ 
    public $xml; 
    
    /** 
     * @read
     * @access public 
     * @var FileSystem
     */ 
    public $fileSystem;
    
    /** 
     * @read
     * @access public 
     * @var Terminal
     */ 
    public $terminal;
    
    /** 
     * @read
     * @access public 
     * @var Encryptor
     */ 
    public $encryptor;
    
    /** 
     * @read
     * @access public 
     * @var Printer
     */ 
    public $printer;
    
    /** 
     * @read
     * @access public 
     * @var Zip
     */ 
    public $zip;
    
    /** 
     * @read
     * @access public 
     * @var Inspector
     */ 
    public $inspector;
    
    /** 
     * @read
     * @access public 
     * @var Domains
     */ 
    public $domains;
    
    /** 
     * @read
     * @access public 
     * @var HTML
     */ 
    public $html;
    
    /** 
     * @read
     * @access public 
     * @var LinksShortener
     */ 
    public $linksShortener;
}