<?php declare(strict_types=1); namespace IR\App\Controllers; if (!defined('IR_START')) exit('<pre>No direct script access allowed</pre>');
/**
 * @framework       iResponse Framework 
 * @version         1.0
 * @author          Amine Idrissi <contact@iresponse.tech>
 * @date            2024
 * @name            Statistics.php	
 */

# core 
use IR\Core\Application as Application;

# mvc 
use IR\Mvc\Controller as Controller;

# models
use IR\App\Models\Admin\Isp as Isp;
use IR\App\Models\Actions\Lead as Lead;

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
 * @name Statistics
 * @description Statistics Controller
 */
class Statistics extends Controller
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
     * @description initializing proccess before the action method executed
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
     * @name fullReport
     * @description the full report action
     * @before init
     * @after closeConnections,checkForMessage
     */
    public function fullReport() 
    { 
        # check for permissions
        $access = Permissions::checkForAuthorization($this->authenticatedUser,__CLASS__,__FUNCTION__);

        if($access == false)
        {
            throw new PageException('Access Denied !',403);
        }

        # set menu status
        $this->masterView->set([
            'statistics' => 'true',
            'full_report' => 'true'
        ]);
        
        # get date filter
        $range = $this->app->http->request->retrieve('date-range',Request::POST);
        $startDate = date('Y-m-d');
        $endDate = date('Y-m-d');
        $total = 2;
        
        if($range != '' && $this->app->utils->strings->indexOf($range,'-') > -1)
        {
            $parts = explode('-',$range);
            
            if(count($parts) == 2)
            {
                $startDate = date('Y-m-d',strtotime(trim($parts[0])));
                $endDate = date('Y-m-d',strtotime(trim($parts[1])));
            }
        }
        else
        {
            $range = date('F d, Y') . ' - ' . date('F d, Y');
        }   

        # get isps 
        $isps = Isp::all(Isp::FETCH_ARRAY,['status = ?','Activated'],['id','name'],'name','ASC');
        $cols = $this->app->http->request->retrieve('selected-columns',Request::POST);
        
        if(is_array($cols))
        {
            $cols = array_filter($cols);
        }
        
        if(count($cols) == 0)
        {
            $columnsArray[] = 'mailer';
        }
        else
        {
            $total = 1;
            
            foreach ($cols as $col) 
            {
                $columnsArray[] = $col;
                $total++;
            }
        }

        $columnsArray[] = 'total';
        
        $columnsSizes = [
            'total' => ' style="width:0.5%" '
        ];
        
        # creating the html part of the list 
        $index = 1;
        $columns = '';
        $filters = '';
        $footer = '';
        $footer .= '<th class="ft_0"></th>';
        
        foreach ($columnsArray as $column) 
        {
            $footer .= '<th class="ft_' . $index . '"></th>'; $index++;
            $size = key_exists($column,$columnsSizes) ? $columnsSizes[$column] : '';
            $columns .= '<th>' . ucwords(str_replace('_',' ',strtolower($column))) . '</th>' . PHP_EOL;

            if(strpos($column,'_date') > -1 || strpos($column,'_time') > -1)
            {
                $filters .= '<td ' . $size . '> <div id="' . $column . '_range" class="input-group date-range-picker"> <input type="text" class="form-control form-filter" name="' . $column . '_range"> <span class="input-group-btn"> <button class="btn default date-range-toggle" type="button"> <i class="fa fa-calendar"></i> </button> </span> </div> </td>' . PHP_EOL;
            }
            else if($column == 'status')
            {
                $filters .= '<td ' . $size . '> <select name="status" class="form-control form-filter input-sm"> <option value="" selected>All</option> <option value="Preparing">Preparing</option> <option value="In Progress">In Progress</option> <option value="Completed">Completed</option> <option value="Paused">Paused</option> <option value="Error">Error</option> <option value="Interrupted">Interrupted</option> </select> </td>' . PHP_EOL;
            }
            else if($column == 'isp')
            {
                $filters .= '<td ' . $size . '> <select name="isp" class="form-control form-filter input-sm"><option value="" selected>All</option>';

                foreach ($isps as $isp) 
                {
                    $filters .= "<option value='{$isp['name']}' selected>{$isp['name']}</option>";
                }

                $filters .= '</select> </td>' . PHP_EOL;
            }
            else if($column == 'offer')
            {
                $filters .= '<td ' . $size . '><input type="text" class="form-control form-filter" name="' . $column . '"></td>' . PHP_EOL;
            }
            else
            {
                $filters .= '<td ' . $size . '><input type="text" class="form-control form-filter" name="' . $column . '"></td>' . PHP_EOL;
            }
        }
        
        $footer .= '<th class="ft_' . $index . '"></th>';
        
        # set data to the page view
        $this->pageView->set([
            'columns' => $columns,
            'filters' => $filters,
            'footer' => $footer,
            'cols' => $columnsArray,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'range' => $range,
            'total' => $total
        ]);
    }
    
    /**
     * @name getFullReport
     * @description the get full report action
     * @before init
     * @after closeConnections,checkForMessage
     */
    public function getFullReport()
    { 
        # check for permissions
        $access = Permissions::checkForAuthorization($this->authenticatedUser,__CLASS__,'fullReport');

        if($access == false)
        {
            throw new PageException('Access Denied !',403);
        }
        
        # get post data
        $data = $this->app->http->request->retrieve(Request::ALL,Request::POST);

        if(count($data))
        {       
            $map = [
                'total' => "SUM(TRUNC(l.payout,2))",
                'mailer' => "COALESCE(l.user_full_name,'Unknown Mailer')",
                'isp' => "COALESCE(i.name,'Unknown ISP')",
                'process_id' => 'l.process_id',
                'server' => "COALESCE(srv.name,'Unknown Server')",
                'vmta' => "COALESCE(v.ip,'Unknown VMTA')",
                'affiliate_network' => "COALESCE(sp.name,'Unknown Affiliate Network')",
                'offer' => "COALESCE(off.name,'Unknown Offer')",
                'data_list' => "COALESCE(lst.name,'Unknown List')",
                'team' => "COALESCE(t.name,'Unknown Team')",
                'sent' => "COALESCE(SUM(drp.total_emails),'0')",
                'delivered' => "COALESCE(SUM(drp.delivered),'0')",
                'hard_bounced' => "COALESCE(SUM(drp.hard_bounced),'0')",
                'opens' => "COALESCE(SUM(drpips.opens),'0')",
                'clicks' => "COALESCE(SUM(drpips.clicks),'0')",
                'leads' => 'COUNT(l.id)',
                'unsubs' => "COALESCE(SUM(drpips.unsubs),'0')",
                'epc' => "CASE SUM(drpips.clicks) WHEN 0 THEN 0 ELSE COALESCE(TRUNC((SUM(TRUNC(l.payout,2)) / SUM(drpips.clicks)),2),'0') END"
            ];

            $cols = json_decode(base64_decode(urldecode($this->app->http->request->retrieve('cols',Request::GET))),true);
            $startDate = base64_decode(urldecode($this->app->http->request->retrieve('dts',Request::GET)));
            $endDate = base64_decode(urldecode($this->app->http->request->retrieve('dte',Request::GET)));
            $groups = [];
            
            # gather all columns from mapping
            $columns = [];
            
            foreach ($cols as $col) 
            {
                $key = $map[$col];
                
                if($key != null && !is_numeric($key) 
                    && $this->app->utils->strings->indexOf(strtoupper($key),'SUM(') == -1
                    && $this->app->utils->strings->indexOf(strtoupper($key),'COUNT(') == -1)
                {
                    $groups[] = $key;
                }
                
                $columns[$key] = $col;
            }

            # prepare query 
            $query = $this->app->database('system')->query()->from('actions.leads l',$columns)->join('production.mta_processes drp',"l.process_id = drp.id");

            if(in_array('server',$columns) || in_array('vmta',$columns))
            {
                $query->join('admin.servers_vmtas v','l.vmta_id = v.id')->join('admin.mta_servers srv','v.mta_server_id = srv.id');
            }
            
            if(in_array('isp',$columns))
            {
                $query->join('admin.isps i','drp.isp_id = i.id');
            }
            
            if(in_array('affiliate_network',$columns))
            {
                $query->join('affiliate.affiliate_networks sp','l.affiliate_network_id = sp.id');
            }
            
            if(in_array('offer',$columns))
            {
                $query->join('affiliate.offers off','l.affiliate_network_id = off.affiliate_network_id AND l.offer_production_id = off.production_id');
            }
            
            if(in_array('data_list',$columns))
            {
                $query->join('lists.data_lists lst','l.list_id = lst.id');
            }
            
            if(in_array('team',$columns))
            {
                $query->join('admin.users usr','usr.production_id = l.user_production_id')
                         ->join('production.teams_authorisations ath','ath.team_member_id = usr.id')
                         ->join('production.teams t','t.id = ath.team_id');
            }

            if(in_array('opens',$columns) || in_array('clicks',$columns) || in_array('epc',$columns) || in_array('unsubs',$columns))
            {
                $query = $query->join('(SELECT process_id,count(opens) as opens,count(clicks) as clicks,count(unsubs) as unsubs from production.mta_processes_ips drpips group by process_id) drpips','l.process_id = drpips.process_id');
            }
            
            $query->where("TO_DATE(to_char(l.action_time,'YYYY-MM-DD'),'YYYY-MM-DD') BETWEEN to_timestamp('$startDate', 'YYYY-MM-DD') AND to_timestamp('$endDate', 'YYYY-MM-DD')",'')->group($groups);      
            die(json_encode(DataTable::init($data,'actions.leads l',$columns,new Lead(),'statistics/revenu-report','DESC',$query,false,true)));
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