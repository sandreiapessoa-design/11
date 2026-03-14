<?php declare(strict_types=1); namespace IR\App\Models\Actions; if (!defined('IR_START')) exit('<pre>No direct script access allowed</pre>');
/**
 * @framework       iResponse Framework 
 * @version         1.0
 * @author          Amine Idrissi <contact@iresponse.tech>
 * @date            2024
 * @name            Lead.php	
 */

# orm 
use IR\Orm\ActiveRecord as ActiveRecord;

/**
 * @name Lead
 * @description Lead Model
 */
class Lead extends ActiveRecord {

    /**
     * @database
     * @readwrite
     */
    protected $_databaseKey = 'system';

    /**
     * @schema
     * @readwrite
     */
    protected $_schema = 'actions';

    /**
     * @table
     * @readwrite
     */
    protected $_table = 'leads';

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
     * @type text
     * @nullable false
     * @length
     */
    protected $_unique_token;

    /**
     * @column
     * @readwrite
     * @indexed
     * @type integer
     * @nullable false
     * @length
     */
    protected $_process_id;
    
    /**
     * @column
     * @readwrite
     * @indexed
     * @type text
     * @nullable false
     * @length 10
     */
    protected $_process_type;
    
    /**
     * @column
     * @readwrite
     * @indexed
     * @type integer
     * @nullable false
     * @length 
     */
    protected $_user_production_id;
    
    /**
     * @column
     * @readwrite
     * @indexed
     * @type text
     * @nullable false
     * @length 500
     */
    protected $_user_full_name;
    
    /**
     * @column
     * @readwrite
     * @indexed
     * @type integer
     * @nullable true
     * @length
     */
    protected $_vmta_id;

    /**
     * @column
     * @readwrite
     * @indexed
     * @type integer
     * @nullable true
     * @length
     */
    protected $_smtp_user_id;
    
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
     * @indexed
     * @type text
     * @nullable false
     * @length 200
     */
    protected $_offer_production_id;

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
     * @type integer
     * @nullable false
     * @length
     */
    protected $_client_id;

    /**
     * @column
     * @readwrite
     * @type timestamp
     * @nullable false
     * @length
     */
    protected $_action_time;

    /**
     * @column
     * @readwrite
     * @type text
     * @nullable false
     * @length 50
     */
    protected $_payout;

    /**
     * @column
     * @readwrite
     * @type boolean
     * @nullable true
     * @length
     */
    protected $_process_updated;
    
    /**
     * @column
     * @readwrite
     * @type text
     * @nullable true
     * @length 500
     */
    protected $_agent;

    /**
     * @column
     * @readwrite
     * @type text
     * @nullable true
     * @length 15
     */
    protected $_action_ip;

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
     * @length 100
     */
    protected $_country;

    /**
     * @column
     * @readwrite
     * @type text
     * @nullable true
     * @length 100
     */
    protected $_region;

    /**
     * @column
     * @readwrite
     * @type text
     * @nullable true
     * @length 100
     */
    protected $_city;

    /**
     * @column
     * @readwrite
     * @type text
     * @nullable true
     * @length 100
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
     * @length 50
     */
    protected $_operating_system;

    /**
     * @column
     * @readwrite
     * @type text
     * @nullable true
     * @length 50
     */
    protected $_browser_name;

    /**
     * @column
     * @readwrite
     * @type text
     * @nullable true
     * @length 50
     */
    protected $_browser_version;
}
