<?php declare(strict_types=1); namespace IR\App\Helpers; 

if (!defined('IR_START')) exit('<pre>No direct script access allowed</pre>');

/**
 * @framework       iResponse Framework 
 * @version         1.0
 * @author          Amine Idrissi <contact@iresponse.tech>
 * @date            2024
 * @name            Api.php
 */

# core 
use IR\Core\Application as Application;

# utilities
use IR\Utils\System\Terminal as Terminal;

/**
 * @name Api
 * @description Api Helper
 */
class Api
{
    /**
     * @name call
     * @description call iresponse api
     * @access public
     * @return array
     */ 
    public static function call(string $controller, string $method, array $parameters = [], bool $nohup = false, $logFile = '', $userId = 0) : array
    {
        # set the current application to a local variable
        $app = Application::getCurrent();
        
        $result = $app->utils->terminal->cmd('java -version');
        
        if(count($result) == 0)
        {
            return ['output' => '','error' => 'Java is not installed !'];
        }
         
        $lines = explode(PHP_EOL, $result['output'] . PHP_EOL . $result['error']); 
        
        if(count($lines) == 0)
        {
            return ['output' => '','error' => 'Java is not installed !'];
        }
         
        # prepare info to call our api
        $api = 'sudo java -Dfile.encoding=UTF8 -jar ' . API_PATH . DS . 'iresponse_services.jar';
        $userId = $userId == 0 ? intval(Authentication::getAuthenticatedUser()->getId()) : $userId;
        
        $data = base64_encode(json_encode([
            'user-id' => $userId,
            'endpoint' => $controller,
            'action' => $method,
            'parameters' => $parameters
        ], JSON_UNESCAPED_UNICODE));
        
        if($nohup == true)
        {
            $logFile = strlen($logFile) > 0 ? "> {$logFile} 2> {$logFile}" : '';
            $app->utils->terminal->cmd("nohup {$api} {$data} {$logFile} &", Terminal::RETURN_NOTHING);
            return ['timestamp' => date('Y-m-d H:i:s'), 'message' => 'Process started successfully !', 'httpStatus' => 200];
        }
        else
        {
            $callResult = $app->utils->terminal->cmd("{$api} {$data}", Terminal::RETURN_OUTPUT, Terminal::STRING_OUTPUT);
        }

        if(isset($callResult) && count($callResult))
        {
            if(strlen($app->utils->strings->trim($callResult['error'])) > 0)
            {
                return ['timestamp' => date('Y-m-d H:i:s'), 'message' => $callResult['error'], 'httpStatus' => 500];
            }
            else
            {
                $json = json_decode($callResult['output'], true);
                return ($json != null && is_array($json) && count($json)) ? $json : ['timestamp' => date('Y-m-d H:i:s'), 'message' => 'No response found !', 'httpStatus' => 500];
            }
        }
        
        return ['timestamp' => date('Y-m-d H:i:s'), 'message' => 'No response found !', 'httpStatus' => 500];
    }

    /**
     * @name callinstallation
     * @description call installation api
     * @access public
     * @return array
     */ 
    public static function callinstallation(string $controller, string $method, array $parameters = [], bool $nohup = false, $logFile = '', $userId = 0) : array
    {
        # set the current application to a local variable
        $app = Application::getCurrent();
        
        $result = $app->utils->terminal->cmd('java -version');
        
        if(count($result) == 0)
        {
            return ['output' => '','error' => 'Java is not installed !'];
        }
         
        $lines = explode(PHP_EOL, $result['output'] . PHP_EOL . $result['error']); 
        
        if(count($lines) == 0)
        {
            return ['output' => '','error' => 'Java is not installed !'];
        }
         
        # prepare info to call our api
        $api = 'sudo java -Dfile.encoding=UTF8 -jar ' . API_PATH . DS . 'installation.jar';
        $userId = $userId == 0 ? intval(Authentication::getAuthenticatedUser()->getId()) : $userId;
        
        $data = base64_encode(json_encode([
            'user-id' => $userId,
            'endpoint' => $controller,
            'action' => $method,
            'parameters' => $parameters
        ], JSON_UNESCAPED_UNICODE));

        if($nohup == true)
        {
            $logFile = strlen($logFile) > 0 ? "> {$logFile} 2> {$logFile}" : '';
            $app->utils->terminal->cmd("nohup {$api} {$data} {$logFile} &", Terminal::RETURN_NOTHING);
            return ['timestamp' => date('Y-m-d H:i:s'), 'message' => 'Process started successfully !', 'httpStatus' => 200];
        }
        else
        {
            $callResult = $app->utils->terminal->cmd("{$api} {$data}", Terminal::RETURN_OUTPUT, Terminal::STRING_OUTPUT);
        }

        if(isset($callResult) && count($callResult))
        {
            if(strlen($app->utils->strings->trim($callResult['error'])) > 0)
            {
                return ['timestamp' => date('Y-m-d H:i:s'), 'message' => $callResult['error'], 'httpStatus' => 500];
            }
            else
            {
                $json = json_decode($callResult['output'], true);
                return ($json != null && is_array($json) && count($json)) ? $json : ['timestamp' => date('Y-m-d H:i:s'), 'message' => 'No response found !', 'httpStatus' => 500];
            }
        }
        
        return ['timestamp' => date('Y-m-d H:i:s'), 'message' => 'No response found !', 'httpStatus' => 500];
    }

    /**
     * Additional methods from the original API1.php can be added here as needed
     * For example, callaffiliate(), callpmta(), callGapi(), callh1(), etc.
     */

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