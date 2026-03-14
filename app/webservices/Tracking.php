<?php declare(strict_types=1); namespace IR\App\Webservices; if (!defined('IR_START')) exit('<pre>No direct script access allowed</pre>');
/**
 * @framework       iResponse Framework 
 * @version         1.0
 * @author          Amine Idrissi <contact@iresponse.tech>
 * @date            2024
 * @name            Tracking.php	
 */

# core 
use IR\Core\Base as Base;
use IR\Core\Application as Application;

# helpers 
use IR\App\Helpers\Authentication as Authentication;
use IR\App\Helpers\Page as Page;
use IR\App\Helpers\Api as Api;

# models
use IR\App\Models\Lists\DataList as DataList;
use IR\App\Models\Production\MtaProcess as MtaProcess;
use IR\App\Models\Production\SmtpProcess as SmtpProcess;
use IR\App\Models\Production\AutoResponder as AutoResponder;
use IR\App\Models\Affiliate\AffiliateNetwork as AffiliateNetwork;
use IR\App\Models\Affiliate\Offer as Offer;
use IR\App\Models\Affiliate\Creative as Creative;
use IR\App\Models\Affiliate\Link as Link;
use IR\App\Models\Admin\User as User;
use IR\App\Models\Lists\Email as Email;
use IR\App\Models\Actions\Open as Open;
use IR\App\Models\Actions\Click as Click;
use IR\App\Models\Lists\SuppressionEmail as SuppressionEmail;
use IR\App\Models\Actions\Unsubscribe as Unsubscribe;
use IR\App\Models\Actions\Optout as Optout;

# orm 
use IR\Orm\Table as Table;
use IR\Orm\Sequence as Sequence;

# http 
use IR\Http\Client as Client;

/**
 * @name Tracking
 * @description Tracking WebService
 */
