<?php declare(strict_types=1); namespace IR\App\Models\Affiliate; if (!defined('IR_START')) exit('<pre>No direct script access allowed</pre>');
/**
 * @framework       iResponse Framework 
 * @version         1.0
 * @author          Amine Idrissi <contact@iresponse.tech>
 * @date            2024
 * @name            Offer.php	
 */

# orm 
use IR\Orm\ActiveRecord as ActiveRecord;

# helpers
use IR\App\Helpers\AuditLog as AuditLog;
use IR\App\Helpers\Authentication as Authentication;
use IR\App\Helpers\Permissions as Permissions;

# utilities
use IR\Utils\Types\Objects as Objects;

/**
 * @name Offer
 * @description Offer Model
 */
class Offer extends ActiveRecord
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
    protected $_schema = 'affiliate';

    /**
     * @table
     * @readwrite
     */
    protected $_table = 'offers';

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
     * @nullable false
     * @length
     */
    protected $_affiliate_network_id;
    
    /**
     * @column
     * @readwrite
     * @type text
     * @nullable false
     * @length 200
     */
    protected $_affiliate_network_name;
    
    /**
     * @column
     * @readwrite
     * @indexed
     * @type text
     * @nullable false
     * @length 200
     */
    protected $_production_id;

    /**
     * @column
     * @readwrite
     * @type text
     * @nullable false
     * @length 200
     */
    protected $_campaign_id;
    
    /**
     * @column
     * @readwrite
     * @type text
     * @nullable true
     * @length
     */
    protected $_verticals_ids;
    
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
     * @type text
     * @nullable false
     * @length
     */
    protected $_countries;

    /**
     * @column
     * @readwrite
     * @type text
     * @nullable true
     * @length 500
     */
    protected $_description;
    
    /**
     * @column
     * @readwrite
     * @type text
     * @nullable true
     * @length 500
     */
    protected $_rules;

    /**
     * @column
     * @readwrite
     * @type date
     * @nullable false
     * @length
     */
    protected $_expiration_date;

    /**
     * @column
     * @readwrite
     * @type text
     * @nullable false
     * @length 10
     */
    protected $_type;

    /**
     * @column
     * @readwrite
     * @type text
     * @nullable false
     * @length 100
     */
    protected $_payout;

    /**
     * @column
     * @readwrite
     * @type text
     * @nullable false
     * @length 200
     */
    protected $_available_days;
    
    /**
     * @column
     * @readwrite
     * @type boolean
     * @nullable false
     * @length
     */
    protected $_auto_sup;
    
    /**
     * @column
     * @readwrite
     * @type text
     * @nullable true
     * @length 500
     */
    protected $_default_suppression_link;
    
    /**
     * @column
     * @readwrite
     * @type timestamp
     * @nullable true
     * @length 
     */
    protected $_last_suppression_updated_date;
    
    /**
     * @column
     * @readwrite
     * @type text
     * @nullable false
     * @length 200
     */
    protected $_created_by;

    /**
     * @column
     * @readwrite
     * @type text
     * @nullable true
     * @length 200
     */
    protected $_last_updated_by;

    /**
     * @column
     * @readwrite
     * @type date
     * @nullable false
     * @length 
     */
    protected $_created_date;

    /**
     * @column
     * @readwrite
     * @type date
     * @nullable true
     * @length 
     */
    protected $_last_updated_date;
    
    # overrided
    
    /**
     * @name first
     * @description returns the first matched record
     * @access static
     * @param integer $format
     * @param array $where
     * @param array $fields
     * @param string $order
     * @param string $direction
     * @return mixed
     */
    public static function first(int $format = ActiveRecord::FETCH_ARRAY, array $where = [], array $fields = ["*"],string $order = '', string $direction = 'ASC')
    {
        $user = Authentication::getAuthenticatedUser();
        
        if($user->getMasterAccess() != 'Enabled')
        {
            Permissions::modelTeamAuthsFilter(__CLASS__,$user,$where);
        }
        
        return parent::first($format,$where, $fields, $order, $direction);
    }

    /**
     * @name all
     * @description creates a query, taking into account the various filters and ﬂags, to return all matching records.
     * @access static
     * @param integer $format
     * @param array $where
     * @param array $fields
     * @param string $order
     * @param string $direction
     * @param integer $limit
     * @param integer $offset
     * @return mixed
     */
    public static function all(int $format = ActiveRecord::FETCH_ARRAY, array $where = [], array $fields = ["*"], string $order = '', string $direction = 'ASC', int $limit = 0, int $offset = 0) 
    {
        $user = Authentication::getAuthenticatedUser();
        
        if($user->getMasterAccess() != 'Enabled')
        {
            Permissions::modelTeamAuthsFilter(__CLASS__,$user,$where);
        }

        return parent::all($format, $where, $fields, $order, $direction, $limit, $offset);
    }
    
    /**
     * @name load
     * @description loads a record if the primary column’s value has been provided
     * @access public
     * @return
     */
    public function load($primayValue = null) 
    {
        $user = Authentication::getAuthenticatedUser();
        
        if($user->getMasterAccess() != 'Enabled')
        {
            $where = [];
            $teamBasedFilterIds = Permissions::modelTeamAuthsFilter(__CLASS__,$user,$where);
            $hasAdminRole = Permissions::hasAdminBasedRole($user);
            
            if($hasAdminRole == false)
            {
                if($primayValue == null)
                {
                    if(in_array($this->_id,$teamBasedFilterIds))
                    {
                        parent::load();
                    }
                }
                else
                {
                    if(in_array($primayValue,$teamBasedFilterIds))
                    {
                        parent::load($primayValue);
                    }
                }
            }
            else
            {
                parent::load($primayValue);
            }
        }
        else
        {
            parent::load($primayValue);
        }
    }
    
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
        AuditLog::registerLog($this->_id,$this->_name,Objects::getInstance()->getName($this),'Insert');
        
        return $this->_id;
    }
    
    /**
     * @name insert 
     * @description creates a record base on the primary key
     * @access public
     * @return integer
     * @throws DatabaseException
     */
    public function update() : int
    {
        # register audit log
        AuditLog::registerLog($this->_id,$this->_name,Objects::getInstance()->getName($this),'Update');
        
        return parent::update();
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
        AuditLog::registerLog($this->_id,$this->_name,Objects::getInstance()->getName($this),'Delete');
        
        # delete offer's assets if any
        FromName::deleteWhere('offer_id = ?',[$this->_id]);
        Subject::deleteWhere('offer_id = ?',[$this->_id]);
        Creative::deleteWhere('offer_id = ?',[$this->_id]);
        Link::deleteWhere('offer_id = ?',[$this->_id]); 
        
        return parent::delete();
    }
}


