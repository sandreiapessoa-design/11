<?php declare(strict_types=1); namespace IR\App\Webservices; if (!defined('IR_START')) exit('<pre>No direct script access allowed</pre>');
/**
 * @framework       iResponse Framework 
 * @version         1.0
 * @author          Amine Idrissi <contact@iresponse.tech>
 * @date            2024
 * @name            Domains.php	
 */

# core 
use IR\Core\Base as Base;
use IR\Core\Application as Application;

# libraries 
use IR\App\Libraries\Library as Library;

# models
use IR\App\Models\Admin\Domain as Domain;

# helpers 
use IR\App\Helpers\Authentication as Authentication;
use IR\App\Helpers\Permissions as Permissions;
use IR\App\Helpers\Page as Page;
use IR\App\Helpers\Api as Api;

/**
 * @name Domains
 * @description Domains WebService
 */
class Domains extends Base
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
     * @name removeBrands
     * @description remove domains brands
     * @before init
     */
    public function removeBrands($parameters = []) 
    { 
        # check for authentication
        if(!Authentication::isUserAuthenticated())
        {
            Page::printApiResults(401,'Only logged-in access allowed !');
        }
        
        # check users roles 
        Authentication::checkUserRoles();
        
        # check for permissions
        $access = Permissions::checkForAuthorization(Authentication::getAuthenticatedUser(),'Domains','brands');

        if($access == false)
        {
            Page::printApiResults(403,'Access Denied !');
        }
        
        $domainsIds = $this->app->utils->arrays->get($parameters,'domains-ids',[]);
        
        if(!is_array($domainsIds) || count($domainsIds) == 0)
        {
            Page::printApiResults(500,'No domains found !');
        }
        
        # get connected user's username
        $username = Authentication::getAuthenticatedUser()->getEmail();
            
        foreach ($domainsIds as $domainsId)
        {
            $domain = new Domain(['id' => $domainsId]);
            $domain->load();
            
            if($domain->getValue() != null && $domain->getValue() != '')
            {
                $brandName = $this->app->utils->strings->trim(str_replace('.','_',$domain->getValue()));
                $brandPath = ASSETS_PATH . DS . 'tracking' . DS . 'brands' . DS . $brandName . '.zip';
                
                if($this->app->utils->fileSystem->fileExists($brandPath))
                {
                    $this->app->utils->fileSystem->deleteFile($brandPath);
                }
                
                $domain->setHasBrand('No');
                $domain->setLastUpdatedBy($username);
                $domain->setLastUpdatedDate(date('Y-m-d'));
                $domain->update();
            }
        }

        Page::printApiResults(200,'Brands has been removed successfully !');
    }
    
    /**
     * @name getAccountDomains
     * @description get account domains action
     * @before init
     */
    public function getAccountDomains($parameters = []) 
    { 
        # check for authentication
        if(!Authentication::isUserAuthenticated())
        {
            Page::printApiResults(401,'Only logged-in access allowed !');
        }
        
        # check users roles 
        Authentication::checkUserRoles();
        
        # check for permissions
        $access = Permissions::checkForAuthorization(Authentication::getAuthenticatedUser(),'Domains','domainsRecords')
                || Permissions::checkForAuthorization(Authentication::getAuthenticatedUser(),'Mailboxes','create');

        if($access == false)
        {
            Page::printApiResults(403,'Access Denied !');
        }
        
        $parts = explode('|',$this->app->utils->arrays->get($parameters,'account'));

        if(count($parts) != 2)
        {
            Page::printApiResults(500,'Incorrect account !');
        }
        
        $accountId = intval($parts[1]);
        $accountType = $parts[0];
        
        if($accountId > 0 || $accountType == 'none')
        {
            
            $domains = Domain::all(Domain::FETCH_ARRAY,['status != ? and account_id = ? and account_type = ?',['Inactivated',$accountId,$accountType]],['id','value']);

            if(count($domains) == 0)
            {
                Page::printApiResults(500,'Domains not found !');
            }

            Page::printApiResults(200,'',['domains' => $domains]);
        }
        else
        {
            Page::printApiResults(500,'Incorrect account id !');
        }
    }
    
    /**
     * @name getDomainRecords
     * @description get domain records action
     * @before init
     */
    public function getDomainRecords($parameters = []) 
    { 
        # check for authentication
        if(!Authentication::isUserAuthenticated())
        {
            Page::printApiResults(401,'Only logged-in access allowed !');
        }
        
        # check users roles 
        Authentication::checkUserRoles();
        
        # check for permissions
        $access = Permissions::checkForAuthorization(Authentication::getAuthenticatedUser(),'Domains','domainsRecords');

        if($access == false)
        {
            Page::printApiResults(403,'Access Denied !');
        }
        
        $domainId = intval($this->app->utils->arrays->get($parameters,'domain-id'));

        if($domainId > 0)
        {
            $domain = Domain::first(Domain::FETCH_ARRAY,['status != ? and id = ?',['Inactivated',$domainId]]);

            if(count($domain) == 0)
            {
                Page::printApiResults(500,'Domain not found !');
            }
            
            # get dns library
            $library = Library::get($domain['account_type'],['account_id' => intval($domain['account_id'])]);
            
            if($library == null)
            {
                Page::printApiResults(500,'DNS library not found !');
            }
            
            $results = $library->getDomainRecords($domain['value']);
            
            if(count($results) == 0)
            {
                Page::printApiResults(500,'Domain records not found !');
            }
            
            $records = [];
            
            foreach ($results as $row) 
            {
                $record = [];
                
                if($domain['account_type'] == 'namecheap')
                {
                    switch ($row['Type']) 
                    {
                        case 'A': $record['type'] = 'a'; break;
                        case 'AAAA': $record['type'] = 'aaaa'; break;
                        case 'CNAME': $record['type'] = 'cname'; break;
                        case 'NS': $record['type'] = 'ns'; break;
                        case 'TXT': $record['type'] = 'txt'; break;
                        case 'MX': $record['type'] = 'mx'; break;
                        default: $record['type'] = ''; break;
                    }
                    
                    if($record['type'] != '')
                    {
                        $record['host'] = $row['Name'];
                        $record['value'] = $row['Address'];
                        $record['ttl'] = $row['TTL'];
                        $records[] = $record;
                    }
                }
                elseif($domain['account_type'] == 'godaddy')
                {
                    switch ($row['type']) 
                    {
                        case 'A': $record['type'] = 'a'; break;
                        case 'AAAA': $record['type'] = 'aaaa'; break;
                        case 'CNAME': $record['type'] = 'cname'; break;
                        case 'NS': $record['type'] = 'ns'; break;
                        case 'TXT': $record['type'] = 'txt'; break;
                        case 'MX': $record['type'] = 'mx'; break;
                        default: $record['type'] = ''; break;
                    }
                    
                    if($record['type'] != '')
                    {
                        $record['host'] = $row['host'];
                        $record['value'] = $row['value'];
                        $record['ttl'] = $row['ttl'];
                        $records[] = $record;
                    }
                }
                elseif($domain['account_type'] == 'namecom')
                {
                    switch ($row['type']) 
                    {
                        case 'A': $record['type'] = 'a'; break;
                        case 'AAAA': $record['type'] = 'aaaa'; break;
                        case 'CNAME': $record['type'] = 'cname'; break;
                        case 'NS': $record['type'] = 'ns'; break;
                        case 'TXT': $record['type'] = 'txt'; break;
                        case 'MX': $record['type'] = 'mx'; break;
                        default: $record['type'] = ''; break;
                    }
                    
                    if($record['type'] != '')
                    {
                        $record['host'] = $row['host'];
                        $record['value'] = $row['answer'];
                        $record['ttl'] = $row['ttl'];
                        $records[] = $record;
                    }
                }
            }

            Page::printApiResults(200,'',['records' => $records]);
        }
        else
        {
            Page::printApiResults(500,'Incorrect domain id !');
        }
    }
    
    /**
     * @name setDomainRecords
     * @description set domain records action
     * @before init
     */
    public function setDomainRecords($parameters = []) 
    { 
        # check for authentication
        if(!Authentication::isUserAuthenticated())
        {
            Page::printApiResults(401,'Only logged-in access allowed !');
        }
        
        # check users roles 
        Authentication::checkUserRoles();
        
        # check for permissions
        $access = Permissions::checkForAuthorization(Authentication::getAuthenticatedUser(),'Domains','domainsRecords');

        if($access == false)
        {
            Page::printApiResults(403,'Access Denied !');
        }
        
        $domainId = intval($this->app->utils->arrays->get($parameters,'domain-id'));

        if($domainId > 0)
        {
            $domain = Domain::first(Domain::FETCH_ARRAY,['status != ? and id = ?',['Inactivated',$domainId]]);

            if(count($domain) == 0)
            {
                Page::printApiResults(500,'Domain not found !');
            }
            
            # get dns library
            $library = Library::get($domain['account_type'],['account_id' => intval($domain['account_id'])]);
            
            if($library == null)
            {
                Page::printApiResults(500,'DNS library not found !');
            }
            
            $mapping = json_decode(base64_decode($this->app->utils->arrays->get($parameters,'records')),true);
            $records = [];
            
            # add new records
            foreach ($mapping as $map)
            {
                $record['type'] = $map['type'];
                $record['host'] = $map['host'];
                $record['value'] = $map['value'];

                if($domain['account_type'] == 'godaddy' || $domain['account_type'] == 'namecom')
                {
                    $record['ttl'] = intval($map['ttl']) < 600 ? 600 : $map['ttl'];
                }
                else
                {
                    $record['ttl'] = $map['ttl'];
                }    

                if($map['type'] == 'mx')
                {
                    $record['priority'] = 10;
                }

                $records[] = $record;
            }
            
            # call iresponse api
            $result = Api::call('Tools','updateDomainsRecords',['domains-records' => [['domain-id' => $domain['id'] , 'records' => $records]]]);

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
        else
        {
            Page::printApiResults(500,'Incorrect domain id !');
        }
    }

    /**
     * @name setMultiDomainsRecords
     * @description set multi domains records action
     * @before init
     */
    public function setMultiDomainsRecords($parameters = []) 
    { 
        # check for authentication
        if(!Authentication::isUserAuthenticated())
        {
            Page::printApiResults(401,'Only logged-in access allowed !');
        }
        
        # check users roles 
        Authentication::checkUserRoles();
        
        # check for permissions
        $access = Permissions::checkForAuthorization(Authentication::getAuthenticatedUser(),'Domains','multiRecords');

        if($access == false)
        {
            Page::printApiResults(403,'Access Denied !');
        }
        
        $data = [];
        $domainIds = $this->app->utils->arrays->get($parameters,'domains-ids');
        $mapping = json_decode(base64_decode($this->app->utils->arrays->get($parameters,'records')),true);
        
        if(is_array($domainIds) && count($domainIds) > 0 && $mapping != null && is_array($mapping) && count($mapping))
        {
            foreach ($domainIds as $domainId)
            {
                $domain = Domain::first(Domain::FETCH_ARRAY,['status != ? and id = ?',['Inactivated',$domainId]]);

                if(count($domain) == 0)
                {
                    Page::printApiResults(500,'Domain not found !');
                }

                # get dns library
                $library = Library::get($domain['account_type'],['account_id' => intval($domain['account_id'])]);

                if($library == null)
                {
                    Page::printApiResults(500,'DNS library not found !');
                }

                $results = $library->getDomainRecords($domain['value']);
            
                if(count($results) == 0)
                {
                    Page::printApiResults(500,'Domain records not found !');
                }

                
                $records = [];

                foreach ($results as $row) 
                {
                    $record = [];

                    if($domain['account_type'] == 'namecheap')
                    {
                        switch ($row['Type']) 
                        {
                            case 'A': $record['type'] = 'a'; break;
                            case 'AAAA': $record['type'] = 'aaaa'; break;
                            case 'CNAME': $record['type'] = 'cname'; break;
                            case 'NS': $record['type'] = 'ns'; break;
                            case 'TXT': $record['type'] = 'txt'; break;
                            case 'MX': $record['type'] = 'mx'; break;
                            default: $record['type'] = ''; break;
                        }

                        if($record['type'] != '')
                        {
                            $record['host'] = $row['Name'];
                            $record['value'] = $row['Address'];
                            $record['ttl'] = $row['TTL'];
                            
                            if($record['type'] == 'mx')
                            {
                                $record['priority'] = key_exists('MXPref',$row) && intval($row['MXPref']) > 0 ? intval($row['MXPref']) : 10;
                            }
                            
                            $records[] = $record;
                        }
                    }
                    elseif($domain['account_type'] == 'godaddy')
                    {
                        switch ($row['type']) 
                        {
                            case 'A': $record['type'] = 'a'; break;
                            case 'AAAA': $record['type'] = 'aaaa'; break;
                            case 'CNAME': $record['type'] = 'cname'; break;
                            case 'NS': $record['type'] = 'ns'; break;
                            case 'TXT': $record['type'] = 'txt'; break;
                            case 'MX': $record['type'] = 'mx'; break;
                            default: $record['type'] = ''; break;
                        }

                        if($record['type'] != '')
                        {
                            $record['host'] = $row['host'];
                            $record['value'] = $row['value'];
                            $record['ttl'] = intval($row['ttl']) < 600 ? 600 : $row['ttl'];
                            
                            if($record['type'] == 'mx')
                            {
                                $record['priority'] = key_exists('priority',$row) && intval($row['priority']) > 0 ? intval($row['priority']) : 10;
                            }
                            
                            $records[] = $record;
                        }
                    }
                    elseif($domain['account_type'] == 'namecom')
                    {
                        switch ($row['type']) 
                        {
                            case 'A': $record['type'] = 'a'; break;
                            case 'AAAA': $record['type'] = 'aaaa'; break;
                            case 'CNAME': $record['type'] = 'cname'; break;
                            case 'NS': $record['type'] = 'ns'; break;
                            case 'TXT': $record['type'] = 'txt'; break;
                            case 'MX': $record['type'] = 'mx'; break;
                            default: $record['type'] = ''; break;
                        }

                        if($record['type'] != '')
                        {
                            $record['host'] = $row['host'];
                            $record['value'] = $row['answer'];
                            $record['ttl'] = intval($row['ttl']) < 600 ? 600 : $row['ttl'];
                            
                            if($record['type'] == 'mx')
                            {
                                $record['priority'] = key_exists('priority',$row) && intval($row['priority']) > 0 ? intval($row['priority']) : 10;
                            }
                            
                            $records[] = $record;
                        }
                    }
                }
                
                # add new records
                foreach ($mapping as $map)
                {
                    $record['type'] = $map['type'];
                    $record['host'] = $map['host'];
                    $record['value'] = $map['value'];
                    
                    if($domain['account_type'] == 'godaddy')
                    {
                        $record['ttl'] = intval($map['ttl']) < 600 ? 600 : $map['ttl'];
                    }
                    else
                    {
                        $record['ttl'] = $map['ttl'];
                    }    
                    
                    if($map['type'] == 'mx')
                    {
                        $record['priority'] = 10;
                    }

                    $records[] = $record;
                }
                
                $data[] = ['domain-id' => $domain['id'] , 'records' => $records];
            }
            
            # call iresponse api
            $result = Api::call('Tools','updateDomainsRecords',['domains-records' => $data]);

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
        else
        {
            Page::printApiResults(500,'Incorrect domains ids !');
        }
    }
}


