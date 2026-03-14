<?php declare(strict_types=1); namespace IR\App\Controllers; if (!defined('IR_START')) exit('<pre>No direct script access allowed</pre>');
/**
 * @framework       iResponse Framework 
 * @version         1.0
 * @author          Amine Idrissi <contact@iresponse.tech>
 * @date            2024
 * @name            Offers.php	
 */

# core 
use IR\Core\Application as Application;

# mvc 
use IR\Mvc\Controller as Controller;

# models
use IR\App\Models\Affiliate\AffiliateNetwork as AffiliateNetwork;
use IR\App\Models\Affiliate\Offer as Offer;
use IR\App\Models\Affiliate\Vertical as Vertical;
use IR\App\Models\Affiliate\FromName as FromName;
use IR\App\Models\Affiliate\Subject as Subject;
use IR\App\Models\Affiliate\Creative as Creative;
use IR\App\Models\Affiliate\Link as Link;
use IR\App\Models\Affiliate\Suppression as Suppression;
use IR\App\Models\Lists\DataProvider as DataProvider;
use IR\App\Models\Admin\Isp as Isp;

# http 
use IR\Http\Request as Request;

# helpers 
use IR\App\Helpers\Authentication as Authentication;
use IR\App\Helpers\Page as Page;
use IR\App\Helpers\DataTable as DataTable;
use IR\App\Helpers\Permissions as Permissions;
use IR\App\Helpers\Api as Api;

# exceptions
use IR\Exceptions\Types\SystemException as SystemException;
use IR\Exceptions\Types\PageException as PageException;

/**
 * @name Offers
 * @description Offers Controller
 */
