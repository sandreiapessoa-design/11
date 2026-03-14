<?php declare(strict_types=1); namespace IR\App\Controllers; if (!defined('IR_START')) exit('<pre>No direct script access allowed</pre>');
/**
 * @framework       iResponse Framework 
 * @version         1.0
 * @author          Amine Idrissi <contact@iresponse.tech>
 * @date            2024
 * @name            Mailboxes.php	
 */

# core 
use IR\Core\Application as Application;

# mvc 
use IR\Mvc\Controller as Controller;

# models
use IR\App\Models\Admin\Mailbox as Mailbox;
use IR\App\Models\Admin\Namecheap as Namecheap;
use IR\App\Models\Admin\GoDaddy as GoDaddy;
use IR\App\Models\Admin\Namecom as Namecom;
use IR\App\Models\Admin\ManagementServer as ManagementServer;

# http 
use IR\Http\Request as Request;

# helpers 
use IR\App\Helpers\Authentication as Authentication;
use IR\App\Helpers\Page as Page;
use IR\App\Helpers\Api as Api;
use IR\App\Helpers\DataTable as DataTable;
use IR\App\Helpers\Permissions as Permissions;

# exceptions
use IR\Exceptions\Types\PageException as PageException;

/**
 * @name Mailboxes
 * @description Mailboxes Controller
 */
class Mailboxes extends Controller
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
            'account_name',
            'domain_name',
            'email',
            'status',
            'created_by',
            'created_date'
        ];
        
        # creating the html part of the list 
        $columns = Page::createTableHeader($columnsArray);
        $filters = Page::createTableFilters($columnsArray);
            
        # set menu status
        $this->masterView->set([
            'emails_management' => 'true',
            'internal_mailboxes' => 'true',
            'internal_mailboxes_show' => 'true'
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
                'account_name',
                'domain_name',
                'email',
                'status',
                'created_by',
                'created_date'
            ];
            
            # fetching the results to create the ajax list
            die(json_encode(DataTable::init($data,'admin.mailboxes',$columns,new Mailbox(),'mailboxes','DESC',null,false)));
        }
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
        
        # set menu status
        $this->masterView->set([
            'emails_management' => 'true',
            'internal_mailboxes' => 'true',
            'internal_mailboxes_create' => 'true'
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
            'accounts' => $accounts
        ]);
    }
 
    /**
     * @name open
     * @description the open action
     * @before init
     * @after closeConnections,checkForMessage
     */
    public function open() 
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
        
        # prevent views
        $this->showMasterView = false;
        $this->showPageView = false;
        
        if(!isset($id) || !is_numeric($id) || intval($id) == 0)
        {
            $valid = false;
        }
        
        $mailbox = Mailbox::first(Mailbox::FETCH_ARRAY,['id = ?',$id]);
        
        if(count($mailbox) == 0)
        {
            $valid = false;
        }
        
        if($valid == true)
        {
            $id = intval($this->app->utils->arrays->get($this->app->getSetting('application'),'webmail_server_id'));
            $webmail = ManagementServer::first(ManagementServer::FETCH_ARRAY,['id = ?',$id]);
            
            if($access == false)
            {
                # stores the message in the session 
                Page::registerMessage('error','No webmail server found !');

                # redirect to lists page
                Page::redirect($this->app->http->request->getBaseURL() . RDS . 'mailboxes.html');
            }
            else
            {
                $template = file_get_contents(ASSETS_PATH . DS . 'templates' . DS . 'servers' . DS . 'zimbra.tpl');
                die(str_replace(['$p_url','$p_email','$p_password'],['https://' . $webmail['main_ip'] . ':8443',$mailbox['email'],$mailbox['password']],$template)); 
            }
        }
        else
        {
            # stores the message in the session 
            Page::registerMessage('error','Invalid mailbox id !');
            
            # redirect to lists page
            Page::redirect($this->app->http->request->getBaseURL() . RDS . 'mailboxes.html');
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
            $domainIds = $this->app->utils->arrays->get($data,'domains',[]);
            $status = $this->app->utils->arrays->get($data,'status','Activated');
            $prefixes = array_values(array_filter(explode(PHP_EOL,$this->app->utils->arrays->get($data,'mailboxes-prefixes','')),"trim"));
            
            if(count($domainIds) && count($prefixes))
            {
                # call iresponse api
                $result = Api::call('Mailboxes','createMailboxes',['domains-ids' => $domainIds,'status' => $status,'prefixes' => $prefixes]);

                if(count($result) == 0)
                {
                    $message = 'No response found !';
                }
                elseif($result['httpStatus'] == 500)
                {
                    $message = $result['message'];
                }
                else
                {
                    $flag = 'success';
                    $message = $result['message'];
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


