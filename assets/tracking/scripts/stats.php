<?php
/**
 * @framework       iResponse Framework 
 * @version         1.0
 * @author          Amine Idrissi <contact@iresponse.tech>
 * @date            2019
 * @name            stats.php
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
require_once BASE_PATH . '/help.php'; 

# define variables 
$stats = [];
$cleanEmails = [];
$bounceEmails = [];
$commands = [];
$types = ['bounces','delivered'];

foreach ($types as $type) 
{
    # move all non empty files to process folder and empty archive
    $rand = random(8,true,true,true,false);
    $folder = "/etc/pmta/{$type}/process/" . $rand;
    exec('mkdir -p ' . $folder);
    exec("find /etc/pmta/{$type}/archived/ -name \"*.csv\" -type f -exec sh -c 'test `wc -l {} | cut -f1 -d\" \"` -gt \"1\"' \; -exec mv -t {$folder} {} +");
    exec("find /etc/pmta/{$type}/archived/ -maxdepth 1 -type f -exec bash -c '[[ $(wc -l < \"$1\") -eq 1 ]] && rm \"$1\"' _ '{}' \;");

    $dir = new DirectoryIterator($folder);
    $lines = [];

    foreach ($dir as $fileinfo) 
    {
        if (!$fileinfo->isDot())
        {
            $path = $fileinfo->getPathname();
            if(count($lines) == 0)
            {
                $lines = file($path);
            }
            else
            {
                $lines = array_merge($lines,file($path));
            }
        }
    }
    
    $lines = array_filter($lines,function($value)
    {
        return strpos(str_replace(['<br/>','<br>','</br>'],'',trim(preg_replace('/\s\s+/','',$value))),'type,bounceCat,vmta,jobId,envId') === FALSE;
    });
    
    # reindex th array
    reindex($lines);
    
    # loop to fetch bounce emails 
    $condition = $type == 'delivered' ? 'success' : 'hardbnc';

    foreach ($lines as $line) 
    {
        $line = str_getcsv($line,',');
        $count = count($line);
        
        if($count >= 5 && $count <= 6)
        {
            $job = explode('_',trim($line[3]));
            $env = explode('_',trim($line[4]));
            $message = $line[5];
            
            if(count($env) == 4)
            {
                $bounceCat = trim($line[1]);
                $mtaDropId = intval($env[0]);
                $vmtaId = intval($env[1]);
                $clientId = intval($env[2]);
                $listId = intval($env[3]);
                $dsnDiag = $count >= 5 ? $line[4] : '';
                
                if($mtaDropId > 0 && $vmtaId > 0)
                {
                    if(!key_exists($mtaDropId,$stats))
                    {
                        $stats[$mtaDropId]['type'] = $job[0];
                        $stats[$mtaDropId]['total'] = [
                            'delivered' => 0,
                            'soft_bounced' => 0,
                            'hard_bounced' => 0
                        ];
                    }
                    
                    if(!key_exists($vmtaId,$stats[$mtaDropId]))
                    {
                        $stats[$mtaDropId][$vmtaId] = [
                            'delivered' => 0,
                            'soft_bounced' => 0,
                            'hard_bounced' => 0
                        ];
                    }
                        
                    if('delivered' == $type)
                    {
                        $stats[$mtaDropId][$vmtaId]['delivered']++;
                        $stats[$mtaDropId]['total']['delivered']++;
                        
                        if(!in_array($listId . "_" . $clientId,$cleanEmails))
                        {
                            $cleanEmails[] = $listId . "_" . $clientId;
                        }
                    }
                    else
                    {
                        $bounce = false;
                        
                        if($bounceCat == 'hardbnc' || strpos($message,' dd ') !== false)
                        {
                            $bounce = true;
                        }
                        
                        if($bounce == true)
                        {
                            $stats[$mtaDropId][$vmtaId]['hard_bounced']++;
                            $stats[$mtaDropId]['total']['hard_bounced']++;
                            
                            if(!in_array($listId . "_" . $clientId,$bounceEmails))
                            { 
                                $bounceEmails[] = $listId . "_" . $clientId;
                            }
                        }
                        else
                        {
                            $stats[$mtaDropId]['total']['soft_bounced']++;
                            $stats[$mtaDropId][$vmtaId]['soft_bounced']++;
                        }
                    }
                }
            }
        }
    }
    
    # move all files to backup
    exec('mv ' . $folder . '/* /etc/pmta/' . $type . '/backup/');
    exec('rm -rf ' . $folder);
}

# make these tables unique 
$bounceEmails = array_filter(array_unique($bounceEmails));
$cleanEmails = array_filter(array_unique($cleanEmails));
$api = decrypt('$p_api');

# send request to calculate stats
$result = json_decode(sendPostRequest($api,[ 'controller' => 'Pmta', 'action' => 'accountings',
    'parameters' => [
        'stats' => base64_encode(json_encode($stats)),
        'bounce-emails' => base64_encode(json_encode($bounceEmails)),
        'clean-emails' => base64_encode(json_encode($cleanEmails))
    ]
]),true);

die($result['message'] . PHP_EOL);