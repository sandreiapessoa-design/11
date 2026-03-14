<?php declare(strict_types=1); namespace IR\App\Models\Production; if (!defined('IR_START')) exit('<pre>No direct script access allowed</pre>');
/**
 * @framework       iResponse Framework 
 * @version         1.0
 * @author          Amine Idrissi <contact@iresponse.tech>
 * @date            2024
 * @name            SmtpProcess.php	
 */

# orm 
use IR\Orm\ActiveRecord as ActiveRecord;

# helpers 
use IR\App\Helpers\AuditLog as AuditLog;
use IR\App\Helpers\Api as Api;

# utilities
use IR\Utils\Types\Objects as Objects;

/**
 * @name SmtpProcess
 * @description SmtpProcess Model
 */
class SmtpProcess extends ActiveRecord
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
    protected $_schema = 'production';

    /**
     * @table
     * @readwrite
     */
    protected $_table = 'smtp_processes';

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
     * @type text
     * @nullable false
     * @length 100
     */
    protected $_process_type;
    
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
     * @column
     * @readwrite
     * @type text
     * @nullable false
     * @length
     */
    protected $_servers_ids;
    
    /**
     * @column
     * @readwrite
     * @type integer
     * @nullable false
     * @length
     */
    protected $_user_id;
    
    /**
     * @column
     * @readwrite
     * @type integer
     * @nullable true
     * @length 
     */
    protected $_total_emails;
     
    /**
     * @column
     * @readwrite
     * @type integer
     * @nullable true
     * @length 
     */
    protected $_progress;
    
    /**
     * @column
     * @readwrite
     * @type text
     * @nullable true
     * @length
     */
    protected $_auto_responders_ids;
    
    /**
     * @column
     * @readwrite
     * @type integer
     * @nullable true
     * @length
     */
    protected $_affiliate_network_id;
    
    /**
     * @column
     * @readwrite
     * @type integer
     * @nullable true
     * @length
     */
    protected $_offer_id;
    
    /**
     * @column
     * @readwrite
     * @type integer
     * @nullable true
     * @length
     */
    protected $_isp_id;

    /**
     * @column
     * @readwrite
     * @type integer
     * @nullable true
     * @length
     */
    protected $_data_start;
    
    /**
     * @column
     * @readwrite
     * @type integer
     * @nullable true
     * @length
     */
    protected $_data_count;
    
    /**
     * @column
     * @readwrite
     * @type text
     * @nullable true
     * @length
     */
    protected $_lists;

    /**
     * @column
     * @readwrite
     * @type integer
     * @nullable true
     * @length 
     */
    protected $_delivered;
     
     /**
     * @column
     * @readwrite
     * @type integer
     * @nullable true
     * @length 
     */
    protected $_hard_bounced;
     
     /**
     * @column
     * @readwrite
     * @type integer
     * @nullable true
     * @length 
     */
    protected $_soft_bounced;
     
    /**
     * @column
     * @readwrite
     * @type integer
     * @nullable true
     * @length 
     */
    protected $_opens;
     
     /**
     * @column
     * @readwrite
     * @type integer
     * @nullable true
     * @length 
     */
    protected $_clicks;
     
     /**
     * @column
     * @readwrite
     * @type integer
     * @nullable true
     * @length 
     */
    protected $_leads;
     
     /**
     * @column
     * @readwrite
     * @type integer
     * @nullable true
     * @length 
     */
    protected $_unsubs;
    
    /**
     * @column
     * @readwrite
     * @type text
     * @nullable true
     * @length
     */
    protected $_negative_file_path;
    
    /**
     * @column
     * @readwrite
     * @type text
     * @nullable false
     * @length
     */
    protected $_content;
    
    /**
     * @name delete
     * @description creates a query object, only if the primary key property value is not empty, and executes the query’s delete() method.
     * @access public
     * @return integer
     */
    public function delete() : int
    {
        # register audit log
        AuditLog::registerLog($this->_id,"SMTPProcess {$this->_id}",Objects::getInstance()->getName($this),'Delete');
        
        # delete role authorisations if any
        SmtpProcessUser::deleteWhere('process_id = ?',[$this->_id]);

        # stop process if still in progress
        Api::call('Production','executeProcessAction',['processes-ids' => [$this->_id],'action' => 'stop','type' => 'smtp']); 
        
        return parent::delete();
    }
}