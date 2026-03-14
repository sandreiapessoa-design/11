<?php declare(strict_types=1); namespace IR\App\Models\Admin; if (!defined('IR_START')) exit('<pre>No direct script access allowed</pre>');
/**
 * @framework       iResponse Framework 
 * @version         1.0
 * @author          Amine Idrissi <contact@iresponse.tech>
 * @date            2024
 * @name            DigitalOceanAccount.php	
 */

# orm 
use IR\Orm\ActiveRecord as ActiveRecord;

# helpers 
use IR\App\Helpers\AuditLog as AuditLog;

# utilities
use IR\Utils\Types\Objects as Objects;

/**
 * @name DigitalOceanAccount
 * @description DigitalOceanAccount Model
 */
class DigitalOceanAccount extends ActiveRecord
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
    protected $_table = 'digital_ocean_accounts';

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
     * @type text
     * @nullable false
     * @length 100
     */
    protected $_name;

    /**
     * @column
     * @readwrite
     * @type text
     * @nullable false
     * @length 500
     */
    protected $_token;
    
    /**
     * @column
     * @readwrite
     * @type text
     * @nullable false
     * @length 100
     */
    protected $_proxy_status;
    
    /**
     * @column
     * @readwrite
     * @type text
     * @nullable true
     * @length 100
     */
    protected $_proxy_ip;
    
    /**
     * @column
     * @readwrite
     * @type text
     * @nullable true
     * @length 5
     */
    protected $_proxy_port;

    /**
     * @column
     * @readwrite
     * @type text
     * @nullable true
     * @length 500
     */
    protected $_proxy_username;
    
    /**
     * @column
     * @readwrite
     * @type text
     * @nullable true
     * @length 500
     */
    protected $_proxy_password;
    
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
        
        # delete account domains if any
        DigitalOceanDroplet::deleteWhere('account_id = ? ',[$this->_id]);
        
        return parent::delete();
    }
}