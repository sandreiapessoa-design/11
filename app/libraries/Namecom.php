<?php declare(strict_types=1); namespace IR\App\Libraries; if (!defined('IR_START')) exit('<pre>No direct script access allowed</pre>');
/**
 * @framework       iResponse Framework 
 * @version         1.0
 * @author          Amine Idrissi <contact@iresponse.tech>
 * @date            2024
 * @name            Namecom.php	
 */

# core 
use IR\Core\Base as Base;
use IR\Core\Application as Application;

# models 
use IR\App\Models\Admin\Namecom as NamecomAccount;

# http 
use IR\Http\Request as Request;

# exceptions
use IR\Exceptions\Types\HTTPException as HTTPException;

/**
 * @name Namecom
 * @description Namecom Helper
 */
class Namecom extends Base
{
    /**
     * @readwrite
     * @access protected 
     * @var integer
     */
    protected $_account_id;
    
    /**
     * @readwrite
     * @access protected 
     * @var array
     */
    protected $_url;

    /**
     * @readwrite
     * @access protected 
     * @var String
     */
    protected $_name;
    
    /**
     * @readwrite
     * @access protected 
     * @var String
     */
    protected $_username;

    /**
     * @readwrite
     * @access protected 
     * @var String
     */
    protected $_apiKey;

    /**
     * @readwrite
     * @access protected 
     * @var String
     */
    protected $_ip;
    
    /*
     * instantiate a namecheap object
     * @credentials array associative array of namecheap API credentials
     * @sandbox boolean whether to use the Namecom Sandbox or the real site
     * @return object a namecheap object
     */

    public function __construct($parameters = [])
    {
        parent::__construct($parameters);
        
        $this->_url = 'https://api.name.com/';
        
        $account = NamecomAccount::first(NamecomAccount::FETCH_ARRAY,['id = ? AND status = ?',[$this->_account_id,'Activated']]);
        
        if(count($account))
        {
            $this->_name = $account['name'];
            $this->_apiKey = $account['api_key'];
            $this->_username = $account['username'];
            $this->_ip = $account['white_listed_ip'];
        }
        else
        {
            throw new HTTPException("There is no account with these credentials !");
        }
    }

    /**
     * @name getAllDomains
     * @description get all domains
     * @return array $domains
     */
    public function getAllDomains()
    {
        $domains = [];
        $response = Application::getCurrent()->http->request->curl($this->_url . 'v4/domains',[],Request::GET,false,false,'',"{$this->_username}:{$this->_apiKey}"); 
        
        if($response != null)
        {
            $json = json_decode($response,true);
            
            if(count($json) && key_exists('domains',$json))
            {
                foreach ($json['domains'] as $domain) 
                {
                    $domains[$domain['domainName']] = [
                        'name' => $domain['domainName'],
                        'expiration-date' => date('Y-m-d',strtotime($domain['expireDate']))
                    ];
                }
            }
        }
        
        return $domains;
    }
    
    /**
     * @name getDomainRecords
     * @description get DNS Records of a domain
     * @param string $domain
     * @return boolean
     */
    public function getDomainRecords($domain)
    {
        $records = [];
        $response = Application::getCurrent()->http->request->curl($this->_url . 'v4/domains/' . $domain . '/records',[],Request::GET,false,false,'',"{$this->_username}:{$this->_apiKey}"); 

        if($response != null)
        {
            $json = json_decode($response,true);
            
            if(count($json) && key_exists('records',$json))
            {
                foreach ($json['records'] as $record) 
                {
                    if(!key_exists('host',$record))
                    {
                        $record['host'] = '@';
                    }
                    
                    $records[] = $record;
                }
            }
        }
       
        return $records;
    }
}


