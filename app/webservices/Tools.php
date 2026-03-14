<?php declare(strict_types=1); namespace IR\App\Webservices; if (!defined('IR_START')) exit('<pre>No direct script access allowed</pre>');
/**
 * @framework       iResponse Framework 
 * @version         1.0
 * @author          Amine Idrissi <contact@iresponse.tech>
 * @date            2024
 * @name            Tools.php	
 */

# core 
use IR\Core\Base as Base;
use IR\Core\Application as Application;

# helpers 
use IR\App\Helpers\Authentication as Authentication;
use IR\App\Helpers\Permissions as Permissions;
use IR\App\Helpers\Page as Page;
use IR\App\Helpers\Api as Api;

/**
 * @name Tools
 * @description Tools WebService
 */
class Tools extends Base
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
     * @name forceDisconnectUsers
     * @description force disconnect users
     * @before init
     */
    public function forceDisconnectUsers($parameters = []) 
    { 
        # check for authentication
        if(!Authentication::isUserAuthenticated())
        {
            Page::printApiResults(401,'Only logged-in access allowed !');
        }
        
        # check users roles 
        Authentication::checkUserRoles();
        
        # check for permissions
        $access = Permissions::checkForAuthorization(Authentication::getAuthenticatedUser(),'Tools','forceDisconnectUsers');

        if($access == false)
        {
            Page::printApiResults(403,'Access Denied !');
        }
        
        $usersIds = $this->app->utils->arrays->get($parameters,'users-ids');
        
        if(count($usersIds) > 0)
        {
            $users = Authentication::getAllAuthenticatedUsers();
            
            foreach ($users as $key => $user) 
            {
                if(in_array($user->getId(),$usersIds))
                {
                    if($this->app->utils->fileSystem->fileExists(SESSIONS_PATH . DS . $key))
                    {
                        $this->app->utils->fileSystem->deleteFile(SESSIONS_PATH . DS . $key);
                    }
                }
            }
            
            Page::printApiResults(200,'Users are disconnected successfully !');
        }
        else
        {
            Page::printApiResults(500,'Incorrect users ids !');
        }
    }
    
    /**
     * @name mailboxExtractor
     * @description get mailbox extractor action
     * @before init
     */
    public function mailboxExtractor($parameters = []) 
    { 
        # check for authentication
        if(!Authentication::isUserAuthenticated())
        {
            Page::printApiResults(401,'Only logged-in access allowed !');
        }
        
        # check users roles 
        Authentication::checkUserRoles();
        
        # check for permissions
        $access = Permissions::checkForAuthorization(Authentication::getAuthenticatedUser(),'Tools','mailboxExtractor');

        if($access == false)
        {
            Page::printApiResults(403,'Access Denied !');
        }
        
        $mailboxes = array_filter(explode(PHP_EOL,$this->app->utils->arrays->get($parameters,'mailboxes','')));
        $maxEmails = intval($this->app->utils->arrays->get($parameters,'max-emails-number')); 
        $folder = $this->app->utils->arrays->get($parameters,'folder'); 
        $dateRange = $this->app->utils->arrays->get($parameters,'date-range'); 
        $emailsOrder = $this->app->utils->arrays->get($parameters,'emails-order','asc'); 
        $returnType = $this->app->utils->arrays->get($parameters,'return-type');   
        $returnHeaderKey = $this->app->utils->arrays->get($parameters,'return-header-key');
        $separator = $this->app->utils->arrays->get($parameters,'separator');
        $filterType = $this->app->utils->arrays->get($parameters,'filter-type');
        $filters = $this->app->utils->arrays->get($parameters,'filters'); 
        
        if(count($mailboxes) == 0)
        {
            Page::printApiResults(500,'No mailboxes found !');
        }
        
        foreach ($mailboxes as $mailbox)
        {
            $email = trim(strtolower($this->app->utils->arrays->first(explode(' ',trim($mailbox)))));
            $match = preg_match('/^(([^<>()[\]\\.,;:\s@"\']+(\.[^<>()[\]\\.,;:\s@"\']+)*)|("[^"\']+"))@((\[\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\])|(([a-zA-Z\d\-]+\.)+[a-zA-Z]{2,}))$/', $email);
            
            if($match === FALSE || $match == 0)
            {
                Page::printApiResults(500,$email . ' is not a valid email !');
            }
        }
        
        if($folder == null || $folder == '')
        {
            Page::printApiResults(500,'No folder specified !');
        }
        
        if($returnType == null || $returnType == '')
        {
            Page::printApiResults(500,'No return type specified !');
        }
        
        if($returnHeaderKey == null || $returnHeaderKey == '')
        {
            $returnHeaderKey = 'none';
        }
        
        if($dateRange != '' && $this->app->utils->strings->indexOf($dateRange,'-') > -1)
        {
            $parts = explode('-',$dateRange);
            
            if(count($parts) == 2)
            {
                $startDate = date('Y-m-d',strtotime(trim($parts[0])));
                $endDate = date('Y-m-d',strtotime(trim($parts[1])));
            }
        }
        else
        {
            $startDate = '';
            $endDate = '';
        }
        
        # check filters
        if(is_array($filters) && count($filters))
        {
            $tmp = [];
            
            foreach ($filters as $filter) 
            {
                if(count($filter) && array_key_exists('key',$filter))
                {
                    if($this->app->utils->arrays->get($filter,'key') != '' && $this->app->utils->arrays->get($filter,'value') != '')
                    {
                        $tmp[] = $filter;
                    }
                }
            }
            
            $filters = $tmp;
        }
        else
        {
            $filters = [];
        }
        
        # call iresponse api
        $data = [
            'mailboxes' => $mailboxes,
            'folder' => $folder,
            'max-emails' => $maxEmails,
            'order' => $emailsOrder,
            'start-date' => $startDate,
            'end-date' => $endDate,
            'filters' => $filters,
            'filter-type' => $filterType,
            'separator' => $separator,
            'return-type' => $returnType,
            'return-header-key' => $returnHeaderKey
        ];

        $result = Api::call('Tools','mailboxExtractor',$data);
        
        if(count($result) == 0)
        {
            Page::printApiResults(500,'No response found !');
        }

        if($result['httpStatus'] == 500)
        {
            Page::printApiResults(500,$result['message']);
        }

        if(!is_array($result['data']) || count($result['data']) == 0)
        {
            Page::printApiResults(500,'Error while trying to execute this command !');
        }

        Page::printApiResults(200,'',['results' => $result['data']]);
    }
}


