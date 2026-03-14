<?php declare(strict_types=1); namespace IR\App\Controllers; if (!defined('IR_START')) exit('<pre>No direct script access allowed</pre>');
/**
 * @framework       iResponse Framework 
 * @version         1.0
 * @author          Amine Idrissi <contact@iresponse.tech>
 * @date            2024
 * @name            MtaServers.php	
 */

# core 
use IR\Core\Application as Application;

# mvc 
use IR\Mvc\Controller as Controller;

# models
use IR\App\Models\Admin\ServerProvider as ServerProvider;
use IR\App\Models\Admin\MtaServer as MtaServer;
use IR\App\Models\Admin\ServerVmta as ServerVmta;
use IR\App\Models\Admin\ProxyServer as ProxyServer;

# http 
use IR\Http\Request as Request;

# helpers 
use IR\App\Helpers\Authentication as Authentication;
use IR\App\Helpers\Page as Page;
use IR\App\Helpers\DataTable as DataTable;
use IR\App\Helpers\Permissions as Permissions;

# exceptions
use IR\Exceptions\Types\PageException as PageException;

/**
 * @name MtaServers
 * @description MtaServers Controller
 */
class MtaServers extends Controller
{
    /**
     * @app
     * @readwrite
     */
    protected $app;
    
    /**
     * @app
     * @readwrite
     */
    protected $authenticatedUser;
    
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
        
        # connect to the database 
        $this->app->database('system')->connect();
        
        # check for authentication
        if(!Authentication::isUserAuthenticated())
        {
            Page::redirect($this->app->http->request->getBaseURL() . RDS . 'auth' . RDS . 'login.' . DEFAULT_EXTENSION);
        }
        
        # check users roles 
        Authentication::checkUserRoles();
        
