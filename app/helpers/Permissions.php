<?php declare(strict_types=1); namespace IR\App\Helpers; if (!defined('IR_START')) exit('<pre>No direct script access allowed</pre>');
/**
 * @framework       iResponse Framework 
 * @version         1.0
 * @author          Amine Idrissi <contact@iresponse.tech>
 * @date            2024
 * @name            Permissions.php	
 */

# core 
use IR\Core\Application as Application;

# models 
use IR\App\Models\Admin\User as User;
use IR\App\Models\Admin\Role as Role;
use IR\App\Models\Admin\UserRole as UserRole;
use IR\App\Models\Production\Team as Team;
use IR\App\Models\Production\TeamAuthorisation as TeamAuthorisation;

/**
 * @name Permissions
 * @description Permissions Helper
 */
class Permissions
{
    /**
     * @name checkForAuthorization
     * @description check for authorization
     * @access public
     * @return array
     */
    public static function checkForAuthorization(User $user , string $controller,string $method) : bool
    {
        if($user->getMasterAccess() != 'Enabled')
        {
            if($method != null && $method != "")
            {
                $controller = Application::getCurrent()->utils->objects->removeNameSpaces($controller); 
                $permissions = $user->getPermissions();
                
                if(count($permissions))
                {
                    foreach ($permissions as $permission) 
                    {
                        if(count($permission))
                        {
                            if($controller == $permission['controller'] && $method == $permission['method'])
                            {
                                return true;
                            }
                        }
                    }
                }
            }

            return false;
        }
        
        return true;
    }
   
    /**
     * @name isMenuAuthorized
     * @description is menu authorized
     * @access public
     * @return array
     */
    public static function isMenuAuthorized(User $user , string $menu) : bool
    {
        if($user->getMasterAccess() != 'Enabled')
        {
            $permissions = $user->getPermissions();
            
            foreach ($permissions as $permission) 
            {
                if(count($permission))
                {
                    if(in_array(trim(strtolower($menu)),explode(',',$permission['parents'])))
                    {
                        return true; 
                    }
                }
            }
            
            return false;
        }
        
        return true;
    }

    
    /**
     * @name isMenuiTemAuthorized
     * @description is menu item authorized
     * @access public
     * @return array
     */
    public static function isMenuiTemAuthorized(User $user , string $menuItem) : bool
    {
        if($user->getMasterAccess() != 'Enabled')
        {
            return in_array(trim(strtolower($menuItem)),array_keys($user->getPermissions()));
        }
        
        return true; 
    }
    
    /**
     * @name hasAdminBasedRole
     * @description has admin based role
     * @access public
     * @return array
     */
    public static function hasAdminBasedRole(User $user) : bool
    {
        if($user->getMasterAccess() != 'Enabled')
        {
            $userRoles = UserRole::all(UserRole::FETCH_ARRAY,['user_id = ?',$user->getId()],['role_id']);
            $rolesIds = [];

            foreach ($userRoles as $rolesUser) 
            {
                if(count($rolesUser))
                {
                    $rolesIds[] = intval($rolesUser['role_id']);
                }
            }

            if(count($rolesIds))
            {
                $roles = Role::all(Role::FETCH_ARRAY,['id IN ?',[$rolesIds]],['id','role_type']);

                if(count($roles))
                {
                    foreach ($roles as $role) 
                    {
                        if($role['role_type'] == 'Admin Based Role')
                        {
                            return true;
                        }
                    }
                }
            }
            
            return false;
        }
        
        return true;
    }

