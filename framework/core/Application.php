<?php declare(strict_types=1); namespace IR\Core; if (!defined('IR_START')) exit('<pre>No direct script access allowed</pre>');
/**
 * @framework       iResponse Framework 
 * @version         1.0
 * @author          Amine Idrissi <contact@iresponse.tech>
 * @date            2024
 * @name            Application.php	
 */

# php defaults
use DirectoryIterator;

# core 
use IR\Core\Base as Base;
use IR\Core\Registry as Registry;

# orm
use IR\Orm\Database as Database;   

# routing
use IR\Routing\Router as Router;

/**
 * @name Application
 * @description core application class
 */
class Application extends Base
{
    /**
     * @name addSetting
     * @description stores a setting of the application
     * @access public
     * @param string $key
     * @param mixed $config
     * @return
     */
    public function addSetting(string $key,$config) 
    {
        $this->settings[$key] = $config;
    }

    /**
     * @name getSetting
     * @description gets setting by a given key
     * @access public
     * @param string $key
     * @return mixed
     */
    public function getSetting(string $key) 
    {
        return isset($this->settings[$key]) ? $this->settings[$key] : NULL;
    }

    /**
     * @name getSettings
     * @description gets all settings
     * @access public
     * @return array
     */
    public function getSettings() : array
    {
        return $this->settings;
    }
    
    /**
     * @name load
     * @description load a configuration file
     * @access public
     * @return array
     */
    public function loadSettingsFromFile(string $name,string $filePath) 
    {
        if(file_exists($filePath))
        {
            $data = json_decode($this->utils->fileSystem->readFile($filePath),true);
  
            if(count($data))
            {
                $this->addSetting($name,$data[$name]);
            }
        }
    }
    
    /**
     * @name initialize
     * @description load all config defaults
     * @access public
     * @return array
     */
    public function initialize() 
    {
        # load app config files
        $this->loadSettingsFromFile('application',CONFIGS_PATH . DS . 'application.json');

        # load databases ( if any ) 
        $databasesDirectory = new DirectoryIterator(DATASOURCES_PATH);
        $dbKeys = [];
        
        foreach ($databasesDirectory as $fileinfo) 
        {
            if (!$fileinfo->isDot() && $fileinfo->getExtension() == 'json') 
            {
                $databaseParams = json_decode($this->utils->fileSystem->readFile($fileinfo->getPathname()),true);

                if(is_array($databaseParams) && count($databaseParams))
                {
                    # create a new database object
                    $database = new Database();
                    $database->setKey($this->utils->arrays->get($databaseParams,'key'));
                    $database->setDriver($this->utils->arrays->get($databaseParams,'driver'));
                    $database->setName($this->utils->arrays->get($databaseParams,'database'));
                    $database->setHost($this->utils->arrays->get($databaseParams,'host'));
                    $database->setPort($this->utils->arrays->get($databaseParams,'port'));
                    $database->setUsername($this->utils->arrays->get($databaseParams,'username'));
                    $database->setPassword($this->utils->arrays->get($databaseParams,'password'));
                    
                    # register the database into the main registry
                    Database::register($database);
                    
                    # insert it's key
                    $dbKeys[] = $database->getKey();
                }
            }
        } 

        if(count($dbKeys))
        {
            Registry::getInstance()->set('db-keys',$dbKeys);
        }
        
        # load the Router class and provide the url + extension
        $this->router = new Router();
        $this->router->url = $this->http->request->getRequestURL() != null ? $this->http->request->getRequestURL() : DEFAULT_CONTROLLER . RDS . DEFAULT_ACTION;
        $this->router->extension = $this->http->request->getRequestExtension() != null ? $this->http->request->getRequestExtension() : DEFAULT_EXTENSION;
    }
    
    /**
     * @name register
     * @description register it in the packager
     * @access public
     * @return array
     */
    public function register() 
    {
        # store the application itself in the registry
        Registry::getInstance()->set('application',$this);
    }

    /**
     * @name start
     * @description start the application
     * @access public
     * @return array
     */
    public function start() 
    {
        if($this->router != null)
        {
            $this->router->dispatch(); 
        }
    }
    
    /**
     * @name db
     * @description get database
     * @access public
     * @return Database
     */
    public function database($databaseKey) : Database
    {
        return Database::retreive($databaseKey);
    }
    
    /**
     * @name getCurrent
     * @description get current database
     * @access public
     * @return Application
     */
    public static function getCurrent()
    {
        return Registry::getInstance()->get('application');
    }
    
    /**
     * @name getCurrent
     * @description get current database
     * @access public
     * @return bool
     */
    public static function isValid() : bool
    {
        return self::getCurrent() != null && self::getCurrent() instanceof Application;
    }
    
    /**
     * @name __construct
     * @description class constructor
     * @access public
     * @return Application
     */
    public function __construct()
    {
        parent::__construct([]);
        
        $this->utils = Utils::getInstance();
        $this->http = Http::getInstance();
    }

    /** 
     * @readwrite
     * @access public 
     * @var array
     */ 
    protected $settings = [];

    /** 
     * @readwrite
     * @access public 
     * @var Router
     */ 
    public $router;

    /** 
     * @readwrite
     * @access public 
     * @var Utils
     */ 
    public $utils;
    
    /** 
     * @readwrite
     * @access public 
     * @var Http
     */ 
    public $http;
}


