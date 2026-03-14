<?php declare(strict_types=1); namespace IR\App\Models\Admin; if (!defined('IR_START')) exit('<pre>No direct script access allowed</pre>');
/**
 * @framework       iResponse Framework 
 * @version         1.0
 * @author          Amine Idrissi <contact@iresponse.tech>
 * @date            2024
 * @name            VultrInstance.php	
 */

# base
use IR\Core\Application as Application;

# orm 
use IR\Orm\ActiveRecord as ActiveRecord;

# helpers 
use IR\App\Helpers\AuditLog as AuditLog;

# utilities
use IR\Utils\Types\Objects as Objects;

/**
 * @name VultrInstance
 * @description VultrInstance Model
 */
class VultrInstance extends ActiveRecord
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
    protected $_schema = 'admin';

    /**
     * @table
     * @readwrite
     */
    protected $_table = 'vultr_instances';

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
     * @type text
     * @nullable false
     * @length 100
     */
    protected $_name;
    
    /**
     * @column
     * @readwrite
     * @indexed
     * @type text
     * @nullable false
     * @length 100
     */
    protected $_instance_id;
    
    /**
     * @column
     * @readwrite
     * @indexed
     * @type integer
     * @nullable false
     * @length 
     */
    protected $_account_id;
    
    /**
     * @column
     * @readwrite
     * @type text
     * @nullable false
     * @length 200
     */
    protected $_account_name;
    
    /**
     * @column
     * @readwrite
     * @indexed
     * @type integer
     * @nullable false
     * @length 
     */
    protected $_mta_server_id;
    
    /**
     * @column
     * @readwrite
     * @type text
     * @nullable false
     * @length 200
     */
    protected $_mta_server_name;
 
    /**
     * @column
     * @readwrite
     * @type text
     * @nullable false
     * @length 100
     */
    protected $_size;
    
    /**
     * @column
     * @readwrite
     * @type text
     * @nullable false
     * @length 100
     */
    protected $_platform;
    
    /**
     * @column
     * @readwrite
     * @type text
     * @nullable false
     * @length 100
     */
    protected $_region;
    
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
        
        # delete mta servers if any
        MtaServer::deleteWhere('id = ? ',[$this->_mta_server_id]);
        
        # change domains statuses 
        Application::getCurrent()->database("system")->execute("UPDATE admin.domains SET ip_id = 0 , availability = 'Available' WHERE mta_server_id = {$this->_mta_server_id}");

        # delete server vmtas if any
        ServerVmta::deleteWhere('mta_server_id = ?',[$this->_mta_server_id]);
        
        return parent::delete();
    }
}
