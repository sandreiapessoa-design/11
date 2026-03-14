<?php declare(strict_types=1); namespace IR\Orm; if (!defined('IR_START')) exit('<pre>No direct script access allowed</pre>');
/**
 * @framework       iResponse Framework 
 * @version         1.0
 * @author          Amine Idrissi <contact@iresponse.tech>
 * @date            2024
 * @name            Database.php	
 */

# core
use IR\Core\Registry as Registry;

# orm 
use IR\Orm\Connector as Connector;

# utilities 
use IR\Utils\Types\Strings as Strings;

# exceptions
use IR\Exceptions\Types\DatabaseException as DatabaseException;

/**
 * @name Database
 * @description orm database class
 */
class Database extends Connector
{
    /**
     * @name exists
     * @description checks if a database exists in the registry
     * @access public
     * @return bool
     */
    public static function exists(string $databaseKey) : bool
    {
        # removes spaces and special characters from the name 
        $databaseKey = Strings::getInstance()->removeAccents(Strings::getInstance()->trim($databaseKey));
        
        return Registry::getInstance()->contains($databaseKey) && Registry::getInstance()->get($databaseKey) != null && Registry::getInstance()->get($databaseKey) instanceof Database;
    }
    
    /**
     * @name retreive
     * @description retreives a database from the registry
     * @access public
     * @return Database
     */
    public static function retreive(string $databaseKey) : Database
    {
        # removes spaces and special characters from the name 
        $databaseKey = Strings::getInstance()->removeAccents(Strings::getInstance()->trim($databaseKey));
        
        # check if the database exists
        if(!self::exists($databaseKey))
        {
            throw new DatabaseException('Database : ' . $databaseKey . ' does not exist !');
        }

        return Registry::getInstance()->get($databaseKey);
    }
    
    /**
     * @name register
     * @description registers a database into the registry
     * @access public
     * @return 
     */
    public static function register(Database $database)
    {
        # check if the retreived object is a database object
        if($database == null || !$database instanceof Database)
        {
            throw new DatabaseException('The object : ' . $database->getKey() . ' is not a database object !');
        }

        # removes spaces and special characters from the name 
        $databaseKey = Strings::getInstance()->removeAccents(Strings::getInstance()->trim($database->getKey()));
        $database->setKey($databaseKey);
        
        # register the database 
        Registry::getInstance()->set($databaseKey,$database);
    }
    
    /**
     * @name query
     * @description prepares a new query builder
     * @access public
     * @return 
     */
    public function query() : Query
    {
        return new Query([
            'database' => $this
        ]);
    }
    
    /** 
     * @readwrite
     * @access protected 
     * @var string
     */
    protected $_key;
    
    /** 
     * @readwrite
     * @access protected 
     * @var string
     */
    protected $_name;
    
    /** 
     * @readwrite
     * @access protected 
     * @var string
     */
    protected $_host;
    
    /** 
     * @readwrite
     * @access protected 
     * @var integer
     */
    protected $_port;
    
    /** 
     * @readwrite
     * @access protected 
     * @var string
     */
    protected $_username;
    
    /** 
     * @readwrite
     * @access protected 
     * @var string
     */
    protected $_password;
    
    /**
     * @readwrite
     * @access protected 
     * @var string
     */
    protected $_driver = "mysql";
    
    /**
     * @readwrite
     * @access protected 
     * @var string
     */
    protected $_charset = "utf8";

    /** 
     * @readwrite
     * @access protected 
     * @var string
     */
    protected $_engine = "InnoDB";
}


