<?php declare(strict_types=1); namespace IR\App\Models\Admin; if (!defined('IR_START')) exit('<pre>No direct script access allowed</pre>');
/**
 * @framework       iResponse Framework 
 * @version         1.0
 * @author          Amine Idrissi <contact@iresponse.tech>
 * @date            2024
 * @name            AtlanticProcess.php	
 */

# orm 
use IR\Orm\ActiveRecord as ActiveRecord;

# helpers 
use IR\App\Helpers\AuditLog as AuditLog;

/**
 * @name AtlanticProcess
 * @description AtlanticProcess Model
 */
class AtlanticProcess extends ActiveRecord
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
    protected $_table = 'atlantic_processes';

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
    protected $_account_id;
    
    /**
     * @column
     * @readwrite
     * @type text
     * @nullable false
     * @length 200
     */
    protected $_region;
    
    /**
     * @column
     * @readwrite
     * @type integer
     * @nullable false
     * @length
     */
    protected $_nb_instances;
    
    /**
     * @column
     * @readwrite
     * @type text
     * @nullable false
     * @length
     */
    protected $_domains;
    
    /**
     * @column
     * @readwrite
     * @type text
     * @nullable false
     * @length 100
     */
    protected $_os;
    
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
    protected $_instances_created;
    
    /**
     * @column
     * @readwrite
     * @type integer
     * @nullable true
     * @length
     */
    protected $_instances_installed;
    
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
        AuditLog::registerLog($this->_id,'Atlantic Process','Instances Creation','Start');
        
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
        AuditLog::registerLog($this->_id,'Atlantic Process','Instances Termination','Start');
        
        return parent::delete();
    }
}


