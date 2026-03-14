<?php declare(strict_types=1); namespace IR\App\Libraries; if (!defined('IR_START')) exit('<pre>No direct script access allowed</pre>');
/**
 * @framework       iResponse Framework 
 * @version         1.0
 * @author          Amine Idrissi <contact@iresponse.tech>
 * @date            2024
 * @name            Namecheap.php	
 */

# core 
use IR\Core\Base as Base;
use IR\Core\Application as Application;

# models 
use IR\App\Models\Admin\Namecheap as NamecheapAccount;

# http 
use IR\Http\Request as Request;

# exceptions
use IR\Exceptions\Types\HTTPException as HTTPException;

/**
 * @name Namecheap
 * @description Namecheap Helper
 */
class Namecheap extends Base
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
     * @sandbox boolean whether to use the Namecheap Sandbox or the real site
     * @return object a namecheap object
     */

    public function __construct($parameters = [])
    {
        parent::__construct($parameters);
        
        $this->_url = 'https://api.namecheap.com/xml.response';
        
        $account = NamecheapAccount::first(NamecheapAccount::FETCH_ARRAY,['id = ? AND status = ?',[$this->_account_id,'Activated']]);
        
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
        $result = $this->_getDomainsPerPage();

        if(count($result))
        {
            $domains = $result['domains'];
            
            if($result['pages'] > 1)
            {
                if($result['pages'] == 2)
                {
                    $result = $this->_getDomainsPerPage(2);
                    $domains = array_merge($domains,$result['domains']);
                }
                else
                {
                    for ($index = 2; $index <= $result['pages']; $index++) 
                    {
                        $result = $this->_getDomainsPerPage($index);
                        $domains = array_merge($domains,$result['domains']);
                    }
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

        # get response
        $parameters = [
            'ApiUser' => $this->_username ,
            'ApiKey' => $this->_apiKey ,
            'UserName' => $this->_username ,
            'ClientIP' => $this->_ip ,
            'Command' => 'namecheap.domains.dns.getHosts'
        ];

        $parts = explode('.', $domain);
        
        if(count($parts) < 2)
        {
            throw new HTTPException('Namecheap API Error : Invalid domain name !');
        }
        
        $parameters['SLD'] = $parts[0];
        $parameters['TLD'] = count($parts) > 2 ? $parts[1] . "." . $parts[2] : $parts[1];
        
        $response = Application::getCurrent()->http->request->curl($this->_url,$parameters,Request::GET);

        if($response != null)
        {
            $response = \simplexml_load_string($response,'SimpleXMLElement', LIBXML_NOCDATA);
            
            # check for errors
            if($response->attributes()->Status != 'OK')
            {
                throw new HTTPException('Namecheap API Error : ' . $response->Errors->Error);
            }
            
            foreach ($response->CommandResponse->DomainDNSGetHostsResult->host as $record)
            {
                $temp = [];

                foreach ($record->attributes() as $key => $value)
                {
                    $temp[$key] = (string) $value;
                }

                $records[] = $temp;
            }
        }

        return $records;
    }
    
    /**
     * @name _getDomainsPerPage
     * @description get Domains Per Page
     * @param integer $page
     * @param integer $pagesize
     * @return array $domains
     */
    protected function _getDomainsPerPage($page = 1, $pagesize = 100)
    {
        $results = [
            'pages' => 1,
            'domains' => []
        ];

        # get response
        $parameters = [
            'ApiUser' => $this->_username ,
            'ApiKey' => $this->_apiKey ,
            'UserName' => $this->_username ,
            'ClientIP' => $this->_ip ,
            'Command' => 'namecheap.domains.getList',
            'Page' => $page,
            'PageSize' => $pagesize,
            'SortBy' => 'NAME'
        ];
        
        $response = Application::getCurrent()->http->request->curl($this->_url,$parameters,Request::GET); 
        
        if($response != null)
        {
            $xml = \simplexml_load_string($response,'SimpleXMLElement', LIBXML_NOCDATA);
            $json = \json_encode($xml); 
            $response = \json_decode($json, true);

            # check for errors
            if(isset($response['Errors']) && count($response['Errors']) > 0)
            {
                throw new HTTPException('Namecheap API Error : ' . $response['Errors']['Error']);
            }
            
            if(isset($response['CommandResponse']) && key_exists('DomainGetListResult',$response['CommandResponse']) && count($response['CommandResponse']['DomainGetListResult']['Domain']))
            {
                $total = intval($response['CommandResponse']['Paging']['TotalItems']);
                $results['pages'] = $total > 0 ?ceil($total / $pagesize) : 1;
                
                foreach ($response['CommandResponse']['DomainGetListResult']['Domain'] as $domain)
                {
                    $domain = key_exists('@attributes',$domain) ? $domain['@attributes'] : $domain;
 
                    if(key_exists('Name',$domain) && $domain['IsExpired'] != 'true' && $domain['IsLocked'] != 'true')
                    {
                        $results['domains'][$domain['Name']] = [
                            'name' => $domain['Name'] ,
                            'expiration-date' => date('Y-m-d',strtotime($domain['Expires']))
                        ]; 
                    }
                }
            }
        }

        return $results;
    }
}


