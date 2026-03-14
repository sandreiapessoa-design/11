<?php declare(strict_types=1); namespace IR\App\Webservices; if (!defined('IR_START')) exit('<pre>No direct script access allowed</pre>');
/**
 * @framework       iResponse Framework 
 * @version         1.0
 * @author          Amine Idrissi <contact@iresponse.tech>
 * @date            2019
 * @name            Servers.php	
 */

# core 
use IR\Core\Base as Base;
use IR\Core\Application as Application;

# helpers 
use IR\App\Helpers\Authentication as Authentication;
use IR\App\Helpers\Permissions as Permissions;
use IR\App\Helpers\Page as Page;
use IR\App\Helpers\Api as Api;
use IR\App\Helpers\AuditLog as AuditLog;

# models
use IR\App\Models\Admin\ManagementServer as ManagementServer;
use IR\App\Models\Admin\MtaServer as MtaServer;
use IR\App\Models\Admin\ServerVmta as ServerVmta;
use IR\App\Models\Admin\SmtpServer as SmtpServer;
use IR\App\Models\Admin\SmtpUser as SmtpUser;
use IR\App\Models\Admin\Domain as Domain;
use IR\App\Models\Admin\ProxyServer as ProxyServer;

# utilities
use IR\Utils\System\Terminal as Terminal;

/**
 * @name Servers
 * @description Servers WebService
 */
