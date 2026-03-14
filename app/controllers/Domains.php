<?php declare(strict_types=1); namespace IR\App\Controllers; if (!defined('IR_START')) exit('<pre>No direct script access allowed</pre>');
/**
 * @framework       iResponse Framework 
 * @version         1.0
 * @author          Amine Idrissi <contact@iresponse.tech>
 * @date            2024
 * @name            Domains.php	
 */

# core 
use IR\Core\Application as Application;

# mvc 
use IR\Mvc\Controller as Controller;

# models
use IR\App\Models\Admin\Domain as Domain;
use IR\App\Models\Admin\Namecheap as Namecheap;
use IR\App\Models\Admin\GoDaddy as GoDaddy;
use IR\App\Models\Admin\Namecom as Namecom;
use IR\App\Models\Admin\SubName as SubName;

# http 
use IR\Http\Request as Request;

# helpers 
use IR\App\Helpers\Authentication as Authentication;
use IR\App\Helpers\Page as Page;
use IR\App\Helpers\DataTable as DataTable;
use IR\App\Helpers\Permissions as Permissions;

# libraries 
use IR\App\Libraries\Library as Library;

# exceptions
use IR\Exceptions\Types\PageException as PageException;

/**
 * @name Domains
 * @description Domains Controller
 */
class Domains extends Controller
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
            'value',
            'status',
            'availability',
            'expiration_date',
            'has_brand',
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
                        $filters .= '<td> <select name="status" class="form-control form-filter input-sm"> <option value="" selected>All</option> <option value="Activated">Activated</option> <option value="Inactivated">Inactivated</option> <option value="Special">Special</option></select> </td>' . PHP_EOL;
                    }
                    else if($column == 'availability')
                    {
                        $filters .= '<td> <select name="availability" class="form-control form-filter input-sm"> <option value="" selected>All</option> <option value="Available">Available</option> <option value="Taken">Taken</option></select> </td>' . PHP_EOL;
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
            'dns_management' => 'true',
            'domains' => 'true',
            'domains_show' => 'true'
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
                'value',
                'status',
                'availability',
                'expiration_date',
                'has_brand',
                'created_by',
                'created_date'
            ];
            
            # fetching the results to create the ajax list
            die(json_encode(DataTable::init($data,'admin.domains',$columns,new Domain(),'domains','DESC')));
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
        
        # set menu status
        $this->masterView->set([
            'dns_management' => 'true',
            'domains' => 'true',
            'domains_add' => 'true'
        ]);
        
        # set data to the page view
        $this->pageView->set([
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
            'dns_management' => 'true',
            'domains' => 'true',
            'domains_show' => 'true'
        ]);
        
        if(!isset($id) || !is_numeric($id) || intval($id) == 0)
        {
            $valid = false;
        }
        
        $domain = Domain::first(Domain::FETCH_ARRAY,['id = ?',$id]);
        
        if(count($domain) == 0)
        {
            $valid = false;
        }
        
        if($valid == true)
        {
            # set data to the page view
            $this->pageView->set([
                'domain' => $domain
            ]); 
        }
        else
        {
            # stores the message in the session 
            Page::registerMessage('error','Invalid domain id !');
            
            # redirect to lists page
            Page::redirect();
        }
    }
    
    /**
     * @name brands
     * @description the brands action
     * @before init
     * @after closeConnections,checkForMessage
     */
    public function brands() 
    { 
        # check for permissions
        $access = Permissions::checkForAuthorization($this->authenticatedUser,__CLASS__,__FUNCTION__);

        if($access == false)
        {
            throw new PageException('Access Denied !',403);
        }
        
        # set menu status
        $this->masterView->set([
            'dns_management' => 'true',
            'domains' => 'true',
            'domains_brands' => 'true'
        ]);
        
        # set data to the page view
        $this->pageView->set([
            'scripts' => $this->app->utils->fileSystem->readFile(VIEWS_PATH . DS . 'includes' . DS . 'file_upload.html')
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

        $message = 'Internal server error !';
        $flag = 'error';

        if(count($data))
        {      
            # get connected user's username
            $username = $this->authenticatedUser->getEmail();
            
            if($this->app->utils->arrays->get($data,'id') > 0)
            {
                # check for permissions
                $access = Permissions::checkForAuthorization($this->authenticatedUser,__CLASS__,'edit');

                if($access == false)
                {
                    throw new PageException('Access Denied !',403);
                }

                $message = 'Record updated succesfully !';
                $domain = new Domain(['id' => intval($this->app->utils->arrays->get($data,'id'))],true);
                $domain->setValue($this->app->utils->arrays->get($data,'domain-name'));
                $domain->setStatus($this->app->utils->arrays->get($data,'domain-status','Activated'));
                $domain->setLastUpdatedBy($username);
                $domain->setLastUpdatedDate(date('Y-m-d'));
                $result = $domain->update(); 

                if($result > -1)
                {
                    $flag = 'success';
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
                
                $account = strval($this->app->utils->arrays->get($data,'account'));
                $entries = array_filter(explode(PHP_EOL,$this->app->utils->arrays->get($data,'domain-names')));
                
                # filter domains 
                if(is_array($entries) && count($entries) > 0)
                {
                    $tmp = [];
                    
                    foreach ($entries as $row)
                    {
                        $row = str_replace(['"',"'",'@',','],'',preg_replace('/\s+/','',$row));
                        
                        if($this->app->utils->domains->isValidDomain($row))
                        {
                            $tmp[] = [ 'name' => $row , 'expiration-date' => date('Y-m-d',strtotime('+1 year'))] ;
                        }
                    }
                    
                    $entries = $tmp;
                    unset($tmp);
                }
                
                if(count($entries) == 0 && $this->app->utils->strings->contains($account,'|') === false)
                {
                    $message = 'You have to enter domains or select an account or both !';
                    $flag = 'error';
                }
                else
                {
                    $domains = [];
                    $accountName = 'None';
                    $accountType = 'none';
                    $accountId = 0;
                    
                    # dns account domains case
                    if($this->app->utils->strings->contains($account,'|') === true)
                    {
                        $accountId = intval($this->app->utils->arrays->last(explode('|',$account)));
                        $accountType = $this->app->utils->arrays->first(explode('|',$account));
                        
                        # get dns library
                        $library = Library::get($accountType,['account_id' => $accountId]);
                        $accountName = $library->getName();
                        $results = $library->getAllDomains();
                        
                        if(count($results) == 0)
                        {
                            $message = 'This account does not contain domain !';
                            $flag = 'error';
                        }
                        else
                        {
                            # dns account get all domains case
                            if(count($entries) == 0)
                            {
                                foreach ($results as $value)
                                {
                                    $domains[] = $value;
                                }
                            }
                            # dns account get specific domains case
                            else
                            {
                                foreach ($entries as $value)
                                {
                                    if(key_exists($value['name'],$results))
                                    {
                                        $domains[] = $value;
                                    }
                                }
                            }
                        }
                    }
                    # spoofin' domains case
                    else
                    {
                        $domains = $entries;
                        unset($entries);
                    }
                    
                    # add domains
                    if(count($domains) == 0)
                    {
                        $message = 'No valid domains found by the info that you provided !';
                        $flag = 'error';
                    }
                    else
                    {
                        $results = Domain::all(Domain::FETCH_ARRAY,[],['id','value']);
                        $oldDomains = [];

                        foreach ($results as $row) 
                        {
                            $oldDomains[$row['value']] = $row['id'];
                        }

                        # start inserting / updating domains
                        $results = 0;
                        
                        foreach ($domains as $row) 
                        {
                            $update = key_exists($row['name'],$oldDomains);
                            
                            if($update == true)
                            {
                                $domain = new Domain(['id' => $oldDomains[$row['name']]],true);
                                $domain->setLastUpdatedBy($username);
                                $domain->setLastUpdatedDate(date('Y-m-d'));
                            }
                            else
                            {
                                $domain = new Domain();
                                $domain->setStatus('Activated');
                                $domain->setAvailability('Available');
                                $domain->setMtaServerId('0');
                                $domain->setIpId('0');
                                $domain->setHasBrand('No');
                                $domain->setCreatedBy($username);
                                $domain->setCreatedDate(date('Y-m-d'));
                                $domain->setLastUpdatedBy($username);
                                $domain->setLastUpdatedDate(date('Y-m-d'));
                            }
                            
                            $domain->setAccountId($accountId);
                            $domain->setAccountName($accountName);
                            $domain->setAccountType($accountType);
                            $domain->setValue($row['name']);
                            $domain->setExpirationDate($row['expiration-date']);
                            $results += ($update == true) ? $domain->update() : $domain->insert();
                        }  
                        
                        if($results > 0)
                        {
                            $flag = 'success';
                            $message = 'Records inserted succesfully !';
                        }
                    }
                }
            }
        }

        # stores the message in the session 
        Page::registerMessage($flag, $message);

        # redirect to lists page
        Page::redirect();
    }
    
    /**
     * @name uploadBrands
     * @description upload domains brands
     * @before init
     */
    public function uploadBrands() 
    { 
        # check for permissions
        $access = Permissions::checkForAuthorization($this->authenticatedUser,__CLASS__,'brands');
        
        $response = [];
        
        if($access == false)
        {
            $response['files'][0]['error'] = 'Access Denied !';
            die(json_encode($response));
        }
        
        # get files data
        $files = $this->app->http->request->retrieve(Request::ALL,Request::FILES);
        $response = [];
        
        if(count($files))
        {
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
            
            if(!in_array($file['type'][0],['application/zip','application/octet-stream','application/x-zip-compressed','multipart/x-zip']) || $file['size'][0] == 0)
            {
                $response['files'][0]['error'] = 'Unsupported file type : ' . $file['type'][0];
                die(json_encode($response));
            }

            $brandName = 'default.zip';
            
            # check for default brand 
            if($file['name'][0] != 'default.zip')
            {
                # get brand domain 
                $domain = Domain::first(Domain::FETCH_OBJECT,['value = ?',str_replace(['.zip','_'],['','.'],$file['name'][0])]);

                if($domain == null || $domain->getValue() == null || $domain->getValue() == '')
                {
                    $response['files'][0]['error'] = 'Domain not found !';
                    die(json_encode($response));
                }
                
                # update domain status 
                $domain->setHasBrand('Yes');
                $domain->update();
            
                $brandName = str_replace(['.zip','_'],['','.'],$file['name'][0]);
            }
 
            # move the file to the brands directory 
            $brandPath = ASSETS_PATH . DS . 'tracking' . DS . 'brands' . DS . $brandName;
            $this->app->utils->fileSystem->moveFileOrDirectory($file['tmp_name'][0],$brandPath);
            
            $response = ['files' => [[
                'name' => $file['name'][0],
                'size' => $file['size'][0],
                'success' => $file['name'][0] != 'default.zip' ? 'Brand for domain ' . $brandName . ' uploaded successfully !' : 'Default brand uploaded successfully !'
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
     * @name subdomains
     * @description the subdomains action
     * @before init
     * @after closeConnections,checkForMessage
     */
    public function subdomains() 
    { 
        # check for permissions
        $access = Permissions::checkForAuthorization($this->authenticatedUser,__CLASS__,__FUNCTION__);

        if($access == false)
        {
            throw new PageException('Access Denied !',403);
        }
        
        # get post data
        $data = $this->app->http->request->retrieve(Request::ALL,Request::POST);

        if(count($data))
        {
            $flag = 'error';
            $message = 'Could not update sub domains names !';
            
            $subNames = array_unique(array_filter(explode(PHP_EOL,$this->app->utils->arrays->get($data,'sub-names'))));
            $result = -1;
            
            if(count($subNames))
            {
                $username = $this->authenticatedUser->getEmail();
                
                foreach ($subNames as $row) 
                {
                    $name = new SubName();
                    $name->setName(strtolower(trim($row)));
                    $name->setCreatedBy($username);
                    $name->setCreatedDate(date('Y-m-d'));
                    $name->setLastUpdatedBy($username);
                    $name->setLastUpdatedDate(date('Y-m-d'));
                    $result += $name->insert();
                }
            }
            
            if($result > 0)
            {
                $flag = 'success';
                $message = 'Subnames stored successfully !';
            }
            
            # stores the message in the session 
            Page::registerMessage($flag, $message);

            # redirect to previsous page
            Page::redirect();
        }
        
        # preparing the columns array to create the list
        $columnsArray = [
            'id',
            'name',
            'created_by',
            'created_date'
        ];
        
        # creating the html part of the list 
        $columns = Page::createTableHeader($columnsArray);
        $filters = Page::createTableFilters($columnsArray);
        
        # set menu status
        $this->masterView->set([
            'dns_management' => 'true',
            'domains' => 'true',
            'domains_subnames' => 'true'
        ]);
            
        # set data to the page view
        $this->pageView->set([
            'columns' => $columns,
            'filters' => $filters
        ]);
    }
    
    /**
     * @name getSubdomains
     * @description the getSubdomains action
     * @before init
     * @after closeConnections
     */
    public function getSubdomains() 
    { 
        # check for permissions
        $access = Permissions::checkForAuthorization($this->authenticatedUser,__CLASS__,'subdomains');

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
                'created_by',
                'created_date'
            ];
        
            # fetching the results to create the ajax list
            die(json_encode(DataTable::init($data,'admin.subdomain_names',$columns,new SubName(),'tools' . RDS . 'subdomains','DESC',null,false)));
        }
    }
    
    /**
     * @name domainsRecords
     * @description the domainsRecords action
     * @before init
     * @after closeConnections,checkForMessage
     */
    public function domainsRecords() 
    { 
        # check for permissions
        $access = Permissions::checkForAuthorization($this->authenticatedUser,__CLASS__,__FUNCTION__);

        if($access == false)
        {
            throw new PageException('Access Denied !',403);
        }

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
        
        # set menu status
        $this->masterView->set([
            'dns_management' => 'true',
            'domains' => 'true',
            'domains_records' => 'true'
        ]);
            
        # set data to the page view
        $this->pageView->set([
            'accounts' => $accounts
        ]);
    }
    
    /**
     * @name multiRecords
     * @description the multiRecords action
     * @before init
     * @after closeConnections,checkForMessage
     */
    public function multiRecords() 
    { 
        # check for permissions
        $access = Permissions::checkForAuthorization($this->authenticatedUser,__CLASS__,__FUNCTION__);

        if($access == false)
        {
            throw new PageException('Access Denied !',403);
        }

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
        
        # set menu status
        $this->masterView->set([
            'dns_management' => 'true',
            'domains' => 'true',
            'domains_multi_records' => 'true'
        ]);
            
        # set data to the page view
        $this->pageView->set([
            'accounts' => $accounts
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