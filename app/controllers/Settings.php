<?php declare(strict_types=1); namespace IR\App\Controllers; if (!defined('IR_START')) exit('<pre>No direct script access allowed</pre>');
/**
 * @framework       iResponse Framework 
 * @version         1.0
 * @author          Amine Idrissi <contact@iresponse.tech>
 * @date            2024
 * @name            Settings.php	
 */

# core 
use IR\Core\Application as Application;

# mvc 
use IR\Mvc\Controller as Controller;

# http 
use IR\Http\Request as Request;

# helpers 
use IR\App\Helpers\Authentication as Authentication;
use IR\App\Helpers\Page as Page;
use IR\App\Helpers\Permissions as Permissions;

# exceptions
use IR\Exceptions\Types\PageException as PageException;

/**
 * @name Settings
 * @description Settings Controller
 */
class Settings extends Controller
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
        
        # get post data
        $data = $this->app->http->request->retrieve(Request::ALL,Request::POST);

        if(count($data))
        {
            $flag = 'error';
            $message = 'Could not update application settings !';
            
            # load app config files
            $settings = json_decode($this->app->utils->fileSystem->readFile(CONFIGS_PATH . DS . 'application.json'),true);
            
            $settings['application']['tracking_enc_key'] = trim(str_replace(["'",'"'],'_',$this->app->utils->arrays->get($data,'tracking-enc-key')));
            
            if(strlen($settings['application']['tracking_enc_key']) == 64)
            {
                # check for pmta firewall ips / domains
                $rows = array_filter(array_unique(explode(PHP_EOL,trim($this->app->utils->arrays->get($data,'pmta-firewall-ips-domains')))));
                $rows = array_map(function($value)
                {
                    $value = $this->app->utils->strings->trim($value);
                    if($this->app->utils->domains->isValidDomain($value) || filter_var($value,FILTER_VALIDATE_IP)) return $value; 
                    return null;
                },$rows);
                $rows = array_filter($rows);
                
                $settings['application']['name'] = $this->app->utils->arrays->get($data,'name');
                $settings['application']['company'] = $this->app->utils->arrays->get($data,'company');
                $settings['application']['version'] = $this->app->utils->arrays->get($data,'version');
                $settings['application']['upload_center_id'] = $this->app->utils->arrays->get($data,'upload-center-id');
                $settings['application']['webmail_server_id'] = $this->app->utils->arrays->get($data,'webmail-server-id');
                $settings['application']['pmta_config_type'] = $this->app->utils->arrays->get($data,'pmta-config-type');
                $settings['application']['pmta_http_port'] = $this->app->utils->arrays->get($data,'pmta-http-port');
                $settings['application']['pmta_firewall_ips_domains'] = implode(PHP_EOL,$rows);
                $settings['application']['new_tab_open'] = $this->app->utils->arrays->get($data,'new-tab-open');
                $settings['application']['optizmo_token'] = $this->app->utils->arrays->get($data,'optizmo-token');
                $settings['application']['bit_shortlinks_token'] = $this->app->utils->arrays->get($data,'bit-api-token');
                $settings['application']['sidebar_behaviour'] = $this->app->utils->arrays->get($data,'sidebar-behaviour');
                $settings['application']['gcloud_bucket_size'] = $this->app->utils->arrays->get($data,'gcloud-bucket-size');
                $settings['application']['gcloud_object_size'] = $this->app->utils->arrays->get($data,'gcloud-object-size');
                $settings['application']['ssl_email'] = $this->app->utils->arrays->get($data,'ssl-email');
                $settings['application']['suppression_timer'] = $this->app->utils->arrays->get($data,'suppression-timer');
                $settings['application']['base_url'] = $this->app->http->request->getBaseURL();

                # convert it to formatted json 
                $settings = json_encode($settings,JSON_PRETTY_PRINT,JSON_UNESCAPED_UNICODE);

                if($this->app->utils->fileSystem->writeFile(CONFIGS_PATH . DS . 'application.json',$settings))
                {
                    # save headers
                    $this->app->utils->fileSystem->writeFile(ASSETS_PATH . DS . 'templates' . DS . 'production' . DS . 'mta_header.tpl',$this->app->utils->arrays->get($data,'mta-header'));
                    $this->app->utils->fileSystem->writeFile(ASSETS_PATH . DS . 'templates' . DS . 'production' . DS . 'smtp_header.tpl',$this->app->utils->arrays->get($data,'smtp-header'));

                    # save gcloud cert
                    $this->app->utils->fileSystem->writeFile(CONFIGS_PATH . DS . 'gcloud.crd.json',$this->app->utils->arrays->get($data,'gcloud-cert'));

                    $flag = 'success';
                    $message = 'Application settings updated successfully !';
                }
            }
            else
            {
                $flag = 'error';
                $message = 'Tracking encryption key should be 64 chars length !';
            }
            
            # stores the message in the session 
            Page::registerMessage($flag, $message);

            # redirect to lists page
            Page::redirect();
        }
        
        # set menu status
        $this->masterView->set([
            'application' => 'true',
            'settings' => 'true'
        ]);
        
        # set data to the page view
        $this->pageView->set([
            'servers' => $this->app->database('system')->query()
                        ->from('admin.management_servers s',['s.id' => 'id','s.name' => 'name','p.name' => 'provider_name','s.main_ip' => 'main_ip'])
                        ->join('admin.servers_providers p','s.provider_id = p.id')
                        ->where('s.status = ?','Activated')
                        ->all(),
            'mtaHeader' => $this->app->utils->fileSystem->readFile(ASSETS_PATH . DS . 'templates' . DS . 'production' . DS . 'mta_header.tpl'),
            'smtpHeader' => $this->app->utils->fileSystem->readFile(ASSETS_PATH . DS . 'templates' . DS . 'production' . DS . 'smtp_header.tpl'),
            'gcloudCert' => $this->app->utils->fileSystem->readFile(CONFIGS_PATH . DS . 'gcloud.crd.json')
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