class Tracking extends Base
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
        
        # get api authenticated user
        $this->authenticatedUser = new User([
            'id' => 0,
            'production_id' => 0,
            'master_access' => 'Enabled',
            'status' => 'Activated',
            'first_name' => 'iResponse',
            'last_name' => 'Tracking User',
            'email' => 'tracking@domain.com',
            'is_tracking_user' => true
        ]);
                
        # store api authenticated user
        Authentication::registerUser($this->authenticatedUser);
        
        # check users roles 
        Authentication::checkUserRoles();
    }
    
    /**
     * @name getLink
     * @description get offer link action
     * @before init
     */
    public function getLink($parameters = []) 
    { 
        $type = $this->app->utils->arrays->get($parameters,'type');
        $processId = intval($this->app->utils->arrays->get($parameters,'process-id'));
        $processType = $this->app->utils->arrays->get($parameters,'process-type');
        $userId = intval($this->app->utils->arrays->get($parameters,'user-id'));
        $listId = intval($this->app->utils->arrays->get($parameters,'list-id'));
        $clientId = intval($this->app->utils->arrays->get($parameters,'client-id'));
        $vmtaId = intval($this->app->utils->arrays->get($parameters,'vmta-id'));
        $offerId = intval($this->app->utils->arrays->get($parameters,'offer-id'));
        $actionId = 0;
        $ispId = 0;
        $affiliateNetworkId = 0;

        # check for blacklisted emails
        if($listId > 0 && $clientId > 0)
        {
            $this->app->database('clients')->connect();
            $dataList = DataList::first(DataList::FETCH_ARRAY,['id = ?',$listId]);     
            
            if(count($dataList))
            {
                $res = $this->app->database('clients')->execute("SELECT is_blacklisted FROM {$dataList['table_schema']}.{$dataList['table_name']} WHERE id = $clientId");
                
                if(count($res))
                {
                    foreach ($res as $row) 
                    {
                        if($row['is_blacklisted'] == true || $row['is_blacklisted'] == 't')
                        {
                            Page::printApiResultsThenLogout(405,'Bad request !');
                        }
                    }
                }
            }
        }
        
        if($processId > 0 || $offerId > 0)
        {
            if($processId > 0)
            {
                $process = $processType == 'mt' || $processType == 'md' 
                ? MtaProcess::first(MtaProcess::FETCH_ARRAY,['id = ?',$processId],['id','offer_id','isp_id','user_id']) 
                : SmtpProcess::first(SmtpProcess::FETCH_ARRAY,['id = ?',$processId],['id','offer_id','isp_id','user_id']);

                if(count($process) == 0 && $offerId == 0)
                {
                    Page::printApiResultsThenLogout(500,'No process found !');
                }
                else if(count($process))
                {
                    $offerId = $offerId == 0 ? intval($process['offer_id']) : $offerId;
                    $ispId = intval($process['isp_id']);
                    
                    if($offerId == 0)
                    {
                        Page::printApiResultsThenLogout(500,'Incorrect offer id !');
                    }
                }
            }
            
            $offer = Offer::first(Offer::FETCH_ARRAY,['id = ?',$offerId],['id','affiliate_network_id']);
            
            if(count($offer) == 0 || intval($offer['id']) == 0)
            {
                Page::printApiResultsThenLogout(500,'No offer found !');
            }

            $affiliateNetworkId = intval($offer['affiliate_network_id']);
            $creative = Creative::first(Creative::FETCH_ARRAY,['offer_id = ?',$offerId],['id'],'id','ASC');
            
            if(count($creative) == 0 || intval($creative['id']) == 0)
            {
                Page::printApiResultsThenLogout(500,'No creative found !');
            }
           
            $link = Link::first(Link::FETCH_ARRAY,['creative_id = ? AND type = ?',[intval($creative['id']),$type]],['type','value']);
            
            if(count($link) == 0 || !key_exists('value',$link) || $link['value'] == '')
            {
                Page::printApiResultsThenLogout(500,'No link found !');
            }
            
            $url = $link['value'];
            
            if($type == 'preview')
            {
                $affiliateNetwork = AffiliateNetwork::first(AffiliateNetwork::FETCH_ARRAY,['id = ?',$affiliateNetworkId],['id','api_type','sub_id_one','sub_id_two','sub_id_three']);
                
                if(count($affiliateNetwork) == 0)
                {
                    Page::printApiResultsThenLogout(500,'No affiliate network found !');
                }

                # generate a new click id 
                $actionId = intval(Sequence::getNextValue('system','clicks','actions'));
                $parameters = ['','',''];
                $count = 0;

                for ($index = 1; $index < 4; $index++) 
                {
                    $parameter = [];
                    $subs = [];

                    switch($index)
                    {
                        case 1 : 
                        {
                            $subs = $affiliateNetwork['sub_id_one'] != null && $affiliateNetwork['sub_id_one'] != '' ? explode('|',trim(strval($affiliateNetwork['sub_id_one']))) : [];
                            break;
                        }
                        case 2 : 
                        {
                            $subs = $affiliateNetwork['sub_id_two'] != null && $affiliateNetwork['sub_id_two'] != '' ? explode('|',trim(strval($affiliateNetwork['sub_id_two']))) : [];
                            break;
                        }
                        case 3 : 
                        {
                            $subs = $affiliateNetwork['sub_id_three'] != null && $affiliateNetwork['sub_id_three'] != '' ? explode('|',trim(strval($affiliateNetwork['sub_id_three']))) : [];
                            break;
                        }
                    }

                    if(count($subs))
                    {
                        foreach ($subs as $sub) 
                        {
                            switch($sub)
                            {
                                case 'mailer_id' : 
                                {
                                    $parameter[] = $userId;
                                    break;
                                }
                                case 'process_id' : 
                                {
                                    $parameter[] = $processId;
                                    break;
                                }
                                case 'isp_id' : 
                                {
                                    $parameter[] = $ispId;
                                    break;
                                }
                                case 'vmta_id' : 
                                {
                                    $parameter[] = $vmtaId;
                                    break;
                                }
                                case 'list_id' : 
                                {
                                    $parameter[] = $listId;
                                    break;
                                }
                                case 'email_id' : 
                                {
                                    $parameter[] = $clientId;
                                    break;
                                }
                            }
                        }
                        
                        if($index == 3)
                        {
                            $parameter[] = $actionId;
                            $parameter[] = $processType;
                        }
                    }

                    $parameters[$count] = implode('_',$parameter);
                    $count++;
                }
                
                $subKey = '';

                switch ($affiliateNetwork['api_type']) 
                {
                    case 'hasoffers': $subKey = 'aff_sub'; break;
                    case 'w4': $subKey = 'sid'; break;
                    case 'everflow': $subKey = 'sub'; break;
                    case 'hitpath': $subKey = RDS; break;
                    case 'pullstat': $subKey = RDS; break;
                    default : $subKey = 's'; break;
                }

                if($subKey != RDS)
                {
                    for ($index = 1; $index < 4; $index++) 
                    {
                        $url = str_replace(['?' . $subKey . '1=','&' . $subKey . '1='],'',$url);
                    }

                    $url .= strpos($url,'?') > -1 ? '&' : '?';
                    $url = trim(strval($url),strval($subKey)) . $subKey . '1=' . $parameters[0] . '&' . $subKey . '2=' . $parameters[1] . '&' . $subKey . '3=' . $parameters[2];
                }
                else
                { 
                    $url = trim(strval($url),strval($subKey)) . RDS . $parameters[0] . RDS . $parameters[1] . RDS . $parameters[2];
                }
            }
            
            Page::printApiResultsThenLogout(200,'Link generated successfully !',['link' => $url,'action_id' => $actionId]);
        }
        else
        {
            Page::printApiResultsThenLogout(500,'Incorrect drop or offer id !');
        }
    }
    
    /**
     * @name procceedTracking
     * @description proceed actions action
     * @before init
     */
    public function procceedTracking($parameters = []) 
    { 
        $actionId = intval($this->app->utils->arrays->get($parameters,'action-id'));
        $action = $this->app->utils->arrays->get($parameters,'action');
        $processId = intval($this->app->utils->arrays->get($parameters,'process-id'));
        $processType = $this->app->utils->arrays->get($parameters,'process-type');
        $userId = intval($this->app->utils->arrays->get($parameters,'user-id'));
        $offerId = intval($this->app->utils->arrays->get($parameters,'offer-id'));
        $vmtaId = intval($this->app->utils->arrays->get($parameters,'vmta-id'));
        $listId = intval($this->app->utils->arrays->get($parameters,'list-id'));
        $clientId = intval($this->app->utils->arrays->get($parameters,'client-id'));
        $agent = $this->app->utils->arrays->get($parameters,'agent');
        $ip = $this->app->utils->arrays->get($parameters,'ip');
        $language = $this->app->utils->arrays->get($parameters,'language');
        $ispId = 0;
        $autoRespondersIds = [];
        $process = [];
        
        if($processId > 0 || $offerId > 0)
        {
            if($processId > 0)
            {
                $process = $processType == 'mt' || $processType == 'md' 
                ? MtaProcess::first(MtaProcess::FETCH_ARRAY,['id = ?',$processId],['id','offer_id','isp_id','user_id','auto_responders_ids']) 
                : SmtpProcess::first(SmtpProcess::FETCH_ARRAY,['id = ?',$processId],['id','offer_id','isp_id','user_id','auto_responders_ids']);
                
                if(count($process) == 0 && $offerId == 0)
                {
                    Page::printApiResultsThenLogout(500,'No process found !');
                }
                else if(count($process))
                {
                    $offerId = $offerId == 0 ? intval($process['offer_id']) : $offerId;
                    $ispId = intval($process['isp_id']);
                    $autoRespondersIds = $process['auto_responders_ids'] != null ? explode(',',$process['auto_responders_ids']) : [];
                    
                    if($offerId == 0)
                    {
                        Page::printApiResultsThenLogout(500,'Incorrect offer id !');
                    }
                }
            }
            
            $offer = Offer::first(Offer::FETCH_ARRAY,['id = ?',$offerId],['id','production_id','affiliate_network_id']);
            
            if(count($offer) == 0 || intval($offer['id']) == 0)
            {
                Page::printApiResultsThenLogout(500,'No offer found !');
            }
            
            # get mailer 
            $user = User::first(User::FETCH_ARRAY,['production_id = ?',$userId],['first_name','last_name']);
            
            if(count($user) == 0)
            {
                Page::printApiResultsThenLogout(500,'No mailer found !');
            }
            
            # prepare client meta data 
            $metaInfo = $this->app->http->client->getMetaData($agent,$ip,$language);
            
            if(count($metaInfo) == 0)
            {
                $metaInfo['country-code'] = 'US';
                $metaInfo['country-name'] = ucwords(strtolower(Client::COUNTRIES[$metaInfo['country-code']]));
                $metaInfo['region-name'] = 'Unknown';
                $metaInfo['city-name'] = 'Unknown';
                $metaInfo['latitude'] = 'Unknown';
                $metaInfo['longitude'] = 'Unknown';
            }
            else
            {
                $metaInfo['country-code'] = key_exists('country-code',$metaInfo) ? $metaInfo['country-code'] : 'US';
                $metaInfo['country-name'] = key_exists('country-name',$metaInfo) ? $metaInfo['country-name'] : ucwords(strtolower(Client::COUNTRIES[$metaInfo['country-code']]));
                $metaInfo['region-name'] = key_exists('region-name',$metaInfo) ? $metaInfo['region-name'] : 'Unknown';
                $metaInfo['city-name'] = key_exists('city-name',$metaInfo) ? $metaInfo['city-name'] : 'Unknown';
                $metaInfo['latitude'] = key_exists('latitude',$metaInfo) ? $metaInfo['latitude'] : 'Unknown';
                $metaInfo['longitude'] = key_exists('longitude',$metaInfo) ? $metaInfo['longitude'] : 'Unknown';
            }
            
            # get affiliate network
            $affiliateNetwork = AffiliateNetwork::first(AffiliateNetwork::FETCH_ARRAY,['id = ?',intval($offer['affiliate_network_id'])],['id','api_type','sub_id_one','sub_id_two','sub_id_three']);
                
            if(count($affiliateNetwork) == 0)
            {
                Page::printApiResultsThenLogout(500,'No affiliate network found !');
            }

            $parameters = ['','',''];
            $count = 0;

            for ($index = 1; $index < 4; $index++) 
            {
                $parameter = [];
                $subs = [];

                switch($index)
                {
                    case 1 : 
                    {
                        $subs = $affiliateNetwork['sub_id_one'] != null && $affiliateNetwork['sub_id_one'] != '' ? explode('|',trim(strval($affiliateNetwork['sub_id_one']))) : [];
                        break;
                    }
                    case 2 : 
                    {
                        $subs = $affiliateNetwork['sub_id_two'] != null && $affiliateNetwork['sub_id_two'] != '' ? explode('|',trim(strval($affiliateNetwork['sub_id_two']))) : [];
                        break;
                    }
                    case 3 : 
                    {
                        $subs = $affiliateNetwork['sub_id_three'] != null && $affiliateNetwork['sub_id_three'] != '' ? explode('|',trim(strval($affiliateNetwork['sub_id_three']))) : [];
                        break;
                    }
                }

                if(count($subs))
                {
                    foreach ($subs as $sub) 
                    {
                        switch($sub)
                        {
                            case 'mailer_id' : 
                            {
                                $parameter[] = $userId;
                                break;
                            }
                            case 'process_id' : 
                            {
                                $parameter[] = $processId;
                                break;
                            }
                            case 'isp_id' : 
                            {
                                $parameter[] = $ispId;
                                break;
                            }
                            case 'vmta_id' : 
                            {
                                $parameter[] = $vmtaId;
                                break;
                            }
                            case 'list_id' : 
                            {
                                $parameter[] = $listId;
                                break;
                            }
                            case 'email_id' : 
                            {
                                $parameter[] = $clientId;
                                break;
                            }
                        }
                    }
                }

                $parameters[$count] = implode('_',$parameter);
                $count++;
            }

            # prepare the action
            $actionData = [
                'id' => $actionId,
                'unique_token' => str_replace('_','',implode('',$parameters)),
                'process_id' => $processId,
                'process_type' => $processType,
                'user_production_id' => $userId,
                'user_full_name' => $user['first_name'] . ' ' . $user['last_name'],
                'affiliate_network_id' => $offer['affiliate_network_id'],
                'offer_production_id' => $offer['production_id'],
                'list_id' => $listId,
                'client_id' => $clientId,
                'action_time' => date('Y-m-d H:i:s'),
                'agent' => $agent,
                'action_ip' => $ip,
                'country_code' => $this->app->utils->arrays->get($metaInfo, 'country-code'),
                'country' => $this->app->utils->arrays->get($metaInfo, 'country-name'),
                'region' => $this->app->utils->arrays->get($metaInfo, 'region-name'),
                'city' => $this->app->utils->arrays->get($metaInfo, 'city-name'),
                'device_type' => $this->app->utils->arrays->get($metaInfo, 'device-type'),
                'device_name' => $this->app->utils->arrays->get($metaInfo, 'device-name'),
                'operating_system' => $this->app->utils->arrays->get($metaInfo, 'os'),
                'browser_name' => $this->app->utils->arrays->get($metaInfo, 'browser-name'),
                'browser_version' => $this->app->utils->arrays->get($metaInfo, 'browser-version')
            ];
            
            # vmta / smtp user id part
            if($processType == 'mt' || $processType == 'md')
            {
                $actionData['vmta_id'] = $vmtaId;
            }
            else
            {
                $actionData['smtp_user_id'] = $vmtaId;
            }
 
            $actionObject = null;
            $actionColumn = '';

            switch ($action) 
            {
                case 'op': 
                {
                    $actionColumn = 'opens';
                    $count = $this->app->database('system')->query()->from('actions.opens')->where('process_id = ? AND list_id = ? AND client_id = ?',[$processId,$listId,$clientId])->count();

                    if($count == 0)
                    {
                        $actionObject = new Open($actionData);
                    }

                    break;
                }
                case 'cl':
                {
                    $actionColumn = 'clicks';
                    $count = $this->app->database('system')->query()->from('actions.clicks')->where('process_id = ? AND list_id = ? AND client_id = ?',[$processId,$listId,$clientId])->count();

                    if($count == 0)
                    {
                        $actionObject = new Click($actionData); 
                    }

                    break;
                }
                case 'oop': 
                {
                    $actionColumn = 'unsubs';
                    $count = $this->app->database('system')->query()->from('actions.optouts')->where('process_id = ? AND list_id = ? AND client_id = ?',[$processId,$listId,$clientId])->count();

                    if($count == 0)
                    {
                        $actionObject = new Optout($actionData);
                    }

                    break;
                }
                case 'un': 
                {
                    $actionColumn = 'unsubs';
                    $count = $this->app->database('system')->query()->from('actions.unsubscribes')->where('process_id = ? AND list_id = ? AND client_id = ?',[$processId,$listId,$clientId])->count();

                    if($count == 0)
                    {
                        $actionObject = new Unsubscribe($actionData);
                    }

                    break;
                }
            }
            
            # save action log
            if($actionObject != null)
            {
                $actionObject->insert();
                $column = '';
                $autoColumn = '';
                
                switch ($action) 
                {
                    case 'op': 
                    {
                        $column = 'opens';
                        $autoColumn = 'on_open';
                        break;
                    } 
                    case 'cl': 
                    {
                        $column = 'clicks';
                        $autoColumn = 'on_click';
                        break;
                    } 
                    case 'un': 
                    {
                        $column = 'unsubs';
                        $autoColumn = 'on_unsub';
                        break;
                    } 
                    case 'oop': 
                    {
                        $autoColumn = 'on_optout';
                    }
                }

                if($column != '')
                {
                    $table = $processType == 'mt' || $processType == 'md' ? 'mta_processes' : 'smtp_processes';
                    $this->app->database('system')->execute("UPDATE production.{$table} SET {$column} = {$column} + 1 WHERE id = {$processId}");
                }
                
                // check if there are any auto responders
                if(count($process) && count($autoRespondersIds) && $autoColumn != null && $autoColumn != '')
                {
                    $ids = [];
                    $autoResponders = AutoResponder::all(AutoResponder::FETCH_ARRAY,["{$autoColumn} = ?",'t'],['id']);
                    
                    if(count($autoResponders))
                    {
                        foreach ($autoResponders as $autoResponder)
                        {
                            $ids[] = intval($autoResponder['id']);
                        }
                    }

                    if(count($ids) > 0)
                    {
                        $data = [
                            'auto-responders-ids' => $autoRespondersIds,
                            'list-id' => $listId,
                            'client-id' => $clientId,
                            'original-process-id' => $process['id'],
                            'original-process-type' => $processType
                        ];

                        Api::call('AutoResponders','proceed',$data,true,'',$user['id']);
                    }
                }
            }
            
            # upda vmta / smtp user stats 
            if(count($process) && ($processType == 'md' || $processType == 'sd') && $vmtaId > 0 && $actionColumn != '')
            {
                $table = $processType == 'mt' || $processType == 'md' ? 'mta_processes_ips' : 'smtp_processes_users';
                $componentColumn = $processType == 'mt' || $processType == 'md' ? 'server_vmta_id' : 'smtp_user_id';
                $this->app->database('system')->execute("UPDATE production.{$table} SET {$actionColumn} = {$actionColumn} + 1 WHERE process_id = {$processId} AND {$componentColumn} = {$vmtaId}");
            }
            
            if($listId > 0 && $clientId > 0)
            {
                # get drop & client info
                $list = DataList::first(DataList::FETCH_ARRAY,['id = ?',$listId],['id','name','table_schema','table_name','isp_id']);

                if(count($list) && count($offer))
                {
                    # connect to lists database 
                    $this->app->database('clients')->connect();

                    # get email object
                    $email = new Email(['id' => $clientId]);
                    $email->setSchema(strtolower($list['table_schema']));
                    $email->setTable(strtolower($list['table_name']));
                    $email->load();

                    if($email->getEmail() == null && filter_var($email->getEmail(),FILTER_VALIDATE_EMAIL))
                    {
                        Page::printApiResultsThenLogout(500,'Email not found !');
                    }

                    if($email->getIsBlacklisted() == 't' || $email->getIsHardBounced() == 't')
                    {
                        Page::printApiResultsThenLogout(500,'Email is blacklisted or bounced !');
                    }
                    
                    if($email->getIsSeed() == 'true' || $email->getIsSeed() == 't' || $email->getIsSeed() == true)
                    {
                        Page::printApiResultsThenLogout(500,'Email is a seed !');
                    }
                    
                    # set verticals 
                    $oldVericals = (intval($email->getVerticals()) > 0) ? [intval($email->getVerticals())] : $email->getVerticals();

                    if(!is_array($oldVericals))
                    {
                        $oldVericals = $oldVericals == '' || $oldVericals == 'null' ? [] : array_filter(explode(',',$oldVericals));
                        $oldVericals = is_array($oldVericals) ? array_unique($oldVericals) : [];
                    }

                    $offerVericals = (array_key_exists('vertical_ids',$offer)) ? $offer['vertical_ids'] : '';
                    $offerVericals = (intval($offerVericals) > 0) ? [intval($offerVericals)] : $offerVericals;

                    if(!is_array($offerVericals))
                    {
                        $offerVericals = $offerVericals == '' || $offerVericals == 'null' ? [] : array_filter(explode(',',$offerVericals));
                        $offerVericals = is_array($offerVericals) ? array_unique($offerVericals) : [];
                    }

                    $verticals = [];

                    if(count($oldVericals) && count($offerVericals)) 
                    {
                        $verticals = array_unique(array_merge($oldVericals,$offerVericals));
                    }
                    else
                    {
                        $verticals = count($oldVericals) ? $oldVericals : $offerVericals;
                    }

                    $email->setVerticals(implode(',',$verticals));
                    $email->setLastActionType($action);
                    $email->setLastActionTime(date('Y-m-d H:i:s'));
                    $email->setAgent($agent);
                    $email->setIp($ip);
                    $email->setCountryCode(strtoupper(strval($metaInfo['country-code'])));

                    if(key_exists(strtoupper($metaInfo['country-code']), Client::COUNTRIES))
                    {
                        $email->setCountry(Client::COUNTRIES[strtoupper($metaInfo['country-code'])]);
                    }
                    else
                    {
                        $email->setCountry(Client::COUNTRIES['US']);
                    }

                    $email->setRegion($metaInfo['region-name']);
                    $email->setCity($metaInfo['city-name']);
                    $email->setLanguage($language);
                    $email->setDeviceType($metaInfo['device-type']);
                    $email->setDeviceName($metaInfo['device-name']);
                    $email->setOs($metaInfo['os']);
                    $email->setBrowserName($metaInfo['browser-name']);
                    $email->setBrowserVersion($metaInfo['browser-version']);

                    if($action == 'un')
                    {
                        $suppObject = new SuppressionEmail();
                        $suppObject->setTable("sup_list_{$affiliateNetwork['id']}_{$offer['production_id']}_{$listId}");
                           
                        if(Table::exists('clients',$suppObject->getTable(),$suppObject->getSchema()))
                        {
                            $suppObject->setEmailMd5($email->getEmailMd5());
                            $suppObject->insert(); 
                        }
                    }
                    
                    if($action == 'oop')
                    {
                        # set flag
                        $isFresh = 'f';
                        $isClean = 'f';
                        $isOpener = 'f';
                        $isClicker = 'f';
                        $isLeader = 'f';
                        $isUnsub = 'f';
                        $isOptOut = 't';
                    }
                    else
                    {
                        if($email->getIsLeader() != 'true' && $email->getIsLeader() != 't' && $email->getIsLeader() != true)
                        {
                            if($email->getIsClicker() != 'true' && $email->getIsClicker() != 't' && $email->getIsClicker() != true)
                            {
                                # set flag
                                $isFresh = 'f';
                                $isClean = 'f';
                                $isOpener = 'f';
                                $isClicker = 'f';
                                $isLeader = 'f';
                                $isUnsub = 'f';
                                $isOptOut = 'f';

                                switch ($action) 
                                {
                                    case 'op': $isOpener = 't'; break;
                                    case 'cl': $isClicker = 't'; break;
                                    case 'ld': $isLeader = 't'; break;
                                    case 'un': $isUnsub = 't'; break;
                                    default : $isClean = 't'; break;
                                }
                            }
                            else
                            {
                                # set flag
                                $isFresh = 'f';
                                $isClean = 'f';
                                $isOpener = 'f';
                                $isClicker = 't';
                                $isLeader = 'f';
                                $isUnsub = 'f';
                                $isOptOut = 'f';
                            }
                        }
                        else
                        {
                            # set flag
                            $isFresh = 'f';
                            $isClean = 'f';
                            $isOpener = 'f';
                            $isClicker = 'f';
                            $isLeader = 't';
                            $isUnsub = 'f';
                            $isOptOut = 'f';
                        }
                    }
 
                    $email->setIsFresh($isFresh);
                    $email->setIsClean($isClean);
                    $email->setIsOpener($isOpener);
                    $email->setIsClicker($isClicker);
                    $email->setIsLeader($isLeader);
                    $email->setIsUnsub($isUnsub);
                    $email->setIsOptout($isOptOut);

                    // update email record
                    $email->update();
                }
            }
            
            Page::printApiResultsThenLogout(200,'Operation completed !');
        }
        else
        {
            Page::printApiResultsThenLogout(500,'Incorrect parameters ids !');
        }
    }
    
    /**
     * @name checkEmail
     * @description check email if we have it action
     * @before init
     */
    public function checkEmail($parameters = []) 
    { 
        $email = strval($this->app->utils->arrays->get($parameters,'email'));
        $listId = intval($this->app->utils->arrays->get($parameters,'list-id'));
        $clientId = intval($this->app->utils->arrays->get($parameters,'client-id'));
        
        if(strlen($email) > 0 && $listId > 0 && $clientId > 0)
        {
            $list = DataList::first(DataList::FETCH_ARRAY,['id = ?',$listId],['id','table_schema','table_name']);
                                   
            if(count($list) == 0)
            {
                Page::printApiResultsThenLogout(500,'List not found !');
            }
            
            # connect to lists database 
            $this->app->database('clients')->connect();

            # get email object
            $client = new Email(['id' => $clientId]);
            $client->setSchema(strtolower($list['table_schema']));
            $client->setTable(strtolower($list['table_name']));
            $client->load();
            
            if($email != null && $client!= null && $client->getEmailMd5() != null && trim($client->getEmailMd5()) == trim($email))
            {
                Page::printApiResultsThenLogout(200,'Email is correct !');
            }
            else
            {
                Page::printApiResultsThenLogout(500,'Emails does not match !');
            }
        }
        else
        {
            Page::printApiResultsThenLogout(500,'Incorrect parameters !');
        }
    }
}