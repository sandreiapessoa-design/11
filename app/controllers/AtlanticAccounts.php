<?php declare(strict_types=1); namespace IR\App\Controllers; if (!defined('IR_START')) exit('<pre>No direct script access allowed</pre>');
/**
 * @framework       iResponse Framework 
 * @version         1.0
 * @author          Amine Idrissi <contact@iresponse.tech>
 * @date            2018
 * @name            AtlanticAccounts.php	
 */

# core 
use IR\Core\Application as Application;

# mvc 
use IR\Mvc\Controller as Controller;

# models
use IR\App\Models\Admin\AtlanticAccount as AtlanticAccount;

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
 * @name AtlanticAccounts
 * @description AtlanticAccounts Controller
 */
class AtlanticAccounts extends Controller
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
            'status',
            'access_key',
            'proxy_status',
            'created_by',
            'created_date'
        ];
        
        # creating the html part of the list 
        $columns = Page::createTableHeader($columnsArray);
        $filters = Page::createTableFilters($columnsArray);
            
        # set menu status
        $this->masterView->set([
            'cloud_management' => 'true',
            'atlantic_management' => 'true',
            'atlantic_accounts' => 'true',
            'atlantic_accounts_show' => 'true'
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
                'access_key',
                'proxy_status',
                'created_by',
                'created_date'
            ];
            
            # fetching the results to create the ajax list
            die(json_encode(DataTable::init($data,'admin.atlantic_accounts',$columns,new AtlanticAccount(),'atlantic-accounts','ASC')));
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
            'cloud_management' => 'true',
            'atlantic_management' => 'true',
            'atlantic_accounts' => 'true',
            'atlantic_accounts_add' => 'true'
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
            'cloud_management' => 'true',
            'atlantic_ocean_management' => 'true',
            'atlantic_accounts' => 'true',
            'atlantic_accounts_show' => 'true'
        ]);
        
        if(!isset($id) || !is_numeric($id) || intval($id) == 0)
        {
            $valid = false;
        }
        
        $account = AtlanticAccount::first(AtlanticAccount::FETCH_ARRAY,['id = ?',$id]);
        
        if(count($account) == 0)
        {
            $valid = false;
        }
        
        if($valid == true)
        {
            # set data to the page view
            $this->pageView->set([
                'account' => $account
            ]); 
        }
        else
        {
            # stores the message in the session 
            Page::registerMessage('error','Invalid atlantic account id !');
            
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
            $account = new AtlanticAccount();
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
                $account->setId(intval($this->app->utils->arrays->get($data,'id')));
                $account->load();
                $account->setLastUpdatedBy($username);
                $account->setLastUpdatedDate(date('Y-m-d'));
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
                $account->setCreatedBy($username);
                $account->setCreatedDate(date('Y-m-d'));
                $account->setLastUpdatedBy($username);
                $account->setLastUpdatedDate(date('Y-m-d'));
            }

            $account->setName($this->app->utils->arrays->get($data,'atlantic-name'));
            $account->setStatus($this->app->utils->arrays->get($data,'atlantic-status'));
            $account->setAccessKey($this->app->utils->arrays->get($data,'atlantic-access-key'));
            $account->setPrivateKey($this->app->utils->arrays->get($data,'atlantic-private-key'));
            
            # check if there is a proxy involved
            if(filter_var($this->app->utils->arrays->get($data,'atlantic-proxy-ip'),FILTER_VALIDATE_IP))
            {
                $account->setProxyStatus('Enabled');
                $account->setProxyIp($this->app->utils->arrays->get($data,'atlantic-proxy-ip'));
                $account->setProxyPort($this->app->utils->arrays->get($data,'atlantic-proxy-port'));
                $account->setProxyUsername($this->app->utils->arrays->get($data,'atlantic-proxy-username'));
                $account->setProxyPassword($this->app->utils->arrays->get($data,'atlantic-proxy-password'));
            }
            else
            {
                $account->setProxyStatus('Disabled');
                $account->setProxyIp('');
                $account->setProxyPort('');
                $account->setProxyUsername('');
                $account->setProxyPassword('');
            }

            $result = $update == false ? $account->insert() : $account->update(); 

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


