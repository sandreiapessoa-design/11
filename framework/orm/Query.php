<?php declare(strict_types=1); namespace IR\Orm; if (!defined('IR_START')) exit('<pre>No direct script access allowed</pre>');
/**
 * @framework       iResponse Framework 
 * @version         1.0
 * @author          Amine Idrissi <contact@iresponse.tech>
 * @date            2024
 * @name            Query.php	
 */

# php defaults 
use \PDO;

# core 
use IR\Core\Base as Base;

# utilities
use IR\Utils\Types\Arrays as Arrays;
use IR\Utils\Types\Strings as Strings;

# exceptions
use IR\Exceptions\Types\DatabaseException as DatabaseException;
use IR\Exceptions\Types\SQLException as SQLException;

/**
 * @name Query
 * @description orm query class
 */
class Query extends Base
{
    /**
     * @name all
     * @description gets all the rows retrieved by the SELECT sql statement
     * @access public
     * @return array
     * @throws DatabaseException
     */
    public function all($processType = Query::EXECUTE_QUERY,$fetch = PDO::FETCH_ASSOC) : array
    {
        $this->_last_query = $this->_build(Query::SELECT);
        
        if($processType == Query::ONLY_BUILD_QUERY)
        {
            return [
                'query' => $this->_last_query
            ];
        }
        
        $result = $this->_database->execute($this->_last_query,Connector::FETCH_ALL,$fetch);
        $this->_reset();
        return (array) $result;
    }
    
    /**
     * @name first
     * @description gets the first row retrieved by the SELECT sql statement
     * @access public
     * @return array
     * @throws DatabaseException
     */
    public function first($processType = Query::EXECUTE_QUERY,$fetch = PDO::FETCH_ASSOC) : array
    {
        $this->_last_query = $this->_build(Query::SELECT);
        
        if($processType == Query::ONLY_BUILD_QUERY)
        {
            return [
                'query' => $this->_last_query
            ];
        }
        
        $result = $this->_database->execute($this->_last_query,Connector::FETCH_FIRST,$fetch);
        $this->_reset();
        return (array) $result;
    }
    
    /**
     * @name last
     * @description gets the last row retrieved by the SELECT sql statement
     * @access public
     * @return array
     * @throws DatabaseException
     */
    public function last($processType = Query::EXECUTE_QUERY,$fetch = PDO::FETCH_ASSOC) : array
    {
        $this->_last_query = $this->_build(Query::SELECT);
        
        if($processType == Query::ONLY_BUILD_QUERY)
        {
            return [
                'query' => $this->_last_query
            ];
        }
        
        $result = $this->_database->execute($this->_last_query,Connector::FETCH_ALL,$fetch);
        $this->_reset();
        return is_array($result) ? Arrays::getInstance()->last($result) : [];
    }
    
    /**
     * @name count
     * @description retrieves the row count of query result
     * @access public 
     * @return integer
     * @throws DatabaseException
     */
    public function count() : int
    {
        $this->_fields[$this->_from] = ['Count(1)' => 'count'];
        $result = $this->first();        
                
        if(count($result))
        {
            if(key_exists('count',$result))
            {
                return intval($result['count']);
            }
        }        
        
        return 0;
    }

    /**
     * @name max
     * @description gets the max of a column
     * @access public 
     * @param integer $column 
     * @return integer
     * @throws DatabaseException
     */
    public function max($column = 'id') : int
    {
        return Strings::getInstance()->contains($this->_from,'.') ?
               Table::max($this->_database->getKey(),Arrays::getInstance()->last(explode('.',$this->_from)),Arrays::getInstance()->first(explode('.',$this->_from)),$column) :
               Table::max($this->_database->getKey(),$this->_from,'public',$column);
    }
    
    /**
     * @name max
     * @description gets the min of a column
     * @access public 
     * @param integer $column 
     * @return integer
     * @throws DatabaseException
     */
    public function min($column = 'id') : int
    {
        return Strings::getInstance()->contains($this->_from,'.') ?
               Table::min($this->_database->getKey(),Arrays::getInstance()->last(explode('.',$this->_from)),Arrays::getInstance()->first(explode('.',$this->_from)),$column) :
               Table::min($this->_database->getKey(),$this->_from,'public',$column);
    }
    
