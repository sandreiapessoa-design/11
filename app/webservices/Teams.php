<?php declare(strict_types=1); namespace IR\App\Webservices; if (!defined('IR_START')) exit('<pre>No direct script access allowed</pre>');
/**
 * @framework       iResponse Framework 
 * @version         1.0
 * @author          Amine Idrissi <contact@iresponse.tech>
 * @date            2024
 * @name            Teams.php	
 */

# core 
use IR\Core\Base as Base;
use IR\Core\Application as Application;

# helpers 
use IR\App\Helpers\Authentication as Authentication;
use IR\App\Helpers\Permissions as Permissions;
use IR\App\Helpers\Page as Page;

# models
use IR\App\Models\Production\Team as Team;
use IR\App\Models\Production\TeamAuthorisation as TeamAuthorisation;
use IR\App\Models\Admin\User as User;
use IR\App\Models\Admin\MtaServer as MtaServer;
use IR\App\Models\Admin\ServerVmta as ServerVmta;
use IR\App\Models\Admin\SmtpServer as SmtpServer;
use IR\App\Models\Affiliate\AffiliateNetwork as AffiliateNetwork;
use IR\App\Models\Affiliate\Offer as Offer;
use IR\App\Models\Lists\DataProvider as DataProvider;

/**
 * @name Teams
 * @description Teams WebService
 */
