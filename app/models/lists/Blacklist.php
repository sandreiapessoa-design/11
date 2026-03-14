<?php declare(strict_types=1); namespace IR\App\Models\Lists; if (!defined('IR_START')) exit('<pre>No direct script access allowed</pre>');
/**
 * @framework       iResponse Framework 
 * @version         1.0
 * @author          Amine Idrissi <contact@iresponse.tech>
 * @date            2024
 * @name            Blacklist.php	
 */

# orm 
use IR\Orm\ActiveRecord as ActiveRecord;

/**
 * @name Blacklist
 * @description Blacklist Model
 */
class Blacklist extends ActiveRecord
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
    protected $_table = 'blacklists'; 

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
     * @nullable true
     * @length
     */
    protected $_process_id;

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
}