    /**
     * @name sum
     * @description gets the sum of a column
     * @access public 
     * @param integer $column 
     * @return integer
     * @throws DatabaseException
     */
    public function sum($column = 'id') : int
    {
        return Strings::getInstance()->contains($this->_from,'.') ?
               Table::sum($this->_database->getKey(),Arrays::getInstance()->last(explode('.',$this->_from)),Arrays::getInstance()->first(explode('.',$this->_from)),$column) :
               Table::sum($this->_database->getKey(),$this->_from,'public',$column);
    }
    
    /**
     * @name avg
     * @description gets the average of a column
     * @access public 
     * @param integer $column 
     * @return integer
     * @throws DatabaseException
     */
    public function avg($column = 'id') : int
    {
        return Strings::getInstance()->contains($this->_from,'.') ?
               Table::average($this->_database->getKey(),Arrays::getInstance()->last(explode('.',$this->_from)),Arrays::getInstance()->first(explode('.',$this->_from)),$column) :
               Table::average($this->_database->getKey(),$this->_from,'public',$column);
    }

    
    /**
     * @name insert
     * @description inserts data into the database
     * @access public
     * @param array $data the data to be inserted
     * @return integer
     * @throws DatabaseException
     */
    public function insert($data,$type = Connector::LAST_INSERTED_ID,$processType = Query::EXECUTE_QUERY) : int
    {  
        $this->_last_query = $this->_build(Query::INSERT,$data);
        
        if($processType == Query::ONLY_BUILD_QUERY)
        {
            return [
                'query' => $this->_last_query
            ];
        }
        
        $id = $this->_database->execute($this->_last_query,$type);
        $this->_reset();
        return intval($id);
    }
    
    /**
     * @name update
     * @description updates data into the database
     * @access public
     * @param array $data the data to be updated
     * @return integer
     * @throws DatabaseException
     */
    public function update($data,$type = Connector::AFFECTED_ROWS,$processType = Query::EXECUTE_QUERY) : int
    {  
        $this->_last_query = $this->_build(Query::UPDATE,$data);
        
        if($processType == Query::ONLY_BUILD_QUERY)
        {
            return [
                'query' => $this->_last_query
            ];
        }
        
        $id = $this->_database->execute($this->_last_query,$type);
        $this->_reset();
        return intval($id);
    }
    
    /**
     * @name delete
     * @description deletes data from the database
     * @access public 
     * @return integer
     * @throws DatabaseException
     */
    public function delete($processType = Query::EXECUTE_QUERY) : int
    {
        $this->_last_query = $this->_build(Query::DELETE);
        
        if($processType == Query::ONLY_BUILD_QUERY)
        {
            return [
                'query' => $this->_last_query
            ];
        }
        
        $this->_database->execute($this->_last_query,Connector::AFFECTED_ROWS);
        $this->_reset();
        return $this->_database->getAffectedRowsCount();
    }
    
    /**
     * @name from
     * @description the from part of the query 
     * @access public
     * @param string $from the table name
     * @param array $fields columns to select ( * by default )
     * @return Query
     */
    public function from(string $from,$fields = ['*']) : Query
    {
        $this->_from = trim($from);
        $this->_fields[$from] = $fields;
        return $this;
    }
    
    /**
     * @name where
     * @description the where part of the query 
     * @access public
     * @param  string $condition
     * @param  array $parameters
     * @return Query
     */
    public function where(string $condition,$parameters,bool $quote = true) : Query
    {
        $arguments = [];
        $i = 0;
        $arguments[$i] = preg_replace("#\?#","%s", str_replace('%','%%',$condition));

        # if the parameters is just a string 
        if(!is_array($parameters))
        {
           $arguments[1] = $quote == true ? $this->_quote($parameters) : $parameters;
        }
        else
        {
            foreach ($parameters as $parameter) 
            {
                $arguments[++$i] = $quote == true ? $this->_quote($parameter) : $parameter;   
            }
        }
        
        $this->_where[] = trim(call_user_func_array("sprintf",$arguments));
        return $this;
    }
    
    /**
     * @name order
     * @description the order part of the query 
     * @access public
     * @param  string $column
     * @param  string $direction
     * @return Query
     */
    public function order(string $column, string $direction = Query::ASC) : Query
    {
        $this->_order = trim($column);
        $this->_direction = trim($direction);  
        return $this;
    }
    
