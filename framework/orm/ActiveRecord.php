<?php declare(strict_types=1); namespace IR\Orm; if (!defined('IR_START')) exit('<pre>No direct script access allowed</pre>');
/**
 * @framework       iResponse Framework 
 * @version         1.0
 * @author          Amine Idrissi <contact@iresponse.tech>
 * @date            2024
 * @name            ActiveRecord.php	
 */

# core 
use IR\Core\Base as Base;

# utilities
use IR\Utils\Meta\Inspector as Inspector;
use IR\Utils\System\FileSystem as FileSystem;
use IR\Utils\Types\Strings as Strings;
use IR\Utils\Types\Arrays as Arrays;

# logging
use IR\Logs\Logger as Logger;

# exceptions
use IR\Exceptions\Types\SystemException as SystemException;
use IR\Exceptions\Types\DatabaseException as DatabaseException;

/**
 * @name ActiveRecord
 * @description orm ActiveRecord class
 */
class ActiveRecord extends Base
{
    /**
     * @name __construct
     * @description the class constructor
     * @access public
     * @param array $options
     * @return ActiveRecord
     * @throws SystemException
     */
    public function __construct(array $options = [], bool $load = false)
    {
        # calling super constructor
        parent::__construct($options);

        # inistialize columns 
        if(empty($this->_columns)) 
        {
            $primaries = 0;
            $columns = [];
            $class = get_class($this);
            
            $properties = Inspector::getInstance()->classProperties($class);
            
            $first = function($array, $key) 
            {
                if (!empty($array[$key]) && sizeof($array[$key]) == 1) 
                {
                    return $array[$key][0];
                }
                
                return null;
            };
            
            foreach ($properties as $property) 
            {
                $propertyMeta = Inspector::getInstance()->propertiesMeta($class,$property);
                
                if (!empty($propertyMeta["@column"])) 
                {
                    $name = preg_replace("#^_#", "", $property);
                    $primary = !empty($propertyMeta["@primary"]);
                    $autoIncrement = !empty($propertyMeta["@autoincrement"]);
                    $type = $first($propertyMeta, "@type");
                    $length = $first($propertyMeta,"@length");
                    $nullable = $first($propertyMeta, "@nullable");
                    $nullable = !empty($nullable) ? $nullable : false;
                    $indexed = !empty($propertyMeta["@indexed"]);
                    $unique = !empty($propertyMeta["@unique"]);
                    $readwrite = !empty($propertyMeta["@readwrite"]);
                    $read = !empty($propertyMeta["@read"]) || $readwrite;
                    $write = !empty($propertyMeta["@write"]) || $readwrite;
                    
                    if (!ActiveRecord::isTypeSupported($type))
                    {
                        throw new SystemException("{$type} is not a valid type");
                    }
                    
                    if ($primary) 
                    {
                        $primaries++;
                    }
                    
                    $columns[$name] = [
                        "raw" => $property,
                        "name" => $name,
                        "primary" => $primary,
                        "autoincrement" => $autoIncrement,
                        "indexed" => $indexed,
                        "unique" => $unique,
                        "type" => $type,
                        "nullable" => $nullable,
                        "length" => $length,
                        "read" => $read,
                        "write" => $write
                    ];
                }
            }
            
            if ($primaries !== 1) 
            {
                throw new SystemException("{$class} must have one @primary column !");
            }
            
            $this->_columns = $columns;
            
            if(count($options) && $load == true)
            {
                $primaryName = Arrays::getInstance()->get($this->getPrimaryColumn(),'name');
                
                if(key_exists($primaryName,$options))
                {
                    $this->load($options[$primaryName]);
                }                
            }
        }
    }
    
