<?php declare(strict_types=1); namespace IR\App\Controllers; if (!defined('IR_START')) exit('<pre>No direct script access allowed</pre>');
/**
 * @framework       iResponse Framework 
 * @version         1.0
 * @author          Amine Idrissi <contact@iresponse.tech>
 * @date            2024
 * @name            Production.php	
 */

# core 
use IR\Core\Application as Application;

# mvc 
use IR\Mvc\Controller as Controller;

# models
use IR\App\Models\Admin\MtaServer as MtaServer;
use IR\App\Models\Admin\ServerVmta as ServerVmta;
use IR\App\Models\Admin\SmtpServer as SmtpServer;
use IR\App\Models\Admin\SmtpUser as SmtpUser;
use IR\App\Models\Admin\ManagementServer as ManagementServer;
use IR\App\Models\Affiliate\AffiliateNetwork as AffiliateNetwork;
use IR\App\Models\Affiliate\Offer as Offer;
use IR\App\Models\Affiliate\FromName as FromName;
use IR\App\Models\Affiliate\Subject as Subject;
use IR\App\Models\Production\Header as Header;
use IR\App\Models\Admin\Isp as Isp;
use IR\App\Models\Lists\DataProvider as DataProvider;
use IR\App\Models\Affiliate\Vertical as Vertical;
use IR\App\Models\Production\MtaProcess as MtaProcess;
use IR\App\Models\Production\SmtpProcess as SmtpProcess;
use IR\App\Models\Production\AutoResponder as AutoResponder;
use IR\App\Models\Production\Team as Team;
use IR\App\Models\Production\TeamAuthorisation as TeamAuthorisation;
use IR\App\Models\Admin\User as User;
use IR\App\Models\Lists\DataList as DataList;

# helpers 
use IR\App\Helpers\Api as Api;
use IR\App\Helpers\Page as Page;
use IR\App\Helpers\DataTable as DataTable;
use IR\App\Helpers\Permissions as Permissions;
use IR\App\Helpers\Authentication as Authentication;

# http 
use IR\Http\Request as Request;

# exceptions
use IR\Exceptions\Types\PageException as PageException;

/**
 * @name Production
 * @description Production Controller
 */
