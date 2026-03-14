<?php declare(strict_types=1);
/**
 * @framework       iResponse Framework 
 * @version         1.0
 * @author          Amine Idrissi <contact@iresponse.tech>
 * @date            2024
 * @name            errors.conf.php	
 */

# core 
use IR\Core\Application as Application;

# helpers
use IR\App\Helpers\Authentication as Authentication;

# exceptions
use IR\Exceptions\Types\FatalException as FatalException;
   
# displaying errors 
if(IS_DEV_MODE)
{
    ini_set('display_errors','On');
    error_reporting(E_ALL);
}
else
{
    ini_set('display_errors','Off');
    error_reporting(0);
}

# catching fatal errors
register_shutdown_function(function()
{
    # check for empty session leaks 
    if(!Authentication::isUserAuthenticated() && Application::isValid())
    {
        if(Application::getCurrent()->utils->fileSystem->fileExists(SESSIONS_PATH . DS . 'sess_' . session_id()))
        {
            Application::getCurrent()->utils->fileSystem->deleteFile(SESSIONS_PATH . DS . 'sess_' . session_id());
        }
    }
    
    # check for actions session leaks 
    if(Authentication::isTrackingUser() && Application::isValid())
    {
        if(Application::getCurrent()->utils->fileSystem->fileExists(SESSIONS_PATH . DS . 'sess_' . session_id()))
        {
            Application::getCurrent()->utils->fileSystem->deleteFile(SESSIONS_PATH . DS . 'sess_' . session_id());
        }
    }
    
    # check if there is empty files
    if(Application::isValid())
    {
        Application::getCurrent()->utils->terminal->cmd('find ' . SESSIONS_PATH . ' -size  0 -print0 | xargs -0 rm --');
    }
    
    $error = error_get_last();

    if($error != null && count($error))
    {
        # create a custom fatal exception to handle
        $e = new FatalException($error['message'],500,null,$error['file'],intval($error['line']));
        
        # try to log and render the current exception 
        $e->logError();

        # end execution
        die();
    }
});
