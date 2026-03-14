<?php declare(strict_types=1); namespace IR\App\Controllers; if (!defined('IR_START')) exit('<pre>No direct script access allowed</pre>');
/**
 * @framework       iResponse Framework 
 * @version         1.0
 * @author          Amine Idrissi <contact@iresponse.tech>
 * @date            2024
 * @name            AffiliateNetworks.php	
 */

# core 
use IR\Core\Application as Application;

# mvc 
use IR\Mvc\Controller as Controller;

# models
use IR\App\Models\Affiliate\AffiliateNetwork as AffiliateNetwork;

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
 * @name AffiliateNetworks
 * @description AffiliateNetworks Controller
 */
class AffiliateNetworks extends Controller
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
            'api_type',
            'affiliate_id',
            'website',
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
            'affiliate_management' => 'true',
            'affiliate_networks' => 'true',
            'affiliate_networks_show' => 'true'
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
                "UPPER(api_type) || ' API'" => 'api_type',
                'affiliate_id',
                'website',
                'created_by',
                'created_date'
            ];
        
            # fetching the results to create the ajax list
            die(json_encode(DataTable::init($data,'affiliate.affiliate_networks',$columns,new AffiliateNetwork(),'affiliate-networks','ASC')));
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
            'affiliate_management' => 'true',
            'affiliate_networks' => 'true',
            'affiliate_networks_add' => 'true'
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
            'affiliate_management' => 'true',
            'affiliate_networks' => 'true',
            'affiliate_networks_show' => 'true'
        ]);
        
        if(!isset($id) || !is_numeric($id) || intval($id) == 0)
        {
            $valid = false;
        }
        
        $affiliateNetwork = AffiliateNetwork::first(AffiliateNetwork::FETCH_ARRAY,['id = ?',$id]);
        
        if(count($affiliateNetwork) == 0)
        {
            $valid = false;
        }
        
        if($valid == true)
        {
            # set data to the page view
            $this->pageView->set([
                'affiliateNetwork' => $affiliateNetwork
            ]); 
        }
        else
        {
            # stores the message in the session 
            Page::registerMessage('error','Invalid affiliate network id !');
            
            # redirect to lists page
            Page::redirect();
        }
    }
    
    /**
     * @name autoLogin
     * @description the auto login action
     * @before init
     * @after closeConnections,checkForMessage
     */
    public function autoLogin() 
    { 
        # check for permissions
        $access = Permissions::checkForAuthorization($this->authenticatedUser,__CLASS__,__FUNCTION__);

        if($access == false)
        {
            throw new PageException('Access Denied !',403);
        }
        
        $arguments = func_get_args(); 
        $id = isset($arguments) && count($arguments) > 0 ? $arguments[0] : null;

        if(isset($id) && is_numeric($id) && intval($id) > 0)
        {
            $affiliateNetwork = AffiliateNetwork::first(AffiliateNetwork::FETCH_ARRAY,['id = ?',$id]);
            
            if(count($affiliateNetwork) == 0)
            {
                # stores the message in the session 
                Page::registerMessage('error','Affiliate network not found !');

                # redirect to lists page
                Page::redirect();
            }
            else
            {
                $templatePath = ASSETS_PATH . DS . 'templates' . DS . 'affiliate' . DS . $affiliateNetwork['api_type'] . '.tpl';
                
                if($this->app->utils->fileSystem->fileExists($templatePath) == FALSE)
                {
                    # stores the message in the session 
                    Page::registerMessage('error','Invalid affiliate network api type !');

                    # redirect to lists page
                    Page::redirect();
                }
                
                $template = $this->app->utils->fileSystem->readFile($templatePath);
                $parsed = parse_url($affiliateNetwork['website']);
                $action = $parsed['scheme'] . '://' . $parsed['host'];
                $username = $affiliateNetwork['username'];
                $password = $affiliateNetwork['password'];
                
                # prepare template
                $template = str_replace([
                    '$p_action',
                    '$p_username',
                    '$p_password'
                    ],[
                        $action,
                        $username,
                        $password
                    ],$template);
                   
                # prevent layout and echo out template
                $this->showMasterView = false;
                echo $template;
            }
        }
        else
        {
            # stores the message in the session 
            Page::registerMessage('error','Invalid affiliate network id !');
            
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
            $affiliateNetwork = new AffiliateNetwork();
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
                $affiliateNetwork->setId(intval($this->app->utils->arrays->get($data,'id')));
                $affiliateNetwork->load();
                $affiliateNetwork->setLastUpdatedBy($username);
                $affiliateNetwork->setLastUpdatedDate(date('Y-m-d'));
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
                $affiliateNetwork->setCreatedBy($username);
                $affiliateNetwork->setCreatedDate(date('Y-m-d'));
                $affiliateNetwork->setLastUpdatedBy($username);
                $affiliateNetwork->setLastUpdatedDate(date('Y-m-d'));
            }

            $affiliateNetwork->setAffiliateId($this->app->utils->arrays->get($data,'affiliate-id'));
            $affiliateNetwork->setName($this->app->utils->arrays->get($data,'name'));
            $affiliateNetwork->setCompanyName($this->app->utils->arrays->get($data,'company-name'));
            $affiliateNetwork->setNetworkId($this->app->utils->arrays->get($data,'network-id'));
            $affiliateNetwork->setStatus($this->app->utils->arrays->get($data,'status','activated'));
            $affiliateNetwork->setWebsite($this->app->utils->arrays->get($data,'website'));
            $affiliateNetwork->setUsername($this->app->utils->arrays->get($data,'username'));
            $affiliateNetwork->setPassword($this->app->utils->arrays->get($data,'password'));
            $affiliateNetwork->setApiType($this->app->utils->arrays->get($data,'api-type'));
            $affiliateNetwork->setApiKey($this->app->utils->arrays->get($data,'api-key'));
            $affiliateNetwork->setSubIdOne(implode('|',array_filter($this->app->utils->arrays->get($data,'sub-prefix_1'))));
            $affiliateNetwork->setSubIdTwo(implode('|',array_filter($this->app->utils->arrays->get($data,'sub-prefix_2'))));
            $affiliateNetwork->setSubIdThree(implode('|',array_filter($this->app->utils->arrays->get($data,'sub-prefix_3'))));
            
            # api url case
            switch($affiliateNetwork->getApiType())
            {
                case 'hasoffers' : 
                {
                    $affiliateNetwork->setApiUrl("https://api-p03.hasoffers.com/v3");
                    break;
                }
                case 'hitpath' : 
                {
                    $website = parse_url($affiliateNetwork->getWebsite());
                    $affiliateNetwork->setApiUrl('http://' . str_replace('affiliate','api',$this->app->utils->arrays->get($website,'host')) . RDS . 'pubapi.php');
                    break;
                } 
                case 'cake' : 
                {
                    $website = parse_url($affiliateNetwork->getWebsite());
                    $affiliateNetwork->setApiUrl('http://' . $this->app->utils->arrays->get($website,'host') . RDS . 'affiliates' . RDS . 'api');
                    break;
                }
                case 'everflow' : 
                {
                    $affiliateNetwork->setApiUrl('https://api.eflow.team');
                    break;
                }
                case 'w4' : 
                {
                    $affiliateNetwork->setApiUrl('https://w4api.com/pub');
                    break;
                }
                case 'pullstat' : 
                {
                    $affiliateNetwork->setApiUrl('https://reports.statpull.com/api/v1/affiliates');
                    break;
                }
            }
            
            $result = $update == false ? $affiliateNetwork->insert() : $affiliateNetwork->update(); 

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


