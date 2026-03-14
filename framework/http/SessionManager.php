<?php declare(strict_types=1); namespace IR\Http; if (!defined('IR_START')) exit('<pre>No direct script access allowed</pre>');
/**
 * @framework       iResponse Framework 
 * @version         1.0
 * @author          Amine Idrissi <contact@iresponse.tech>
 * @date            2024
 * @name            Session.php	
 */

/**
 * @name Session
 * @description sessions class
 */
class SessionManager
{
    /**
     * @name getInstance
     * @description singleton access to constructor
     * @access public | static
     * @return SessionManager
     */
    public static function getInstance() : SessionManager
    {
        if(self::$_instance == null)
        {
            self::$_instance = new SessionManager();
            self::$_instance->_session = new AppSessionHandler();
        }
        
        return self::$_instance;
    }
    
    /**
     * @name connect
     * @description connect session
     * @access public
     * @return
     */
    public function connect() 
    {
        $this->_session->connect();
    }
    
    /**
     * @name close
     * @description close session
     * @access public
     * @return
     */
    public function close() 
    {
        $this->_session->release();
    }

    /**
     * @name disconnect
     * @description destroys the session and empty data array
     * @access public
     * @return
     */
    public function disconnect() 
    {
        # destroy the session and unset everything
        $this->_session->connect();
        $this->_session->disconnect();
    }


    /**
     * @name get
     * @description get variables from $_SESSION array.
     * @access public
     * @param string $property the key of the value to get from the session
     * @param mixed $default the default value retrieved incase of no result found
     * @return mixed
     */
    public function get($property,$delete = false,$default = null) 
    {
        $this->_session->connect();
        
        $result = $default;
        
        if ($this->exists($property)) 
        {
            $result = $_SESSION[$property];
        } 
        
        $this->_session->release();
        
        if($delete == true)
        {
            $this->delete($property);
        }
            
        return $result;
    }

    /**
     * @name set
     * @description set variables to $_SESSION array
     * @access public
     * @param string $property the property to set to the session
     * @param mixed $value the balue to set to the property
     * @param boolean $makeItArray make the value as a new array inside the session
     * @return
     */
    public function set($property, $value = null, $makeItArray = false) 
    {
        $this->_session->connect();
        
        if (is_array($property)) 
        {
            foreach ($property as $key => $singleProperty) 
            {
                $_SESSION[$key] = $singleProperty;
            }
        } 
        else 
        {
            if ($makeItArray == false) 
            {
                $_SESSION[$property] = $value;
            } 
            else 
            {
                $_SESSION[$property][] = $value;
            }
        }
        
        $this->_session->release();
    }

    /**
     * @name delete
     * @description deletes variables from $_SESSION array
     * @access public
     * @param string $property the property to delete from the session
     * @return
     */
    public function delete($property) 
    {
        $this->_session->connect();
        
        if (is_array($property)) 
        {
            foreach ($property as $key => $singleProperty) 
            {
                $singleProperty = null;
                unset($_SESSION[$key]);
            }
        } 
        else 
        {
            $_SESSION[$property] = null;
            unset($_SESSION[$property]);
        }
        
        $this->_session->release();
    }

    /**
     * @name exists
     * @description checks if exists
     * @access public
     * @param string $property the key of the value to get from the session
     * @return boolean
     */
    public function exists($property) 
    {
        $this->_session->connect();
        $exists = key_exists($property,$_SESSION);
        $this->_session->release();
        return $exists;
    }
        
    /**
     * @name unserialize
     * @description unserialize a session data
     * @access public
     * @param string $session session content
     * @return boolean
     */
    public function unserialize($session) : array
    {
        $method = ini_get('session.serialize_handler');
        
        switch ($method) 
        {
            case 'php' :
            {
                return $this->unserializePHPSession($session);
            }  
            case 'php_binary':
            {
                return $this->unserializePHPBinary($session);
            }
            default:
            {
                return [];
            }
        }
    }

    private function unserializePHPSession($session) : array
    {
        $result = [];
        $offset = 0;
        
        while ($offset < strlen($session)) 
        {
            if(strstr(substr($session, $offset), "|")) 
            {
                $pos = strpos($session, "|", $offset);
                $num = $pos - $offset;
                $varname = substr($session, $offset, $num);
                $offset += $num + 1;
                $data = unserialize(substr($session, $offset));
                $result[$varname] = $data;
                $offset += strlen(serialize($data));
            }
        }
        
        return $result;
    }

    private function unserializePHPBinary($session) : array
    {
        $result = [];
        $offset = 0;
        
        while ($offset < strlen($session)) 
        {
            $num = ord($session[$offset]);
            $offset += 1;
            $varname = substr($session, $offset, $num);
            $offset += $num;
            $data = unserialize(substr($session, $offset));
            $result[$varname] = $data;
            $offset += strlen(serialize($data));
        }
        
        return $result;
    }
    
    /**
     * @name __construct
     * @description class constructor
     * @access private
     * @return SessionManager
     */
    private function __construct() {}

    /**
     * @name __clone
     * @description preventing cloning object
     * @access public
     * @return SessionManager
     */
    public function __clone()
    {
        return (self::$_instance != null) ? self::$_instance : new SessionManager();  
    }
    
    /** 
     * @readwrite
     * @access private 
     * @var SessionManager
     */ 
    private static $_instance;
    
    /** 
     * @readwrite
     * @access private 
     * @var AppSessionHandler
     */ 
    private $_session;
}


