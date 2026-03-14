<?php declare(strict_types=1); namespace IR\App\Controllers; if (!defined('IR_START')) exit('<pre>No direct script access allowed</pre>');
/**
 * @framework       iResponse Framework 
 * @version         1.0
 * @author          Amine Idrissi <contact@iresponse.tech>
 * @date            2024
 * @name            Dashboard.php	
 */

# core 
use IR\Core\Application as Application;

# mvc 
use IR\Mvc\Controller as Controller;

# helpers 
use IR\App\Helpers\Authentication as Authentication;
use IR\App\Helpers\Page as Page;
use IR\App\Helpers\Permissions as Permissions;

# exceptions
use IR\Exceptions\Types\PageException as PageException;

/**
 * @name Dashboard
 * @description Dashboard Controller
 */
class Dashboard extends Controller
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
        
        # gather first row info
        $servers = $this->app->database('system')->query()->from('admin.mta_servers')->where('status = ?','Activated')->count();
        $ips = $this->app->database('system')->query()->from('admin.servers_vmtas')->where('status = ? and type = ?',['Activated','Default'])->count();
        $affiliateNetworks = $this->app->database('system')->query()->from('affiliate.affiliate_networks')->where('status = ?','Activated')->count();
        $offers = $this->app->database('system')->query()->from('affiliate.offers')->where('status = ?','Activated')->count();
        $tests = $this->app->database('system')->query()->from('production.mta_processes')->where("process_type NOT LIKE 'drop' AND start_time BETWEEN ? AND current_date + interval '1' day ",date('Y-m-d'))->count()
               + $this->app->database('system')->query()->from('production.smtp_processes')->where("process_type NOT LIKE 'drop' AND start_time BETWEEN ? AND current_date + interval '1' day ",date('Y-m-d'))->count();
        $drops = $this->app->database('system')->query()->from('production.mta_processes')->where("process_type = 'drop' AND start_time BETWEEN ? AND current_date + interval '1' day ",date('Y-m-d'))->count()
               + $this->app->database('system')->query()->from('production.smtp_processes')->where("process_type = 'drop' AND start_time BETWEEN ? AND current_date + interval '1' day ",date('Y-m-d'))->count();
          
        # gather all information about affiliate networks stats
        $stats = $this->app->database('system')->query()
                ->from('actions.leads',['SUM(payout)' => 'earnings','COUNT(1)' => 'conversions'])
                ->where("action_time BETWEEN ? AND current_date + interval '1' day",[date('Y-m-01')])
                ->first();

        $earnings = doubleval($this->app->utils->arrays->get($stats,'earnings'));
        $conversions = intval($this->app->utils->arrays->get($stats,'conversions'));
        
        $clicks = $this->app->utils->arrays->get($this->app->database('system')->query()
                    ->from('actions.clicks',['COUNT(1)' => 'count'])
                    ->where("action_time BETWEEN ? AND current_date + interval '1' day",[date('Y-m-01')])
                    ->first(),'count');

        # gather all information about sending stats
        $stats = $this->app->database('system')->query()
                ->from('production.mta_processes',['SUM(total_emails)' => 'sum_total','SUM(delivered)' => 'sum_delivered','SUM(hard_bounced)' => 'sum_hard_bounced'])
                ->where("process_type = 'drop' AND start_time BETWEEN ? AND current_date + interval '1' day",[date('Y-m-d')])
                ->first(); 
                
        $sent = intval($this->app->utils->arrays->get($stats,'sum_total'));
        $delivered = intval($this->app->utils->arrays->get($stats,'sum_delivered'));
        $bounced = intval($this->app->utils->arrays->get($stats,'sum_hard_bounced'));

        $stats = $this->app->database('system')->query()
                ->from('production.smtp_processes',['SUM(total_emails)' => 'sum_total','SUM(delivered)' => 'sum_delivered','SUM(hard_bounced)' => 'sum_hard_bounced'])
                ->where("process_type = 'drop' AND start_time BETWEEN ? AND current_date + interval '1' day",[date('Y-m-d')])
                ->first(); 
                
        $sent += intval($this->app->utils->arrays->get($stats,'sum_total'));
        $delivered += intval($this->app->utils->arrays->get($stats,'sum_delivered'));
        $bounced += intval($this->app->utils->arrays->get($stats,'sum_hard_bounced'));
        
        # set menu status
        $this->masterView->set([
            'dashboard' => 'true'
        ]);
        
        # set data to the page view
        $this->pageView->set([
            'servers' => $servers,
            'ips' => $ips,
            'affiliateNetworks' => $affiliateNetworks,
            'offers' => $offers,
            'tests' => $tests,
            'drops' => $drops,
            'earnings' => number_format((double)$earnings, 2, '.', ''),
            'clicks' => $clicks,
            'conversions' => $conversions,
            'sent' => $sent,
            'delivered' => $delivered,
            'bounced' => $bounced
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


