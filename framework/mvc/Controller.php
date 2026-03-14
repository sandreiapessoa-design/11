<?php declare(strict_types=1); namespace IR\Mvc; if (!defined('IR_START')) exit('<pre>No direct script access allowed</pre>');
/**
 * @framework       iResponse Framework 
 * @version         1.0
 * @author          Amine Idrissi <contact@iresponse.tech>
 * @date            2024
 * @name            Controller.php	
 */

# core
use IR\Core\Base as Base;
use IR\Core\Application as Application;

# routing
use IR\Routing\Router as Router;

# templating
use IR\Mvc\View as View;

# helpers 
use IR\App\Helpers\Authentication as Authentication;

# exceptions
use IR\Exceptions\Types\SystemException as SystemException;

/**
 * @name Controller
 * @description Controller class
 */
class Controller extends Base
{
    /**
     * @name __construct
     * @description the class constructor
     * @access public
     * @param array $options
     * @return Controller
     */
    public function __construct(array $options = []) 
    {
        parent::__construct($options);

        if ($this->showMasterView == true) 
        {
            # Master view
            $view = new View([ "file" => VIEWS_PATH . DS . $this->masterTemplate .  '.' . $this->defaultExtension ]);
            $this->fillViewDefaults($view);
            $this->masterView = $view;
        }
        
        if ($this->showPageView == true && Application::isValid()) 
        {
            # Page view
            if(Application::getCurrent()->router != null && Application::getCurrent()->router instanceof Router)
            {
                 $view = new View([ "file" => VIEWS_PATH . DS . Application::getCurrent()->router->controller . DS . Application::getCurrent()->router->action .  '.' . $this->defaultExtension ]);
                 $this->fillViewDefaults($view);
                 $this->pageView = $view;
            }              
        }
    }
    
    /**
     * @name render
     * @description an automatic view rendering, will first render the current action’s view, and then the layout’s view
     * @access public
     * @return
     * @throws BackendException
     */
    public function render() 
    {
        $results = '';
 
        try 
        {
            # master view case
            if($this->showMasterView == true)
            {
                # check if there is a page view or not
                if($this->showPageView == true && $this->pageView != null)
                {
                    $this->masterView->set("pageView",$this->pageView->render());
                }

                $results = $this->masterView->render();
            }
            else 
            {
                if($this->showPageView == true)
                {
                    $results = $this->pageView->render();
                }
            }    
           
            # render the result 
            header("Content-type: {$this->defaultContentType}");
            echo $results;

            # prevent multiple pages shown
            $this->showMasterView = false;
            $this->showPageView = false;
        } 
        catch (\Throwable $e) 
        {
            throw new SystemException($e->getMessage(),$e->getCode(),$e);
        }
    }
    
    /**
     * @name fillViewDefaults
     * @description fills a view with default data ( application info , user info ..... )
     * @access protected
     * @return
     */
    protected function fillViewDefaults(View &$view)
    {
        if(Application::getCurrent() instanceof Application)
        {
            $view->set('app',Application::getCurrent()->getSetting('application')); 
            $view->set('utils',Application::getCurrent()->utils);
            $view->set('http',Application::getCurrent()->http);
            
            if(Application::getCurrent()->router != null)
            {
                $view->set('router',[
                    'controller' => Application::getCurrent()->router->controller,
                    'controller_class' => (Application::getCurrent()->utils->strings->contains(strval(Application::getCurrent()->router->controller),'-')) ? str_replace(' ','',ucwords(str_replace('-',' ',strval(Application::getCurrent()->router->controller)))) : ucfirst(strval(Application::getCurrent()->router->controller)),
                    'action' => Application::getCurrent()->router->action,
                    'action_method' => (Application::getCurrent()->utils->strings->contains(strval(Application::getCurrent()->router->action),'-')) ? str_replace(' ','',ucwords(str_replace('-',' ', strval(Application::getCurrent()->router->action)))) : ucfirst(strval(Application::getCurrent()->router->action)),
                    'url' => Application::getCurrent()->router->url,
                    'parameters' => Application::getCurrent()->router->parameters
                ]);
            }
            
            $urls = [
                'base_url' => Application::getCurrent()->http->request->getBaseURL()
            ];

            if($view->get('app') != null && is_array($view->get('app')))
            {
                $view->set('app', array_merge($view->get('app'),$urls));
            }
            else
            {
                $view->set('app',$urls);
            }
            
            if(Authentication::getAuthenticatedUser() != null)
            {
                $view->set('connectedUser',Authentication::getAuthenticatedUser()); 
            }
        }
    }

    /**
     * @readwrite
     * @access protected 
     * @var array
     */
    public $parameters;

    /**
     * @readwrite
     * @access protected 
     * @var View
     */
    public $masterView;

    /**
     * @readwrite
     * @access protected 
     * @var View
     */
    public $pageView;

    /**
     * @readwrite
     * @access protected 
     * @var boolean
     */
    public $showMasterView = true;

    /**
     * @readwrite
     * @access protected 
     * @var boolean
     */
    public $showPageView = true;

    /**
     * @readwrite
     * @access protected 
     * @var string
     */
    public $masterTemplate = "master";

    /**
     * @readwrite
     * @access protected 
     * @var string
     */
    public $defaultExtension = "html";

    /**
     * @readwrite
     * @access protected 
     * @var string
     */
    public $defaultContentType = "text/html";
}


