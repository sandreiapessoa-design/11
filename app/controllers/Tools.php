<?php declare(strict_types=1); namespace IR\App\Controllers; if (!defined('IR_START')) exit('<pre>No direct script access allowed</pre>');
/**
 * @framework       iResponse Framework 
 * @version         1.0
 * @author          Amine Idrissi <contact@iresponse.tech>
 * @date            2024
 * @name            Tools.php	
 */

# defaults 
 use  \DOMDocument;
 
# core 
use IR\Core\Application as Application;

# mvc 
use IR\Mvc\Controller as Controller;

# models
use IR\App\Models\Admin\ServerVmta as ServerVmta;

# http 
use IR\Http\Request as Request;

# helpers 
use IR\App\Helpers\Authentication as Authentication;
use IR\App\Helpers\Page as Page;
use IR\App\Helpers\Permissions as Permissions;

# exceptions
use IR\Exceptions\Types\PageException as PageException;

/**
 * @name Tools
 * @description Tools Controller
 */
class Tools extends Controller
{
    /**
     * @app
     * @readwrite
     */
    protected $app;
    
    /**
     * @app
     * @readwrite
     */
    protected $authenticatedUser;
    
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
        
        # check for authentication
        if(!Authentication::isUserAuthenticated())
        {
            Page::redirect($this->app->http->request->getBaseURL() . RDS . 'auth' . RDS . 'login.' . DEFAULT_EXTENSION);
        }
        
        # check users roles 
        Authentication::checkUserRoles();
        
