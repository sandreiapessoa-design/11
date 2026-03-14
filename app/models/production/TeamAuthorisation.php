<?php declare(strict_types=1); namespace IR\App\Models\Production; if (!defined('IR_START')) exit('<pre>No direct script access allowed</pre>');
/**
 * @framework       iResponse Framework 
 * @version         1.0
 * @author          Amine Idrissi <contact@iresponse.tech>
 * @date            2024
 * @name            TeamAuthorisation.php	
 */

# orm 
use IR\Orm\ActiveRecord as ActiveRecord;

/**
 * @name TeamAuthorisation
 * @description TeamAuthorisation Model
 */
class TeamAuthorisation extends ActiveRecord
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
    protected $_table = 'teams_authorisations';

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
    protected $_team_id;
    
    /**
     * @column
     * @readwrite
     * @indexed
     * @type integer
     * @nullable true
     * @length 
     */
    protected $_team_member_id;
    
    /**
     * @column
     * @readwrite
     * @type text
     * @nullable true
     * @length 
     */
    protected $_vmtas_ids;
    
    /**
     * @column
     * @readwrite
     * @type text
     * @nullable true
     * @length 
     */
    protected $_smtp_servers_ids;
    
    /**
     * @column
     * @readwrite
     * @type text
     * @nullable true
     * @length 
     */
    protected $_offers_ids;
    
    /**
     * @column
     * @readwrite
     * @type text
     * @nullable false
     * @length 
     */
    protected $_data_lists_ids;
}