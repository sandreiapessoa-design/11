<?php declare(strict_types=1); namespace IR\Exceptions; if (!defined('IR_START')) exit('<pre>No direct script access allowed</pre>');
/**
 * @framework       iResponse Framework 
 * @version         1.0
 * @author          Amine Idrissi <contact@iresponse.tech>
 * @date            2024
 * @name            IRException.php	
 */

# core
use IR\Core\Application as Application;

# controllers
use IR\App\Controllers\Errors as Errors;

# utilities 
use IR\Utils\Types\Objects as Objects;

# http 
use IR\Http\Response as Response;

# logging 
use IR\Logs\Logger as Logger;

/**
 * @name IRException
 * @description mother class of all iresponse exception types
 */
class IRException extends \Exception
{
    public function __construct(string $message = "", int $code = 500, \Throwable $cause = null,string $file = '',int $line = 0)
    {
        # check if th error code is less than 400
        $this->code = $code >= 400 ? $code : 500;
        
        parent::__construct($message, $code, $cause);
        
        # set the file and the line
        if($cause != null)
        {
            $this->message = $cause->getMessage();
            $this->file = $cause->getFile();
            $this->line = $cause->getLine();
        }
        
        # set file and line if there are custom info
        if(!empty($file) && $line != 0)
        {
            $this->line = $line;
            $this->file = $file;
        }
    }

    /**
     * @name logError
     * @description logging the error
     * @access public
     */
    public function logError()
    {
        Logger::getInstance()->error($this);
    }
    
    /**
     * @name render
     * @description renders the whole exception as a page or a json message depends on request
     * @access public
     */
    public function render()
    {
        try
        {
            $code = $this->getCode(); 

            # defining header status
            Response::getInstance()->header(($code >= 400 && $code <= 500) ? $code : 500);

            # define that if the error page from the handler is shown or not 
            $pageShown = false;
            
            if(Application::isValid())
            {
                if(Application::getCurrent()->router->instance != null)
                {
                    Application::getCurrent()->router->instance->showMasterView = false;
                    Application::getCurrent()->router->instance->showPageView = false;
                }

                # create errors controller object
                $errorsController = new Errors();
                
                if($errorsController != null)
                {
                    # save this exeption into the session
                    Application::getCurrent()->http->session->set('mb-exception',$this);
                    
                    $errorsController->showErrorPage();
                    $pageShown = true;
                }
            }
            
            if($pageShown == false)
            {
                echo("<pre>Oops!! something went wrong !<br/><br/><span style='color:red'>{$this->getMessage()}</span></pre>");  
            }

            # exiting from the script
            die();
        }
        catch (\Exception $e)
        {
            die('<pre>' . $this->getExceptionMessageString($e) . '</pre>');
        }
    }
    
    /**
     * @name getExceptionMessageString
     * @description this method is to get the error message
     * @access public
     * @return string
     */
    public function getExceptionMessageString()
    {
        return sprintf('%s [ %s ]: %s ~ %s [ %d ]',Objects::getInstance()->getName($this), $this->getCode(), strip_tags($this->getMessage()), $this->getFile(), $this->getLine());
    }
    
    /**
     * @name __toString
     * @description this method is to get the error message
     * @access public
     * @return string
     */
    public function __toString()
    {
        return $this->getExceptionMessageString();
    }
}


