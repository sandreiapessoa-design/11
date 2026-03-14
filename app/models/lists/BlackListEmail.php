<?php declare(strict_types=1); namespace IR\App\Models\Lists; if (!defined('IR_START')) exit('<pre>No direct script access allowed</pre>');
/**
 * @framework       iResponse Framework 
 * @version         1.0
 * @author          Amine Idrissi <contact@iresponse.tech>
 * @date            2024
 * @name            BlackListEmail.php	
 */

# orm 
use IR\Orm\ActiveRecord as ActiveRecord;

/**
 * @name BlackListEmail
 * @description BlackListEmail Model
 */
class BlackListEmail extends ActiveRecord
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
    protected $_schema = 'specials';

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
     * @unique
     * @type text
     * @nullable false
     * @length 500
     */
    protected $_email_md5;
}


