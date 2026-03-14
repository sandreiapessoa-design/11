<?php declare(strict_types=1); namespace IR\App\Models\Production; if (!defined('IR_START')) exit('<pre>No direct script access allowed</pre>');
/**
 * @framework       iResponse Framework 
 * @version         1.0
 * @author          Amine Idrissi <contact@iresponse.tech>
 * @date            2024
 * @name            AutoResponder.php	
 */

# orm 
use IR\Orm\ActiveRecord as ActiveRecord;

/**
 * @name AutoResponder
 * @description AutoResponder Model
 */
class AutoResponder extends ActiveRecord
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
    protected $_table = 'auto_responders';

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
     * @length 200
     */
    protected $_name;
    
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
    protected $_type;

    /**
     * @column
     * @readwrite
     * @type integer
     * @nullable false
     * @length
     */
    protected $_server_id;
    
    /**
     * @column
     * @readwrite
     * @type text
     * @nullable false
     * @length 200
     */
    protected $_server_name;
    
    /**
     * @column
     * @readwrite
     * @type integer
     * @nullable false
     * @length
     */
    protected $_component_id;
    
    /**
     * @column
     * @readwrite
     * @type text
     * @nullable false
     * @length 200
     */
    protected $_component_name;
    
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
     * @type text
     * @nullable true
     * @length
     */
    protected $_clients_excluded;
    
    /**
     * @column
     * @readwrite
     * @type boolean
     * @nullable true
     * @length 
     */
    protected $_on_open;
     
     /**
     * @column
     * @readwrite
     * @type boolean
     * @nullable true
     * @length 
     */
    protected $_on_click;
     
     /**
     * @column
     * @readwrite
     * @type boolean
     * @nullable true
     * @length 
     */
    protected $_on_unsub;
     
     /**
     * @column
     * @readwrite
     * @type boolean
     * @nullable true
     * @length 
     */
    protected $_on_optout;
    
    /**
     * @column
     * @readwrite
     * @type text
     * @nullable false
     * @length
     */
    protected $_content;
}