    /**
     * @name filterResults
     * @description filterResults
     * @access public
     * @return array
     */
    public static function modelTeamAuthsFilter($model,$user,&$where) : array
    {
        $teamBasedFilterIds = []; 
        
        if(Permissions::hasAdminBasedRole($user) == false)
        {
            $filter = false;
            $authorisations = TeamAuthorisation::all(TeamAuthorisation::FETCH_ARRAY,['team_member_id = ?',$user->getId()]);
            $model = Application::getCurrent()->utils->objects->removeNameSpaces($model);
            
            # server provider case
            if($model == 'ServerProvider')
            {
                $providersIds = [];
                $vmtasIds = [];
                
                foreach ($authorisations as $authorisation) 
                {
                    $vmtasIds = array_merge($vmtasIds,array_unique(array_filter(explode(',',$authorisation['vmtas_ids']))));
                }

                if(count($vmtasIds))
                {
                    $results = Application::getCurrent()->database('system')->execute("SELECT id FROM admin.servers_providers WHERE id IN (SELECT provider_id FROM admin.mta_servers WHERE id IN "
                    . "(SELECT mta_server_id FROM admin.servers_vmtas WHERE id IN (" . implode(',',$vmtasIds) . ")))");
                    
                    foreach ($results as $row) 
                    {
                        $providersIds[] = intval($row['id']);
                    }   
                }
                
                if($authorisation['smtp_servers_ids'] != '')
                {
                    $results = Application::getCurrent()->database('system')->execute("SELECT id FROM admin.servers_providers WHERE id IN (SELECT provider_id FROM admin.smtp_servers WHERE id IN ({$authorisation['smtp_servers_ids']})");
                    
                    foreach ($results as $row) 
                    {
                        $providersIds[] = intval($row['id']);
                    }   
                }
                
                if(count($providersIds))
                {
                    $teamBasedFilterIds = array_unique($providersIds);
                }
                
                $filter = true;
            }
            # mta servers case
            if($model == 'MtaServer')
            {
                $vmtasIds = [];
                
                foreach ($authorisations as $authorisation) 
                {
                    $vmtasIds = array_merge($vmtasIds,array_unique(array_filter(explode(',',$authorisation['vmtas_ids']))));
                }

                if(count($vmtasIds))
                {
                    $results = Application::getCurrent()->database('system')->execute("SELECT id FROM admin.mta_servers WHERE id IN (SELECT mta_server_id FROM admin.servers_vmtas WHERE id IN (" . implode(',',$vmtasIds) . "))");
                    
                    foreach ($results as $row) 
                    {
                        $teamBasedFilterIds[] = intval($row['id']);
                    }   
                }
                
                $filter = true;
            }
            # affiliate networks case
            else if($model == 'AffiliateNetwork')
            {
                $offersIds = [];
                
                foreach ($authorisations as $authorisation) 
                {
                    $offersIds = array_merge($offersIds,array_unique(array_filter(explode(',',$authorisation['offers_ids']))));
                }

                if(count($offersIds))
                {
                    $results = Application::getCurrent()->database('system')->execute("SELECT id FROM affiliate.affiliate_networks WHERE id IN (SELECT affiliate_network_id FROM affiliate.offers WHERE id IN (" . implode(',',$offersIds) . "))");
                    
                    foreach ($results as $row) 
                    {
                        $teamBasedFilterIds[] = intval($row['id']);
                    }   
                }
                
                $filter = true;
            }
            # data providers case
            else if($model == 'DataProvider')
            {
                $listsIds = [];
                
                foreach ($authorisations as $authorisation) 
                {
                    $listsIds = array_merge($listsIds,array_unique(array_filter(explode(',',$authorisation['data_lists_ids']))));
                }

                if(count($listsIds))
                {
                    $results = Application::getCurrent()->database('system')->execute("SELECT id FROM lists.data_providers WHERE id IN (SELECT data_provider_id FROM lists.data_lists WHERE id IN (" . implode(',',$listsIds) . "))");
                    
                    foreach ($results as $row) 
                    {
                        $teamBasedFilterIds[] = intval($row['id']);
                    }   
                }
                
                $filter = true;
            }
            else if($model == 'Isp')
            {
                $teams = Team::all(Team::FETCH_ARRAY,["status = ? and " . $user->getId() . " = ANY (string_to_array(team_members_ids,',')::int[])",['Activated']],['id','isps_ids']);

                foreach ($teams as $team) 
                {
                    $teamBasedFilterIds = array_merge($teamBasedFilterIds,array_filter(explode(",",$team['isps_ids'])));
                }
                
                $filter = true;
            }
            else
            {
                $column = '';
                
                switch ($model) 
                {
                    case 'ServerVmta' :
                    {
                        $column = 'vmtas_ids';
                        break;
                    }
                    case 'SmtpServer' :
                    {
                        $column = 'smtp_servers_ids';
                        break;
                    }
                    case 'Offer' :
                    {
                        $column = 'offers_ids';
                        break;
                    }
                    case 'DataList' :
                    {
                        $column = 'data_lists_ids';
                        break;
                    }
                }

                if($column != '')
                {
                    foreach ($authorisations as $authorisation) 
                    {
                        $teamBasedFilterIds = array_merge($teamBasedFilterIds,array_unique(array_filter(explode(',',$authorisation[$column]))));
                    }
                    
                    $filter = true;
                }
            }
            
            $teamBasedFilterIds = count($teamBasedFilterIds) == 0 && $filter == true ? [-1] : $teamBasedFilterIds;

            if(is_array($where) && count($where) && count($teamBasedFilterIds))
            {
                $where[0] .= ' AND id IN ?';

                if(is_array($where[1]) && count($where[1]))
                {
                    $where[1][] = $teamBasedFilterIds;
                }
                else if(isset($where[1]))
                {
                    $where[1] = [$where[1],$teamBasedFilterIds];
                }
            }
            else
            {
                $where = ['id IN ?',[$teamBasedFilterIds]];
            }
        }
        
        return $teamBasedFilterIds;
    }

