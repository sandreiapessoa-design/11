<?php declare(strict_types=1); namespace IR\App\Controllers; if (!defined('IR_START')) exit('<pre>No direct script access allowed</pre>');
/**
 * @framework       iResponse Framework 
 * @version         1.0
 * @author          Amine Idrissi <contact@iresponse.tech>
 * @date            2024
 * @name            AutoResponders.php	
 */

# core 
use IR\Core\Application as Application;

# mvc 
use IR\Mvc\Controller as Controller;

# models
use IR\App\Models\Production\AutoResponder as AutoResponder;
use IR\App\Models\Admin\MtaServer as MtaServer;
use IR\App\Models\Admin\SmtpServer as SmtpServer;
use IR\App\Models\Admin\ServerVmta as ServerVmta;
use IR\App\Models\Admin\SmtpUser as SmtpUser;
use IR\App\Models\Affiliate\AffiliateNetwork as AffiliateNetwork;

# http 
use IR\Http\Request as Request;

# helpers 
use IR\App\Helpers\Authentication as Authentication;
use IR\App\Helpers\Page as Page;
use IR\App\Helpers\DataTable as DataTable;
use IR\App\Helpers\Permissions as Permissions;

/**
 * @name AutoResponders
 * @description AutoResponders Controller
 */
class AutoResponders extends Controller
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
     * @after closeConnections
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
            'status',
            'server_name',
            'component_name',
            'type',
            'on_open',
            'on_click',
            'on_unsub',
            'on_optout'
        ];
        
        # creating the html part of the list 
        $columns = Page::createTableHeader($columnsArray);
        $filters = Page::createTableFilters($columnsArray);
        
        # set menu status
        $this->masterView->set([
            'auto_responders' => 'true',
            'auto_responders_show' => 'true'
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
                'status',
                'server_name',
                'component_name',
                'type',
                "CASE WHEN on_open = 't' THEN 'Enabled' ELSE 'Disabled' END" => 'on_open',
                "CASE WHEN on_click = 't' THEN 'Enabled' ELSE 'Disabled' END" => 'on_click',
                "CASE WHEN on_unsub = 't' THEN 'Enabled' ELSE 'Disabled' END" => 'on_unsub',
                "CASE WHEN on_optout = 't' THEN 'Enabled' ELSE 'Disabled' END" => 'on_optout'
            ];
            
            # fetching the results to create the ajax list
            die(json_encode(DataTable::init($data,'production.auto_responders',$columns,new AutoResponder(),'auto-responders','DESC')));
        }
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
        $access = Permissions::checkForAuthorization($this->authenticatedUser,__CLASS__,'create');

        if($access == false)
        {
            throw new PageException('Access Denied !',403);
        }
        
        $arguments = func_get_args(); 
        $id = isset($arguments) && count($arguments) > 0 ? $arguments[0] : null;
        Page::redirect($this->app->http->request->getBaseURL() . RDS . 'auto-responders' . RDS . 'create' . RDS . $id . RDS . DEFAULT_EXTENSION);
    }
    
    /**
     * @name create
     * @description the create action
     * @before init
     * @after closeConnections,checkForMessage
     */
    public function create()
    { 
        # check for permissions
        $access = Permissions::checkForAuthorization($this->authenticatedUser,__CLASS__,__FUNCTION__);

        if($access == false)
        {
            throw new PageException('Access Denied !',403);
        }
        
        $arguments = func_get_args(); 
        $id = isset($arguments) && count($arguments) > 0 ? intval($arguments[0]) : 0;
        
        # set menu status
        $this->masterView->set([
            'auto_responders' => 'true',
            'auto_responders_create' => 'true'
        ]);

        # set data to the page view
        $this->pageView->set([
            'servers' => MtaServer::all(MtaServer::FETCH_ARRAY,['status = ? AND is_installed = ?',['Activated','t']],['id','name','provider_name'],'name','ASC'),
            'affiliateNetworks' => AffiliateNetwork::all(AffiliateNetwork::FETCH_ARRAY,['status = ?','Activated'],['id','name']),
            'mtaHeader' => $this->app->utils->fileSystem->readFile(ASSETS_PATH . DS . 'templates' . DS . 'production' . DS . 'mta_header.tpl'),
            'smtpHeader' => $this->app->utils->fileSystem->readFile(ASSETS_PATH . DS . 'templates' . DS . 'production' . DS . 'smtp_header.tpl'),
            'id' => $id
        ]); 
    }

    /**
     * @name save
     * @description the save action
     * @before init
     * @after closeConnections
     */
    public function save() 
    { 
        # check for permissions
        $access = Permissions::checkForAuthorization($this->authenticatedUser,__CLASS__,'create');

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
            $update = false;
            $autoResponder = new AutoResponder();

            # update case
            if($this->app->utils->arrays->get($data,'id') > 0)
            {
                $update = true;
                $message = 'Record updated succesfully !';
                $autoResponder->setId(intval($this->app->utils->arrays->get($data,'id')));
                $autoResponder->load();
            }
            else
            {
                $message = 'Record stored succesfully !';
            }

            $autoResponder->setName($this->app->utils->arrays->get($data,'name'));
            $autoResponder->setStatus($this->app->utils->arrays->get($data,'status','Activated'));
            $autoResponder->setType($this->app->utils->arrays->get($data,'type','mta'));
            $autoResponder->setServerId(intval($this->app->utils->arrays->get($data,'server-id',0)));
            
            if($autoResponder->getServerId() == 0)
            {
                $message = 'Server id should be greater than 0 !';
                $flag = 'error';
            }
            else
            {
                $server = $autoResponder->getType() == 'mta' ? MtaServer::first(MtaServer::FETCH_ARRAY,['id = ?',$autoResponder->getServerId()],['id','name'])
                : SmtpServer::first(SmtpServer::FETCH_ARRAY,['id = ?',$autoResponder->getServerId()],['id','name']);
                
                if(count($server) == 0)
                {
                    $message = 'Server not found !';
                    $flag = 'error';
                }
                else
                {
                    $autoResponder->setServerName($server['name']);
                    $autoResponder->setComponentId(intval($this->app->utils->arrays->get($data,'component-id',0)));
                    
                    $component = $autoResponder->getType() == 'mta' ? ServerVmta::first(ServerVmta::FETCH_ARRAY,['id = ?',$autoResponder->getComponentId()],['id','name'])
                    : SmtpUser::first(SmtpUser::FETCH_ARRAY,['id = ?',$autoResponder->getComponentId()],['id','username']);
                    
                    if(count($component) == 0)
                    {
                        $message = $autoResponder->getType() == 'mta' ? 'Vmta not found !' : 'Smtp user not found !';
                        $flag = 'error';
                    }
                    else
                    {
                        $autoResponder->setComponentName($autoResponder->getType() == 'mta' ? $component['name'] : $component['username']);
                        $autoResponder->setAffiliateNetworkId(intval($this->app->utils->arrays->get($data,'affiliate-network-id',0)));
                        $autoResponder->setOfferId(intval($this->app->utils->arrays->get($data,'offer-id',0)));
                        $autoResponder->setOnOpen($this->app->utils->arrays->get($data,'on-open','off') == 'on' ? 't' : 'f');
                        $autoResponder->setOnClick($this->app->utils->arrays->get($data,'on-click','off') == 'on' ? 't' : 'f');
                        $autoResponder->setOnUnsub($this->app->utils->arrays->get($data,'on-unsub','off') == 'on' ? 't' : 'f');
                        $autoResponder->setOnOptout($this->app->utils->arrays->get($data,'on-optout','off') == 'on' ? 't' : 'f');
                        $autoResponder->setContent(base64_encode(json_encode($data,JSON_UNESCAPED_UNICODE)));
                    }
                }
            }
            
            $result = $update == false ? $autoResponder->insert() : $autoResponder->update(); 

            if($result > -1)
            {
                $flag = 'success';
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