<?php declare(strict_types=1); namespace IR\App\Models\Admin; if (!defined('IR_START')) exit('<pre>No direct script access allowed</pre>');
/**
 * @framework       iResponse Framework 
 * @version         1.0
 * @author          Amine Idrissi <contact@iresponse.tech>
 * @date            2024
 * @name            PmtaHistory.php	
 */

# orm 
use IR\Orm\ActiveRecord as ActiveRecord;

/**
 * @name PmtaHistory
 * @description PmtaHistory Model
 */
class PmtaHistory extends ActiveRecord
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
    protected $_table = 'pmta_commands_history';

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
     * @type integer
     * @nullable false
     * @length
     */
    protected $_server_id;
    
    /**
     * @column
     * @readwrite
     * @indexed
     * @type integer
     * @nullable false
     * @length
     */
    protected $_user_id;

    /**
     * @column
     * @readwrite
     * @type text
     * @nullable false
     * @length 50
     */
    protected $_action;
    
    /**
     * @column
     * @readwrite
     * @type text
     * @nullable false
     * @length 50
     */
    protected $_target;
    
    /**
     * @column
     * @readwrite
     * @type text
     * @nullable true
     * @length 500
     */
    protected $_isps;

    /**
     * @column
     * @readwrite
     * @type text
     * @nullable true
     * @length 
     */
    protected $_vmtas;
    
    /**
     * @column
     * @readwrite
     * @type text
     * @nullable true
     * @length 
     */
    protected $_results;
    
    /**
     * @column
     * @readwrite
     * @type timestamp
     * @nullable false
     * @length 
     */
    protected $_action_time;
}


