<?php declare(strict_types=1); namespace IR\App\Controllers; if (!defined('IR_START')) exit('<pre>No direct script access allowed</pre>');
/**
 * @framework       iResponse Framework 
 * @version         1.0
 * @author          Amine Idrissi <contact@iresponse.tech>
 * @date            2018
 * @name            AuditLogs.php	
 */

# core 
use IR\Core\Application as Application;

# mvc 
use IR\Mvc\Controller as Controller;

# models
use IR\App\Models\Admin\AuditLog as AuditLog;

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
 * @name AuditLogs
 * @description AuditLogs Controller
 */
class AuditLogs extends Controller
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
            'action_by',
            'record_id',
            'record_name',
            'record_type',
            'action_type',
            'action_time'
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
            'application' => 'true',
            'auditlogs' => 'true'
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
                'action_by',
                'record_id',
                'record_name',
                'record_type',
                'action_type',
                'action_time'
            ];
            
            # fetching the results to create the ajax list
            die(json_encode(DataTable::init($data,'admin.audit_logs',$columns,new AuditLog(),'audit-logs','DESC',null,false)));
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


