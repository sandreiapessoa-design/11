<?php declare(strict_types=1); namespace IR\Templating; if (!defined('IR_START')) exit('<pre>No direct script access allowed</pre>');
/**
 * @framework       iResponse Framework 
 * @version         1.0
 * @author          Amine Idrissi <contact@iresponse.tech>
 * @date            2024
 * @name            Implementation.php	
 */

# php defaults
use Exception;

# core 
use IR\Core\Base as Base;

# utilities
use IR\Utils\Types\Strings as Strings;

# exceptions
use IR\Exceptions\Types\TemplateException as TemplateException;

/**
 * @name Implementation
 * @description Implementation class
 */
class Implementation extends Base
{
    /**
     * @name handle
     * @description creates a working function for the template 
     * @access public
     * @param array $node the mapping array node
     * @param string $content the content to parse
     * @return
     * @throws TemplateException
     */
    public function handle($node, $content) 
    {
        try 
        {
            $handler = $this->_handler($node);
            return call_user_func_array([$this, $handler],[$node, $content]);
        } 
        catch (Exception $e) 
        {
            throw new TemplateException($e->getMessage(),500,$e);
        }
    }

    /**
     * @name match
     * @description evaluates a $source string to determine if it matches a tag or statement.
     * @access public
     * @param string $source the template content 
     * @return array
     * @throws TemplateException
     */
    public function match($source) 
    {
        $type = null;
        $delimiter = null;
        
        foreach ($this->_map as $_delimiter => $_type) 
        {
            if (!$delimiter || Strings::getInstance()->indexOf($source, $type["opener"]) == -1) 
            {
                $delimiter = $_delimiter;
                $type = $_type;
            }

            $indexOf = Strings::getInstance()->indexOf($source, $_type["opener"]);

            if ($indexOf > -1) 
            {
                if (Strings::getInstance()->indexOf($source, $type["opener"]) > $indexOf) 
                {
                    $delimiter = $_delimiter;
                    $type = $_type;
                }
            }
        }

        if ($type == null) 
        {
            return null;
        }

        return [
            "type" => $type,
            "delimiter" => $delimiter
        ];
    }

    /**
     * @name _handler
     * @description determines the correct handler method to execute
     * @access protected
     * @param array $node The mapping array node
     * @return string
     */
    protected function _handler($node) 
    {
        if (empty($node["delimiter"])) 
        {
            return null;
        }

        if (!empty($node["tag"])) 
        {
            return $this->_map[$node["delimiter"]]["tags"][$node["tag"]]["handler"];
        }

        return $this->_map[$node["delimiter"]]["handler"];
    } 
}


