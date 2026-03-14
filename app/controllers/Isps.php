<?php declare(strict_types=1); namespace IR\App\Controllers; if (!defined('IR_START')) exit('<pre>No direct script access allowed</pre>');
/**
 * @framework       iResponse Framework 
 * @version         1.0
 * @author          Amine Idrissi <contact@iresponse.tech>
 * @date            2024
 * @name            Isps.php	
 */

# core 
use IR\Core\Application as Application;

# mvc 
use IR\Mvc\Controller as Controller;

# models
use IR\App\Models\Admin\Isp as Isp;

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
 * @name Isps
 * @description Isps Controller
 */
class Isps extends Controller
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
        $columns = Page::createTableHeader($columnsArray);
        $filters = Page::createTableFilters($columnsArray);
        
        # set menu status
        $this->masterView->set([
            'emails_management' => 'true',
            'isps' => 'true',
            'isps_show' => 'true'
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
            die(json_encode(DataTable::init($data,'admin.isps',$columns,new Isp(),'isps','ASC')));
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
            'isps' => 'true',
            'isps_add' => 'true'
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
            'isps' => 'true',
            'isps_show' => 'true'
        ]);
        
        if(!isset($id) || !is_numeric($id) || intval($id) == 0)
        {
            $valid = false;
        }
        
        $isp = Isp::first(Isp::FETCH_ARRAY,['id = ?',$id]);
        
        if(count($isp) == 0)
        {
            $valid = false;
        }
        
        if($valid == true)
        {
            # set data to the page view
            $this->pageView->set([
                'isp' => $isp
            ]); 
        }
        else
        {
            # stores the message in the session 
            Page::registerMessage('error','Invalid isp id !');
            
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
            $oldSchema = '';
            
            # connect to the database 
            $this->app->database('clients')->connect();

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
                $isp = new Isp();
                $isp->setId(intval($this->app->utils->arrays->get($data,'id')));
                $isp->load();
                $oldSchema = $isp->getSchemaName();
                $isp->setLastUpdatedBy($username);
                $isp->setLastUpdatedDate(date('Y-m-d'));
                $isp->setName($this->app->utils->arrays->get($data,'name'));
                $isp->setStatus($this->app->utils->arrays->get($data,'status','Activated'));
                $isp->setSchemaName(str_replace("'",'',$this->app->database('clients')->escape(strtolower($isp->getName()))));
                $result = $isp->update();

                if($result > -1 && $isp->getSchemaName() != $oldSchema)
                {
                    # rename database scheme for this isp 
                    $this->app->database('clients')->execute("ALTER SCHEMA $oldSchema RENAME TO {$isp->getSchemaName()}");
                }
            }
            else
            {
                # check for permissions
                $access = Permissions::checkForAuthorization($this->authenticatedUser,__CLASS__,'add');

                if($access == false)
                {
                    throw new PageException('Access Denied !',403);
                }
                
                $isps = array_filter(explode(PHP_EOL,$this->app->utils->arrays->get($data,'isps-names')));
                
                if(is_array($isps) && count($isps) > 0)
                {
                    foreach ($isps as $ispString)
                    {
                        $oldIsp = Isp::first(Isp::FETCH_ARRAY,['name = ?',trim($ispString)]);
                        
                        if(count($oldIsp) == 0)
                        {
                            $isp = new Isp();
                            $isp->setName(trim($ispString));
                            $isp->setStatus($this->app->utils->arrays->get($data,'status','Activated'));
                            $isp->setSchemaName(str_replace("'",'',$this->app->database('clients')->escape(strtolower($isp->getName()))));
                            $isp->setCreatedBy($username);
                            $isp->setCreatedDate(date('Y-m-d'));
                            $isp->setLastUpdatedBy($username);
                            $isp->setLastUpdatedDate(date('Y-m-d'));
                            $result = $isp->insert();
                            
                            if($result > -1)
                            {
                                # create database scheme for this isp 
                                $this->app->database('clients')->execute("CREATE SCHEMA IF NOT EXISTS {$isp->getSchemaName()}");
                            }
                            
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


