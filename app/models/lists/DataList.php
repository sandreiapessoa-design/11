<?php declare(strict_types=1); namespace IR\App\Models\Lists; if (!defined('IR_START')) exit('<pre>No direct script access allowed</pre>');
/**
 * @framework       iResponse Framework 
 * @version         1.0
 * @author          Amine Idrissi <contact@iresponse.tech>
 * @date            2024
 * @name            DataList.php	
 */

# core 
use IR\Core\Application as Application;

# orm 
use IR\Orm\Table as Table;
use IR\Orm\ActiveRecord as ActiveRecord;

# helpers
use IR\App\Helpers\AuditLog as AuditLog;
use IR\App\Helpers\Authentication as Authentication;
use IR\App\Helpers\Permissions as Permissions;

# utilities
use IR\Utils\Types\Objects as Objects;

/**
 * @name DataList
 * @description DataList Model
 */
class DataList extends ActiveRecord
{
    /**
     * @database
     * @readwrite
     */
    protected $_databaseKey = 'system';

    /**
     * @schema
     * @readwrite
     */
    protected $_schema = 'lists';

    /**
     * @table
     * @readwrite
     */
    protected $_table = 'data_lists';

    # columns

    /**
     * @column
     * @readwrite
     * @primary
     * @indexed
     * @autoincrement
     * @type integer
     * @nullable false
     * @length 
     */
    protected $_id;

    /**
     * @column
     * @readwrite
     * @indexed
     * @type text
     * @nullable false
     * @length 20
     */
    protected $_status;

    /**
     * @column
     * @readwrite
     * @indexed
     * @type integer
     * @nullable false
     * @length
     */
    protected $_data_provider_id;
    
    /**
     * @column
     * @readwrite
     * @type text
     * @nullable false
     * @length 200
     */
    protected $_data_provider_name;
    
    /**
     * @column
     * @readwrite
     * @type text
     * @nullable false
     * @length 200
     */
    protected $_name;
    
    /**
     * @column
     * @readwrite
     * @type text
     * @nullable false
     * @length 100
     */
    protected $_table_name;
    
    /**
     * @column
     * @readwrite
     * @type text
     * @nullable false
     * @length 100
     */
    protected $_table_schema;
    
    /**
     * @column
     * @readwrite
     * @indexed
     * @type integer
     * @nullable false
     * @length
     */
    protected $_isp_id;
    
    /**
     * @column
     * @readwrite
     * @type text
     * @nullable false
     * @length 200
     */
    protected $_isp_name;
    
    /**
     * @column
     * @readwrite
     * @type integer
     * @nullable true
     * @length
     */
    protected $_total_count;
    
    /**
     * @column
     * @readwrite
     * @type text
     * @nullable false
     * @length 20
     */
    protected $_encrypt_emails;

    /**
     * @column
     * @readwrite
     * @type text
     * @nullable false
     * @length 200
     */
    protected $_created_by;

    /**
     * @column
     * @readwrite
     * @type text
     * @nullable true
     * @length 200
     */
    protected $_last_updated_by;

    /**
     * @column
     * @readwrite
     * @type date
     * @nullable false
     * @length 
     */
    protected $_created_date;

    /**
     * @column
     * @readwrite
     * @type date
     * @nullable true
     * @length 
     */
    protected $_last_updated_date;

    # overrided
    
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
        $user = Authentication::getAuthenticatedUser();

        if($user->getMasterAccess() != 'Enabled')
        {
            Permissions::modelTeamAuthsFilter(__CLASS__,$user,$where);
        }

        return parent::first($format,$where, $fields, $order, $direction);
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
        $user = Authentication::getAuthenticatedUser();
        
        if($user->getMasterAccess() != 'Enabled')
        {
            Permissions::modelTeamAuthsFilter(__CLASS__,$user,$where);
        }

        return parent::all($format, $where, $fields, $order, $direction, $limit, $offset);
    }
    
    /**
     * @name load
     * @description loads a record if the primary column’s value has been provided
     * @access public
     * @return
     */
    public function load($primayValue = null) 
    {
        $user = Authentication::getAuthenticatedUser();
        
        if($user->getMasterAccess() != 'Enabled')
        {
            $where = [];
            $teamBasedFilterIds = Permissions::modelTeamAuthsFilter(__CLASS__,$user,$where);
            $hasAdminRole = Permissions::hasAdminBasedRole($user);
            
            if($hasAdminRole == false)
            {
                if($primayValue == null)
                {
                    if(in_array($this->_id,$teamBasedFilterIds))
                    {
                        parent::load();
                    }
                }
                else
                {
                    if(in_array($primayValue,$teamBasedFilterIds))
                    {
                        parent::load($primayValue);
                    }
                }
            }
            else
            {
                parent::load($primayValue);
            }
        }
        else
        {
            parent::load($primayValue);
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
        $this->_id = parent::insert();
        
        # register audit log
        AuditLog::registerLog($this->_id,$this->_name,Objects::getInstance()->getName($this),'Insert');
        
        return $this->_id;
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
        # register audit log
        AuditLog::registerLog($this->_id,$this->_name,Objects::getInstance()->getName($this),'Update');
        
        return parent::update();
    }
    
    /**
     * @name delete
     * @description creates a query object, only if the primary key property value is not empty, and executes the query’s delete() method.
     * @access public
     * @return integer
     */
    public function delete() : int
    {
        # register audit log
        AuditLog::registerLog($this->_id,$this->_name,Objects::getInstance()->getName($this),'Delete');
        
        # delete data list table if any 
        Application::getCurrent()->database('clients')->connect();
        
        # check if the is there 
        if(Table::exists('clients',$this->_table_name,$this->_table_schema))
        {
            Application::getCurrent()->database('clients')->execute('DROP TABLE ' . $this->_table_schema . '.' . $this->_table_name);
            Application::getCurrent()->database('clients')->execute('DROP SEQUENCE ' . $this->_table_schema . '.seq_id_' . $this->_table_name);
        }
        
        return parent::delete();
    }
}


