<?php declare(strict_types=1); namespace IR\App\Webservices; if (!defined('IR_START')) exit('<pre>No direct script access allowed</pre>');
/**
 * @framework       iResponse Framework 
 * @version         1.0
 * @author          Amine Idrissi <contact@iresponse.tech>
 * @date            2024
 * @name            Amazon.php	
 */

# core 
use IR\Core\Base as Base;
use IR\Core\Application as Application; 

# models
use IR\App\Models\Admin\AwsAccount as AwsAccount;
use IR\App\Models\Admin\AwsProcess as AwsProcess; 
use IR\App\Models\Admin\AwsAccountProcess as AwsAccountProcess;
use IR\App\Models\Admin\Domain as Domain;

# helpers 
use IR\App\Helpers\Authentication as Authentication;
use IR\App\Helpers\Permissions as Permissions;
use IR\App\Helpers\Page as Page;
use IR\App\Helpers\Api as Api;

/**
 * @name Amazon
 * @description Amazon WebService
 */
class Amazon extends Base
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
        $access = Permissions::checkForAuthorization(Authentication::getAuthenticatedUser(),'AmazonInstances','create');

        if($access == false)
        {
            Page::printApiResults(403,'Access Denied !');
        }

        $account = AwsAccount::first(AwsAccount::FETCH_ARRAY,['id = ?',intval($this->app->utils->arrays->get($parameters,'account-id',0))],['id','name']);
            
        if(!is_array($account) || count($account) == 0)
        {
            Page::printApiResults(500,'Account not found !');
        }
        
        $regions = $this->app->utils->arrays->get($parameters,'regions',[]);

        if(!is_array($regions) || count($regions) == 0)
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
        
        $storage = intval($this->app->utils->arrays->get($parameters,'storage',0));
        
        if($storage < 8)
        {
            Page::printApiResults(500,'Please enter a storage equal or greater than 8Gb !');
        }
        
        $domains = $this->app->utils->arrays->get($parameters,'domains',[]);

        if(!is_array($domains) || count($domains) == 0)
        {
            $domains = 'rdns';
        }
        else
        {
            $domains = implode(',',$domains);
        }
        
        $subnetsFilter = $this->app->utils->arrays->get($parameters,'subnets-filter','');
        $os = $this->app->utils->arrays->get($parameters,'os','');
        
        if($os == null || $os == '')
        {
            Page::printApiResults(500,'Please provide an operating system to install with !');
        }
        
        $type = $this->app->utils->arrays->get($parameters,'instance-type','');
        
        if($type == null || $type == '')
        {
            Page::printApiResults(500,'Please provide an instance type to install with !');
        }
        
        # create a process object
        $process = new AwsProcess();
        $process->setStatus('In Progress');
        $process->setAccountId($account['id']);
        $process->setAccountName($account['name']);
        $process->setRegions(implode(',',$regions));
        $process->setNbInstances($nbInstances);
        $process->setNbPrivateIps($nbIps);
        $process->setStorage($storage);
        $process->setDomains($domains);
        $process->setOs($os);
        $process->setSubnetsFilter($subnetsFilter);
        $process->setInstanceType($type);
        $process->setProgress('0%');
        $process->setInstancesCreated('0');
        $process->setInstancesInstalled('0');
        $process->setStartTime(date('Y-m-d H:i:s'));    
        $process->setFinishTime(null);    

        # call iresponse api
        Api::call('Amazon','createInstances',['process-id' => $process->insert()],true,LOGS_PATH . DS . 'cloud_apis' . DS . 'inst_aws_' . $account['id'] . '.log');
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
        $access = Permissions::checkForAuthorization(Authentication::getAuthenticatedUser(),'AmazonInstances','create');

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
        $result = Api::call('Amazon','stopProcesses',['processes-ids' => $processesIds]);

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
        $access = Permissions::checkForAuthorization(Authentication::getAuthenticatedUser(),'AmazonInstances','main');

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
        $result = Api::call('Amazon','executeInstancesActions',['instances-ids' => $instancesIds,'action' => $action]);

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
        $access = Permissions::checkForAuthorization(Authentication::getAuthenticatedUser(),'AmazonAccounts','edit');

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
        $result = Api::call('Amazon','stopAccountsProcesses',['processes-ids' => $processesIds]);

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
     * @name executeRestarts
     * @description execute instances restarts actions
     * @before init
     */
    public function executeRestarts($parameters = []) 
    { 
        # check for authentication
        if(!Authentication::isUserAuthenticated())
        {
            Page::printApiResults(401,'Only logged-in access allowed !');
        }

        # check users roles 
        Authentication::checkUserRoles();
        
        # check for permissions
        $access = Permissions::checkForAuthorization(Authentication::getAuthenticatedUser(),'AmazonAccounts','main');

        if($access == false)
        {
            Page::printApiResults(403,'Access Denied !');
        }

        $accountsIds = $this->app->utils->arrays->get($parameters,'accounts-ids',[]);

        if(!is_array($accountsIds) || count($accountsIds) == 0)
        {
            Page::printApiResults(500,'No accounts found !');
        }
        
        $region = $this->app->utils->arrays->get($parameters,'region','');

        if($region == null || $region == '')
        {
            Page::printApiResults(500,'Please provide a region !');
        }

        foreach ($accountsIds as $accountId)
        {
            if(intval($accountId) > 0)
            {
                $account = AwsAccount::first(AwsAccount::FETCH_ARRAY,['id = ?',intval($accountId)],['id','name']);
            
                if(!is_array($account) || count($account) == 0)
                {
                    Page::printApiResults(500,'Account not found !');
                }

                # create a process object
                $process = new AwsAccountProcess();
                $process->setStatus('In Progress');
                $process->setProcessType('Restarting Instances');
                $process->setAccountId($account['id']);
                $process->setAccountName($account['name']);
                $process->setRegion($region);
                $process->setStartTime(date('Y-m-d H:i:s'));    
                $process->setFinishTime(null); 
                
                # call iresponse api
                Api::call('Amazon','executeRestarts',['process-id' => $process->insert()],true,LOGS_PATH . DS . 'aws_restarts' . DS . 'res_aws_' . $accountId . '.log');
            }
        }

        Page::printApiResults(200,"Process started successfully !");
    }
    
    /**
     * @name executeRotatesRestarts
     * @description execute instances restarts actions
     * @before init
     */
    public function executeRotatesRestarts($parameters = []) 
    { 
        # check for authentication
        if(!Authentication::isUserAuthenticated())
        {
            Page::printApiResults(401,'Only logged-in access allowed !');
        }

        # check users roles 
        Authentication::checkUserRoles();
        
        # check for permissions
        $access = Permissions::checkForAuthorization(Authentication::getAuthenticatedUser(),'AmazonAccounts','main');

        if($access == false)
        {
            Page::printApiResults(403,'Access Denied !');
        }

        $accountsIds = $this->app->utils->arrays->get($parameters,'accounts-ids',[]);

        if(!is_array($accountsIds) || count($accountsIds) == 0)
        {
            Page::printApiResults(500,'No accounts found !');
        }

        $region = $this->app->utils->arrays->get($parameters,'region','');

        if($region == null || $region == '')
        {
            Page::printApiResults(500,'Please provide a region !');
        }
        
        foreach ($accountsIds as $accountId)
        {
            if(intval($accountId) > 0)
            {
                $account = AwsAccount::first(AwsAccount::FETCH_ARRAY,['id = ?',intval($accountId)],['id','name']);
            
                if(!is_array($account) || count($account) == 0)
                {
                    Page::printApiResults(500,'Account not found !');
                }

                # create a process object
                $process = new AwsAccountProcess();
                $process->setStatus('In Progress');
                $process->setProcessType('Restarting / Rotating Ips');
                $process->setAccountId($account['id']);
                $process->setAccountName($account['name']);
                $process->setRegion($region);
                $process->setStartTime(date('Y-m-d H:i:s'));    
                $process->setFinishTime(null); 
                
                # call iresponse api
                Api::call('Amazon','executeRotatesRestarts',['process-id' => $process->insert()],true,LOGS_PATH . DS . 'aws_restarts' . DS . 'res_aws_' . $accountId . '.log');
            }
        }

        Page::printApiResults(200,"Process started successfully !");
    }
    
    /**
     * @name fetchElasticIps
     * @description search for elastic ips actions
     * @before init
     */
    public function fetchElasticIps($parameters = []) 
    { 
        # check for authentication
        if(!Authentication::isUserAuthenticated())
        {
            Page::printApiResults(401,'Only logged-in access allowed !');
        }

        # check users roles 
        Authentication::checkUserRoles();
        
        # check for permissions
        $access = Permissions::checkForAuthorization(Authentication::getAuthenticatedUser(),'AmazonAccounts','main');

        if($access == false)
        {
            Page::printApiResults(403,'Access Denied !');
        }

        $accountsIds = $this->app->utils->arrays->get($parameters,'accounts-ids',[]);

        if(!is_array($accountsIds) || count($accountsIds) == 0)
        {
            Page::printApiResults(500,'No accounts found !');
        }
        
        $region = $this->app->utils->arrays->get($parameters,'region','');

        if($region == null || $region == '')
        {
            Page::printApiResults(500,'Please provide a region !');
        }

        foreach ($accountsIds as $accountId)
        {
            if(intval($accountId) > 0)
            {
                $account = AwsAccount::first(AwsAccount::FETCH_ARRAY,['id = ?',intval($accountId)],['id','name']);
            
                if(!is_array($account) || count($account) == 0)
                {
                    Page::printApiResults(500,'Account not found !');
                }
                
                # create a process object
                $process = new AwsAccountProcess();
                $process->setStatus('In Progress');
                $process->setProcessType('Elastic Ips Fetching');
                $process->setAccountId($account['id']);
                $process->setAccountName($account['name']);
                $process->setRegion($region);
                $process->setStartTime(date('Y-m-d H:i:s'));    
                $process->setFinishTime(null);   
        
                # call iresponse api
                Api::call('Amazon','searchForElasticIps',['process-id' => $process->insert()],true,LOGS_PATH . DS . 'aws_restarts' . DS . 'res_aws_' . $accountId . '.log');
            }
        }

        Page::printApiResults(200,"Process started successfully !");
    }
    
    /**
     * @name refreshInstancesIps
     * @description refresh instances ips actions
     * @before init
     */
    public function refreshInstancesIps($parameters = []) 
    { 
        # check for authentication
        if(!Authentication::isUserAuthenticated())
        {
            Page::printApiResults(401,'Only logged-in access allowed !');
        }

        # check users roles 
        Authentication::checkUserRoles();
        
        # check for permissions
        $access = Permissions::checkForAuthorization(Authentication::getAuthenticatedUser(),'AmazonAccounts','main');

        if($access == false)
        {
            Page::printApiResults(403,'Access Denied !');
        }

        $accountsIds = $this->app->utils->arrays->get($parameters,'accounts-ids',[]);

        if(!is_array($accountsIds) || count($accountsIds) == 0)
        {
            Page::printApiResults(500,'No accounts found !');
        }
        
        $region = $this->app->utils->arrays->get($parameters,'region','');

        if($region == null || $region == '')
        {
            Page::printApiResults(500,'Please provide a region !');
        }

        $bundle = [];
        
        foreach ($accountsIds as $accountId)
        {
            if(intval($accountId) > 0)
            {
                $account = AwsAccount::first(AwsAccount::FETCH_ARRAY,['id = ?',intval($accountId)],['id','name']);
            
                if(!is_array($account) || count($account) == 0)
                {
                    Page::printApiResults(500,'Account not found !');
                }

                $bundle[] = [ 'account-id' => $accountId, 'region' => $region ];
            }
        }

        if(count($bundle) == 0)
        {
            Page::printApiResults(500,'No Instances found for these accounts to refresh !');
        }
        
        # call iresponse api
        $result = Api::call('Amazon','refreshInstances',['bundle' => $bundle]);

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
        $access = Permissions::checkForAuthorization(Authentication::getAuthenticatedUser(),'AmazonInstances','main');

        if($access == false)
        {
            Page::printApiResults(403,'Access Denied !');
        }

        $instancesIds = $this->app->utils->arrays->get($parameters,'instances-ids',[]);

        if(!is_array($instancesIds) || count($instancesIds) == 0)
        {
            Page::printApiResults(500,'No instances found !');
        }

        # call iresponse api
        $result = Api::call('Amazon','calculateInstancesLogs',['instances-ids' => $instancesIds]);
        
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
        $access = Permissions::checkForAuthorization(Authentication::getAuthenticatedUser(),'AmazonInstances','create');

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