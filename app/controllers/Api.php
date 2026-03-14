<?php declare(strict_types=1); namespace IR\App\Controllers; if (!defined('IR_START')) exit('<pre>No direct script access allowed</pre>');
/**
 * @framework       iResponse Framework 
 * @version         1.0
 * @author          Amine Idrissi <contact@iresponse.tech>
 * @date            2024
 * @name            Api.php	
 */

# core 
use IR\Core\Application as Application;

# mvc 
use IR\Mvc\Controller as Controller;

# http
use IR\Http\Request as Request;

# helpers
use IR\App\Helpers\Page as Page;

/**
 * @name Api
 * @description Api Controller
 */
class Api extends Controller
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
        
        # connect to the database 
        $this->app->database('system')->connect();
        
        # prevent layout
        $this->showMasterView = false;
    }
    
    /**
     * @name main
     * @description the main action
     * @before init
     * @after closeConnections
     */
    public function main() 
    { 
        # get post data
        $data = $this->app->http->request->retrieve(Request::ALL,Request::POST);
        
        if(count($data) == 0)
        {
            Page::printApiResults(500,'Our system detected no parameters passed !');
        }
        
        try
        {
            $controller = $this->app->utils->arrays->get($data,'controller');
            $action = $this->app->utils->arrays->get($data,'action');
            $parameters = $this->app->utils->arrays->get($data,'parameters',[]);
                    
            if($controller == null || strlen($controller) == 0)
            {
                Page::printApiResults(404,'Webservice controller not found !');
            }
            
            $fileName = ($this->app->utils->strings->contains($controller,'-')) ? str_replace(' ','',ucwords(str_replace('-',' ',$controller))) : ucfirst($controller);

            $class = FW_ABBR . ANS . 'App' . ANS . 'Webservices' . ANS . $fileName; 

            # check if the controller exists
            if(!file_exists(BASE_PATH . DS . 'app' . DS . 'webservices' . DS . $fileName . ".php"))
            {
                Page::printApiResults(404,'Webservice controller not found !');
            }
        
            # loading the controller
            $instance = new $class();

            if($instance != null)
            {
                if($action == null || !method_exists($instance,strval($action)))
                {
                    Page::printApiResults(404,'webservice action not found !');
                }
                
                $methodMeta = $this->app->utils->inspector->methodMeta($class,$action);

                if (!empty($methodMeta["@protected"]) || !empty($methodMeta["@private"])) 
                {
                    Page::printApiResults(403,"Action {$action} is not accessible !");
                }

                $hooks = function($meta, $type) use ($class,$instance) 
                {
                    if (isset($meta[$type])) 
                    {
                        $run = [];

                        foreach ($meta[$type] as $method) 
                        {
                            $hookMeta = $this->app->utils->inspector->methodMeta($class,$method);

                            if (in_array($method, $run) && !empty($hookMeta["@once"])) 
                            {
                                continue;
                            }

                            $instance->$method();
                            $run[] = $method;
                        }
                    }
                };
            
                # calling "before" hook function
                $hooks($methodMeta, "@before");

                # executing the main action requested from the url 
                $instance->{$action}($parameters);

                # calling "after" hook function
                $hooks($methodMeta, "@after");  
            }
        } 
        catch (\Throwable $e)
        {
            Page::printApiResults(500,'Internal server error !',[],$e);
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
}


