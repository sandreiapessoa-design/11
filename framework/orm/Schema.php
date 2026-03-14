<?php declare(strict_types=1); namespace IR\Orm; if (!defined('IR_START')) exit('<pre>No direct script access allowed</pre>');
/**
 * @framework       iResponse Framework 
 * @version         1.0
 * @author          Amine Idrissi <contact@iresponse.tech>
 * @date            2024
 * @name            Schema.php	
 */

# exceptions
use IR\Exceptions\Types\DatabaseException as DatabaseException;

/**
 * @name Schema
 * @description orm schemas class
 */
class Schema
{
    /**
     * @name create
     * @description creates a new schema
     * @access public 
     * @return mixed
     * @throws DatabaseException
     */
    public static function create(string $database,string $name)
    {
        Database::retreive($database)->execute("CREATE SCHEMA {$name}",Connector::AFFECTED_ROWS);
    }
    
    /**
     * @name drop
     * @description drops a schema
     * @access public 
     * @return mixed
     * @throws DatabaseException
     */
    public function drop(string $database,string $name)
    {
        Database::retreive($database)->execute("DROP SCHEMA IF EXISTS {$name} CASCADE",Connector::AFFECTED_ROWS);
    }

    /**
     * @name rename
     * @description renames a schema
     * @access static 
     * @return mixed
     * @throws DatabaseException
     */
    public static function rename(string $database,string $schema,string $name)
    {
        Database::retreive($database)->execute("ALTER SCHEMA {$schema} RENAME TO {$name}",Connector::AFFECTED_ROWS);  
    }
    
    /**
     * @name __construct
     * @description private constructor to prevent it being created directly
     * @access private
     * @return
     */ 
    private function __construct()  
    {}  

    /**
     * @name __clone
     * @description private clone to prevent it being cloned directly
     * @access private
     * @return
     */ 
    private function __clone()  
    {}
}


