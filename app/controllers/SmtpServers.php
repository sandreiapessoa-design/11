<?php declare(strict_types=1); namespace IR\App\Controllers; if (!defined('IR_START')) exit('<pre>No direct script access allowed</pre>');
/**
 * @framework       iResponse Framework 
 * @version         1.0
 * @author          Amine Idrissi <contact@iresponse.tech>
 * @date            2024
 * @name            SmtpServers.php	
 */

# core 
use IR\Core\Application as Application;

# mvc 
use IR\Mvc\Controller as Controller;

# models
use IR\App\Models\Admin\ServerProvider as ServerProvider;
use IR\App\Models\Admin\SmtpServer as SmtpServer;
use IR\App\Models\Admin\SmtpUser as SmtpUser;

# http 
use IR\Http\Request as Request;

# helpers 
use IR\App\Helpers\Authentication as Authentication;
use IR\App\Helpers\Page as Page;
use IR\App\Helpers\DataTable as DataTable;
use IR\App\Helpers\Permissions as Permissions;
use IR\App\Helpers\Api as Api;

# exceptions
use IR\Exceptions\Types\PageException as PageException;

/**
 * @name SmtpServers
 * @description SmtpServers Controller
 */
class SmtpServers extends Controller
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
            'encryption_type',
            'smtp_port',
            'expiration_date'
        ];
        
        # creating the html part of the list 
        $columns = Page::createTableHeader($columnsArray);
        $filters = Page::createTableFilters($columnsArray);
            
        # set menu status
        $this->masterView->set([
            'servers_management' => 'true',
            'smtp_servers' => 'true',
            'smtp_servers_show' => 'true'
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
            # preparing the columns array to create the list
            $columns = [
                's.id' => 'id',
                's.name' => 'name',
                'p.name' => 'provider_name',
                's.status' => 'status',
                's.host_name' => 'host_name',
                's.encryption_type' => 'encryption_type',
                's.smtp_port' => 'smtp_port',
                's.expiration_date' => 'expiration_date'
            ];
        
            # fetching the results to create the ajax list
            $query = $this->app->database('system')->query()->from('admin.smtp_servers s',$columns)->join('admin.servers_providers p','s.provider_id = p.id');
            die(json_encode(DataTable::init($data,'admin.smtp_servers s',$columns,new SmtpServer(),'smtp-servers','DESC',$query)));
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
            'smtp_servers' => 'true',
            'smtp_servers_add' => 'true'
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
            'smtp_servers' => 'true',
            'smtp_servers_show' => 'true'
        ]);
        
        if(!isset($id) || !is_numeric($id) || intval($id) == 0)
        {
            $valid = false;
        }
        
        $server = SmtpServer::first(SmtpServer::FETCH_ARRAY,['id = ?',$id]);
        
        if(count($server) == 0)
        {
            $valid = false;
        }
        
        if($valid == true)
        {
            $columnsArray = [
                'id',
                'username',
                'password',
                'status',
                'proxy_ip',
                'proxy_port',
                'proxy_username',
                'proxy_password',
                'created_by',
                'created_date'
            ];

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
            Page::registerMessage('error','Invalid smtp server id !');
            
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

        $message = 'Internal server error !';
        $flag = 'error';

        if(count($data))
        {  
            $update = false;
            $server = new SmtpServer();
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
                $server->setCreatedBy($username);
                $server->setCreatedDate(date('Y-m-d'));
                $server->setLastUpdatedBy($username);
                $server->setLastUpdatedDate(date('Y-m-d'));
            }

            $provider = ServerProvider::first(ServerProvider::FETCH_ARRAY,['id = ?',intval($this->app->utils->arrays->get($data,'server-provider'))]);
            $result = -1;
            
            if(count($provider) == 0)
            {
                $message = 'Provider not found !';
            }
            else
            {
                $server->setName($this->app->utils->arrays->get($data,'server-name'));
                $server->setStatus($this->app->utils->arrays->get($data,'server-status','Activated'));
                $server->setProviderId(intval($this->app->utils->arrays->get($provider,'id')));
                $server->setProviderName(intval($this->app->utils->arrays->get($provider,'name')));
                $server->setHostName($this->app->utils->arrays->get($data,'host'));
                $server->setSmtpPort($this->app->utils->arrays->get($data,'port'));
                $server->setEncryptionType($this->app->utils->arrays->get($data,'encryption'));
                $server->setUsersCount(1);
                $server->setExpirationDate($this->app->utils->arrays->get($data,'expiration-date'));
                $result = $update == false ? $server->insert() : $server->update(); 
                    
                if($result > -1)
                {
                    if($update == false)
                    {
                        $user = new SmtpUser();
                        $user->setSmtpServerId(intval($result));
                        $user->setStatus($this->app->utils->arrays->get($data,'status','Activated'));
                        $user->setUsername($this->app->utils->arrays->get($data,'username'));
                        $user->setPassword($this->app->utils->arrays->get($data,'password'));
                        $user->setProxyIp($this->app->utils->arrays->get($data,'proxy-ip'));
                        $user->setProxyPort(intval($this->app->utils->arrays->get($data,'proxy-port',0)));
                        $user->setProxyUsername($this->app->utils->arrays->get($data,'proxy-username'));
                        $user->setProxyPassword($this->app->utils->arrays->get($data,'proxy-password'));
                        $user->setCreatedBy($username);
                        $user->setCreatedDate(date('Y-m-d'));
                        $user->setLastUpdatedBy($username);
                        $user->setLastUpdatedDate(date('Y-m-d'));
                        $user->insert();
                    }

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
     * @name smtpBulkCheck
     * @description the smtp bulk check action
     * @before init
     * @after closeConnections,checkForMessage
     */
    public function smtpBulkCheck() 
    { 
        # check for permissions
        $access = Permissions::checkForAuthorization($this->authenticatedUser,__CLASS__,__FUNCTION__);

        if($access == false)
        {
            throw new PageException('Access Denied !',403);
        }
        
        # get post data
        $data = $this->app->http->request->retrieve(Request::ALL,Request::POST);
        $results = '';
        
        if(count($data))
        {
            $flag = 'error';
            $message = 'Internal server error !';
            
            $smtps = array_filter(explode(PHP_EOL,strval($this->app->utils->arrays->get($data,'smtp-accounts'))));
            
            if(count($smtps))
            {
                # call iresponse api
                $accounts = [];
                
                foreach ($smtps as $smtp) 
                {
                    $smtp = explode(' ',$smtp);
                    
                    if(count($smtp) >= 5)
                    {
                        $account = [
                            'host' => $this->app->utils->strings->trim($smtp[0]),
                            'port' => $this->app->utils->strings->trim($smtp[1]),
                            'encryption' => $this->app->utils->strings->trim($smtp[2]),
                            'username' => $this->app->utils->strings->trim($smtp[3]),
                            'password' => $this->app->utils->strings->trim($smtp[4])
                        ];
                        
                        if(count($smtp) == 7)
                        {
                            $account['proxy-host'] = $this->app->utils->strings->trim($smtp[5]);
                            $account['proxy-port'] = $this->app->utils->strings->trim($smtp[5]);
                        }
                        
                        $accounts[] = $account;
                    }
                }
                
                $result = Api::call('Servers','smtpBulkCheck',['accounts' => $accounts]);
                
                if(count($result) == 0)
                {
                    $message = 'No response found !';
                }
                else
                {
                    if($result['httpStatus'] == 500)
                    {
                        $message = $result['message'];
                    }
                    elseif($result['httpStatus'] == 200 && count($result['data']))
                    {
                        
                        foreach ($result['data'] as $account)
                        {
                            $results .= "{$account['host']} {$account['port']} {$account['encryption']} {$account['username']} {$account['password']}";
                            
                            if(key_exists('proxy-host',$account))
                            {
                                $results .= " {$account['proxy-host']} {$account['proxy-port']}";
                            }
                            
                            $results .= PHP_EOL;
                        }
                        
                        if(strlen($results) > 0)
                        {
                            $flag = 'success';
                            $message = 'SMTP accounts check completed !';
                        }
                    }
                }
            }

            # stores the message in the session 
            Page::registerMessage($flag, $message);
        }
        
        # set menu status
        $this->masterView->set([
            'servers_management' => 'true',
            'smtp_servers' => 'true',
            'smtp_servers_bulk_check' => 'true'
        ]);
        
        # set data to the page view
        $this->pageView->set([
            'accounts' => $results
        ]);
    }
    
    /**
     * @name users
     * @description the users action
     * @before init
     * @after closeConnections
     */
    public function users() 
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
            'smtp_servers' => 'true',
            'smtp_servers_show' => 'true'
        ]);
        
        $arguments = func_get_args();
        $page = isset($arguments) && count($arguments) ? $arguments[0] : '';
  
        if(isset($page) && $page != '')
        {
            switch ($page)
            {
                case 'add' :
                {
                    $id = isset($arguments) && count($arguments) > 1 ? intval($arguments[1]) : 0;
                    $this->pageView->setFile(VIEWS_PATH . DS . 'smtp-servers' . DS . 'users' . DS . 'add.' . DEFAULT_EXTENSION);
                    
                    # set data to the page view
                    $this->pageView->set([
                        'server' => SmtpServer::first(SmtpServer::FETCH_ARRAY,['id = ?',$id])
                    ]);
                    
                    # check for message 
                    Page::checkForMessage($this);
                    break;
                }
                case 'edit' :
                {
                    $id = isset($arguments) && count($arguments) > 1 ? intval($arguments[1]) : 0;
                    $user = SmtpUser::first(SmtpUser::FETCH_ARRAY,['id = ?',$id]); 
                    
                    if(count($user) == 0)
                    {
                        # stores the message in the session 
                        Page::registerMessage('error','Invalid smtp user Id !');

                        # redirect to lists page
                        Page::redirect();
                    }
                    else
                    {
                        # set data to the page view
                        $this->pageView->set([
                            'user' => $user,
                            'server' => SmtpServer::first(SmtpServer::FETCH_ARRAY,['id = ?',$user['smtp_server_id']])
                        ]);
                    }

                    $this->pageView->setFile(VIEWS_PATH . DS . 'smtp-servers' . DS . 'users' . DS . 'edit.' . DEFAULT_EXTENSION);
                    
                    # check for message 
                    Page::checkForMessage($this);
                    break;
                }
                case 'save' :
                {
                    # get post data
                    $data = $this->app->http->request->retrieve(Request::ALL,Request::POST);

                    $message = 'Internal server error !';
                    $flag = 'error';
                    
                    if(count($data))
                    {        
                        $update = false;
                        
                        $username = Authentication::getAuthenticatedUser()->getEmail();
                        $server = SmtpServer::first(SmtpServer::FETCH_ARRAY,['id = ?',intval($this->app->utils->arrays->get($data,'server-id'))],['id','name']);

                        if(count($server))
                        {
                            # update case
                            if($this->app->utils->arrays->get($data,'id') > 0)
                            {
                                $update = true;
                                $message = 'Record updated succesfully !';
                                $user = new SmtpUser();
                                $user->setId(intval($this->app->utils->arrays->get($data,'id')));
                                $user->load();
                                $user->setSmtpServerId($this->app->utils->arrays->get($server,'id'));
                                $user->setSmtpServerName($this->app->utils->arrays->get($server,'name'));
                                $user->setUsername($this->app->utils->arrays->get($data,'username'));
                                $user->setPassword($this->app->utils->arrays->get($data,'password'));
                                $user->setProxyIp($this->app->utils->arrays->get($data,'proxy-ip'));
                                $user->setProxyPort(intval($this->app->utils->arrays->get($data,'proxy-port',0)));
                                $user->setProxyUsername($this->app->utils->arrays->get($data,'proxy-username'));
                                $user->setProxyPassword($this->app->utils->arrays->get($data,'proxy-password'));
                                $user->setStatus($this->app->utils->arrays->get($data,'status','Activated'));
                                $user->setLastUpdatedBy($username);
                                $user->setLastUpdatedDate(date('Y-m-d'));
                                $userid = $user->update();
                                
                                if($userid > -1)
                                {
                                    $flag = 'success';
                                }
                            }
                            else
                            {
                                $users = explode(PHP_EOL,$this->app->utils->arrays->get($data,'users'));
                                $usersObjects = [];
                                
                                if(count($users))
                                {
                                    foreach ($users as $line) 
                                    {
                                        if($this->app->utils->strings->indexOf($line,' ') != -1)
                                        {
                                            $lineParts = explode(' ',$line);
                                            
                                            if(count($lineParts) > 1)
                                            {
                                                $user = new SmtpUser();
                                                $user->setSmtpServerId($this->app->utils->arrays->get($server,'id'));
                                                $user->setSmtpServerName($this->app->utils->arrays->get($server,'name'));
                                                $user->setUsername($this->app->utils->arrays->first($lineParts));
                                                $user->setPassword($this->app->utils->arrays->get($lineParts,1));
                                                
                                                if(count($lineParts) == 4)
                                                {
                                                    $user->setProxyIp($this->app->utils->arrays->get($lineParts,2));
                                                    $user->setProxyPort(intval($this->app->utils->arrays->get($lineParts,3,0)));
                                                }
                                                else if(count($lineParts) == 6)
                                                {
                                                    $user->setProxyIp($this->app->utils->arrays->get($lineParts,2));
                                                    $user->setProxyPort(intval($this->app->utils->arrays->get($lineParts,3,0)));
                                                    $user->setProxyUsername($this->app->utils->arrays->get($lineParts,4));
                                                    $user->setProxyPassword($this->app->utils->arrays->get($lineParts,5));
                                                }
                                                
                                                $user->setStatus('Activated');
                                                $user->setCreatedBy($username);
                                                $user->setCreatedDate(date('Y-m-d'));
                                                $user->setLastUpdatedBy($username);
                                                $user->setLastUpdatedDate(date('Y-m-d'));
                                                $usersObjects[] = $user;
                                            }
                                        }
                                    }
                                    
                                    if(count($usersObjects))
                                    {
                                        $ids = SmtpUser::insertRows($usersObjects,SmtpUser::OBJECTS_ROWS);
                                        
                                        if(count($ids))
                                        {
                                            $flag = 'success';
                                            $message = 'Record(s) stored succesfully !';  
                                        }
                                    }
                                }
                            }
                        }
                    }

                    # stores the message in the session 
                    Page::registerMessage($flag, $message);

                    # redirect to lists page
                    Page::redirect();
                    break;
                }
                case 'get' : 
                {
                    # get post data
                    $data = $this->app->http->request->retrieve(Request::ALL,Request::POST);

                    if(count($data))
                    {
                        $serverId = isset($arguments) && count($arguments) ? intval($arguments[1]) : 0;
                        
                        $columns = [
                            'id',
                            'username',
                            'password',
                            'status',
                            'proxy_ip',
                            'proxy_port',
                            'proxy_username',
                            'proxy_password',
                            'created_by',
                            'created_date'
                        ];
                        
                        $query = $this->app->database('system')->query()->from('admin.smtp_users',$columns)->where('smtp_server_id = ?',$serverId);
                        die(json_encode(DataTable::init($data,'admin.smtp_users',$columns,new SmtpUser(),'smtp-servers' . RDS . 'users','DESC',$query)));
                    }
                    
                    break;
                }
            }
        }
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