    /**
     * @name first
     * @description returns the first matched record
     * @access static
     * @param integer $format
     * @param array $where
     * @param array $fields
     * @param string $order
     * @param string $direction
     * @return mixed
     */
    public static function first(int $format = ActiveRecord::FETCH_ARRAY, array $where = [], array $fields = ["*"],string $order = '', string $direction = 'ASC')
    {
        $result = [];
        $activeRecord = new static();
        
        $schema = $activeRecord->getSchema() != null ? $activeRecord->getSchema() . "." :  null;
        $query = Database::retreive($activeRecord->getDatabaseKey())->query()->from($schema . $activeRecord->getTable(), $fields);

        if(count($where) > 1)
        {
            $query->where($where[0],$where[1]);
        }

        if ($order != '')  
        {
            $query->order($order, $direction);
        }
        
        # retrieving the data
        $result = $query->first();

        if(count($result))
        {
            foreach ($result as $column => &$value)
            {
                if($column != null)
                {
                    $clm = $activeRecord->getColumn($column);
                    
                    if(is_array($clm) && count($clm))
                    {
                        if(Arrays::getInstance()->get($clm,'type') == 'integer')
                        {
                            $value = ($value == null || $value == '') ? 0 : intval($value);
                        }
                        elseif(Arrays::getInstance()->get($clm,'type') == 'boolean')
                        {
                            $value =  ($value == null || $value == '') ? 'f' : 't';
                        }
                    }
                }
            }

            if($format == ActiveRecord::FETCH_OBJECT)
            {
                $class = get_class($activeRecord);
                $result = new $class($result);
            }
        }
 
        return $result;
    }
    
    /**
     * @name all
     * @description creates a query, taking into account the various filters and ﬂags, to return all matching records.
     * @access static
     * @param integer $format
     * @param array $where
     * @param array $fields
     * @param string $order
     * @param string $direction
     * @param integer $limit
     * @param integer $offset
     * @return mixed
     */
    public static function all(int $format = ActiveRecord::FETCH_ARRAY, array $where = [], array $fields = ["*"], string $order = '', string $direction = 'ASC', int $limit = 0, int $offset = 0) 
    {
        $result = [];
        $activeRecord = new static();
        
        $schema = $activeRecord->getSchema() != null ? $activeRecord->getSchema() . "." :  null;
        $query = Database::retreive($activeRecord->getDatabaseKey())->query()->from($schema . $activeRecord->getTable(), $fields);

        if(count($where) > 1)
        {
            $query->where($where[0],$where[1]);
        }

        if ($order != '') 
        {
            $query->order($order, $direction);
        }

        if ($limit > 0) 
        {
            $query->limit($limit, $offset);
        }
        
        # retrieving the data
        $result = $query->all();
        
        if(count($result))
        {
            foreach ($result as &$row)
            {
                if(count($row))
                {
                    foreach ($row as $column => &$value)
                    {
                        if($column != null)
                        {
                            $clm = $activeRecord->getColumn($column);
                    
                            if(is_array($clm) && count($clm))
                            {
                                if(Arrays::getInstance()->get($clm,'type') == 'integer')
                                {
                                    $value = ($value == null || $value == '') ? 0 : intval($value);
                                }
                                elseif(Arrays::getInstance()->get($clm,'type') == 'boolean')
                                {
                                    $value =  ($value == null || $value == '') ? 'f' : 't';
                                }
                            }
                        }
                    }
                }
            }
            
            if($format == ActiveRecord::FETCH_OBJECT)
            {
                $class = get_class($activeRecord);
                
                for ($index = 0; $index < count($result); $index++) 
                {
                    $result[$index] = new $class($result[$index]);
                }
            }
        }

        return $result;
    }
    
    
    
    /**
     * @name count
     * @description returns a count of the matched records. 
     * @access static
     * @param array $where
     * @return integer
     */
    public static function count(string $condition = '',$parameters = []) : int
    {
        $activeRecord = new static();
        $schema = $activeRecord->getSchema() != null ? $activeRecord->getSchema() . "." :  null;
        $query = Database::retreive($activeRecord->getDatabaseKey())->query()->from($schema . $activeRecord->getTable());

        if(!empty($condition))
        {
            $query->where($condition,$parameters);
        }

        $result = $query->count();
        return $result;
    }
    
