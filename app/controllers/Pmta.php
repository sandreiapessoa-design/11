<?php declare(strict_types=1); namespace IR\App\Controllers; if (!defined('IR_START')) exit('<pre>No direct script access allowed</pre>');
/**
 * @framework       iResponse Framework 
 * @version         1.0
 * @author          Amine Idrissi <contact@iresponse.tech>
 * @date            2024
 * @name            Pmta.php	
 */

# core 
use IR\Core\Application as Application;

# mvc 
use IR\Mvc\Controller as Controller;

# http 
use IR\Http\Request as Request;

# models
use IR\App\Models\Admin\Isp as Isp;
use IR\App\Models\Admin\PmtaHistory as PmtaHistory;
use IR\App\Models\Admin\MtaServer as MtaServer;

# helpers 
use IR\App\Helpers\Authentication as Authentication;
use IR\App\Helpers\Page as Page;
use IR\App\Helpers\DataTable as DataTable;
use IR\App\Helpers\Permissions as Permissions;

# exceptions
use IR\Exceptions\Types\PageException as PageException;

/**
 * @name Pmta
 * @description Pmta Controller
 */
class Pmta extends Controller
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
        Page::redirect($this->app->http->request->getBaseURL() . RDS . 'pmta' . RDS . 'commands.' . DEFAULT_EXTENSION);
    }
    
    /**
     * @name commands
     * @description the commands action
     * @before init
     * @after closeConnections,checkForMessage
     */
    public function commands() 
    { 
        # check for permissions
        $access = Permissions::checkForAuthorization($this->authenticatedUser,__CLASS__,__FUNCTION__);

        if($access == false)
        {
            throw new PageException('Access Denied !',403);
        }
        
        # set menu status
        $this->masterView->set([
            'pmta_management' => 'true',
            'pmta_management_commands' => 'true'
        ]);

        $pmtaPort = $this->app->utils->arrays->get($this->app->getSetting('application'),'pmta_http_port');
        
        # set data to the page view
        $this->pageView->set([
            'servers' => MtaServer::all(MtaServer::FETCH_ARRAY,['status = ? AND is_installed = ?',['Activated','t']],['id','name','provider_name','main_ip'],'id','DESC'),
            'isps' => Isp::all(Isp::FETCH_ARRAY,['status = ?','Activated'],['id','name']),
            'pmtaPort' => $pmtaPort,
        ]);
    }
    
    /**
     * @name globalVmtas
     * @description the globalVmtas action
     * @before init
     * @after closeConnections,checkForMessage
     */
    public function globalVmtas() 
    { 
        # check for permissions
        $access = Permissions::checkForAuthorization($this->authenticatedUser,__CLASS__,__FUNCTION__);

        if($access == false)
        {
            throw new PageException('Access Denied !',403);
        }
        
        # set menu status
        $this->masterView->set([
            'pmta_management' => 'true',
            'pmta_management_global_vmtas' => 'true' 
        ]);

        # set data to the page view
        $this->pageView->set([
            'servers' => MtaServer::all(MtaServer::FETCH_ARRAY,['status = ? AND is_installed = ?',['Activated','t']],['id','name','provider_name','main_ip'],'id','DESC'),
            'isps' => Isp::all(Isp::FETCH_ARRAY,['status = ?','Activated'],['id','name'])
        ]);
    }
    
    /**
     * @name individualVmtas
     * @description the individualVmtas action
     * @before init
     * @after closeConnections,checkForMessage
     */
    public function individualVmtas() 
    { 
        # check for permissions
        $access = Permissions::checkForAuthorization($this->authenticatedUser,__CLASS__,__FUNCTION__);

        if($access == false)
        {
            throw new PageException('Access Denied !',403);
        }
        
        # set menu status
        $this->masterView->set([
            'pmta_management' => 'true',
            'pmta_management_individual_vmtas' => 'true' 
        ]);

        # set data to the page view
        $this->pageView->set([
            'servers' => MtaServer::all(MtaServer::FETCH_ARRAY,['status = ? AND is_installed = ?',['Activated','t']],['id','name','provider_name','main_ip'],'id','DESC'),
            'isps' => Isp::all(Isp::FETCH_ARRAY,['status = ?','Activated'],['id','name'])
        ]);
    }
    
    /**
     * @name smtpVmtas
     * @description the smtpVmtas action
     * @before init
     * @after closeConnections,checkForMessage
     */
    public function smtpVmtas() 
    { 
        # check for permissions
        $access = Permissions::checkForAuthorization($this->authenticatedUser,__CLASS__,__FUNCTION__);

        if($access == false)
        {
            throw new PageException('Access Denied !',403);
        }
        
        # set menu status
        $this->masterView->set([
            'pmta_management' => 'true',
            'pmta_management_smtp_vmtas' => 'true' 
        ]);

        # set data to the page view
        $this->pageView->set([
            'servers' => MtaServer::all(MtaServer::FETCH_ARRAY,['status = ? AND is_installed = ?',['Activated','t']],['id','name','provider_name','main_ip'],'id','DESC')
        ]);
    }
    
    /**
     * @name configs
     * @description the configs action
     * @before init
     * @after closeConnections,checkForMessage
     */
    public function configs() 
    { 
        # check for permissions
        $access = Permissions::checkForAuthorization($this->authenticatedUser,__CLASS__,__FUNCTION__);

        if($access == false)
        {
            throw new PageException('Access Denied !',403);
        }

        # set menu status
        $this->masterView->set([
            'pmta_management' => 'true',
            'pmta_management_configs' => 'true'
        ]);
        
        # set data to the page view
        $this->pageView->set([
            'servers' => MtaServer::all(MtaServer::FETCH_ARRAY,['status = ? AND is_installed = ?',['Activated','t']],['id','name','provider_name','main_ip'],'id','DESC')
        ]); 
    }
    
    /**
     * @name templates
     * @description the templates action
     * @before init
     * @after closeConnections,checkForMessage
     */
    public function templates() 
    { 
        # check for permissions
        $access = Permissions::checkForAuthorization($this->authenticatedUser,__CLASS__,__FUNCTION__);

        if($access == false)
        {
            throw new PageException('Access Denied !',403);
        }

        # set menu status
        $this->masterView->set([
            'pmta_management' => 'true',
            'pmta_management_templates' => 'true'
        ]);  
        
        # set data to the page view
        $this->pageView->set([
            'servers' => MtaServer::all(MtaServer::FETCH_ARRAY,['status = ? AND is_installed = ?',['Activated','t']],['id','name','provider_name','main_ip'],'id','DESC')
        ]);
    }
    
    /**
     * @name history
     * @description the history action
     * @before init
     * @after closeConnections,checkForMessage
     */
    public function history() 
    { 
        # check for permissions
        $access = Permissions::checkForAuthorization($this->authenticatedUser,__CLASS__,__FUNCTION__);

        if($access == false)
        {
            throw new PageException('Access Denied !',403);
        }
        
        $columnsArray = [
            'id',
            'user_full_name',
            'server_name',
            'action',
            'target',
            'isps',
            'action_time',
            'results'
        ];
        
        # creating the html part of the list 
        $columns = Page::createTableHeader($columnsArray);
        $filters = Page::createTableFilters($columnsArray);
            
        # set menu status
        $this->masterView->set([
            'pmta_management' => 'true',
            'pmta_management_history' => 'true'
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
        $access = Permissions::checkForAuthorization($this->authenticatedUser,__CLASS__,'history');

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
                'h.id' => 'id',
                "u.first_name || ' ' || u.last_name" => 'user_full_name',
                's.name' => 'server_name',
                'h.action' => 'action',
                'h.target' => 'target',
                'h.isps' => 'isps',
                'h.action_time' => 'action_time',
                'h.results' => 'results'
            ];
            
            # fetching the results to create the ajax list
            $query = $this->app->database('system')->query()->from('admin.pmta_commands_history h',$columns)
                    ->join('admin.mta_servers s','h.server_id = s.id')
                    ->join('admin.users u','h.user_id = u.id');
            die(json_encode(DataTable::init($data,'admin.pmta_commands_history h',$columns,new PmtaHistory(),'pmta' . RDS . 'history','DESC',$query,false)));
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


