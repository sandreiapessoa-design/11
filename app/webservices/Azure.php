<?php declare(strict_types=1); namespace IR\App\Webservices; if (!defined('IR_START')) exit('<pre>No direct script access allowed</pre>');
/**
 * @framework       iResponse Framework 
 * @version         1.0
 * @author          H1 <h1@live.fi>
 * @date            2021
 * @name            Azure.php	
 */

# core 
use IR\Core\Base as Base;
use IR\Core\Application as Application; 

# models
use IR\App\Models\Admin\AzureAccount as AzureAccount;
use IR\App\Models\Admin\AzureProcess as AzureProcess;
use IR\App\Models\Admin\AzureAccountProcess as AzureAccountProcess;
use IR\App\Models\Admin\Domain as Domain;

# helpers 
use IR\App\Helpers\Authentication as Authentication;
use IR\App\Helpers\Permissions as Permissions;
use IR\App\Helpers\Page as Page;
use IR\App\Helpers\Api as Api;

/**
 * @name Azure
 * @description Azure WebService
 */
class Azure extends Base
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
     * @description create ec2 instances
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
        $access = Permissions::checkForAuthorization(Authentication::getAuthenticatedUser(),'AzureInstances','create');

        if($access == false)
        {
            Page::printApiResults(403,'Access Denied !');
        }

        $account = AzureAccount::first(AzureAccount::FETCH_ARRAY,['id = ?',intval($this->app->utils->arrays->get($parameters,'account-id',0))],['id','name']);
            
        if(!is_array($account) || count((array)$account) == 0)
        {
            Page::printApiResults(500,'Account not found !');
        }
        
        $regions = $this->app->utils->arrays->get($parameters,'regions',[]);

        if(!is_array($regions) || count((array)$regions) == 0)
        {
            Page::printApiResults(500,'Please provide at least one region !');
        }
        
        $nbInstances = intval($this->app->utils->arrays->get($parameters,'nb-of-instances',0));
        
        if($nbInstances == 0)
        {
            Page::printApiResults(500,'Please provide a number of instances to create !');
        }
        
        $nbIps = intval($this->app->utils->arrays->get($parameters,'nb-of-ips',0));
        
        if($nbIps == 0)
        {
            Page::printApiResults(500,'Please provide a number of ips to assign !');
        }

        $domains = $this->app->utils->arrays->get($parameters,'domains',[]);

        if(!is_array($domains) || count((array)$domains) == 0)
        {
            $domains = 'rdns';
        }
        else
        {
            $domains = implode(',',$domains);
        }

        $type = $this->app->utils->arrays->get($parameters,'instance-type','');
        
        if($type == null || $type == '')
        {
            Page::printApiResults(500,'Please provide an instance type to install with !');
        }

        $pmtaVersion = $this->app->utils->arrays->get($parameters,'pmta-version','');
        
        if($pmtaVersion == null || $pmtaVersion == '')
        {
            Page::printApiResults(500,'Please provide pmta to install with !');
        }

        $cronie = $this->app->utils->arrays->get($parameters,'cronie','enabled');
        
        # create a process object
        $process = new AzureProcess();
        $process->setStatus('In Progress');
        $process->setAccountId($account['id']);
        $process->setAccountName($account['name']);
        $process->setRegion(implode(',',$regions));
        $process->setNbInstances($nbInstances);
        $process->setNbPrivateIps($nbIps);
        $process->setDomains($domains);
        $process->setInstanceType($type);
        $process->setProgress('0%');
        $process->setInstancesCreated('0');
        $process->setInstancesInstalled('0');
        $process->setStartTime(date('Y-m-d H:i:s'));    
        $process->setFinishTime(null);    

        # call azure api
        Api::call('Azure','createInstances',['process-id' => $process->insert(),'prefixes-length' => 16, 'use-prefixes' => 'disabled', 'enable-crons' => $cronie, 'pmta-version' => $pmtaVersion],true,LOGS_PATH . DS . 'cloud_apis' . DS . 'inst_azure_' . $account['id'] . '.log');
        Page::printApiResults(200,'Instances Creation process(es) started');
    }
    
    /**
     * @name stopInstancesProcesses
     * @description stop azure instances creation processes action
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
        $access = Permissions::checkForAuthorization(Authentication::getAuthenticatedUser(),'AzureInstances','create');

        if($access == false)
        {
            Page::printApiResults(403,'Access Denied !');
        }
        
        $processesIds = $this->app->utils->arrays->get($parameters,'processes-ids',[]);

        if(!is_array($processesIds) || count((array)$processesIds) == 0)
        {
            Page::printApiResults(500,'No processes found !');
        }
        
        # call azure api
        $result = Api::call('Azure','stopProcesses',['processes-ids' => $processesIds]);

        if(count((array)$result) == 0)
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
     * @description execute azure instances actions
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
        $access = Permissions::checkForAuthorization(Authentication::getAuthenticatedUser(),'AzureInstances','main');

        if($access == false)
        {
            Page::printApiResults(403,'Access Denied !');
        }

        $instancesIds = $this->app->utils->arrays->get($parameters,'instances-ids',[]);

        if(!is_array($instancesIds) || count((array)$instancesIds) == 0)
        {
            Page::printApiResults(500,'No processes found !');
        }
        
        $action = $this->app->utils->arrays->get($parameters,'action','');
        
        if($action == null || $action == '')
        {
            Page::printApiResults(500,'Please provide an action !');
        }
		
		$changeips = $this->app->utils->arrays->get($parameters,'change-ips','');
        
        # call azure api
        $result = Api::call('Azure','executeInstancesActions',['instances-ids' => $instancesIds,'action' => $action, 'change-ips' => $changeips]);

        if(count((array)$result) == 0)
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
     * @name stopAccountsProcesses
     * @description stop accounts actions
     * @before init
     */
    public function stopAccountsProcesses($parameters = []) 
    { 
        # check for authentication
        if(!Authentication::isUserAuthenticated())
        {
            Page::printApiResults(401,'Only logged-in access allowed !');
        }

        # check users roles 
        Authentication::checkUserRoles();
        
        # check for permissions
        $access = Permissions::checkForAuthorization(Authentication::getAuthenticatedUser(),'AzureAccounts','edit');

        if($access == false)
        {
            Page::printApiResults(403,'Access Denied !');
        }
        
        $processesIds = $this->app->utils->arrays->get($parameters,'processes-ids',[]);

        if(!is_array($processesIds) || count((array)$processesIds) == 0)
        {
            Page::printApiResults(500,'No processes found !');
        }
        
        # call azure api
        $result = Api::call('Azure','stopAccountsProcesses',['processes-ids' => $processesIds]);

        if(count((array)$result) == 0)
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
     * @name executeAutoRestarts
     * @description execute instances restarts actions
     * @before init
     */
    public function executeAutoRestarts($parameters = [])
    { 
        # check for authentication
        if(!Authentication::isUserAuthenticated())
        {
            Page::printApiResults(401,'Only logged-in access allowed !');
        }

        # check users roles 
        Authentication::checkUserRoles();
        
        # check for permissions
        $access = Permissions::checkForAuthorization(Authentication::getAuthenticatedUser(),'AzureAccounts','main');

        if($access == false)
        {
            Page::printApiResults(403,'Access Denied !');
        }

        $accountsIds = $this->app->utils->arrays->get($parameters,'accounts-ids',[]);

        if(!is_array($accountsIds) || count((array)$accountsIds) == 0)
        {
            Page::printApiResults(500,'No accounts found !');
        }
        
        $region = $this->app->utils->arrays->get($parameters,'region','');

        if($region == null || $region == '')
        {
            Page::printApiResults(500,'Please provide a region !');
        }

        $timeUnit = $this->app->utils->arrays->get($parameters,'time-unit','');
        if($timeUnit == null || $timeUnit == '')
        {
            Page::printApiResults(500,'Please select a time unit !');
        }

        $timeValue = $this->app->utils->arrays->get($parameters,'time-value',0);
        if($timeValue == null || $timeValue == '' || $timeValue == 0)
        {
            Page::printApiResults(500,'Please eter a valid a time value !');
        }

        foreach ($accountsIds as $accountId)
        {
            if(intval($accountId) > 0)
            {
                $account = AzureAccount::first(AzureAccount::FETCH_ARRAY,['id = ?',intval($accountId)],['id','name']);
            
                if(!is_array($account) || count((array)$account) == 0)
                {
                    Page::printApiResults(500,'Account not found !');
                }

                # create a process object
                $process = new AzureAccountProcess();
                $process->setStatus('In Progress');
                $process->setProcessType('auto-restarts');
                $process->setAccountId($account['id']);
                $process->setAccountName($account['name']);
                $process->setRegions($region);
                $process->setProcesstimeUnit($timeUnit);
                $process->setProcesstimeValue($timeValue);
                $process->setStartTime(date('Y-m-d H:i:s'));
                $process->setFinishTime(null);
                
                # call azure api
                Api::call('Azure','executeAutoRestarts',['process-id' => $process->insert()],true,LOGS_PATH . DS . 'azure_restarts' . DS . 'res_azure_' . $accountId . '.log');
            }
        }

        Page::printApiResults(200,"Process started successfully !");
    }

    /**
     * @name executeAutoChangeIps
     * @description execute instances restarts actions
     * @before init
     */
    public function executeAutoChangeIps($parameters = [])
    {
        # check for authentication
        if(!Authentication::isUserAuthenticated())
        {
            Page::printApiResults(401,'Only logged-in access allowed !');
        }

        # check users roles
        Authentication::checkUserRoles();

        # check for permissions
        $access = Permissions::checkForAuthorization(Authentication::getAuthenticatedUser(),'AzureAccounts','main');

        if($access == false)
        {
            Page::printApiResults(403,'Access Denied !');
        }

        $accountsIds = $this->app->utils->arrays->get($parameters,'accounts-ids',[]);

        if(!is_array($accountsIds) || count((array)$accountsIds) == 0)
        {
            Page::printApiResults(500,'No accounts found !');
        }

        $region = $this->app->utils->arrays->get($parameters,'region','');

        if($region == null || $region == '')
        {
            Page::printApiResults(500,'Please provide a region !');
        }

        $timeUnit = $this->app->utils->arrays->get($parameters,'time-unit','');
        if($timeUnit == null || $timeUnit == '')
        {
            Page::printApiResults(500,'Please select a time unit !');
        }

        $timeValue = $this->app->utils->arrays->get($parameters,'time-value',0);
        if($timeValue == null || $timeValue == '' || $timeValue == 0)
        {
            Page::printApiResults(500,'Please eter a valid a time value !');
        }

        foreach ($accountsIds as $accountId)
        {
            if(intval($accountId) > 0)
            {
                $account = AzureAccount::first(AzureAccount::FETCH_ARRAY,['id = ?',intval($accountId)],['id','name']);

                if(!is_array($account) || count((array)$account) == 0)
                {
                    Page::printApiResults(500,'Account not found !');
                }

                # create a process object
                $process = new AzureAccountProcess();
                $process->setStatus('In Progress');
                $process->setProcessType('start-change-ips');
                $process->setAccountId($account['id']);
                $process->setAccountName($account['name']);
                $process->setRegions($region);
                $process->setProcesstimeUnit($timeUnit);
                $process->setProcesstimeValue($timeValue);
                $process->setStartTime(date('Y-m-d H:i:s'));
                $process->setFinishTime(null);

                # call azure api
                Api::call('Azure','executeAutoChangeIps',['process-id' => $process->insert()],true,LOGS_PATH . DS . 'azure_restarts' . DS . 'res_azure_' . $accountId . '.log');
            }
        }

        Page::printApiResults(200,"Process started successfully !");
    }
    
    /**
     * @name executeChangeIps
     * @description refresh instances ips actions
     * @before init
     */
    public function executeChangeIps($parameters = []) 
    { 
        # check for authentication
        if(!Authentication::isUserAuthenticated())
        {
            Page::printApiResults(401,'Only logged-in access allowed !');
        }

        # check users roles 
        Authentication::checkUserRoles();
        
        # check for permissions
        $access = Permissions::checkForAuthorization(Authentication::getAuthenticatedUser(),'AzureInstances','main');

        if($access == false)
        {
            Page::printApiResults(403,'Access Denied !');
        }
		
		$instancesIds = $this->app->utils->arrays->get($parameters,'instances-ids',[]);

        if(!is_array($instancesIds) || count((array)$instancesIds) == 0)
        {
            Page::printApiResults(500,'No processes found !');
        }
        
        # call azure api
        $result = Api::call('Azure','executeChangeIps',['instances-ids' => $instancesIds]);

        if(count((array)$result) == 0)
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
     * @name executeRefreshIps
     * @description refresh instances domains actions
     * @before init
     */
    public function executeRefreshIps($parameters = []) 
    { 
        # check for authentication
        if(!Authentication::isUserAuthenticated())
        {
            Page::printApiResults(401,'Only logged-in access allowed !');
        }

        # check users roles 
        Authentication::checkUserRoles();
        
        # check for permissions
        $access = Permissions::checkForAuthorization(Authentication::getAuthenticatedUser(),'AzureInstances','main');

        if($access == false)
        {
            Page::printApiResults(403,'Access Denied !');
        }
		
		$instancesIds = $this->app->utils->arrays->get($parameters,'instances-ids',[]);

        if(!is_array($instancesIds) || count((array)$instancesIds) == 0)
        {
            Page::printApiResults(500,'No processes found !');
        }
        
        # call azure api
        $result = Api::call('Azure','getPublicIps',['instances-ids' => $instancesIds]);

        if(count((array)$result) == 0)
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
     * @name calculateInstancesLogs
     * @description calculate instances logs
     * @before init
     */
    public function calculateInstancesLogs($parameters = []) 
    { 
        # check for authentication
        if(!Authentication::isUserAuthenticated())
        {
            Page::printApiResults(401,'Only logged-in access allowed !');
        }

        # check users roles 
        Authentication::checkUserRoles();
        
        # check for permissions
        $access = Permissions::checkForAuthorization(Authentication::getAuthenticatedUser(),'AzureInstances','main');

        if($access == false)
        {
            Page::printApiResults(403,'Access Denied !');
        }

        $instancesIds = $this->app->utils->arrays->get($parameters,'instances-ids',[]);

        if(!is_array($instancesIds) || count((array)$instancesIds) == 0)
        {
            Page::printApiResults(500,'No instances found !');
        }

        # call azure api
        $result = Api::call('Azure','calculateInstancesLogs',['instances-ids' => $instancesIds]);
        
        if(count((array)$result) == 0)
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
        $access = Permissions::checkForAuthorization(Authentication::getAuthenticatedUser(),'AzureInstances','create');

        if($access == false)
        {
            Page::printApiResults(403,'Access Denied !');
        }
        
        $parts = explode('|',$this->app->utils->arrays->get($parameters,'account'));

        if(count((array)$parts) != 2)
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
            
            if(count((array)$domains) == 0)
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