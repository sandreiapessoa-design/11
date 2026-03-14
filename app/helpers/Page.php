<?php declare(strict_types=1); namespace IR\App\Helpers; if (!defined('IR_START')) exit('<pre>No direct script access allowed</pre>');
/**
 * @framework       iResponse Framework 
 * @version         1.0
 * @author          Amine Idrissi <contact@iresponse.tech>
 * @date            2024
 * @name            Page.php	
 */

# core 
use IR\Core\Application as Application;

# mvc 
use IR\Mvc\Controller as Controller;

# http 
use IR\Http\Request as Request;

# logging 
use IR\Logs\Logger as Logger;

/**
 * @name Page
 * @description Page Helper
 */
class Page
{
    /**
     * @name registerMessage
     * @description store a message in the session
     * @access public
     * @return array
     */
    public static function registerMessage(string $flag,string $message) 
    {
        Application::getCurrent()->http->session->set('process_message_flag',$flag);
        Application::getCurrent()->http->session->set('process_message',$message);
    }
    
    /**
     * @name getMessage
     * @description get the stored message in the session
     * @access public
     * @return array
     */
    public static function getMessage($delete = true) : array
    {
        return [
            'flag' => Application::getCurrent()->http->session->get('process_message_flag',$delete),
            'message' => Application::getCurrent()->http->session->get('process_message',$delete)
        ];
    }
    
    /**
     * @name checkForMessage
     * @description check if there is a message sent to the page 
     * @access static
     * @param mixed $controller
     * @return
     */
    public static function checkForMessage(Controller $controller,bool $isMaster = true)
    {
        if($controller != null)
        {
            # check if there is a message from a previous action
            $message = self::getMessage();
            $app = Application::getCurrent();
            
            if(count($message) && $app->utils->arrays->get($message,'message') != '')
            {
                $button = $app->utils->arrays->get($message,'flag') == 'error' ? 'btn-danger' : 'btn-primary';

                $html = '<script>iResponse.alertBox({title:"' . $app->utils->arrays->get($message,'message') . '",type:"' . $app->utils->arrays->get($message,'flag') . '",allowOutsideClick:"true",confirmButtonClass:"' . $button . '"});</script>';

                # set the message into the template data system 
                if($isMaster == true)
                {
                    $controller->masterView->set('prev_action_message',$html);
                }
                else
                {
                    $controller->pageView->set('prev_action_message',$html);
                }
            }
        }
    }
    
    /**
     * @name redirect
     * @description redirect
     * @access public
     * @return
     */
    public static function redirect($url = null) 
    {
        $url = ($url != null && $url != '' && filter_var($url,FILTER_VALIDATE_URL)) ? $url : Application::getCurrent()->http->request->retrieve('HTTP_REFERER',Request::SERVER);
        $url = $url != null && $url != '' && filter_var($url,FILTER_VALIDATE_URL) && Application::getCurrent()->utils->strings->indexOf($url,Application::getCurrent()->http->request->getBaseURL()) > -1 ? $url : Application::getCurrent()->http->request->getBaseURL() . RDS . DEFAULT_CONTROLLER;
        Application::getCurrent()->http->response->redirect($url);
    }
    
    /**
     * @name printApiMessage
     * @description print api message as Json
     * @access public
     * @return array
     */
    public static function printApiResults(int $code,string $message,$data = [],$exception = null) 
    {
        # log the exception
        if($exception != null)
        {
            Logger::getInstance()->error($exception);
        }

        die(json_encode(['status' => $code ,'message' => $message ,'data' => $data]));
    }
    
    /**
     * @name printApiResultsThenLogout
     * @description print api message as Json then lougout from current session
     * @access public
     * @return array
     */
    public static function printApiResultsThenLogout(int $code,string $message,$data = [],$exception = null) 
    {
        # log the exception
        if($exception != null)
        {
            Logger::getInstance()->error($exception);
        }
        
        # logout from session
        Application::getCurrent()->http->session->disconnect();
        
        die(json_encode(['status' => $code ,'message' => $message ,'data' => $data]));
    }
    
    /**
     * @name openPageInNewTab
     * @description check if we have to open a new page in a new tab
     * @access public
     * @return bool
     */
    public static function openPageInNewTab() : bool
    {
        $newPage = false;
        
        if(strtolower(Application::getCurrent()->utils->arrays->get(Application::getCurrent()->getSetting('application'),'new_tab_open')) == 'new')
        {
            $newPage = true;
        }
        
        return $newPage;
    }
    
    /**
     * @name isMenuClosed
     * @description check if the sidebar menu should be closed 
     * @access public
     * @return bool
     */
    public static function isMenuClosed() : bool
    {
        $closed = false;
        
        if(strtolower(Application::getCurrent()->utils->arrays->get(Application::getCurrent()->getSetting('application'),'sidebar_behaviour')) == 'closed')
        {
            $closed = true;
        }
        
        return $closed;
    }
       
    /**
     * @name createTableHeader
     * @description create the header part of a table
     * @access public
     * @return string
     */
    public static function createTableHeader(array $columnsArray) : string
    {
        $columns = '';
        
        foreach ($columnsArray as $column) 
        {
            if($column != 'id')
            {
                $columns .= '<th>' . ucwords(str_replace('_',' ',strtolower($column))) . '</th>' . PHP_EOL;
            }
        }
        
        return $columns;
    }
    
    
    /**
     * @name createTableFilters
     * @description create the header filters part of a table
     * @access public
     * @return string
     */
    public static function createTableFilters(array $columnsArray,array $options = ['Activated','Inactivated']) : string
    {
        $filters = '';
        
        foreach ($columnsArray as $column) 
        {
            if($column != 'id')
            {
                if(strpos($column,'_date') > -1 || strpos($column,'_time') > -1)
                {
                    $filters .= '<td> <div id="' . $column . '_range" class="input-group date-range-picker"> '
                             . '<input type="text" class="form-control form-filter" name="' . $column . '_range"> '
                             . '<span class="input-group-btn"> <button class="btn default date-range-toggle" type="button">'
                             . ' <i class="fa fa-calendar"></i> </button> </span> </div> </td>' . PHP_EOL;
                }
                else
                {
                    if($column == 'status')
                    {
                        $filters .= '<td> <select name="status" class="form-control form-filter input-sm"><option value="" selected>All</option>';
                        
                        foreach ($options as $option)
                        {
                            $filters .= '<option value="' . $option . '">' . $option . '</option>';
                        }
                        
                        $filters .= '</select> </td>' . PHP_EOL;
                    }
                    else
                    {
                        $filters .= '<td><input type="text" class="form-control form-filter" name="' . $column . '"></td>' . PHP_EOL;
                    }
                }
            }
        }
        
        return $filters;
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


