<?php declare(strict_types=1); namespace IR\Orm; if (!defined('IR_START')) exit('<pre>No direct script access allowed</pre>');
/**
 * @framework       iResponse Framework 
 * @version         1.0
 * @author          Amine Idrissi <contact@iresponse.tech>
 * @date            2024
 * @name            Table.php	
 */

# php defaults
use \PDO;

# utilities
use IR\Utils\Types\Arrays as Arrays;

# exceptions
use IR\Exceptions\Types\DatabaseException as DatabaseException;

/**
 * @name Table
 * @description orm tables class
 */
class Table
{
    /**
     * @name create
     * @description creates a new table
     * @access static 
     * @return mixed
     * @throws DatabaseException
     */
    public static function create(string $database,string $table,array $columns,string $schema = 'public')
    {
        if($table != '' && count($columns) > 0)
        {
            $lines = [];
            $indecies = [];
            $primaries = [];
            $sequence = '';
            $template = "CREATE TABLE IF NOT EXISTS %s (\n%s,\n%s\n);";

            foreach ($columns as $column) 
            {
                $name = $column["name"];
                $indexed = $column["indexed"];
                $type = $column["type"];
                $length = $column["length"];
                $nullable = trim($column["nullable"]) == "true" ? " DEFAULT NULL " : " NOT NULL ";
                $unique = $column["unique"] ? " UNIQUE " : "";
                
                switch ($type) 
                {
                    case ActiveRecord::INT: 
                    {
                        $line = "{$name} integer {$nullable} {$unique}";

                        if(Arrays::getInstance()->get(Database::retreive($database)->getProperties(),'driver') == 'pgsql')
                        {
                            if ($column["primary"]) 
                            {
                                $primaries[] = "CONSTRAINT c_pk_{$name}_{$table} PRIMARY KEY({$name}) ";
                            }

                            if ($column["autoincrement"]) 
                            {
                                $sequence = "seq_{$name}_{$table}";
                            }
                        }
                        else
                        {
                            if ($column["primary"]) 
                            {
                                $line .= " PRIMARY KEY ";
                            }

                            if ($column["autoincrement"]) 
                            {
                                $line .= " AUTO_INCREMENT ";
                            }
                        }
                        
                        $lines[] = $line;
                        break;
                    }
                    case ActiveRecord::DECIMAL: 
                    {
                        $lines[] = "{$name} decimal {$nullable} {$unique}";
                        break;
                    }

                    case ActiveRecord::TEXT: 
                    {
                        if ($length !== null && $length <= 255) 
                        {
                            $lines[] = "{$name} varchar({$length}) {$nullable} {$unique}";
                        } 
                        else 
                        {
                            $lines[] = "{$name} text {$nullable} {$unique}";
                        }
                        break;
                    }
                    case ActiveRecord::BOOL: 
                    {
                        $lines[] = "{$name} boolean ";
                        break;
                    }
                    case ActiveRecord::TIME_STAMP: 
                    {
                        $lines[] = "{$name} timestamp {$nullable} {$unique}";
                        break;
                    }
                    case ActiveRecord::DATE: 
                    {
                        $lines[] = "{$name} date {$nullable} {$unique}";
                        break;
                    }
                }
                
                # indecises
                if($indexed == 'true')
                {
                    $indecies[] = $name;
                }
            }

            # create the table query
            $query = sprintf($template,"{$schema}.{$table}", join(",\n", $lines), join(",\n", $primaries), Database::retreive($database)->getEngine(), Database::retreive($database)->getCharset());

            # creating a new table
            Database::retreive($database)->execute($query,Connector::AFFECTED_ROWS);
                
            # create indecises
            if(count($indecies))
            {
                Database::retreive($database)->execute("CREATE INDEX IF NOT EXISTS {$schema}_{$table}_idx ON {$schema}.{$table} USING btree (" . implode(',',$indecies) . ") TABLESPACE pg_default",Connector::AFFECTED_ROWS);
                Database::retreive($database)->execute("ALTER TABLE {$schema}.{$table} CLUSTER ON {$schema}_{$table}_idx",Connector::AFFECTED_ROWS);
            }
            
            # sequence section
            Sequence::create($database,$sequence, $schema);
        }
        else
        {
            throw new DatabaseException("Invalid arguments to create the table !");
        }
    }

