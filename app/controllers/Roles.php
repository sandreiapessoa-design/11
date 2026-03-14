<?php declare(strict_types=1); namespace IR\App\Controllers; if (!defined('IR_START')) exit('<pre>No direct script access allowed</pre>');
/**
 * @framework       iResponse Framework 
 * @version         1.0
 * @author          Amine Idrissi <contact@iresponse.tech>
 * @date            2024
 * @name            Roles.php	
 */

# core 
use IR\Core\Application as Application;

# mvc 
use IR\Mvc\Controller as Controller;

# models
use IR\App\Models\Admin\User as User;
use IR\App\Models\Admin\Role as Role;
use IR\App\Models\Admin\Permission as Permission;
use IR\App\Models\Admin\UserRole as UserRole;

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
 * @name Roles
 * @description Roles Controller
 */
class Roles extends Controller
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
            'role_type',
            'status',
            'created_by',
            'created_date'
        ];
        
        # creating the html part of the list 
        $columns = Page::createTableHeader($columnsArray);
        $filters = Page::createTableFilters($columnsArray);
            
        # set menu status
        $this->masterView->set([
            'roles' => 'true',
            'roles_show' => 'true'
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
                'role_type',
                'status',
                'created_by',
                'created_date'
            ];
            
            # fetching the results to create the ajax list
            die(json_encode(DataTable::init($data,'admin.roles',$columns,new Role(),'roles','ASC')));
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
            'roles' => 'true',
            'roles_add' => 'true'
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
            'roles' => 'true',
            'roles_show' => 'true'
        ]);
        
        if(!isset($id) || !is_numeric($id) || intval($id) == 0)
        {
            $valid = false;
        }
        
        $role = Role::first(Role::FETCH_ARRAY,['id = ?',$id]);
        
        if(count($role) == 0)
        {
            $valid = false;
        }
        
        if($valid == true)
        {
            $permissions = [];
            $result = Permission::all(Permission::FETCH_ARRAY,['role_id = ?',$id],['permission_key']);
            
            if(count($result))
            {
                foreach ($result as $value) 
                {
                    if(count($value) && key_exists('permission_key',$value))
                    {
                        $permissions[] = $value['permission_key'];
                    }
                }
            }
            
            # set data to the page view
            $this->pageView->set([
                'role' => $role,
                'permissions' => $permissions
            ]); 
        }
        else
        {
            # stores the message in the session 
            Page::registerMessage('error','Invalid role id !');
            
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
            $role = new Role();
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
                $role->setId(intval($this->app->utils->arrays->get($data,'id')));
                $role->load();
                $role->setLastUpdatedBy($username);
                $role->setLastUpdatedDate(date('Y-m-d'));
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
                $role->setCreatedBy($username);
                $role->setCreatedDate(date('Y-m-d'));
                $role->setLastUpdatedBy($username);
                $role->setLastUpdatedDate(date('Y-m-d'));
            }

            $role->setName($this->app->utils->arrays->get($data,'name'));
            $role->setStatus($this->app->utils->arrays->get($data,'status'));
            $role->setRoleType($this->app->utils->arrays->get($data,'type'));
            $result = $update == false ? $role->insert() : $role->update(); 

            if($result > -1)
            {
                $roleId = ($this->app->utils->arrays->get($data,'id') > 0) ? intval($this->app->utils->arrays->get($data,'id')) : intval($result);
                
                # delete old permissions if any 
                $this->app->database('system')->query()->from('admin.permissions')->where('role_id = ?',$roleId)->delete();
                
                $permissions = $this->app->utils->arrays->get($data,'permissions');
                $map = json_decode($this->app->utils->fileSystem->readFile(CONFIGS_PATH . DS . 'permissions.map.json'),true);
                
                if(is_array($permissions) && count($permissions))
                {
                    foreach ($permissions as $permissionKey) 
                    {
                        if(key_exists($permissionKey,$map))
                        {
                            $permission = new Permission();
                            $permission->setRoleId($roleId);
                            $permission->setRoleName($role->getName());
                            $permission->setController($map[$permissionKey]['controller']);
                            $permission->setMethod($map[$permissionKey]['method']);
                            $permission->setParents($map[$permissionKey]['parents']);
                            $permission->setPermissionKey($permissionKey);
                            $permission->setCreatedBy($username);
                            $permission->setCreatedDate(date('Y-m-d'));
                            $permission->setLastUpdatedBy($username);
                            $permission->setLastUpdatedDate(date('Y-m-d'));
                            $permission->insert();
                        }
                    }
                }
                
                $flag = 'success';
            }
        }
        
        # stores the message in the session 
        Page::registerMessage($flag, $message);

        # redirect to lists page
        Page::redirect();
    }
    
    /**
     * @name affect
     * @description the affect action
     * @before init
     * @after closeConnections,checkForMessage
     */
    public function affect() 
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
            $username = $this->authenticatedUser->getEmail();
            $message = 'Internal server error !';
            $flag = 'error';
        
            $roleId = intval($this->app->utils->arrays->get($data,'role-id'));
            $users = $this->app->utils->arrays->get($data,'users');
            
            if($roleId > 0)
            {
                # delete old users
                $this->app->database('system')->query()->from('admin.users_roles')->where('role_id = ?',$roleId)->delete();
                    
                if($users != null && is_array($users) && count($users))
                {
                    foreach ($users as $userId) 
                    {
                        $userRole = new UserRole();
                        $userRole->setRoleId($roleId);
                        $userRole->setUserId(intval($userId));
                        $userRole->setCreatedBy($username);
                        $userRole->setCreatedDate(date('Y-m-d'));
                        $userRole->setLastUpdatedBy($username);
                        $userRole->setLastUpdatedDate(date('Y-m-d'));
                        $userRole->insert();
                    }

                    $message = 'Role affectation updated successfully !';
                    $flag = 'success';
                }
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
                'roles' => 'true',
                'roles_affect' => 'true'
            ]);
        
            # set data to the page view
            $this->pageView->set([
                'users' => User::all(User::FETCH_ARRAY,['master_access = ? AND status = ?',['Disabled','Activated']]),
                'roles' => Role::all(Role::FETCH_ARRAY,['status = ?','Activated'])
            ]);
        }
    }
    
    /**
     * @name users
     * @description the roles action
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
            $username = $this->authenticatedUser->getEmail();
            $message = 'Internal server error !';
            $flag = 'error';
        
            $userId = intval($this->app->utils->arrays->get($data,'user-id'));
            $roles = $this->app->utils->arrays->get($data,'roles');
            
            if($userId > 0)
            {
                # delete old roles
                $this->app->database('system')->query()->from('admin.users_roles')->where('user_id = ?',$userId)->delete();
                    
                foreach ($roles as $roleId) 
                {
                    $userRole = new UserRole();
                    $userRole->setRoleId($roleId);
                    $userRole->setUserId(intval($userId));
                    $userRole->setCreatedBy($username);
                    $userRole->setCreatedDate(date('Y-m-d'));
                    $userRole->setLastUpdatedBy($username);
                    $userRole->setLastUpdatedDate(date('Y-m-d'));
                    $userRole->insert();
                }

                $message = 'User roles affectation updated successfully !';
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
                'roles' => 'true',
                'users_roles' => 'true'
            ]);
        
            # set data to the page view
            $this->pageView->set([
                'users' => User::all(User::FETCH_ARRAY,['master_access = ? AND status = ?',['Disabled','Activated']]),
                'roles' => Role::all(Role::FETCH_ARRAY,['status = ?','Activated'])
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