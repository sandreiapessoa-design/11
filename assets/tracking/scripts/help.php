<?php
/**
 * @framework       iResponse Framework 
 * @version         1.0
 * @author          Amine Idrissi <contact@iresponse.tech>
 * @date            2019
 * @name            help.php	
 */

# help methods 

/**
 * @name getIp
 * @description get client ip
 * @access public
 * @return string
 */
function getIp()
{
    $ip = "";

    if (!empty($_SERVER['HTTP_CLIENT_IP']))
    {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } 
    elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))
    {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } 
    else 
    {
        $ip = $_SERVER['REMOTE_ADDR'];
    }

    if(filter_var($ip,FILTER_VALIDATE_IP,FILTER_FLAG_IPV6))
    {
        $ipv4 = hexdec(substr($ip, 0, 2)). "." . hexdec(substr($ip, 2, 2)). "." . hexdec(substr($ip, 5, 2)). "." . hexdec(substr($ip, 7, 2));
        $ip = $ipv4;
    }

    if(!filter_var($ip,FILTER_VALIDATE_IP,FILTER_FLAG_IPV4))
    {
        $match = array();

        if (preg_match('/\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}/',$ip, $match)) 
        {
            $ip = count($match) > 0 && filter_var($match[0],FILTER_VALIDATE_IP) ? $match[0] : "";
        }
    }

    return $ip;
}

/**
 * @name decrypt
 * @description decrypt a value
 * @access public
 * @return string
 */
function decrypt($value)
{
    $encrypted = base64_decode($value);
    $salt = substr($encrypted,0,32);
    $encrypted = substr($encrypted,32);
    $salted = $dx = '';
    while (strlen($salted) < 48) 
    {
        $dx = md5($dx . '$p_tracking_enc_key' . $salt, true);
        $salted .= $dx;
    }
    $key = substr($salted,0,32);
    $iv = substr($salted,32,16);
    return openssl_decrypt($encrypted, 'aes-256-cbc', $key,OPENSSL_RAW_DATA, $iv);
}

/**
 * @name cmd
 * @description executes a system command
 * @access public
 * @return array
 */
function cmd($command,$return = 'output',$type = 'string')
{
    $result = ['output' => '' , 'error' => ''];

    if(isset($command) && $command != '')
    {
        $descriptorspec = [
            0 => ["pipe", "r"], 
            1 => ["pipe", "w"],
            2 => ["pipe", "w"],
        ];

        $pipes = [];
        $process = proc_open($command, $descriptorspec,$pipes, dirname(__FILE__), null);  

        if(is_resource($process))
        {
            if($return == 'output')
            {
                if($type == 'string')
                {
                    $result['output'] = trim(stream_get_contents($pipes[1]));
                    $result['error'] = trim(stream_get_contents($pipes[2]));
                }
                else
                {
                    $result['output'] = explode(PHP_EOL,trim(stream_get_contents($pipes[1])));
                    $result['error'] = explode(PHP_EOL,trim(stream_get_contents($pipes[2])));
                }
            }

            # close all pipes
            fclose($pipes[1]);
            fclose($pipes[2]);

            # close the process
            proc_close($process);
        }
    }

    return $result;
}

/**
 * @name sendPostRequest
 * @description send post request
 * @access public
 * @param string $url
 * @param boolean $data
 * @return mixed
 */
function sendPostRequest($url,$data) 
{
    $response = null;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
    curl_setopt($ch, CURLOPT_URL,$url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS,http_build_query($data));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
    $response = curl_exec($ch);
    curl_close($ch);
    return $response;
}

/**
 * @name reindex
 * @description reindexes the supplied array from 0 to number of values - 1.
 * @param array $source
 * @return
 */
function reindex(array &$source) 
{
    $temp = $source;
    $source = [];

    foreach ($temp as $value) 
    {
        $source[] = $value;
    }
}

/**
 * @name checkForImage
 * @description checks if the link is an image
 * @param string $url 
 * @return
 */
