<?php declare(strict_types=1); namespace IR\App\Models\Lists; if (!defined('IR_START')) exit('<pre>No direct script access allowed</pre>');
/**
 * @framework       iResponse Framework 
 * @version         1.0
 * @author          Amine Idrissi <contact@iresponse.tech>
 * @date            2024
 * @name            Email.php	
 */

# orm 
use IR\Orm\ActiveRecord as ActiveRecord;

/**
 * @name Email
 * @description Email Model
 */
class Email extends ActiveRecord
{
    /**
     * @database
     * @readwrite
     */
    protected $_databaseKey = 'clients';

    /**
     * @schema
     * @readwrite
     */
    protected $_schema = '';

    /**
     * @table
     * @readwrite
     */
    protected $_table = '';

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
    protected $_list_id;
    
    /**
     * @column
     * @readwrite
     * @indexed
     * @type text
     * @nullable false
     * @length 500
     */
    protected $_email;
    
    /**
     * @column
     * @readwrite
     * @type text
     * @nullable false
     * @length 500
     */
    protected $_email_md5;
    
    /**
     * @column
     * @readwrite
     * @type text
     * @nullable true
     * @length 500
     */
    protected $_first_name;
    
    /**
     * @column
     * @readwrite
     * @type text
     * @nullable true
     * @length 500
     */
    protected $_last_name;
    
    /**
     * @column
     * @readwrite
     * @type text
     * @nullable true
     * @length
     */
    protected $_verticals;
    
    /**
     * @column
     * @readwrite
     * @type boolean
     * @nullable true
     * @length 
     */
    protected $_is_seed;
    
    /**
     * @column
     * @readwrite
     * @type boolean
     * @nullable true
     * @length 
     */
    protected $_is_fresh; 
    
    /**
     * @column
     * @readwrite
     * @type boolean
     * @nullable true
     * @length 
     */
    protected $_is_clean; 
    
    /**
     * @column
     * @readwrite
     * @type boolean
     * @nullable true
     * @length 
     */
    protected $_is_opener; 
    
    /**
     * @column
     * @readwrite
     * @type boolean
     * @nullable true
     * @length 
     */
    protected $_is_clicker;
    
    /**
     * @column
     * @readwrite
     * @type boolean
     * @nullable true
     * @length 
     */
    protected $_is_leader;

    /**
     * @column
     * @readwrite
     * @type boolean
     * @nullable true
     * @length 
     */
    protected $_is_unsub;
    
    /**
     * @column
     * @readwrite
     * @type boolean
     * @nullable true
     * @length 
     */
    protected $_is_optout;
    
    /**
     * @column
     * @readwrite
     * @type boolean
     * @nullable true
     * @length 
     */
    protected $_is_blacklisted;
    
    /**
     * @column
     * @readwrite
     * @type boolean
     * @nullable true
     * @length 
     */
    protected $_is_hard_bounced;
    
    /**
     * @column
     * @readwrite
     * @type timestamp
     * @nullable true
     * @length 
     */
    protected $_last_action_time;
    
    /**
     * @column
     * @readwrite
     * @type text
     * @nullable true
     * @length 100
     */
    protected $_last_action_type;
    
    /**
     * @column
     * @readwrite
     * @type text
     * @nullable true
     * @length 
     */
    protected $_agent;

    /**
     * @column
     * @readwrite
     * @type text
     * @nullable true
     * @length 100
     */
    protected $_ip;

    /**
     * @column
     * @readwrite
     * @type text
     * @nullable true
     * @length 10
     */
    protected $_country_code;
    
    /**
     * @column
     * @readwrite
     * @type text
     * @nullable true
     * @length
     */
    protected $_country;

    /**
     * @column
     * @readwrite
     * @type text
     * @nullable true
     * @length
     */
    protected $_region;

    /**
     * @column
     * @readwrite
     * @type text
     * @nullable true
     * @length
     */
    protected $_city;

    /**
     * @column
     * @readwrite
     * @type text
     * @nullable true
     * @length 100
     */
    protected $_language;

    /**
     * @column
     * @readwrite
     * @type text
     * @nullable true
     * @length 
     */
    protected $_device_type;

    /**
     * @column
     * @readwrite
     * @type text
     * @nullable true
     * @length 100
     */
    protected $_device_name;

    /**
     * @column
     * @readwrite
     * @type text
     * @nullable true
     * @length 
     */
    protected $_os;

    /**
     * @column
     * @readwrite
     * @type text
     * @nullable true
     * @length 
     */
    protected $_browser_name;

    /**
     * @column
     * @readwrite
     * @type text
     * @nullable true
     * @length 100
     */
    protected $_browser_version;
}


