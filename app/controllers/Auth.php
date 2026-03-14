<?php declare(strict_types=1); namespace IR\App\Controllers; if (!defined('IR_START')) exit('<pre>No direct script access allowed</pre>');
/**
 * @framework       iResponse Framework 
 * @version         1.0
 * @author          Amine Idrissi <contact@iresponse.tech>
 * @date            2024
 * @name            Auth.php	
 */

# core 
use IR\Core\Application as Application;

# mvc 
use IR\Mvc\Controller as Controller;

# helpers 
use IR\App\Helpers\Authentication as Authentication;
use IR\App\Helpers\Page as Page;

# models
use IR\App\Models\Admin\User as User;

# http 
use IR\Http\Request as Request;

/**
 * @name Auth
 * @description Auth Controller
 */
class Auth extends Controller
{
    /**
     * @app
     * @readwrite
     */
    public $app;
    
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
    }
    
    /**
     * @name main
     * @description the main action
     * @before init
     * @after closeConnections
     */
    public function main() 
    { 
        # redirect to login page 
        Page::redirect($this->app->http->request->getBaseURL() . RDS . 'auth' . RDS . 'login.' . DEFAULT_EXTENSION);
    }
    
    /**
     * @name login
     * @description the login action
     * @before init
     * @after closeConnections
     */
    public function login() 
    {
        # prevent master view 
        $this->showMasterView = false;
        
        # check authentication
        if(Authentication::isUserAuthenticated())
        {
            Page::redirect($this->app->http->request->getBaseURL() . RDS . DEFAULT_CONTROLLER . '.' . DEFAULT_EXTENSION);
        }

        # check if a form has been submitted
        $email = $this->app->http->request->retrieve('email',Request::POST); 
        $password = $this->app->http->request->retrieve('password',Request::POST);

        if(isset($email) && filter_var($email,FILTER_VALIDATE_EMAIL))
        {
            $flag = 'error';
            $message = 'Incorrect email or password !';
        
            $user = User::first(User::FETCH_OBJECT,['email = ?',$email]);

            if(isset($user) && is_object($user) && $user instanceof User && password_verify($password,$user->getPassword())) 
            { 
                # remove the password from the session 
                $user->setPassword('');

                # check if this user is activated or not 
                if($user->getStatus() != 'Activated')
                {
                    $flag = 'error';
                    $message = 'This user is inactivated !';
                }
                else
                {
                    $userRoles = $this->app->database('system')->query()->from('admin.users_roles')->where('user_id = ?',$user->getId())->count();
                
                    if($user->getMasterAccess() != 'Enabled' && $userRoles == 0)
                    {
                        $flag = 'error';
                        $message = 'This user has no role in this application !';
                    }
                    else
                    {
                        if($user->getAvatarName() == "" || $user->getAvatarName() == null)
                        {
                            $user->setAvatarName("default.png");
                        }

                        # register the user in the session 
                        Authentication::registerUser($user);

                        # redirect to dashboard page 
                        Page::redirect($this->app->http->request->getBaseURL() . RDS . DEFAULT_CONTROLLER . '.' . DEFAULT_EXTENSION);
                    }
                }  
            } 
            
            # check for message 
            $this->checkForMessage($message,$flag,false);
        }
        
        // distroy empty session
        $this->app->http->session->disconnect();
    }
    
    /**
     * @name logout
     * @description the logout action
     * @before init
     * @after closeConnections
     */
    public function logout() 
    {
        $this->showMasterView = false;
        $this->app->http->session->disconnect();
        Page::redirect($this->app->http->request->getBaseURL() . RDS . 'auth' . RDS . 'login.' . DEFAULT_EXTENSION);
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
     * @name closeConnections
     * @description close all connections
     * @once
     * @protected
     */
    public function checkForMessage($message,$flag,$isMaster) 
    {
        $button = $flag == 'error' ? 'btn-danger' : 'btn-primary';

        $html = '<script>iResponse.alertBox({title:"' . $message . '",type:"' . $flag . '",allowOutsideClick:"true",confirmButtonClass:"' . $button . '"});</script>';

        # set the message into the template data system 
        if($isMaster == true)
        {
            $this->masterView->set('prev_action_message',$html);
        }
        else
        {
            $this->pageView->set('prev_action_message',$html);
        }
    }
}