    /**
     * @name drop
     * @description drops a table
     * @access static 
     * @return mixed
     * @throws DatabaseException
     */
    public static function drop(string $database,string $table,string $schema = 'public')
    {
        if($table != '')
        {
            Database::retreive($database)->execute("DROP TABLE IF EXISTS {$schema}.{$table}",Connector::AFFECTED_ROWS);
            Database::retreive($database)->execute("DROP SEQUENCE IF EXISTS {$schema}.seq_id_{$table}",Connector::AFFECTED_ROWS);
        }
    }
    
    /**
     * @name reindex
     * @description reindexes a table
     * @access static 
     * @return mixed
     * @throws DatabaseException
     */
    public static function reindex(string $database,string $table,string $schema = 'public')
    {
        if($table != '')
        {
            Database::retreive($database)->execute("REINDEX TABLE {$schema}.{$table}",Connector::AFFECTED_ROWS);  
        }
    }
    
    /**
     * @name rename
     * @description renames a table
     * @access static 
     * @return mixed
     * @throws DatabaseException
     */
    public static function rename(string $database,string $table,string $name,string $schema = 'public')
    {
        Database::retreive($database)->execute("ALTER TABLE {$schema}.{$table} RENAME TO {$name}",Connector::AFFECTED_ROWS);  
    }
    
    /**
     * @name empt
     * @description empties a table
     * @access static 
     * @return mixed
     * @throws DatabaseException
     */
    public static function empt(string $database,string $table,string $schema = 'public')
    {
        Database::retreive($database)->execute("DELETE FROM {$schema}.{$table}",Connector::AFFECTED_ROWS);  
    }
    
    /**
     * @name columns
     * @description gets the list of columns of a table
     * @access static 
     * @return array
     * @throws DatabaseException
     */
    public static function columns(string $database,string $table,string $schema = 'public',$primary = 'id') : array
    {
        $columns = [];
        
        # check if the parameters are correct
        if($table == '' || $schema == '')
        {
            throw new DatabaseException('You need to provide a table name and a schema name !');
        }

        # check if the table exists
        if(!Table::exists($table, $schema))
        {
            throw new DatabaseException('Table or Schema not found !');
        }
           
        $result = Database::retreive($database)->execute("SELECT * FROM information_schema.columns WHERE table_schema = '{$schema}' AND table_name = '{$table}' ORDER BY ordinal_position ASC",Connector::FETCH_ALL); 
        
        if(count($result))
        {
            foreach ($result as $row) 
            {
                $columns[] = [
                    'name' => '_' . strtolower($row['column_name']),
                    'is_primary' => strtolower($row['column_name']) == strtolower($primary),
                    'is_auto_increment' => strtolower($row['column_name']) == strtolower($primary),
                    'type' => strtolower($row['data_type']) == 'character varying' ? 'text' : strtolower($row['data_type']),
                    'lenght' => strval($row['character_maximum_length']),
                    'is_nullable' => $row['is_nullable'] == 'YES' ? 'true' : 'false'
                ];
            }
        }

        return $columns;
    }
    
    /**
     * @name empt
     * @description empties a table
     * @access static 
     * @return mixed
     * @throws DatabaseException
     */
    public static function count(string $database,string $table,string $schema = 'public') : int
    {
        $count = 0;
        
        if($table != '')
        {
            $result = Database::retreive($database)->execute("SELECT COUNT(1) FROM {$schema}.{$table}",Connector::FETCH_FIRST); 
            
            if(count($result))
            {
                if(key_exists('count',$result))
                {
                    $count = intval($result['count']);
                }
            }
        }
        
        return (int) $count;
    }
    
    /**
     * @name max
     * @description get max from a table
     * @access static 
     * @return mixed
     * @throws DatabaseException
     */
    public static function max(string $database,string $table,string $schema = 'public',$column = 'id') : int
    {
        $max = 0;
        
        if($table != '')
        {
            $result = Database::retreive($database)->execute("SELECT MAX($column) AS max FROM {$schema}.{$table}",Connector::FETCH_FIRST); 
            
            if(count($result))
            {
                if(key_exists('max',$result))
                {
                    $max = intval($result['max']);
                }
            }
        }
        
        return (int) $max;
    }
    