    /**
     * @name limit
     * @description the limit part of the query 
     * @access public
     * @param  integer $limit
     * @param  integer $offset
     * @return Query
     */
    public function limit(int $limit, int $offset = 0) : Query
    {   
        $this->_limit = $limit;
        $this->_offset = $offset;    
        return $this;
    }
    
    /**
     * @name limit
     * @description the limit part of the query 
     * @access public
     * @param  integer $limit
     * @param  integer $offset
     * @return Query
     */
    public function group(array $columns) : Query
    {   
        $this->_group = $columns;    
        return $this;
    }
    
    /**
     * @name join
     * @description the join part of the query 
     * @access public
     * @param string $join the table name 
     * @param string $on the condition
     * @param array $fields the fileds to select
     * @param string $type the join type
     * @return Query
     */
    public function join(string $join,string $on,array $fields = [],string $type = Query::LEFT_JOIN) : Query
    {
        $this->_fields += [$join => $fields];
        $this->_join[] = trim("{$type} {$join} ON {$on}");
        return $this;
    } 
    
    /**
     * @name _resetParameters
     * @description resets database class parameters ( from , where , limit ..... ) after a an execution of a query
     * @access protected
     * @return boolean
     */
    protected function _reset()
    {
        $this->_from = '';
        $this->_fields = [];
        $this->_limit = 0;
        $this->_offset = 0;
        $this->_order = '';
        $this->_direction = '';
        $this->_group = [];
        $this->_join = [];
        $this->_where = [];
    }
    
    /**
     * @name _quote
     * @description wraps the $value passed to it in the applicable quotation marks, so that it can be added to the applicable query in a syntactically 
     * @access protected 
     * @param string $value
     * @return string
     */
    protected function _quote($value)
    {
        if(!is_array($value) && Strings::getInstance()->startsWith(Strings::getInstance()->trim(strval($value)),'nq[') && Strings::getInstance()->endsWith(Strings::getInstance()->trim(strval($value)),']'))
        {
            return str_replace(['nq[',']'],'',$value);
        }
        
        if (is_array($value)) 
        {
            $buffer = [];
            
            foreach ($value as $i) 
            {
                array_push($buffer, $this->_quote($i));
            }
            
            $buffer = join(",", $buffer);
            return "({$buffer})";
        }
        
        if (is_null($value)) 
        {
            return "NULL";
        }
        
        if (is_bool($value)) 
        {
            return (int) $value;
        }

        return $this->_database->escape(strval($value));
    }
    
