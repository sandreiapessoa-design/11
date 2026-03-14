<?php declare(strict_types=1); namespace IR\App\Webservices; if (!defined('IR_START')) exit('<pre>No direct script access allowed</pre>');
/**
 * @framework       iResponse Framework 
 * @version         1.0
 * @author          Amine Idrissi <contact@iresponse.tech>
 * @date            2024
 * @name            Dashboarphp	
 */

# core 
use IR\Core\Base as Base;
use IR\Core\Application as Application;

# helpers 
use IR\App\Helpers\Authentication as Authentication;
use IR\App\Helpers\Permissions as Permissions;
use IR\App\Helpers\Page as Page;

/**
 * @name Dashboard
 * @description Dashboard WebService
 */
class Dashboard extends Base
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
     * @name getSentStatsChart
     * @description get sent stats chart
     * @before init
     */
    public function getSentStatsChart($parameters = []) 
    { 
        # check for authentication
        if(!Authentication::isUserAuthenticated())
        {
            Page::printApiResults(401,'Only logged-in access allowed !');
        }
        
        # check users roles 
        Authentication::checkUserRoles();
        
        # check for permissions
        $access = Permissions::checkForAuthorization(Authentication::getAuthenticatedUser(),'Dashboard','main');

        if($access == false)
        {
            Page::printApiResults(403,'Access Denied !');
        }
        
        $days = $this->app->utils->arrays->get($parameters,'days');

        if(count($days) > 0 && count($days) <= 31)
        {
            $sent = [];
            
            # sent
            $result = $this->app->database('system')->query()
                      ->from('production.mta_processes',["date_part('day',start_time)" => 'day','SUM(total_emails)' => 'total'])
                      ->where('process_type = ?',['drop'])
                      ->where("date_part('month', start_time) = date_part('month', current_date)",[])
                      ->where("date_part('year', start_time) = date_part('year', current_date)",[])
                      ->group(['day'])
                      ->order('day')
                      ->all();

            foreach ($days as $day) 
            {
                $found = false;

                if($result != null && count($result))     
                {
                    foreach ($result as $row) 
                    {
                        if($row['day'] == $day)  
                        {
                            $sent[] = intval($row['total']);
                            $found = true;
                        }
                    }      
                }
                
                if($found == false)
                {
                    $sent[] = 0;
                }
            }
            
            # delivery
            $result = $this->app->database('system')->query()
                      ->from('production.mta_processes',["date_part('day',start_time)" => 'day','SUM(delivered)' => 'total'])
                      ->where('process_type = ?',['drop'])
                      ->where("date_part('month', start_time) = date_part('month', current_date)",[])
                      ->where("date_part('year', start_time) = date_part('year', current_date)",[])
                      ->group(['day'])
                      ->order('day')
                      ->all();
            
            $delivery = [];
            
            foreach ($days as $day) 
            {
                $found = false;

                if($result != null && count($result))     
                {
                    foreach ($result as $row) 
                    {
                        if($row['day'] == $day)  
                        {
                            $delivery[] = intval($row['total']);
                            $found = true;
                        }
                    }      
                }
                
                if($found == false)
                {
                    $delivery[] = 0;
                }
            }
            
            # bounced
            $result = $this->app->database('system')->query()
                      ->from('production.mta_processes',["date_part('day',start_time)" => 'day','SUM(hard_bounced)' => 'total'])
                      ->where('process_type = ?',['drop'])
                      ->where("date_part('month', start_time) = date_part('month', current_date)",[])
                      ->where("date_part('year', start_time) = date_part('year', current_date)",[])
                      ->group(['day'])
                      ->order('day')
                      ->all();
            
            $bounced = [];
            
            foreach ($days as $day) 
            {
                $found = false;

                if($result != null && count($result))     
                {
                    foreach ($result as $row) 
                    {
                        if($row['day'] == $day)  
                        {
                            $bounced[] = intval($row['total']);
                            $found = true;
                        }
                    } 
                }
                    
                if($found == false)
                {
                    $bounced[] = 0;
                }
            }

            Page::printApiResults(200,'',['sent' => $sent,'delivery' => $delivery,'bounced' => $bounced]);
        }
        else
        {
            Page::printApiResults(500,'Incorrect nomber of days !');
        }
    }
    
    /**
     * @name getActionsStatsChart
     * @description get actions stats chart
     * @before init
     */
    public function getActionsStatsChart($parameters = []) 
    { 
        # check for authentication
        if(!Authentication::isUserAuthenticated())
        {
            Page::printApiResults(401,'Only logged-in access allowed !');
        }
        
        # check users roles 
        Authentication::checkUserRoles();
        
        # check for permissions
        $access = Permissions::checkForAuthorization(Authentication::getAuthenticatedUser(),'Dashboard','main');

        if($access == false)
        {
            Page::printApiResults(403,'Access Denied !');
        }
        
        $days = $this->app->utils->arrays->get($parameters,'days');

        if(count($days) > 0 && count($days) <= 31)
        {
            # opens
            $result = $this->app->database('system')->query()
                    ->from('actions.opens',["date_part('day',action_time)" => 'day','COUNT(id)' => 'total'])
                    ->where("date_part('month', action_time) = date_part('month', current_date)",[])
                    ->where("date_part('year', action_time) = date_part('year', current_date)",[])
                    ->group(["date_part('day',action_time)","date_part('month',action_time)"])
                    ->order("date_part('day',action_time),date_part('month',action_time)")
                    ->all();
     
            $opens = [];
            
            foreach ($days as $day) 
            {
                $found = false;

                if($result != null && count($result))     
                {
                    foreach ($result as $row) 
                    {
                        if($row['day'] == $day)  
                        {
                            $opens[] = intval($row['total']);
                            $found = true;
                        }
                    }      
                }
                
                if($found == false)
                {
                    $opens[] = 0;
                }
            }
            
            # clicks
            $result = $this->app->database('system')->query()
                    ->from('actions.clicks',["date_part('day',action_time)" => 'day','COUNT(id)' => 'total'])
                    ->where("date_part('month', action_time) = date_part('month', current_date)",[])
                    ->where("date_part('year', action_time) = date_part('year', current_date)",[])
                    ->group(["date_part('day',action_time)","date_part('month',action_time)"])
                    ->order("date_part('day',action_time),date_part('month',action_time)")
                    ->all();
     
            $clicks = [];
            
            foreach ($days as $day) 
            {
                $found = false;

                if($result != null && count($result))     
                {
                    foreach ($result as $row) 
                    {
                        if($row['day'] == $day)  
                        {
                            $clicks[] = intval($row['total']);
                            $found = true;
                        }
                    }      
                }
                
                if($found == false)
                {
                    $clicks[] = 0;
                }
            }
            
            # leads
            $result = $this->app->database('system')->query()
                    ->from('actions.leads',["date_part('day',action_time)" => 'day','COUNT(id)' => 'total'])
                    ->where("date_part('month', action_time) = date_part('month', current_date)",[])
                    ->where("date_part('year', action_time) = date_part('year', current_date)",[])
                    ->group(["date_part('day',action_time)","date_part('month',action_time)"])
                    ->order("date_part('day',action_time),date_part('month',action_time)")
                    ->all();
            
            $leads = [];
            
            foreach ($days as $day) 
            {
                $found = false;

                if($result != null && count($result))     
                {
                    foreach ($result as $row) 
                    {
                        if($row['day'] == $day)  
                        {
                            $leads[] = intval($row['total']);
                            $found = true;
                        }
                    }      
                }
                
                if($found == false)
                {
                    $leads[] = 0;
                }
            }
            
            # leads
            $result = $this->app->database('system')->query()
                    ->from('actions.unsubscribes',["date_part('day',action_time)" => 'day','COUNT(id)' => 'total'])
                    ->where("date_part('month', action_time) = date_part('month', current_date)",[])
                    ->where("date_part('year', action_time) = date_part('year', current_date)",[])
                    ->group(["date_part('day',action_time)","date_part('month',action_time)"])
                    ->order("date_part('day',action_time),date_part('month',action_time)")
                    ->all();
            
            $unsubs = [];
            
            foreach ($days as $day) 
            {
                $found = false;

                if($result != null && count($result))     
                {
                    foreach ($result as $row) 
                    {
                        if($row['day'] == $day)  
                        {
                            $unsubs[] = intval($row['total']);
                            $found = true;
                        }
                    }      
                }
                
                if($found == false)
                {
                    $unsubs[] = 0;
                }
            }

            Page::printApiResults(200,'',['opens' => $opens ,'clicks' => $clicks ,'leads' => $leads,'unsubs' => $unsubs]);
        }
        else
        {
            Page::printApiResults(500,'Incorrect nomber of days !');
        }
    }
    
    /**
     * @name getMonthlyEarningsChart
     * @description get affiliate networks monthly earnings chart
     * @before init
     */
    public function getMonthlyEarningsChart($parameters = []) 
    { 
        # check for authentication
        if(!Authentication::isUserAuthenticated())
        {
            Page::printApiResults(401,'Only logged-in access allowed !');
        }
        
        # check users roles 
        Authentication::checkUserRoles();
        
        # check for permissions
        $access = Permissions::checkForAuthorization(Authentication::getAuthenticatedUser(),'Dashboard','main');

        if($access == false)
        {
            Page::printApiResults(403,'Access Denied !');
        }
        
        $months = $this->app->utils->arrays->get($parameters,'months');
        
        if(count($months) > 0 && count($months) <= 12)
        {
            # earnings
            $result = $this->app->database('system')->query()
                    ->from('actions.leads',["date_part('month',action_time)" => 'month','SUM(CAST(payout AS DECIMAL))' => 'total'])
                    ->where("date_part('year', action_time) = date_part('year', current_date)",[])
                    ->group(["date_part('month',action_time)","date_part('year',action_time)"])
                    ->order("date_part('month',action_time), date_part('year',action_time)")
                    ->all();
                    
            $earnings = [];
            
            foreach (array_keys($months) as $month) 
            {
                $found = false;

                if($result != null && count($result))     
                {
                    foreach ($result as $row) 
                    {
                        if($row['month'] == ($month+1))  
                        {
                            $earnings[] = doubleval($row['total']);
                            $found = true;
                        }
                    }      
                }
                
                if($found == false)
                {
                    $earnings[] = 0;
                }
            }

            Page::printApiResults(200,'',['earnings' => $earnings]);
        }
        else
        {
            Page::printApiResults(500,'Incorrect nomber of months !');
        }
    }
    
    /**
     * @name getDailyEarningsChart
     * @description get daily earnings chart
     * @before init
     */
    public function getDailyEarningsChart($parameters = []) 
    { 
        # check for authentication
        if(!Authentication::isUserAuthenticated())
        {
            Page::printApiResults(401,'Only logged-in access allowed !');
        }
        
        # check users roles 
        Authentication::checkUserRoles();
        
        # check for permissions
        $access = Permissions::checkForAuthorization(Authentication::getAuthenticatedUser(),'Dashboard','main');

        if($access == false)
        {
            Page::printApiResults(403,'Access Denied !');
        }
        
        $days = $this->app->utils->arrays->get($parameters,'days');
        
        if(count($days) > 0 && count($days) <= 31)
        {
            # clicks
            $result = $this->app->database('system')->query()
                    ->from('actions.leads',["date_part('day',action_time)" => 'day','SUM(CAST(payout AS DECIMAL))' => 'total'])
                    ->where("date_part('month', action_time) = date_part('month', current_date)",[])
                    ->where("date_part('year', action_time) = date_part('year', current_date)",[])
                    ->group(["date_part('day',action_time)","date_part('month',action_time)"])
                    ->order("date_part('day',action_time), date_part('month',action_time)")
                    ->all();
            
            $earnings = [];
            
            foreach ($days as $day) 
            {
                $found = false;

                if($result != null && count($result))     
                {
                    foreach ($result as $row) 
                    {
                        if($row['day'] == $day)  
                        {
                            $earnings[] = intval($row['total']);
                            $found = true;
                        }
                    }      
                }
                
                if($found == false)
                {
                    $earnings[] = 0;
                }
            }

            Page::printApiResults(200,'',['earnings' => $earnings]);
        }
        else
        {
            Page::printApiResults(500,'Incorrect nomber of months !');
        }
    }
}