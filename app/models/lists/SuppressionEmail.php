<?php declare(strict_types=1); namespace IR\App\Models\Lists; if (!defined('IR_START')) exit('<pre>No direct script access allowed</pre>');
/**
 * @framework       iResponse Framework 
 * @version         1.0
 * @author          Amine Idrissi <contact@iresponse.tech>
 * @date            2024
 * @name            SuppressionEmail.php	
 */

# orm 
use IR\Orm\ActiveRecord as ActiveRecord;

/**
 * @name SuppressionEmail
 * @description SuppressionEmail Model
 */
class SuppressionEmail extends ActiveRecord
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
    protected $_schema = 'suppressions';

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
     * @type text
     * @nullable false
     * @length 500
     */
    protected $_email_md5;
}


