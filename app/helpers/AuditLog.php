<?php declare(strict_types=1); namespace IR\App\Helpers; if (!defined('IR_START')) exit('<pre>No direct script access allowed</pre>');
/**
 * @framework       iResponse Framework 
 * @version         1.0
 * @author          Amine Idrissi <contact@iresponse.tech>
 * @date            2024
 * @name            AuditLog.php	
 */

# model 
use IR\App\Models\Admin\AuditLog as Log;

/**
 * @name AuditLog
 * @description AuditLog Helper
 */
class AuditLog
{
    /**
     * @name registerLog
     * @description register audit log
     * @access public
     * @return
     */
    public static function registerLog(int $recordId,$recordName,string $recordType,string $actionType) 
    {
        $log = new Log();
        
        if(Authentication::isUserAuthenticated())
        {
            $log->setActionBy(Authentication::getAuthenticatedUser()->getFirstName() . ' ' . Authentication::getAuthenticatedUser()->getLastName());
        }
        else
        {
            $log->setActionBy('Unauthorized User');
        }
        
        if($recordName != null && is_string($recordName) &&  $recordName != '')
        {
            $log->setRecordName($recordName);
        }
        else
        {
            $log->setRecordName('No Name');
        }
        
        $log->setRecordId($recordId);
        $log->setRecordType($recordType);
        $log->setActionType($actionType);
        $log->setActionTime(date('Y-m-d H:i:s'));
        $log->insert();
    }
      
    /**
     * @name __construct
     * @description private constructor to prevent it being created directly
     * @access private
     * @return
     */ 
    private function __construct()  
    {}  

    /**
     * @name __clone
     * @description private clone to prevent it being cloned directly
     * @access private
     * @return
     */ 
    private function __clone()  
    {}
}