    /**
     * @name insertRows 
     * @description insert a row of records base on the primary key
     * @access static
     * @return integer
     * @throws DatabaseException
     */
    public static function insertRows($rows = [],int $type = ActiveRecord::ARRAYS_ROWS) : array
    {
        $ids = [];
        
        # check if the retreived type is a supported type
        if($type != ActiveRecord::ARRAYS_ROWS && $type != ActiveRecord::OBJECTS_ROWS)
        {
            throw new DatabaseException('Please check that you have sent supported row type !');
        }
        
        # check if the retreived array is not empty
        if($rows == null || count($rows) == 0)
        {
            throw new DatabaseException('Please check that you have sent an array of data to be inserted !');
        }
        
        $index = 0;

        foreach ($rows as $row) 
        {
            try 
            {
                if($type == ActiveRecord::OBJECTS_ROWS)
                {
                    $activeRecord = $row;
                }
                elseif($type == ActiveRecord::ARRAYS_ROWS)
                {
                    $activeRecord = new static($row);
                }
                else
                {
                    throw new DatabaseException('The row number ' . $index . ' is not a correct row !');
                }
            
                $ids[] = $activeRecord->insert();
            } 
            catch (DatabaseException $e) 
            {
                Logger::getInstance()->error($e);
            }
            
            $index++;
        }
        
        return $ids;
    }
    
    /**
     * @name updateRows 
     * @description updates a row of records base on the primary key
     * @access static
     * @return integer
     * @throws DatabaseException
     */
    public static function updateRows($rows = [],int $type = ActiveRecord::ARRAYS_ROWS) : array
    {
        $ids = [];
        
        # check if the retreived type is a supported type
        if($type != ActiveRecord::ARRAYS_ROWS && $type != ActiveRecord::OBJECTS_ROWS)
        {
            throw new DatabaseException('Please check that you have sent supported row type !');
        }
        
        # check if the retreived array is not empty
        if($rows == null || count($rows) == 0)
        {
            throw new DatabaseException('Please check that you have sent an array of data to be updated !');
        }
        
        $index = 0;
        
        foreach ($rows as $row) 
        {
            try 
            {
                if($type == ActiveRecord::OBJECTS_ROWS)
                {
                    $activeRecord = $row;
                }
                elseif($type == ActiveRecord::ARRAYS_ROWS)
                {
                    $activeRecord = new static($row);
                }
                else
                {
                    throw new DatabaseException('The row number ' . $index . ' is not a correct row !');
                }
            
                $ids[] = $activeRecord->update();
            } 
            catch (DatabaseException $e) 
            {
                Logger::getInstance()->error($e);
            }
            
            $index++;
        }
        
        return $ids;
    }
    
    /**
     * @name deleteRows 
     * @description deletes a row of records base on the primary key
     * @access static
     * @return array
     * @throws DatabaseException
     */
    public static function deleteRows($ids = []) : array
    {
        $affectedRows = [];

        # check if the retreived array is not empty to delete all 
        if($ids == null || count($ids) == 0)
        {
            $activeRecord = new static();
            $schema = $activeRecord->getSchema() != null ? $activeRecord->getSchema() . "." :  null;
            $affectedRows[] = Database::retreive($activeRecord->getDatabaseKey())->query()->from($schema . $activeRecord->getTable())->delete(); 
        }
        else
        {
            foreach ($ids as $id) 
            {
                try 
                {
                    $activeRecord = new static();
                    $primary = $activeRecord->getPrimaryColumn();
                    $activeRecord->{$primary["raw"]} = $id;
                    $affectedRows[] = $activeRecord->delete();
                } 
                catch (DatabaseException $e) 
                {
                    Logger::getInstance()->error($e);
                }
            }
        }

        return $affectedRows;
    }
    
    /**
     * @name deleteWhere 
     * @description deletes a row of records base on a where condition
     * @access static
     * @param $where array
     * @return integer
     * @throws DatabaseException
     */
    public static function deleteWhere(string $condition,array $values) : int
    {
        if($condition != null && $condition != '' && is_array($values) || count($values) == 0)
        {
            $activeRecord = new static();
            $schema = $activeRecord->getSchema() != null ? $activeRecord->getSchema() . "." :  null;
            return Database::retreive($activeRecord->getDatabaseKey())->query()
                   ->from($schema . $activeRecord->getTable())->where($condition,$values)->delete();
        }
        
        return 0;
    }
    