class Teams extends Base
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
     * @name getTeamMembers
     * @description get team members action
     * @before init
     */
    public function getTeamMembers($parameters = []) 
    { 
        # check for authentication
        if(!Authentication::isUserAuthenticated())
        {
            Page::printApiResults(401,'Only logged-in access allowed !');
        }
        
        # check users roles 
        Authentication::checkUserRoles();
        
        # check for permissions
        $access = Permissions::checkForAuthorization(Authentication::getAuthenticatedUser(),'Teams','authorisations');

        if($access == false)
        {
            Page::printApiResults(403,'Access Denied !');
        }
        
        $teamId = intval($this->app->utils->arrays->get($parameters,'team-id'));

        if($teamId > 0)
        {
            $team = Team::first(Team::FETCH_ARRAY,['status = ? and id = ?',['Activated',$teamId]]);

            if(count($team) == 0)
            {
                Page::printApiResults(500,'Team not found !');
            }

            $users = User::all(User::FETCH_ARRAY,['id IN ?',[explode(',',$team['team_members_ids'])]],['id','first_name','last_name']);

            if(count($users) == 0)
            {
                Page::printApiResults(500,'No members found !');
            }
            else
            {
                Page::printApiResults(200,'',['users' => $users]);
            }
        }
        else
        {
            Page::printApiResults(500,'Incorrect team id !');
        }
    }
    
    /**
     * @name getTeamMembersAffectation
     * @description get team members action
     * @before init
     */
    public function getTeamMembersAffectation($parameters = []) 
    { 
        # check for authentication
        if(!Authentication::isUserAuthenticated())
        {
            Page::printApiResults(401,'Only logged-in access allowed !');
        }
        
        # check users roles 
        Authentication::checkUserRoles();
        
        # check for permissions
        $access = Permissions::checkForAuthorization(Authentication::getAuthenticatedUser(),'Teams','authorisations');

        if($access == false)
        {
            Page::printApiResults(403,'Access Denied !');
        }
        
        $teamId = intval($this->app->utils->arrays->get($parameters,'team-id'));

        if($teamId > 0)
        {
            $team = Team::first(Team::FETCH_ARRAY,['status = ? and id = ?',['Activated',$teamId]]);

            if(count($team) == 0)
            {
                Page::printApiResults(500,'Team not found !');
            }

            $affetedUsers = User::all(User::FETCH_ARRAY,['id IN ?',[explode(',',$team['team_members_ids'])]],['id','first_name','last_name']);
            $unaffectedUsers = User::all(User::FETCH_ARRAY,['id NOT IN ? AND master_access = ? AND status = ?',[explode(',',$team['team_members_ids']),'Disabled','Activated']]);

            if(count($affetedUsers) == 0 && count($unaffectedUsers) == 0)
            {
                Page::printApiResults(500,'No members found !');
            }
            else
            {
                Page::printApiResults(200,'',['affected-users' => $affetedUsers,'unaffected-users' => $unaffectedUsers]);
            }
        }
        else
        {
            Page::printApiResults(500,'Incorrect team id !');
        }
    }
    
    /**
     * @name getMemberAuthorisations
     * @description get member authorisations action
     * @before init
     */
    public function getMemberAuthorisations($parameters = []) 
    { 
        # check for authentication
        if(!Authentication::isUserAuthenticated())
        {
            Page::printApiResults(401,'Only logged-in access allowed !');
        }
        
        # check users roles 
        Authentication::checkUserRoles();
        
        # check for permissions
        $access = Permissions::checkForAuthorization(Authentication::getAuthenticatedUser(),'Teams','authorisations');

        if($access == false)
        {
            Page::printApiResults(403,'Access Denied !');
        }
        
        $teamId = intval($this->app->utils->arrays->get($parameters,'team-id'));
        $userId = intval($this->app->utils->arrays->get($parameters,'user-id'));

        if($teamId > 0)
        {
            $team = Team::first(Team::FETCH_ARRAY,['status = ? and id = ?',['Activated',$teamId]]);

            if(count($team) == 0)
            {
                Page::printApiResults(500,'Team not found !');
            }
            
            # options
            $mtaServersOptions = '';
            $smtpServersOptions = '';
            $affiliateNetworksOptions = '';
            $dataProvidersOptions = '';

            # authorisations
            $auth = TeamAuthorisation::first(TeamAuthorisation::FETCH_ARRAY,['team_id = ? AND team_member_id = ?',[$teamId,$userId]]);
            $mtaServers = MtaServer::all(MtaServer::FETCH_ARRAY,[],['id','name'],'naturalsort(name)','ASC');
            $smtpServers = SmtpServer::all(SmtpServer::FETCH_ARRAY,[],['id','name'],'naturalsort(name)','ASC');
            $affiliateNetworks = AffiliateNetwork::all(AffiliateNetwork::FETCH_ARRAY,[],['id','name'],'naturalsort(name)','ASC');
            $dataProviders = DataProvider::all(DataProvider::FETCH_ARRAY,[],['id','name'],'naturalsort(name)','ASC');

            # check authorisations
            if(count($auth))
            {
                # mta servers 
                $serversIds = [];
                $vmtasIds = array_unique(array_filter(explode(',',$auth['vmtas_ids'])));
                
                if(count($vmtasIds))
                {
                    $results = $this->app->database('system')->execute("SELECT id FROM admin.mta_servers WHERE id IN (SELECT mta_server_id FROM admin.servers_vmtas WHERE id IN (" . $auth['vmtas_ids'] . "))");
                    
                    if(count($results))
                    {
                        foreach ($results as $row) 
                        {
                            $serversIds[] = intval($row['id']);
                        } 
                    }
                }

                foreach ($mtaServers as $server)
                {
                    $selected = (in_array(intval($server['id']),$serversIds)) ? ' selected ' : '';
                    $mtaServersOptions .= '<option value="' . $server['id'] . '" ' . $selected . '>' . $server['name'] . '</option>';
                }

                # smtp servers 
                $serversIds = array_filter(explode(',',$auth['smtp_servers_ids']));

                foreach ($smtpServers as $server)
                {
                    $selected = (in_array(intval($server['id']),$serversIds)) ? ' selected ' : '';
                    $smtpServersOptions .= '<option value="' . $server['id'] . '" ' . $selected . '>' . $server['name'] . '</option>';
                }
                
                # affiliate networks
                $affiliateNetworksIds = [];
                $offersIds = array_unique(array_filter(explode(',',$auth['offers_ids'])));
                
                if(count($offersIds))
                {
                    $results = $this->app->database('system')->execute("SELECT id FROM affiliate.affiliate_networks WHERE id IN (SELECT affiliate_network_id FROM affiliate.offers WHERE id IN (" . $auth['offers_ids'] . "))");
                    
                    if(count($results))
                    {
                        foreach ($results as $row) 
                        {
                            $affiliateNetworksIds[] = intval($row['id']);
                        }
                    }
                }
                
                foreach ($affiliateNetworks as $affiliateNetwork)
                {
                    $selected = (in_array(intval($affiliateNetwork['id']),$affiliateNetworksIds)) ? ' selected ' : '';
                    $affiliateNetworksOptions .= '<option value="' . $affiliateNetwork['id'] . '" ' . $selected . '>' . $affiliateNetwork['name'] . '</option>';
                }
                
                # data providers
                $dataProvidersIds = [];
                $listsIds = array_unique(array_filter(explode(',',$auth['data_lists_ids'])));
                
                if(count($listsIds))
                {
                    $results = $this->app->database('system')->execute("SELECT id FROM lists.data_providers WHERE id IN (SELECT data_provider_id FROM lists.data_lists WHERE id IN (" . $auth['data_lists_ids'] . "))");
                    
                    if(count($results))
                    {
                        foreach ($results as $row) 
                        {
                            $dataProvidersIds[] = intval($row['id']);
                        }   
                    }
                }

                foreach ($dataProviders as $dataProvider)
                {
                    $selected = (in_array(intval($dataProvider['id']),$dataProvidersIds)) ? ' selected ' : '';
                    $dataProvidersOptions .= '<option value="' . $dataProvider['id'] . '" ' . $selected . '>' . $dataProvider['name'] . '</option>';
                }
            }
            
            $results = [
                'mtaServers' => $mtaServersOptions,
                'smtpServers' => $smtpServersOptions,
                'affiliateNetworks' => $affiliateNetworksOptions,
                'dataProviders' => $dataProvidersOptions,
            ];

            Page::printApiResults(200,'',$results);
        }
        else
        {
            Page::printApiResults(500,'Incorrect team  or member id !');
        }
    }
    
    /**
     * @name getMemberVmtas
     * @description get member vmtas action
     * @before init
     */
    public function getMemberVmtas($parameters = []) 
    { 
        # check for authentication
        if(!Authentication::isUserAuthenticated())
        {
            Page::printApiResults(401,'Only logged-in access allowed !');
        }
        
        # check users roles 
        Authentication::checkUserRoles();
        
        # check for permissions
        $access = Permissions::checkForAuthorization(Authentication::getAuthenticatedUser(),'Teams','authorisations');

        if($access == false)
        {
            Page::printApiResults(403,'Access Denied !');
        }
        
        $teamId = intval($this->app->utils->arrays->get($parameters,'team-id'));
        $userId = intval($this->app->utils->arrays->get($parameters,'user-id'));
        $serversIds = $this->app->utils->arrays->get($parameters,'server-ids');

        if($teamId > 0 && count($serversIds))
        {
            $team = Team::first(Team::FETCH_ARRAY,['status = ? and id = ?',['Activated',$teamId]]);

            if(count($team) == 0)
            {
                Page::printApiResults(500,'Team not found !');
            }


            # options
            $vmtasOptions = '';

            # authorisations
            $auth = TeamAuthorisation::first(TeamAuthorisation::FETCH_ARRAY,['team_id = ? AND team_member_id = ?',[$teamId,$userId]]);
            $vmtasIds = array_unique(array_filter(explode(',',$auth['vmtas_ids'])));
            $vmtas = ServerVmta::all(ServerVmta::FETCH_ARRAY,['status = ? and mta_server_id in ?',['Activated',$serversIds]],
            ['id','status','name','ip','mta_server_name']);

            foreach ($vmtas as $vmta)
            {
                $selected = (in_array(intval($vmta['id']),$vmtasIds)) ? ' selected ' : '';
                $vmtasOptions .= '<option value="' . $vmta['id'] . '" data-ip="' . $vmta['ip'] . '" ' . $selected . '> ( ' . $vmta['mta_server_name'] . ' ) ' . $vmta['name'] . ' ( ' . $vmta['status'] . ' ) </option>';
            }

            Page::printApiResults(200,'',['vmtas' => $vmtasOptions]);
        }
    }
    
    /**
     * @name getMemberOffers
     * @description get member offers action
     * @before init
     */
    public function getMemberOffers($parameters = []) 
    { 
        # check for authentication
        if(!Authentication::isUserAuthenticated())
        {
            Page::printApiResults(401,'Only logged-in access allowed !');
        }
        
        # check users roles 
        Authentication::checkUserRoles();
        
        # check for permissions
        $access = Permissions::checkForAuthorization(Authentication::getAuthenticatedUser(),'Teams','authorisations');

        if($access == false)
        {
            Page::printApiResults(403,'Access Denied !');
        }
        
        $teamId = intval($this->app->utils->arrays->get($parameters,'team-id'));
        $userId = intval($this->app->utils->arrays->get($parameters,'user-id'));
        $affiliateNetworksIds = $this->app->utils->arrays->get($parameters,'affiliate-networks-ids');

        if($teamId > 0 && count($affiliateNetworksIds))
        {
            $team = Team::first(Team::FETCH_ARRAY,['status = ? and id = ?',['Activated',$teamId]]);

            if(count($team) == 0)
            {
                Page::printApiResults(500,'Team not found !');
            } 

            # options
            $offersOptions = '';

            # authorisations
            $auth = TeamAuthorisation::first(TeamAuthorisation::FETCH_ARRAY,['team_id = ? AND team_member_id = ?',[$teamId,$userId]]);
            $offersIds = array_unique(array_filter(explode(',',$auth['offers_ids'])));
            $offers = Offer::all(Offer::FETCH_ARRAY,['status = ? and affiliate_network_id in ?',['Activated',$affiliateNetworksIds]],
            ['id','name','affiliate_network_name']);

            foreach ($offers as $offer)
            {
                $selected = (in_array(intval($offer['id']),$offersIds)) ? ' selected ' : '';
                $offersOptions .= '<option value="' . $offer['id'] . '" ' . $selected . '> ( ' . $offer['affiliate_network_name'] . ' ) ' . $offer['name'] . '</option>';
            }

            Page::printApiResults(200,'',['offers' => $offersOptions]);
        }
    }
    
    /**
     * @name getMemberDataLists
     * @description get member lists action
     * @before init
     */
    public function getMemberDataLists($parameters = []) 
    { 
        # check for authentication
        if(!Authentication::isUserAuthenticated())
        {
            Page::printApiResults(401,'Only logged-in access allowed !');
        }
        
        # check users roles 
        Authentication::checkUserRoles();
        
        # check for permissions
        $access = Permissions::checkForAuthorization(Authentication::getAuthenticatedUser(),'Teams','authorisations');

        if($access == false)
        {
            Page::printApiResults(403,'Access Denied !');
        }
        
        $teamId = intval($this->app->utils->arrays->get($parameters,'team-id'));
        $userId = intval($this->app->utils->arrays->get($parameters,'user-id'));
        $dataProvidersIds = $this->app->utils->arrays->get($parameters,'data-providers-ids');

        if($teamId > 0 && count($dataProvidersIds))
        {
            $team = Team::first(Team::FETCH_ARRAY,['status = ? and id = ?',['Activated',$teamId]]);

            if(count($team) == 0)
            {
                Page::printApiResults(500,'Team not found !');
            }

            # options
            $dataListsOptions = '';

            # authorisations
            $isps = explode(',',$team['isps_ids']);
            $isps = is_array($isps) ? $isps : [$isps];
            $auth = TeamAuthorisation::first(TeamAuthorisation::FETCH_ARRAY,['team_id = ? AND team_member_id = ?',[$teamId,$userId]]);
            $columns = ['l.id' => 'id','l.name' => 'name','p.name' => 'data_provider_name','i.name' => 'isp_name'];
            $dataLists = $this->app->database('system')->query()->from('lists.data_lists l',$columns)
                        ->join('admin.isps i','l.isp_id = i.id')
                        ->join('lists.data_providers p','l.data_provider_id = p.id')
                        ->where('l.isp_id IN ? AND data_provider_id IN ?',[$isps,$dataProvidersIds])
                        ->all();
            $dataListsIds = array_unique(array_filter(explode(',',$auth['data_lists_ids'])));

            foreach ($dataLists as $dataList)
            {
                $selected = (in_array(intval($dataList['id']),$dataListsIds)) ? ' selected ' : '';
                $dataListsOptions .= '<option value="' . $dataList['id'] . '" ' . $selected . '> ( ' . $dataList['data_provider_name'] . ' - ' . $dataList['isp_name'] . ' ) ' . $dataList['name'] . '</option>';
            }

            Page::printApiResults(200,'',['data-lists' => $dataListsOptions]);
        }
    }
    
    /**
     * @name updateMemberAuthorisations
     * @description update team member authorisations action
     * @before init
     */
    public function updateMemberAuthorisations($parameters = []) 
    { 
        # check for authentication
        if(!Authentication::isUserAuthenticated())
        {
            Page::printApiResults(401,'Only logged-in access allowed !');
        }
        
        # check users roles 
        Authentication::checkUserRoles();
        
        # check for permissions
        $access = Permissions::checkForAuthorization(Authentication::getAuthenticatedUser(),'Teams','authorisations');

        if($access == false)
        {
            Page::printApiResults(403,'Access Denied !');
        }
        
        $teamId = intval($this->app->utils->arrays->get($parameters,'team-id'));
        $userId = intval($this->app->utils->arrays->get($parameters,'user-id'));
        $vmtasIds = $this->app->utils->arrays->get($parameters,'vmtas-ids',[]);
        $smtpServersIds = $this->app->utils->arrays->get($parameters,'smtp-servers-ids',[]);
        $offersIds = $this->app->utils->arrays->get($parameters,'offers-ids',[]);
        $dataListsIds = $this->app->utils->arrays->get($parameters,'data-lists-ids',[]);
        
        # double check for arrays 
        $vmtasIds = is_array($vmtasIds) ? $vmtasIds : [];
        $smtpServersIds = is_array($smtpServersIds) ? $smtpServersIds : [];
        $offersIds = is_array($offersIds) ? $offersIds : [];
        $dataListsIds = is_array($dataListsIds) ? $dataListsIds : [];
        
        if($teamId > 0 && $userId > 0)
        {
            $team = Team::first(Team::FETCH_ARRAY,['status = ? and id = ?',['Activated',$teamId]]);

            if(count($team) == 0)
            {
                Page::printApiResults(500,'Team not found !');
            }

            # authorisation
            $auth = TeamAuthorisation::first(TeamAuthorisation::FETCH_ARRAY,['team_id = ? AND team_member_id = ?',[$teamId,$userId]]);

            if(count($auth) == 0)
            {
                Page::printApiResults(500,'Team authorisations not found !');
            }

            $oldVmtasIds = array_unique(array_filter(explode(',',$auth['vmtas_ids'])));
            $oldSmtpServersIds = array_unique(array_filter(explode(',',$auth['smtp_servers_ids'])));
            $oldOffersIds = array_unique(array_filter(explode(',',$auth['offers_ids'])));
            $oldDataListsIds = array_unique(array_filter(explode(',',$auth['data_lists_ids'])));

            $addVmtasIds = [];
            $deleteVmtasIds = [];
            $addSmtpServersIds = [];
            $deleteSmtpServersIds = [];
            $addOffersIds = [];
            $deleteOffersIds = [];
            $addDataListsIds = [];
            $deleteDataListsIds = [];

            # mta servers vmtas
            if(is_array($oldVmtasIds) && count($oldVmtasIds))
            {
                foreach ($oldVmtasIds as $id) 
                {
                    if(!in_array($id,$vmtasIds))
                    {
                        $deleteVmtasIds[] = $id;
                    }
                }
            }

            if(is_array($vmtasIds) && count($vmtasIds))
            {
                foreach ($vmtasIds as $id) 
                {
                    if(!in_array($id,$oldVmtasIds))
                    {
                        $addVmtasIds[] = $id;
                    }
                }
            }

            # smtp servers
            if(is_array($oldSmtpServersIds) && count($oldSmtpServersIds))
            {
                foreach ($oldSmtpServersIds as $id) 
                {
                    if(!in_array($id,$smtpServersIds))
                    {
                        $deleteSmtpServersIds[] = $id;
                    }
                }
            }

            if(is_array($smtpServersIds) && count($smtpServersIds))
            {
                foreach ($smtpServersIds as $id) 
                {
                    if(!in_array($id,$oldSmtpServersIds))
                    {
                        $addSmtpServersIds[] = $id;
                    }
                }
            }
            
            # offers
            if(is_array($oldOffersIds) && count($oldOffersIds))
            {
                foreach ($oldOffersIds as $id) 
                {
                    if(!in_array($id,$offersIds))
                    {
                        $deleteOffersIds[] = $id;
                    }
                }
            }

            if(is_array($offersIds) && count($offersIds))
            {
                foreach ($offersIds as $id) 
                {
                    if(!in_array($id,$oldOffersIds))
                    {
                        $addOffersIds[] = $id;
                    }
                }
            }

            # data lists
            if(is_array($oldDataListsIds) && count($oldDataListsIds))
            {
                foreach ($oldDataListsIds as $id) 
                {
                    if(!in_array($id,$dataListsIds))
                    {
                        $deleteDataListsIds[] = $id;
                    }
                }
            }

            if(is_array($dataListsIds) && count($dataListsIds))
            {
                foreach ($dataListsIds as $id) 
                {
                    if(!in_array($id,$oldDataListsIds))
                    {
                        $addDataListsIds[] = $id;
                    }
                }
            }

            # mta servers vmtas part
            $tmp = [];

            if(count($deleteVmtasIds))
            {
                foreach ((array_filter(explode(',',$auth['vmtas_ids']))) as $id) 
                {
                    if(!in_array($id,$deleteVmtasIds))
                    {
                        $tmp[] = $id;
                    }
                }
            }
            else
            {
               $tmp = array_filter(explode(',',$auth['vmtas_ids']));
            }

            if(count($addVmtasIds))
            {
                $tmp = array_unique(array_merge($tmp,$addVmtasIds));
            }

            $auth['vmtas_ids'] = implode(',',$tmp);

            # smtp servers part
            $tmp = [];

            if(count($deleteSmtpServersIds))
            {
                foreach (array_filter(explode(',',$auth['smtp_servers_ids'])) as $id) 
                {
                    if(!in_array($id,$deleteSmtpServersIds))
                    {
                        $tmp[] = $id;
                    }
                }
            }
            else
            {
               $tmp = array_filter(explode(',',$auth['smtp_servers_ids']));
            }

            if(count($addSmtpServersIds))
            {
                $tmp = array_unique(array_merge($tmp,$addSmtpServersIds));
            }

            $auth['smtp_servers_ids'] = implode(',',$tmp);
            
            # offers part
            $tmp = [];

            if(count($deleteOffersIds))
            {
                foreach (array_filter(explode(',',$auth['offers_ids'])) as $id) 
                {
                    if(!in_array($id,$deleteOffersIds))
                    {
                        $tmp[] = $id;
                    }
                }
            }
            else
            {
               $tmp = array_filter(explode(',',$auth['offers_ids']));
            }

            if(count($addOffersIds))
            {
                $tmp = array_unique(array_merge($tmp,$addOffersIds));
            }

            $auth['offers_ids'] = implode(',',$tmp);

            # data lists part
            $tmp = [];

            if(count($deleteDataListsIds))
            {
                foreach (array_filter(explode(',',$auth['data_lists_ids'])) as $id) 
                {
                    if(!in_array($id,$deleteDataListsIds))
                    {
                        $tmp[] = $id;
                    }
                }
            }
            else
            {
               $tmp = array_filter(explode(',',$auth['data_lists_ids']));
            }

            if(count($addDataListsIds))
            {
                $tmp = array_unique(array_merge($tmp,$addDataListsIds));
            }

            $auth['data_lists_ids'] = implode(',',$tmp);

            $auth = new TeamAuthorisation($auth);
            $auth->update();

            Page::printApiResults(200,'Authorisations updated successfully !',[]);
        }
        else
        {
            Page::printApiResults(500,'No team or team member found !');
        }
    }
    
    /**
     * @name getUserTeams
     * @description get user teams action
     * @before init
     */
    public function getUserTeams($parameters = []) 
    { 
        # check for authentication
        if(!Authentication::isUserAuthenticated())
        {
            Page::printApiResults(401,'Only logged-in access allowed !');
        }
        
        # check users roles 
        Authentication::checkUserRoles();
        
        # check for permissions
        $access = Permissions::checkForAuthorization(Authentication::getAuthenticatedUser(),'Teams','users');

        if($access == false)
        {
            Page::printApiResults(403,'Access Denied !');
        }
        
        $userId = intval($this->app->utils->arrays->get($parameters,'user-id'));

        if($userId > 0)
        {
            $user = User::first(User::FETCH_ARRAY,['status = ? and id = ?',['Activated',$userId]]);

            if(count($user) == 0)
            {
                Page::printApiResults(500,'User not found !');
            }

            $teamAuthorisations = TeamAuthorisation::all(TeamAuthorisation::FETCH_ARRAY,['team_member_id = ?',$userId]);

            $teamsIds = [];

            foreach ($teamAuthorisations as $auth) 
            {
                if(count($auth))
                {
                    $teamsIds[] = intval($auth['team_id']);
                }
            }

            Page::printApiResults(200,'',['teams' => $teamsIds]);
        }
        else
        {
            Page::printApiResults(500,'Incorrect user id !');
        }
    }
}