        # get the authenticated user
        $this->authenticatedUser = Authentication::getAuthenticatedUser();
    }

    /**
     * @name mailboxExtractor
     * @description the mailboxExtractor action
     * @before init
     * @after closeConnections
     */
    public function mailboxExtractor() 
    { 
        # check for permissions
        $access = Permissions::checkForAuthorization($this->authenticatedUser,__CLASS__,__FUNCTION__);

        if($access == false)
        {
            throw new PageException('Access Denied !',403);
        }
        
        # set menu status
        $this->masterView->set([
            'tools' => 'true',
            'mailboxtractor' => 'true'
        ]);
    }
    
    /**
     * @name spfLookup
     * @description the spf lookup action
     * @before init
     * @after closeConnections,checkForMessage
     */
    public function spfLookup()
    { 
        # check for permissions
        $access = Permissions::checkForAuthorization($this->authenticatedUser,__CLASS__,__FUNCTION__);

        if($access == false)
        {
            throw new PageException('Access Denied !',403);
        }
        
        # get post data
        $data = $this->app->http->request->retrieve(Request::ALL,Request::POST);
        $results = '';
        
        if(count($data))
        {
            $flag = 'error';
            $message = 'Could not check spfs !';
            
            $values = [];
            $domains = explode(PHP_EOL,strval($this->app->utils->arrays->get($data,'domains')));
            
            if(count($domains))
            {
                foreach ($domains as $line) 
                {
                    if($this->app->utils->domains->isValidDomain(trim($line)))
                    {
                        $values[] = trim($line);
                    }
                }
            }
            
            $resultGrabbed = false;
            
            # check values
            if(count($values))
            {
                $checkResults = [];
                
                foreach ($values as $value) 
                {
                    $checkResults[$value] = 'No spf records found ! ';
                    $res = dns_get_record($value,DNS_TXT);
                    
                    if(count($res))
                    {
                        foreach ($res as $row) 
                        {
                            $txt = $this->app->utils->arrays->get($row,'txt');
                            
                            if($txt != null && $this->app->utils->strings->indexOf(strval($txt),'v=spf1') > -1)
                            {
                                $checkResults[$value] = $txt;
                            }
                        }
                    }
                }
                
                $results .= '<table class="console-table"><thead><tr><th>Domain</th><th>Record</th></tr></thead><tbody>';
                
                foreach ($checkResults as $key => $value) 
                {
                    $results .= "<tr><td>{$key}</td><td>{$value}</td></tr>";
                }
                
                $results .= '</tbody></table>';
                $resultGrabbed = true;
            }

            if($resultGrabbed == true)
            {
                $flag = 'success';
                $message = 'spf check completed !';
            }
            
            # stores the message in the session 
            Page::registerMessage($flag, $message);
        }
        
        # set menu status
        $this->masterView->set([
            'tools' => 'true',
            'spfLookup' => 'true'
        ]);
        
        # set data to the page view
        $this->pageView->set([
            'results' => $results
        ]);
    }
    
    /**
     * @name blacklist
     * @description the blacklist action
     * @before init
     * @after closeConnections,checkForMessage
     */
    public function blacklist() 
    { 
        # check for permissions
        $access = Permissions::checkForAuthorization($this->authenticatedUser,__CLASS__,__FUNCTION__);

        if($access == false)
        {
            throw new PageException('Access Denied !',403);
        }
        
        # get post data
        $data = $this->app->http->request->retrieve(Request::ALL,Request::POST);
        $results = '';
        
        if(count($data))
        {
            $flag = 'error';
            $message = 'Could not check reputations !';
            
            $values = [];
            
            $type = strval($this->app->utils->arrays->get($data,'type'));
            $lines = explode(PHP_EOL,strval($this->app->utils->arrays->get($data,'text')));
            
            if(count($lines))
            {
                foreach ($lines as $line) 
                {
                    if($type == 'ips')
                    {
                        if(filter_var(trim($line),FILTER_VALIDATE_IP))
                        {
                            $values[] = trim($line);
                        }
                    }
                    else
                    {
                        if($this->app->utils->domains->isValidDomain(trim($line)))
                        {
                            $values[] = trim($line);
                        }
                    }
                }
            }
            
            $resultGrabbed = false;
            
            # check values
            if(count($values))
            {
                $res = $this->app->http->request->curl("https://www.bulkblacklist.com/",[$type => implode(PHP_EOL,$values)],Request::POST,false,true);
                
                if($res != '')
                {
                    // create new DOMDocument
                    $html = new DOMDocument('1.0', 'UTF-8');
                    $internalErrors = libxml_use_internal_errors(true);
                    $html->loadHTML($res);
                    libxml_use_internal_errors($internalErrors);
                    
                    $rows = [];
                    
                    foreach($html->getElementsByTagName('tr') as $element)
                    {
                        $td = [];
                        
                        foreach($element->getElementsByTagName('td') as $row)  
                        {
                            $td [] = $row->ownerDocument->saveHTML($row->childNodes[0]);
                        }
                        
                        $rows[] = $td;
                    }
                    
                    if(count($rows) > 1)
                    {
                        $results .= '<table class="console-table"><thead><tr>';
                        
                        for ($i = 1; $i < count($rows[0]); $i++) 
                        {
                            $results .= '<th>' . ucwords(strtolower(str_replace('SURBL - ','',$rows[0][$i]))) . '</th>';
                        }
                        
                        $results .= '</tr></thead><tbody>';
                        
                        for ($i = 1; $i < count($rows); $i++) 
                        {
                            $results .= '<tr>';
                            $row = $rows[$i];
                            
                            if(count($row))
                            {
                                for ($j = 1; $j < count($row); $j++) 
                                {       
                                    $results .= '<td>';
                                    
                                    if($this->app->utils->strings->indexOf($row[$j],'images/g.png') > -1)
                                    {
                                        $results .= str_replace('<img src="images/g.png">','<i class="fa fa-check green"></i>',$row[$j]);
                                    }
                                    else if($this->app->utils->strings->indexOf($row[$j],'images/r.png') > -1)
                                    {
                                        $results .= str_replace('<img src="images/r.png">','<i class="fa fa-close red"></i>',$row[$j]);
                                    }   
                                    else
                                    {
                                        $results .= $row[$j];
                                    }
                                    
                                    $results .= '</td>';
                                }
                            }
                            
                            $results .= '</tr>';
                        }
                        
                        $results .= '</tbody></table>';
                    }
                }
                
                $resultGrabbed = true;
            }

            if($resultGrabbed == true)
            {
                $flag = 'success';
                $message = 'Blacklist check completed !';
            }
            
            # stores the message in the session 
            Page::registerMessage($flag, $message);
        }
        
        # set menu status
        $this->masterView->set([
            'tools' => 'true',
            'blacklist' => 'true'
        ]);
        
        # set data to the page view
        $this->pageView->set([
            'results' => $results
        ]);
    }

    /**
     * @name extractor
     * @description the extractor action
     * @before init
     * @after closeConnections,checkForMessage
     */
    public function extractor() 
    { 
        # check for permissions
        $access = Permissions::checkForAuthorization($this->authenticatedUser,__CLASS__,__FUNCTION__);

        if($access == false)
        {
            throw new PageException('Access Denied !',403);
        }
        
        # get post data
        $data = $this->app->http->request->retrieve(Request::ALL,Request::POST);
        $result = '';
        
        if(count($data))
        {
            $flag = 'error';
            $message = 'Could not extract sub domains names !';
            
            $text = $this->app->utils->arrays->get($data,'text');
            $type = $this->app->utils->arrays->get($data,'type');
            $unique = $this->app->utils->arrays->get($data,'unique','enabled');
            $ips = [];
            
            if(strlen($text) > 0)
            {
                if(in_array($type,['all-our-ips','all-our-ips-v4','all-our-ips-v6']))
                {
                    $res = ServerVmta::all(ServerVmta::FETCH_ARRAY,['status = ?','Activated']);
                    
                    foreach ($res as $row) 
                    {
                        $ips[] = trim($row['ip']);
                    }
                    
                    $ips = array_unique($ips);
                }
                
                switch ($type) 
                {
                    case 'all-ips':
                    {
                        $matches = [];
                        preg_match_all("/\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}/",$text, $matches);
                        
                        if($unique == 'enabled')
                        {
                            $matches[0] = array_unique($matches[0]);
                        }
                        
                        $result .= implode(PHP_EOL,$matches[0]);
                    
                        $matches = [];
                        preg_match_all("/((([0-9A-Fa-f]{1,4}:){7}([0-9A-Fa-f]{1,4}|:))|(([0-9A-Fa-f]{1,4}:){6}(:[0-9A-Fa-f]{1,4}|((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3})|:))|(([0-9A-Fa-f]{1,4}:){5}(((:[0-9A-Fa-f]{1,4}){1,2})|:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3})|:))|(([0-9A-Fa-f]{1,4}:){4}(((:[0-9A-Fa-f]{1,4}){1,3})|((:[0-9A-Fa-f]{1,4})?:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3}))|:))|(([0-9A-Fa-f]{1,4}:){3}(((:[0-9A-Fa-f]{1,4}){1,4})|((:[0-9A-Fa-f]{1,4}){0,2}:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3}))|:))|(([0-9A-Fa-f]{1,4}:){2}(((:[0-9A-Fa-f]{1,4}){1,5})|((:[0-9A-Fa-f]{1,4}){0,3}:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3}))|:))|(([0-9A-Fa-f]{1,4}:){1}(((:[0-9A-Fa-f]{1,4}){1,6})|((:[0-9A-Fa-f]{1,4}){0,4}:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3}))|:))|(:(((:[0-9A-Fa-f]{1,4}){1,7})|((:[0-9A-Fa-f]{1,4}){0,5}:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3}))|:)))(%.+)?/",$text, $matches);
                        
                        if($unique == 'enabled')
                        {
                            $matches[0] = array_unique($matches[0]);
                        }
                        
                        $result .= implode(PHP_EOL,$matches[0]);
                        
                        break;
                    }
                    case 'all-ips-v4':
                    {
                        $matches = [];
                        preg_match_all("/\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}/",$text, $matches);
                        
                        if($unique == 'enabled')
                        {
                            $matches[0] = array_unique($matches[0]);
                        }
                        
                        $result .= implode(PHP_EOL,$matches[0]);
                    
                        break;
                    }
                    case 'all-ips-v6':
                    {
                        $matches = [];
                        preg_match_all("/((([0-9A-Fa-f]{1,4}:){7}([0-9A-Fa-f]{1,4}|:))|(([0-9A-Fa-f]{1,4}:){6}(:[0-9A-Fa-f]{1,4}|((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3})|:))|(([0-9A-Fa-f]{1,4}:){5}(((:[0-9A-Fa-f]{1,4}){1,2})|:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3})|:))|(([0-9A-Fa-f]{1,4}:){4}(((:[0-9A-Fa-f]{1,4}){1,3})|((:[0-9A-Fa-f]{1,4})?:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3}))|:))|(([0-9A-Fa-f]{1,4}:){3}(((:[0-9A-Fa-f]{1,4}){1,4})|((:[0-9A-Fa-f]{1,4}){0,2}:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3}))|:))|(([0-9A-Fa-f]{1,4}:){2}(((:[0-9A-Fa-f]{1,4}){1,5})|((:[0-9A-Fa-f]{1,4}){0,3}:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3}))|:))|(([0-9A-Fa-f]{1,4}:){1}(((:[0-9A-Fa-f]{1,4}){1,6})|((:[0-9A-Fa-f]{1,4}){0,4}:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3}))|:))|(:(((:[0-9A-Fa-f]{1,4}){1,7})|((:[0-9A-Fa-f]{1,4}){0,5}:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3}))|:)))(%.+)?/",$text, $matches);
                        
                        if($unique == 'enabled')
                        {
                            $matches[0] = array_unique($matches[0]);
                        }
                        
                        $result .= implode(PHP_EOL,$matches[0]);
                    
                        break;
                    }
                    case 'all-our-ips':
                    {
                        $matches = [];
                        preg_match_all("/\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}/",$text, $matches);
                        
                        if($unique == 'enabled')
                        {
                            $matches[0] = array_unique($matches[0]);
                        }
                        
                        $result .= implode(PHP_EOL,$matches[0]);
                    
                        $matches = [];
                        preg_match_all("/((([0-9A-Fa-f]{1,4}:){7}([0-9A-Fa-f]{1,4}|:))|(([0-9A-Fa-f]{1,4}:){6}(:[0-9A-Fa-f]{1,4}|((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3})|:))|(([0-9A-Fa-f]{1,4}:){5}(((:[0-9A-Fa-f]{1,4}){1,2})|:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3})|:))|(([0-9A-Fa-f]{1,4}:){4}(((:[0-9A-Fa-f]{1,4}){1,3})|((:[0-9A-Fa-f]{1,4})?:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3}))|:))|(([0-9A-Fa-f]{1,4}:){3}(((:[0-9A-Fa-f]{1,4}){1,4})|((:[0-9A-Fa-f]{1,4}){0,2}:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3}))|:))|(([0-9A-Fa-f]{1,4}:){2}(((:[0-9A-Fa-f]{1,4}){1,5})|((:[0-9A-Fa-f]{1,4}){0,3}:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3}))|:))|(([0-9A-Fa-f]{1,4}:){1}(((:[0-9A-Fa-f]{1,4}){1,6})|((:[0-9A-Fa-f]{1,4}){0,4}:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3}))|:))|(:(((:[0-9A-Fa-f]{1,4}){1,7})|((:[0-9A-Fa-f]{1,4}){0,5}:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3}))|:)))(%.+)?/",$text, $matches);
                        
                        if($unique == 'enabled')
                        {
                            $matches[0] = array_unique($matches[0]);
                        }
                        
                        if(count($matches[0]))
                        {
                            foreach ($matches[0] as $ip) 
                            {
                                if(in_array(trim($ip),$ips))
                                {
                                    $result .= trim($ip) . PHP_EOL;
                                }
                            }
                        }
                        
                        break;
                    }
                    case 'all-our-ips-v4':
                    {
                        $matches = [];
                        preg_match_all("/\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}/",$text, $matches);
                        
                        if($unique == 'enabled')
                        {
                            $matches[0] = array_unique($matches[0]);
                        }
                        
                        if(count($matches[0]))
                        {
                            foreach ($matches[0] as $ip) 
                            {
                                if(in_array(trim($ip),$ips))
                                {
                                    $result .= trim($ip) . PHP_EOL;
                                }
                            }
                        }
                    
                        break;
                    }
                    case 'all-our-ips-v6':
                    {
                        $matches = [];
                        preg_match_all("/((([0-9A-Fa-f]{1,4}:){7}([0-9A-Fa-f]{1,4}|:))|(([0-9A-Fa-f]{1,4}:){6}(:[0-9A-Fa-f]{1,4}|((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3})|:))|(([0-9A-Fa-f]{1,4}:){5}(((:[0-9A-Fa-f]{1,4}){1,2})|:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3})|:))|(([0-9A-Fa-f]{1,4}:){4}(((:[0-9A-Fa-f]{1,4}){1,3})|((:[0-9A-Fa-f]{1,4})?:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3}))|:))|(([0-9A-Fa-f]{1,4}:){3}(((:[0-9A-Fa-f]{1,4}){1,4})|((:[0-9A-Fa-f]{1,4}){0,2}:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3}))|:))|(([0-9A-Fa-f]{1,4}:){2}(((:[0-9A-Fa-f]{1,4}){1,5})|((:[0-9A-Fa-f]{1,4}){0,3}:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3}))|:))|(([0-9A-Fa-f]{1,4}:){1}(((:[0-9A-Fa-f]{1,4}){1,6})|((:[0-9A-Fa-f]{1,4}){0,4}:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3}))|:))|(:(((:[0-9A-Fa-f]{1,4}){1,7})|((:[0-9A-Fa-f]{1,4}){0,5}:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3}))|:)))(%.+)?/",$text, $matches);
                        
                        if($unique == 'enabled')
                        {
                            $matches[0] = array_unique($matches[0]);
                        }
                        
                        if(count($matches[0]))
                        {
                            foreach ($matches[0] as $ip) 
                            {
                                if(in_array(trim($ip),$ips))
                                {
                                    $result .= trim($ip) . PHP_EOL;
                                }
                            }
                        }
                    
                        break;
                    }
                    case 'all-emails':
                    {
                        $matches = [];
                        preg_match_all('/[a-z0-9_\-\+\.]+@[a-z0-9\-]+\.([a-z]{2,4})(?:\.[a-z]{2})?/i',$text, $matches);
                        
                        if($unique == 'enabled')
                        {
                            $matches[0] = array_unique($matches[0]);
                        }
                        
                        $result .= implode(PHP_EOL,$matches[0]);
                    
                        break;
                    }
                    case 'all-senders':
                    {
                        $matches = [];
                        preg_match_all('/[a-z0-9_\-\+\.]+@[a-z0-9\-]+\.([a-z]{2,4})(?:\.[a-z]{2})?/i',$text, $matches);
                        
                        if($unique == 'enabled')
                        {
                            $matches[0] = array_unique($matches[0]);
                        }
                        
                        foreach ($matches[0] as $match)
                        {
                            $result .= $this->app->utils->arrays->first(explode('@',$match)) . PHP_EOL;
                        }
                    
                        break;
                    }
                }
                
                $flag = 'success';
                $message = 'Values extracted successfully !';
            }
            
            # stores the message in the session 
            Page::registerMessage($flag, $message);
        }
        
        # set menu status
        $this->masterView->set([
            'tools' => 'true',
            'extractor' => 'true'
        ]);
        
        # set data to the page view
        $this->pageView->set([
            'result' => $result
        ]);
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
    
    /**
     * @name checkForMessage
     * @description checks for session messages
     * @once
     * @protected
     */
    public function checkForMessage() 
    {
        # check for message 
        Page::checkForMessage($this);
    }
}


