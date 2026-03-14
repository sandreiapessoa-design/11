<?php declare(strict_types=1); namespace IR\App\Webservices; if (!defined('IR_START')) exit('<pre>No direct script access allowed</pre>');
/**
 * @framework       iResponse Framework 
 * @version         1.0
 * @author          Amine Idrissi <contact@iresponse.tech>
 * @date            2024
 * @name            Production.php	
 */

# core 
use IR\Core\Base as Base;
use IR\Core\Application as Application;

# helpers 
use IR\App\Helpers\Authentication as Authentication;
use IR\App\Helpers\Permissions as Permissions;
use IR\App\Helpers\AuditLog as AuditLog;
use IR\App\Helpers\Page as Page;
use IR\App\Helpers\Api as Api;

# models
use IR\App\Models\Admin\MtaServer as MtaServer;
use IR\App\Models\Admin\SmtpServer as SmtpServer;
use IR\App\Models\Admin\ServerVmta as ServerVmta;
use IR\App\Models\Admin\SmtpUser as SmtpUser;
use IR\App\Models\Admin\Isp as Isp;
use IR\App\Models\Affiliate\AffiliateNetwork as AffiliateNetwork;
use IR\App\Models\Affiliate\Offer as Offer;
use IR\App\Models\Affiliate\FromName as FromName;
use IR\App\Models\Affiliate\Subject as Subject;
use IR\App\Models\Affiliate\Creative as Creative;
use IR\App\Models\Affiliate\Link as Link;
use IR\App\Models\Lists\DataList as DataList;
use IR\App\Models\Production\MtaProcess as MtaProcess;
use IR\App\Models\Production\SmtpProcess as SmtpProcess;
use IR\App\Models\Production\AutoResponder as AutoResponder;

# orm 
use IR\Orm\Query as Query;

# http 
use IR\Http\Request as Request;

/**
 * @name Production
 * @description Production WebService
 */