class Offers extends Controller
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
            'affiliate_network_name',
            'status',
            'production_id',
            'type',
            'payout'
        ];
        
        # creating the html part of the list 
        $columns = Page::createTableHeader($columnsArray);
        $filters = Page::createTableFilters($columnsArray);
        
        # set menu status
        $this->masterView->set([
            'affiliate_management' => 'true',
            'offers' => 'true',
            'offers_show' => 'true'
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
                'affiliate_network_name',
                'status',
                'production_id',
                'type',
                'payout'
            ];
        
            # fetching the results to create the ajax list
            die(json_encode(DataTable::init($data,'affiliate.offers',$columns,new Offer(),'offers','DESC')));
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
            'offers' => 'true',
            'offers_add' => 'true'
        ]);
        
        # set data to the page view
        $this->pageView->set([
            'affiliateNetworks' => AffiliateNetwork::all(AffiliateNetwork::FETCH_ARRAY,['status = ?','Activated']),
            'verticals' => Vertical::all(Vertical::FETCH_ARRAY,['status = ?','Activated'])
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
            'offers' => 'true',
            'offers_show' => 'true'
        ]);
        
        if(!isset($id) || !is_numeric($id) || intval($id) == 0)
        {
            $valid = false;
        }
        
        $offer = Offer::first(Offer::FETCH_ARRAY,['id = ?',$id]);
        
        if(count($offer) == 0)
        {
            $valid = false;
        }
        
        if($valid == true)
        {
            # from names part
            $columns = [
                'id',
                'name',
                'value',
                'status',
                'created_by',
                'created_date'
            ];

            # creating the html part of the list 
            $fromNamesColumns = '';
            $fromNamesFilter = '';

            foreach ($columns as $column) 
            {
                if($column != 'id')
                {
                    $fromNamesColumns .= '<th>' . ucwords(str_replace('_',' ',strtolower($column))) . '</th>' . PHP_EOL;

                    if(strpos($column,'_date') > -1 || strpos($column,'_time') > -1)
                    {
                        $fromNamesFilter .= '<td> <div id="' . $column . '_range" class="input-group date-range-picker"> <input type="text" class="form-control form-filter" name="' . $column . '_range"> <span class="input-group-btn"> <button class="btn default date-range-toggle" type="button"> <i class="fa fa-calendar"></i> </button> </span> </div> </td>' . PHP_EOL;
                    }
                    else
                    {
                        if($column == 'status')
                        {
                            $fromNamesFilter .= '<td> <select name="status" class="form-control form-filter input-sm"> <option value="" selected>All</option> <option value="Activated">Activated</option> <option value="Inactivated">Inactivated</option> </select> </td>' . PHP_EOL;
                        }
                        else
                        {
                            $fromNamesFilter .= '<td><input type="text" class="form-control form-filter" name="' . $column . '"></td>' . PHP_EOL;
                        }
                    }
                }
            }
            
            # subjects part
            $columns = [
                'id',
                'name',
                'value',
                'status',
                'created_by',
                'created_date'
            ];

            # creating the html part of the list 
            $subjectsColumns = '';
            $subjectsFilters = '';

            foreach ($columns as $column) 
            {
                if($column != 'id')
                {
                    $subjectsColumns .= '<th>' . ucwords(str_replace('_',' ',strtolower($column))) . '</th>' . PHP_EOL;

                    if(strpos($column,'_date') > -1 || strpos($column,'_time') > -1)
                    {
                        $subjectsFilters .= '<td> <div id="' . $column . '_range" class="input-group date-range-picker"> <input type="text" class="form-control form-filter" name="' . $column . '_range"> <span class="input-group-btn"> <button class="btn default date-range-toggle" type="button"> <i class="fa fa-calendar"></i> </button> </span> </div> </td>' . PHP_EOL;
                    }
                    else
                    {
                        if($column == 'status')
                        {
                            $subjectsFilters .= '<td> <select name="status" class="form-control form-filter input-sm"> <option value="" selected>All</option> <option value="Activated">Activated</option> <option value="Inactivated">Inactivated</option> </select> </td>' . PHP_EOL;
                        }
                        else
                        {
                            $subjectsFilters .= '<td><input type="text" class="form-control form-filter" name="' . $column . '"></td>' . PHP_EOL;
                        }
                    }
                }
            }
            
            # cretives part
            $columns = [
                'id',
                'name',
                'status',
                'created_by',
                'created_date'
            ];

            # creating the html part of the list 
            $creativesColumns = '';
            $creativesFilters = '';

            foreach ($columns as $column) 
            {
                if($column != 'id')
                {
                    $creativesColumns .= '<th>' . ucwords(str_replace('_',' ',strtolower($column))) . '</th>' . PHP_EOL;

                    if(strpos($column,'_date') > -1 || strpos($column,'_time') > -1)
                    {
                        $creativesFilters .= '<td> <div id="' . $column . '_range" class="input-group date-range-picker"> <input type="text" class="form-control form-filter" name="' . $column . '_range"> <span class="input-group-btn"> <button class="btn default date-range-toggle" type="button"> <i class="fa fa-calendar"></i> </button> </span> </div> </td>' . PHP_EOL;
                    }
                    else
                    {
                        if($column == 'status')
                        {
                            $creativesFilters .= '<td> <select name="status" class="form-control form-filter input-sm"> <option value="" selected>All</option> <option value="Activated">Activated</option> <option value="Inactivated">Inactivated</option> </select> </td>' . PHP_EOL;
                        }
                        else
                        {
                            $creativesFilters .= '<td><input type="text" class="form-control form-filter" name="' . $column . '"></td>' . PHP_EOL;
                        }
                    }
                }
            }
            
            # set data to the page view
            $this->pageView->set([
                'offer' => $offer,
                'affiliateNetworks' => AffiliateNetwork::all(AffiliateNetwork::FETCH_ARRAY,['status = ?','Activated']),
                'verticals' => Vertical::all(Vertical::FETCH_ARRAY,['status = ?','Activated']),
                'fromNamesColumns' => $fromNamesColumns,
                'fromNamesFilters' => $fromNamesFilter,
                'subjectsColumns' => $subjectsColumns,
                'subjectsFilters' => $subjectsFilters,
                'creativesColumns' => $creativesColumns,
                'creativesFilters' => $creativesFilters
            ]); 
        }
        else
        {
            # stores the message in the session 
            Page::registerMessage('error','Invalid offer id !');
            
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
            $offer = new Offer();
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
                $offer->setId(intval($this->app->utils->arrays->get($data,'id')));
                $offer->load();
                $offer->setLastUpdatedBy($username);
                $offer->setLastUpdatedDate(date('Y-m-d'));
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
                $offer->setCreatedBy($username);
                $offer->setCreatedDate(date('Y-m-d'));
                $offer->setLastUpdatedBy($username);
                $offer->setLastUpdatedDate(date('Y-m-d'));
            }

            $affiliateNetwork = AffiliateNetwork::first(AffiliateNetwork::FETCH_ARRAY,['id = ?',intval($this->app->utils->arrays->get($data,'affiliate-network-id'))]);

            if(count($affiliateNetwork) == 0)
            {
                $message = 'Affiliate network not found !';
            }
            else
            {
                $offer->setStatus($this->app->utils->arrays->get($data,'offer-status','activated'));
                $offer->setAffiliateNetworkId($this->app->utils->arrays->get($affiliateNetwork,'id'));
                $offer->setAffiliateNetworkName($this->app->utils->arrays->get($affiliateNetwork,'name'));
                $offer->setProductionId($this->app->utils->arrays->get($data,'production-id'));
                $offer->setCampaignId($this->app->utils->arrays->get($data,'campaign-id'));
                $offer->setName($this->app->utils->arrays->get($data,'offer-name'));
                $offer->setCountries(implode('/',$this->app->utils->arrays->get($data,'countries')));
                $offer->setVerticalsIds(implode(',',$this->app->utils->arrays->get($data,'vertical-ids')));
                $offer->setAvailableDays(implode(',',$this->app->utils->arrays->get($data,'days')));
                $offer->setDescription(base64_encode($this->app->utils->arrays->get($data,'description','No Description !')));
                $offer->setRules(base64_encode($this->app->utils->arrays->get($data,'rules','No Rules!')));
                $offer->setExpirationDate($this->app->utils->arrays->get($data,'expiration-date'));
                $offer->setType(strtoupper($this->app->utils->arrays->get($data,'payout-type')));
                $offer->setDefaultSuppressionLink($this->app->utils->arrays->get($data,'default-suppression-link'));
                $offer->setPayout(number_format(floatval(str_replace(['$','€'],'',$this->app->utils->strings->trim($this->app->utils->arrays->get($data,'payout-amount')))),2));
                $result = $update == false ? $offer->insert() : $offer->update(); 

                if($result > -1)
                {
                    $flag = 'success';
                }
            }
        }

        # stores the message in the session 
        Page::registerMessage($flag, $message);

        # redirect to lists page
        Page::redirect();
    }
    
    /**
     * @name import
     * @description the import action
     * @before init
     * @after closeConnections
     */
    public function import() 
    { 
        # get post data
        $data = $this->app->http->request->retrieve(Request::ALL,Request::POST);

        $message = 'Internal server error !';
        $flag = 'error';

        if(count($data))
        {   
            $affiliateNetworkId = intval($this->app->utils->arrays->get($data,'affiliate-network-id'));
            
            if($affiliateNetworkId == 0)
            {
                $message = 'Incorrect affiliate network id !';
            }
            else
            {
                $getAll = $this->app->utils->arrays->get($data,'get-all','off');
                $offerIds = ($getAll != 'on') ? array_unique(array_filter(explode(PHP_EOL,$this->app->utils->arrays->get($data,'production-ids')))) : [];
                
                # check if there are some unwanted characters
                if(count($offerIds))
                {
                    $tmp = [];
                    
                    foreach ($offerIds as $offerId)
                    {
                        $tmp[] = preg_replace('/\s*/m','',$offerId);
                    }
                    
                    $offerIds = $tmp;
                }
                
                $maxCreatives = intval($this->app->utils->arrays->get($data,'max-creatives'));
                
                try 
                {
                    # call iresponse api
                    $result = Api::call('Affiliate','getOffers',['affiliate-network-id' => $affiliateNetworkId,'offers-ids' => $offerIds,'max-creatives' => $maxCreatives]);

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
                catch (SystemException $e) 
                {
                    $message = $e->getMessage();
                }  
            }
        }

        # stores the message in the session 
        Page::registerMessage($flag, $message);

        # redirect to lists page
        Page::redirect();
    }
    
    /**
     * @name names
     * @description the names action
     * @before init
     * @after closeConnections
     */
    public function names() 
    {
        # check for permissions
        $access = Permissions::checkForAuthorization($this->authenticatedUser,__CLASS__,'edit');

        if($access == false)
        {
            throw new PageException('Access Denied !',403);
        }
        
        # set menu status
        $this->masterView->set([
            'affiliate_management' => 'true',
            'offers' => 'true',
            'offers_show' => 'true'
        ]);
        
        $arguments = func_get_args();
        $page = isset($arguments) && count($arguments) ? $arguments[0] : '';
  
        if(isset($page) && $page != '')
        {
            switch ($page)
            {
                case 'add' :
                {
                    $offerId = isset($arguments) && count($arguments) > 1 ? intval($arguments[1]) : 0;
                    
                    if($offerId == 0)
                    {
                        # stores the message in the session 
                        Page::registerMessage('error','Invalid offer id !');
                        
                        # redirect to lists page
                        Page::redirect();
                    }
                    
                    # set data to the page view
                    $this->pageView->set([
                        'offer' => Offer::first(Offer::FETCH_ARRAY,['id = ?',$offerId])
                    ]);
                    
                    $this->pageView->setFile(VIEWS_PATH . DS . 'offers' . DS . 'names' . DS . 'add.' . DEFAULT_EXTENSION);
                    
                    # check for message 
                    Page::checkForMessage($this);
                    break;
                }
                case 'edit' :
                {
                    $id = isset($arguments) && count($arguments) > 1 ? intval($arguments[1]) : 0;
                    $fromName = FromName::first(FromName::FETCH_ARRAY,['id = ?',$id]); 
                    
                    if(count($fromName) == 0)
                    {
                        # stores the message in the session 
                        Page::registerMessage('error','Invalid offer from name id !');

                        # redirect to lists page
                        Page::redirect();
                    }
                    else
                    {
                        # set data to the page view
                        $this->pageView->set([
                            'fromName' => $fromName
                        ]);
                    }

                    $this->pageView->setFile(VIEWS_PATH . DS . 'offers' . DS . 'names' . DS . 'edit.' . DEFAULT_EXTENSION);
                    
                    # check for message 
                    Page::checkForMessage($this);
                    break;
                }
                case 'save' :
                {
                    # get post data
                    $data = $this->app->http->request->retrieve(Request::ALL,Request::POST);

                    $message = 'Internal server error !';
                    $flag = 'error';
                    
                    $username = $this->authenticatedUser->getEmail();
                    
                    if(count($data))
                    {  
                        if($this->app->utils->arrays->get($data,'id') > 0)
                        {
                            $fromName = new FromName();
                            $fromName->setId(intval($this->app->utils->arrays->get($data,'id')));
                            $fromName->load();
                            $fromName->setValue($this->app->utils->arrays->get($data,'from-name'));
                            $fromName->setStatus($this->app->utils->arrays->get($data,'status','Activated'));
                            $fromName->setLastUpdatedBy($username);
                            $fromName->setLastUpdatedDate(date('Y-m-d'));
                            
                            $result = $fromName->update(); 

                            if($result > -1)
                            {
                                $message = 'Record updated succesfully !';
                                $flag = 'success';
                            }
                        }
                        else
                        {
                            $offerId = intval($this->app->utils->arrays->get($data,'offer-id'));
                            $fromNames = array_filter(explode(PHP_EOL,$this->app->utils->arrays->get($data,'from-names')));
                            
                            if(count($fromNames) == 0)
                            {
                                $message = 'You have to enter domains or select an account or both !';
                                $flag = 'error';
                            }
                            else
                            {
                                $offer = Offer::first(Offer::FETCH_ARRAY,['id = ?',$offerId]);
                                $result = -1;

                                if(count($offer) == 0)
                                {
                                    $message = 'Offer not found !';
                                }
                                else
                                {
                                    $index = 0;
                                    $fromNameCheck = FromName::first(FromName::FETCH_ARRAY,['offer_id = ?',intval($this->app->utils->arrays->get($data,'offer-id'))],['id','name'],'id','DESC');

                                    if(count($fromNameCheck))
                                    {
                                        $index = intval($this->app->utils->arrays->last(explode('_',$this->app->utils->arrays->get($fromNameCheck,'name'))));
                                    }

                                    $index++;
                                    
                                    foreach ($fromNames as $value) 
                                    {
                                        $fromName = new FromName();
                                        $fromName->setOfferId(intval($this->app->utils->arrays->get($offer,'id')));
                                        $fromName->setAffiliateNetworkId(intval($this->app->utils->arrays->get($offer,'affiliate_network_id')));
                                        $fromName->setName("offer_from_name_{$index}");
                                        $fromName->setValue($value);
                                        $fromName->setStatus($this->app->utils->arrays->get($data,'status','Activated'));
                                        $fromName->setCreatedBy($username);
                                        $fromName->setCreatedDate(date('Y-m-d'));
                                        $fromName->setLastUpdatedBy($username);
                                        $fromName->setLastUpdatedDate(date('Y-m-d'));
                                        $result += $fromName->insert();
                                        $index++;
                                    }
                                }
                                
                                if($result > 0)
                                {
                                    $message = 'Record stored succesfully !';
                                    $flag = 'success';
                                }
                            }
                        }
                    }
        
                    # stores the message in the session 
                    Page::registerMessage($flag, $message);

                    # redirect to lists page
                    Page::redirect();
                    break;
                }
                case 'get' : 
                {
                    # get post data
                    $data = $this->app->http->request->retrieve(Request::ALL,Request::POST);

                    if(count($data))
                    {
                        $offerId = isset($arguments) && count($arguments) ? intval($arguments[1]) : 0;
                        
                        # preparing the columns array to create the list
                        $columns = [
                            'id',
                            'name',
                            'value',
                            'status',
                            'created_by',
                            'created_date'
                        ];
                        
                        # fetching the results to create the ajax list
                        $query = $this->app->database('system')->query()->from('affiliate.from_names',$columns)->where('offer_id = ?',$offerId);
                        die(json_encode(DataTable::init($data,'affiliate.from_names',$columns,new FromName(),'offers' . RDS . 'names','DESC',$query)));
                    }
                    
                    break;
                }
            }
        }
    }
    
    /**
     * @name subjects
     * @description the subjects action
     * @before init
     * @after closeConnections
     */
    public function subjects() 
    {
        # check for permissions
        $access = Permissions::checkForAuthorization($this->authenticatedUser,__CLASS__,'edit');

        if($access == false)
        {
            throw new PageException('Access Denied !',403);
        }
        
        # set menu status
        $this->masterView->set([
            'affiliate_management' => 'true',
            'offers' => 'true',
            'offers_show' => 'true'
        ]);
        
        $arguments = func_get_args();
        $page = isset($arguments) && count($arguments) ? $arguments[0] : '';
  
        if(isset($page) && $page != '')
        {
            switch ($page)
            {
                case 'add' :
                {
                    $offerId = isset($arguments) && count($arguments) > 1 ? intval($arguments[1]) : 0;
                    
                    if($offerId == 0)
                    {
                        # stores the message in the session 
                        Page::registerMessage('error','Invalid offer id !');
                        
                        # redirect to lists page
                        Page::redirect();
                    }
                    
                    # set data to the page view
                    $this->pageView->set([
                        'offer' => Offer::first(Offer::FETCH_ARRAY,['id = ?',$offerId])
                    ]);
                        
                    $this->pageView->setFile(VIEWS_PATH . DS . 'offers' . DS . 'subjects' . DS . 'add.' . DEFAULT_EXTENSION);
                    
                    # check for message 
                    Page::checkForMessage($this);
                    break;
                }
                case 'edit' :
                {
                    $id = isset($arguments) && count($arguments) > 1 ? intval($arguments[1]) : 0;
                    $subject = Subject::first(Subject::FETCH_ARRAY,['id = ?',$id]); 
                    
                    if(count($subject) == 0)
                    {
                        # stores the message in the session 
                        Page::registerMessage('error','Invalid offer subject id !');

                        # redirect to lists page
                        Page::redirect();
                    }
                    else
                    {
                        # set data to the page view
                        $this->pageView->set([
                            'subject' => $subject
                        ]);
                    }

                    $this->pageView->setFile(VIEWS_PATH . DS . 'offers' . DS . 'subjects' . DS . 'edit.' . DEFAULT_EXTENSION);
                    
                    # check for message 
                    Page::checkForMessage($this);
                    break;
                }
                case 'save' :
                {
                    # get post data
                    $data = $this->app->http->request->retrieve(Request::ALL,Request::POST);

                    $message = 'Internal server error !';
                    $flag = 'error';
                    
                    $username = $this->authenticatedUser->getEmail();
                    
                    if(count($data))
                    {  
                        if($this->app->utils->arrays->get($data,'id') > 0)
                        {
                            $subject = new Subject();
                            $subject->setId(intval($this->app->utils->arrays->get($data,'id')));
                            $subject->load();
                            $subject->setValue($this->app->utils->arrays->get($data,'subject'));
                            $subject->setStatus($this->app->utils->arrays->get($data,'status','Activated'));
                            $subject->setLastUpdatedBy($username);
                            $subject->setLastUpdatedDate(date('Y-m-d'));
                            
                            $result = $subject->update(); 

                            if($result > -1)
                            {
                                $message = 'Record updated succesfully !';
                                $flag = 'success';
                            }
                        }
                        else
                        {
                            $offerId = intval($this->app->utils->arrays->get($data,'offer-id'));
                            $subjects = array_filter(explode(PHP_EOL,$this->app->utils->arrays->get($data,'subjects')));
                            
                            if(count($subjects) == 0)
                            {
                                $message = 'You have to enter domains or select an account or both !';
                                $flag = 'error';
                            }
                            else
                            {
                                $offer = Offer::first(Offer::FETCH_ARRAY,['id = ?',$offerId]);
                                $result = -1;

                                if(count($offer) == 0)
                                {
                                    $message = 'Offer not found !';
                                }
                                else
                                {
                                    $index = 0;
                                    $subjectCheck = Subject::first(Subject::FETCH_ARRAY,['offer_id = ?',intval($this->app->utils->arrays->get($data,'offer-id'))],['id','name'],'id','DESC');

                                    if(count($subjectCheck))
                                    {
                                        $index = intval($this->app->utils->arrays->last(explode('_',$this->app->utils->arrays->get($subjectCheck,'name'))));
                                    }

                                    $index++;
                            
                                    foreach ($subjects as $value) 
                                    {
                                        $subject = new Subject();
                                        $subject->setOfferId(intval($this->app->utils->arrays->get($offer,'id')));
                                        $subject->setAffiliateNetworkId(intval($this->app->utils->arrays->get($offer,'affiliate_network_id')));
                                        $subject->setName("offer_subject_{$index}");
                                        $subject->setValue($value);
                                        $subject->setStatus($this->app->utils->arrays->get($data,'status','Activated'));
                                        $subject->setCreatedBy($username);
                                        $subject->setCreatedDate(date('Y-m-d'));
                                        $subject->setLastUpdatedBy($username);
                                        $subject->setLastUpdatedDate(date('Y-m-d'));
                                        $result += $subject->insert();
                                        $index++;
                                    }
                                }
                                
                                if($result > 0)
                                {
                                    $message = 'Record stored succesfully !';
                                    $flag = 'success';
                                }
                            }
                        }
                    }
        
                    # stores the message in the session 
                    Page::registerMessage($flag, $message);

                    # redirect to lists page
                    Page::redirect();
                    break;
                }
                case 'get' : 
                {
                    # get post data
                    $data = $this->app->http->request->retrieve(Request::ALL,Request::POST);

                    if(count($data))
                    {
                        $offerId = isset($arguments) && count($arguments) ? intval($arguments[1]) : 0;
                        
                        # preparing the columns array to create the list
                        $columns = [
                            'id',
                            'name',
                            'value',
                            'status',
                            'created_by',
                            'created_date'
                        ];
                        
                        # fetching the results to create the ajax list
                        $query = $this->app->database('system')->query()->from('affiliate.subjects',$columns)->where('offer_id = ?',$offerId);
                        die(json_encode(DataTable::init($data,'affiliate.subjects',$columns,new Subject(),'offers' . RDS . 'subjects','DESC',$query)));
                    }
                    
                    break;
                }
            }
        }
    }
    
    /**
     * @name creatives
     * @description the creatives action
     * @before init
     * @after closeConnections
     */
    public function creatives() 
    {
        # check for permissions
        $access = Permissions::checkForAuthorization($this->authenticatedUser,__CLASS__,'edit');

        if($access == false)
        {
            throw new PageException('Access Denied !',403);
        }
        
        # set menu status
        $this->masterView->set([
            'affiliate_management' => 'true',
            'offers' => 'true',
            'offers_show' => 'true'
        ]);
        
        $arguments = func_get_args();
        $page = isset($arguments) && count($arguments) ? $arguments[0] : '';
  
        if(isset($page) && $page != '')
        {
            switch ($page)
            {
                case 'add' :
                {
                    $offerId = isset($arguments) && count($arguments) > 1 ? intval($arguments[1]) : 0;
                    
                    if($offerId == 0)
                    {
                        # stores the message in the session 
                        Page::registerMessage('error','Invalid offer id !');
                        
                        # redirect to lists page
                        Page::redirect();
                    }
                    
                    # set data to the page view
                    $this->pageView->set([
                        'offer' => Offer::first(Offer::FETCH_ARRAY,['id = ?',$offerId])
                    ]);
                    
                    $this->pageView->setFile(VIEWS_PATH . DS . 'offers' . DS . 'creatives' . DS . 'add.' . DEFAULT_EXTENSION);
                    
                    # check for message 
                    Page::checkForMessage($this);
                    break;
                }
                case 'edit' :
                {
                    $id = isset($arguments) && count($arguments) > 1 ? intval($arguments[1]) : 0;
                    $creative = Creative::first(FromName::FETCH_ARRAY,['id = ?',$id]); 
                    
                    if(count($creative) == 0)
                    {
                        # stores the message in the session 
                        Page::registerMessage('error','Invalid offer creative id !');

                        # redirect to lists page
                        Page::redirect();
                    }
                    else
                    {
                        # set data to the page view
                        $this->pageView->set([
                            'creative' => $creative,
                            'links' => Link::all(Link::FETCH_ARRAY,['creative_id = ?',$id])
                        ]);
                    }

                    $this->pageView->setFile(VIEWS_PATH . DS . 'offers' . DS . 'creatives' . DS . 'edit.' . DEFAULT_EXTENSION);
                    
                    # check for message 
                    Page::checkForMessage($this);
                    break;
                }
                case 'save' :
                {
                    # get post data
                    $data = $this->app->http->request->retrieve(Request::ALL,Request::POST);

                    $message = 'Internal server error !';
                    $flag = 'error';
                    
                    $username = $this->authenticatedUser->getEmail();
                    $creative = new Creative();
                    
                    if(count($data))
                    {  
                        # update case
                        if($this->app->utils->arrays->get($data,'id') > 0)
                        {
                            $update = true;
                            $message = 'Record updated succesfully !';
                            $creative->setId(intval($this->app->utils->arrays->get($data,'id')));
                            $creative->load();
                            $creative->setLastUpdatedBy($username);
                            $creative->setLastUpdatedDate(date('Y-m-d'));
                        }
                        else
                        {
                            $update = false;
                            $message = 'Record stored succesfully !';
                            $creative->setCreatedBy($username);
                            $creative->setCreatedDate(date('Y-m-d'));
                            $creative->setLastUpdatedBy($username);
                            $creative->setLastUpdatedDate(date('Y-m-d'));
                        }

                        $offer = Offer::first(Offer::FETCH_ARRAY,['id = ?',intval($this->app->utils->arrays->get($data,'offer-id'))]);
                        $result = -1;

                        if(count($offer) == 0)
                        {
                            $message = 'Offer not found !';
                        }
                        else
                        {
                            $creative->setOfferId(intval($this->app->utils->arrays->get($offer,'id')));
                            $creative->setAffiliateNetworkId(intval($this->app->utils->arrays->get($offer,'affiliate_network_id')));
                            $creative->setValue(base64_encode($this->app->utils->arrays->get($data,'creative-html')));
                            $creative->setStatus($this->app->utils->arrays->get($data,'status','Activated'));

                            if($update == false)
                            {
                                $index = 0;
                                $creativeCheck = Creative::first(Creative::FETCH_ARRAY,['offer_id = ?',intval($this->app->utils->arrays->get($data,'offer-id'))],['id','name'],'id','DESC');
                                
                                if(count($creativeCheck))
                                {
                                    $index = intval($this->app->utils->arrays->last(explode('_',$this->app->utils->arrays->get($creativeCheck,'name'))));
                                }
                                
                                $index++;
                                
                                $creative->setName("offer_creative_{$index}");
                            }

                            $result = $update == false ? $creative->insert() : $creative->update(); 

                            if($result > -1)
                            {
                                # delete old links if exists
                                if(intval($this->app->utils->arrays->get($data,'id')) > 0)
                                {
                                    Application::getCurrent()->database('system')->query()->from('affiliate.links')->where('creative_id = ?',intval($this->app->utils->arrays->get($data,'id')))->delete();
                                }

                                $id = $this->app->utils->arrays->get($data,'id') > 0 ? intval($this->app->utils->arrays->get($data,'id')) : $result;

                                if(count($this->app->utils->arrays->get($data,'group-c')))
                                {
                                    foreach ($this->app->utils->arrays->get($data,'group-c') as $row)
                                    {
                                        if(count($row))
                                        {
                                            $link = new Link();
                                            $link->setOfferId(intval($this->app->utils->arrays->get($offer,'id')));
                                            $creative->setAffiliateNetworkId(intval($this->app->utils->arrays->get($offer,'affiliate_network_id')));
                                            $link->setCreativeId($id);
                                            $link->setType($this->app->utils->arrays->get($row,'link-type'));
                                            $link->setValue($this->app->utils->arrays->get($row,'link-value'));
                                            $link->setStatus($this->app->utils->arrays->get($data,'status','Activated'));
                                            $link->setCreatedBy($username);
                                            $link->setCreatedDate(date('Y-m-d'));
                                            $link->setLastUpdatedBy($username);
                                            $link->setLastUpdatedDate(date('Y-m-d'));
                                            $link->insert();
                                        }
                                    }
                                }
                                
                                $flag = 'success';
                            }
                        }
                    }
        
                    # stores the message in the session 
                    Page::registerMessage($flag, $message);

                    # redirect to lists page
                    Page::redirect();
                    break;
                }
                case 'get' : 
                {
                    # get post data
                    $data = $this->app->http->request->retrieve(Request::ALL,Request::POST);

                    if(count($data))
                    {
                        $offerId = isset($arguments) && count($arguments) ? intval($arguments[1]) : 0;
                        
                        # preparing the columns array to create the list
                        $columns = [
                            'id',
                            'name',
                            'status',
                            'created_by',
                            'created_date'
                        ];
                        
                        # fetching the results to create the ajax list
                        $query = $this->app->database('system')->query()->from('affiliate.creatives',$columns)->where('offer_id = ?',$offerId);
                        die(json_encode(DataTable::init($data,'affiliate.creatives',$columns,new Creative(),'offers' . RDS . 'creatives','DESC',$query)));
                    }
                    
                    break;
                }
            }
        }
    }
    
    /**
     * @name suppression
     * @description the suppression action
     * @before init
     * @after closeConnections,checkForMessage
     */
    public function suppression() 
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
            'offers' => 'true',
            'offers_suppression' => 'true'
        ]);
        
        # preparing the columns array to create the list
        $columnsArray = [
            'id',
            'affiliate_network_name',
            'offer_name',
            'progress',
            'emails_found',
            'status',
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
                        $filters .= '<td> <select name="status" class="form-control form-filter input-sm"> <option value="" selected>All</option> <option value="In Progress">In Progress</option> <option value="Completed">Completed</option> <option value="Interrupted">Interrupted</option><option value="Error">Error</option></select> </td>' . PHP_EOL;
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
            'dataProviders' => DataProvider::all(DataProvider::FETCH_ARRAY,['status = ?','Activated'],['id','name']),
            'isps' => Isp::all(Isp::FETCH_ARRAY,['status = ?','Activated'],['id','name']),
            'affiliateNetworks' => AffiliateNetwork::all(AffiliateNetwork::FETCH_ARRAY,['status = ?','Activated'],['id','name']),
            'columns' => $columns,
            'filters' => $filters,
        ]);
    }
    
    /**
     * @name getSuppressionProcesses
     * @description the getSuppressionProcesses action
     * @before init
     * @after closeConnections
     */
    public function getSuppressionProcesses() 
    { 
        # check for permissions
        $access = Permissions::checkForAuthorization($this->authenticatedUser,__CLASS__,'suppression');

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
                'affiliate_network_name',
                'offer_name',
                'progress',
                'emails_found',
                'status',
                'start_time',
                'finish_time'
            ];
        
            # fetching the results to create the ajax list
            die(json_encode(DataTable::init($data,'affiliate.suppressions',$columns,new Suppression(),'offers','DESC',null,false)));
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