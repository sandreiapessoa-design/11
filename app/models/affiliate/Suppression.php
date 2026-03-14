<?php declare(strict_types=1); namespace IR\App\Models\Affiliate; if (!defined('IR_START')) exit('<pre>No direct script access allowed</pre>');
/**
 * @framework       iResponse Framework 
 * @version         1.0
 * @author          Amine Idrissi <contact@iresponse.tech>
 * @date            2024
 * @name            Suppression.php	
 */

# orm 
use IR\Orm\ActiveRecord as ActiveRecord;


# helpers 
use IR\App\Helpers\AuditLog as AuditLog;

/**
 * @name Suppression
 * @description Suppression Model
 */
class Suppression extends ActiveRecord
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
    protected $_schema = 'affiliate';

    /**
     * @table
     * @readwrite
     */
    protected $_table = 'suppressions'; 

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
     * @nullable true
     * @length 20
     */
    protected $_process_id;
    
    /**
     * @column
     * @readwrite
     * @indexed
     * @type integer
     * @nullable false
     * @length
     */
    protected $_affiliate_network_id;
    
    /**
     * @column
     * @readwrite
     * @type text
     * @nullable false
     * @length 200
     */
    protected $_affiliate_network_name;
    
    /**
     * @column
     * @readwrite
     * @indexed
     * @type integer
     * @nullable false
     * @length
     */
    protected $_offer_id;
    
    /**
     * @column
     * @readwrite
     * @type text
     * @nullable false
     * @length 200
     */
    protected $_offer_name;
    
    /**
     * @column
     * @readwrite
     * @indexed
     * @type text
     * @nullable false
     * @length
     */
    protected $_lists_ids;
    
    /**
     * @column
     * @readwrite
     * @type text
     * @nullable false
     * @length 10
     */
    protected $_progress;
    
    /**
     * @column
     * @readwrite
     * @type integer
     * @nullable true
     * @length
     */
    protected $_emails_found;
    
    /**
     * @column
     * @readwrite
     * @type timestamp
     * @nullable false
     * @length 
     */
    protected $_start_time;
    
    /**
     * @column
     * @readwrite
     * @type timestamp
     * @nullable true
     * @length 
     */
    protected $_finish_time;
    
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
        AuditLog::registerLog($this->_id,'Suppression Process','Affiliate Process','Start');
        
        return $this->_id;
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
        AuditLog::registerLog($this->_id,'Suppression Process','Affiliate Process','Delete');
        
        return parent::delete();
    }
}