<?php declare(strict_types=1); namespace IR\App\Libraries; if (!defined('IR_START')) exit('<pre>No direct script access allowed</pre>');
/**
 * @framework       iResponse Framework 
 * @version         1.0
 * @author          Amine Idrissi <contact@iresponse.tech>
 * @date            2018
 * @name            Godaddy.php	
 */

# core 
use IR\Core\Base as Base;
use IR\Core\Application as Application;

# models 
use IR\App\Models\Admin\GoDaddy as GodaddyAccount;

# http 
use IR\Http\Request as Request;

# exceptions
use IR\Exceptions\Types\HTTPException as HTTPException;

/**
 * @name Godaddy
 * @description Godaddy Helper
 */
class Godaddy extends Base
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
    protected $_customer_id;

    /**
     * @readwrite
     * @access protected 
     * @var String
     */
    protected $_access_key;

    /**
     * @readwrite
     * @access protected 
     * @var String
     */
    protected $_secret_key;
    
    /*
     * instantiate a namecheap object
     * @credentials array associative array of namecheap API credentials
     * @sandbox boolean whether to use the Godaddy Sandbox or the real site
     * @return object a namecheap object
     */

    public function __construct($parameters = [])
    {
        parent::__construct($parameters);
        
        $this->_url = 'https://api.godaddy.com';
        
        $account = GodaddyAccount::first(GodaddyAccount::FETCH_ARRAY,['id = ? AND status = ?',[$this->_account_id,'Activated']]);
        
        if(count($account))
        {
            $this->_name = $account['name'];
            $this->_customer_id = $account['customer_id'];
            $this->_access_key = $account['access_key'];
            $this->_secret_key = $account['secret_key'];
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
        $parameters = ['status' => 'ACTIVE','limit' => '500'];
        $headers = ['Accept: application/json','Authorization: sso-key ' . $this->_access_key . ':' . $this->_secret_key];
        $response = Application::getCurrent()->http->request->curl($this->_url . RDS . 'v1/domains',$parameters,Request::GET,$headers);
        
        if($response != null)
        {
            $json = json_decode($response,true);
            
            if(count($json))
            {
                foreach ($json as $domain) 
                {
                    $domains[$domain['domain']] = [
                        'name' => $domain['domain'],
                        'expiration-date' => date('Y-m-d',strtotime($domain['expires']))
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

        $headers = ['Accept: application/json','Authorization: sso-key ' . $this->_access_key . ':' . $this->_secret_key];
        $response = Application::getCurrent()->http->request->curl($this->_url . RDS . 'v1/domains/' . $domain . '/records',[],Request::GET,$headers);
        
        if($response != null)
        {
            $json = json_decode($response,true);
            
            if($json != null && $json != false && is_array($json) && count($json) > 0)
            {   
                foreach ($json as $value) 
                {
                    $records[] = [
                        'type' => $value['type'],
                        'host' => $value['name'],
                        'value' => $value['data'],
                        'ttl' => $value['ttl']
                    ];
                }
            }
        }

        return $records;
    }
}


