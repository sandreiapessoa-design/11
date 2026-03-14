<?php declare(strict_types=1); namespace IR\App\Webservices; if (!defined('IR_START')) exit('<pre>No direct script access allowed</pre>');
/**
 * @framework       iResponse Framework 
 * @version         1.0
 * @author          Amine Idrissi <contact@iresponse.tech>
 * @date            2024
 * @name            DigitalOcean.php	
 */

# core 
use IR\Core\Base as Base;
use IR\Core\Application as Application;

# models
use IR\App\Models\Admin\DigitalOceanAccount as DigitalOceanAccount;
use IR\App\Models\Admin\DigitalOceanProcess as DigitalOceanProcess;
use IR\App\Models\Admin\Domain as Domain;

# helpers 
use IR\App\Helpers\Authentication as Authentication;
use IR\App\Helpers\Permissions as Permissions;
use IR\App\Helpers\Page as Page;
use IR\App\Helpers\Api as Api;

/**
 * @name DigitalOcean
 * @description DigitalOcean WebService
 */
class DigitalOcean extends Base
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
     * @name createDroplets
     * @description create droplets
     * @before init
     */
    public function createDroplets($parameters = []) 
    { 
        # check for authentication
        if(!Authentication::isUserAuthenticated())
        {
            Page::printApiResults(401,'Only logged-in access allowed !');
        }
        
        # check users roles 
        Authentication::checkUserRoles();
        
        # check for permissions
        $access = Permissions::checkForAuthorization(Authentication::getAuthenticatedUser(),'DigitalOceanDroplets','create');

        if($access == false)
        {
            Page::printApiResults(403,'Access Denied !');
        }

        $account = DigitalOceanAccount::first(DigitalOceanAccount::FETCH_ARRAY,['id = ?',intval($this->app->utils->arrays->get($parameters,'account-id',0))],['id']);
            
        if(!is_array($account) || count($account) == 0)
        {
            Page::printApiResults(500,'Account not found !');
        }

        $nbDroplets = intval($this->app->utils->arrays->get($parameters,'nb-of-droplets',0));
        
        if($nbDroplets == 0)
        {
            Page::printApiResults(500,'Please provide a number of droplets to create !');
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
            Page::printApiResults(500,'Please provide an droplet size to install with !');
        }
        
        # create a process object
        $process = new DigitalOceanProcess();
        $process->setStatus('In Progress');
        $process->setAccountId($account['id']);
        $process->setRegion($region);
        $process->setNbDroplets($nbDroplets);
        $process->setDomains(implode(',',$domains));
        $process->setOs($os);
        $process->setSize($size);
        $process->setProgress('0%');
        $process->setDropletsCreated('0');
        $process->setDropletsInstalled('0');
        $process->setStartTime(date('Y-m-d H:i:s'));    
        $process->setFinishTime(null);    

        # call iresponse api
        Api::call('DigitalOcean','createDroplets',['process-id' => $process->insert()],true,LOGS_PATH . DS . 'cloud_apis' . DS . 'inst_dgo_' . $account['id'] . '.log');
        Page::printApiResults(200,'Droplets Creation process(es) started');
    }
    
    /**
     * @name stopDropletsProcesses
     * @description stop aws droplets creation processes action
     * @before init
     */
    public function stopDropletsProcesses($parameters = []) 
    { 
        # check for authentication
        if(!Authentication::isUserAuthenticated())
        {
            Page::printApiResults(401,'Only logged-in access allowed !');
        }

        # check users roles 
        Authentication::checkUserRoles();
        
        # check for permissions
        $access = Permissions::checkForAuthorization(Authentication::getAuthenticatedUser(),'DigitalOceanDroplets','create');

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
        $result = Api::call('DigitalOcean','stopProcesses',['processes-ids' => $processesIds]);

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
     * @name executeDropletsActions
     * @description execute aws droplets actions
     * @before init
     */
    public function executeDropletsActions($parameters = []) 
    { 
        # check for authentication
        if(!Authentication::isUserAuthenticated())
        {
            Page::printApiResults(401,'Only logged-in access allowed !');
        }

        # check users roles 
        Authentication::checkUserRoles();
        
        # check for permissions
        $access = Permissions::checkForAuthorization(Authentication::getAuthenticatedUser(),'DigitalOceanDroplets','main');

        if($access == false)
        {
            Page::printApiResults(403,'Access Denied !');
        }

        $dropletsIds = $this->app->utils->arrays->get($parameters,'droplets-ids',[]);

        if(!is_array($dropletsIds) || count($dropletsIds) == 0)
        {
            Page::printApiResults(500,'No processes found !');
        }
        
        $action = $this->app->utils->arrays->get($parameters,'action','');
        
        if($action == null || $action == '')
        {
            Page::printApiResults(500,'Please provide an action !');
        }
        
        # call iresponse api
        $result = Api::call('DigitalOcean','executeDropletsActions',['droplets-ids' => $dropletsIds,'action' => $action]);

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
        $access = Permissions::checkForAuthorization(Authentication::getAuthenticatedUser(),'DigitalOceanDroplets','create');

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