class Servers extends Base
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
     * @name checkMtaServer
     * @description checkMtaServer action
     * @before init
     */
    public function checkMtaServer($parameters = []) 
    { 
        # check for authentication
        if(!Authentication::isUserAuthenticated())
        {
            Page::printApiResults(401,'Only logged-in access allowed !');
        }
        
        # check users roles 
        Authentication::checkUserRoles();
        
        # check for permissions
        $access = Permissions::checkForAuthorization(Authentication::getAuthenticatedUser(),'MtaServers','edit');

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

            # call iresponse api
            $result = Api::call('Servers','checkServer',['server-id' => $serverId,'server-type' => 'mta']);
            
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
            Page::printApiResults(500,'Incorrect server Id !');
        }
    }
    
    /**
     * @name checkManagementServer
     * @description checkManagementServer action
     * @before init
     */
    public function checkManagementServer($parameters = []) 
    { 
        # check for authentication
        if(!Authentication::isUserAuthenticated())
        {
            Page::printApiResults(401,'Only logged-in access allowed !');
        }
        
        # check users roles 
        Authentication::checkUserRoles();
        
        # check for permissions
        $access = Permissions::checkForAuthorization(Authentication::getAuthenticatedUser(),'ManagementServers','edit');

        if($access == false)
        {
            Page::printApiResults(403,'Access Denied !');
        }
        
        $serverId = intval($this->app->utils->arrays->get($parameters,'server-id'));

        if($serverId > 0)
        {
            $managementServer = ManagementServer::first(ManagementServer::FETCH_ARRAY,['status = ? and id = ?',['Activated',$serverId]]);

            if(count($managementServer) == 0)
            {
                Page::printApiResults(404,'Management server not found !');
            }

            # call iresponse api
            $result = Api::call('Servers','checkServer',['server-id' => $serverId,'server-type' => 'management']);
            
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
            Page::printApiResults(500,'Incorrect server Id !');
        }
    }
    
    /**
     * @name checkSmtpServer
     * @description check SMTP Server action
     * @before init
     */
    public function checkSmtpServer($parameters = []) 
    { 
        # check for authentication
        if(!Authentication::isUserAuthenticated())
        {
            Page::printApiResults(401,'Only logged-in access allowed !');
        }
        
        # check users roles 
        Authentication::checkUserRoles();
        
        # check for permissions
        $access = Permissions::checkForAuthorization(Authentication::getAuthenticatedUser(),'SmtpServers','edit');

        if($access == false)
        {
            Page::printApiResults(403,'Access Denied !');
        }
        
        $serverId = intval($this->app->utils->arrays->get($parameters,'server-id'));
        $userId = intval($this->app->utils->arrays->get($parameters,'user-id'));

        if($serverId > 0 && $userId > 0)
        {
            $smtpServer = SmtpServer::first(SmtpServer::FETCH_ARRAY,['status = ? and id = ?',['Activated',$serverId]]);

            if(count($smtpServer) == 0)
            {
                Page::printApiResults(404,'Smtp server not found !');
            }

            $smtpUser = SmtpUser::first(SmtpUser::FETCH_ARRAY,['status = ? and id = ?',['Activated',$userId]]);

            if(count($smtpUser) == 0)
            {
                Page::printApiResults(404,'Smtp user not found !');
            }

            # call iresponse api
            $result = Api::call('Servers','checkServer',['server-id' => $serverId,'server-type' => 'smtp','smtp-user-id' => $userId]);
            
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
            Page::printApiResults(500,'Incorrect server or user id !');
        }
    }

    /**
     * @name extractServerRdns
     * @description extractServerRdns action
     * @before init
     */
    public function extractServerRdns($parameters = []) 
    { 
        # check for authentication
        if(!Authentication::isUserAuthenticated())
        {
            Page::printApiResults(401,'Only logged-in access allowed !'); 
        }
        
        # check users roles 
        Authentication::checkUserRoles();
        
        # check for permissions
        $access = Permissions::checkForAuthorization(Authentication::getAuthenticatedUser(),'MtaServers','edit');

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

            # call iresponse api
            $vmtas = ServerVmta::all(ServerVmta::FETCH_ARRAY,['status = ? and type = ? and mta_server_id = ?',['Activated','Default',$serverId]],['ip','domain']);
            
            if(count($vmtas) == 0)
            {
                Page::printApiResults(500,'No vmtas found !');
            }
            
            $rdns = 'Ip;RDNS' . PHP_EOL;
            
            foreach ($vmtas as $value)
            {
                $rdns .= "{$value['ip']};{$value['domain']}" . PHP_EOL;
            }
            
            Page::printApiResults(200,'RDNS exported successfully !',['rdns' => $rdns]);
        }
        else
        {
            Page::printApiResults(500,'Incorrect server Id !');
        }
    }
    
    /**
     * @name getProxies
     * @description get proxies action
     * @before init
     */
    public function getProxies($parameters = []) 
    { 
        # check for authentication
        if(!Authentication::isUserAuthenticated())
        {
            Page::printApiResults(401,'Only logged-in access allowed !');
        }
        
        # check users roles 
        Authentication::checkUserRoles();
        
        # check for permissions
        $access = Permissions::checkForAuthorization(Authentication::getAuthenticatedUser(),'MtaServers','proxies');

        if($access == false)
        {
            Page::printApiResults(403,'Access Denied !');
        }
        
        $proxiesIds = $this->app->utils->arrays->get($parameters,'proxy-ids');
        $proxyType = $this->app->utils->arrays->get($parameters,'proxy-type');
        
        if(count($proxiesIds) > 0)
        {
            $result = '';
            $proxies = ProxyServer::all(ProxyServer::FETCH_ARRAY,['status = ? and id in ?',['Activated',$proxiesIds]]);
            
            if(count($proxies) == 0)
            {
                Page::printApiResults(500,'Proxies not found !');
            }

            foreach ($proxies as $proxy) 
            {
                if(count($proxy))
                {
                    if($proxy['mta_server_id'] > 0)
                    {
                        $ips = ServerVmta::all(ServerVmta::FETCH_ARRAY,['status = ? and mta_server_id = ?',['Activated',$proxy['mta_server_id']]],['id','ip']);
                        
                        if(count($ips))
                        {
                            foreach ($ips as $ip) 
                            {
                                $result .= ($proxyType == 'http') ? $ip['ip'] . ':' . $proxy['http_port'] : $ip['ip'] . ':' . $proxy['socks_port'];
                                
                                if($proxy['proxy_username'] != null && $proxy['proxy_username'] != '')
                                {
                                    $result .= ':' . $proxy['proxy_username'] . ':' . $proxy['proxy_password'];
                                }
                                
                                $result .= '<br/>';
                            }
                        }
                    }
                }
            }

            Page::printApiResults(200,'',['proxies' => $result]);
        }
        else
        {
            Page::printApiResults(500,'Incorrect proxy id !');
        }
    }

    /**
     * @name installProxy
     * @description install proxy action
     * @before init
     */
    public function installProxy($parameters = []) 
    { 
        # check for authentication
        if(!Authentication::isUserAuthenticated())
        {
            Page::printApiResults(401,'Only logged-in access allowed !');
        }
        
        # check users roles 
        Authentication::checkUserRoles();
        
        # check for permissions
        $access = Permissions::checkForAuthorization(Authentication::getAuthenticatedUser(),'MtaServers','proxies');

        if($access == false)
        {
            Page::printApiResults(403,'Access Denied !');
        }
        
        $serversIds = $this->app->utils->arrays->get($parameters,'servers-ids',[]);
        $serversIds = is_array($serversIds) && count($serversIds) ? $serversIds : [intval($serversIds)];
        $username = $this->app->utils->arrays->get($parameters,'proxy-username');
        $password = $this->app->utils->arrays->get($parameters,'proxy-password');
        $proxyPort = intval($this->app->utils->arrays->get($parameters,'http-proxy-port'));
        $socksPort = intval($this->app->utils->arrays->get($parameters,'socks-proxy-port'));

        if((count($serversIds) == 0))
        {
            Page::printApiResults(500,'No mta servers found !');
        }

        if($proxyPort == 0)
        {
            Page::printApiResults(500,'Incorrect proxy port !');
        }

        # call iresponse api
        $data = [
            'servers-ids' => $serversIds,
            'username' => $username,
            'password' => $password,
            'proxy-port' => $proxyPort,
            'socks-port' => $socksPort
        ];
        
        $result = Api::call('Servers','installProxy',$data);

        if(count($result) == 0)
        {
            Page::printApiResults(500,'No response found !');
        }

        if($result['httpStatus'] == 500)
        {
            Page::printApiResults(500,$result['message']);
        }

        # register audit log
        AuditLog::registerLog(1,'Mta Servers ' . join(' ',$serversIds),'MtaServer','Install Proxy');
                
        Page::printApiResults(200,$result['message']);
    }
    
    /**
     * @name getRandomMapping
     * @description get random mapping action
     * @before init
     */
    public function getRandomMapping($parameters = []) 
    { 
        # check for authentication
        if(!Authentication::isUserAuthenticated())
        {
            Page::printApiResults(401,'Only logged-in access allowed !');
        }
        
        # check users roles 
        Authentication::checkUserRoles();
        
        # check for permissions
        $access = Permissions::checkForAuthorization(Authentication::getAuthenticatedUser(),'MtaServers','install');

        if($access == false)
        {
            Page::printApiResults(403,'Access Denied !');
        }
        
        $template = base64_decode($this->app->utils->arrays->get($parameters,'template'));
        $domains = $this->app->utils->arrays->get($parameters,'domains');
        $ipsv4Options = $this->app->utils->arrays->get($parameters,'ips-v4-options');
        $maxIps = intval($this->app->utils->arrays->get($parameters,'max-ips'));
        $domainsOptions = $this->app->utils->arrays->get($parameters,'domains-options');
        $mapping = '';
        $indexes = [];
        
        if($maxIps <= 0 || $maxIps > 60)
        {
            Page::printApiResults(500,'Max Ips should be between 1 and 60 !');
        }
        
        if($template != FALSE && count($domains) && count($ipsv4Options) && count($domainsOptions))
        {
            $start = $maxIps;
            $finish = $maxIps * 2;

            for ($i = 1; $i < count($domains); $i++) 
            {
                $html = '';
                $options = '';
                
                foreach ($domainsOptions as $domain) 
                {
                    if($this->app->utils->strings->indexOf($domain['html'],$domains[$i]) > -1)
                    {
                        $options .= '<option value="' . $domain['value'] . '" selected>' . $domain['html'] . '</option>';
                    }
                    else
                    {
                        $options .= '<option value="' . $domain['value'] . '">' . $domain['html'] . '</option>';
                    }
                }
                
                # implement domains
                $html = str_replace('$domains',$options,$template);
                
                $options = '';
                
                foreach ($ipsv4Options as $index => $option) 
                {
                    if($index >= $start && $index < $finish)
                    {
                        $options .= '<option value="' . $option['value'] . '" selected>' . $option['html'] . '</option>';
                    }
                    else
                    {
                        $options .= '<option value="' . $option['value'] . '">' . $option['html'] . '</option>';
                    }
                }
                
                # implement ips v4
                $html = str_replace('$ipsv4',$options,$html);
                $html = str_replace('$ipsv6','',$html);
                
                # increment values 
                $start = $start + $maxIps;
                $finish = $finish + $maxIps;
                
                # replace index 
                $html = str_replace('data-index="0"','data-index="' . $i . '"',$html);
                
                # implement it into the html
                $mapping .= $html;
                $indexes[] = $i;
            }
            
            Page::printApiResults(200,'',['mapping' => $mapping,'indexes' => $indexes]);
        }
        else
        {
            Page::printApiResults(500,'Incorrect parameters !');
        }
    }
    
    /**
     * @name getServerInfo
     * @description get Server Info
     * @before init
     */
    public function getServerInfo($parameters = []) 
    {
        # check for authentication
        if(!Authentication::isUserAuthenticated())
        {
            Page::printApiResults(401,'Only logged-in access allowed !');
        }
        
        # check users roles 
        Authentication::checkUserRoles();
        
        # check for permissions
        $access = Permissions::checkForAuthorization(Authentication::getAuthenticatedUser(),'MtaServers','install');

        if($access == false)
        {
            Page::printApiResults(403,'Access Denied !');
        }
        
        $serverId = intval($this->app->utils->arrays->get($parameters,'server-id'));
        $template = base64_decode($this->app->utils->arrays->get($parameters,'template'));
        
        if($serverId > 0)
        {
            # retrieve the server by id
            $server = MtaServer::first(MtaServer::FETCH_ARRAY,['id = ?',$serverId]);
            
            if(count($server) == 0)
            {
                Page::printApiResults(404,'Server not found !');
            }

            # call iresponse api
            $result = Api::callinstallation('Servers','getServerInfo',['server-id' => $serverId]);
            
            if(count($result) == 0)
            {
                Page::printApiResults(500,'No response found !');
            }
            
            if($result['httpStatus'] == 500)
            {
                Page::printApiResults(500,$result['message']);
            }
            
            $ipsv4 = array_unique(array_filter(explode(PHP_EOL,trim($result['data']['ips-v4'],PHP_EOL))));
            $ipsv6 = array_unique(array_filter(explode(PHP_EOL,trim($result['data']['ips-v6'],PHP_EOL))));

            if(count($ipsv4) == 0 && count($ipsv6) == 0)
            {
                Page::printApiResults(500,'This server contains no ips !');
            }

            # sort all ips
            natsort($ipsv4);
            natsort($ipsv6);

            $results['server-id'] = $serverId;
            $results['server-name'] = $server['name'];
            $results['server-ip'] = $server['main_ip'];
            $results['server-os'] = $result['data']['name'].' ' . intval($result['data']['version']) . ' ' . $result['data']['bits'];
            $results['server-ips-v4-sum'] = count($ipsv4);
            $results['server-ips-v6-sum'] = count($ipsv6);
            $results['server-ram'] = $this->app->utils->fileSystem->sizeReadable(intval($this->app->utils->arrays->get(explode(PHP_EOL,trim($result['data']['server-ram'],PHP_EOL)),0))*pow(1024,2),'GB');
            $results['server-storage'] = $this->app->utils->arrays->get(explode(PHP_EOL,trim($result['data']['server-storage'],PHP_EOL)),0);
            $results['ips-v4-options'] = '';
            $results['ips-v6-options'] = '';
            $results['domains-options'] = '';
            $results['mapping'] = '';
            $results['indexes'] = [];
            $check = [];
            $domain = '';
            $installedDomains = [];

            $vmtas = ServerVmta::all(ServerVmta::FETCH_ARRAY,['ip in ?',[array_unique(array_merge($ipsv4,$ipsv6))]]);
            foreach ($vmtas as $row) $check[$row['ip']] = $row;

            # filtering ips v4
            if($results['server-ips-v4-sum'] > 0)
            {
                foreach ($ipsv4 as $row) 
                {
                    if(in_array($row,array_keys($check)))
                    {
                        $domain = $this->app->utils->domains->getDomainFromURL($check[$row]['domain']);
                        $results['ips-v4-options'] .= '<option value="' . $check[$row]['id'] . '|' .  $check[$row]['ip'] . '" domain="' . $domain . '">' . $check[$row]['ip'] .'</option>';
                        $installedDomains[] = $domain;
                    }
                    else
                    {
                        $results['ips-v4-options'] .= '<option value="0|' . $row . '" domain="">' . $row . '</option>';
                    } 
                }
            }

            # filtering ips v6
            if($results['server-ips-v6-sum'] > 0)
            {
                foreach ($ipsv6 as $row) 
                {
                    if(in_array($row, array_keys($check)))
                    {
                        $domain = $this->app->utils->domains->getDomainFromURL($check[$row]['domain']);
                        $results['ips-v6-options'] .= '<option value="' . $check[$row]['id'] . '|' .  $check[$row]['ip'] . '" domain="' . $domain . '">' . $check[$row]['ip'] .'</option>';
                        $installedDomains[] = $domain;
                    }
                    else
                    {
                        $results['ips-v6-options'] .= '<option value="0|' . $row . '" domain="">' . $row . '</option>';
                    } 
                }
            }

            # remove duplicate installed domains 
            $installedDomains = array_unique($installedDomains);

            if(count($installedDomains))
            {
                $domainsFromDb = Domain::all(Domain::FETCH_ARRAY,['status = ? AND (availability = ? OR value IN ?)',['Activated','Available',$installedDomains]],['id','value','ip_id','account_name'],'id','DESC');

            }
            else
            {
                $domainsFromDb = Domain::all(Domain::FETCH_ARRAY,['status = ? AND availability = ?',['Activated','Available']],['id','value','ip_id','account_name'],'id','DESC');
            }

            $tmpDomains = [];

            foreach ($domainsFromDb as $domain)
            {
                $results['domains-options'] .= '<option value="' . $domain['id'] . '|' . $domain['value'] . '" data-domain-value="' . $domain['value'] . '">' . ' ( ' . $domain['account_name'] . ' ) ' . $domain['value'] . '</option>';

                if(in_array(trim($domain['value']),$installedDomains))
                {

                    $tmpDomains[$domain['value']] = $domain['id'] . '|' . $domain['value'];
                }
            }

            # check if there installed mappings 
            if(count($installedDomains))
            {
                # rewrap installed domains 
                $tmp = [];

                foreach ($installedDomains as $row) 
                {
                    $tmp[] = $row;
                }

                $installedDomains = $tmp;

                $results['first-domain'] = (key_exists($installedDomains[0],$tmpDomains)) ? $tmpDomains[$installedDomains[0]] : '';

                if(count($installedDomains) > 1)
                {
                    for ($index = 1; $index < count($installedDomains); $index++) 
                    {
                        if(key_exists($installedDomains[$index],$tmpDomains))
                        {
                            $domainValue = $tmpDomains[$installedDomains[$index]];
                            $installedDomain = $this->app->utils->arrays->get(explode('|',$domainValue),1);

                            $html = '';
                            $options = '';

                            foreach ($domainsFromDb as $domain)
                            {
                                $selected = ($installedDomain == $domain['value']) ? 'selected' : '';
                                $options .= '<option value="' . $domain['id'] . '|' . $domain['value'] . '" data-domain-value="' . $domain['value'] . '" ' . $selected . '>' . ' ( ' . $domain['account_name'] . ' ) ' . $domain['value'] . '</option>';
                            }

                            # implement domains
                            $html = str_replace('$domains',$options,$template);

                            $options = '';

                            # filtering ips v4
                            if($results['server-ips-v4-sum'] > 0)
                            {
                                foreach ($ipsv4 as $row) 
                                {
                                    if(in_array($row,array_keys($check)))
                                    {
                                        $domain = $this->app->utils->domains->getDomainFromURL($check[$row]['domain']);
                                        $selected = ($installedDomain == $domain) ? 'selected' : '';
                                        $options .= '<option value="' . $check[$row]['id'] . '|' .  $check[$row]['ip'] . '" domain="' . $domain . '" ' . $selected . '>' . $check[$row]['ip'] .'</option>';
                                    }
                                    else
                                    {
                                        $options .= '<option value="0|' . $row . '" domain="">' . $row . '</option>';
                                    } 
                                }
                            }

                            # implement ips v4
                            $html = str_replace('$ipsv4',$options,$html);

                            $options = '';

                            # filtering ips v6
                            if($results['server-ips-v6-sum'] > 0)
                            {
                                foreach ($ipsv6 as $row) 
                                {
                                    if(in_array($row,array_keys($check)))
                                    {
                                        $domain = $this->app->utils->domains->getDomainFromURL($check[$row]['domain']);
                                        $selected = ($installedDomain == $domain) ? 'selected' : '';
                                        $options .= '<option value="' . $check[$row]['id'] . '|' .  $check[$row]['ip'] . '" domain="' . $domain . '" ' . $selected . '>' . $check[$row]['ip'] .'</option>';
                                    }
                                    else
                                    {
                                        $options .= '<option value="0|' . $row . '" domain="">' . $row . '</option>';
                                    } 
                                }
                            }

                            # implement ips v6
                            $html = str_replace('$ipsv6',$options,$html);

                            # replace index 
                            $html = str_replace('data-index="0"','data-index="' . $index . '"',$html);

                            # implement it into the html
                            $results['mapping'] .= $html;
                            $results['indexes'][] = $index;
                        }
                    }
                }
            }

            Page::printApiResults(200,'',$results);
        }
        else
        {
            Page::printApiResults(500,'Incorrect server id !');
        }
    }
    
    /**
     * @name beginInstallation
     * @description begin server installation
     * @before init
     */
    public function beginInstallation($parameters = []) 
    {
        # check for authentication
        if(!Authentication::isUserAuthenticated())
        {
            Page::printApiResults(401,'Only logged-in access allowed !');
        }
        
        # check users roles 
        Authentication::checkUserRoles();
        
        # check for permissions
        $access = Permissions::checkForAuthorization(Authentication::getAuthenticatedUser(),'MtaServers','install');

        if($access == false)
        {
            Page::printApiResults(403,'Access Denied !');
        }
        
        $serverId = intval($this->app->utils->arrays->get($parameters,'server-id'));

        if($serverId > 0)
        {
            # start validation
            $server = MtaServer::first(MtaServer::FETCH_ARRAY,['id = ?',$serverId]);

            if(count($server) == 0)
            {
                Page::printApiResults(404,'Server not found !');
            }

            $updateIps = $this->app->utils->arrays->get($parameters,'update-ips');

            if($updateIps == 'enabled')
            {
                $domains = [];
                $mapping = $this->app->utils->arrays->get($parameters,'mapping');

                foreach ($mapping as $map) 
                {
                    if(is_array($map) && count($map))
                    {
                        $domain = (key_exists('domain',$map)) ? $map['domain'] : '';
                        $ips4sum = (key_exists('ips-v4',$map)) ? count($map['ips-v4']) : 0;
                        $ips6sum = (key_exists('ips-v6',$map)) ? count($map['ips-v6']) : 0;

                        if($domain == '')
                        {
                            Page::printApiResults(500,'No domain found !');
                        }

                        if($ips4sum == 0 && $ips6sum == 0)
                        {
                            Page::printApiResults(500,'Each mapping should have at least one ip ( v4 or v6 ) !');
                        }

                        if(in_array($map['domain'], $domains))
                        {
                            Page::printApiResults(500,'Domains should be appearing only once in mapping !');
                        }

                        $domains[] = $domain;
                    }
                }
            }
     
            # log file path 
            $logFile = LOGS_PATH . '/installations/inst_' . $serverId . '.log';
            $processFile = LOGS_PATH . '/installations/inst_' . $serverId . '_proc.log';
            
            # start procces logging 
            $this->app->utils->terminal->cmd("> " . $logFile,Terminal::RETURN_NOTHING);
            $this->app->utils->terminal->cmd('echo "Installation Started !" > ' . $processFile,Terminal::RETURN_NOTHING);
          
            # call iresponse api
            $result = Api::callinstallation('Servers','installServer',$parameters,true,$logFile);
            
            if(count($result) == 0)
            {
                Page::printApiResults(500,'No response found !');
            }
            
            if($result['httpStatus'] == 500)
            {
                Page::printApiResults(500,$result['message']);
            }
            
            # register audit log
            AuditLog::registerLog($serverId,$server['name'],'MtaServer','Start Mta Servers Installation');
            
            Page::printApiResults(200,'Mta server installation started !',['server-id' => $serverId]);
        }
        else
        {
            Page::printApiResults(500,'Incorrect server id !');
        }
    }
    
    /**
     * @name getInstallationLogs
     * @description get installation logs
     * @before init
     */
    public function getInstallationLogs($parameters = []) 
    {
        # check for authentication
        if(!Authentication::isUserAuthenticated())
        {
            Page::printApiResults(401,'Only logged-in access allowed !');
        }
        
        # check users roles 
        Authentication::checkUserRoles();
        
        # check for permissions
        $access = Permissions::checkForAuthorization(Authentication::getAuthenticatedUser(),'MtaServers','install');

        if($access == false)
        {
            Page::printApiResults(403,'Access Denied !');
        }
        
        $serverId = intval($this->app->utils->arrays->get($parameters,'server-id'));

        if($serverId > 0)
        {
            # log file path 
            $logFile = LOGS_PATH . '/installations/inst_' . $serverId . '.log';
            $processFile = LOGS_PATH . '/installations/inst_' . $serverId . '_proc.log';

            # read logs
            $logs = shell_exec("cat " . $logFile);
            $procc = shell_exec("cat " . $processFile);

            Page::printApiResults(200,'',['logs' => str_replace(PHP_EOL,'<br/>',$logs) , 'process' => $procc]);  
        }
        else
        {
            Page::printApiResults(500,'Incorrect server id !');
        }
    }

    /**
     * @name installGapi
     * @description install proxy action
     * @before init
     */
    public function installGapi($parameters = []) 
    { 
        # check for authentication
        if(!Authentication::isUserAuthenticated())
        {
            Page::printApiResults(401,'Only logged-in access allowed !');
        }
        
        # check users roles 
        Authentication::checkUserRoles();
        
        # check for permissions
        $access = Permissions::checkForAuthorization(Authentication::getAuthenticatedUser(),'MtaServers','gapi');

        if($access == false)
        {
            Page::printApiResults(403,'Access Denied !');
        }
        
        $serversId =intval($this->app->utils->arrays->get($parameters,'server-id'));

        
        if((count($serversId) == 0))
        {
            Page::printApiResults(500,'No mta servers found !'); 
        }

        

        # call iresponse api
        $data = [
            'server-id' => $serversId,
            
        ];
        
        $result = Api::callGapi('Servers','installGapi',$data);

        if(count($result) == 0)
        {
            Page::printApiResults(500,'No response found !');
        }

        if($result['httpStatus'] == 500)
        {
            Page::printApiResults(500,$result['message']);
        }

        # register audit log
        AuditLog::registerLog(1,'Mta Servers ' . join(' ',$serversId),'MtaServer','Install Gapi');
                
        Page::printApiResults(200,$result['message']);
    }
    
    /**
     * @name clearInstallationLogs
     * @description clear installation logs
     * @before init
     */
    public function clearInstallationLogs($parameters = []) 
    {
        # check for authentication
        if(!Authentication::isUserAuthenticated())
        {
            Page::printApiResults(401,'Only logged-in access allowed !');
        }
        
        # check users roles 
        Authentication::checkUserRoles();
        
        # check for permissions
        $access = Permissions::checkForAuthorization(Authentication::getAuthenticatedUser(),'MtaServers','install');

        if($access == false)
        {
            Page::printApiResults(403,'Access Denied !');
        }
        
        $serverId = intval($this->app->utils->arrays->get($parameters,'server-id'));

        if($serverId > 0)
        {
            # clear log files 
            $this->app->utils->fileSystem->deleteFile(LOGS_PATH . '/installations/inst_' . $serverId . '.log');
            $this->app->utils->fileSystem->deleteFile(LOGS_PATH . '/installations/inst_' . $serverId . '_proc.log');

            Page::printApiResults(200,'Logs cleared !');  
        }
        else
        {
            Page::printApiResults(500,'Incorrect server id !');
        }
    }
    
    /**
     * @name executeServersCommand
     * @description executeServersCommand action
     * @before init
     */
    public function executeServersCommand($parameters = []) 
    { 
        # check for authentication
        if(!Authentication::isUserAuthenticated())
        {
            Page::printApiResults(401,'Only logged-in access allowed !');
        }
        
        # check users roles 
        Authentication::checkUserRoles();
        
        # check for permissions
        $access = Permissions::checkForAuthorization(Authentication::getAuthenticatedUser(),'MtaServers','serversActions');

        if($access == false)
        {
            Page::printApiResults(403,'Access Denied !');
        }

        $servers = $this->app->utils->arrays->get($parameters,'servers');
        $command = $this->app->utils->arrays->get($parameters,'action');

        if(count($servers) > 0)
        {
            if($command == null || $command == '')
            {
                Page::printApiResults(404,'Server command not found !');
            }

            if(count($servers) == 0)
            {
                Page::printApiResults(404,'Servers not found !');
            }
                 
            # call iresponse api
            $result = Api::call('Servers','executeCommand',['servers-ids' => $servers,'command' => $command]);

            if(count($result) == 0)
            {
                Page::printApiResults(500,'No response found !');
            }
            
            if($result['httpStatus'] == 500)
            {
                Page::printApiResults(500,$result['message']);
            }
            
            if(!is_array($result['data']) || count($result['data']) == 0)
            {
                Page::printApiResults(500,'Error while trying to execute this command !');
            }

            $command = ucwords(str_replace('-',' ',$command));
            $logs = '###############################################  Command : ' . $command . '\'s Results #####################################################' . PHP_EOL . PHP_EOL;
            
            foreach ($result['data'] as $server => $log) 
            {
                
                $logs .= strtoupper($server) . ' :' . PHP_EOL;
                $logs .= '___________________________' . PHP_EOL . PHP_EOL;
                $logs .= $log . PHP_EOL;
                $logs .= '#######################################################################################################################################' . PHP_EOL . PHP_EOL;
            }
            
            Page::printApiResults(200,$result['message'],['logs' => $logs]);
        }
        else
        {
            Page::printApiResults(500,'No servers found !');
        }
    }
    
    /**
     * @name serversFilterSearch
     * @description serversFilterSearch
     * @before init
     */
    public function serversFilterSearch($parameters = []) 
    {
        # check for authentication
        if(!Authentication::isUserAuthenticated())
        {
            Page::printApiResults(401,'Only logged-in access allowed !');
        }
        
        # check users roles 
        Authentication::checkUserRoles();
        
        # check for permissions
        $access = Permissions::checkForAuthorization(Authentication::getAuthenticatedUser(),'MtaServers','proxies')
                || Permissions::checkForAuthorization(Authentication::getAuthenticatedUser(),'Pmta','commands')
                || Permissions::checkForAuthorization(Authentication::getAuthenticatedUser(),'Teams','manage')
                || Permissions::checkForAuthorization(Authentication::getAuthenticatedUser(),'Teams','members');
        
        if($access == false)
        {
            Page::printApiResults(403,'Access Denied !');
        }
        
        $serversIds = is_array($this->app->utils->arrays->get($parameters,'servers-ids')) ? array_filter($this->app->utils->arrays->get($parameters,'servers-ids')) : [];
        $type = $this->app->utils->arrays->get($parameters,'type');
        $values = $this->app->utils->arrays->get($parameters,'values',[]);
        $values = is_array($values) ? array_filter($values) : [];
        
        if($type != '' && count($values) > 0)
        {
            $where = [];
            $servers = [];
            $vmtas = [];
            $serversCondition = count($serversIds) ? " AND mta_server_id IN (" . implode(',',$serversIds) . ")" : "";
            
            switch ($type)
            {
                case 'vmtas-by-ip':
                {
                    $where = ['status = ? AND ip IN ?' . $serversCondition,['Activated',$values]];
                    break;
                }
                case 'vmtas-by-ip-rev':
                {
                    $where = ['status = ? AND ip NOT IN ?' . $serversCondition,['Activated',$values]];
                    break;
                }
                case 'vmtas-by-rdns':
                {
                    $where = ['status = ? AND domain IN ?' . $serversCondition,['Activated',$values]];
                    break;
                }
                case 'vmtas-by-rdns-rev': 
                {
                    $where = ['status = ? AND domain NOT IN ?' . $serversCondition,['Activated',$values]];
                    break;
                }
                case 'vmtas-by-domain-rev':
                case 'vmtas-by-domain':
                {
                    $cond = [];

                    foreach ($values as $value) 
                    {
                        $cond[] = $this->app->utils->domains->getDomainFromURL($value);
                    }

                    $where = ["status = ? AND ( domain IN ? OR domain SIMILAR TO '%(" . implode('|', $cond) . ")')" . $serversCondition,['Activated',$values]];
                    break;
                }
            }
            
            if(count($where))
            {
                $results = ServerVmta::all(ServerVmta::FETCH_ARRAY,$where);

                if(!empty($results))
                {
                    foreach ($results as $line) 
                    {
                        if(!in_array($line['mta_server_id'],$servers))
                        {
                            $servers[] = intval($line['mta_server_id']);
                        }

                        if(!in_array($line['ip'],$vmtas))
                        {
                            $vmtas[] = $line['ip'];
                        }
                    }
                }
            }

            Page::printApiResults(200,'',['servers' => $servers , 'vmtas' => $vmtas]); 
        }
        else
        {
            Page::printApiResults(500,'Incorrect parameters !');
        }
    }
    
    /**
     * @name configureAdditionalIps
     * @description add additional ips action
     * @before init
     */
    public function configureAdditionalIps($parameters = []) 
    {
        # check for authentication
        if(!Authentication::isUserAuthenticated())
        {
            Page::printApiResults(401,'Only logged-in access allowed !');
        }
        
        # check users roles 
        Authentication::checkUserRoles();
        
        # check for permissions
        $access = Permissions::checkForAuthorization(Authentication::getAuthenticatedUser(),'MtaServers','configureAdditionalIps');

        if($access == false)
        {
            Page::printApiResults(403,'Access Denied !');
        }
        
        $serverId = intval($this->app->utils->arrays->get($parameters,'serverId'));
        $lines = array_filter(explode(PHP_EOL,$this->app->utils->arrays->get($parameters,'lines','')));
        $method = 'configureAdditionalIps';
        
        foreach ($lines as $line)
        {
            if($this->app->utils->strings->contains($line,"|"))
            {
                $data = array_filter(explode("|",$line));
            
                for ($index = 0; $index < count($data); $index++)
                {
                    if(!filter_var(trim($data[$index]),FILTER_VALIDATE_IP,FILTER_FLAG_IPV4))
                    {
                        Page::printApiResults(500,$data[$index] . '  is not a valid Ip !');
                    }
                }
                
                //$method = 'configureAdditionalRanges';
            }
            else
            {
                if(!filter_var(trim($line),FILTER_VALIDATE_IP,FILTER_FLAG_IPV4))
                {
                    Page::printApiResults(500,$line . '  is not a valid Ip !');
                }
            }
        }
        
        # call iresponse api
        $result = Api::call('Servers',$method,['server-id' => $serverId,'lines' => $lines]);

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
        $access = Permissions::checkForAuthorization(Authentication::getAuthenticatedUser(),'ManagementServers','add')
                || Permissions::checkForAuthorization(Authentication::getAuthenticatedUser(),'ManagementServers','edit');

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
        
        if($accountId > 0)
        {
            $where = ['status = ? and account_id = ? and account_type = ?',['Special',$accountId,$accountType]];
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