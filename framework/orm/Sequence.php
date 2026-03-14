<?php declare(strict_types=1); namespace IR\Orm; if (!defined('IR_START')) exit('<pre>No direct script access allowed</pre>');
/**
 * @framework       iResponse Framework 
 * @version         1.0
 * @author          Amine Idrissi <contact@iresponse.tech>
 * @date            2024
 * @name            Sequence.php	
 */

# exceptions
use IR\Exceptions\Types\DatabaseException as DatabaseException;

/**
 * @name Schema
 * @description orm sequences class
 */
class Sequence
{
    /**
     * @name create
     * @description creates a new sequence
     * @access static 
     * @return mixed
     * @throws DatabaseException
     */
    public static function create(string $database,string $sequence,string $schema = 'public')
    {
        Database::retreive($database)->execute("CREATE SEQUENCE IF NOT EXISTS {$schema}.{$sequence} START 1",Connector::AFFECTED_ROWS);
    }
    
    /**
     * @name drop
     * @description drops a sequence
     * @access static 
     * @return mixed
     * @throws DatabaseException
     */
    public static function drop(string $database,string $sequence,string $schema = 'public')
    {
        Database::retreive($database)->execute("DROP SEQUENCE IF EXISTS {$schema}.{$sequence} CASCADE",Connector::AFFECTED_ROWS);
    }
    
    /**
     * @name rename
     * @description renames a sequence
     * @access static 
     * @return mixed
     * @throws DatabaseException
     */
    public static function rename(string $database,string $sequence,string $name,string $schema = 'public') 
    {
        Database::retreive($database)->execute("ALTER SEQUENCE {$schema}.{$sequence} RENAME TO {$schema}.{$name}",Connector::AFFECTED_ROWS);   
    }
    
    /**
     * @name reset
     * @description resets a sequence
     * @access static 
     * @return mixed
     * @throws DatabaseException
     */
    public static function reset(string $database,string $sequence,string $schema = 'public')
    {
        Database::retreive($database)->execute("ALTER SEQUENCE IF EXISTS {$schema}.{$sequence} RESTART WITH 1",Connector::AFFECTED_ROWS);
    }
    
    /**
     * @name set
     * @description sets a value to a sequence
     * @access static 
     * @return mixed
     * @throws DatabaseException
     */
    public static function incrementBy(string $database,string $sequence,string $schema = 'public',int $increment = 1)
    {
        Database::retreive($database)->execute("ALTER SEQUENCE IF EXISTS {$schema}.{$sequence} INCREMENT BY $increment",Connector::AFFECTED_ROWS);
    }
    
    /**
     * @name getCurrentValue
     * @description gets the current value of a sequence
     * @access static 
     * @return mixed
     * @throws DatabaseException
     */
    public static function getCurrentValue(string $database,string $sequence,string $table,string $schema = 'public') : int
    {
        $currVal = 0;
        $result = Database::retreive($database)->execute("SELECT {$schema}.{$sequence}.CURRVAL as currval FROM {$schema}.{$table};",Connector::FETCH_FIRST);
        
        if(count($result))
        {
            if(key_exists('currval',$result))
            {
                $currVal = intval($result['currval']);
            }
        }
        
        return intval($currVal);
    }
    
    /**
     * @name getNextValue
     * @description gets the next value of a sequence
     * @access static 
     * @return mixed
     * @throws DatabaseException
     */
    public static function getNextValue(string $database,string $table,string $schema = 'public') : int
    {
        $nextval = 0;
        $result = Database::retreive($database)->execute("SELECT NEXTVAL('{$schema}.seq_id_{$table}')",Connector::FETCH_FIRST);

        if(count($result))
        {
            if(key_exists('nextval',$result))
            {
                $nextval = intval($result['nextval']);
            }
        }
        
        return intval($nextval);
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