    /**
     * @name load
     * @description loads a record if the primary column’s value has been provided
     * @access public
     * @return
     */
    public function load($primayValue = null) 
    {
        $primary = $this->getPrimaryColumn();
        
        if (isset($primary)) 
        { 
            # set a new value to the primary column
            if($primayValue != null)
            {
                $this->{$primary["raw"]} = $primayValue;
            }
            
            if (!empty($this->{$primary["raw"]})) 
            {
                $schema = $this->getSchema() != null ? "{$this->getSchema()}." :  '';
                $record = Database::retreive($this->getDatabaseKey())->query()->from($schema . $this->getTable())->where("{$primary["name"]} = ?",$this->{$primary["raw"]})->first();
                
                if ($record != null) 
                {
                    $keys = array_keys($record);

                    foreach ($keys as $key) 
                    {
                        if (!empty($record[$key])) 
                        {
                            if(Arrays::getInstance()->get($this->getColumn($key),'type') == 'integer')
                            {
                                $value = ($record[$key] == null || $record[$key] == '') ? 0 : intval($record[$key]);
                            }
                            elseif(Arrays::getInstance()->get($this->getColumn($key),'type') == 'boolean')
                            {
                                $value =  ($record[$key] == null || $record[$key] == '' || $record[$key] == false) ? 'false' : 'true';
                            }
                            else
                            {
                                $value = $record[$key];
                            }
                            
                            $this->{"_{$key}"} = $value;
                        }
                    }
                }
            }
        }
    }

    /**
     * @name insert 
     * @description creates a record base on the primary key
     * @access public
     * @return integer
     * @throws DatabaseException
     */
    public function insert() : int
    {
        $primary = $this->getPrimaryColumn();
        $columns = $this->getColumns();
        $schema = $this->getSchema() != null ? $this->getSchema() . "." :  null;
        $query = Database::retreive($this->getDatabaseKey())->query()->from($schema . $this->getTable());
        $data = [];
        $primaryValue = 0;
        
        # primary column section
        if(isset($primary))
        {
            if (!empty($this->{$primary["raw"]})) 
            {
                $primaryValue = intval($this->{$primary["raw"]});
                $data["{$primary["name"]}"] = intval($this->{$primary["raw"]});
            }
            else
            {
                $data["{$primary["name"]}"] = "nq[nextval('{$schema}seq_{$primary["name"]}_{$this->getTable()}')]";
            }
        }

        # other columns 
        foreach ($columns as $key => $column) 
        {
            if ($column != $primary && $column) 
            {
                if($this->{$column["raw"]} == null || $this->{$column["raw"]} == "")
                {
                    switch ($column['type']) 
                    {
                        case 'integer':
                        {
                            $data[$key] = '0';
                            break;
                        }
                        case 'boolean':
                        {
                            $data[$key] = 'f';
                            break;
                        }
                        case 'timestamp':
                        {
                            $data[$key] = 'nq[NULL]';
                            break;
                        }
                        default :
                        {
                            $data[$key] = '';
                        }
                    }
                }
                else
                {
                    $data[$key] = $this->{$column["raw"]};
                }
            }
        }
        
        if($primaryValue > 0)
        {
            $query->insert($data,Connector::AFFECTED_ROWS);
            $result = $primaryValue;
        }
        else
        {
            $result = $query->insert($data);
        }

        if ($result > 0) 
        {
            $this->{$primary["raw"]} = $result;
        }

        return intval($result);
    }
    