class Production extends Controller
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
     * @after closeConnections
     */
    public function main() 
    { 
        Page::redirect($this->app->http->request->getBaseURL() . RDS . 'production' . RDS . 'send-process' . RDS . DEFAULT_EXTENSION);
    }
    
    /**
     * @name sendProcess
     * @description the sendProcess action
     * @before init
     * @after closeConnections,checkForMessage
     */
    public function sendProcess() 
    { 
        # check for permissions 
        $access = Permissions::checkForAuthorization($this->authenticatedUser,__CLASS__,__FUNCTION__);

        if($access == false)
        {
            throw new PageException('Access Denied !',403);
        } 

        # set menu status
        $this->masterView->set([
            'production' => 'true',
            'drops_mta_send' => 'true'
        ]);
        
        $arguments = func_get_args(); 
        $processType = isset($arguments) && count($arguments) > 0 ? $arguments[0] : null;
        $processId = isset($arguments) && count($arguments) > 1 ? $arguments[1] : null;

        # set data to the page view
        $this->pageView->set([
            'servers' => MtaServer::all(MtaServer::FETCH_ARRAY,['status = ? AND is_installed = ?',['Activated','t']],['id','name','main_ip'],'naturalsort(name)','ASC'),
            'autoResponders' => AutoResponder::all(AutoResponder::FETCH_ARRAY,['status = ?',['Activated']],['id','name']),
            'affiliateNetworks' => AffiliateNetwork::all(AffiliateNetwork::FETCH_ARRAY,['status = ?','Activated'],['id','name']),
            'headers' => Header::all(Header::FETCH_ARRAY,['created_by = ?',$this->authenticatedUser->getEmail()],['id','name','header'],'naturalsort(name)','ASC'),
            'mtaHeader' => $this->app->utils->fileSystem->readFile(ASSETS_PATH . DS . 'templates' . DS . 'production' . DS . 'mta_header.tpl'),
            'smtpHeader' => $this->app->utils->fileSystem->readFile(ASSETS_PATH . DS . 'templates' . DS . 'production' . DS . 'smtp_header.tpl'),
            'isps' => Isp::all(Isp::FETCH_ARRAY,['status = ?','Activated'],['id','name'],'name','ASC'),
            'dataProviders' => DataProvider::all(DataProvider::FETCH_ARRAY,['status = ?','Activated'],['id','name']),
            'verticals' => Vertical::all(Vertical::FETCH_ARRAY,['status = ?','Activated'],['id','name']),
            'processId' => $processId,
            'processType' => $processType
        ]); 
    }
    
    /**
     * @name mtaDrops
     * @description the mta drops action
     * @before init
     * @after closeConnections,checkForMessage
     */
    public function mtaDrops() 
    { 
        # check for permissions
        $access = Permissions::checkForAuthorization($this->authenticatedUser,__CLASS__,__FUNCTION__);

        if($access == false)
        {
            throw new PageException('Access Denied !',403);
        }

        # set menu status
        $this->masterView->set([
            'production' => 'true',
            'drops_mta_drops' => 'true'
        ]);
        
        # get isps 
        $isps = Isp::all(Isp::FETCH_ARRAY,['status = ?','Activated'],['id','name'],'name','ASC');
        
        # preparing the columns array to create the list
        $columnsArray = [
            'id',
            'start_time',
            'mailer',
            'isp',
            'servers',
            'offer',
            'lists',
            'status',
            'start',
            'total',
            'progress',
            'delivered',
            'bounced',
            'opens',
            'clicks',
            'leads',
            'unsubs'
        ];
        
        $columnsSizes = [
            'start_time' => ' style="width:3%" ',
            'mailer' => ' style="width:7%" ',
            'servers' => ' style="width:9%" ',
            'isp' => ' style="width: 5.5%" ',
            'offer' => ' style="width:13%" ',
            'status' => ' style="width:3%" ',
            'start' => ' style="width:0.5%" ',
            'lists' => ' style="width:10%" ',
            'total' => ' style="width:0.5%" ',
            'progress' => ' style="width:0.5%" ',
            'delivered' => ' style="width:0.5%" ',
            'bounced' => ' style="width:0.5%" ',
            'opens' => ' style="width:0.5%" ',
            'clicks' => ' style="width:0.5%" ',
            'leads' => ' style="width:0.5%" ',
            'unsubs' => ' style="width:0.5%" '
        ];
        
        # creating the html part of the list 
        $index = 1;
        $columns = '';
        $filters = '';
        $footer = '<th class="ft_' . $index . '"></th>';
     
        foreach ($columnsArray as $column) 
        {
            $footer .= '<th class="ft_' . $index . '"></th>'; $index++;
            
            if($column != 'id')
            {
                $size = key_exists($column,$columnsSizes) ? $columnsSizes[$column] : '';
                
                $columns .= '<th>' . ucwords(str_replace('_',' ',strtolower($column))) . '</th>' . PHP_EOL;
                
                if(strpos($column,'_date') > -1 || strpos($column,'_time') > -1)
                {
                    $filters .= '<td ' . $size . '> <div id="' . $column . '_range" class="input-group date-range-picker"> <input type="text" class="form-control form-filter" name="' . $column . '_range"> <span class="input-group-btn"> <button class="btn default date-range-toggle" type="button"> <i class="fa fa-calendar"></i> </button> </span> </div> </td>' . PHP_EOL;
                }
                else if($column == 'status')
                {
                    $filters .= '<td ' . $size . '> <select name="status" class="form-control form-filter input-sm"> <option value="" selected>All</option> <option value="In Progress">In Progress</option> <option value="Completed">Completed</option> <option value="Paused">Paused</option> <option value="Error">Error</option> <option value="Interrupted">Interrupted</option> </select> </td>' . PHP_EOL;
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
        }
        
        $footer .= '<th class="ft_' . $index . '"></th>';
            
        # set data to the page view
        $this->pageView->set([
            'columns' => $columns,
            'filters' => $filters,
            'footer' => $footer
        ]);
    }
    
    /**
     * @name getMtaDrops
     * @description the get mta drops action
     * @before init
     * @after closeConnections,checkForMessage
     */
    public function getMtaDrops()
    { 
        # check for permissions
        $access = Permissions::checkForAuthorization($this->authenticatedUser,__CLASS__,'mtaDrops');

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
                'd.id' => 'id',
                "to_char(start_time, 'YYYY-MM-DD HH24:MI')" => 'start_time',
                "u.first_name || ' ' || u.last_name" => 'mailer',
                'i.name' => 'isp',
                "'<button style=\"margin-top:-7px\" class=\"btn btn-sm blue-madison show-process-servers\" data-type=\"md\" title=\"Show Process Servers\" data-id=\"' || d.id || '\"><i class=\"fa fa-globe\"></i></button>' || replace((SELECT string_agg(name, ',') FROM admin.mta_servers s WHERE s.id = ANY (string_to_array(d.servers_ids,',')::int[])),',',' ')" => 'servers',
                "'(' || off.production_id || ') ' || off.name" => 'offer',
                "'<button style=\"margin-top:-7px\" class=\"btn btn-sm green-dark show-process-lists\" data-type=\"md\" title=\"Show Process Lists\" data-id=\"' || d.id || '\"><i class=\"fa fa-at\"></i></button>' || replace((SELECT string_agg(name, ',') FROM lists.data_lists dt WHERE dt.id = ANY (string_to_array(d.lists,',')::int[])),',',' ') " => 'lists',
                'd.status' => 'status',
                'COALESCE(d.data_start,0)' => 'start',
                'COALESCE(d.total_emails,0)' => 'total',
                'COALESCE(d.progress,0)' => 'progress',
                "COALESCE((SELECT SUM(ips.delivered) FROM production.mta_processes_ips ips WHERE ips.process_id = d.id),0)" => 'delivered',
                "COALESCE((SELECT SUM(ips.hard_bounced) FROM production.mta_processes_ips ips WHERE ips.process_id = d.id),0)" => 'bounced', 
                "(SELECT COUNT(1) FROM actions.opens op WHERE op.process_id = d.id)" => 'opens',
                "(SELECT COUNT(1) FROM actions.clicks cl WHERE cl.process_id = d.id)" => 'clicks',
                "(SELECT COUNT(1) FROM actions.leads ld WHERE ld.process_id = d.id)" => 'leads',
                "(SELECT COUNT(1) FROM actions.unsubscribes un WHERE un.process_id = d.id)" => 'unsubs'
            ];
            
            # prepare query 
            $query = $this->app->database('system')->query()->from('production.mta_processes d',$columns)
                    ->join('admin.users u','d.user_id = u.id')
                    ->join('admin.isps i','d.isp_id = i.id')
                    ->join('affiliate.offers off','d.offer_id = off.id')
                    ->where('d.process_type = ?',['drop']);
            
            # fetching the results to create the ajax list
            if(Authentication::getAuthenticatedUser()->getMasterAccess() != 'Enabled')
            {
                $userTeams = [$this->authenticatedUser->getId()];
                $authorisations = TeamAuthorisation::all(TeamAuthorisation::FETCH_ARRAY,['team_member_id = ?',Authentication::getAuthenticatedUser()->getId()],['team_id']);

                foreach ($authorisations as $authorisation) 
                {
                    $userTeams[] = $authorisation['team_id'];
                }
                
                if(count($userTeams) != 0)
                {
                    # check if the user is a team leader 
                    $userIds = [Authentication::getAuthenticatedUser()->getId()];
                    $teams = Team::all(Team::FETCH_ARRAY,["status = ? and " . $userIds[0] . " = ANY (string_to_array(team_leaders_ids,',')::int[])",['Activated']],['id','name']);

                    if(count($teams))
                    {
                        foreach ($teams as $team) 
                        {
                            $members = TeamAuthorisation::all(TeamAuthorisation::FETCH_ARRAY,['team_member_id > 0 AND team_id = ?',$team['id']],['id','team_id','team_member_id']);

                            if(count($members))
                            {
                                foreach ($members as $member) 
                                {
                                    $userIds[] = intval($member['team_member_id']);
                                }
                            }
                        }
                    }
                    
                    $query->where('d.user_id in ?',[$userIds]);
                }
            }
            
            die(json_encode(DataTable::init($data,'production.mta_processes d',$columns,new MtaProcess(),'production' . RDS . 'mta-drops','DESC',$query,false)));
        }
    }
    
    /**
     * @name mtaTests
     * @description the mta tests action
     * @before init
     * @after closeConnections,checkForMessage
     */
    public function mtaTests() 
    { 
        # check for permissions
        $access = Permissions::checkForAuthorization($this->authenticatedUser,__CLASS__,__FUNCTION__);

        if($access == false)
        {
            throw new PageException('Access Denied !',403);
        }

        # set menu status
        $this->masterView->set([
            'production' => 'true',
            'drops_mta_tests' => 'true'
        ]);
        
        # get isps 
        $isps = Isp::all(Isp::FETCH_ARRAY,['status = ?','Activated'],['id','name'],'name','ASC');
        
        # preparing the columns array to create the list
        $columnsArray = [
            'id',
            'start_time',
            'mailer',
            'isp_name' => 'isp',
            'servers',
            'offer',
            'status',
            'total',
            'progress',
            'delivered',
            'bounced',
            'opens',
            'clicks',
            'leads',
            'unsubs'
        ];
        
        $columnsSizes = [
            'start_time' => ' style="width:2%" ',
            'mailer' => ' style="width:7%" ',
            'servers' => ' style="width:9%" ',
            'isp' => ' style="width: 5.5%" ',
            'offer' => ' style="width:10%" ',
            'status' => ' style="width:3%" ',
            'start' => ' style="width:0.5%" ',
            'total' => ' style="width:0.5%" ',
            'progress' => ' style="width:0.5%" ',
            'delivered' => ' style="width:0.5%" ',
            'bounced' => ' style="width:0.5%" ',
            'opens' => ' style="width:0.5%" ',
            'leads' => ' style="width:0.5%" ',
            'clicks' => ' style="width:0.5%" ',
            'unsubs' => ' style="width:0.5%" '
        ];
        
        # creating the html part of the list 
        $index = 1;
        $columns = '';
        $filters = '';
        $footer = '<th class="ft_' . $index . '"></th>';
     
        foreach ($columnsArray as $column) 
        {
            $footer .= '<th class="ft_' . $index . '"></th>'; $index++;
            
            if($column != 'id')
            {
                $size = key_exists($column,$columnsSizes) ? $columnsSizes[$column] : '';
                
                $columns .= '<th>' . ucwords(str_replace('_',' ',strtolower($column))) . '</th>' . PHP_EOL;
                
                if(strpos($column,'_date') > -1 || strpos($column,'_time') > -1)
                {
                    $filters .= '<td ' . $size . '> <div id="' . $column . '_range" class="input-group date-range-picker"> <input type="text" class="form-control form-filter" name="' . $column . '_range"> <span class="input-group-btn"> <button class="btn default date-range-toggle" type="button"> <i class="fa fa-calendar"></i> </button> </span> </div> </td>' . PHP_EOL;
                }
                else if($column == 'status')
                {
                    $filters .= '<td ' . $size . '> <select name="status" class="form-control form-filter input-sm"> <option value="" selected>All</option> <option value="In Progress">In Progress</option> <option value="Completed">Completed</option> <option value="Paused">Paused</option> <option value="Error">Error</option> <option value="Interrupted">Interrupted</option> </select> </td>' . PHP_EOL;
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
        }
        
        $footer .= '<th class="ft_' . $index . '"></th>';
            
        # set data to the page view
        $this->pageView->set([
            'columns' => $columns,
            'filters' => $filters,
            'footer' => $footer
        ]);
    }
    
    /**
     * @name getMtaTests
     * @description the get mta tests action
     * @before init
     * @after closeConnections,checkForMessage
     */
    public function getMtaTests()
    { 
        # check for permissions
        $access = Permissions::checkForAuthorization($this->authenticatedUser,__CLASS__,'mtaTests');

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
                't.id' => 'id',
                "to_char(start_time, 'YYYY-MM-DD HH24:MI')" => 'start_time',
                "u.first_name || ' ' || u.last_name" => 'mailer',
                'i.name' => 'isp',
                "'<button style=\"margin-top:-7px\" class=\"btn btn-sm blue-madison show-process-servers\" data-type=\"mt\" title=\"Show Process Servers\" data-id=\"' || t.id || '\"><i class=\"fa fa-globe\"></i></button>' || substring(replace((SELECT string_agg(name, ',') FROM admin.mta_servers s WHERE s.id = ANY (string_to_array(t.servers_ids,',')::int[])),',',' '),1,18)" => 'servers',
                "'(' || off.production_id || ') ' || off.name" => 'offer',
                't.status' => 'status',
                'COALESCE(t.total_emails,0)' => 'total',
                'COALESCE(t.progress,0)' => 'progress',
                'COALESCE(t.delivered,0)' => 'delivered',
                'COALESCE(t.hard_bounced,0)' => 'bounced',
                "(SELECT COUNT(1) FROM actions.opens op WHERE op.process_id = t.id)" => 'opens',
                "(SELECT COUNT(1) FROM actions.clicks cl WHERE cl.process_id = t.id)" => 'clicks',
                "(SELECT COUNT(1) FROM actions.leads ld WHERE ld.process_id = t.id)" => 'leads',
                "(SELECT COUNT(1) FROM actions.unsubscribes un WHERE un.process_id = t.id)" => 'unsubs'
            ];
            

            # prepare query 
            $query = $this->app->database('system')->query()->from('production.mta_processes t',$columns)
                    ->join('admin.users u','t.user_id = u.id')
                    ->join('admin.isps i','t.isp_id = i.id')
                    ->join('affiliate.offers off','t.offer_id = off.id')
                    ->where("t.process_type NOT LIKE 'drop'",[]);
     
            # fetching the results to create the ajax list
            if(Authentication::getAuthenticatedUser()->getMasterAccess() != 'Enabled')
            {
                $userTeams = [$this->authenticatedUser->getId()];
                $authorisations = TeamAuthorisation::all(TeamAuthorisation::FETCH_ARRAY,['team_member_id = ?',Authentication::getAuthenticatedUser()->getId()],['team_id']);

                foreach ($authorisations as $authorisation) 
                {
                    $userTeams[] = $authorisation['team_id'];
                }
                
                if(count($userTeams) != 0)
                {
                    # check if the user is a team leader 
                    $userIds = [Authentication::getAuthenticatedUser()->getId()];
                    $teams = Team::all(Team::FETCH_ARRAY,["status = ? and " . $userIds[0] . " = ANY (string_to_array(team_leaders_ids,',')::int[])",['Activated']],['id','name']);


                    if(count($teams))
                    {
                        foreach ($teams as $team) 
                        {
                            $members = TeamAuthorisation::all(TeamAuthorisation::FETCH_ARRAY,['team_member_id > 0 AND team_id = ?',$team['id']],['id','team_id','team_member_id']);

                            if(count($members))
                            {
                                foreach ($members as $member) 
                                {
                                    $userIds[] = intval($member['team_member_id']);
                                }
                            }
                        }
                    }

                    $query->where('t.user_id in ?',[$userIds]);
                }
            }
            
            die(json_encode(DataTable::init($data,'production.mta_processes t',$columns,new MtaProcess(),'production' . RDS . 'mta-tests','DESC',$query,false)));
        }
    }

    /**
     * @name smtpDrops
     * @description the smtp drops action
     * @before init
     * @after closeConnections,checkForMessage
     */
    public function smtpDrops() 
    { 
        # check for permissions
        $access = Permissions::checkForAuthorization($this->authenticatedUser,__CLASS__,__FUNCTION__);

        if($access == false)
        {
            throw new PageException('Access Denied !',403);
        }

        # set menu status
        $this->masterView->set([
            'production' => 'true',
            'drops_smtp_drops' => 'true'
        ]);
        
        # get isps 
        $isps = Isp::all(Isp::FETCH_ARRAY,['status = ?','Activated'],['id','name'],'name','ASC');
        
        # preparing the columns array to create the list
        $columnsArray = [
            'id',
            'start_time',
            'mailer',
            'isp',
            'servers',
            'offer',
            'lists',
            'status',
            'start',
            'total',
            'progress',
            'delivered',
            'bounced',
            'opens',
            'clicks',
            'leads',
            'unsubs'
        ];
        
        $columnsSizes = [
            'start_time' => ' style="width:2%" ',
            'mailer' => ' style="width:7%" ',
            'servers' => ' style="width:9%" ',
            'isp' => ' style="width: 5.5%" ',
            'offer' => ' style="width:13%" ',
            'status' => ' style="width:3%" ',
            'start' => ' style="width:0.5%" ',
            'lists' => ' style="width:10%" ',
            'total' => ' style="width:0.5%" ',
            'progress' => ' style="width:0.5%" ',
            'delivered' => ' style="width:0.5%" ',
            'bounced' => ' style="width:0.5%" ',
            'opens' => ' style="width:0.5%" ',
            'clicks' => ' style="width:0.5%" ',
            'leads' => ' style="width:0.5%" ',
            'unsubs' => ' style="width:0.5%" '
        ];
        
        # creating the html part of the list 
        $index = 1;
        $columns = '';
        $filters = '';
        $footer = '<th class="ft_' . $index . '"></th>';
     
        foreach ($columnsArray as $column) 
        {
            $footer .= '<th class="ft_' . $index . '"></th>'; $index++;
            
            if($column != 'id')
            {
                $size = key_exists($column,$columnsSizes) ? $columnsSizes[$column] : '';
                
                $columns .= '<th>' . ucwords(str_replace('_',' ',strtolower($column))) . '</th>' . PHP_EOL;
                
                if(strpos($column,'_date') > -1 || strpos($column,'_time') > -1)
                {
                    $filters .= '<td ' . $size . '> <div id="' . $column . '_range" class="input-group date-range-picker"> <input type="text" class="form-control form-filter" name="' . $column . '_range"> <span class="input-group-btn"> <button class="btn default date-range-toggle" type="button"> <i class="fa fa-calendar"></i> </button> </span> </div> </td>' . PHP_EOL;
                }
                else if($column == 'status')
                {
                    $filters .= '<td ' . $size . '> <select name="status" class="form-control form-filter input-sm"> <option value="" selected>All</option><option value="In Progress">In Progress</option> <option value="Completed">Completed</option> <option value="Paused">Paused</option> <option value="Error">Error</option> <option value="Interrupted">Interrupted</option> </select> </td>' . PHP_EOL;
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
        }
        
        $footer .= '<th class="ft_' . $index . '"></th>';
            
        # set data to the page view
        $this->pageView->set([
            'columns' => $columns,
            'filters' => $filters,
            'footer' => $footer
        ]);
    }

    /**
     * @name getSmtpDrops
     * @description the get smtp drops action
     * @before init
     * @after closeConnections,checkForMessage
     */
    public function getSmtpDrops()
    { 
        # check for permissions
        $access = Permissions::checkForAuthorization($this->authenticatedUser,__CLASS__,'smtpDrops');

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
                'd.id' => 'id',
                "to_char(start_time, 'YYYY-MM-DD HH24:MI')" => 'start_time',
                "u.first_name || ' ' || u.last_name" => 'mailer',
                'i.name' => 'isp',
                "'<button style=\"margin-top:-7px\" class=\"btn btn-sm blue-madison show-process-servers\" data-type=\"sd\" title=\"Show Process Servers\" data-id=\"' || d.id || '\"><i class=\"fa fa-globe\"></i></button>' || replace((SELECT string_agg(name, ',') FROM admin.smtp_servers s WHERE s.id = ANY (string_to_array(d.servers_ids,',')::int[])),',',' ')" => 'servers',
                "'(' || off.production_id || ') ' || off.name" => 'offer',
                "'<button style=\"margin-top:-7px\" class=\"btn btn-sm green-dark show-process-lists\" data-type=\"sd\" title=\"Show Process Lists\" data-id=\"' || d.id || '\"><i class=\"fa fa-at\"></i></button>' || replace((SELECT string_agg(name, ',') FROM lists.data_lists dt WHERE dt.id = ANY (string_to_array(d.lists,',')::int[])),',',' ') " => 'lists',
                'd.status' => 'status',
                'COALESCE(d.data_start,0)' => 'start',
                'COALESCE(d.total_emails,0)' => 'total',
                'COALESCE(d.progress,0)' => 'progress',
                "COALESCE((SELECT SUM(ips.delivered) FROM production.smtp_processes_users ips WHERE ips.process_id = d.id),0)" => 'delivered',
                "COALESCE((SELECT SUM(ips.hard_bounced) FROM production.smtp_processes_users ips WHERE ips.process_id = d.id),0)" => 'bounced', 
                "(SELECT COUNT(1) FROM actions.opens op WHERE op.process_id = d.id)" => 'opens',
                "(SELECT COUNT(1) FROM actions.clicks cl WHERE cl.process_id = d.id)" => 'clicks',
                "(SELECT COUNT(1) FROM actions.leads ld WHERE ld.process_id = d.id)" => 'leads',
                "(SELECT COUNT(1) FROM actions.unsubscribes un WHERE un.process_id = d.id)" => 'unsubs'
            ];
            
            # prepare query 
            $query = $this->app->database('system')->query()->from('production.smtp_processes d',$columns)
                    ->join('admin.users u','d.user_id = u.id')
                    ->join('admin.isps i','d.isp_id = i.id')
                    ->join('affiliate.offers off','d.offer_id = off.id')
                    ->where('d.process_type = ?','drop');

            # fetching the results to create the ajax list
            if(Authentication::getAuthenticatedUser()->getMasterAccess() != 'Enabled')
            {
                $userTeams = [$this->authenticatedUser->getId()];
                $authorisations = TeamAuthorisation::all(TeamAuthorisation::FETCH_ARRAY,['team_member_id = ?',Authentication::getAuthenticatedUser()->getId()],['team_id']);

                foreach ($authorisations as $authorisation) 
                {
                    $userTeams[] = $authorisation['team_id'];
                }
                
                if(count($userTeams) != 0)
                {
                    # check if the user is a team leader 
                    $userIds = [Authentication::getAuthenticatedUser()->getId()];
                    $teams = Team::all(Team::FETCH_ARRAY,["status = ? and " . $userIds[0] . " = ANY (string_to_array(team_leaders_ids,',')::int[])",['Activated']],['id','name']);

                    if(count($teams))
                    {
                        foreach ($teams as $team) 
                        {
                            $members = TeamAuthorisation::all(TeamAuthorisation::FETCH_ARRAY,['team_member_id > 0 AND team_id = ?',$team['id']],['id','team_id','team_member_id']);

                            if(count($members))
                            {
                                foreach ($members as $member) 
                                {
                                    $userIds[] = intval($member['team_member_id']);
                                }
                            }
                        }
                    }
                    
                    $query->where('d.user_id in ?',[$userIds]);
                }
            }
            
            die(json_encode(DataTable::init($data,'production.smtp_processes d',$columns,new SmtpProcess(),'production' . RDS . 'smtp-drops','DESC',$query,false)));
        }
    }
    
    /**
     * @name smtpTests
     * @description the smtp tests action
     * @before init
     * @after closeConnections,checkForMessage
     */
    public function smtpTests() 
    { 
        # check for permissions
        $access = Permissions::checkForAuthorization($this->authenticatedUser,__CLASS__,__FUNCTION__);

        if($access == false)
        {
            throw new PageException('Access Denied !',403);
        }

        # set menu status
        $this->masterView->set([
            'production' => 'true',
            'drops_smtp_tests' => 'true'
        ]);
        
        # get isps 
        $isps = Isp::all(Isp::FETCH_ARRAY,['status = ?','Activated'],['id','name'],'name','ASC');
        
        # preparing the columns array to create the list
        $columnsArray = [
            'id',
            'start_time',
            'mailer',
            'isp_name' => 'isp',
            'servers',
            'offer',
            'status',
            'total',
            'progress',
            'delivered',
            'bounced',
            'opens',
            'clicks',
            'leads',
            'unsubs'
        ];
        
        $columnsSizes = [
            'start_time' => ' style="width:2%" ',
            'mailer' => ' style="width:7%" ',
            'servers' => ' style="width:9%" ',
            'isp' => ' style="width: 5.5%" ',
            'offer' => ' style="width:10%" ',
            'status' => ' style="width:3%" ',
            'start' => ' style="width:0.5%" ',
            'total' => ' style="width:0.5%" ',
            'progress' => ' style="width:0.5%" ',
            'delivered' => ' style="width:0.5%" ',
            'bounced' => ' style="width:0.5%" ',
            'opens' => ' style="width:0.5%" ',
            'leads' => ' style="width:0.5%" ',
            'clicks' => ' style="width:0.5%" ',
            'unsubs' => ' style="width:0.5%" '
        ];
        
        # creating the html part of the list 
        $index = 1;
        $columns = '';
        $filters = '';
        $footer = '<th class="ft_' . $index . '"></th>';
     
        foreach ($columnsArray as $column) 
        {
            $footer .= '<th class="ft_' . $index . '"></th>'; $index++;
            
            if($column != 'id')
            {
                $size = key_exists($column,$columnsSizes) ? $columnsSizes[$column] : '';
                
                $columns .= '<th>' . ucwords(str_replace('_',' ',strtolower($column))) . '</th>' . PHP_EOL;
                
                if(strpos($column,'_date') > -1 || strpos($column,'_time') > -1)
                {
                    $filters .= '<td ' . $size . '> <div id="' . $column . '_range" class="input-group date-range-picker"> <input type="text" class="form-control form-filter" name="' . $column . '_range"> <span class="input-group-btn"> <button class="btn default date-range-toggle" type="button"> <i class="fa fa-calendar"></i> </button> </span> </div> </td>' . PHP_EOL;
                }
                else if($column == 'status')
                {
                    $filters .= '<td ' . $size . '> <select name="status" class="form-control form-filter input-sm"> <option value="" selected>All</option> <option value="In Progress">In Progress</option> <option value="Completed">Completed</option> <option value="Paused">Paused</option> <option value="Error">Error</option> <option value="Interrupted">Interrupted</option> </select> </td>' . PHP_EOL;
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
        }
        
        $footer .= '<th class="ft_' . $index . '"></th>';
            
        # set data to the page view
        $this->pageView->set([
            'columns' => $columns,
            'filters' => $filters,
            'footer' => $footer
        ]);
    }
    
    /**
     * @name getSmtpTests
     * @description the get smtp tests action
     * @before init
     * @after closeConnections,checkForMessage
     */
    public function getSmtpTests()
    { 
        # check for permissions
        $access = Permissions::checkForAuthorization($this->authenticatedUser,__CLASS__,'smtpTests');

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
                't.id' => 'id',
                "to_char(start_time, 'YYYY-MM-DD HH24:MI')" => 'start_time',
                "u.first_name || ' ' || u.last_name" => 'mailer',
                'i.name' => 'isp',
                "'<button style=\"margin-top:-7px\" class=\"btn btn-sm blue-madison show-process-servers\" data-type=\"st\" title=\"Show Process Servers\" data-id=\"' || t.id || '\"><i class=\"fa fa-globe\"></i></button>' || substring(replace((SELECT string_agg(name, ',') FROM admin.smtp_servers s WHERE s.id = ANY (string_to_array(t.servers_ids,',')::int[])),',',' '),1,18)" => 'servers',
                "'(' || off.production_id || ') ' || off.name" => 'offer',
                't.status' => 'status',
                'COALESCE(t.total_emails,0)' => 'total',
                'COALESCE(t.progress,0)' => 'progress',
                'COALESCE(t.delivered,0)' => 'delivered',
                'COALESCE(t.hard_bounced,0)' => 'bounced',
                "(SELECT COUNT(1) FROM actions.opens op WHERE op.process_id = t.id)" => 'opens',
                "(SELECT COUNT(1) FROM actions.clicks cl WHERE cl.process_id = t.id)" => 'clicks',
                "(SELECT COUNT(1) FROM actions.leads ld WHERE ld.process_id = t.id)" => 'leads',
                "(SELECT COUNT(1) FROM actions.unsubscribes un WHERE un.process_id = t.id)" => 'unsubs'
            ];
            

            # prepare query 
            $query = $this->app->database('system')->query()->from('production.smtp_processes t',$columns)
                    ->join('admin.users u','t.user_id = u.id')
                    ->join('admin.isps i','t.isp_id = i.id')
                    ->join('affiliate.offers off','t.offer_id = off.id')
                    ->where("t.process_type NOT LIKE 'drop'",[]);
     
            # fetching the results to create the ajax list
            if(Authentication::getAuthenticatedUser()->getMasterAccess() != 'Enabled')
            {
               $userTeams = [$this->authenticatedUser->getId()];
                $authorisations = TeamAuthorisation::all(TeamAuthorisation::FETCH_ARRAY,['team_member_id = ?',Authentication::getAuthenticatedUser()->getId()],['team_id']);

                foreach ($authorisations as $authorisation) 
                {
                    $userTeams[] = $authorisation['team_id'];
                }
                
                if(count($userTeams) != 0)
                {
                    # check if the user is a team leader 
                    $userIds = [Authentication::getAuthenticatedUser()->getId()];
                    $teams = Team::all(Team::FETCH_ARRAY,["status = ? and " . $userIds[0] . " = ANY (string_to_array(team_leaders_ids,',')::int[])",['Activated']],['id','name']);


                    if(count($teams))
                    {
                        foreach ($teams as $team) 
                        {
                            $members = TeamAuthorisation::all(TeamAuthorisation::FETCH_ARRAY,['team_member_id > 0 AND team_id = ?',$team['id']],['id','team_id','team_member_id']);

                            if(count($members))
                            {
                                foreach ($members as $member) 
                                {
                                    $userIds[] = intval($member['team_member_id']); 
                                }
                            }
                        }
                    }

                    $query->where('t.user_id in ?',[$userIds]);
                }
            }
            
            die(json_encode(DataTable::init($data,'production.smtp_processes t',$columns,new SmtpProcess(),'production' . RDS . 'smtp-tests','DESC',$query,false)));
        }
    }
    
    /**
     * @name processDetails
     * @description the mta process details action
     * @before init
     * @after closeConnections,checkForMessage
     */
    public function processDetails() 
    { 
        $arguments = func_get_args(); 
        $type = isset($arguments) && count($arguments) > 0 ? $arguments[0] : null;
        $id = isset($arguments) && count($arguments) > 1 ? $arguments[1] : null;
        
        # check for permissions
        $method = '';
        
        switch ($type)
        {
            case 'mt' : $method = 'mtaTests'; break; 
            case 'md' : $method = 'mtaDrops'; break; 
            case 'st' : $method = 'smtpTests'; break; 
            case 'sd' : $method = 'smtpDrops'; break; 
        }
        
        $access = Permissions::checkForAuthorization($this->authenticatedUser,__CLASS__,$method);

        if($access == false)
        {
            throw new PageException('Access Denied !',403);
        }

        $valid = true;
        
        # set menu status
        $this->masterView->set([
            'production' => 'true',
            'drops_mta' => 'true',
            'drops_mta_show' => 'true'
        ]);
        
        if(!isset($id) || !is_numeric($id) || intval($id) == 0)
        {
            $valid = false;
        }
        
        $where = ['id = ?',$id];
        
        if(Authentication::getAuthenticatedUser()->getMasterAccess() != 'Enabled')
        {
            $hasAdminRole = Permissions::hasAdminBasedRole(Authentication::getAuthenticatedUser());
            
            if($hasAdminRole == false)
            {
                # check if the user is a team leader 
                $userIds = [Authentication::getAuthenticatedUser()->getId()];
                $teams = Team::all(Team::FETCH_ARRAY,["status = ? and " . $userIds[0] . " = ANY (string_to_array(team_leaders_ids,',')::int[])",['Activated']],['id','name']);

                if(count($teams))
                {
                    foreach ($teams as $team) 
                    {
                        $members = TeamAuthorisation::all(TeamAuthorisation::FETCH_ARRAY,['team_member_id > 0 AND team_id = ?',$team['id']],['id','team_id','team_member_id']);

                        if(count($members))
                        {
                            foreach ($members as $member) 
                            {
                                $userIds[] = intval($member['team_member_id']);
                            }
                        }
                    }
                }

                $where = ['id = ? AND user_id in ?',[$id,$userIds]];
            }
        }
        
        $process = $type == 'mt' || $type == 'md' ? MtaProcess::first(MtaProcess::FETCH_ARRAY,$where) : SmtpProcess::first(SmtpProcess::FETCH_ARRAY,$where);
        
        if(count($process) == 0)
        {
            $valid = false;
        }
        
        if($valid == true)
        { 
            $json = json_decode(base64_decode($process['content']),true);
            
            if(count($json) == 0)
            {
                # stores the message in the session 
                Page::registerMessage('error','Invalid drop content !');

                # redirect to lists page
                Page::redirect();
            }

            $processDetails = [];
            $serversIds = [];
            $vmtasIds = [];
            
            # fill with basic data
            foreach ($process as $key => $row) 
            {
                if($key != 'content')
                {
                    $processDetails[$key] = $row;
                }
            }
            
            # type 
            $processDetails['process_type'] = $type == 'mt' || $type == 'md' ? 'mta' : 'smtp';
            $processDetails['type'] = $this->app->utils->arrays->get($json,'type');
            
            # get mailer info 
            $user = User::first(User::FETCH_ARRAY,['id = ?',$processDetails['user_id']],['id','first_name','last_name','production_id']);
            
            if(count($user))
            {
                $processDetails['user_production_id'] = $user['production_id'];
                $processDetails['user_full_name'] = $user['first_name'] . ' ' . $user['last_name'] ;
            }
            
            # servers and vmtas
            foreach ($this->app->utils->arrays->get($json,'selected-vmtas') as $val) 
            {
               $serversIds[] = intval($this->app->utils->arrays->get(explode('|',$val),0));
               $vmtasIds[] = intval($this->app->utils->arrays->get(explode('|',$val),1));
            }

            $processDetails['servers'] = ($processDetails['process_type'] == 'mta') ? MtaServer::all(MtaServer::FETCH_ARRAY,['id IN ?',[$serversIds]],['id','name'])
            : SmtpServer::all(SmtpServer::FETCH_ARRAY,['id IN ?',[$serversIds]],['id','name']);
            
            if($processDetails['process_type'] == 'mta')
            {
                $processDetails['vmtas'] = ServerVmta::all(ServerVmta::FETCH_ARRAY,['id IN ?',[$vmtasIds]],['id','name','domain','ip','mta_server_name']);
            }
            else
            {
                $processDetails['smtp_users'] = SmtpUser::all(SmtpUser::FETCH_ARRAY,['id IN ?',[$vmtasIds]],['id','username','smtp_server_name']);
            }
  
            # test emails & placeholders
            $processDetails['test_emails'] = $this->app->utils->arrays->get($json,'rcpts');
            $processDetails['placeholders_one'] = $this->app->utils->arrays->get($json,'placeholders-one');
            $processDetails['placeholders_two'] = $this->app->utils->arrays->get($json,'placeholders-two');
            $processDetails['placeholders_three'] = $this->app->utils->arrays->get($json,'placeholders-three');
            
            # rotations & combinations
            $processDetails['vmtas_rotation'] = $this->app->utils->arrays->get($json,'vmta-rotation',1);
            $processDetails['test_rotation'] = $this->app->utils->arrays->get($json,'rcpt-rotation',1);
            $processDetails['test_after'] = $this->app->utils->arrays->get($json,'test-after',1000);
            $processDetails['test_emails_combination'] = $this->app->utils->arrays->get($json,'rcpt-combination','off');
            $processDetails['placeholders_one_rotation'] = $this->app->utils->arrays->get($json,'placeholders-one-rotation','off');
            $processDetails['placeholders_one_combination'] = $this->app->utils->arrays->get($json,'placeholders-one-combination','off');
            $processDetails['placeholders_two_rotation'] = $this->app->utils->arrays->get($json,'placeholders-two-rotation','off');
            $processDetails['placeholders_two_combination'] = $this->app->utils->arrays->get($json,'placeholders-two-combination','off');
            $processDetails['placeholders_three_rotation'] = $this->app->utils->arrays->get($json,'placeholders-three-rotation','off');
            $processDetails['placeholders_three_combination'] = $this->app->utils->arrays->get($json,'placeholders-three-combination','off');
            $processDetails['split_emails_type'] = $this->app->utils->arrays->get($json,'emails-split-type');
            $processDetails['emails_process_type'] = $this->app->utils->arrays->get($json,'vmtas-emails-process');
            $processDetails['batch'] = $this->app->utils->arrays->get($json,'batch');
            $processDetails['x_delay'] = $this->app->utils->arrays->get($json,'x-delay');
            $processDetails['return_path'] = $this->app->utils->arrays->get($json,'return-path');
            $processDetails['static_domain'] = $this->app->utils->arrays->get($json,'static-domain');
            $processDetails['charset'] = $this->app->utils->arrays->get($json,'creative-charset');
            $processDetails['content_type'] = $this->app->utils->arrays->get($json,'creative-content-type');
            $processDetails['content_transfer_encoding'] = $this->app->utils->arrays->get($json,'creative-content-transfert-encoding');
            $processDetails['link_type'] = $this->app->utils->arrays->get($json,'link-type');

            # affiliate
            $processDetails['affiliate_network_name'] = $process['affiliate_network_id'] > 0 ? 
            $this->app->utils->arrays->get(AffiliateNetwork::first(AffiliateNetwork::FETCH_ARRAY,['id = ?',intval($process['affiliate_network_id'])],['name']),'name') : '';          
            $processDetails['offer_name'] = $process['offer_id'] > 0 ? 
            $this->app->utils->arrays->get(Offer::first(Offer::FETCH_ARRAY,['id = ?',intval($process['offer_id'])],['name']),'name') : '';           
            $processDetails['from_name'] = intval($this->app->utils->arrays->get($json,'from-name-id')) > 0 ? 
            $this->app->utils->arrays->get(FromName::first(FromName::FETCH_ARRAY,['id = ?',intval($this->app->utils->arrays->get($json,'from-name-id'))],['value']),'value') : '';   
            $processDetails['from_name_encoding'] = $this->app->utils->arrays->get($json,'from-name-encoding');
            $processDetails['subject'] = intval($this->app->utils->arrays->get($json,'subject-id')) > 0 ? 
            $this->app->utils->arrays->get(Subject::first(Subject::FETCH_ARRAY,['id = ?',intval($this->app->utils->arrays->get($json,'subject-id'))],['value']),'value') : '';
            $processDetails['subject_encoding'] = $this->app->utils->arrays->get($json,'subject-encoding');
            
            # data and isps
            $processDetails['isp_name'] = $process['isp_id'] > 0 ? 
            $this->app->utils->arrays->get(Isp::first(Isp::FETCH_ARRAY,['id = ?',intval($process['isp_id'])],['name']),'name') : '';
            $processDetails['countries'] = key_exists('countries',$json) && is_array($json['countries']) ? implode(',',$json['countries']): 'US';
            $processDetails['data_start'] = $this->app->utils->arrays->get($json,'data-start');
            $processDetails['data_count'] = $this->app->utils->arrays->get($json,'data-count');
            $processDetails['data_duplicate'] = $this->app->utils->arrays->get($json,'data-duplicate',1);
            
            if(strlen($process['lists']) > 0)
            {
                $processDetails['lists'] = DataList::all(DataList::FETCH_ARRAY,['id IN ?',[explode(',',$process['lists'])]],['id','name','data_provider_name']);
            }
            else
            {
                $processDetails['lists'] = [];
            }
            
            # data flags
            $processDetails['is_fresh'] = array_key_exists('fresh-filter',$json) ? 'on' : 'off';
            $processDetails['is_clean'] = array_key_exists('clean-filter',$json) ? 'on' : 'off';
            $processDetails['is_openers'] = array_key_exists('openers-filter',$json) ? 'on' : 'off';
            $processDetails['is_clickers'] = array_key_exists('clickers-filter',$json) ? 'on' : 'off';
            $processDetails['is_leaders'] = array_key_exists('leaders-filter',$json) ? 'on' : 'off';
            $processDetails['is_optouts'] = array_key_exists('optouts-filter',$json) ? 'on' : 'off';
            $processDetails['is_unsubs'] = array_key_exists('unsubs-filter',$json) ? 'on' : 'off';
            $processDetails['is_seeds'] = array_key_exists('seeds-filter',$json) ? 'on' : 'off';
            
            # header and body
            $headers = $this->app->utils->arrays->get($json,'headers',[]);
            $processDetails['headers'] = is_array($headers) && count($headers) ? implode(PHP_EOL . '_SEPARATOR_' . PHP_EOL,$headers): '';
            $processDetails['body'] = $this->app->utils->arrays->get($json,'body');

            # set data to the page view
            $this->pageView->set([
                'process' => $processDetails
            ]); 
        }
        else
        {
            # stores the message in the session 
            Page::registerMessage('error','Invalid process id !');
            
            # redirect to lists page
            Page::redirect();
        }
    }
    
    /**
     * @name uploadImages
     * @description upload images action
     * @before init
     * @after closeConnections,checkForMessage
     */
    public function uploadImages()
    { 
        # check for permissions
        $access = Permissions::checkForAuthorization($this->authenticatedUser,__CLASS__,__FUNCTION__);

        if($access == false)
        {
            throw new PageException('Access Denied !',403);
        }
        
        # get post data
        $files = $this->app->http->request->retrieve(Request::ALL,Request::FILES);
        
        $message = 'Internal server error !';
        $flag = 'error';
        
        $html = '';
        
        if(count($files))
        {
            $offerImage = $this->app->utils->arrays->get($files,'offer-image');
            $unsubImage = $this->app->utils->arrays->get($files,'unsub-image');
            $optoutImage = $this->app->utils->arrays->get($files,'optout-image');
            
            # prepare html 
            $html = '<html>' . PHP_EOL;
            $html .= '   <head>' . PHP_EOL;
            $html .= '       <meta http-equiv="Content-Type" content="text/html; charset=utf-8">' . PHP_EOL;
            $html .= '   </head>' . PHP_EOL;
            $html .= '   <body>' . PHP_EOL;
            $html .= '       <center>' . PHP_EOL;
            
            $images = [];

            # get offers image
            if(isset($offerImage) && count($offerImage) && $offerImage['size'] > 0)
            {
                $valid = true;
                
                # start validations 
                if(intval($offerImage['error']) > 0)
                {
                    switch (intval($offerImage['error'])) 
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
                        case UPLOAD_ERR_NO_FILE:
                        {
                            $message = "No file was uploaded";
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
                    
                    $valid = false;
                }
            
                if(!in_array($offerImage['type'],['image/jpg','image/jpeg','image/png','image/gif','image/bmp']) || $offerImage['size'] == 0)
                {
                    $message = 'Unsupported file type : ' . $offerImage['type'];
                    $valid = false;
                }
                
                if($valid)
                {
                    $extension = $this->app->utils->arrays->last(explode('.',$offerImage['name']));
                    $fileName = $this->app->utils->strings->randomHex(15) . '.' . $extension;
                    $this->app->utils->fileSystem->moveFileOrDirectory($offerImage['tmp_name'],MEDIA_PATH . DS . $fileName);
                    $html .= '      <a href="http://[domain]/[url]"><img src="http://[domain]/' . $fileName . '" alt="image"/></a><br/>' . PHP_EOL;
                    $images[] = MEDIA_PATH . DS . $fileName;
                }
            }
            
            # get unsub image
            if(isset($unsubImage) && count($unsubImage) && $unsubImage['size'] > 0)
            {
                $valid = true;
                
                # start validations 
                if(intval($unsubImage['error']) > 0)
                {
                    switch (intval($unsubImage['error'])) 
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
                        case UPLOAD_ERR_NO_FILE:
                        {
                            $message = "No file was uploaded";
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
                    
                    $valid = false;
                }
            
                if(!in_array($unsubImage['type'],['image/jpg','image/jpeg','image/png','image/gif','image/bmp']) || $unsubImage['size'] == 0)
                {
                    $message = 'Unsupported file type : ' . $unsubImage['type'];
                    $valid = false;
                }
                
                if($valid)
                {
                    $extension = $this->app->utils->arrays->last(explode('.',$unsubImage['name']));
                    $fileName = $this->app->utils->strings->randomHex(15) . '.' . $extension;
                    $this->app->utils->fileSystem->moveFileOrDirectory($unsubImage['tmp_name'],MEDIA_PATH . DS . $fileName);
                    $html .= '      <a href="http://[domain]/[unsub]"><img src="http://[domain]/' . $fileName . '" alt="image"/></a><br/>' . PHP_EOL;
                    $images[] = MEDIA_PATH . DS . $fileName;
                }
            } 
            
            # get offers image
            if(isset($optoutImage) && count($optoutImage) && $optoutImage['size'] > 0)
            {
                $valid = true;
                
                # start validations 
                if(intval($optoutImage['error']) > 0)
                {
                    switch (intval($optoutImage['error'])) 
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
                        case UPLOAD_ERR_NO_FILE:
                        {
                            $message = "No file was uploaded";
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
                    
                    $valid = false;
                }
            
                if(!in_array($optoutImage['type'],['image/jpg','image/jpeg','image/png','image/gif','image/bmp']) || $optoutImage['size'] == 0)
                {
                    $message = 'Unsupported file type : ' . $optoutImage['type'];
                    $valid = false;
                }
                
                if($valid)
                {
                    $extension = $this->app->utils->arrays->last(explode('.',$optoutImage['name']));
                    $fileName = $this->app->utils->strings->randomHex(15) . '.' . $extension;
                    $this->app->utils->fileSystem->moveFileOrDirectory($optoutImage['tmp_name'],MEDIA_PATH . DS . $fileName);
                    $html .= '      <a href="http://[domain]/[optout]"><img src="http://[domain]/' . $fileName . '" alt="image"/></a><br/>' . PHP_EOL;
                    $images[] = MEDIA_PATH . DS . $fileName;
                }
            }
             
            # try to upload images 
            if(count($images))
            {
                $id = intval($this->app->utils->arrays->get($this->app->getSetting('application'),'upload_center_id'));
                $uploadCenter = ManagementServer::first(ManagementServer::FETCH_ARRAY,['id = ?',$id],['id','name']);

                if(count($uploadCenter) > 0)
                {
                    # call iresponse api
                    $result = Api::call('Production','uploadImages',['images-paths' => $images]);

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
                else
                {
                    $flag = 'success';
                    $message = 'Images uploaded successfully !';
                }
            }

            $html .= '       </center>' . PHP_EOL;
            $html .= '   </body>' . PHP_EOL;
            $html .= '</html>' . PHP_EOL;
             
            Page::registerMessage($flag,$message);
        }
        
        # set menu status
        $this->masterView->set([
            'production' => 'true',
            'upload_images' => 'true'
        ]);
        
        # set data to the page view
        $this->pageView->set([
            'html' => $html
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