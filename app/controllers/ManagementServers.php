<?php declare(strict_types=1); namespace IR\App\Controllers; if (!defined('IR_START')) exit('<pre>No direct script access allowed</pre>');
/**
 * @framework       iResponse Framework 
 * @version         1.0
 * @author          Amine Idrissi <contact@iresponse.tech>
 * @date            2019
 * @name            ManagementServers.php	
 */

# core 
use IR\Core\Application as Application;

# mvc 
use IR\Mvc\Controller as Controller;

# models
use IR\App\Models\Admin\ServerProvider as ServerProvider;
use IR\App\Models\Admin\ManagementServer as ManagementServer;
use IR\App\Models\Admin\Namecheap as Namecheap;
use IR\App\Models\Admin\GoDaddy as GoDaddy; 
use IR\App\Models\Admin\Namecom as Namecom;

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
 * @name ManagementServers
 * @description ManagementServers Controller
 */
class ManagementServers extends Controller
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
            'expiration_date'
        ];
            
        # creating the html part of the list 
        $columns = Page::createTableHeader($columnsArray);
        $filters = Page::createTableFilters($columnsArray);
            
        # set menu status
        $this->masterView->set([
            'servers_management' => 'true',
            'management_servers' => 'true',
            'management_servers_show' => 'true'
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
                'id',
                'name',
                'provider_name',
                'status',
                'host_name',
                'main_ip',
                'expiration_date'
            ];
        
            # fetching the results to create the ajax list
            die(json_encode(DataTable::init($data,'admin.management_servers',$columns,new ManagementServer(),'management-servers','DESC',null)));
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
            'management_servers' => 'true',
            'management_servers_add' => 'true'
        ]);
        
        $accounts = [];
        $namecheaps = Namecheap::all(Namecheap::FETCH_ARRAY,['status = ?','Activated'],['id','name']);
        $godaddies = GoDaddy::all(GoDaddy::FETCH_ARRAY,['status = ?','Activated'],['id','name']);
        $namecoms = Namecom::all(Namecom::FETCH_ARRAY,['status = ?','Activated'],['id','name']);
        
        foreach ($namecheaps as $row)
        {
            $accounts[] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'type' => 'namecheap'
            ];
        }
        
        foreach ($godaddies as $row)
        {
            $accounts[] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'type' => 'godaddy'
            ];
        }
        
        foreach ($namecoms as $row)
        {
            $accounts[] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'type' => 'namecom'
            ];
        }
        
        # set data to the page view
        $this->pageView->set([
            'serversProviders' => ServerProvider::all(ServerProvider::FETCH_ARRAY,['status = ?','Activated'],['id','name']),
            'accounts' => $accounts
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
            'management_servers' => 'true',
            'management_servers_show' => 'true'
        ]);
        
        if(!isset($id) || !is_numeric($id) || intval($id) == 0)
        {
            $valid = false;
        }
        
        $server = ManagementServer::first(ManagementServer::FETCH_ARRAY,['id = ?',$id]);
        
        if(count($server) == 0)
        {
            $valid = false;
        }
        
        if($valid == true)
        {
            # set data to the page view
            $this->pageView->set([
                'server' => $server,
                'serversProviders' => ServerProvider::all(ServerProvider::FETCH_ARRAY,['status = ?','Activated'],['id','name'])
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
            $server = new ManagementServer();
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
                $server->setProviderName($this->app->utils->arrays->get($provider,'name'));
                $server->setHostName($this->app->utils->arrays->get($data,'host-name'));
                $server->setMainIp($this->app->utils->arrays->get($data,'server-ip'));
                $server->setSshLoginType($this->app->utils->arrays->get($data,'server-login-type'));
                $server->setSshUsername($this->app->utils->arrays->get($data,'server-username'));
                $server->setSshPort($this->app->utils->arrays->get($data,'server-ssh-port'));
                $server->setExpirationDate($this->app->utils->arrays->get($data,'expiration-date'));
                
                switch ($this->app->utils->arrays->get($data,'server-login-type'))
                {
                    case 'user-pass':
                    {
                        $server->setSshPassword($this->app->utils->arrays->get($data,'server-password'));
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
                            $server->setSshPrublicKey($this->app->utils->fileSystem->readFile($tmpFileName));
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


