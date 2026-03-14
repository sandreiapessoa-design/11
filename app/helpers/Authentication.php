<?php declare(strict_types=1); namespace IR\App\Helpers; if (!defined('IR_START')) exit('<pre>No direct script access allowed</pre>');
/**
 * @framework       iResponse Framework 
 * @version         1.0
 * @author          Amine Idrissi <contact@iresponse.tech>
 * @date            2024
 * @name            Authentication.php	
 */

# core 
use IR\Core\Application as Application;

# models 
use IR\App\Models\Admin\User as User;
use IR\App\Models\Admin\Role as Role;
use IR\App\Models\Admin\UserRole as UserRole;
use IR\App\Models\Admin\Permission as Permission;

# session
use IR\Http\AppSessionHandler as AppSessionHandler;

/**
 * @name Authentication
 * @description Authentication Helper
 */
class Authentication
{
    /**
     * @name registerUser
     * @description store the current authenticated user in the session
     * @access public
     * @return
     */
    public static function registerUser(User $user) 
    {
        if($user != null)
        {
            # save permissions
            $userPermissions = [];
            
            if($user->getMasterAccess() != 'Enabled')
            {
                $userRoles = UserRole::all(UserRole::FETCH_ARRAY,['user_id = ?',intval($user->getId())]);

                if(count($userRoles))
                {
                    $rolesIds = [];

                    foreach ($userRoles as $userRole) 
                    {
                        if(count($userRole))
                        {
                            $rolesIds[] = intval($userRole['role_id']);
                        }
                    }

                    if(count($rolesIds))
                    {
                        $permissions = Permission::all(Permission::FETCH_ARRAY,['role_id IN ?',[$rolesIds]]);

                        if(count($permissions))
                        {
                            foreach ($permissions as $permission) 
                            {
                                if(count($permission))
                                {
                                    $userPermissions[$permission['permission_key']] = $permission;
                                }
                            }
                            
                            $user->setPermissions($userPermissions);
                        }
                    }
                }
            }
            
            Application::getCurrent()->http->session->set('ir_logged_user',$user);
        }
    }
    
    /**
     * @name checkUserRoles
     * @description update user roles and auths
     * @access public
     * @return
     */
    public static function checkUserRoles() 
    {
        $user = self::getAuthenticatedUser();
        
        if($user != null)
        {
            # save permissions
            $userPermissions = [];
            
            if($user->getMasterAccess() != 'Enabled')
            {
                $rolesIds = [];
                $userRoles = UserRole::all(UserRole::FETCH_ARRAY,['user_id = ?',intval($user->getId())]);

                if(count($userRoles))
                {
                    
                    foreach ($userRoles as $userRole) 
                    {
                        if(count($userRole))
                        {
                            $rolesIds[] = intval($userRole['role_id']);
                        }
                    }

                    if(count($rolesIds))
                    {
                        $permissions = Permission::all(Permission::FETCH_ARRAY,['role_id IN ?',[$rolesIds]]);

                        if(count($permissions))
                        {
                            foreach ($permissions as $permission) 
                            {
                                if(count($permission))
                                {
                                    $userPermissions[$permission['permission_key']] = $permission;
                                }
                            }
                            
                            $user->setPermissions($userPermissions);
                        }
                    }
                }
                
                if(count($rolesIds))
                {
                    $roles = Role::all(Role::FETCH_ARRAY,['id in ?',[$rolesIds]]);
                
                    if(count($roles))
                    {
                        $names = [];

                        foreach ($roles as $role) 
                        {
                            $names[] = $role['name'];
                        }

                        $user->setRoles($names);
                    }
                }
            }
            
            Application::getCurrent()->http->session->set('ir_logged_user',$user);
        }
    }
    
    
    /**
     * @name getAuthenticatedUser
     * @description get the current authentiacted user's information
     * @access public
     * @return Application
     */
    public static function getAuthenticatedUser()
    {
        return Application::getCurrent()->http->session->get('ir_logged_user') != null 
        && Application::getCurrent()->http->session->get('ir_logged_user') instanceof User 
        ? Application::getCurrent()->http->session->get('ir_logged_user') : new User();
    }
    
    /**
     * @name getAllAuthenticatedUsers
     * @description get all authentiacted users information
     * @access public
     * @return Application
     */
    public static function getAllAuthenticatedUsers() : array
    {
        $users = [];
        $sessions = Application::getCurrent()->utils->fileSystem->scanDir(SESSIONS_PATH);
        
        foreach ($sessions as $session) 
        {
            $content = Application::getCurrent()->utils->fileSystem->readFile(SESSIONS_PATH . DS . $session);
            $unserialized = Application::getCurrent()->http->session->unserialize(Application::getCurrent()->utils->encryptor->decrypt($content,AppSessionHandler::$_SESSION_KEY));
            
            if(count($unserialized) && key_exists('ir_logged_user',$unserialized) && $unserialized['ir_logged_user'] instanceof User)
            {
                $users[$session] = $unserialized['ir_logged_user'];
            }
        }
        
        return $users;
    }
    
    /**
     * @name isUserAuthenticated
     * @description checks if there is a user authenticated
     * @access public
     * @return bool
     */
    public static function isUserAuthenticated() : bool
    {
        return Application::getCurrent()->http->session->get('ir_logged_user') != null;
    }
    
    /**
     * @name isTrackingUser
     * @description checks if there is a tracking user authenticated
     * @access public
     * @return bool
     */
    public static function isTrackingUser() : bool
    {
        return self::isUserAuthenticated() && self::getAuthenticatedUser() != null && self::getAuthenticatedUser()->getIsTrackingUser() == true;
    }
    
    /**
     * @name getUserPermissions
     * @description get user permissions
     * @access public
     * @return array
     */
    public static function getUserPermissions() : array
    {
        return Application::getCurrent()->http->session->get('ir_logged_user_permissions');
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
}