function checkForImage($url,$domain)
{
    $parts = explode('.',$url);
    $extention = end($parts);
    $extention = strtolower($extention);

    if(in_array($extention,['jpg','jpeg','png','gif','bmp']))
    {
        $image = "$domain/media/" . end(explode(RDS,$url));
        header("Content-type:image/{$extention}");
        echo file_get_contents($image); 
        die();
    }
}

/**
 * @name parseURL
 * @description parse url
 * @param string $url
 * @return
 */
function parseURL($url)
{
    $data = [];
    
    if(strpos($url,'/') === FALSE && strpos($url,'act') === FALSE)
    {
        $url = decrypt(base64_decode(str_replace("_","=",$url)));
    }

    $parts = parse_url("http://{$_SERVER['HTTP_HOST']}/{$url}");
    $query = key_exists('query',$parts) ? $parts['query'] : null;
    $path = $parts['path'];
    $output = [];
    
    if($query != '')
    {
        if(strpos($query,'act=') !== FALSE)
        {
            $params = explode('&',$query);

            if($params != null && count($params) > 0)
            {
                foreach ($params as $param) 
                {
                    $keyValue = explode('=',$param);

                    if($keyValue != null && count($keyValue) == 2)
                    {
                        $output[$keyValue[0]] = $keyValue[1];
                    }
                }
            }
        }
    }
    else if(strpos(trim($path,'/'),'/') !== FALSE)
    {   
        $params = explode('/',trim($path,'/'));

        if(count($params))
        {
            if(in_array($params[0],['op','cl','un','oop']))
            {
                if(count($params) == 7)
                {
                    $output["act"] = $params[0];
                    $output["pid"] = $params[1];
                    $output["uid"] = $params[2];
                    $output["vid"] = $params[3];
                    $output["ofid"] = $params[4];
                    $output["lid"] = $params[5];
                    $output["cid"] = $params[6];
                }
            }
        }

    }
 
    if(count($output) == 0)
    {
        die('<pre>Could not parse url !</pre>');
    }

    if(count($output) && key_exists('act',$output))
    {
        $data['act'] = key_exists('pid',$output) ? $output['act'] : 0;
        $data['process-id'] = 0;

        if(key_exists('pid',$output))
        {
            if(strpos($output['pid'],'_') === FALSE)
            {
                $data['process-id'] = intval($output['pid']);
                $data['process-type'] = 'md';
            }
            else
            {
                $parts = explode('_',$output['pid']);
                
                if(count($parts) == 2)
                {
                    $data['process-id'] = intval($parts[0]);
                    $data['process-type'] = $parts[1];
                }
            }
        }
        
        $data['user-id'] = key_exists('uid',$output) ? intval($output['uid']) : 0;
        $data['vmta-id'] = key_exists('vid',$output) ? intval($output['vid']) : 0;
        $data['offer-id'] = key_exists('ofid',$output) ? intval($output['ofid']) : 0;
        $data['list-id'] = key_exists('lid',$output) ? intval($output['lid']) : 0;
        $data['client-id'] = key_exists('cid',$output) ? intval($output['cid']) : 0; 
    }
    else
    {
        die('<pre>Could not parse url !</pre>');
    }
    
    return $data;
}

/**
 * @name random
 * @description generates random text 
 * @access public 
 * @param integer $size the size of generated text 
 * @param boolean $letters boolean value to tell the function whether use letters or not 
 * @param boolean $numbers boolean value to tell the function whether use uppercase letters too or not 
 * @param boolean $uppercase boolean value to tell the function whether use numbers or not
 * @param boolean $special boolean value to tell the function whether use special characters or not
 * @return string
 */
function random(int $size = 5, bool $letters = true, bool $numbers = true, bool $uppercase = false, bool $special = false) : string
{
    $result = '';
    $characters = '';

    if($letters)
    {
        $characters .= 'abcdefghijklmnopqrstuvwxyz';
        if($uppercase)
        {
            $characters .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        }
    }

    if($numbers)
    {
        $characters .= '0123456789';
    }

    if($special)
    {
        $characters .= '@\\/_*$&-#[](){}';
    }

    for ($i = 0; $i <$size; $i++) 
    {
         $result .= $characters[rand(0, strlen($characters) - 1)];
    }

    return $result;
}