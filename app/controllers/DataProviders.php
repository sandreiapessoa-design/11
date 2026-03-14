<?php declare(strict_types=1); namespace IR\App\Controllers; if (!defined('IR_START')) exit('<pre>No direct script access allowed</pre>');
/**
 * @framework       iResponse Framework 
 * @version         1.0
 * @author          Amine Idrissi <contact@iresponse.tech>
 * @date            2024
 * @name            DataProviders.php	
 */

# core 
use IR\Core\Application as Application;

# mvc 
use IR\Mvc\Controller as Controller;

# models
use IR\App\Models\Lists\DataProvider as DataProvider;

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
 * @name DataProviders
 * @description DataProviders Controller
 */
class DataProviders extends Controller
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
        
        # set menu status
        $this->masterView->set([
            'emails_management' => 'true',
            'data_providers' => 'true',
            'data_providers_show' => 'true'
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
                'created_by',
                'created_date'
            ];
        
            # fetching the results to create the ajax list
            die(json_encode(DataTable::init($data,'lists.data_providers',$columns,new DataProvider(),'data-providers','ASC')));
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
            'emails_management' => 'true',
            'data_providers' => 'true',
            'data_providers_add' => 'true'
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
            'emails_management' => 'true',
            'data_providers' => 'true',
            'data_providers_show' => 'true'
        ]);
        
        if(!isset($id) || !is_numeric($id) || intval($id) == 0)
        {
            $valid = false;
        }
        
        $dataProvider = DataProvider::first(DataProvider::FETCH_ARRAY,['id = ?',$id]);
        
        if(count($dataProvider) == 0)
        {
            $valid = false;
        }
        
        if($valid == true)
        {
            # set data to the page view
            $this->pageView->set([
                'dataProvider' => $dataProvider
            ]); 
        }
        else
        {
            # stores the message in the session 
            Page::registerMessage('error','Invalid data provider id !');
            
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
            $username = $this->authenticatedUser->getEmail();
            $result = -1;
            
            # update case
            if($this->app->utils->arrays->get($data,'id') > 0)
            {
                # check for permissions
                $access = Permissions::checkForAuthorization($this->authenticatedUser,__CLASS__,'edit');

                if($access == false)
                {
                    throw new PageException('Access Denied !',403);
                }
        
                $message = 'Record updated succesfully !';
                $provider = new DataProvider();
                $provider->setId(intval($this->app->utils->arrays->get($data,'id')));
                $provider->load();
                $provider->setLastUpdatedBy($username);
                $provider->setLastUpdatedDate(date('Y-m-d'));
                $provider->setName($this->app->utils->arrays->get($data,'name'));
                $provider->setStatus($this->app->utils->arrays->get($data,'status','Activated'));
                $result = $provider->update();
            }
            else
            {
                # check for permissions
                $access = Permissions::checkForAuthorization($this->authenticatedUser,__CLASS__,'add');

                if($access == false)
                {
                    throw new PageException('Access Denied !',403);
                }
                
                $providers = array_filter(explode(PHP_EOL,$this->app->utils->arrays->get($data,'providers-names')));
                
                if(is_array($providers) && count($providers) > 0)
                {
                    foreach ($providers as $prv)
                    {
                        $oldPrv = DataProvider::first(DataProvider::FETCH_ARRAY,['name = ?',trim($prv)]);
                        
                        if(count($oldPrv) == 0)
                        {
                            $provider = new DataProvider();
                            $provider->setName(trim($prv));
                            $provider->setStatus($this->app->utils->arrays->get($data,'status','Activated'));
                            $provider->setCreatedBy($username);
                            $provider->setCreatedDate(date('Y-m-d'));
                            $provider->setLastUpdatedBy($username);
                            $provider->setLastUpdatedDate(date('Y-m-d'));
                            $result = $provider->insert();
                            $message = 'Records stored succesfully !';
                        }
                    }
                }
            }

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


