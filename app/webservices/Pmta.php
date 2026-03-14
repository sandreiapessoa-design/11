<?php declare(strict_types=1); namespace IR\App\Webservices; if (!defined('IR_START')) exit('<pre>No direct script access allowed</pre>');
/**
 * @framework       iResponse Framework 
 * @version         1.0
 * @author          Amine Idrissi <contact@iresponse.tech>
 * @date            2024
 * @name            Pmta.php	
 */

# core 
use IR\Core\Base as Base;
use IR\Core\Application as Application;

# helpers 
use IR\App\Helpers\Authentication as Authentication;
use IR\App\Helpers\Permissions as Permissions;
use IR\App\Helpers\Page as Page;
use IR\App\Helpers\Api as Api;

# orm 
use IR\Orm\Table as Table;

# models
use IR\App\Models\Admin\MtaServer as MtaServer;
use IR\App\Models\Admin\ServerVmta as ServerVmta;
use IR\App\Models\Admin\PmtaProcess as PmtaProcess;

/**
 * @name Pmta
 * @description Pmta WebService
 */
class Pmta extends Base
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
     * @name getServerVmtas
     * @description getServerVmtas action
     * @before init
     */
    public function getServerVmtas($parameters = []) 
    { 
        # check for authentication
        if(!Authentication::isUserAuthenticated())
        {
            Page::printApiResults(401,'Only logged-in access allowed !');
        }
        
        # check users roles 
        Authentication::checkUserRoles();
        
        # check for permissions
        $access = Permissions::checkForAuthorization(Authentication::getAuthenticatedUser(),'Pmta','configs')
                || Permissions::checkForAuthorization(Authentication::getAuthenticatedUser(),'Pmta','commands')
                || Permissions::checkForAuthorization(Authentication::getAuthenticatedUser(),'Pmta','templates');

        if($access == false)
        {
            Page::printApiResults(403,'Access Denied !');
        }
        
        $serverIds = array_filter($this->app->utils->arrays->get($parameters,'server-ids'));

        if(count($serverIds))
        {
            $mtaServer = MtaServer::first(MtaServer::FETCH_ARRAY,['status = ? and id in ?',['Activated',$serverIds]]);

            if(count($mtaServer) == 0)
            {
                Page::printApiResults(404,'Mta server not found !');
            }

            $result = ServerVmta::all(ServerVmta::FETCH_ARRAY,['status = ? and mta_server_id in ?',['Activated',$serverIds]],['id','mta_server_id','mta_server_name','name','ip','domain']);
            
            $vmtas = [];

            if(count($result))
            {
                foreach ($result as $row) 
                {
                    $vmtas[] = [
                        'id' => $row['id'],
                        'name' => $row['name'],
                        'ip' => $row['ip'],
                        'rdns' => $row['domain'],
                        'domain' => $this->app->utils->domains->getDomainFromURL($row['domain']),
                        'server-id' => $row['mta_server_id'],
                        'server-name' => $row['mta_server_name']
                    ];
                }

                if(count($vmtas))
                {
                    Page::printApiResults(200,'',['vmtas' => $vmtas]);
                }
                else
                {
                    Page::printApiResults(500,'No vmtas Found !');
                }
            }
            else
            {
                Page::printApiResults(500,'Error while trying to get vmtas !');
            }
        }
        else
        {
            Page::printApiResults(500,'Incorrect server Id !');
        }
    }
    
    /**
     * @name getTemplateConfig
     * @description getConfig action
     * @before init
     */
    public function getTemplateConfig($parameters = []) 
    { 
        # check for authentication
        if(!Authentication::isUserAuthenticated())
        {
            Page::printApiResults(401,'Only logged-in access allowed !');
        }
        
        # check users roles 
        Authentication::checkUserRoles();
        
        # check for permissions
        $access = Permissions::checkForAuthorization(Authentication::getAuthenticatedUser(),'Pmta','templates');

        if($access == false)
        {
            Page::printApiResults(403,'Access Denied !');
        }

        $type = $this->app->utils->arrays->get($parameters,'type');

        if($type == null || $type == '')
        {
            Page::printApiResults(404,'Config type not found !');
        }
        
        $name = $this->app->utils->arrays->get($parameters,'name');

        if($name == null || $name == '')
        {
            Page::printApiResults(404,'Config not found !');
        }

        $config = "";
        $pmtaConfigFolder = ASSETS_PATH . DS . 'pmta' . DS . 'configs' . DS .  $type;
      
        if(file_exists($pmtaConfigFolder . DS . 'parameters' . DS . $name . '.conf'))
        {
            $config = $this->app->utils->fileSystem->readFile($pmtaConfigFolder . DS . 'parameters' . DS . $name . '.conf');
        }
        else
        {
            Page::printApiResults(404,'Config not found !');
        }
        
        Page::printApiResults(200,'',['config' => $config]);
    }
    
    /**
     * @name saveTemplateConfig
     * @description saveTemplateConfig action
     * @before init
     */
    public function saveTemplateConfig($parameters = []) 
    { 
        # check for authentication
        if(!Authentication::isUserAuthenticated())
        {
            Page::printApiResults(401,'Only logged-in access allowed !');
        }
        
        # check users roles 
        Authentication::checkUserRoles();
        
        # check for permissions
        $access = Permissions::checkForAuthorization(Authentication::getAuthenticatedUser(),'Pmta','templates');

        if($access == false)
        {
            Page::printApiResults(403,'Access Denied !');
        }

        $serverIds = $this->app->utils->arrays->get($parameters,'servers-ids',[]);
        $name = $this->app->utils->arrays->get($parameters,'name');

        if($name == null || $name == '')
        {
            Page::printApiResults(404,'Config not found !');
        }

        $content = $this->app->utils->arrays->get($parameters,'content');

        if($content == null || $content == '')
        {
            Page::printApiResults(404,'Config content found !');
        }

        $type = $this->app->utils->arrays->get($parameters,'type');

        if($type == null || $type == '')
        {
            Page::printApiResults(404,'Config type not found !');
        }
        
        $pmtaConfigFolder = ASSETS_PATH . DS . 'pmta' . DS . 'configs' . DS .  $type;

        if(file_exists($pmtaConfigFolder . DS . 'parameters' . DS . $name . '.conf'))
        {
            $this->app->utils->fileSystem->writeFile($pmtaConfigFolder . DS . 'parameters' . DS . $name . '.conf',$content);
            
            # apply on active servers if any
            if(is_array($serverIds) && count($serverIds))
            {
                # call iresponse api
                $result = Api::call('Pmta','applyTemplateConfigs',['servers-ids' => $serverIds,'path' => '/etc/pmta/parameters/' . $name . '.conf','content' => base64_encode($content)]);

                if(count($result) == 0)
                {
                    Page::printApiResults(500,'No response found !');
                }

                if($result['httpStatus'] == 500)
                {
                    Page::printApiResults(500,$result['message']);
                }
            }
            
            Page::printApiResults(200,'Config file updated successfully !');
        }
        else
        {
            Page::printApiResults(404,'Config not found !');
        }
    }
    
    /**
     * @name getConfig
     * @description getConfig action
     * @before init
     */
    public function getConfig($parameters = []) 
    { 
        # check for authentication
        if(!Authentication::isUserAuthenticated())
        {
            Page::printApiResults(401,'Only logged-in access allowed !');
        }
        
        # check users roles 
        Authentication::checkUserRoles();
        
        # check for permissions
        $access = Permissions::checkForAuthorization(Authentication::getAuthenticatedUser(),'Pmta','configs');

        if($access == false)
        {
            Page::printApiResults(403,'Access Denied !');
        }
        
        $serverId = intval($this->app->utils->arrays->get($parameters,'server-id'));

        if($serverId > 0)
        {
            $mtaServer = MtaServer::first(MtaServer::FETCH_ARRAY,['status = ? and id = ?',['Activated',$serverId]]);

            if(count($mtaServer) == 0)
            {
                Page::printApiResults(404,'Mta server not found !');
            }

            $type = $this->app->utils->arrays->get($parameters,'type');

            if($type == null || $type == '')
            {
                Page::printApiResults(404,'Config type not found !');
            }

            $name = $this->app->utils->arrays->get($parameters,'name');

            if($name == null || $name == '')
            {
                Page::printApiResults(404,'Config not found !');
            }

            # call iresponse api
            $result = Api::call('Pmta','getConfig',['server-id' => $serverId,'type' => $type,'name' => $name]);

            if(count($result) == 0)
            {
                Page::printApiResults(500,'No response found !');
            }
            
            if($result['httpStatus'] == 500)
            {
                Page::printApiResults(500,$result['message']);
            }
            
            Page::printApiResults(200,'',['config' => $result['data']]);
        }
        else
        {
            Page::printApiResults(500,'Incorrect server id !');
        }
    }
    
    /**
     * @name saveConfig
     * @description saveConfig action
     * @before init
     */
    public function saveConfig($parameters = []) 
    { 
        # check for authentication
        if(!Authentication::isUserAuthenticated())
        {
            Page::printApiResults(401,'Only logged-in access allowed !');
        }
        
        # check users roles 
        Authentication::checkUserRoles();
        
        # check for permissions
        $access = Permissions::checkForAuthorization(Authentication::getAuthenticatedUser(),'Pmta','configs');

        if($access == false)
        {
            Page::printApiResults(403,'Access Denied !');
        }
        
        $serverId = intval($this->app->utils->arrays->get($parameters,'server-id'));

        if($serverId > 0)
        {
            $mtaServer = MtaServer::first(MtaServer::FETCH_ARRAY,['status = ? and id = ?',['Activated',$serverId]]);

            if(count($mtaServer) == 0)
            {
                Page::printApiResults(404,'Mta Server not found !');
            }

            $type = $this->app->utils->arrays->get($parameters,'type');

            if($type == null || $type == '')
            {
                Page::printApiResults(404,'Config type not found !');
            }

            $name = $this->app->utils->arrays->get($parameters,'name');

            if($name == null || $name == '')
            {
                Page::printApiResults(404,'Config not found !');
            }

            $content = $this->app->utils->arrays->get($parameters,'content');

            if($content == null || $content == '')
            {
                Page::printApiResults(404,'Config content found !');
            }

            # call iresponse api
            $result = Api::call('Pmta','saveConfig',['server-id' => $serverId,'type' => $type,'name' => $name,'content' => $content]);

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
        else
        {
            Page::printApiResults(500,'Incorrect server id !');
        }
    }
    
    /**
     * @name executePmtaCommand
     * @description executePmtaCommand action
     * @before init
     */
    public function executePmtaCommand($parameters = []) 
    { 
        # check for authentication
        if(!Authentication::isUserAuthenticated())
        {
            Page::printApiResults(401,'Only logged-in access allowed !');
        }
        
        # check users roles 
        Authentication::checkUserRoles();
        
        # check for permissions
        $access = Permissions::checkForAuthorization(Authentication::getAuthenticatedUser(),'Pmta','commands');

        if($access == false)
        {
            Page::printApiResults(403,'Access Denied !');
        }
        
        $serversBundle = [];
            
        $servers = $this->app->utils->arrays->get($parameters,'servers');
        $vmtas = $this->app->utils->arrays->get($parameters,'vmtas',[]);
        $queues = array_filter(explode('|',$this->app->utils->arrays->get($parameters,'queues','')));
        $target = $this->app->utils->arrays->get($parameters,'target','all');
        $command = $this->app->utils->arrays->get($parameters,'action');
        $scheduleTimes = intval($this->app->utils->arrays->get($parameters,'schedule-times'));
        $schedulePeriod = intval($this->app->utils->arrays->get($parameters,'schedule-period'));
        
        if(count($servers) > 0)
        {
            if($command == null || $command == '')
            {
                Page::printApiResults(404,'PowerMta command not found !');
            }

            if(count($queues) && $queues[0] == '*/*')
            {
                $queues = [];
            }
            
            foreach ($servers as $serverId) 
            {
                $tmp = [
                    'server' => $serverId,
                    'queues' => $queues,
                    'target' => $target,
                    'schedule-times' => $scheduleTimes,
                    'schedule-period' => $schedulePeriod,
                    'command' => $command
                ];

                $vms = [];

                if(is_array($vmtas) && count($vmtas))
                {
                    foreach ($vmtas as $vmta) 
                    {
                        $parts = explode("|",$vmta);

                        if(count($parts) && intval($serverId) == intval($parts[0]))
                        {
                            $vms[] = $vmta;
                        }
                    }
                }

                $tmp['vmtas'] = $vms;
                $serversBundle[] = $tmp;
            }
            
            # call iresponse api
            $result = Api::call('Pmta','executeCommand',['bundle' => $serversBundle]);

            if(count($result) == 0)
            {
                Page::printApiResults(500,'No response found !');
            }
            
            if($result['httpStatus'] == 500)
            {
                Page::printApiResults(500,$result['message']);
            }
            
            Page::printApiResults(200,'',['logs' => $result['data']]);
        }
        else
        {
            Page::printApiResults(500,'No servers found !');
        }
    }
 
    /**
     * @name startProcesses
     * @description startProcesses action
     * @before init
     */
    public function startProcesses($parameters = []) 
    { 
        # check for authentication
        if(!Authentication::isUserAuthenticated())
        {
            Page::printApiResults(401,'Only logged-in access allowed !');
        }
        
        # check users roles 
        Authentication::checkUserRoles();
        
        # check for permissions
        $access = Permissions::checkForAuthorization(Authentication::getAuthenticatedUser(),'Pmta','processes');

        if($access == false)
        {
            Page::printApiResults(403,'Access Denied !');
        }
        
        $serversIds = $this->app->utils->arrays->get($parameters,'servers');
        $vmtas = $this->app->utils->arrays->get($parameters,'vmtas',[]);
        $queues = array_filter(explode('|',$this->app->utils->arrays->get($parameters,'queues','')));
        $pauseTime = intval($this->app->utils->arrays->get($parameters,'pause-time'));
        $resumeTime = intval($this->app->utils->arrays->get($parameters,'resume-time'));
        
        if(is_array($serversIds) && count($serversIds) > 0)
        {
            $mtaServers = MtaServer::all(MtaServer::FETCH_ARRAY,['id IN ?',[$serversIds]],['id','provider_id','provider_name','name']);
            $processesIds = [];
            
            if(count($queues) && $queues[0] == '*/*')
            {
                $queues = [];
            }
            
            foreach ($mtaServers as $server) 
            {
                $vmtasIds = [];

                if(is_array($vmtas) && count($vmtas))
                {
                    foreach ($vmtas as $vmta) 
                    {
                        $parts = explode("|",$vmta);

                        if(count($parts) > 1 && intval($this->app->utils->arrays->get($server,'id')) == intval($parts[0]))
                        {
                            $vmtasIds[] = intval($parts[1]);
                        }
                    }
                }
                
                $process = new PmtaProcess([
                    'provider_id' => intval($this->app->utils->arrays->get($server,'provider_id')),
                    'provider_name' => $this->app->utils->arrays->get($server,'provider_name'),
                    'server_id' => intval($this->app->utils->arrays->get($server,'id')),
                    'server_name' => $this->app->utils->arrays->get($server,'name'),
                    'user_full_name' => Authentication::getAuthenticatedUser()->getFirstName() . ' ' . Authentication::getAuthenticatedUser()->getLastName(),
                    'queues' => implode(',',$queues),
                    'vmtas' => implode(',',$vmtasIds),
                    'pause_wait' => $pauseTime,
                    'resume_wait' => $resumeTime,
                    'action_start_time' => date('Y-m-d H:i:s')
                ]);
                
                $processesIds[] = $process->insert();
            }
            
            # call iresponse api
            $result = Api::call('Pmta','executeAutoPauseResumeProcess',['processes-ids' => $processesIds]);

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
        else
        {
            Page::printApiResults(500,'No servers found !');
        }
    }
    
    /**
     * @name stopProcesses
     * @description stop pmta processes action
     * @before init
     */
    public function stopProcesses($parameters = []) 
    { 
        # check for authentication
        if(!Authentication::isUserAuthenticated())
        {
            Page::printApiResults(401,'Only logged-in access allowed !');
        }

        # check users roles 
        Authentication::checkUserRoles();
        
        # check for permissions
        $access = Permissions::checkForAuthorization(Authentication::getAuthenticatedUser(),'Pmta','processes');

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
        $result = Api::call('Pmta','stopAutoPauseResumeProcess',['processes-ids' => $processesIds]);

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
     * @name updateGlobalVmtas
     * @description updateGlobalVmtas action
     * @before init
     */
    public function updateGlobalVmtas($parameters = []) 
    { 
        # check for authentication
        if(!Authentication::isUserAuthenticated())
        {
            Page::printApiResults(401,'Only logged-in access allowed !');
        }
        
        # check users roles 
        Authentication::checkUserRoles();
        
        # check for permissions
        $access = Permissions::checkForAuthorization(Authentication::getAuthenticatedUser(),'Pmta','globalVmtas');

        if($access == false)
        {
            Page::printApiResults(403,'Access Denied !');
        }
        
        $servers = $this->app->utils->arrays->get($parameters,'servers');
        $ispId = intval($this->app->utils->arrays->get($parameters,'isp-id'));
        $domain = $this->app->utils->arrays->get($parameters,'domain');
        
        if(count($servers) > 0)
        {
            if($ispId == null || $ispId == 0)
            {
                Page::printApiResults(500,'Isp not selected !');
            }
            
            if($domain == null || $domain == '')
            {
                Page::printApiResults(500,'Domain not inserted !');
            }

            # call iresponse api
            $result = Api::call('Pmta','updateGlobalVmtas',['action' => 'create','servers-ids' => $servers,'isp-id' => $ispId,'domain' => $domain]);

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
        else
        {
            Page::printApiResults(500,'No servers found !');
        }
    }
    
    /**
     * @name resetGlobalVmtas
     * @description resetGlobalVmtas action
     * @before init
     */
    public function resetGlobalVmtas($parameters = []) 
    { 
        # check for authentication
        if(!Authentication::isUserAuthenticated())
        {
            Page::printApiResults(401,'Only logged-in access allowed !');
        }
        
        # check users roles 
        Authentication::checkUserRoles();
        
        # check for permissions
        $access = Permissions::checkForAuthorization(Authentication::getAuthenticatedUser(),'Pmta','globalVmtas');

        if($access == false)
        {
            Page::printApiResults(403,'Access Denied !');
        }
        
        $servers = $this->app->utils->arrays->get($parameters,'servers');
        $ispId = intval($this->app->utils->arrays->get($parameters,'isp-id'));
        
        if(count($servers) > 0)
        {
            # call iresponse api
            $result = Api::call('Pmta','updateGlobalVmtas',['action' => 'reset','servers-ids' => $servers,'isp-id' => $ispId]);

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
        else
        {
            Page::printApiResults(500,'No servers found !');
        }
    }
    
     /**
     * @name updateIndividualVmtas
     * @description updateIndividualVmtas action
     * @before init
     */
    public function updateIndividualVmtas($parameters = []) 
    { 
        # check for authentication
        if(!Authentication::isUserAuthenticated())
        {
            Page::printApiResults(401,'Only logged-in access allowed !');
        }
        
        # check users roles 
        Authentication::checkUserRoles();
        
        # check for permissions
        $access = Permissions::checkForAuthorization(Authentication::getAuthenticatedUser(),'Pmta','individualVmtas');

        if($access == false)
        {
            Page::printApiResults(403,'Access Denied !');
        }
        
        $ipsDomains = array_filter(explode(PHP_EOL,strval($this->app->utils->arrays->get($parameters,'mapping',''))));
        $ispId = intval($this->app->utils->arrays->get($parameters,'isp-id'));
        
        if(count($ipsDomains) > 0)
        {
            if($ispId == null || $ispId == 0)
            {
                Page::printApiResults(500,'Isp not selected !');
            }
            
            $pairs = [];
            $mapping = [];
            $ipsFromMap = [];
            
            foreach ($ipsDomains as $row) 
            {
                if($this->app->utils->strings->contains($row,'|'))
                {
                    $ip = trim(strval($this->app->utils->arrays->first(explode('|',$row))));
                    $domain = trim(strval($this->app->utils->arrays->last(explode('|',$row))));
                    
                    if(filter_var($ip,FILTER_VALIDATE_IP,FILTER_FLAG_IPV4) || filter_var($ip,FILTER_VALIDATE_IP,FILTER_FLAG_IPV6))
                    {
                        $ipsFromMap[] = $ip;
                        $pairs[$ip] = $domain;
                    }
                }
            }
            
            if(count($ipsFromMap) == 0)
            {
                Page::printApiResults(500,'no ips inserted !');
            }
            
            $vmtas = ServerVmta::all(ServerVmta::FETCH_ARRAY,['ip in ?',[$ipsFromMap]],['mta_server_id','ip']);
            
            if(count($vmtas) == 0)
            {
                Page::printApiResults(500,'no ips found !');
            }
            
            foreach ($vmtas as $vmta) 
            {
                if(!key_exists($vmta['mta_server_id'],$mapping))
                {
                    $mapping[$vmta['mta_server_id']] = [];
                }
                
                $mapping[$vmta['mta_server_id']][] = [
                    'vmta-ip' => $vmta['ip'],
                    'domain' => $pairs[$vmta['ip']]
                ];
            }
            
            # call iresponse api
            $result = Api::call('Pmta','updateIndividualVmtas',['action' => 'create','mapping' => $mapping,'isp-id' => $ispId]);

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
        else
        {
            Page::printApiResults(500,'No ips / domains found !');
        }
    }
    
    /**
     * @name resetIndividualVmtas
     * @description resetIndividualVmtas action
     * @before init
     */
    public function resetIndividualVmtas($parameters = []) 
    { 
        # check for authentication
        if(!Authentication::isUserAuthenticated())
        {
            Page::printApiResults(401,'Only logged-in access allowed !');
        }
        
        # check users roles 
        Authentication::checkUserRoles();
        
        # check for permissions
        $access = Permissions::checkForAuthorization(Authentication::getAuthenticatedUser(),'Pmta','individualVmtas');

        if($access == false)
        {
            Page::printApiResults(403,'Access Denied !');
        }
        
        $ips = array_filter(explode(PHP_EOL,strval($this->app->utils->arrays->get($parameters,'ips',''))));
        $ispId = intval($this->app->utils->arrays->get($parameters,'isp-id'));

        if(count($ips) > 0)
        {
            if($ispId == null || $ispId == 0)
            {
                Page::printApiResults(500,'Isp not selected !');
            }

            $mapping = [];
            $ipsFromMap = [];
            
            foreach ($ips as $row) 
            {
                if($this->app->utils->strings->contains($row,'|'))
                {
                    $ip = trim(strval($this->app->utils->arrays->first(explode('|',$row))));

                    if(filter_var($ip,FILTER_VALIDATE_IP,FILTER_FLAG_IPV4) || filter_var($ip,FILTER_VALIDATE_IP,FILTER_FLAG_IPV6))
                    {
                        $ipsFromMap[] = $ip;
                    }
                }
                else
                {
                    if(filter_var($this->app->utils->strings->trim($row),FILTER_VALIDATE_IP,FILTER_FLAG_IPV4) || filter_var($ip,FILTER_VALIDATE_IP,FILTER_FLAG_IPV6))
                    {
                        $ipsFromMap[] = $this->app->utils->strings->trim($row);
                    }
                }
            }
            
            if(count($ipsFromMap) == 0)
            {
                Page::printApiResults(500,'no ips inserted !');
            }
            
            $vmtas = ServerVmta::all(ServerVmta::FETCH_ARRAY,['ip in ?',[$ipsFromMap]],['mta_server_id','ip']);
            
            if(count($vmtas) == 0)
            {
                Page::printApiResults(500,'no ips found !');
            }
            
            foreach ($vmtas as $vmta) 
            {
                if(!key_exists($vmta['mta_server_id'],$mapping))
                {
                    $mapping[$vmta['mta_server_id']] = [];
                }
                
                $mapping[$vmta['mta_server_id']][] = [
                    'vmta-ip' => $vmta['ip']
                ];
            }

            # call iresponse api
            $result = Api::call('Pmta','updateIndividualVmtas',['action' => 'reset','mapping' => $mapping,'isp-id' => $ispId]);

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
        else
        {
            Page::printApiResults(500,'No ips found !');
        }
    }
    
    /**
     * @name createSMTPVmtas
     * @description createSMTPVmtas action
     * @before init
     */
    public function createSMTPVmtas($parameters = []) 
    { 
        # check for authentication
        if(!Authentication::isUserAuthenticated())
        {
            Page::printApiResults(401,'Only logged-in access allowed !');
        }
        
        # check users roles 
        Authentication::checkUserRoles();
        
        # check for permissions
        $access = Permissions::checkForAuthorization(Authentication::getAuthenticatedUser(),'Pmta','smtpVmtas');

        if($access == false)
        {
            Page::printApiResults(403,'Access Denied !');
        }
        
        $servers = $this->app->utils->arrays->get($parameters,'servers');
        $smtps = array_filter(explode(PHP_EOL,$this->app->utils->arrays->get($parameters,'smtps','')));
        $encryption = $this->app->utils->arrays->get($parameters,'encryption');
        
        if(count($servers) > 0)
        {
            if(count($smtps) == 0)
            {
                Page::printApiResults(500,'No mailboxes found !');
            }
            
            $smtpsList = [];
            
            foreach ($smtps as $smtp)
            {
                $smtpParts = array_filter(explode(' ',$smtp));
                
                if(count($smtpParts) == 4)
                {
                    $smtpsList[] = [
                        'host' => $smtpParts[0],
                        'port' => $smtpParts[1],
                        'username' => $smtpParts[2],
                        'password' => $smtpParts[3]
                    ];
                }
            }
            
            if(count($smtpsList) == 0)
            {
                Page::printApiResults(500,'No smtp found !');
            }
            
            # call iresponse api
            $result = Api::call('Pmta','createSMTPVmtas',['servers-ids' => $servers,'smtps-list' => $smtpsList , 'encryption' => $encryption]);
            
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
        else
        {
            Page::printApiResults(500,'No servers found !');
        }
    }
    
    /**
     * @name resetSMTPVmtas
     * @description resetSMTPVmtas action
     * @before init
     */
    public function resetSMTPVmtas($parameters = []) 
    { 
        # check for authentication
        if(!Authentication::isUserAuthenticated())
        {
            Page::printApiResults(401,'Only logged-in access allowed !');
        }
        
        # check users roles 
        Authentication::checkUserRoles();
        
        # check for permissions
        $access = Permissions::checkForAuthorization(Authentication::getAuthenticatedUser(),'Pmta','smtpVmtas');

        if($access == false)
        {
            Page::printApiResults(403,'Access Denied !');
        }
        
        $servers = $this->app->utils->arrays->get($parameters,'servers');
        
        if(count($servers) > 0)
        { 
            # call iresponse api
            $result = Api::call('Pmta','resetSMTPVmtas',['servers-ids' => $servers]);

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
        else
        {
            Page::printApiResults(500,'No servers found !');
        }
    }
    
    /**
     * @name accountings
     * @description accountings action
     * @before init
     */
    public function accountings($parameters = []) 
    { 
        $stats = json_decode(base64_decode($this->app->utils->arrays->get($parameters,'stats')),true);
        $bounceEmails = json_decode(base64_decode($this->app->utils->arrays->get($parameters,'bounce-emails')),true);
        $cleanEmails = json_decode(base64_decode($this->app->utils->arrays->get($parameters,'clean-emails')),true);
        
        # calculate stats
        if(count($stats))
        { 
            foreach ($stats as $processId => $processStats) 
            {
                if(count($processStats))
                {
                    if(key_exists('total', $processStats) && key_exists('type', $processStats))
                    {
                        
                        # save totals 
                        $delivered = intval($processStats['total']['delivered']);
                        $softBounced = intval($processStats['total']['soft_bounced']);
                        $hardBounced = intval($processStats['total']['hard_bounced']);
                        
                        if($delivered > 0 || $softBounced > 0 || $hardBounced > 0)
                        {
                            $this->app->database('system')->execute("UPDATE production.mta_processes SET delivered = delivered + " . $delivered . " , soft_bounced = soft_bounced + " . $softBounced . " , hard_bounced = hard_bounced + " . $hardBounced . " WHERE id = {$processId}");
                        }
                        
                        if($processStats['type'] == 'md')
                        {
                            foreach ($processStats as $vmtaId => $vmtasStats) 
                            {
                                if($vmtaId != 'total' && $vmtaId != 'type' && count($vmtasStats))
                                {
                                    $delivered = intval($processStats[$vmtaId]['delivered']);
                                    $softBounced = intval($processStats[$vmtaId]['soft_bounced']);
                                    $hardBounced = intval($processStats[$vmtaId]['hard_bounced']);

                                    if($delivered > 0 || $softBounced > 0 || $hardBounced > 0)
                                    {
                                        $this->app->database('system')->execute("UPDATE production.mta_processes_ips SET delivered = delivered + " . $delivered . " , soft_bounced = soft_bounced + " . $softBounced . " , hard_bounced = hard_bounced + " . $hardBounced . " WHERE process_id = {$processId} AND server_vmta_id = {$vmtaId}");
                                    }
                                }
                            }
                        }
                    }
                }  
            }
        }

        # calculate bounce and clean emails
        if(count($bounceEmails) > 0 || count($cleanEmails) > 0)
        {   
            # prepare lists 
            $lists = [];
            
            # get lists from db
            $dataLists = $this->app->database('system')->query()->from('lists.data_lists')->all();
            
            if(count($dataLists) == 0)
            {
                Page::printApiResults(500,'No data lists found !');
            }
            
            foreach ($dataLists as $row) 
            {
                $lists[intval($row['id'])] = strtolower($row['table_schema'] . '.' . $row['table_name']);
            }

            # connect to clients database
            $this->app->database('clients')->connect();
            
            # update clean emails
            foreach ($cleanEmails as $row) 
            {
                if(count($row))
                {
                    $parsed = explode('_',$row);
                    $listId = intval($this->app->utils->arrays->get($parsed,0));
                    $clientId = intval($this->app->utils->arrays->get($parsed,1));
                    
                    if(key_exists($listId,$lists))
                    {
                        $parsed = explode('.',$lists[$listId]);
                        $schema = $this->app->utils->arrays->get($parsed,0);
                        $table = $this->app->utils->arrays->get($parsed,1);
                            
                        if(Table::exists('clients',$table,$schema))
                        {
                            # update client flags
                            $this->app->database('clients')->execute("UPDATE {$lists[$listId]} SET is_clean = 't' , is_fresh = 'f' WHERE id = $clientId AND is_fresh = 't'");
                        }
                    }
                }
            }
            
            # update bounce emails
            foreach ($bounceEmails as $row) 
            {
                if(count($row))
                {
                    $parsed = explode('_',$row);
                    $listId = intval($this->app->utils->arrays->get($parsed,0));
                    $clientId = intval($this->app->utils->arrays->get($parsed,1));
                    
                    if(key_exists($listId,$lists))
                    {
                        $parsed = explode('.',$lists[$listId]);
                        $schema = $this->app->utils->arrays->get($parsed,0);
                        $table = $this->app->utils->arrays->get($parsed,1);
                            
                        if(Table::exists('clients',$table,$schema))
                        {
                            # update client flags
                            $this->app->database('clients')->execute("UPDATE {$lists[$listId]} SET is_hard_bounced = 't' WHERE id = $clientId");
                        }
                    }
                }
            }
        }
        
        Page::printApiResults(200,'Operation completed successfully !');
    }
}