        # get the authenticated user
        $this->authenticatedUser = Authentication::getAuthenticatedUser();
    }
    
    /**
     * @name main
     * @description the main action
     * @before init
     * @after closeConnections,checkForMessage
     */
    public function main() 
    { 
        # check for permissions
        $access = Permissions::checkForAuthorization($this->authenticatedUser,__CLASS__,__FUNCTION__);

        if($access == false)
        {
            throw new PageException('Access Denied !',403);
        }
        
        # preparing the columns array to create the list
        $columnsArray = [
            'id',
            'name',
            'provider_name',
            'status',
            'host_name',
            'main_ip',
            'ips',
            'ssh_status',
            'os',
            'country',
            'expiration_date'
        ];
            
        # creating the html part of the list 
        $columns = Page::createTableHeader($columnsArray);
        $filters = Page::createTableFilters($columnsArray);
            
        # set menu status
        $this->masterView->set([
            'servers_management' => 'true',
            'mta_servers' => 'true',
            'mta_servers_show' => 'true'
        ]);
        
        # set data to the page view
        $this->pageView->set([
            'columns' => $columns,
            'filters' => $filters
        ]);
    }
    
    /**
     * @name get
     * @description the get action
     * @before init
     * @after closeConnections
     */
    public function get() 
    { 
        # check for permissions
        $access = Permissions::checkForAuthorization($this->authenticatedUser,__CLASS__,'main');

        if($access == false)
        {
            throw new PageException('Access Denied !',403);
        }
        
        # get post data
        $data = $this->app->http->request->retrieve(Request::ALL,Request::POST);

        if(count($data))
        {
            $url = $this->app->http->request->getBaseURL();
            
            # preparing the columns array to create the list
            $columns = [
                'id',
                'name',
                'provider_name',
                'status',
                'host_name',
                'main_ip',
                'ips_count' => 'ips',
                'ssh_connectivity_status' => 'ssh_status',
                'os',
                "CASE WHEN country_code IS NOT NULL AND country_code NOT LIKE '' THEN '<img src=\"{$url}/images/flags/' || LOWER(country_code) || '.png\" alt=\"' || country_code || '\"/>' ELSE 'Not Checked' END" => 'country',
                'expiration_date'
            ];

            # fetching the results to create the ajax list
            die(json_encode(DataTable::init($data,'admin.mta_servers s',$columns,new MtaServer(),'mta-servers','DESC',null)));
        }
    }
    
    /**
     * @name add
     * @description the add action
     * @before init
     * @after closeConnections,checkForMessage
     */
    public function add() 
    { 
        # check for permissions
        $access = Permissions::checkForAuthorization($this->authenticatedUser,__CLASS__,__FUNCTION__);

        if($access == false)
        {
            throw new PageException('Access Denied !',403);
        }
        
        # set menu status
        $this->masterView->set([
            'servers_management' => 'true',
            'mta_servers' => 'true',
            'mta_servers_add' => 'true'
        ]);
        
        # set data to the page view
        $this->pageView->set([
            'serversProviders' => ServerProvider::all(ServerProvider::FETCH_ARRAY,['status = ?','Activated'],['id','name'])
        ]); 
    }
    
    /**
     * @name multiAdd
     * @description the multi add action
     * @before init
     * @after closeConnections,checkForMessage
     */
    public function multiAdd() 
    { 
        # check for permissions
        $access = Permissions::checkForAuthorization($this->authenticatedUser,__CLASS__,'add');

        if($access == false)
        {
            throw new PageException('Access Denied !',403);
        }
        
        # set menu status
        $this->masterView->set([
            'servers_management' => 'true',
            'mta_servers' => 'true',
            'mta_servers_multi_add' => 'true'
        ]);
        
        # set data to the page view
        $this->pageView->set([
            'serversProviders' => ServerProvider::all(ServerProvider::FETCH_ARRAY,['status = ?','Activated'],['id','name'])
        ]); 
    }
    
    /**
     * @name edit
     * @description the edit action
     * @before init
     * @after closeConnections,checkForMessage
     */
    public function edit() 
    { 
        # check for permissions
        $access = Permissions::checkForAuthorization($this->authenticatedUser,__CLASS__,__FUNCTION__);

        if($access == false)
        {
            throw new PageException('Access Denied !',403);
        }
        
        $arguments = func_get_args(); 
        $id = isset($arguments) && count($arguments) > 0 ? $arguments[0] : null;
        $valid = true;
        
        # set menu status
        $this->masterView->set([
            'servers_management' => 'true',
            'mta_servers' => 'true',
            'mta_servers_show' => 'true'
        ]);
        
        if(!isset($id) || !is_numeric($id) || intval($id) == 0)
        {
            $valid = false;
        }
        
        $server = MtaServer::first(MtaServer::FETCH_ARRAY,['id = ?',$id]);

        if(count($server) == 0)
        {
            $valid = false;
        }
        
        if($valid == true)
        {
            # preparing the columns array to create the list        
            $columnsArray = [
                'id',
                'name',
                'domain',
                'ip',
                'status',
                'ping_status',
                'type',
                'custom_domain',
                'created_by',
                'created_date'
            ];

            # creating the html part of the list 
            $columns = '';
            $filters = '';

            foreach ($columnsArray as $column) 
            {
                if($column != 'id')
                {
                    $columns .= '<th>' . ucwords(str_replace('_',' ',strtolower($column))) . '</th>' . PHP_EOL;

                    if(strpos($column,'_date') > -1 || strpos($column,'_time') > -1)
                    {
                        $filters .= '<td> <div id="' . $column . '_range" class="input-group date-range-picker"> <input type="text" class="form-control form-filter" name="' . $column . '_range"> <span class="input-group-btn"> <button class="btn default date-range-toggle" type="button"> <i class="fa fa-calendar"></i> </button> </span> </div> </td>' . PHP_EOL;
                    }
                    else
                    {
                        if($column == 'status')
                        {
                            $filters .= '<td> <select name="status" class="form-control form-filter input-sm"> <option value="" selected>All</option> <option value="Activated">Activated</option> <option value="Inactivated">Inactivated</option> </select> </td>' . PHP_EOL;
                        }
                        else
                        {
                            $filters .= '<td><input type="text" class="form-control form-filter" name="' . $column . '"></td>' . PHP_EOL;
                        }
                    }
                }
            }
            
            # set data to the page view
            $this->pageView->set([
                'server' => $server,
                'serversProviders' => ServerProvider::all(ServerProvider::FETCH_ARRAY,['status = ?','Activated'],['id','name']),
                'columns' => $columns,
                'filters' => $filters
            ]); 
        }
        else
        {
            # stores the message in the session 
            Page::registerMessage('error','Invalid mta server id !');
            
            # redirect to lists page
            Page::redirect();
        }
    }
    
    /**
     * @name save
     * @description the save action
     * @before init
     * @after closeConnections
     */
    public function save() 
    { 
        # get post data
        $data = $this->app->http->request->retrieve(Request::ALL,Request::POST);
        $files = $this->app->http->request->retrieve(Request::ALL,Request::FILES);
        
        $message = 'Internal server error !';
        $flag = 'error';

        if(count($data))
        {  
            $update = false;
            $server = new MtaServer();
            $username = $this->authenticatedUser->getEmail();

            # update case
            if($this->app->utils->arrays->get($data,'id') > 0)
            {
                # check for permissions
                $access = Permissions::checkForAuthorization($this->authenticatedUser,__CLASS__,'edit');

                if($access == false)
                {
                    throw new PageException('Access Denied !',403);
                }
        
                $update = true;
                $message = 'Record updated succesfully !';
                $server->setId(intval($this->app->utils->arrays->get($data,'id')));
                $server->load();
                $server->setLastUpdatedBy($username);
                $server->setLastUpdatedDate(date('Y-m-d'));
            }
            else
            {
                # check for permissions
                $access = Permissions::checkForAuthorization($this->authenticatedUser,__CLASS__,'add');

                if($access == false)
                {
                    throw new PageException('Access Denied !',403);
                }
        
                $message = 'Record stored succesfully !';
                $server->setSshConnectivityStatus('Not Checked');
                $server->setCreatedBy($username);
                $server->setCreatedDate(date('Y-m-d'));
                $server->setLastUpdatedBy($username);
                $server->setLastUpdatedDate(date('Y-m-d'));
                $server->setIpsCount(0);
                $server->setIsInstalled('f');
            }

            $provider = ServerProvider::first(ServerProvider::FETCH_ARRAY,['id = ?',intval($this->app->utils->arrays->get($data,'server-provider'))]);
            $result = -1;
            
            if(count($provider) == 0)
            {
                $message = 'Provider not found !';
            }
            else
            {
                $server->setName(trim($this->app->utils->arrays->get($data,'server-name')));
                $server->setStatus($this->app->utils->arrays->get($data,'server-status','Activated'));
                $server->setProviderId(intval($this->app->utils->arrays->get($provider,'id')));
                $server->setProviderName($this->app->utils->arrays->get($provider,'name'));
                $server->setHostName(trim($this->app->utils->arrays->get($data,'server-hostname')));
                $server->setMainIp(trim($this->app->utils->arrays->get($data,'server-ip')));
                $server->setSshLoginType($this->app->utils->arrays->get($data,'server-login-type'));
                $server->setSshUsername(trim($this->app->utils->arrays->get($data,'server-username')));
                $server->setSshPort(trim($this->app->utils->arrays->get($data,'server-ssh-port')));
                $server->setOldSshPort(trim($this->app->utils->arrays->get($data,'server-ssh-port')));
                $server->setExpirationDate($this->app->utils->arrays->get($data,'expiration-date'));
                
                switch ($this->app->utils->arrays->get($data,'server-login-type'))
                {
                    case 'user-pass':
                    {
                        $server->setSshPassword($this->app->utils->arrays->get($data,'server-password'));
                        $server->setOldSshPassword($this->app->utils->arrays->get($data,'server-password'));
                        break;
                    }
                    case 'pem':
                    {
                        $tmpFileName = $this->app->utils->arrays->get($this->app->utils->arrays->get($files,'server-pem-file'),'tmp_name');
   
                        if($tmpFileName != null && $tmpFileName != '')
                        {
                            $server->setSshPemContent($this->app->utils->fileSystem->readFile($tmpFileName));
                        }

                        $server->setSshPassphrase($this->app->utils->arrays->get($data,'server-passphrase'));
                        break;
                    }
                    case 'key-pairs':
                    {
                        $tmpFileName = $this->app->utils->arrays->get($this->app->utils->arrays->get($files,'server-private-key'),'tmp_name');
   
                        if($tmpFileName != null && $tmpFileName != '')
                        {
                            $server->setSshPrivateKey($this->app->utils->fileSystem->readFile($tmpFileName));
                        }
                        
                        $tmpFileName = $this->app->utils->arrays->get($this->app->utils->arrays->get($files,'server-public-key'),'tmp_name');
   
                        if($tmpFileName != null && $tmpFileName != '')
                        {
                            $server->setSshPublicKey($this->app->utils->fileSystem->readFile($tmpFileName));
                        }

                        $server->setSshPassphrase($this->app->utils->arrays->get($data,'server-passphrase'));
                        break;
                    }
                }
                
                $result = $update == false ? $server->insert() : $server->update(); 

                if($result > -1)
                {
                    $flag = 'success';
                }
            }
        }
        
        # stores the message in the session 
        Page::registerMessage($flag, $message);

        # redirect to lists page
        Page::redirect();
    }

    /**
     * @name multiSave
     * @description the multi save action
     * @before init
     * @after closeConnections
     */
    public function multiSave() 
    { 
        # check for permissions
        $access = Permissions::checkForAuthorization($this->authenticatedUser,__CLASS__,'add');

        if($access == false)
        {
            throw new PageException('Access Denied !',403);
        }
                
        # get post data
        $data = $this->app->http->request->retrieve(Request::ALL,Request::POST);

        $message = 'Internal server error !';
        $flag = 'error';

        if(count($data))
        {  
            $provider = ServerProvider::first(ServerProvider::FETCH_ARRAY,['id = ?',intval($this->app->utils->arrays->get($data,'server-provider'))]);
            $result = -1;
            
            if(count($provider) == 0)
            {
                $message = 'Provider not found !';
            }
            else
            {
                $username = $this->authenticatedUser->getEmail();
                $mtaServerPrefix = str_replace([' ','_','-'],'_',trim($this->app->utils->arrays->get($data,'server-prefix-name')));
                $sshUsername = $this->app->utils->arrays->get($data,'server-username','root');
                $sshPassword = $this->app->utils->arrays->get($data,'server-password');
                $ips = array_filter(array_unique(explode(PHP_EOL,$this->app->utils->arrays->get($data,'main-ips'))));
                
                if(!is_array($ips) || count($ips) == 0)
                {
                    $message = 'Main ips not found !';
                }
                else
                {
                    $result = -1;
                    $index = 1;
                    
                    # check for index 
                    $res = MtaServer::first(MtaServer::FETCH_ARRAY,["name LIKE '{$mtaServerPrefix}%'",[]],['name'],'id','DESC');
                    
                    if(count($res))
                    {
                        $index = intval($this->app->utils->arrays->last(explode('_',$res['name']))) + 1;
                    }
                    
                    foreach ($ips as $ip)
                    {
                        $res = MtaServer::first(MtaServer::FETCH_ARRAY,["main_ip = ?",$ip],['id'],'id','DESC');
                        
                        if(count($res) == 0)
                        {
                            $server = new MtaServer();
                            $server->setSshConnectivityStatus('Not Checked');
                            $server->setCreatedBy($username);
                            $server->setCreatedDate(date('Y-m-d'));
                            $server->setLastUpdatedBy($username);
                            $server->setLastUpdatedDate(date('Y-m-d'));
                            $server->setIpsCount(0);
                            $server->setIsInstalled('f');
                            $server->setName($mtaServerPrefix . '_' . $index); 
                            $server->setStatus('Activated');
                            $server->setProviderId(intval($this->app->utils->arrays->get($provider,'id')));
                            $server->setProviderName($this->app->utils->arrays->get($provider,'name'));
                            $server->setHostName('');
                            $server->setMainIp(str_replace(["\n","\r"],'',$this->app->utils->strings->trim($ip)));
                            $server->setSshLoginType('user-pass');
                            $server->setSshUsername(str_replace(["\n","\r"],'',$this->app->utils->strings->trim($sshUsername)));
                            $server->setSshPort(22);
                            $server->setOldSshPort(22);
                            $server->setExpirationDate(date("Y-m-d",strtotime(date("Y-m-d",strtotime(date("Y-m-d"))) . "+1 month" )));
                            $server->setSshPassword(str_replace(["\n","\r"],'',$this->app->utils->strings->trim($sshPassword)));
                            $server->setOldSshPassword(str_replace(["\n","\r"],'',$this->app->utils->strings->trim($sshPassword)));
                            $result += $server->insert(); 
                            $index++;
                        }
                    }
                    
                    if($result > -1)
                    {
                        $flag = 'success';
                    }
                }
            }
        }
        
        # stores the message in the session 
        Page::registerMessage($flag, $message);

        # redirect to lists page
        Page::redirect();
    }
    
    /**
     * @name install
     * @description the install action
     * @before init
     * @after closeConnections,checkForMessage
     */
    public function install() 
    { 
        # check for permissions
        $access = Permissions::checkForAuthorization($this->authenticatedUser,__CLASS__,__FUNCTION__);

        if($access == false)
        {
            throw new PageException('Access Denied !',403);
        }
        
        # set menu status
        $this->masterView->set([
            'servers_management' => 'true',
            'mta_servers' => 'true',
            'mta_servers_install' => 'true'
        ]);
        
        $arguments = func_get_args(); 
        $serverId = isset($arguments) && count($arguments) > 0 ? intval($arguments[0]) : 0;
        
        # set data to the page view
        $this->pageView->set([
            'serverId' => $serverId,
            'servers' => MtaServer::all(MtaServer::FETCH_ARRAY,['status = ?','Activated'],['id','name','provider_name'],'id','DESC')
        ]); 
    }
    
    /**
     * @name proxies
     * @description the proxies action
     * @before init
     * @after closeConnections,checkForMessage
     */
    public function proxies() 
    { 
        # check for permissions
        $access = Permissions::checkForAuthorization($this->authenticatedUser,__CLASS__,__FUNCTION__);

        if($access == false)
        {
            throw new PageException('Access Denied !',403);
        }
        
        # set menu status
        $this->masterView->set([
            'servers_management' => 'true',
            'mta_servers' => 'true',
            'mta_servers_proxies' => 'true'
        ]);

        # preparing the columns array to create the list        
        $columnsArray = [
            'id',
            'name',
            'status',
            'http_port',
            'socks_port',
            'proxy_username',
            'proxy_password',
            'ips_count',
            'created_by',
            'created_date'
        ];

        # creating the html part of the list 
        $columns = '';
        $filters = '';

        foreach ($columnsArray as $column) 
        {
            if($column != 'id')
            {
                $columns .= '<th>' . ucwords(str_replace('_',' ',strtolower($column))) . '</th>' . PHP_EOL;

                if(strpos($column,'_date') > -1 || strpos($column,'_time') > -1)
                {
                    $filters .= '<td> <div id="' . $column . '_range" class="input-group date-range-picker"> <input type="text" class="form-control form-filter" name="' . $column . '_range"> <span class="input-group-btn"> <button class="btn default date-range-toggle" type="button"> <i class="fa fa-calendar"></i> </button> </span> </div> </td>' . PHP_EOL;
                }
                else
                {
                    if($column == 'status')
                    {
                        $filters .= '<td> <select name="status" class="form-control form-filter input-sm"> <option value="" selected>All</option> <option value="Activated">Activated</option> <option value="Inactivated">Inactivated</option> </select> </td>' . PHP_EOL;
                    }
                    else
                    {
                        $filters .= '<td><input type="text" class="form-control form-filter" name="' . $column . '"></td>' . PHP_EOL;
                    }
                }
            }
        }

        # set data to the page view
        $this->pageView->set([
            'servers' => MtaServer::all(MtaServer::FETCH_ARRAY,['status = ?','Activated'],['id','name','provider_name'],'id','DESC'),
            'columns' => $columns,
            'filters' => $filters
        ]); 
    }
    
    /**
     * @name serversActions
     * @description the serversActions action
     * @before init
     * @after closeConnections
     */
    public function serversActions() 
    { 
        # check for permissions
        $access = Permissions::checkForAuthorization($this->authenticatedUser,__CLASS__,__FUNCTION__);

        if($access == false)
        {
            throw new PageException('Access Denied !',403);
        }
        
        # set menu status
        $this->masterView->set([
            'servers_management' => 'true',
            'mta_servers' => 'true',
            'mta_servers_actions' => 'true'
        ]);
            
        # set data to the page view
        $this->pageView->set([
            'servers' => MtaServer::all(MtaServer::FETCH_ARRAY,['status = ?','Activated'],['id','name','provider_name'],'id','DESC')
        ]);
    }
    
    /**
     * @name getProxyServers
     * @description the getProxyServers action
     * @before init
     * @after closeConnections
     */
    public function getProxyServers() 
    { 
        # check for permissions
        $access = Permissions::checkForAuthorization($this->authenticatedUser,__CLASS__,'proxies');

        if($access == false)
        {
            throw new PageException('Access Denied !',403);
        }
        
        # get post data
        $data = $this->app->http->request->retrieve(Request::ALL,Request::POST);

        if(count($data))
        {
            # preparing the columns array to create the list
            $columns = [
                'id',
                'name',
                'status',
                'http_port',
                'socks_port',
                'proxy_username',
                'proxy_password',
                'ips_count',
                'created_by',
                'created_date'
            ];
        
            die(json_encode(DataTable::init($data,'admin.proxy_servers',$columns,new ProxyServer(),'mta-servers' . RDS . 'proxies','DESC',null,false)));
        }
    }
    
    /**
     * @name vmtas
     * @description the vmtas action
     * @before init
     * @after closeConnections
     */
    public function vmtas() 
    {
        # check for permissions
        $access = Permissions::checkForAuthorization($this->authenticatedUser,__CLASS__,'edit');

        if($access == false)
        {
            throw new PageException('Access Denied !',403);
        }
        
        # set menu status
        $this->masterView->set([
            'servers_management' => 'true',
            'mta_servers' => 'true',
            'mta_servers_show' => 'true'
        ]);
        
        $arguments = func_get_args();
        $page = isset($arguments) && count($arguments) ? $arguments[0] : '';
  
        if(isset($page) && $page != '')
        {
            switch ($page)
            {
                case 'get' : 
                {
                    # get post data
                    $data = $this->app->http->request->retrieve(Request::ALL,Request::POST);

                    if(count($data))
                    {
                        $serverId = isset($arguments) && count($arguments) ? intval($arguments[1]) : 0;
                        
                        # preparing the columns array to create the list
                        $columns = [
                            'id',
                            'name',
                            'domain',
                            'ip',
                            'status',
                            'ping_status',
                            'type',
                            'custom_domain',
                            'created_by',
                            'created_date'
                        ];
                        
                        # fetching the results to create the ajax list
                        $query = $this->app->database('system')->query()->from('admin.servers_vmtas',$columns)->where('mta_server_id = ?',$serverId);
                        die(json_encode(DataTable::init($data,'admin.servers_vmtas',$columns,new ServerVmta(),'mta-servers' . RDS . 'vmtas','DESC',$query,false)));
                    }
                    
                    break;
                }
            }
        }
    }
    
    /**
     * @name vmtasList
     * @description the vmtasList action
     * @before init
     * @after closeConnections,checkForMessage
     */
    public function vmtasList() 
    { 
        # check for permissions
        $access = Permissions::checkForAuthorization($this->authenticatedUser,__CLASS__,__FUNCTION__);

        if($access == false)
        {
            throw new PageException('Access Denied !',403);
        }
        
        # preparing the columns array to create the list
        $columnsArray = [
            'id',
            'status',
            'type',
            'mta_server_name',
            'ip',
            'domain',
            'custom_domain'
        ];
            
        # creating the html part of the list 
        $columns = '';
        $filters = '';

        foreach ($columnsArray as $column) 
        {
            if($column != 'id')
            {
                $columns .= '<th>' . ucwords(str_replace('_',' ',strtolower($column))) . '</th>' . PHP_EOL;

                if(strpos($column,'_date') > -1 || strpos($column,'_time') > -1)
                {
                    $filters .= '<td> <div id="' . $column . '_range" class="input-group date-range-picker"> <input type="text" class="form-control form-filter" name="' . $column . '_range"> <span class="input-group-btn"> <button class="btn default date-range-toggle" type="button"> <i class="fa fa-calendar"></i> </button> </span> </div> </td>' . PHP_EOL;
                }
                else
                {
                    if($column == 'status')
                    {
                        $filters .= '<td> <select name="status" class="form-control form-filter input-sm"> <option value="" selected>All</option> <option value="Activated">Activated</option> <option value="Inactivated">Inactivated</option> </select> </td>' . PHP_EOL;
                    }
                    else
                    {
                        $filters .= '<td><input type="text" class="form-control form-filter" name="' . $column . '"></td>' . PHP_EOL;
                    }
                }
            }
        }
            
        # set menu status
        $this->masterView->set([
            'servers_management' => 'true',
            'mta_servers' => 'true',
            'vmtas_show' => 'true'
        ]);
        
        # set data to the page view
        $this->pageView->set([
            'columns' => $columns,
            'filters' => $filters
        ]);
    }
    
    /**
     * @name getVmtasList
     * @description the getVmtasList action
     * @before init
     * @after closeConnections
     */
    public function getVmtasList() 
    { 
        # check for permissions
        $access = Permissions::checkForAuthorization($this->authenticatedUser,__CLASS__,'vmtasList');

        if($access == false)
        {
            throw new PageException('Access Denied !',403);
        }
        
        # get post data
        $data = $this->app->http->request->retrieve(Request::ALL,Request::POST);

        if(count($data))
        {
            # preparing the columns array to create the list
            $columns = [
                'v.id' => 'id',
                'v.status' => 'status',
                'v.type' => 'type',
                's.name' => 'mta_server_name',
                'v.ip' => 'ip',
                'v.domain' => 'domain',
                'v.custom_domain' => 'custom_domain'
            ];
        
            # fetching the results to create the ajax list
            $query = $this->app->database('system')->query()->from('admin.servers_vmtas v',$columns)->join('admin.mta_servers s','v.mta_server_id = s.id');
            die(json_encode(DataTable::init($data,'admin.servers_vmtas v',$columns,new ServerVmta(),'mta-servers' . RDS . 'vmtas-list','DESC',$query,false)));
        }
    }

    /**
     * @name configureAdditionalIps
     * @description configure additional ips action
     * @before init
     * @after closeConnections,checkForMessage
     */
    public function configureAdditionalIps() 
    { 
        
        # check for permissions
        $access = Permissions::checkForAuthorization($this->authenticatedUser,__CLASS__,__FUNCTION__);
       
        if($access == false)
        {
            throw new PageException('Access Denied !',403);
        }
        
        # set menu status
        $this->masterView->set([
            'servers_management' => 'true',
            'mta_servers' => 'true',
            'configure_additional_ips' => 'true'
        ]);
        
        
        # set data to the page view
        $this->pageView->set([
            'servers' => MtaServer::all(MtaServer::FETCH_ARRAY,['status = ?','Activated'],['id','name','provider_name'],'name','ASC')
        ]); 
    }

    /**
     * @name closeConnections
     * @description close all connections
     * @once
     * @protected
     */
    public function closeConnections() 
    {
        # connect to the database 
        $this->app->database('system')->disconnect();
        $this->app->database('clients')->disconnect();
    }
    
    /**
     * @name checkForMessage
     * @description checks for session messages
     * @once
     * @protected
     */
    public function checkForMessage() 
    {
        # check for message 
        Page::checkForMessage($this); 
    }
}