    /**
     * @name insert 
     * @description creates a record base on the primary key
     * @access public
     * @return integer
     * @throws DatabaseException
     */
    public function update() : int
    {
        $primary = $this->getPrimaryColumn();
        $columns = $this->getColumns();
        $schema = $this->getSchema() != null ? $this->getSchema() . "." :  null;
        $query = Database::retreive($this->getDatabaseKey())->query()->from($schema . $this->getTable());
        $data = [];

        # primary column section
        if(isset($primary))
        {
            if (empty($this->{$primary["raw"]})) 
            {
                throw new DatabaseException('You need to provide a primary value ( like id = 1 ) !');
            }
            else
            {
                $query->where("{$primary["name"]} = ?", $this->{$primary["raw"]});
            }
        }

        # other columns 
        foreach ($columns as $key => $column) 
        {
            if ($column != $primary && $column) 
            {
                $data[$key] = $this->{$column["raw"]};
            }
        }
        
        $result = $query->update($data);

        if ($result > 0) 
        {
            $this->{$primary["raw"]} = $result;
        }

        return intval($result);
    }
    
    /**
     * @name delete
     * @description creates a query object, only if the primary key property value is not empty, and executes the query’s delete() method.
     * @access public
     * @return integer
     */
    public function delete() : int
    {
        $primary = $this->getPrimaryColumn();
        $raw = $primary["raw"];
        $name = $primary["name"];
        
        if (!empty($this->$raw))
        {
            $schema = $this->getSchema() != null ? $this->getSchema() . "." :  null;
            return Database::retreive($this->getDatabaseKey())->query()->from($schema . $this->getTable())->where("{$name} = ?",$this->$raw)->delete();
        }
    }
    
    /**
     * @name load
     * @description loads a record if the primary column’s value has been provided
     * @access public
     * @return
     */
    public function unload() 
    {
        $columns = $this->getColumns();
        
        if(count($columns))
        {
            foreach ($columns as $column) 
            {
                $this->{$column["raw"]} = null;
            }
        }
    }
    
    /**
     * @name getColumn
     * @description gets a column by its name
     * @access public
     * @return array
     */
    public function getColumn($name) 
    {
        if (!empty($this->_columns[$name])) 
        {
            return $this->_columns[$name];
        }
        
        return null;
    }

    /**
     * @name getPrimaryColumn
     * @description gets the primary column
     * @access public
     * @return array
     */
    public function getPrimaryColumn() 
    {
        if (!isset($this->_primary)) 
        {
            $primary = NULL;
            
            foreach ($this->_columns as $column) 
            {
                if ($column["primary"]) 
                {
                    $primary = $column;
                    break;
                }
            }
            
            $this->_primary = $primary;
        }
        
        return $this->_primary;
    }
    