class Production extends Base
{
    /**
     * @app
     * @readwrite
     */
    protected $app;
    
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
    }

    /**
     * @name getServers
     * @description get servers action
     * @before init
     */
    public function getServers($parameters = []) 
    { 
        # check for authentication
        if(!Authentication::isUserAuthenticated())
        {
            Page::printApiResults(401,'Only logged-in access allowed !');
        }
        
        # check users roles 
        Authentication::checkUserRoles();
        
        # check for permissions
        $access = Permissions::checkForAuthorization(Authentication::getAuthenticatedUser(),'Production','sendProcess');

        if($access == false)
        {
            Page::printApiResults(403,'Access Denied !');
        }
        
        $type = $this->app->utils->arrays->get($parameters,'type');
        $servers = $type == 'mta' ? MtaServer::all(MtaServer::FETCH_ARRAY,['status = ? AND is_installed = ?',['Activated','t']],['id','name','main_ip','provider_name'],'naturalsort(name)','ASC') 
                   : SmtpServer::all(SmtpServer::FETCH_ARRAY,['status = ?',['Activated']],['id','name'],'naturalsort(name)','ASC');
            
        if(count($servers) > 0)
        {
            Page::printApiResults(200,'',['servers' => $servers]);
        }
        else
        {
            Page::printApiResults(500,'Servers not found !');
        }
    }
    
    /**
     * @name getVmtas
     * @description get vmtas action
     * @before init
     */
    public function getVmtas($parameters = []) 
    { 
        # check for authentication
        if(!Authentication::isUserAuthenticated())
        {
            Page::printApiResults(401,'Only logged-in access allowed !');
        }
        
        # check users roles 
        Authentication::checkUserRoles();
        
        # check for permissions
        $access = Permissions::checkForAuthorization(Authentication::getAuthenticatedUser(),'Production','sendProcess');

        if($access == false)
        {
            Page::printApiResults(403,'Access Denied !');
        }
        
        $serverIds = $this->app->utils->arrays->get($parameters,'server-ids');
        $vmtasType = $this->app->utils->arrays->get($parameters,'vmtas-type');
        $ispId = intval($this->app->utils->arrays->get($parameters,'isp-id'));
        
        if(count($serverIds) > 0)
        {
            $vmtas = [];
            
            switch ($vmtasType)
            {
                case 'default-vmtas':
                {
                    $vmtas = ServerVmta::all(ServerVmta::FETCH_ARRAY,['status = ? and mta_server_id in ? AND type = ?',['Activated',$serverIds,'Default']],
                    ['id','mta_server_id','mta_server_name','type','ip','domain','custom_domain']);
                    break;
                }
                case 'smtp-vmtas':
                {
                    $vmtas = ServerVmta::all(ServerVmta::FETCH_ARRAY,['status = ? and mta_server_id in ? AND type = ?',['Activated',$serverIds,'SMTP']],
                    ['id','mta_server_id','mta_server_name','type','ip','domain','custom_domain']);
                    break;
                }
                case 'merged-vmtas':
                case 'custom-vmtas':
                {
                    if($ispId > 0)
                    {
                        $vmtas = ServerVmta::all(ServerVmta::FETCH_ARRAY,['status = ? and mta_server_id in ? AND type = ? AND isp_id = ?',['Activated',$serverIds,'Custom',$ispId]],
                        ['id','mta_server_id','mta_server_name','type','ip','domain','custom_domain']);
                    }
                    
                    break;
                }
                case 'all-vmtas':
                {
                    $vmtas = ServerVmta::all(ServerVmta::FETCH_ARRAY,['status = ? and mta_server_id in ?',['Activated',$serverIds]],
                    ['id','mta_server_id','mta_server_name','type','ip','domain','custom_domain']);
                    break;
                }
            }
            
            if(count($vmtas) == 0)
            {
                Page::printApiResults(500,'No vmtas found !');
            }
            
            Page::printApiResults(200,'',['vmtas' => $vmtas]);
        }
        else
        {
            Page::printApiResults(500,'Incorrect server id !');
        }
    }
    
    /**
     * @name getSmtpUsers
     * @description get smtp users action
     * @before init
     */
    public function getSmtpUsers($parameters = []) 
    { 
        # check for authentication
        if(!Authentication::isUserAuthenticated())
        {
            Page::printApiResults(401,'Only logged-in access allowed !');
        }
        
        # check users roles 
        Authentication::checkUserRoles();
        
        # check for permissions
        $access = Permissions::checkForAuthorization(Authentication::getAuthenticatedUser(),'Production','sendProcess');

        if($access == false)
        {
            Page::printApiResults(403,'Access Denied !');
        }
        
        $serverIds = $this->app->utils->arrays->get($parameters,'server-ids');
            
        if(count($serverIds) > 0)
        {
            $smtpUsers = SmtpUser::all(SmtpUser::FETCH_ARRAY,['smtp_server_id IN ? AND status = ?',[$serverIds,'Activated']]);
     
            if(count($smtpUsers) == 0)
            {
                Page::printApiResults(500,'No smtp users found !');
            }
            
            Page::printApiResults(200,'',['smtp-users' => $smtpUsers]);
        }
        else
        {
            Page::printApiResults(500,'Incorrect server id !');
        }
    }
    
    /**
     * @name getOffers
     * @description get offers action
     * @before init
     */
    public function getOffers($parameters = []) 
    { 
        # check for authentication
        if(!Authentication::isUserAuthenticated())
        {
            Page::printApiResults(401,'Only logged-in access allowed !');
        }
        
        # check users roles 
        Authentication::checkUserRoles();
        
        # check for permissions
        $access = Permissions::checkForAuthorization(Authentication::getAuthenticatedUser(),'Production','sendProcess');

        if($access == false)
        {
            Page::printApiResults(403,'Access Denied !');
        }
        
        $affiliateNetworkId = $this->app->utils->arrays->get($parameters,'affiliate-network-id');
            
        if($affiliateNetworkId > 0)
        {
            $todayName = strtolower(trim(date('D')));
            $offers = Offer::all(Offer::FETCH_ARRAY,["status = ? AND affiliate_network_id = ? AND available_days LIKE '%{$todayName}%'",['Activated',$affiliateNetworkId]],['id','name','production_id']);

            if(count($offers) == 0)
            {
                Page::printApiResults(500,'No offers found !');
            }

            Page::printApiResults(200,'',['offers' => $offers]);
        }
        else
        {
            Page::printApiResults(500,'Incorrect sponsor id !');
        }
    }
    
    /**
     * @name getOfferDetails
     * @description get offer details action
     * @before init
     */
    public function getOfferDetails($parameters = []) 
    { 
        # check for authentication
        if(!Authentication::isUserAuthenticated())
        {
            Page::printApiResults(401,'Only logged-in access allowed !');
        }
        
        # check users roles 
        Authentication::checkUserRoles();
        
        # check for permissions
        $access = Permissions::checkForAuthorization(Authentication::getAuthenticatedUser(),'Production','sendProcess');

        if($access == false)
        {
            Page::printApiResults(403,'Access Denied !');
        }
        
        $offerId = $this->app->utils->arrays->get($parameters,'offer-id');
            
        if($offerId > 0)
        {
            $fromNames = FromName::all(FromName::FETCH_ARRAY,['status = ? AND offer_id = ?',['Activated',$offerId]],['id','value']);
            $subjects = Subject::all(Subject::FETCH_ARRAY,['status = ? AND offer_id = ?',['Activated',$offerId]],['id','value']);
            $creatives = Creative::all(Creative::FETCH_ARRAY,['status = ? AND offer_id = ?',['Activated',$offerId]],['id','name']);

            Page::printApiResults(200,'',['from-names' => $fromNames, 'subjects' => $subjects , 'creatives' => $creatives]);
        }
        else
        {
            Page::printApiResults(500,'Incorrect offer id !');
        }
    }
    
    /**
     * @name generateLinks
     * @description generateLinks action
     * @before init
     */
    public function generateLinks($parameters = []) 
    { 
        # check for authentication
        if(!Authentication::isUserAuthenticated())
        {
            Page::printApiResults(401,'Only logged-in access allowed !');
        }
        
        # check users roles 
        Authentication::checkUserRoles();
        
        # check for permissions
        $access = Permissions::checkForAuthorization(Authentication::getAuthenticatedUser(),'Production','sendProcess');

        if($access == false)
        {
            Page::printApiResults(403,'Access Denied !');
        }

        $linkType = $this->app->utils->arrays->get($parameters,'link-type');
        
        if($linkType != '')
        {
            $data = [];
            $data['link-type'] = $linkType;
            $data['offer-id'] = intval($this->app->utils->arrays->get($parameters,'offer-id'));
            $data['static-domain'] = $this->app->utils->arrays->get($parameters,'static-domain');
            $data['send-type'] = $this->app->utils->arrays->get($parameters,'send-type');
            
            if($data['send-type'] == 'mta')
            {
                $data['vmta-id'] = $this->app->utils->arrays->get($parameters,'vmta');
            }
            else
            {
                $data['smtp-user-id'] = $this->app->utils->arrays->get($parameters,'vmta');
            }

            # call iresponse api
            $result = Api::call('Production','generateLinks',$data);

            if(count($result) == 0)
            {
                Page::printApiResults(500,'No response found !');
            }
            
            if($result['httpStatus'] == 500)
            {
                Page::printApiResults(500,$result['message']);
            }
            
            if(count($result['data']) == 0)
            {
                Page::printApiResults(500,'No links generated !');
            }
            
            $table = "<table class='table table-bordered table-striped table-condensed'>";
            $table .= "<thead><tr>";
            $table .= "<td>Type</td><td>Link</td>";
            $table .= "</tr></thead>";
            $table .= "<tbody>";
            $table .= "<tr>";
            $table .= "<td>Open link</td>";
            $table .= "<td>" . $result['data']['open-link'] . "</td>";
            $table .= "</tr>";
            $table .= "<tr>";
            $table .= "<td>Click link</td>";
            $table .= "<td>" . $result['data']['click-link'] . "</td>";
            $table .= "</tr>";
            $table .= "<tr>";
            $table .= "<td>Unsub link</td>";
            $table .= "<td>" . $result['data']['unsub-link'] . "</td>";
            $table .= "</tr>";
            $table .= "<tr>";
            $table .= "<td>Optout link</td>";
            $table .= "<td>" . $result['data']['optout-link'] . "</td>";
            $table .= "</tr>";
            $table .= "</tbody></table>";
            
            Page::printApiResults(200,'',['links' => $table]); 
        }
        else
        {
            Page::printApiResults(500,'Incorrect link encoding !');
        }
    }
    
    /**
     * @name getCreativeDetails
     * @description get creative details action
     * @before init
     */
    public function getCreativeDetails($parameters = []) 
    { 
        # check for authentication
        if(!Authentication::isUserAuthenticated())
        {
            Page::printApiResults(401,'Only logged-in access allowed !');
        }
        
        # check users roles 
        Authentication::checkUserRoles();
        
        # check for permissions
        $access = Permissions::checkForAuthorization(Authentication::getAuthenticatedUser(),'Production','sendProcess');

        if($access == false)
        {
            Page::printApiResults(403,'Access Denied !');
        }
        
        $creativeId = $this->app->utils->arrays->get($parameters,'creative-id');
            
        if($creativeId > 0)
        {
            $creative = Creative::first(Creative::FETCH_ARRAY,['id = ?',$creativeId],['value']);

            if(count($creative) == 0)
            {
                Page::printApiResults(500,'No creative found !');
            }

            $links = Link::all(Link::FETCH_ARRAY,['creative_id = ?',$creativeId]);
            $html = html_entity_decode(base64_decode($creative['value']));

            foreach ($links as $link) 
            {
                $tag = strtolower($link['type']) == 'preview' ? '[url]' : '[unsub]';      
                $html = str_replace($link['value'],'http://[domain]/' . $tag,$html);
            }

            $html .= PHP_EOL . '<br/><br/><span style="color:#888;font-size:11px;font-family:verdana;display:block;text-align:center;margin-top:10px">click <a href="http://[domain]/[optout]">here</a> to remove yourself from our emails list</span><br/><br/>'; 
            Page::printApiResults(200,'',['creative' => $html]);
        }
        else
        {
            Page::printApiResults(500,'Incorrect creative id !');
        }
    }
    
    /**
     * @name getEmailsLists
     * @description get emails lists action
     * @before init
     */
    public function getEmailsLists($parameters = []) 
    { 
        # check for authentication
        if(!Authentication::isUserAuthenticated())
        {
            Page::printApiResults(401,'Only logged-in access allowed !');
        }
        
        # check users roles 
        Authentication::checkUserRoles();
        
        # check for permissions
        $access = Permissions::checkForAuthorization(Authentication::getAuthenticatedUser(),'Production','sendProcess');

        if($access == false)
        {
            Page::printApiResults(403,'Access Denied !');
        }
        
        $data = [];
        $ispId = intval($this->app->utils->arrays->get($parameters,'isp-id'));
        $dataProvidersIds = $this->app->utils->arrays->get($parameters,'data-providers-ids',[]);

        if($ispId == 0)
        {
            Page::printApiResults(500,'Incorrect isp id !');
        }
        
        if(!is_array($dataProvidersIds) || count($dataProvidersIds) == 0)
        {
            Page::printApiResults(500,'Incorrect data providers ids !');
        }
        
        $lists = DataList::all(DataList::FETCH_ARRAY,['isp_id = ? AND data_provider_id IN ? AND status = ?',[$ispId,$dataProvidersIds,'Activated']],['id']);
        
        if(!is_array($lists) || count($lists) == 0)
        {
            Page::printApiResults(500,'No data lists found !');
        }
        
        foreach ($lists as $list) { $data['data-lists-ids'][] = $list['id']; }
        $data['offer-id'] = intval($this->app->utils->arrays->get($parameters,'offer-id'));
        $data['verticals-ids'] = $this->app->utils->arrays->get($parameters,'verticals',[]); 
        $data['countries'] = $this->app->utils->arrays->get($parameters,'countries',[]);
        $data['filters'] = $this->app->utils->arrays->get($parameters,'filters',[]);
        $data['verticals-ids'] = is_array($data['verticals-ids']) && count($data['verticals-ids']) ? "'" . implode("','",$data['verticals-ids']) . "'" : '';
        $data['countries'] = is_array($data['countries']) && count($data['countries']) ? "'" . implode("','",$data['countries']) . "'" : '';

        # call iresponse api
        $result = Api::call('Production','getEmailsLists',$data);

        if(count($result) == 0)
        {
            Page::printApiResults(500,'No response found !');
        }

        if($result['httpStatus'] == 500)
        {
            Page::printApiResults(500,$result['message']);
        }

        if(count($result['data']) == 0)
        {
            Page::printApiResults(500,'No links generated !');
        }
        
        Page::printApiResults(200,'',['lists' => $result['data']]);
    }
    
    /**
     * @name uploadNegative
     * @description uploadNegative action
     * @before init
     */
    public function uploadNegative() 
    { 
        # check for authentication
        if(!Authentication::isUserAuthenticated())
        {
            Page::printApiResults(401,'Only logged-in access allowed !');
        }
        
        # check users roles 
        Authentication::checkUserRoles();
        
        # check for permissions
        $access = Permissions::checkForAuthorization(Authentication::getAuthenticatedUser(),'Production','sendProcess');

        if($access == false)
        {
            Page::printApiResults(403,'Access Denied !');
        }

        # check for negative file 
        $files = $this->app->http->request->retrieve(Request::ALL,Request::FILES);

        if(count($files) && key_exists('negative-file',$files))
        {
            $file = $this->app->utils->arrays->get($files,'negative-file');

            if(intval($file['size']) > 0)
            {
                # start validations 
                if(intval($file['error']) > 0)
                {
                    switch (intval($file['error'])) 
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

                    Page::printApiResults(500,"Negative upload error : $message !");
                }

                if(!in_array($file['type'],['text/plain']) || $file['size'] == 0)
                {
                    Page::printApiResults(500,"Negative upload error : Unsupported file type !");
                }

                $negativeFile = $this->app->utils->strings->randomHex(8) . '.txt';
                $this->app->utils->fileSystem->copyFileOrDirectory($file['tmp_name'],STORAGE_PATH . DS . 'negatives' . DS . $negativeFile);
                Page::printApiResults(200,'Negtive file uploaded successfully !',['negative-file' => $negativeFile]);
            }
            else
            {
                Page::printApiResults(500,"Negative file is empty !");
            }
        }
        else
        {
            Page::printApiResults(500,"Could not upload negative !");
        }
    }
    
    /**
     * @name deleteNegative
     * @description deleteNegative action
     * @before init
     */
    public function deleteNegative($parameters = []) 
    { 
        # check for authentication
        if(!Authentication::isUserAuthenticated())
        {
            Page::printApiResults(401,'Only logged-in access allowed !');
        }
        
        # check users roles 
        Authentication::checkUserRoles();
        
        # check for permissions
        $access = Permissions::checkForAuthorization(Authentication::getAuthenticatedUser(),'Production','sendProcess');

        if($access == false)
        {
            Page::printApiResults(403,'Access Denied !');
        }
        
        $negativeFile = $this->app->utils->arrays->get($parameters,'negative-file','');
            
        if($negativeFile != '' && file_exists(STORAGE_PATH . DS . 'negatives' . DS . $negativeFile))
        {
            $this->app->utils->fileSystem->deleteFile(STORAGE_PATH . DS . 'negatives' . DS . $negativeFile);
            Page::printApiResults(200,'Negative file removed successfully !');
        }
        else
        {
            Page::printApiResults(500,'Negative file not found !');
        }
    }
    
    /**
     * @name getOfferDetails
     * @description get offer details action
     * @before init
     */
    public function checkForSuppression($parameters = []) 
    { 
        # check for authentication
        if(!Authentication::isUserAuthenticated())
        {
            Page::printApiResults(401,'Only logged-in access allowed !');
        }
        
        # check users roles 
        Authentication::checkUserRoles();
        
        # check for permissions
        $access = Permissions::checkForAuthorization(Authentication::getAuthenticatedUser(),'Production','sendProcess');

        if($access == false)
        {
            Page::printApiResults(403,'Access Denied !');
        }
        
        $offerId = $this->app->utils->arrays->get($parameters,'offer-id');
            
        if($offerId > 0) 
        {
            $valid = false;
            $offer = Offer::first(Offer::FETCH_ARRAY,['status = ? AND id = ?',['Activated',$offerId]],['id','last_suppression_updated_date']);
            $interval = intval($this->app->utils->arrays->get($this->app->getSetting('application'),'suppression_timer'));

            if(count($offer))
            {
                if($offer['last_suppression_updated_date'] != null && $offer['last_suppression_updated_date'] != '')
                {
                    $today = strtotime(date('Y-m-d H:i:s'));
                    $suppDate = strtotime($offer['last_suppression_updated_date']);
                    $diff = (int) (($today - $suppDate) / 3600 / 24);

                    if($diff < $interval)
                    {
                        $valid = true;
                    }
                }
            }
            
            Page::printApiResults(200,'',['valid' => $valid]);
        }
        else
        {
            Page::printApiResults(500,'Incorrect offer id !');
        }
    }
    
    /**
     * @name proceedSend
     * @description proceed send/test action
     * @before init
     */
    public function proceedSend($parameters = []) 
    { 
        # check for authentication
        if(!Authentication::isUserAuthenticated())
        {
            Page::printApiResults(401,'Only logged-in access allowed !');
        }
        
        # check users roles 
        Authentication::checkUserRoles();
        
        # check for permissions
        $access = Permissions::checkForAuthorization(Authentication::getAuthenticatedUser(),'Production','sendProcess');

        if($access == false)
        {
            Page::printApiResults(403,'Access Denied !');
        }
        
        $parameters = $this->app->http->request->retrieve(Request::ALL,Request::POST);
         
        if(count($parameters))
        {
            # drop 
            $json = json_encode($parameters);
            $type = strtolower(str_replace(' ','-',$this->app->utils->arrays->get($parameters,'type','test-all')));
            $smtpMtaSwitch = $this->app->utils->arrays->get($parameters,'smtp-mta-type','mta');

            # servers section
            $serversIds = [];
            $componentsIds = $this->app->utils->arrays->get($parameters,'selected-vmtas',[]);
            $staticDomain = $this->app->utils->arrays->get($parameters,'static-domain','[domain]');

            # auto responders
            $autoRespondersIds = $this->app->utils->arrays->get($parameters,'auto-responders-ids',[]);
            $autoRespondersIds = is_array($autoRespondersIds) && count($autoRespondersIds) > 0 ? implode(',',$autoRespondersIds) : '';
            
            # negative 
            $negativeFile = $this->app->utils->arrays->get($parameters,'negative-file','');
            
            # cpa section
            $affiliateNetworkId = intval($this->app->utils->arrays->get($parameters,'affiliate-network-id',0));
            $offerId = intval($this->app->utils->arrays->get($parameters,'offer-id',0));

            # test emails section
            $rcpts = array_filter(explode(PHP_EOL,$this->app->utils->arrays->get($parameters,'rcpts','')));

            # emails lists section
            $ispId = intval($this->app->utils->arrays->get($parameters,'isp-id'),0);
            $dataProviderIds = $this->app->utils->arrays->get($parameters,'data-providers-ids',[]);
            $listsIds = $this->app->utils->arrays->get($parameters,'lists',[]);
            $dataStart = intval($this->app->utils->arrays->get($parameters,'data-start',0));
            $dataCount = intval($this->app->utils->arrays->get($parameters,'data-count',0)); 
            $dataDuplicate = intval($this->app->utils->arrays->get($parameters,'data-duplicate',1)); 
            $dataDuplicate = $dataDuplicate == 0 ? 1 : $dataDuplicate;
            $dataActualCount = $type == 'drop' ? $dataCount * $dataDuplicate : count($rcpts); 
            $receipientsCount = 0;

            # begin validations 
            if($smtpMtaSwitch == 'smtp' && ($staticDomain == '' || $staticDomain == '[domain]'))
            {
                Page::printApiResults(500,'Static domain should not be empty nor [domain] !');
            }
            
            if(!is_array($componentsIds) || count($componentsIds) == 0)
            {
                Page::printApiResults(500,$smtpMtaSwitch == 'mta' ? 'No vmtas found !' : 'No smtp users found !');
            }
            
            $tmp = [];
            
            foreach ($componentsIds as $value)
            {
                $tmp[] = intval($this->app->utils->arrays->get(explode('|',$value),1));
            }
            
            $componentsIds = array_filter(array_unique($tmp));
            
            $components = $smtpMtaSwitch == 'mta' ? ServerVmta::all(ServerVmta::FETCH_ARRAY,['id IN ?',[$componentsIds]],['id','mta_server_id' => 'server_id']) 
                    : SmtpUser::all(SmtpUser::FETCH_ARRAY,['id IN ?',[$componentsIds]],['id','smtp_server_id' => 'server_id']);

            if(count($components) == 0)
            { 
                Page::printApiResults(500,$smtpMtaSwitch == 'mta' ? 'No vmtas found !' : 'No smtp users found !');
            }

            if(count($components) != count($componentsIds))
            {
                Page::printApiResults(500,$smtpMtaSwitch == 'mta' ? 'Some vmtas are no longer available for you !' : 'Some smtp servers are no longer available for you !');
            }
            
            # collect servers ids 
            foreach ($components as $component)
            {
                $serversIds[] = intval($component['server_id']);
            }
            
            $serversIds = array_unique($serversIds);

            if(count($serversIds) == 0)
            {
                Page::printApiResults(500,'No servers selected !');
            }
            
            $servers = $smtpMtaSwitch == 'mta' ? MtaServer::all(MtaServer::FETCH_ARRAY,['id IN ?',[$serversIds]],['id']) 
                                                    : SmtpServer::all(SmtpServer::FETCH_ARRAY,['id IN ?',[$serversIds]],['id']);
            if(count($servers) == 0)
            {
                Page::printApiResults(500,'No servers selected !');
            }

            if(count($servers) != count($serversIds))
            {
                Page::printApiResults(500,'Some mta servers are no longer available for you !');
            }

            # recipients validation
            if(count($rcpts))
            {
                $invalidEmails = false;

                foreach ($rcpts as $email) 
                {
                    $email = preg_replace( "/\r|\n/","", trim($email));

                    if(!empty($email) && !filter_var($email,FILTER_VALIDATE_EMAIL))
                    {
                        $invalidEmails = true;
                    }

                    if(filter_var($email, \FILTER_VALIDATE_EMAIL))
                    {
                        $receipientsCount++;
                    }
                }

                if($invalidEmails == true)
                {
                    Page::printApiResults(500,'Please check your recipients , it looks like there is some invalid emails !');
                }
            }

            if ($receipientsCount == 0)
            {
                Page::printApiResults(500,'Please insert at least one recipient!');
            }

            if($ispId == 0 || count(Isp::first(Isp::FETCH_ARRAY,['id = ?',$ispId],['id'])) == 0)
            {
                Page::printApiResults(500,'No isp selected !');
            }

            # check for empty placeholders 
            $placeholders = $this->app->utils->arrays->get($parameters,'placeholders');
            $size = count($placeholders);
            
            if($size > 0)
            {
                for ($index = 0; $index < $size; $index++)
                {
                    if($this->app->utils->strings->contains($json,'[placeholder' . ($index + 1)  . ']') 
                    && $this->app->utils->strings->trim(strval($placeholders[$index]) == ''))
                    {
                        Page::printApiResults(500,"Please check your placeholders "  . ($index + 1) . " it's empty !");
                    }
                }
            }

            # negative check 
            if($negativeFile != '' && !$this->app->utils->strings->contains($json,'[negative]'))
            {
                Page::printApiResults(500,"You have uploaded a negative file but you forgot its tag !");
            }
            
            # drop validations
            if('drop' == $type)
            {    
                if($this->app->utils->strings->contains($json,'[enc_b64_b]') || $this->app->utils->strings->contains($json,'[enc_hex_b]')
                || $this->app->utils->strings->contains($json,'[enc_qp_b]'))
                {
                    foreach (['[enc_b64_','[enc_qp_','[enc_hex_'] as $val)
                    {
                        $match = [];
                        preg_match_all('~\\' . $val . 'b\\]([^{]*)\\' . $val . 'e\\]~i',$json,$match);
                        
                        if(count($match) && count($match[1]))
                        {
                            foreach ($match[1] as $value)
                            {
                                if($this->app->utils->strings->contains($value,'[email]') || $this->app->utils->strings->contains($value,'[email_id]') ||
                                $this->app->utils->strings->contains($value,'[last_name]') || $this->app->utils->strings->contains($value,'[first_name]'))
                                {
                                    Page::printApiResults(500,'Encryption tags should not contains email sensitive tags like [email] , [first_name] ...etc. !');
                                }
                            }
                        }
                    }
                }

                if($dataCount == 0)
                {
                    Page::printApiResults(500,'Data count should be greater than 0 !');
                }

                if($affiliateNetworkId == 0 || count(AffiliateNetwork::first(AffiliateNetwork::FETCH_ARRAY,['id = ?',$affiliateNetworkId],['id'])) == 0)
                {
                    Page::printApiResults(500,'No affiliate network selected !');
                }

                if($offerId == 0 || count(Offer::first(Offer::FETCH_ARRAY,['id = ?',$offerId],['id'])) == 0)
                {
                    Page::printApiResults(500,'No offer selected !');
                }

                if(!is_array($dataProviderIds) || count($dataProviderIds) == 0)
                {
                    Page::printApiResults(500,'No data provider selected !');
                }

                if(!is_array($listsIds) || count($listsIds) == 0)
                {
                    Page::printApiResults(500,'No data lists selected !');
                }
                
                $lists = DataList::all(DataList::FETCH_ARRAY,['id IN ?',[$listsIds]],['id']);
                
                if(count($lists) == 0)
                {
                    Page::printApiResults(500,'No data lists selected !');
                }

                if(count($lists) != count($listsIds))
                {
                    Page::printApiResults(500,'Some data lists are no longer available for you !');
                }
            }
                        
            # save the process into the database 
            $process = $smtpMtaSwitch == 'mta' ? new MtaProcess() : new SmtpProcess();
            $process->setContent(base64_encode($json));
            $process->setServersIds($this->app->utils->arrays->implode($serversIds));
            $process->setProcessType($type);
            $process->setStatus('In Progress');
            $process->setStartTime(date('Y-m-d H:i:s'));
            $process->setUserId(Authentication::getAuthenticatedUser()->getId());
            $process->setTotalEmails($dataActualCount);
            $process->setProgress(0);
            $process->setAffiliateNetworkId($affiliateNetworkId);
            $process->setOfferId($offerId);
            $process->setIspId($ispId);
            
            # negative case
            if($negativeFile != '')
            {
                $process->setNegativeFilePath(STORAGE_PATH . DS . 'negatives' . DS . $negativeFile);
            }
            
            $process->setAutoRespondersIds($autoRespondersIds);

            if($type == 'drop')
            {
                $process->setDataStart($dataStart);
                $process->setDataCount($dataCount);
                $process->setLists($this->app->utils->arrays->implode($listsIds));
            }

            $processId = 0;
            
            try 
            {
                $processId = $process->insert();
            } 
            catch (Exception $e) 
            {
                $e = new SystemException($e->getMessage(),500,$e);
                $e->logError();
                
                Page::printApiResults(500,'Could not save process information !');
            }

            if($processId == 0)
            {
                Page::printApiResults(500,'Could not save process information !');
            }

            $controller = $smtpMtaSwitch == 'mta' ? 'MtaProcesses' : 'SmtpProcesses';
            $action = $type == 'drop' ? 'proceedDrop' : 'proceedTest';
            
            # register audit log
            AuditLog::registerLog($processId,$controller,'Production Process',ucfirst($action));
                
            # call iresponse api
            Api::call($controller,$action,['process-id' => $processId],true);
            
            Page::printApiResults(200,'Your process has been started !');
        }
        else
        {
            Page::printApiResults(500,'Parameters not found !');
        }
    }
    
    /**
     * @name getProcessServers
     * @description getProcessServers action
     * @before init
     */
    public function getProcessServers($parameters = []) 
    { 
        # check for authentication
        if(!Authentication::isUserAuthenticated())
        {
            Page::printApiResults(401,'Only logged-in access allowed !');
        }
        
        # check users roles 
        Authentication::checkUserRoles();
        
        # check for permissions
        $type = $this->app->utils->arrays->get($parameters,'type');
        
        $method = '';
        
        switch ($type) 
        {
            case 'mt' : $method = 'mtaTests'; break;
            case 'md' : $method = 'mtaDrops'; break; 
            case 'st' : $method = 'smtpTests'; break; 
            case 'sd' : $method = 'smtpDrops'; break; 
        }

        $access = $method != '' && Permissions::checkForAuthorization(Authentication::getAuthenticatedUser(),'Production',$method);

        if($access == false)
        {
            Page::printApiResults(403,'Access Denied !');
        }

        $processId = intval($this->app->utils->arrays->get($parameters,'id'));

        if($processId > 0)
        {
            if($type == 'mt' || $type == 'st')
            {
                $table = $type == 'md' || $type == 'mt' ? 'mta_processes' : 'smtp_processes';
                $serversTable = $type == 'md' || $type == 'mt' ? 'mta_servers' : 'smtp_servers';
                $columns = ['t.id' => 'id',"replace((SELECT string_agg(name, ',') FROM admin.{$serversTable} s WHERE s.id = ANY (string_to_array(t.servers_ids,',')::int[])),',',' ')" => 'mta_servers_names'];     
                $query = $this->app->database('system')->query()->from("production.{$table} t",$columns);
                $test = $query->where("t.id = ? AND t.process_type NOT LIKE 'drop'",[$processId])->order('t.id',Query::DESC)->first();        
                
                if(count($test) == 0)
                {
                    Page::printApiResults(500,'No process found !');
                }
                
                $results = "<table class='table table-bordered table-striped table-condensed'>";
                $results .= "<thead><tr>";
                $results .= "<td>Id</td><td>Servers</td>";
                $results .= "</tr></thead>";
                $results .= "<tbody>";
                $results .= "<tr>";
                $results .= "<td>{$test['id']}</td>";
                $results .= "<td>" . str_replace(' ','<br/>',$test['mta_servers_names']) . "</td>";
                $results .= "</tr>";
                $results .= "</tbody></table>";
            }
            else
            {
                $columns = ['i.sent_total','i.delivered','i.hard_bounced','i.soft_bounced'];
                
                $processComponents = $type == 'md' || $type == 'mt' ? $this->app->database('system')->query()->from('production.mta_processes_ips i',$columns)
                       ->join('admin.servers_vmtas v','v.id = i.server_vmta_id',['v.ip' => 'component','v.mta_server_name' => 'server'])
                       ->where('i.process_id = ?',$processId)
                       ->all()
                : $this->app->database('system')->query()->from('production.smtp_processes_users i',$columns)
                       ->join('admin.smtp_users u','u.id = i.smtp_user_id',['u.username' => 'component','u.smtp_server_name' => 'server'])
                       ->where('i.process_id = ?',$processId)
                       ->all();
     
                if(count($processComponents) == 0)
                {
                    Page::printApiResults(500,'No stats found for this drop !');
                }
                
                $stats = [];
                $componentLabel = $type == 'md' || $type == 'mt' ? 'Ip' : 'SMTP User';
                
                foreach ($processComponents as $processComponent)
                {
                    if($processComponent['server'] != '')
                    {
                        if(!key_exists($processComponent['server'],$stats))
                        {
                            $stats[$processComponent['server']] = [
                                'ips' => [],
                                'total' => 0,
                                'delivered' => 0,
                                'soft_bounced' => 0,
                                'hard_bounced' => 0
                            ];
                        }

                        $stats[$processComponent['server']]['ips'][] = [
                            'ip' => $processComponent['component'],
                            'total' => intval($processComponent['sent_total']),
                            'delivered' => intval($processComponent['delivered']),
                            'soft_bounced' => intval($processComponent['soft_bounced']),
                            'hard_bounced' => intval($processComponent['hard_bounced'])
                        ];

                        $stats[$processComponent['server']]['total'] = $stats[$processComponent['server']]['total'] + intval($processComponent['sent_total']);
                        $stats[$processComponent['server']]['delivered'] = $stats[$processComponent['server']]['delivered'] + intval($processComponent['delivered']);
                        $stats[$processComponent['server']]['soft_bounced'] = $stats[$processComponent['server']]['soft_bounced'] + intval($processComponent['soft_bounced']);
                        $stats[$processComponent['server']]['hard_bounced'] = $stats[$processComponent['server']]['hard_bounced'] + intval($processComponent['hard_bounced']);
                    }
                }
                
                if(count($stats) == 0)
                {
                    Page::printApiResults(500,'No stats found for this process !');
                }
                
                $results = '<div class="panel-group accordion scrollable" id="process-stats">';
                $index = 0;
                
                foreach ($stats as $server => $stat)
                { 
                    $results .= '<div class="panel panel-default">';
                    $results .= '<div class="panel-heading">';
                    $results .= '<h4 class="panel-title">';
                    $results .= '<a class="accordion-toggle" data-toggle="collapse" data-parent="#drop-stats" href="#stats-' . $server . '"> Server : ' . $server . ' </a>';
                    $results .= '</h4>';
                    $results .= '</div>';
                    $collapse = $index == 0 ? 'in' : 'collapse';
                    $results .= '<div id="stats-' . $server . '" class="panel-collapse ' . $collapse . '">';
                    $results .= '<div class="panel-body">';
                    $results .= "<table class='table table-bordered table-striped table-condensed'>";
                    $results .= "<thead><tr>";
                    $results .= "<td><b>{$componentLabel}</b></td><td><b>Total</b></td><td><b>Delivered</b></td><td><b>Soft Bounced</b></td><td><b>Hard Bounced</b></td>";
                    $results .= "</tr></thead>";
                    $results .= "<tbody>";
                    
                    foreach ($stat['ips'] as $ip)
                    {
                        $results .= "<tr>";
                        $results .= "<td>{$ip['ip']}</td>";
                        $results .= "<td>{$ip['total']}</td>";
                        $results .= "<td>{$ip['delivered']}</td>";
                        $results .= "<td>{$ip['soft_bounced']}</td>";
                        $results .= "<td>{$ip['hard_bounced']}</td>";
                        $results .= "</tr>";
                    }
                    
                    $results .= "<tr>";
                    $results .= "<td><b>Total</b></td>";
                    $results .= "<td><b>{$stat['total']}</b></td>";
                    $results .= "<td><b>{$stat['delivered']}</b></td>";
                    $results .= "<td><b>{$stat['soft_bounced']}</b></td>";
                    $results .= "<td><b>{$stat['hard_bounced']}</b></td>";
                    $results .= "</tr>";
                    
                    $results .= "</tbody></table>";
                    $results .= '</div>';
                    $results .= '</div>';
                    $results .= '</div>';
                    $index++;
                }
                
                $results .= '</div>';
            }

            Page::printApiResults(200,'',['servers' => $results]);
        }
        else
        {
            Page::printApiResults(500,'Incorrect process id !');
        }
    }
    
    /**
     * @name getProcessLists
     * @description getProcessLists action
     * @before init
     */
    public function getProcessLists($parameters = []) 
    { 
        # check for authentication
        if(!Authentication::isUserAuthenticated())
        {
            Page::printApiResults(401,'Only logged-in access allowed !');
        }
        
        # check users roles 
        Authentication::checkUserRoles();
        
        # check for permissions
        $type = $this->app->utils->arrays->get($parameters,'type');
        
        $method = '';
        
        switch ($type)
        {
            case 'md' : $method = 'mtaDrops'; break;  
            case 'sd' : $method = 'smtpDrops'; break; 
        }

        $access = $method != '' && Permissions::checkForAuthorization(Authentication::getAuthenticatedUser(),'Production',$method);

        if($access == false)
        {
            Page::printApiResults(403,'Access Denied !');
        }

        $id = intval($this->app->utils->arrays->get($parameters,'id'));
           
        if($id > 0)
        {
            $process = $type == 'md' || $type == 'mt' ? MtaProcess::first(MtaProcess::FETCH_ARRAY,['id = ?',$id],['id','lists','content'],'id','DESC')
                    : SmtpProcess::first(SmtpProcess::FETCH_ARRAY,['id = ?',$id],['id','lists','content'],'id','DESC');
            
            if(count($process) == 0)
            {
                Page::printApiResults(500,'No process found !');
            }
            
            $datalists = [];
            $res = DataList::all(DataList::FETCH_ARRAY,['status = ?','Activated'],['id','name']);
            
            foreach ($res as $row) 
            {
                $datalists[$row['id']] = $row['name'];
            }
            
            $table = "<table class='table table-bordered table-striped table-condensed'>";
            $table .= "<thead><tr>";
            $table .= "<td>Data Lists</td>";
            $table .= "</tr></thead>";
            $table .= "<tbody>";
            
            if(count($process))
            {
                $json = json_decode(base64_decode($process['content']),true);

                $filters = '(';
                $filters .= array_key_exists('fresh-filter',$json) && $this->app->utils->arrays->get($json,'fresh-filter') == 'on' ? ' Fresh ,' : '';
                $filters .= array_key_exists('clean-filter',$json) && $this->app->utils->arrays->get($json,'clean-filter') == 'on' ? ' Clean ,' : '';
                $filters .= array_key_exists('openers-filter',$json) && $this->app->utils->arrays->get($json,'openers-filter') == 'on' ? ' Openers ,' : '';
                $filters .= array_key_exists('clickers-filter',$json) && $this->app->utils->arrays->get($json,'clickers-filter') == 'on' ? ' Clickers ,' : '';
                $filters .= array_key_exists('leaders-filter',$json) && $this->app->utils->arrays->get($json,'leaders-filter') == 'on' ? ' Leaders ,' : '';
                $filters .= array_key_exists('unsubs-filter',$json) && $this->app->utils->arrays->get($json,'unsubs-filter') == 'on' ? ' Unsubscribers ,' : '';
                $filters .= array_key_exists('optouts-filter',$json) && $this->app->utils->arrays->get($json,'optouts-filter') == 'on' ? ' Optouts ,' : '';
                $filters .= array_key_exists('repliers-filter',$json) && $this->app->utils->arrays->get($json,'repliers-filter') == 'on' ? ' Repliers ,' : '';
                $filters = $filters == '(' ? '( All )' : rtrim($filters,' ,') . ' )';
                $table .= "<tr><td>";

                foreach (explode(',',$process['lists']) as $listId) 
                {
                    if(key_exists($listId,$datalists))
                    {
                        $table .= "{$datalists[$listId]} {$filters} <br/>";
                    }
                }

                $table = rtrim($table,'<br/>');
                $table .= "</td>";
                $table .= "</tr>";
            }
            
            $table .= "</tbody></table>";
            Page::printApiResults(200,'',['lists' => $table]);
        }
        else
        {
            Page::printApiResults(500,'Incorrect process id !');
        }
    }
    
    /**
     * @name executeProcessAction
     * @description executeProcessAction action
     * @before init
     */
    public function executeProcessAction($parameters = []) 
    {
        # check for authentication
        if(!Authentication::isUserAuthenticated())
        {
            Page::printApiResults(401,'Only logged-in access allowed !');
        }
        
        # check users roles 
        Authentication::checkUserRoles();
        
        # check for permissions
        $type = $this->app->utils->arrays->get($parameters,'type');
        
        $method = '';
        
        switch ($type)
        {
            case 'mt' : $method = 'mtaTests'; break;
            case 'md' : $method = 'mtaDrops'; break; 
            case 'st' : $method = 'smtpTests'; break; 
            case 'sd' : $method = 'smtpDrops'; break; 
        }

        $access = $method != '' && Permissions::checkForAuthorization(Authentication::getAuthenticatedUser(),'Production',$method);

        if($access == false)
        {
            Page::printApiResults(403,'Access Denied !');
        }

        $processesIds = $this->app->utils->arrays->get($parameters,'processes-ids',[]);
        
        if(!is_array($processesIds) || count($processesIds) == 0)
        {
            Page::printApiResults(500,'No processes found !');
        }
        
        # call iresponse api
        $result = Api::call('Production','executeProcessAction',$parameters);

        if(count($result) == 0)
        {
            Page::printApiResults(500,'No response found !');
        }

        if($result['httpStatus'] == 500)
        {
            Page::printApiResults(500,$result['message']);
        }
            
        Page::printApiResults(200,$result['message']);
    }
    
    /**
     * @name getProcess
     * @description getProcess action
     * @before init
     */
    public function getProcess($parameters = []) 
    {
        # check for authentication
        if(!Authentication::isUserAuthenticated())
        {
            Page::printApiResults(401,'Only logged-in access allowed !');
        }
        
        # check users roles 
        Authentication::checkUserRoles();
        
        # check for permissions
        $processType = $this->app->utils->arrays->get($parameters,'process-type');
        
        # check for permissions
        $method = '';
        
        switch ($processType)
        {
            case 'mt' : $method = 'mtaTests'; break;
            case 'md' : $method = 'mtaDrops'; break; 
            case 'st' : $method = 'smtpTests'; break; 
            case 'sd' : $method = 'smtpDrops'; break; 
        }

        $access = $method != '' && Permissions::checkForAuthorization(Authentication::getAuthenticatedUser(),'Production',$method);

        if($access == false)
        {
            Page::printApiResults(403,'Access Denied !');
        }

        $processId = intval($this->app->utils->arrays->get($parameters,'process-id'));
        $process = $processType == 'mt' || $processType == 'md' ? MtaProcess::first(MtaProcess::FETCH_ARRAY,['id = ?',$processId],['user_id','content']) 
                 : SmtpProcess::first(SmtpProcess::FETCH_ARRAY,['id = ?',$processId],['content']);
        
        if(count($process) == 0)
        {
            Page::printApiResults(500,'No process found !');
        }

        if(Authentication::getAuthenticatedUser()->getMasterAccess() != 'Enabled')
        {
            if(intval($process['user_id']) != intval(Authentication::getAuthenticatedUser()->getId()))
            { 
                Page::printApiResults(500,'No process found !');
            }
        } 
            
        # inject process type 
        $process = json_decode(base64_decode($process['content']),true);
        $process['process-type'] = $processType;
        
        # return process array
        Page::printApiResults(200,'',['process' => $process]);
    }
    
    /**
     * @name getAutoReponder
     * @description getAutoReponder action
     * @before init
     */
    public function getAutoReponder($parameters = []) 
    {
        # check for authentication
        if(!Authentication::isUserAuthenticated())
        {
            Page::printApiResults(401,'Only logged-in access allowed !');
        }
        
        # check users roles 
        Authentication::checkUserRoles();

        $access = Permissions::checkForAuthorization(Authentication::getAuthenticatedUser(),'AutoResponders','create');

        if($access == false)
        {
            Page::printApiResults(403,'Access Denied !');
        }

        $autoResponderId = intval($this->app->utils->arrays->get($parameters,'auto-responder-id'));
        $autoResponder = AutoResponder::first(AutoResponder::FETCH_ARRAY,['id = ?',$autoResponderId],['content']);
        
        if(count($autoResponder) == 0)
        {
            Page::printApiResults(500,'No auto responder found !');
        }

        # return process array
        Page::printApiResults(200,'',['auto-responder' => json_decode(base64_decode($autoResponder['content']),true)]);
    }
    
    /**
     * @name getMtaBounceLogs
     * @description getMtaBounceLogs action
     * @before init
     */
    public function getMtaBounceLogs($parameters = []) 
    {
        # check for authentication
        if(!Authentication::isUserAuthenticated())
        {
            Page::printApiResults(401,'Only logged-in access allowed !');
        }
        
        # check users roles 
        Authentication::checkUserRoles();
        
        # check for permissions
        $processType = $this->app->utils->arrays->get($parameters,'process-type');
        
        # check for permissions
        $method = '';
        
        switch ($processType)
        {
            case 'mt' : $method = 'MtaTests'; break; 
            case 'md' : $method = 'MtaDrops'; break; 
        }

        $access = $method != '' && Permissions::checkForAuthorization(Authentication::getAuthenticatedUser(),'Production',$method);

        if($access == false)
        {
            Page::printApiResults(403,'Access Denied !');
        }
        
        $processesIds = $this->app->utils->arrays->get($parameters,'processes-ids',[]);
        
        if(!is_array($processesIds) || count($processesIds) == 0)
        {
            Page::printApiResults(500,'No processes ids found !');
        }
        
        # call iresponse api
        $result = Api::call('Pmta','getBounceLogs',['processes-ids' => $processesIds,'processes-type' => $processType]);

        if(count($result) == 0)
        {
            Page::printApiResults(500,'No response found !');
        }

        if($result['httpStatus'] == 500)
        {
            Page::printApiResults(500,$result['message']);
        }
          
        $content = $this->app->utils->arrays->toCsv($result['data']);
        $random = $this->app->utils->strings->random(8,true,true,false,false);
        Page::printApiResults(200,'',['content' => $content,'name' => 'pmta_logs_' . $random . '.csv']);
    }
}