<?php declare(strict_types=1); namespace IR\App\Webservices; if (!defined('IR_START')) exit('<pre>No direct script access allowed</pre>');
/**
 * @framework       iResponse Framework 
 * @version         1.0
 * @author          Amine Idrissi <contact@iresponse.tech>
 * @date            2024
 * @name            Linode.php	
 */

# core 
use IR\Core\Base as Base;
use IR\Core\Application as Application;

# models
use IR\App\Models\Admin\LinodeAccount as LinodeAccount;
use IR\App\Models\Admin\LinodeProcess as LinodeProcess;
use IR\App\Models\Admin\Domain as Domain;

# helpers 
use IR\App\Helpers\Authentication as Authentication;
use IR\App\Helpers\Permissions as Permissions;
use IR\App\Helpers\Page as Page;
use IR\App\Helpers\Api as Api;

/**
 * @name Linode
 * @description Linode WebService
 */
class Linode extends Base
{
    /**
     * @app
     * @readwrite
     */
    protected $app;
    
    /**
     * @name init
     * @description initializing process before the action method executed
     * @once
     * @protected
     */
    public function init() 
    {
        # set the current application to a local variable
        $this->app = Application::getCurrent();
    }
    
    /**
     * @name createInstances
     * @description create instances
     * @before init
     */
    public function createInstances($parameters = []) 
    { 
        # check for authentication
        if(!Authentication::isUserAuthenticated())
        {
            Page::printApiResults(401,'Only logged-in access allowed !');
        }
        
        # check users roles 
        Authentication::checkUserRoles();
        
        # check for permissions
        $access = Permissions::checkForAuthorization(Authentication::getAuthenticatedUser(),'LinodeInstances','create');

        if($access == false)
        {
            Page::printApiResults(403,'Access Denied !');
        }

        $account = LinodeAccount::first(LinodeAccount::FETCH_ARRAY,['id = ?',intval($this->app->utils->arrays->get($parameters,'account-id',0))],['id']);
            
        if(!is_array($account) || count($account) == 0)
        {
            Page::printApiResults(500,'Account not found !');
        }

        $nbInstances = intval($this->app->utils->arrays->get($parameters,'nb-of-instances',0));
        
        if($nbInstances == 0)
        {
            Page::printApiResults(500,'Please provide a number of instances to create !');
        }
        
        $domains = $this->app->utils->arrays->get($parameters,'domains',[]);

        if(!is_array($domains) || count($domains) == 0)
        {
            Page::printApiResults(500,'Please provide at least one region !');
        }

        $region = $this->app->utils->arrays->get($parameters,'region','');
        
        if($region == null || $region == '')
        {
            Page::printApiResults(500,'Please provide a region !');
        }

        $os = $this->app->utils->arrays->get($parameters,'os','');
        
        if($os == null || $os == '')
        {
            Page::printApiResults(500,'Please provide an operating system to install with !');
        }
        
        $size = $this->app->utils->arrays->get($parameters,'size','');
        
        if($size == null || $size == '')
        {
            Page::printApiResults(500,'Please provide an instance size to install with !');
        }
        
        # create a process object
        $process = new LinodeProcess();
        $process->setStatus('In Progress');
        $process->setAccountId($account['id']);
        $process->setRegion($region);
        $process->setNbInstances($nbInstances);
        $process->setDomains(implode(',',$domains));
        $process->setOs($os);
        $process->setSize($size);
        $process->setProgress('0%');
        $process->setInstancesCreated('0');
        $process->setInstancesInstalled('0');
        $process->setStartTime(date('Y-m-d H:i:s'));    
        $process->setFinishTime(null);    

        # call iresponse api
        Api::call('Linode','createInstances',['process-id' => $process->insert()],true,LOGS_PATH . DS . 'cloud_apis' . DS . 'inst_lind_' . $account['id'] . '.log');
        Page::printApiResults(200,'Instances Creation process(es) started');
    }
    