    /**
     * @name min
     * @description get min from a table
     * @access static 
     * @return mixed
     * @throws DatabaseException
     */
    public static function min(string $database,string $table,string $schema = 'public',$column = 'id') : int
    {
        $min = 0;
        
        if($table != '')
        {
            $result = Database::retreive($database)->execute("SELECT MIN($column) AS min FROM {$schema}.{$table}",Connector::FETCH_FIRST); 
            
            if(count($result))
            {
                if(key_exists('min',$result))
                {
                    $min = intval($result['min']);
                }
            }
        }
        
        return (int) $min;
    }
    
    /**
     * @name sum
     * @description get sum from a table
     * @access static 
     * @return mixed
     * @throws DatabaseException
     */
    public static function sum(string $database,string $table,string $schema = 'public',$column = 'id') : int
    {
        $sum = 0;
        
        if($table != '')
        {
            $result = Database::retreive($database)->execute("SELECT SUM($column) AS sum FROM {$schema}.{$table}",Connector::FETCH_FIRST); 
            
            if(count($result))
            {
                if(key_exists('sum',$result))
                {
                    $sum = intval($result['sum']);
                }
            }
        }
        
        return (int) $sum;
    }
    
    /**
     * @name average
     * @description get average from a table
     * @access static 
     * @return mixed
     * @throws DatabaseException
     */
    public static function average(string $database,string $table,string $schema = 'public',$column = 'id') : int
    {
        $average = 0;
        
        if($table != '')
        {
            $result = Database::retreive($database)->execute("SELECT AVG($column) AS average FROM {$schema}.{$table}",Connector::FETCH_FIRST); 
            
            if(count($result))
            {
                if(key_exists('average',$result))
                {
                    $average = intval($result['average']);
                }
            }
        }
        
        return (int) $average;
    }
    
    /**
     * @name exists
     * @description checks if table exists in the database
     * @access static
     * @param string $table must specify table .
     * @param string $schema must specify schema .
     * @return boolean
     */
    public static function exists(string $database,string $table,string $schema = 'public') : bool
    {  
        $result = false;
        $sql = "";

        $type = Arrays::getInstance()->get(Database::retreive($database)->getProperties(),'driver');
        
        if($type == 'mysql')
        {
            $sql = "SELECT id AS exists FROM information_schema.tables WHERE table_schema = '$schema' AND table_name = '$table' LIMIT 1;";
        }
        elseif($type == 'pgsql')
        {
            $sql = "SELECT EXISTS (SELECT 1 FROM pg_catalog.pg_class c JOIN pg_catalog.pg_namespace n ON n.oid = c.relnamespace WHERE  n.nspname = '$schema' AND c.relname = '$table' AND c.relkind = 'r');";
        }

        $checkResults = Database::retreive($database)->execute($sql,Connector::FETCH_FIRST,PDO::FETCH_ASSOC);

        if(count($checkResults))
        {
            if(key_exists('exists',$checkResults))
            {
                if($checkResults['exists'] == '1')
                {
                    $result = true;
                }
            }
        }

        return (bool) $result;
    }

    /**
     * @name available
     * @description retrieves all tables in the database
     * @access static
     * @param string $schema must specify schema for get tables.
     * @return mixed
     */
    public static function available(string $database,string $schema = '') : array
    {  
        $result = [];

        $type = Arrays::getInstance()->get(Database::retreive($database)->getProperties(),'driver');
        $name = Database::retreive($database)->getName();

        if($type == 'mysql')
        {
            if($name != "") $complete = " FROM {$name}";
            $sql = "SHOW tables ".$complete.";";
        }
        elseif($type == 'pgsql')
        {
            if($schema != null && $schema != '')
            {
                $sql = "SELECT relname AS name FROM pg_stat_user_tables WHERE schemaname = '$schema' ORDER BY name ASC;";
            }
            else
            {
                $sql = "SELECT schemaname || '.' || relname AS name FROM pg_stat_user_tables ORDER BY name ASC;";
            }
        }

       $tables = Database::retreive($database)->execute($sql,Connector::FETCH_ALL,PDO::FETCH_ASSOC);

        if(count($tables))
        {
            foreach ($tables as $table) 
            {
                if(count($table))
                {
                    $result[] = $table['name'];
                }
            }
        }

        return $result;
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


