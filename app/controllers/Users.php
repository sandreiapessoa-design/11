<?php declare(strict_types=1); namespace IR\App\Controllers; if (!defined('IR_START')) exit('<pre>No direct script access allowed</pre>');
/**
 * @framework       iResponse Framework 
 * @version         1.0
 * @author          Amine Idrissi <contact@iresponse.tech>
 * @date            2024
 * @name            Users.php	
 */

# core 
use IR\Core\Application as Application;

# mvc 
use IR\Mvc\Controller as Controller;

# models
use IR\App\Models\Admin\User as User;

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
 * @name Users
 * @description Users Controller
 */
class Users extends Controller
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
            'production_id',
            'first_name',
            'last_name',
            'user_type',
            'status',
            'email',
            'created_by',
            'created_date'
        ];
        
        # creating the html part of the list 
        $columns = Page::createTableHeader($columnsArray);
        $filters = Page::createTableFilters($columnsArray);
           
        # set menu status
        $this->masterView->set([
            'users_and_roles' => 'true',
            'users' => 'true',
            'users_show' => 'true'
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
                'production_id',
                'first_name',
                'last_name',
                "CASE WHEN master_access='Enabled' THEN 'Super User' ELSE 'Normal User' END" => 'user_type',
                'status',
                'email',
                'created_by',
                'created_date'
            ];
            
            # fetching the results to create the ajax list
            die(json_encode(DataTable::init($data,'admin.users',$columns,new User(),'users','ASC')));
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
            'users_and_roles' => 'true',
            'users' => 'true',
            'users_add' => 'true'
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
            'users_and_roles' => 'true',
            'users' => 'true',
            'users_show' => 'true'
        ]);
        
        if(!isset($id) || !is_numeric($id) || intval($id) == 0)
        {
            $valid = false;
        }
        
        $user = User::first(User::FETCH_ARRAY,['id = ?',$id]);
        
        if(count($user) == 0)
        {
            $valid = false;
        }
        
        if($valid == true)
        {
            # set data to the page view
            $this->pageView->set([
                'user' => $user
            ]); 
        }
        else
        {
            # stores the message in the session 
            Page::registerMessage('error','Invalid user id !');
            
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
        $files = $this->app->http->request->retrieve(Request::ALL,Request::FILES);
        
        $message = 'Internal server error !';
        $flag = 'error';

        if(count($data))
        {        
            $update = false;
            $user = new User();
            $username = $this->authenticatedUser->getEmail();

            # update case
            if($this->app->utils->arrays->get($data,'id') > 0)
            {
                # check for permissions
                if($this->authenticatedUser->getId() != $this->app->utils->arrays->get($data,'id'))
                {
                    # check for permissions
                    $access = Permissions::checkForAuthorization($this->authenticatedUser,__CLASS__,'edit');

                    if($access == false)
                    {
                        throw new PageException('Access Denied !',403);
                    }
                }
                
                $update = true;
                $message = 'Record updated succesfully !';
                $user->setId(intval($this->app->utils->arrays->get($data,'id')));
                $user->load();
                $user->setLastUpdatedBy($username);
                $user->setLastUpdatedDate(date('Y-m-d'));
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
                $user->setCreatedBy($username);
                $user->setCreatedDate(date('Y-m-d'));
                $user->setLastUpdatedBy($username);
                $user->setLastUpdatedDate(date('Y-m-d'));
            }

            if($this->app->utils->arrays->get($data,'production-id') != 0)
            {
                $user->setProductionId(intval($this->app->utils->arrays->get($data,'production-id')));
                $user->setFirstName(ucwords($this->app->utils->arrays->get($data,'first-name')));
                $user->setLastName(ucwords($this->app->utils->arrays->get($data,'last-name')));
                $user->setMasterAccess($this->app->utils->arrays->get($data,'superuser-status','Disabled'));
                $user->setStatus($this->app->utils->arrays->get($data,'status','Activated'));
                $user->setEmail($this->app->utils->arrays->get($data,'email'));

                if($this->app->utils->arrays->get($data,'password') != null && $this->app->utils->arrays->get($data,'password') != '')
                {
                    $user->setPassword(password_hash($this->app->utils->arrays->get($data,'password'),PASSWORD_BCRYPT));
                }

                if(count($this->app->utils->arrays->get($files,'avatar-image')))
                {
                    $ext = $this->app->utils->arrays->last(explode('.',$this->app->utils->arrays->get($this->app->utils->arrays->get($files,'avatar-image'),'name')));
                    $tmpFileName = $this->app->utils->arrays->get($this->app->utils->arrays->get($files,'avatar-image'),'tmp_name');

                    if($tmpFileName != null && $tmpFileName != '')
                    {
                        $imageName = $this->app->utils->strings->random(10,true,true,true,false) . '.' .  $ext;
                        $this->app->utils->fileSystem->moveFileOrDirectory($tmpFileName,PUBLIC_PATH . DS . 'images' . DS . 'avatars' . DS . $imageName);
                        $user->setAvatarName($imageName);
                    }
                }
                
                $result = $update == false ? $user->insert() : $user->update(); 

                if($result > -1)
                {
                    $flag = 'success';
                }
            }
            else
            {
                $message = 'User should not have 0 as a production id !';
                $flag = 'error';
            }
        }

        # stores the message in the session 
        Page::registerMessage($flag, $message);

        # redirect to previous page 
        Page::redirect();
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