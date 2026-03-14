<?php declare(strict_types=1); namespace IR\App\Helpers; if (!defined('IR_START')) exit('<pre>No direct script access allowed</pre>');
/**
 * @framework       iResponse Framework 
 * @version         1.0
 * @author          Amine Idrissi <contact@iresponse.tech>
 * @date            2024
 * @name            DataTable.php	
 */

# core 
use IR\Core\Application as Application;
use IR\Orm\Database as Database;

/**
 * @name DataTable
 * @description DataTable Helper
 */
class DataTable
{
    /**
     * @name init
     * @description init and fill an AJAX Html Table
     * @access static
     * @return array
     */
    public static function init($data,$table,$columns,$object,$url,$order = 'ASC',$preResult = null,$edit = true,$disableFilter = false) : array
    {
        $records = [];
        $user = Authentication::getAuthenticatedUser();
        $url = Application::getCurrent()->http->request->getBaseURL() . RDS . $url;
        $alias = Application::getCurrent()->utils->strings->contains($table,' ') ? Application::getCurrent()->utils->arrays->last(explode(' ',$table)) . '.' : '';
        $results = $preResult != null ? $preResult : Database::retreive('system')->query()->from($table,$columns);
        $count = clone $results;
        $count->from($table,['1']);
        $customActionType = Application::getCurrent()->utils->arrays->get($data,'customActionType','');
        $customActionName = Application::getCurrent()->utils->arrays->get($data,'customActionName','');
        
        $start = intval(Application::getCurrent()->utils->arrays->get($data,'start'));
        $total = intval(Application::getCurrent()->utils->arrays->get($data,'length'));
        $draw = intval(Application::getCurrent()->utils->arrays->get($data,'draw'));
        $action = Application::getCurrent()->utils->arrays->get($data,'action','');
        $class = '';
        $condition = '';
        $values = [];
        
        if ($customActionType == "group_action") 
        {
            $ids = Application::getCurrent()->utils->arrays->get($data,'id',''); 
            
            $records["customActionStatus"] = "Error";
            $records["customActionMessage"] = "Internal server error !";
            $method = 'edit';
            
            if(count($ids) && $object != null)
            {
                foreach ($ids as $id)
                {
                   $id = intval(trim($id));
                   
                   if($id > 0)
                   {
                        $object->setId($id);
                        $object->load();
                        
                        # get class name
                        $class = Application::getCurrent()->utils->objects->getName($object);
                        $controller = key_exists($class,Permissions::$_MODEL_CONTOLLER_MAPPING) ? Permissions::$_MODEL_CONTOLLER_MAPPING[$class] : $controller;

                        switch ($customActionName)
                        {
                            case 'special':
                            {
                                # check for permissions
                                $access = Permissions::checkForAuthorization(Authentication::getAuthenticatedUser(),$controller,$method);
                                
                                if($access == false)
                                {
                                    $records["customActionStatus"] = "Error";
                                    $records["customActionMessage"] = "Operation Denied !";
                                }
                                else
                                {
                                    $object->setStatus('Special');
                                    $object->setAvailability('Available');
                                    $object->setMtaServerId(0);
                                    $object->setIpId(0);
                                    $object->update();

                                    $records["customActionStatus"] = "OK";
                                    $records["customActionMessage"] = "Records set special !";
                                }
                                
                                break;
                            }
                            case 'available':
                            {
                                # check for permissions
                                $access = Permissions::checkForAuthorization(Authentication::getAuthenticatedUser(),$controller,$method);
                                
                                if($access == false)
                                {
                                    $records["customActionStatus"] = "Error";
                                    $records["customActionMessage"] = "Operation Denied !";
                                }
                                else
                                {
                                    $object->setStatus('Activated');
                                    $object->setAvailability('Available');
                                    $object->setMtaServerId(0);
                                    $object->setIpId(0);
                                    $object->update();

                                    $records["customActionStatus"] = "OK";
                                    $records["customActionMessage"] = "Records set available !";
                                }
                                
                                break;
                            }
                            case 'activate':
                            {
                                # check for permissions
                                $access = Permissions::checkForAuthorization(Authentication::getAuthenticatedUser(),$controller,$method);
                                
                                if($access == false)
                                {
                                    $records["customActionStatus"] = "Error";
                                    $records["customActionMessage"] = "Operation Denied !";
                                }
                                else
                                {
                                    $object->setStatus('Activated');
                                    $object->update();

                                    $records["customActionStatus"] = "OK";
                                    $records["customActionMessage"] = "Records activated !";
                                }
                                
                                break;
                            }
                            case 'Inactivate':
                            {
                                # check for permissions
                                $access = Permissions::checkForAuthorization(Authentication::getAuthenticatedUser(),$controller,$method);

                                if($access == false)
                                {
                                    $records["customActionStatus"] = "Error";
                                    $records["customActionMessage"] = "Operation Denied !";
                                }
                                else
                                {
                                    $object->setStatus('Inactivated');
                                    $object->update();

                                    $records["customActionStatus"] = "OK";
                                    $records["customActionMessage"] = "Records Inactivated !";
                                }
                                
                                break;
                            }
                            case 'delete':
                            {  
                                # check for permissions
                                $method = $method == 'proxies' ? $method : 'delete';
                                $access = Permissions::checkForAuthorization(Authentication::getAuthenticatedUser(),$controller,$method);
                                
                                if($access == false)
                                {
                                    $records["customActionStatus"] = "Error";
                                    $records["customActionMessage"] = "Operation Denied !";
                                }
                                else
                                {
                                    # delete the object
                                    if($class != 'ProxyServer' && $class != 'Mailbox')
                                    {       
                                        $object->delete();
                                    }
                                    
                                    $records["customActionStatus"] = "OK";
                                    $records["customActionMessage"] = "Record(s) deleted successfully !";
                                }
                                
                                break;
                            }
                            default :
                            {
                                $records["customActionStatus"] = "ERROR";
                                $records["customActionMessage"] = "Unsupported action !";
                                
                                break;
                            }
                        }
                   }
                }
                
                # special cases of removing
                if($customActionName == 'delete')
                {
                    $access = false;

                    switch ($class)
                    {
                        case 'ProxyServer' :
                        {
                            $access = Permissions::checkForAuthorization(Authentication::getAuthenticatedUser(),'MtaServers','proxies');
                                
                            if($access == false)
                            {
                                $records["customActionStatus"] = "Error";
                                $records["customActionMessage"] = "Operation Denied !";
                            }
                            else
                            {
                                # uninstall proxy servers
                                Api::call('Servers','uninstallProxies',['proxies-ids' => $ids]);
                                break;
                            }
                        }
                        case 'Mailbox' :
                        {
                            $access = Permissions::checkForAuthorization(Authentication::getAuthenticatedUser(),'Mailboxes','delete');
                                
                            if($access == false)
                            {
                                $records["customActionStatus"] = "Error";
                                $records["customActionMessage"] = "Operation Denied !";
                            }
                            else
                            {
                                # uninstall proxy servers
                                Api::call('Mailboxes','removeMailboxes',['mailboxes-ids' => $ids]);
                                break;
                            }
                            break;
                        }
                    }
                    
                    if($access == true)
                    {
                        foreach ($ids as $id)
                        {
                           $id = intval(trim($id));

                           if($id > 0)
                           {
                                $object->setId($id);
                                $object->load();
                                $object->delete();
                           }
                        }
                        
                        $records["customActionStatus"] = "OK";
                        $records["customActionMessage"] = "Record(s) deleted successfully !";
                    }
                }
            }
        }
        else if($action == 'filter')
        {
            foreach ($columns as $key => $val)
            {
                $column = is_string($key) ? $key : $val;
               
                if(strpos($column,'_date') > -1 || strpos($column,'_time') > -1)
                {
                    $range = explode('|',Application::getCurrent()->utils->arrays->get($data,$val . '_range',''));
                    $from = Application::getCurrent()->utils->strings->trim(Application::getCurrent()->utils->arrays->get($range,0));
                    $to = Application::getCurrent()->utils->strings->trim(Application::getCurrent()->utils->arrays->get($range,1));
                    
                    if($from != '' && $to != '')
                    {
                        $column = (strpos($val,'_date') > -1 || strpos($val,'_time') > -1) ? $val : $column;
                        $condition .= " AND TO_DATE(to_char($column,'YYYY-MM-DD'),'YYYY-MM-DD') between to_timestamp('$from', 'YYYY-MM-DD') and to_timestamp('$to', 'YYYY-MM-DD') ";
                    }
                }
                else
                {
                    $value = Application::getCurrent()->utils->arrays->get($data,$val,'');
                   
                    if($value != '')
                    {
                        if(is_numeric($value))
                        {
                            $condition .= " AND $column = ? ";
                            $values[] = $value;
                        }
                        else
                        {
                            if($column == 'status')
                            {
                                $condition .= " AND $column = ? ";
                                $values[] = $value;
                            }
                            else
                            {
                                $condition .= " AND LOWER(CAST($column AS TEXT)) LIKE LOWER('%$value%') ";
                            }
                        }
                    }
                } 
            } 
        }

        $records["data"] = []; 
        
        if($condition != '')
        {
            $results->where(trim(trim($condition),'AND'),$values);
            $count->where(trim(trim($condition),'AND'),$values);
        }
        
        if($total > 0)
        {
            $results->limit($total,$start);
        }

        $orderFilter = Application::getCurrent()->utils->arrays->get($data,'order');

        if(count($orderFilter))
        {
            $tmpColumns = [];
            
            foreach ($columns as $column) 
            {
                $tmpColumns[] = $column;
            }
            
            $col = $tmpColumns[intval($orderFilter[0]['column']) -1];
            $results = $results->order($col,strtoupper($orderFilter[0]['dir']));
        }
        else
        {
            $results = $results->order("id",$order); 
        }
        
        $model = Application::getCurrent()->utils->objects->getName($object); 

        if($user->getMasterAccess() != 'Enabled' && in_array($model,Permissions::$_TEAM_BASED_MODELS) && $disableFilter == false)
        {
            $where = [];
            $teamBasedFilterIds = [];
            $hasAdminRole = Permissions::hasAdminBasedRole($user);
            $teamBasedFilterIds = Permissions::modelTeamAuthsFilter($model,$user,$where);

            if($hasAdminRole == false)
            {
                if(count($teamBasedFilterIds))
                {
                    $results->where($alias . 'id IN ?',[$teamBasedFilterIds]);
                    $count->where($alias . 'id IN ?',[$teamBasedFilterIds]);
                }
            }

            $results = ($hasAdminRole == false && count($teamBasedFilterIds) == 0) ? [] : $results->all();
            $count = ($hasAdminRole == false && count($teamBasedFilterIds) == 0) ? 0 : count($count->all());
        }
        else
        {
            $results = $results->all();
            $count = count($count->all());
        }

        foreach ($results as $row)
        {
            if(key_exists('id',$row))
            {
                $record = ['<label class="mt-checkbox mt-checkbox-single mt-checkbox-outline" style="top: -3px;"><input name="id[]" type="checkbox" class="checkboxes" value="'.$row['id'].'"/><span></span></label>'];
            }
            else
            {
                $record = ['<label class="mt-checkbox mt-checkbox-single mt-checkbox-outline" style="top: -3px;"><input name="id[]" type="checkbox" class="checkboxes" value=""/><span></span></label>'];
            }
            
            foreach ($columns as $column)
            {
                if(strpos($column,'_date') > -1 && $row[$column] != null && $row[$column] != '')
                {
                    $data = date('Y-m-d',strtotime(strval($row[$column])));
                }
                elseif(strpos($column,'_time') > -1 && $row[$column] != null && $row[$column] != '')
                {
                    $data = date('Y-m-d H:i',strtotime(strval($row[$column])));
                }
                else
                {
                    $data = strval($row[$column]);
                }
                
                $data = strlen(strip_tags($data)) >= 30 ? str_replace(strip_tags($data),substr(strip_tags($data),0,30),$data) . '...' : $data;
                $record[] = $data;      
            }
 
            if($edit == true)
            {
                $record[] = '<div class="margin-bottom-5" style="text-align: center;padding-top:2px;margin-right: 10px;"><a href="' . $url . RDS . 'edit' . RDS . $row['id'] . '.html" class="font-grey-mint margin-bottom"><i class="fa fa-edit" style="font-size:13px"></i></a></div>';
            }
            else
            {
                $record[] = '<div class="margin-bottom-5" style="text-align: center;padding-top:2px;margin-right: 10px;">&nbsp;</div>';
            }
                                    
            $records["data"][] = $record;
        }

        $records["draw"] = $draw;
        $records["recordsTotal"] = $count;
        $records["recordsFiltered"] = $count;
        
        return $records;
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