    /**
     * @name sync
     * @description syncs with the database
     * @access static
     * @return integer
     * @throws DatabaseException
     */
    public static function sync(int $type = ActiveRecord::CREATE_TABLE,string $database = '',string $table = '',string $schema = '') : bool
    {
        $result = false;
        
        # create class case 
        if($type == ActiveRecord::CREATE_CLASS)
        {
            # check if the parameters are correct
            if($database == '' || $table == '' || $schema == '')
            {
                throw new DatabaseException('You need to provide a table name and a schema name !');
            }
            
            # check if the table exists
            if(!Table::exists($database,$table, $schema))
            {
                throw new DatabaseException('Table or Schema not found !');
            }
            
            $columns = Table::columns($database,$table, $schema);
            
            if(count($columns) == 0)
            {
                throw new DatabaseException('Table columns not found !');
            }
            
            $properties = '';
            
            foreach ($columns as $column) 
            {
                $tab = '    ';
                $properties .= $tab . '/**' . PHP_EOL;
                $properties .= $tab . ' * @column' . PHP_EOL;
                $properties .= $tab . ' * @readwrite' . PHP_EOL;
                
                if($column['is_primary'] == true)
                {
                    $properties .= $tab . ' * @primary' . PHP_EOL;
                }
                
                if($column['is_auto_increment'] == true)
                {
                    $properties .= $tab . ' * @autoincrement' . PHP_EOL;
                }
                
                $properties .= $tab . ' * @type ' . $column['type'] . PHP_EOL;
                $properties .= $tab . ' * @nullable ' . strval($column['is_nullable']) . PHP_EOL;
                $properties .= $tab . ' * @length ' . strval($column['length']) . PHP_EOL;
                $properties .= $tab . ' */' . PHP_EOL;
                $properties .= $tab . ' protected $' . $column['name'] . ';' . PHP_EOL . PHP_EOL;
            }
            
            if(!FileSystem::getInstance()->fileExists(RESSOURCES_PATH . DS . 'templates' . DS . 'database' . DS . 'active_record.tpl'))
            {
                throw new DatabaseException('Template file not found !');
            }
            
            $fileContent = FileSystem::getInstance()->readFile(RESSOURCES_PATH . DS . 'templates' . DS . 'database' . DS . 'active_record.tpl');
            $className = Strings::getInstance()->singular(str_replace(' ','',preg_replace( "/\r|\n/","",ucwords(str_replace('_',' ',$table)))));
            $classContent = str_replace(['ph[name]','ph[database]','ph[schema]','ph[table]','ph[columns]','ph[namespace]'],[$className,$database,$schema,$table,rtrim($properties,PHP_EOL . PHP_EOL),'ICB\App\Models\\' . ucfirst($schema)],$fileContent);
            
            # create the schema folder if it does not exist
            if(!FileSystem::getInstance()->fileExists(MODELS_PATH . DS . $schema))
            {
                FileSystem::getInstance()->createDir(MODELS_PATH . DS . $schema);
            }
            
            # write the class file
            FileSystem::getInstance()->writeFile(MODELS_PATH . DS . $schema . DS . $className . '.php' , $classContent); 
            $result = FileSystem::getInstance()->fileExists(MODELS_PATH . DS . $schema . DS . $className . '.php');
        }
        elseif($type == ActiveRecord::CREATE_TABLE)
        {
            $calledClass = get_called_class();
            
            if(isset($calledClass) && class_exists($calledClass))
            {
                $activeRecord = new $calledClass();
                $database = $database != '' ? $database : $activeRecord->getDatabaseKey();
                $table = $table != '' ? $table : $activeRecord->getTable();
                $schema = $schema != '' ? $schema :  $activeRecord->getSchema();
                Table::create($database,$table,$activeRecord->getColumns(),$schema);
                $result = Table::exists($database,$table, $schema);
            }
        }
        else
        {
            throw new DatabaseException('Unsupported sync type !');
        }
        
        return $result;
    }

    /**
     * @name isTypeSupported
     * @description checks if a given type is a supported type 
     * @access static
     * @return bool
     */
    public static function isTypeSupported(string $type) : bool
    {
        return $type == ActiveRecord::INT || $type == ActiveRecord::TEXT || $type == ActiveRecord::DECIMAL || $type == ActiveRecord::BOOL || $type == ActiveRecord::DATE || $type == ActiveRecord::TIME_STAMP;
    }

    /**
     * @readwrite
     * @access protected 
     * @var array
     */
    protected $_columns = [];

    /**
     * @readwrite
     * @access protected 
     * @var array
     */       
    protected $_primary;
    
    /** 
     * @read
     * @access protected 
     * @var string
     */
    const INT = 'integer';
    
    /** 
     * @read
     * @access protected 
     * @var string
     */
    const DECIMAL = 'decimal';
    
    /** 
     * @read
     * @access protected 
     * @var string
     */
    const TEXT = 'text';
    
    /** 
     * @read
     * @access protected 
     * @var string
     */
    const DATE = 'date';
    
    /** 
     * @read
     * @access protected 
     * @var string
     */
    const TIME_STAMP = 'timestamp';
    
    /** 
     * @read
     * @access protected 
     * @var string
     */
    const BOOL = 'boolean';
    
    /** 
     * @read
     * @access protected 
     * @var integer
     */
    const CREATE_TABLE = 0;
    
    /** 
     * @read
     * @access protected 
     * @var integer
     */
    const CREATE_CLASS = 1;
    
    /** 
     * @read
     * @access protected 
     * @var integer
     */
    const FETCH_ARRAY = 2;
    
    /** 
     * @read
     * @access protected 
     * @var integer
     */
    const FETCH_OBJECT = 3;
    
    /** 
     * @read
     * @access protected 
     * @var integer
     */
    const OBJECTS_ROWS = 4;
    
    /** 
     * @read
     * @access protected 
     * @var integer
     */
    const ARRAYS_ROWS = 5;
}


