<?php declare(strict_types=1); namespace IR\App\Controllers; if (!defined('IR_START')) exit('<pre>No direct script access allowed</pre>');
/**
 * @framework       iResponse Framework 
 * @version         1.0
 * @author          Amine Idrissi <contact@iresponse.tech>
 * @date            2018
 * @name            DigitalOceanDroplets.php	
 */

# core 
use IR\Core\Application as Application;

# mvc 
use IR\Mvc\Controller as Controller;

# models
use IR\App\Models\Admin\DigitalOceanDroplet as DigitalOceanDroplet;
use IR\App\Models\Admin\DigitalOceanAccount as DigitalOceanAccount;
use IR\App\Models\Admin\DigitalOceanProcess as DigitalOceanProcess;
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
 * @name DigitalOceanDroplets
 * @description DigitalOceanDroplets Controller
 */
class DigitalOceanDroplets extends Controller
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
            'account_name',
            'mta_server_name',
            'region'
        ];
        
        # creating the html part of the list 
        $columns = Page::createTableHeader($columnsArray);
        $filters = Page::createTableFilters($columnsArray);
            
        # set menu status
        $this->masterView->set([
            'cloud_management' => 'true',
            'digital_ocean_management' => 'true',
            'digital_ocean_droplets' => 'true',
            'digital_ocean_droplets_show' => 'true'
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
                'account_name',
                'mta_server_name',
                'region'
            ];
            
            # fetching the results to create the ajax list
            die(json_encode(DataTable::init($data,'admin.digital_ocean_droplets',$columns,new DigitalOceanDroplet(),'digital-ocean-droplets','DESC',null,false)));
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

        # preparing the columns array to create the list
        $columnsArray = [
            'id',
            'process_id',
            'status',
            'progress',
            'droplets_created',
            'droplets_installed',
            'start_time',
            'finish_time'
        ];
        
        # creating the html part of the list 
        $columns = Page::createTableHeader($columnsArray);
        $filters = Page::createTableFilters($columnsArray);

        # set menu status
        $this->masterView->set([
            'cloud_management' => 'true',
            'digital_ocean_management' => 'true',
            'digital_ocean_droplets' => 'true',
            'digital_ocean_droplets_create' => 'true'
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
            'accounts' => DigitalOceanAccount::all(DigitalOceanAccount::FETCH_ARRAY,['status = ?','Activated'],['id','name']),
            'dnsaccounts' => $accounts,
            'columns' => $columns,
            'filters' => $filters
        ]); 
    }
    
    /**
     * @name getProcesses
     * @description the getProcesses action
     * @before init
     * @after closeConnections
     */
    public function getProcesses() 
    { 
        # check for permissions
        $access = Permissions::checkForAuthorization($this->authenticatedUser,__CLASS__,'create');

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
                'process_id',
                'status',
                'progress',
                'droplets_created',
                'droplets_installed',
                'start_time',
                'finish_time'
            ];
            
            # fetching the results to create the ajax list
            die(json_encode(DataTable::init($data,'admin.digital_ocean_processes',$columns,new DigitalOceanProcess(),'digital-ocean-droplets','DESC',null,false)));
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


