<?php declare(strict_types=1); namespace IR\App\Webservices; if (!defined('IR_START')) exit('<pre>No direct script access allowed</pre>');
/**
 * @framework       iResponse Framework 
 * @version         1.0
 * @author          Amine Idrissi <contact@iresponse.tech>
 * @date            2024
 * @name            Roles.php	
 */

# core 
use IR\Core\Base as Base;
use IR\Core\Application as Application;

# helpers 
use IR\App\Helpers\Authentication as Authentication;
use IR\App\Helpers\Permissions as Permissions;
use IR\App\Helpers\Page as Page;

# models
use IR\App\Models\Admin\User as User;
use IR\App\Models\Admin\Role as Role;
use IR\App\Models\Admin\UserRole as UserRole;

/**
 * @name Roles
 * @description Roles WebService
 */
class Roles extends Base
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
     * @name getRoleUsers
     * @description get role users action
     * @before init
     */
    public function getRoleUsers($parameters = []) 
    { 
        # check for authentication
        if(!Authentication::isUserAuthenticated())
        {
            Page::printApiResults(401,'Only logged-in access allowed !');
        }
        
        # check users roles 
        Authentication::checkUserRoles();
        
        # check for permissions
        $access = Permissions::checkForAuthorization(Authentication::getAuthenticatedUser(),'Roles','affect');

        if($access == false)
        {
            Page::printApiResults(403,'Access Denied !');
        }
        
        $roleId = intval($this->app->utils->arrays->get($parameters,'role-id'));

        if($roleId > 0)
        {
            $role = Role::first(Role::FETCH_ARRAY,['status = ? and id = ?',['Activated',$roleId]]);

            if(count($role) == 0)
            {
                Page::printApiResults(500,'Role not found !');
            }

            $rolesUsers = UserRole::all(UserRole::FETCH_ARRAY,['role_id = ?',$roleId]);

            $usersIds = [];

            foreach ($rolesUsers as $rolesUser) 
            {
                if(count($rolesUser))
                {
                    $usersIds[] = intval($rolesUser['user_id']);
                }
            }

            Page::printApiResults(200,'',['users' => $usersIds]);
        }
        else
        {
            Page::printApiResults(500,'Incorrect role id !');
        }
    }
    
    /**
     * @name getUserRoles
     * @description get user roles action
     * @before init
     */
    public function getUserRoles($parameters = []) 
    { 
        # check for authentication
        if(!Authentication::isUserAuthenticated())
        {
            Page::printApiResults(401,'Only logged-in access allowed !');
        }
        
        # check users roles 
        Authentication::checkUserRoles();
        
        # check for permissions
        $access = Permissions::checkForAuthorization(Authentication::getAuthenticatedUser(),'Roles','users');

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

            $userRoles = UserRole::all(UserRole::FETCH_ARRAY,['user_id = ?',$userId]);

            $rolesIds = [];

            foreach ($userRoles as $rolesUser) 
            {
                if(count($rolesUser))
                {
                    $rolesIds[] = intval($rolesUser['role_id']);
                }
            }

            Page::printApiResults(200,'',['roles' => $rolesIds]);
        }
        else
        {
            Page::printApiResults(500,'Incorrect user id !');
        }
    }
}