    /**
     * @name __construct
     * @description private constructor to prevent it being created directly
     * @access private
     * @return
     */ 
    private function __construct()  
    {}  

    /**
     * @name __clone
     * @description private clone to prevent it being cloned directly
     * @access private
     * @return
     */ 
    private function __clone()  
    {}
    
    /**
     * @var
     * @readwrite
     */
    public static $_TEAM_BASED_MODELS = [
        'MtaServer',
        'ServerProvider',
        'AffiliateNetwork',
        'DataProvider',
        'Isp',
        'Offer',
        'DataList',
        'ServerVmta',
        'SmtpServer'
    ];
    
    /**
     * @var
     * @readwrite
     */
    public static $_MODEL_CONTOLLER_MAPPING = [
        'AuditLog' => 'AuditLogs',
        'AwsAccount' => 'AmazonAccounts',
        'AwsInstance' => 'AmazonInstances',
        'AwsProcess' => 'AmazonInstances',
        'AwsAccountProcess' => 'AmazonAccounts',
        'DigitalOceanAccount' => 'DigitalOceanAccounts',
        'DigitalOceanDroplet' => 'DigitalOceanDroplets',
        'DigitalOceanProcess' => 'DigitalOceanDroplets',
        'LinodeAccount' => 'LinodeAccounts',
        'LinodeInstance' => 'LinodeInstances',
        'LinodeProcess' => 'LinodeInstances',
        'HetznerAccount' => 'HetznerAccounts',
        'HetznerInstance' => 'HetznerInstances',
        'HetznerProcess' => 'HetznerInstances',
        'AtlanticAccount' => 'AtlanticAccounts',
        'AtlanticInstance' => 'AtlanticInstances',
        'AtlanticProcess' => 'AtlanticInstances',
        'ScalewayAccount' => 'ScalewayAccounts',
        'ScalewayInstance' => 'ScalewayInstances',
        'ScalewayProcess' => 'ScalewayInstances',
        'VultrAccount' => 'VultrAccounts',
        'VultrInstance' => 'VultrInstances',
        'VultrProcess' => 'VultrInstances',
        'Domain' => 'Domains',
        'GoDaddy' => 'GodaddyAccounts',
        'Isp' => 'Isps',
        'Mailbox' => 'Mailboxes',
        'ManagementServer' => 'ManagementServers',
        'MtaServer' => 'MtaServers',
        'Namecheap' => 'NamecheapAccounts',
        'Namecom' => 'NamecomAccounts',
        'PmtaHistory' => 'Pmta',
        'ProxyServer' => 'MtaServers',
        'Role' => 'Roles',
        'ServerProvider' => 'ServersProviders',
        'ServerVmta' => 'MtaServers',
        'SmtpServer' => 'SmtpServers',
        'SmtpUser' => 'SmtpServers',
        'SubName' => 'Domains',
        'User' => 'Users',
        'AffiliateNetwork' => 'AffiliateNetworks',
        'Offer' => 'Offers',
        'Creative' => 'Offers',
        'FromName' => 'Offers',
        'Link' => 'Offers',
        'Subject' => 'Offers',
        'Suppression' => 'Offers',
        'Vertical' => 'Verticals',
        'DataList' => 'DataLists',
        'Blacklist' => 'DataLists',
        'DataProvider' => 'DataProviders',
        'MtaProcess' => 'MtaProcesses',
        'SmtpProcess' => 'SmtpProcesses',
        'AutoResponder' => 'AutoResponders',
        'PmtaProcess' => 'Pmta',
        'Header' => 'Headers',
        'Team' => 'Teams',
        'TeamAuthorisation' => 'Teams'
    ]; 
}