<?php declare(strict_types=1); namespace IR\Orm; if (!defined('IR_START')) exit('<pre>No direct script access allowed</pre>');
/**
 * @framework       iResponse Framework 
 * @version         1.0
 * @author          Amine Idrissi <contact@iresponse.tech>
 * @date            2024
 * @name            Connector.php	
 */

# php defaults 
use \PDO;
use \PDOException;

# system 
use IR\Core\Base as Base;

# utilities 
use IR\Logs\Logger as Logger;

# utilities 
use IR\Utils\System\Terminal as Terminal;

# exceptions
use IR\Exceptions\Types\DatabaseException as DatabaseException;
use IR\Exceptions\Types\ArgumentException as ArgumentException;
use IR\Exceptions\Types\SQLException as SQLException;

/**
 * @name Connector
 * @description orm connector class
 */
abstract class Connector extends Base
{
    /**
     * @name connect
     * @description connects to the database
     * @access public
     * @return Connector
     * @throws DatabaseException , ArgumentException
     */
    public function connect()
    {
        if(!$this->isConnected())
        {
            if(in_array($this->getDriver(),$this->_supportedDrivers))
            {
                try
                {
                    switch($this->getDriver())
                    {    
                        case 'mysql':
                        {
                            $port = is_numeric($this->getPort()) ? ";port={$this->getPort()}" : '';
                            $this->_connection = new PDO('mysql:host='. $this->getHost() ."{$port};dbname=". $this->getName() , $this->getUsername(), $this->getPassword());
                            break;
                        }                          
                        case 'pgsql':
                        {
                            $port = is_numeric($this->getPort()) ? ";port={$this->getPort()}" : '';
                            $this->_connection = new PDO('pgsql:dbname='. $this->getName() ."{$port};host=". $this->getHost() , $this->getUsername(), $this->getPassword());
                            break;
                        } 
                        default:
                        {
                            $this->_connection = null;
                            break;
                        }
                    }

                    # check if the pdo object if created 
                    if($this->_connection == null)
                    {
                        $this->_lastErrorMessage = 'Database could not connect !';
                        throw new DatabaseException($this->_lastErrorMessage);
                    }
                    
                    if($this->_connection instanceof PDO)
                    {
                        $this->_connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    }
                }
                catch(PDOException $e)
                {
                    $this->_lastErrorMessage = $e->getMessage();
                    throw new DatabaseException($e->getMessage(),500,$e);
                }
            }
            else
            {
                $this->_lastErrorMessage = 'Database driver is not supported !';
                throw new DatabaseException($this->_lastErrorMessage);
            }
        }
    }
    
    /**
     * @name disconnect
     * @description disconnects from the database
     * @access public
     * @return Connector
     */
    public function disconnect()
    {
        if($this->isConnected())
        {
            $this->_connection = null;
            $this->_affectedRowsCount = 0;
            $this->_lastInsertedId = 0;
            $this->_lastErrorMessage = '';
        }
    }
    
    /**
     * @name isConnected
     * @description returns if it's connected or not
     * @access public
     * @return boolean
     */
    public function isConnected() : bool
    {
        if ($this->_connection != null && $this->_connection instanceof PDO)
        {
            try 
            {
                return (bool) $this->_connection->query('SELECT 1+1');
            } 
            catch (PDOException $e) 
            {
                Logger::getInstance()->error($e);
            }
        }

        return false;
    }
    
    /**
     * @name getProperties
     * @description returns the PDO connection properties
     * @access public
     * @return array
     */
    public function getProperties() : array
    {
        if ($this->isConnected())
        {
            return [
                'driver' => $this->_connection->getAttribute(PDO::ATTR_DRIVER_NAME),
                'server' => $this->_connection->getAttribute(PDO::ATTR_SERVER_VERSION),
                'status' => $this->_connection->getAttribute(PDO::ATTR_CONNECTION_STATUS),
                'client' => $this->_connection->getAttribute(PDO::ATTR_CLIENT_VERSION),
                'information' => $this->_connection->getAttribute(PDO::ATTR_SERVER_INFO)
            ];
        }

        return [];
    }
    
    /**
     * @name escape
     * @description escapes the provided value to make it safe for queries
     * @access public
     * @param string $value the string to be escaped
     * @return mixed
     * @throws DatabaseException
     */
    public function escape(string $value) : string
    {
        # check if we're still connected
        if ($this->isConnected())
        {
            return $this->_connection->quote($value);
        }
        
        return $value;
    }
    
    /**
     * @name execute
     * @description executes an SQL statement
     * @access public
     * @param string $sql
     * @param integer $type
     * @param integer $fetch
     * @return mixed 
     * @throws DatabaseException | SQLException
     */
    public function execute(string $sql,int $type = Connector::FETCH_ALL ,int $fetch = PDO::FETCH_ASSOC)
    {
        # checks if the database is connected
        if (!$this->isConnected())
        {
            $this->_lastErrorMessage = 'Database is not connected !';
            throw new DatabaseException($this->_lastErrorMessage);
        }
        
        $this->_lastErrorMessage = '';
        
        try
        {
            $statement = $this->_connection->query($sql);
            $this->_affectedRowsCount = $statement->rowCount();

            $result = FALSE;
            
            if($type == Connector::FETCH_ALL)
            {
                $result = $statement->fetchAll($fetch);
            }
            elseif($type == Connector::FETCH_FIRST)
            {
                $result = $statement->fetch($fetch);
            }
            elseif($type == Connector::AFFECTED_ROWS)
            {
                $result = $this->_affectedRowsCount;
            }
            elseif($type == Connector::LAST_INSERTED_ID)
            {
                $this->_lastInsertedId = $this->_connection->lastInsertId();
                $result = $this->_lastInsertedId;
            }
            
            return $result == FALSE ? NULL : $result;
        }
        catch(PDOException $e)
        {
            $this->_lastErrorMessage = $e->getMessage();
            throw new SQLException($this->_lastErrorMessage,500,$e);
        }
    }
    