    /**
     * @name _build
     * @description builds a Pqsql compatible SQL query, from the ground up. it declares the template for our SELECT statement.
     * @access protected 
     * @param integer $type
     * @return string
     */
    protected function _build($type = Query::SELECT,$data = []) : string
    {
        switch ($type) 
        {
            case Query::SELECT :
            {
                $fields = [];
                $where = $order = $limit = $join = $group = '';
                $template = "SELECT %s FROM %s %s %s %s %s %s";

                foreach ($this->_fields as $_fields)
                {
                    foreach ($_fields as $field => $alias)
                    {
                        if (is_string($field))
                        {
                            $fields[] = "{$field} AS {$alias}";
                        }
                        else
                        {
                            $fields[] = $alias;
                        }
                    }
                }

                $fields = trim(join(',', $fields));

                # join case
                if (!empty($this->_join))
                {
                    $join = trim(join(' ', $this->_join));
                }
        
                # where case
                if (!empty($this->_where))
                {
                    $joined = join(' AND ', $this->_where);
                    $where = trim("WHERE {$joined}");
                }

                # group by case
                if (!empty($this->_group)) 
                {
                    $joined = join(',', $this->_group);
                    $group = trim("GROUP BY {$joined}");
                }
                
                # order case
                if (!empty($this->_order)) 
                {
                    $order = trim("ORDER BY {$this->_order} {$this->_direction}");
                }
        
                # limit case
                if (!empty($this->_limit)) 
                {
                    if ($this->_offset) 
                    {
                        $type = Arrays::getInstance()->get($this->_database->getProperties(),'driver');

                        if($type == 'mysql')
                        {
                            $limit = trim("LIMIT {$this->_limit}, {$this->_offset}");
                        }
                        elseif($type == 'pgsql')
                        {
                            $limit = trim("OFFSET {$this->_offset} LIMIT {$this->_limit}");
                        }  
                    } 
                    else 
                    {
                        $limit = trim("LIMIT {$this->_limit}");
                    }
                }
                
                return trim(sprintf($template, $fields,$this->_from, $join, $where, $group,$order, $limit));
            }
            case Query::INSERT :
            {
                $fields = [];
                $values = [];
                $template = "INSERT INTO %s (%s) VALUES (%s)";

                foreach ($data as $field => $value) 
                {
                    $fields[] = $field;
                    $values[] = $this->_quote(strval($value));
                }

                $fields = trim(join(',', $fields));
                $values = trim(join(',', $values));
                
                return trim(sprintf($template,$this->_from, $fields, $values));
            }
            case Query::UPDATE :
            {
                $parts = [];
                $where = $limit = '';
                $template = "UPDATE %s SET %s %s";

                foreach ($data as $field => $value) 
                {
                    $parts[] = "{$field} = " . $this->_quote($value);
                }

                $parts = join(',', $parts);

                # where case
                if (!empty($this->_where)) 
                {
                    $joined = join(',', $this->_where);
                    $where = trim("WHERE {$joined}");
                }

                return trim(sprintf($template,$this->_from, $parts, $where));
            }
            case Query::DELETE :
            {
                $where = '';
                $template = "DELETE FROM %s %s";

                # where case
                if (!empty($this->_where)) 
                {
                    $joined = join(',', $this->_where);
                    $where = trim("WHERE {$joined}");
                }

                return trim(sprintf($template,$this->_from, $where));
            }
            default:
            {
                throw new SQLException('Unsupported query type !');
            }
        }
    }
    
    /**
     * @readwrite
     * @access protected 
     * @var Database
     */
    protected $_database;
    
    /**
     * @readwrite
     * @access protected 
     * @var array
     */
    protected $_from;

    /** 
     * @readwrite
     * @access protected 
     * @var array
     */
    protected $_fields;

    /** 
     * @readwrite
     * @access protected 
     * @var integer
     */
    protected $_limit;

    /** 
     * @readwrite
     * @access protected 
     * @var integer
     */
    protected $_offset;

    /** 
     * @readwrite
     * @access protected 
     * @var string
     */
    protected $_order;

    /** 
     * @readwrite
     * @access protected 
     * @var string
     */
    protected $_direction;
    
    /** 
     * @readwrite
     * @access protected 
     * @var string
     */
    protected $_group;

    /** 
     * @readwrite
     * @access protected 
     * @var array
     */
    protected $_join = [];

    /** 
     * @readwrite
     * @access protected 
     * @var array
     */
    protected $_where = [];
    
    /** 
     * @readwrite
     * @access protected 
     * @var array
     */
    protected $_last_query = '';

    /** 
     * @read
     * @access protected 
     * @var int
     */
    const SELECT = 0;
    
    /** 
     * @read
     * @access protected 
     * @var int
     */
    const INSERT = 1;
    
    /** 
     * @read
     * @access protected 
     * @var int
     */
    const UPDATE = 2;
    
    /** 
     * @read
     * @access protected 
     * @var int
     */
    const DELETE = 3;
    
    /** 
     * @read
     * @access protected 
     * @var int
     */
    const ONLY_BUILD_QUERY = 0;
    
    /** 
     * @read
     * @access protected 
     * @var int
     */
    const EXECUTE_QUERY = 1;
    
    /** 
     * @read
     * @access protected 
     * @var string
     */
    const ASC = 'ASC';
    
    /** 
     * @read
     * @access protected 
     * @var string
     */
    const DESC = 'DESC';
    
    /** 
     * @read
     * @access static 
     * @var string
     */
    const LEFT_JOIN  = 'LEFT JOIN';

    /** 
     * @read
     * @access static 
     * @var string
     */
    const RIGHT_JOIN = 'RIGHT JOIN';

    /** 
     * @read
     * @access static 
     * @var string
     */
    const INNER_JOIN = 'INNER JOIN';

    /** 
     * @read
     * @access static 
     * @var string
     */
    const FULL_OUTER_JOIN = 'FULL OUTER JOIN';
}


