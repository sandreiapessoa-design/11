<?php declare(strict_types=1); namespace IR\Routing; if (!defined('IR_START')) exit('<pre>No direct script access allowed</pre>');
/**
 * @framework       iResponse Framework 
 * @version         1.0
 * @author          Amine Idrissi <contact@iresponse.tech>
 * @date            2024
 * @name            Router.php	
 */

# core 
use IR\Core\Base as Base;
use IR\Core\Application as Application;

# utilities 
use IR\Utils\Types\Strings as Strings;

# exceptions
use IR\Exceptions\Types\PageException as PageException;

/**
 * @name Router
 * @description Router class
 */
class Router extends Base
{
    /**
     * @name dispatch
     * @description dispatches the request 
     * @access public
     * @return
     */
    public function dispatch() 
    {
        $this->controller = DEFAULT_CONTROLLER;
        $this->action = DEFAULT_ACTION;
        $this->parameters = [];

        $parts = explode("/", trim($this->url, "/"));
        
        if (sizeof($parts) > 0) 
        {
            $this->controller = $parts[0];  

            if (sizeof($parts) > 1) 
            {
                $this->action = $parts[1];
                $this->parameters = array_slice($parts,2);
            }
        }

        $this->pass();
    }
    
    /**
     * @name pass
     * @description gets the controller name , action name and loads the appropriate controller object and call the action method , It calls also hooks methods if defined 
     * @access public
     * @return
     * @throws PageException
     */
    protected function pass() 
    {     
        $fileName = (Strings::getInstance()->contains($this->controller,'-')) ? str_replace(' ','',ucwords(str_replace('-',' ', $this->controller))) : ucfirst($this->controller);
        $action = (Strings::getInstance()->contains($this->action,'-')) ? lcfirst(str_replace(' ','',ucwords(str_replace('-',' ', $this->action)))) : $this->action;
        
        $class = FW_ABBR . ANS . 'App' . ANS . 'Controllers' . ANS . $fileName; 

        # check if the controller exists
        if(!file_exists(BASE_PATH . DS . 'app' . DS . 'controllers' . DS . $fileName . ".php"))
        {
            throw new PageException(Application::getCurrent()->http->request->getRequestURL() . ' : Page Not Found',404);
        }

        # loading the controller
        $instance = new $class();
        
        if($instance != null)
        {
            $instance->parameters =  $this->parameters;
            $instance->defaultExtension = $this->extension;
            $instance->defaultContentType = 'text/' . $this->extension;

            if (!method_exists($instance,$action)) 
            {
                $instance->showMasterView = false;
                $instance->showPageView = false;
                
                throw new PageException("Page {$this->action} not found",404);
            }

            $methodMeta = Application::getCurrent()->utils->inspector->methodMeta($class,$action);

            if (!empty($methodMeta["@protected"]) || !empty($methodMeta["@private"])) 
            {
                throw new PageException("Action {$this->action} not accessible from routing");
            }

            $hooks = function($meta, $type) use ($class,$instance) 
            {
                if (isset($meta[$type])) 
                {
                    $run = [];

                    foreach ($meta[$type] as $method) 
                    {
                        $hookMeta = Application::getCurrent()->utils->inspector->methodMeta($class,$method);

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
            call_user_func_array([$instance,$action],is_array($this->parameters) ? $this->parameters : []);

            # calling "after" hook function
            $hooks($methodMeta, "@after");  
            
            $this->instance = $instance;
            
            # render the page 
            $this->instance->render();
        }
    }

    /** 
     * @readwrite
     * @access public 
     * @var string
     */
    public $url;

    /** 
     * @readwrite
     * @access public 
     * @var string
     */
    public $extension;

    /** 
     * @readwrite
     * @access public 
     * @var string
     */ 
    public $controller;
    
    /** 
     * @readwrite
     * @access public 
     * @var \IR\Mvc\Controller
     */ 
    public $instance;

    /** 
     * @readwrite
     * @access public 
     * @var string
     */ 
    public $action;

    /** 
     * @readwrite
     * @access public 
     * @var array
     */ 
    public $parameters = [];
}


