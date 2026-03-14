<?php declare(strict_types=1); namespace IR\App\Controllers; if (!defined('IR_START')) exit('<pre>No direct script access allowed</pre>');
/**
 * @framework       iResponse Framework 
 * @version         1.0
 * @author          Amine Idrissi <contact@iresponse.tech>
 * @date            2024
 * @name            Errors.php	
 */

# core 
use IR\Core\Application as Application;

# mvc 
use IR\Mvc\Controller as Controller;
use IR\Mvc\View as View;

/**
 * @name Errors
 * @description Errors Controller
 */
class Errors extends Controller
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
    }
    
    /**
     * @name main
     * @description the main action
     * @before init
     */
    public function main() 
    { 
        $this->showErrorPage();
    }
    
    /**
     * @name showErrorPage
     * @description the showErrorPage action
     * @before init
     */
    public function showErrorPage() 
    { 
        # set the current application to a local variable
        $this->app = Application::getCurrent();
        
        # get the exception from the database
        $exception = $this->app->http->session->get('mb-exception',true);
        
        # disconnect from the databases
        $this->app->database('system')->disconnect();
        $this->app->database('clients')->disconnect();
        
        # initialize error page info 
        $message = "Internal server error !";
        $code = 500;
        
        if(isset($exception) && is_object($exception))
        {
            $message = $exception->getMessage();
            $code = $exception->getCode();
        }
        
        $code = $code < 400 ? 500 : $code;
        
        $this->app->router->controller = 'errors';
        $this->app->router->action = 'main';
        
        # create a new view as an error action view and fill it with data 
        $message = str_replace('Uncaught Error: ','', $message);
        $message = explode(' in ',$message);
        $message = is_array($message) ? trim($message[0]) : trim($message);
        
        $view = new View(['file' => VIEWS_PATH . DS . 'errors' . DS . 'main.html']);
        $this->fillViewDefaults($view);
        
        $view->set('code',$code);
        $view->set('message',$message);
        
        $this->masterView = $view;
        $this->pageView = null;
        $this->render();
    }
}