    /**
     * @name secureQuery
     * @description executes an SQL statement in a secure way
     * @access public
     * @param string $sql
     * @param array $parameters
     * @param integer $type
     * @param integer $fetch
     * @return mixed 
     * @throws DatabaseException | SQLException
     * @notes $parameters should be in "key => value" format , eg : [':id' => 1 , ':name' => 'IceBerg'] and the sql : SELECT * FROM table where id = :id and name = :name 
     */
    public function prepare(string $sql,array $parameters = [],int $type = Connector::FETCH_ALL ,int $fetch = PDO::FETCH_ASSOC)
    {
        # checks if the database is connected
        if (!$this->isConnected())
        {
            $this->_lastErrorMessage = 'Database is not connected !';
            throw new DatabaseException($this->_lastErrorMessage);
        }
        
        $this->_lastErrorMessage = '';
        
        try
        {
            $preparedStatement = $this->_connection->prepare($sql);

            if(count($parameters))
            {
                foreach ($parameters as $key => $value)
                {
                    $preparedStatement->bindParam($key,$value,PDO::PARAM_STR);
                }
            }

            $preparedStatement->execute(); 
            $this->_affectedRowsCount = $preparedStatement->rowCount();

            $result = FALSE;
            
            if($type == Connector::FETCH_ALL)
            {
                $result = $preparedStatement->fetchAll($fetch);
            }
            elseif($type == Connector::FETCH_FIRST)
            {
                $result = $preparedStatement->fetch($fetch);
            }
            elseif($type == Connector::AFFECTED_ROWS)
            {
                $result = $this->_affectedRowsCount;
            }
            elseif($type == Connector::LAST_INSERTED_ID)
            {
                $this->_lastInsertedId = $this->_connection->lastInsertId();
                $result = $this->_lastInsertedId;
            }
            
            return $result == FALSE ? NULL : $result;
        }
        catch(PDOException $e)
        {
            $this->_lastErrorMessage = $e->getMessage();
            throw new SQLException($this->_lastErrorMessage,500,$e);
        }
    }
    
    /**
     * @name copy
     * @description SQL copy statement
     * @access public
     * @param string $schema
     * @param string $table
     * @param string $filePath
     * @return array
     * @throws DatabaseException | SQLException
     */
    public function copy(string $schema,string $table,string $filePath) : array
    {                 
        $command = "export PGPASSWORD='{$this->getPassword()}'; psql -h {$this->getHost()} -U{$this->getUsername()} -d{$this->getName()} -p{$this->getPort()} -c \"COPY {$schema}.{$table} FROM STDIN WITH CSV HEADER DELIMITER AS ';' NULL AS '';\" < $filePath";
        return Terminal::getInstance()->cmd($command,Terminal::RETURN_OUTPUT,Terminal::ARRAY_OUTPUT);
    }
    
    /**
     * @name transaction
     * @description executes the transactional operations.
     * @access public
     * @param int $type
     * @return mixed
     */
    public function transaction(int $type = Connector::BEGIN_TRANSACTION) 
    {
        # checks if the database is connected
        if (!$this->isConnected())
        {
            $this->_lastErrorMessage = 'Database is not connected !';
            throw new DatabaseException($this->_lastErrorMessage);
        }
        
        $this->_lastErrorMessage = '';
        
        try 
        {
            if ($type == Connector::BEGIN_TRANSACTION)
            {
                $this->_connection->beginTransaction();
            }  
            elseif ($type == Connector::COMMIT_TRANSACTION)
            {
                $this->_connection->commit();
            }
            elseif ($type == Connector::ROLLBACK_TRANSACTION)
            {
                $this->_connection->rollBack();
            }
            else 
            {
                $this->_lastErrorMessage = 'The passed transaction type is wrong!';
                throw new ArgumentException($this->_lastErrorMessage);
            }
        } 
        catch (PDOException $e) 
        {
            $this->_lastErrorMessage = $e->getMessage();
            throw new DatabaseException($this->_lastErrorMessage,500,$e);
        } 
    }
    
    /**
     * @readwrite
     * @access protected 
     * @var \PDO
     */
    protected $_connection;
    
    /**
     * @readwrite
     * @access protected 
     * @var array
     */
    protected $_supportedDrivers = ["mysql", "pgsql"];

    /**
     * @readwrite
     * @access protected 
     * @var string
     */
    protected $_lastErrorMessage = "";

    /**
     * @readwrite
     * @access protected 
     * @var integer
     */
    protected $_lastInsertedId = 0;

    /**
     * @readwrite
     * @access protected 
     * @var integer
     */
    protected $_affectedRowsCount = 0;
    
    /**
     * @access static 
     * @var integer
     */
    const FETCH_FIRST = 0;
    
    /**
     * @access static 
     * @var integer
     */
    const FETCH_ALL = 1;
    
    /**
     * @access static 
     * @var integer
     */
    const AFFECTED_ROWS = 2;
    
    /**
     * @access static 
     * @var integer
     */
    const LAST_INSERTED_ID = 3;
    
    /**
     * @access static 
     * @var integer
     */
    const BEGIN_TRANSACTION = 4;
    
    /**
     * @access static 
     * @var integer
     */
    const COMMIT_TRANSACTION = 5;
    
    /**
     * @access static 
     * @var integer
     */
    const ROLLBACK_TRANSACTION = 6;
}


