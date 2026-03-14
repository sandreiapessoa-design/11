<?php declare(strict_types=1); namespace IR\App\Controllers; if (!defined('IR_START')) exit('<pre>No direct script access allowed</pre>');
/**
 * @framework       iResponse Framework 
 * @version         1.0
 * @author          Amine Idrissi <contact@iresponse.tech>
 * @date            2024
 * @name            Teams.php	
 */

# core 
use IR\Core\Application as Application;

# mvc 
use IR\Mvc\Controller as Controller;

# models
use IR\App\Models\Production\Team as Team;
use IR\App\Models\Production\TeamAuthorisation as TeamAuthorisation;
use IR\App\Models\Admin\User as User;
use IR\App\Models\Admin\Isp as Isp;

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
 * @name Teams
 * @description Teams Controller
 */
class Teams extends Controller
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
            'status',
            'isps',
            'team_leaders_count',
            'team_members_count',
            'created_by',
            'created_date'
        ];
        
        # creating the html part of the list 
        $columns = Page::createTableHeader($columnsArray);
        $filters = Page::createTableFilters($columnsArray);

        # set menu status
        $this->masterView->set([
            'teams' => 'true',
            'teams_show' => 'true'
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
                'status',
                "substring(replace((SELECT string_agg(name, ',') FROM admin.isps i WHERE i.id = ANY (string_to_array(isps_ids,',')::int[])),',','/'),1,18)" => 'isps',
                'team_leaders_count',
                'team_members_count',
                'created_by',
                'created_date'
            ];
        
            # fetching the results to create the ajax list
            die(json_encode(DataTable::init($data,'production.teams',$columns,new Team(),'teams','DESC')));
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
            'teams' => 'true',
            'teams_add' => 'true'
        ]);
        
        # set data to the page view
        $this->pageView->set([
            'users' => User::all(User::FETCH_ARRAY,['master_access = ? AND status = ?',['Disabled','Activated']]),
            'isps' => Isp::all(Isp::FETCH_ARRAY,['status = ?','Activated'],['id','name'])
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
            'teams' => 'true',
            'teams_show' => 'true'
        ]);
        
        if(!isset($id) || !is_numeric($id) || intval($id) == 0)
        {
            $valid = false;
        }
        
        $team = Team::first(Team::FETCH_ARRAY,['id = ?',$id]);
        
        if(count($team) == 0)
        {
            $valid = false;
        }
        
        if($valid == true)
        {
            # set data to the page view
            $this->pageView->set([
                'team' => $team,
                'users' => User::all(User::FETCH_ARRAY,['master_access = ? AND status = ?',['Disabled','Activated']]),
                'isps' => Isp::all(Isp::FETCH_ARRAY,['status = ?','Activated'],['id','name'])
            ]); 
        }
        else
        {
            # stores the message in the session 
            Page::registerMessage('error','Invalid team id !');
            
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
            $team = new Team();
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
                $team->setId(intval($this->app->utils->arrays->get($data,'id')));
                $team->load();
                $team->setLastUpdatedBy($username);
                $team->setLastUpdatedDate(date('Y-m-d'));
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
                $team->setCreatedBy($username);
                $team->setCreatedDate(date('Y-m-d'));
                $team->setLastUpdatedBy($username);
                $team->setLastUpdatedDate(date('Y-m-d'));
            }

            $team->setName($this->app->utils->arrays->get($data,'name'));
            $team->setStatus($this->app->utils->arrays->get($data,'status','Activated'));
            $team->setIspsIds(implode(',',$this->app->utils->arrays->get($data,'isps')));
            $team->setTeamLeadersIds(implode(',',$this->app->utils->arrays->get($data,'team-leaders')));
            $team->setTeamLeadersCount(count($this->app->utils->arrays->get($data,'team-leaders')));
            $team->setTeamMembersIds(implode(',',$this->app->utils->arrays->get($data,'team-members')));
            $team->setTeamMembersCount(count($this->app->utils->arrays->get($data,'team-members')));
            $result = $update == false ? $team->insert() : $team->update(); 

            if($result > -1)
            {
                $flag = 'success';

                $id = $update == false ? $result : intval($this->app->utils->arrays->get($data,'id'));
                $members = $this->app->utils->arrays->get($data,'team-members');
                
                # new team case
                if($update == false)
                {
                    if(is_array($members) && count($members))
                    {
                        foreach ($members as $memberId) 
                        {
                            $teamAuthorisation = new TeamAuthorisation();
                            $teamAuthorisation->setTeamId($id);
                            $teamAuthorisation->setTeamMemberId($memberId);
                            $teamAuthorisation->insert();
                        }
                    }
                }
                else
                {
                    $teamAuths = TeamAuthorisation::all(TeamAuthorisation::FETCH_ARRAY,['team_id = ?',$id]);

                    $newMembers = [];
                    $deletedMembers = [];
                    
                    # gather new added members
                    if(is_array($members) && count($members))
                    {
                        foreach ($members as $memberId) 
                        {
                            $found = false;

                            if(count($teamAuths))
                            {
                                foreach ($teamAuths as $auth) 
                                {
                                    if(intval($auth['team_member_id']) == intval($memberId))
                                    {
                                        $found = true;
                                        break;
                                    }
                                }
                            }

                            if($found == false)
                            {
                                $newMembers[] = $memberId;
                            }
                        }
                    }

                    # gather members to delete
                    if(count($teamAuths))
                    {
                        foreach ($teamAuths as $auth) 
                        {
                            $found = false;

                            if(is_array($members) && count($members))
                            {
                                foreach ($members as $memberId) 
                                {
                                    if(intval($auth['team_member_id']) == intval($memberId))
                                    {
                                        $found = true;
                                        break;
                                    }
                                }
                            }

                            if($found == false)
                            {
                                $deletedMembers[] = intval($auth['id']);
                            }
                        }
                    }
                    
                    # add new members auths 
                    if(count($newMembers))
                    {
                        foreach ($newMembers as $memberId) 
                        {
                            $teamAuthorisation = new TeamAuthorisation();
                            $teamAuthorisation->setTeamId($id);
                            $teamAuthorisation->setTeamMemberId($memberId);
                            $teamAuthorisation->insert();
                        }
                    }
                    
                    # delete removed members auths 
                    if(count($deletedMembers))
                    {
                        TeamAuthorisation::deleteWhere('id IN ?',[$deletedMembers]);
                    }
                }
            }
        }

        # stores the message in the session 
        Page::registerMessage($flag, $message);

        # redirect to lists page
        Page::redirect();
    }
    
    /**
     * @name authorisations
     * @description the authorisations action
     * @before init
     * @after closeConnections,checkForMessage
     */
    public function authorisations() 
    { 
        # check for permissions
        $access = Permissions::checkForAuthorization($this->authenticatedUser,__CLASS__,__FUNCTION__);

        if($access == false)
        {
            throw new PageException('Access Denied !',403);
        }
        
        # set menu status
        $this->masterView->set([
            'teams' => 'true',
            'teams_authorisations' => 'true'
        ]);

        # get teams 
        if($this->authenticatedUser->getMasterAccess() != 'Enabled')
        {
            $roles = $this->authenticatedUser->getRoles();
            
            if(in_array('Mailers Team Leader',$roles))
            {
                $teams = Team::all(Team::FETCH_ARRAY,["status = ? and " . $this->authenticatedUser->getId() . " = ANY (string_to_array(team_leaders_ids,',')::int[])",['Activated']],['id','name'],'naturalsort(name)','ASC');
            }
            else
            {
                $teams = Team::all(Team::FETCH_ARRAY,['status = ?',['Activated']],['id','name'],'naturalsort(name)','ASC');
            }
        }
        else
        {
            $teams = Team::all(Team::FETCH_ARRAY,['status = ?',['Activated']],['id','name'],'naturalsort(name)','ASC');
        }
        
        # set data to the page view
        $this->pageView->set([
            'teams' => $teams
        ]);
    }
 
    /**
     * @name users
     * @description the users action
     * @before init
     * @after closeConnections
     */
    public function users()
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
            $message = 'Internal server error !';
            $flag = 'error';

            $teamId = intval($this->app->utils->arrays->get($data,'team-id'));
            $affectedUsers = $this->app->utils->arrays->get($data,'affected-users');
            
            if($teamId > 0)
            {
                # get team 
                $team = Team::first(Team::FETCH_OBJECT,['id = ?',$teamId]);
                
                if($team->getName() != '')
                {
                    # get old members
                    $oldAffetedUsers = User::all(User::FETCH_ARRAY,['id IN ?',[explode(',',$team->getTeamMembersIds())]],['id']);
                    
                    # empty team if there is no affected users
                    if(!is_array($affectedUsers) || count($affectedUsers) == 0)
                    {
                        # delete all teams auths 
                        TeamAuthorisation::deleteWhere('team_id = ?',[$teamId]);
                    }
                    else
                    {
                        # delete un affected users 
                        foreach ($oldAffetedUsers as $user)
                        {
                            if(!in_array($user['id'],$affectedUsers))
                            {
                                # delete team member auths 
                                TeamAuthorisation::deleteWhere('team_id = ? AND team_member_id = ?',[$teamId,$user['id']]);
                            }
                        }
                        
                        # add new affected users 
                        foreach ($affectedUsers as $userId)
                        {
                            $found = false;
                            
                            foreach ($oldAffetedUsers as $user)
                            {
                                if($user['id'] == $userId)
                                {
                                    $found = true;
                                }
                            }
                            
                            if($found == false)
                            {
                                $teamAuthorisation = new TeamAuthorisation();
                                $teamAuthorisation->setTeamId($teamId);
                                $teamAuthorisation->setTeamMemberId($userId);
                                $teamAuthorisation->insert();
                            }
                        }
                    }
                    
                    # update team 
                    $auths = TeamAuthorisation::all(TeamAuthorisation::FETCH_ARRAY,['team_id = ?',$teamId],['team_member_id']);
                    
                    if(count($auths) == 0)
                    {
                        $team->setTeamMembersIds('');
                        $team->setTeamMembersCount(0);
                        $team->update();
                    }
                    else
                    {
                        $membersIds = [];
                        
                        foreach ($auths as $auth)
                        {
                            $membersIds[] = intval($auth['team_member_id']);
                        }
                        
                        $membersIds = array_unique($membersIds);
                        $team->setTeamMembersIds(implode(',',$membersIds));
                        $team->setTeamMembersCount(count($membersIds));
                        $team->update();
                    }  
                }

                $message = 'Teams affectation updated successfully !';
                $flag = 'success';
            }
            
            # stores the message in the session 
            Page::registerMessage($flag, $message);

            # redirect to lists page
            Page::redirect();
        }
        else
        {
            # set menu status
            $this->masterView->set([
                'teams' => 'true',
                'users_teams' => 'true'
            ]);
        
            # set data to the page view
            $this->pageView->set([
                'teams' => Team::all(Team::FETCH_ARRAY,['status = ?','Activated'],['id','name'],'naturalsort(name)','ASC')
            ]);
            
            # check for message 
            Page::checkForMessage($this);
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