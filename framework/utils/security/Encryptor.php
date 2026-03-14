<?php declare(strict_types=1); namespace IR\Utils\Security; if (!defined('IR_START')) exit('<pre>No direct script access allowed</pre>');
/**
 * @framework       iResponse Framework 
 * @version         1.0
 * @author          Amine Idrissi <contact@iresponse.tech>
 * @date            2024
 * @name            Encryptor.php	
 */

/**
 * @name Encryptor
 * @description encryption class
 */
class Encryptor
{
    /**
     * @name getInstance
     * @description singleton access to constructor
     * @access public | static
     * @return Encryptor
     */
    public static function getInstance() : Encryptor
    {
        if(self::$_instance == null)
        {
            self::$_instance = new Encryptor();
        }
        
        return self::$_instance;
    }
    
    /**
     * @name encrypt
     * @description encrypt a value
     * @access public
     * @param $value string 
     * @param $secretKey string
     * @return
     */
    public function encrypt(string $value,string $secretKey) : string
    {
        if($value != '')
        {
            $salt = openssl_random_pseudo_bytes(32);
            $salted = $dx = '';

            while (strlen($salted) < 48) 
            {
                $dx = md5($dx . $secretKey . $salt, true);
                $salted .= $dx;
            }

            $key = substr($salted,0,32);
            $iv = substr($salted,32,16);
            return base64_encode($salt . openssl_encrypt($value,'aes-256-cbc',$key,OPENSSL_RAW_DATA,$iv));
        }
        
        return $value;
    }

    /**
     * @name decrypt
     * @description decrypt encrypted value
     * @access static
     * @param $value string 
     * @param $secretKey string
     * @return
     */
    public function decrypt(string $value,string $secretKey)
    {
        if($value != '')
        {
            $encrypted = base64_decode($value);
            
            if($encrypted !== FALSE)
            {
                $salt = substr($encrypted,0,32);
                $encrypted = substr($encrypted,32);

                if(!is_string($encrypted))
                {
                    throw new \Exception('Encrypted value should be a string !');
                }

                $salted = $dx = '';
                while (strlen($salted) < 48) 
                {
                    $dx = md5($dx . $secretKey . $salt, true);
                    $salted .= $dx;
                }

                $key = substr($salted,0,32);
                $iv = substr($salted,32,16);

                return openssl_decrypt($encrypted, 'aes-256-cbc', $key,OPENSSL_RAW_DATA, $iv); 
            }
            else
            {
                return $value;
            }
        }
         
        return $value;
    }

    /**
     * @name __construct
     * @description class constructor
     * @access private
     * @return Encryptor
     */
    private function __construct() {}

    /**
     * @name __clone
     * @description preventing cloning object
     * @access public
     * @return Encryptor
     */
    public function __clone()
    {
        return (self::$_instance != null) ? self::$_instance : new Encryptor();  
    }
    
    /** 
     * @readwrite
     * @access private 
     * @var Encryptor
     */ 
    private static $_instance;
}