    /**
     * @name stopInstancesProcesses
     * @description stop aws instances creation processes action
     * @before init
     */
    public function stopInstancesProcesses($parameters = []) 
    { 
        # check for authentication
        if(!Authentication::isUserAuthenticated())
        {
            Page::printApiResults(401,'Only logged-in access allowed !');
        }

        # check users roles 
        Authentication::checkUserRoles();
        
        # check for permissions
        $access = Permissions::checkForAuthorization(Authentication::getAuthenticatedUser(),'LinodeInstances','create');

        if($access == false)
        {
            Page::printApiResults(403,'Access Denied !');
        }
        
        $processesIds = $this->app->utils->arrays->get($parameters,'processes-ids',[]);

        if(!is_array($processesIds) || count($processesIds) == 0)
        {
            Page::printApiResults(500,'No processes found !');
        }
        
        # call iresponse api
        $result = Api::call('Linode','stopProcesses',['processes-ids' => $processesIds]);

        if(count($result) == 0)
        {
            Page::printApiResults(500,'No response found !');
        }

        if($result['httpStatus'] == 500)
        {
            Page::printApiResults(500,$result['message']);
        }
            
        Page::printApiResults(200,$result['message']);
    }
    
    /**
     * @name executeInstancesActions
     * @description execute aws instances actions
     * @before init
     */
    public function executeInstancesActions($parameters = []) 
    { 
        # check for authentication
        if(!Authentication::isUserAuthenticated())
        {
            Page::printApiResults(401,'Only logged-in access allowed !');
        }

        # check users roles 
        Authentication::checkUserRoles();
        
        # check for permissions
        $access = Permissions::checkForAuthorization(Authentication::getAuthenticatedUser(),'LinodeInstances','main');

        if($access == false)
        {
            Page::printApiResults(403,'Access Denied !');
        }

        $instancesIds = $this->app->utils->arrays->get($parameters,'instances-ids',[]);

        if(!is_array($instancesIds) || count($instancesIds) == 0)
        {
            Page::printApiResults(500,'No processes found !');
        }
        
        $action = $this->app->utils->arrays->get($parameters,'action','');
        
        if($action == null || $action == '')
        {
            Page::printApiResults(500,'Please provide an action !');
        }
        
        # call iresponse api
        $result = Api::call('Linode','executeInstancesActions',['instances-ids' => $instancesIds,'action' => $action]);

        if(count($result) == 0)
        {
            Page::printApiResults(500,'No response found !');
        }

        if($result['httpStatus'] == 500)
        {
            Page::printApiResults(500,$result['message']);
        }
            
        Page::printApiResults(200,$result['message']);
    }
    
    /**
     * @name getAccountDomains
     * @description get account domains action
     * @before init
     */
    public function getAccountDomains($parameters = []) 
    { 
        # check for authentication
        if(!Authentication::isUserAuthenticated())
        {
            Page::printApiResults(401,'Only logged-in access allowed !');
        }
        
        # check users roles 
        Authentication::checkUserRoles();
        
        # check for permissions
        $access = Permissions::checkForAuthorization(Authentication::getAuthenticatedUser(),'LinodeInstances','create');

        if($access == false)
        {
            Page::printApiResults(403,'Access Denied !');
        }
        
        $parts = explode('|',$this->app->utils->arrays->get($parameters,'account'));

        if(count($parts) != 2)
        {
            Page::printApiResults(500,'Incorrect account !');
        }
        
        $accountId = intval($parts[1]);
        $accountType = $parts[0];
        
        if($accountId > 0 || $accountType == 'none')
        {
            $where = $accountType == 'none' ? ['status = ? and account_type = ? and availability = ?',['Activated',$accountType,'Available']] :
            ['status = ? and account_id = ? and account_type = ? and availability = ?',['Activated',$accountId,$accountType,'Available']];
            $domains = Domain::all(Domain::FETCH_ARRAY,$where,['id','value']);
            
            if(count($domains) == 0)
            {
                Page::printApiResults(500,'Domains not found !');
            }

            Page::printApiResults(200,'',['domains' => $domains]);
        }
        else
        {
            Page::printApiResults(500,'Incorrect account id !');
        }
    }
}


