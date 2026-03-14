<?php
/**
 * @framework       iResponse Framework 
 * @version         1.0
 * @author          Amine Idrissi <contact@iresponse.tech>
 * @date            2019
 * @name            index.php
 */

# defining start time
define('IR_START', microtime(true));

# defining the base path
define('BASE_PATH',dirname(__FILE__));

# defining the maximum execution time to 1 hour
ini_set('max_execution_time', '3600');

# defining the socket timeout to 1 min
ini_set('default_socket_timeout', '60');

# defining the maximum memory limit 
ini_set('memory_limit', '-1');

# disabling remote file include
ini_set("allow_url_fopen", '1');
ini_set("allow_url_include", '0');

# defining the default time zone
date_default_timezone_set("UTC");

# getting the peak of memory, in bytes, that's been allocated to our PHP script. 
define('START_MEMORY', memory_get_peak_usage(true));
define('START_TIME',microtime(true));

# defining separators
define('DS',DIRECTORY_SEPARATOR);
define('RDS','/');

# require the helper
require_once '/var/www/scripts/help.php';

# parse url to get parameters 
$url = (filter_input(INPUT_SERVER, 'HTTP_X_REWRITE_URL') != null) ? ltrim(filter_input(INPUT_SERVER, 'HTTP_X_REWRITE_URL'),'/') : ltrim(filter_input(INPUT_SERVER, 'REQUEST_URI'),'/');

# check if is an image 
checkForImage($url,decrypt('$p_upload_ip'));

# check for short link
?><script>if(window.location.href.includes("#")) window.location.href = window.location.href.replace(/\/\#\//g,'#').replace(/\/\#/g,'#').replace(/\#/g,'/');</script><?php

# check for root call of the domain
if($url == '')
{
    require_once 'home.html';
    die();
}

# prepare data array 
$data = parseURL($url);

# start tracking
if(count($data))
{
    if($data['process-id'] > 0 || $data['offer-id'] > 0)
    {
        $api = decrypt('$p_api');
        $data['ip'] = getIp();
        $data['agent'] = (filter_input(INPUT_SERVER,'HTTP_USER_AGENT') != null) ? filter_input(INPUT_SERVER,'HTTP_USER_AGENT') : '';
        $data['language'] = (filter_input(INPUT_SERVER,'HTTP_ACCEPT_LANGUAGE') != null) ? strtoupper(substr(filter_input(INPUT_SERVER,'HTTP_ACCEPT_LANGUAGE'), 0, 2)) : '';

        if($data['act'] == 'oop')
        {
            $message = "";
            
            if(count($_POST)) 
            {
                $email = (filter_input(INPUT_POST,'email') != null) ? filter_input(INPUT_POST,'email') : '';
                        
                # send tracking information to bluemail
                if(!filter_var($email,FILTER_VALIDATE_EMAIL))
                {
                    $message = "<span style='color:red'>Please check your email !</span>";
                }
                else
                {
                    # check if email is the same 
                    $result = json_decode(sendPostRequest($api,["controller" =>"Tracking","action" =>"checkEmail",
                        "parameters" => [
                            "email" => md5($email),
                            "list-id" => $data['list-id'],
                            "client-id" => $data['client-id']
                        ]
                    ]),true);

                    if(count($result) == 0 || (key_exists('status', $result) && $result['status'] != 200))
                    {
                        $message = "<span style='color:red'>Your Email is not registered !</span>";
                    }
                
                    if(strtolower(trim($result['message'])) == 'email is correct !')
                    {
                        # execute tracking job
                        exec('nohup php -r \'require_once "/var/www/scripts/help.php"; $result = json_decode(sendPostRequest("' . $api . '",["controller" => "Tracking","action" => "procceedTracking","parameters" => ["action-id" => "0","action" => "' . $data["act"] . '","process-id" => "' . $data["process-id"] . '","process-type" => "' . $data["process-type"] . '","user-id" => "' . $data['user-id'] . '","vmta-id" => "' . $data["vmta-id"] . '","offer-id" => "' . $data['offer-id'] . '","list-id" => "' . $data["list-id"] . '","client-id" => "' . $data["client-id"] . '","agent" => "' . $data["agent"] . '","ip" => "' . $data["ip"] . '","language" => "' . $data["language"] . '"]]),true); print_r($result["message"] . PHP_EOL); \' 2>&1 &');

                        $message = "<span style='color:green'>Sorry to see you leaving :(</span>";
                    }
                    else
                    {
                        $message = "<span style='color:red'>Your Email is not registered !</span>";
                    }
                }
            }
            
            include_once BASE_PATH . DS . 'optout.php';  
        }
        else
        {
            # generating link and redirecting
            $link = '';
            $actionId = 0;
            
            if(in_array($data['act'],['cl','un']))
            {
                # get offer link
                $type = $data['act'] == 'cl' ? 'preview' : 'unsub';
                $result = json_decode(sendPostRequest($api,[ 'controller' => 'Tracking', 'action' => 'getLink',
                    'parameters' => [
                        'type' => $type,
                        'process-id' => $data['process-id'],
                        'process-type' => $data['process-type'],
                        'user-id' => $data['user-id'],
                        'vmta-id' => $data['vmta-id'],
                        'list-id' => $data['list-id'],
                        'client-id' => $data['client-id'],
                        'offer-id' => $data['offer-id'],
                        'ip' => $data['ip']
                    ]
                ]),true);
                
                if($result === FALSE || count($result) == 0)
                {
                    die('<pre>405 : Bad request !</pre>');
                }

                if($result['status'] != 200)
                {
                    die('<pre>' . $result['status'] . ' : ' . $result['message'] . '</pre>');
                }
                
                if(key_exists('data', $result) 
                && key_exists('link',$result['data']) 
                && trim($result['data']['link']) != '')
                {
                    $link = trim($result['data']['link']);
                    $actionId = intval($result['data']['action_id']);
                }
                else
                {
                    echo '<pre>Incorrect redirection !</pre>';
                }
            }

            # send tracking information to master app
            if(in_array($data['act'],['op','cl','un']) && $data['process-id'] > 0)
            {
                # execute tracking job
                exec('nohup php -r \'require_once "/var/www/scripts/help.php"; $result = json_decode(sendPostRequest("' . $api . '",["controller" => "Tracking","action" => "procceedTracking","parameters" => ["action-id" => "' . $actionId . '","action" => "' . $data["act"] . '","process-id" => "' . $data["process-id"] . '","process-type" => "' . $data["process-type"] . '","user-id" => "' . $data['user-id'] . '","vmta-id" => "' . $data["vmta-id"] . '","offer-id" => "' . $data['offer-id'] . '","list-id" => "' . $data["list-id"] . '","client-id" => "' . $data["client-id"] . '","agent" => "' . $data["agent"] . '","ip" => "' . $data["ip"] . '","language" => "' . $data["language"] . '"]]),true); print_r($result["message"] . PHP_EOL); \' 2>&1 &');
            }
            
            # redirecting in case of a click or unsub 
            if($link != '')
            {
                header('Location: ' . $link);
                exit();
            }
            else
            {
                echo '<pre>Operation completed !</pre>';
            }
        }
    }
    else
    {
        echo '<pre>No drop found !</pre>';
    }
}
else
{
    echo '<pre>No parameters found !</pre>';
}