<?php declare(strict_types=1); namespace IR\App\Controllers; if (!defined('IR_START')) exit('<pre>No direct script access allowed</pre>');
/**
 * @framework       iResponse Framework 
 * @version         1.0
 * @author          Amine Idrissi <contact@iresponse.tech>
 * @date            2024
 * @name            DataLists.php	
 */

# core 
use IR\Core\Application as Application;

# mvc 
use IR\Mvc\Controller as Controller;

# models
use IR\App\Models\Lists\DataProvider as DataProvider;
use IR\App\Models\Lists\DataList as DataList;
use IR\App\Models\Admin\Isp as Isp;
use IR\App\Models\Affiliate\Vertical as Vertical;
use IR\App\Models\Lists\Blacklist as Blacklist;

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
 * @name DataLists
 * @description DataLists Controller
 */
class DataLists extends Controller
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
            'data_provider_name',
            'name',
            'isp_name',
            'total_count',
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
            'data_lists' => 'true',
            'data_lists_show' => 'true'
        ]);
            
        # set data to the page view
        $this->pageView->set([
            'dataProviders' => DataProvider::all(DataProvider::FETCH_ARRAY,['status = ?','Activated'],['id','name']),
            'isps' => Isp::all(Isp::FETCH_ARRAY,['status = ?','Activated'],['id','name']),
            'verticals' => Vertical::all(Vertical::FETCH_ARRAY,['status = ?','Activated'],['id','name']),
            'columns' => $columns,
            'filters' => $filters,
            'scripts' => $this->app->utils->fileSystem->readFile(VIEWS_PATH . DS . 'includes' . DS . 'file_upload.html')
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
                'data_provider_name',
                'name',
                'isp_name',
                'total_count',
                'status',
                'created_by',
                'created_date'
            ];

            # fetching the results to create the ajax list
            die(json_encode(DataTable::init($data,'lists.data_lists',$columns,new DataList(),'data-lists','DESC',null,false)));
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
            'data_lists' => 'true',
            'data_lists_add' => 'true'
        ]);
    }
    
    /**
     * @name duplicates
     * @description the duplicates action
     * @before init
     * @after closeConnections,checkForMessage
     */
    public function duplicates() 
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
            'data_lists' => 'true',
            'data_lists_duplicates' => 'true'
        ]);

        # set data to the page view
        $this->pageView->set([
            'scripts' => $this->app->utils->fileSystem->readFile(VIEWS_PATH . DS . 'includes' . DS . 'file_upload.html')
        ]);
    }
    
    /**
     * @name blacklists
     * @description the blacklists action
     * @before init
     * @after closeConnections,checkForMessage
     */
    public function blacklists() 
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
            'data_lists' => 'true',
            'data_lists_blacklist' => 'true'
        ]);
        
        # preparing the columns array to create the list
        $columnsArray = [
            'id',
            'progress',
            'status',
            'emails_found',
            'start_time',
            'finish_time'
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
                        $filters .= '<td> <select name="status" class="form-control form-filter input-sm"> <option value="" selected>All</option> <option value="In Progress">In Progress</option> <option value="Finished">Finished</option> <option value="Interrupted">Interrupted</option></select> </td>' . PHP_EOL;
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
            'columns' => $columns,
            'filters' => $filters,
            'scripts' => $this->app->utils->fileSystem->readFile(VIEWS_PATH . DS . 'includes' . DS . 'file_upload.html')
        ]);
    }
    
    /**
     * @name getBlacklistsProcesses
     * @description the getBlacklistsProcesses action
     * @before init
     * @after closeConnections
     */
    public function getBlacklistsProcesses() 
    { 
        # check for permissions
        $access = Permissions::checkForAuthorization($this->authenticatedUser,__CLASS__,'blacklists');

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
                'progress',
                'status',
                'emails_found',
                'start_time',
                'finish_time'
            ];
        
            # fetching the results to create the ajax list
            die(json_encode(DataTable::init($data,'lists.blacklists',$columns,new Blacklist(),'blacklists','DESC',null,false)));
        }
    }
    
    /**
     * @name uploadBlacklists
     * @description upload blacklists
     * @before init
     */
    public function uploadBlacklists() 
    { 
        # check for permissions
        $access = Permissions::checkForAuthorization($this->authenticatedUser,__CLASS__,'blacklists');

        $response = [];
        
        if($access == false)
        {
            $response['files'][0]['error'] = 'Access Denied !';
            die(json_encode($response));
        }
        
        # check if there is another blacklist process in progress
        $process = Blacklist::first(Blacklist::FETCH_ARRAY,['status = ?','In Progress'],['id']);
            
        if(is_array($process) && count($process) > 0)
        {
            $response['files'][0]['error'] = 'Another blacklisting process is running !';
            die(json_encode($response));
        }
        
        # get files data
        $files = $this->app->http->request->retrieve(Request::ALL,Request::FILES);

        if(count($files))
        {
            $parameters = [];
            $file = $this->app->utils->arrays->get($files,'files');
            
            # start validations 
            if(intval($file['error'][0]) > 0)
            {
                switch (intval($file['error'][0])) 
                {
                    case UPLOAD_ERR_INI_SIZE:
                    {
                        $message = "The uploaded file exceeds the upload_max_filesize directive in php.ini";
                        break;
                    }
                    case UPLOAD_ERR_FORM_SIZE:
                    {
                        $message = "The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form";
                        break;
                    }
                    case UPLOAD_ERR_PARTIAL:
                    {
                        $message = "The uploaded file was only partially uploaded";
                        break;
                    }
                    case UPLOAD_ERR_NO_FILE:
                    {
                        $message = "No file was uploaded";
                        break;
                    }
                    case UPLOAD_ERR_NO_TMP_DIR:
                    {
                        $message = "Missing a temporary folder";
                        break;
                    }
                    case UPLOAD_ERR_CANT_WRITE:
                    {
                        $message = "Failed to write file to disk";
                        break;
                    }
                    case UPLOAD_ERR_EXTENSION:
                    {
                        $message = "File upload stopped by extension";
                        break;
                    }
                    default:
                    {
                        $message = "Unknown upload error";
                    }
                } 
                
                $response['files'][0]['error'] = $message;
                die(json_encode($response));
            }
            
            if(!in_array($file['type'][0],['text/plain']) || $file['size'][0] == 0)
            {
                $response['files'][0]['error'] = 'Unsupported file type : ' . $file['type'][0];
                die(json_encode($response));
            }

            # create a tmp directory for emails
            $parameters['tmp-dir'] = $this->app->utils->strings->randomHex(10);
            $this->app->utils->fileSystem->createDir(TRASH_PATH . DS . $parameters['tmp-dir']);
           
            # move the file to the temp directory 
            $parameters['file-name'] = $this->app->utils->strings->randomHex(15) . '.txt';
            $this->app->utils->fileSystem->moveFileOrDirectory($file['tmp_name'][0],TRASH_PATH . DS . $parameters['tmp-dir'] . DS . $parameters['file-name']);
            
            $process = new Blacklist([
                'status' => 'In Progress',
                'progress' => '0%',
                'emails_found' => 0,
                'start_time' => date('Y-m-d H:i:s')
            ]);
            
            $parameters['id'] = $process->insert();
            
            # call iresponse api
            $result = Api::call('DataLists','manageBlacklists',$parameters,true);
            
            if(count($result) == 0)
            {
                $response['files'][0]['error'] = 'Could not manage blacklists !';
                die(json_encode($response));
            }
            
            if($result['httpStatus'] == 500)
            {              
                $response['files'][0]['error'] = $result['message'];
                die(json_encode($response));
            }
            
            $response = ['files' => [[
                'name' => $file['name'][0],
                'size' => $file['size'][0],
                'success' => $result['message']
            ]]];

            die(json_encode($response));
        }
        else
        {
            $response['files'][0]['error'] = 'No parameters passed !';
            die(json_encode($response));
        }
    }
    
    /**
     * @name dwn
     * @description the download action
     * @before init
     * @after closeConnections,checkForMessage
     */
    public function dwn() 
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
            'data_lists' => 'true',
            'data_lists_duplicates' => 'true'
        ]);

        # set data to the page view
        $this->pageView->set([
            'lists' => DataList::all(DataList::FETCH_ARRAY,['status = ?','Activated'])
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
        # get post data
        $data = $this->app->http->request->retrieve(Request::ALL,Request::POST);
        $files = $this->app->http->request->retrieve(Request::ALL,Request::FILES);
        $response = [];
        
        if(count($data) && count($files))
        {     
            $file = $this->app->utils->arrays->get($files,'files');

            $parameters = [];
            $parameters['name'] = $this->app->utils->arrays->get($data,'list-name');
            $parameters['table'] = $this->app->utils->strings->prepareDbTableName($parameters['name']);
            $parameters['emails-type'] = $this->app->utils->arrays->get($data,'emails-type');
            $parameters['country-code'] = strtolower($this->app->utils->arrays->get($data,'country',''));
            $parameters['file-type'] = $this->app->utils->arrays->get($data,'file-type');
            $parameters['data-provider-id'] = intval($this->app->utils->arrays->get($data,'data-provider-id'));
            $parameters['old-list-id'] = intval($this->app->utils->arrays->get($data,'list-old-id'));
            $parameters['isp-id'] = intval($this->app->utils->arrays->get($data,'isp'));
            $parameters['verticals-ids'] = $this->app->utils->arrays->get($data,'verticals','');          
            $parameters['max-per-list'] = intval($this->app->utils->arrays->get($data,'list-deviding-value','0'));
            $parameters['encrypt-emails'] = $this->app->utils->arrays->get($data,'encrypt-emails');
            $parameters['duplicate-value'] = intval($this->app->utils->arrays->get($data,'duplicate-value','1'));
            $parameters['allow-duplicates'] = $this->app->utils->arrays->get($data,'allow-duplicates');
            $parameters['filter-data'] = $this->app->utils->arrays->get($data,'filter-data');

            # start validations 
            if(intval($file['error'][0]) > 0)
            {
                switch (intval($file['error'][0])) 
                {
                    case UPLOAD_ERR_INI_SIZE:
                    {
                        $message = "The uploaded file exceeds the upload_max_filesize directive in php.ini";
                        break;
                    }
                    case UPLOAD_ERR_FORM_SIZE:
                    {
                        $message = "The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form";
                        break;
                    }
                    case UPLOAD_ERR_PARTIAL:
                    {
                        $message = "The uploaded file was only partially uploaded";
                        break;
                    }
                    case UPLOAD_ERR_NO_FILE:
                    {
                        $message = "No file was uploaded";
                        break;
                    }
                    case UPLOAD_ERR_NO_TMP_DIR:
                    {
                        $message = "Missing a temporary folder";
                        break;
                    }
                    case UPLOAD_ERR_CANT_WRITE:
                    {
                        $message = "Failed to write file to disk";
                        break;
                    }
                    case UPLOAD_ERR_EXTENSION:
                    {
                        $message = "File upload stopped by extension";
                        break;
                    }
                    default:
                    {
                        $message = "Unknown upload error";
                    }
                } 
                
                $response['files'][0]['error'] = $message;
                die(json_encode($response));
            }
            
            if(strlen($parameters['name']) < 3 && $parameters['old-list-id'] == 0)
            {
                $response['files'][0]['error'] = 'List name\'s length should be greater than 2 chars !';
                die(json_encode($response));
            }

            if($parameters['data-provider-id'] == 0)
            {
                $response['files'][0]['error'] = 'No data provider found !';
                die(json_encode($response));
            }

            if(empty($parameters['name']) && $parameters['old-list-id'] == 0)
            {
                $response['files'][0]['error'] = 'No name found !';
                die(json_encode($response));
            }
            
            if(!in_array($file['type'][0],['text/csv','text/plain']) || $file['size'][0] == 0)
            {
                $response['files'][0]['error'] = 'Unsupported file type : ' . $file['type'][0];
                die(json_encode($response));
            }

            if(!in_array($parameters['emails-type'],['fresh','clean','openers','clickers','leads','seeds','unsubs']))
            {
                $response['files'][0]['error'] = 'Unsupported emails type !';
                die(json_encode($response));
            }

            if(empty($parameters['country-code']) && $parameters['old-list-id'] == 0)
            {
                $response['files'][0]['error'] = 'No country found !';
                die(json_encode($response));
            }

            if($parameters['isp-id'] == 0 && $parameters['old-list-id'] == 0)
            {
                $response['files'][0]['error'] = 'No isp found !';
                die(json_encode($response));
            }
            
            # create a tmp directory for emails
            $parameters['tmp-dir'] = $this->app->utils->strings->randomHex(20);
            $this->app->utils->fileSystem->createDir(TRASH_PATH . DS . $parameters['tmp-dir']);
           
            # move the file to the temp directory 
            $parameters['file-name'] = $this->app->utils->strings->randomHex(20) . '.' . $this->app->utils->arrays->last(explode('.',$file['name'][0]));
            $this->app->utils->fileSystem->moveFileOrDirectory($file['tmp_name'][0],TRASH_PATH . DS . $parameters['tmp-dir'] . DS . $parameters['file-name']);

            $response = ['files' => [[
                'name' => $file['name'][0],
                'size' => $file['size'][0]
            ]]];
            
            # call iresponse api
            $result = Api::call('DataLists','createLists',$parameters);
            
            if(count($result) == 0)
            {
                $response['files'][0]['error'] = 'Could not create lists !';
                die(json_encode($response));
            }
            
            if($result['httpStatus'] == 500)
            {              
                $response['files'][0]['error'] = $result['message'];
                die(json_encode($response));
            }
            
            $response = ['files' => [[
                'name' => $file['name'][0],
                'size' => $file['size'][0],
                'success' => $result['message']
            ]]];

            die(json_encode($response));
        }
        else
        {
            $response['files'][0]['error'] = 'No parameters passed !';
            die(json_encode($response));
        }
    }
    
    
    /**
     * @name emailFetch
     * @description the emailFetch action
     * @before init
     * @after closeConnections,checkForMessage
     */
    public function emailsFetch() 
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
            'data_lists' => 'true',
            'data_lists_fetch' => 'true'
        ]);
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