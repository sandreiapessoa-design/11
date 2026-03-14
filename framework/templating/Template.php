<?php declare(strict_types=1); namespace IR\Templating; if (!defined('IR_START')) exit('<pre>No direct script access allowed</pre>');
/**
 * @framework       iResponse Framework 
 * @version         1.0
 * @author          Amine Idrissi <contact@iresponse.tech>
 * @date            2024
 * @name            Template.php	
 */

# php defaults 
use \Throwable;

# core
use IR\Core\Base as Base;

# utilities
use IR\Utils\Types\Arrays as Arrays;
use IR\Utils\Types\Strings as Strings;

# exceptions
use IR\Exceptions\Types\TemplateException as TemplateException;

/**
 * @name Template
 * @description Template class
 */
class Template extends Base
{
    /**
     * @name parse
     * @description creates a working function for the template 
     * @access public
     * @param string $templatePath the template path 
     * @return Template
     * @throws TemplateException
     */
    public function parse(string $templatePath) : Template
    {
        if (!$this->_implementation instanceof Implementation) 
        {
            throw new TemplateException('Unsupported Implementation');
        }

        try 
        {
            $array = $this->_array(file_get_contents($templatePath));
            $tree = $this->_tree($array["all"]);
            $this->_code = $this->_header . $this->_script($tree) . $this->_footer;
            $this->_function = create_function("\$_data", $this->_code);
        } 
        catch (Throwable $e) 
        {
            $line = $e->getLine();
            
            if($this->_code != null)
            {
                $words = Strings::getInstance()->similarWords(str_replace(['$_text[] =  ',';'],'',Arrays::getInstance()->get(explode(PHP_EOL,$this->_code),($e->getLine()-1))),$e->getMessage());

                foreach (explode(PHP_EOL,file_get_contents($templatePath)) as $lineNumber => $row) 
                {
                    foreach ($words as $word) 
                    {
                        if (strpos($row,$word) !== false) 
                        {
                           $line = intval($lineNumber) + 1;
                           break;
                        }
                    }  
                }
            }
            

            throw new TemplateException($e->getMessage(),$e->getCode(),null,$templatePath,$line);
        }

        return $this;
    }

    /**
     * @name process
     * @description checks for the existence of the protected $_function property and throws a TemplateException exception if it is not present. 
     * @notice it then tries to execute the generated function with the $data passed to it , if the function errors, another TemplateException exception is thrown.
     * @access public
     * @param array $data the data passed to the template
     * @return string
     * @throws TemplateException
     */
    public function process(string $templatePath,array $data = []) 
    {
        # parse the function template 
        $this->parse($templatePath);
        
        if ($this->_function == null) 
        {
            throw new TemplateException('No function defined in the parser');
        }

        try 
        {
            $function = $this->_function;
            return $function($data);
        } 
        catch (Throwable $e) 
        {
            $line = $e->getLine();
            $words = Strings::getInstance()->similarWords(str_replace(['$_text[] =  ',';'],'',Arrays::getInstance()->get(explode(PHP_EOL,$this->_code),($e->getLine()-1))),$e->getMessage());

            foreach (explode(PHP_EOL,file_get_contents($templatePath)) as $lineNumber => $row) 
            {
                foreach ($words as $word) 
                {
                    if (strpos($row,$word) !== false) 
                    {
                       $line = intval($lineNumber) + 1;
                       break;
                    }
                }  
            }

            throw new TemplateException($e->getMessage(),$e->getCode(),null,$templatePath,$line);
        }
    }
     
    /**
     * @name _arguments
     * @description returns the bits between the {...} characters in a neat associative array if the expression has a specific argument format (such as for, foreach, or macro).
     * @access protected
     * @param string $source the chunk of template
     * @param string $expression the expression to check for arguments in
     * @return array
     */
    protected function _arguments(string $source, string $expression) : array
    {
        $args = $this->_array($expression,[
            $expression => [
                "opener" => "{",
                "closer" => "}"
            ]
        ]);
        $tags = $args["tags"];
        $arguments = [];
        $sanitized = Strings::getInstance()->sanitize($expression, "()[],.<>*$@");

        foreach ($tags as $i => $tag) 
        {
            $sanitized = str_replace($tag, "(.*)", $sanitized);
            $tags[$i] = str_replace(["{", "}"], "", $tag);
        }

        $matches = [];
        if (preg_match("#{$sanitized}#", $source, $matches)) 
        {
            foreach ($tags as $i => $tag) 
            {
                $arguments[$tag] = $matches[$i + 1];
            }
        }
        return $arguments;
    }

    /**
     * @name _tag
     * @description checks if the chunk of template passed , is a tag or a plain string.
     * @notice it will return false for a non-match , it then extracts all the bits between the opener and closer strings.
     * @access protected
     * @param string $source the chunk of template
     * @return mixed
     */
    protected function _tag(string $source)
    {
        $tag = null;
        $arguments = [];

        $match = $this->_implementation->match($source);

        if ($match == null) 
        {
            return false;
        }

        $delimiter = $match["delimiter"];
        $type = $match["type"];

        $start = strlen($type["opener"]);
        $end = strpos($source, $type["closer"]);
        $extract = substr($source, $start, $end - $start);
        $extract = ($extract === false) ? '' : $extract;
        
        if (isset($type["tags"])) 
        {
            $tags = implode("|", array_keys($type["tags"]));
            $regex = "#^(/){0,1}({$tags})\s*(.*)$#";

            $matches = [];
            
            if (!preg_match($regex, $extract, $matches)) 
            {
                return false;
            }
            
            $tag = $matches[2];
            $extract = $matches[3];

            $closer = !!$matches[1];
        }

        if ($tag && $closer) 
        {
            return [
                "tag" => $tag,
                "delimiter" => $delimiter,
                "closer" => true,
                "source" => false,
                "arguments" => false,
                "isolated" => $type["tags"][$tag]["isolated"]
            ];
        }

        if (isset($type["arguments"])) 
        {
            $arguments = $this->_arguments($extract, $type["arguments"]);
        } 
        else if ($tag && isset($type["tags"][$tag]["arguments"])) 
        {
            $arguments = $this->_arguments($extract, $type["tags"][$tag]["arguments"]);
        }

        return [
            "tag" => $tag,
            "delimiter" => $delimiter,
            "closer" => false,
            "source" => $extract,
            "arguments" => $arguments,
            "isolated" => (!empty($type["tags"]) ? $type["tags"][$tag]["isolated"] : false)
        ];
    }

    /**
     * @name _array
     * @description deconstructs a template string into arrays of tags, text, and a combination of the two
     * @access protected
     * @param string $source the chunk of template
     * @return array
     */
    protected function _array(string $source) : array
    {
        $parts = [];
        $tags = [];
        $all = [];

        $type = null;
        $delimiter = null;

        while ($source) 
        {
            $match = $this->_implementation->match($source);

            $type = $match["type"];
            $delimiter = $match["delimiter"];

            $opener = strpos($source, $type["opener"]);
            $closer = strpos($source, $type["closer"]) + strlen($type["closer"]);

            if ($opener !== false) 
            {
                $parts[] = substr($source, 0, $opener);
                $tags[] = substr($source, $opener, $closer - $opener);
                $source = substr($source, $closer);
            } 
            else 
            {
                $parts[] = $source;
                $source = "";
            }
        }

        foreach ($parts as $i => $part) 
        {
            $all[] = $part;
            if (isset($tags[$i])) 
            {
                $all[] = $tags[$i];
            }
        }
        return [
            "text" => Arrays::getInstance()->clean($parts),
            "tags" => Arrays::getInstance()->clean($tags),
            "all" => Arrays::getInstance()->clean($all)
        ];
    }

    /**
     * @name _tree
     * @description it loops through the array of template segments, 
     * generated by the _[] method, and organizes 
     * them into a hierarchical structure. 
     * Plain text nodes are simply assigned as-is to 
     * the tree, while additional metadata is generated
     * and assigned with the tags. 
     * @notice certain statements have an isolated property. 
     * This specifies whether text is allowed before the statement.
     * When the loop gets to an isolated tag, it removes the preceding
     * segment (as long as it is a plain text segment), so that the resultant
     * function code is syntactically correct.
     * @access protected
     * @param array $array The array of template segments
     * @return array
     */
    protected function _tree(array $array) : array
    {
        $root = [
            "children" => []
        ];
        
        $current = & $root;

        foreach ($array as $i => $node) 
        {
            $result = $this->_tag($node);

            if ($result) 
            {
                $tag = isset($result["tag"]) ? $result["tag"] : "";
                $arguments = isset($result["arguments"]) ? $result["arguments"] : "";

                if ($tag) 
                {
                    if (!$result["closer"]) 
                    {
                        $last = Arrays::getInstance()->last($current["children"]);

                        if ($result["isolated"] && is_string($last)) 
                        {
                            array_pop($current["children"]);
                        }

                        $current["children"][] = [
                            "index" => $i,
                            "parent" => &$current,
                            "children" => [],
                            "raw" => $result["source"],
                            "tag" => $tag,
                            "arguments" => $arguments,
                            "delimiter" => $result["delimiter"],
                            "number" => sizeof($current["children"])
                        ];
                        
                        $current = & $current["children"][sizeof($current["children"]) - 1];
                    } 
                    else if (isset($current["tag"]) && $result["tag"] == $current["tag"]) 
                    {
                        $start = $current["index"] + 1;
                        $length = $i - $start;
                        $current["source"] = implode(array_slice($array, $start, $length));
                        $current = & $current["parent"];
                    }
                } 
                else 
                {
                    $current["children"][] = [
                        "index" => $i,
                        "parent" => &$current,
                        "children" => [],
                        "raw" => $result["source"],
                        "tag" => $tag,
                        "arguments" => $arguments,
                        "delimiter" => $result["delimiter"],
                        "number" => sizeof($current["children"])
                    ];
                }
            } 
            else 
            {
                $current["children"][] = $node;
            }
        }
        
        return $root;
    }

    /**
     * @name _script
     * @description walks the hierarchy (generated by the _tree() method), parses plain text nodes, and indirectly invokes the handler for each valid tag. 
     * @access protected
     * @param array $tree The hierarchy tree array
     * @return string
     */
    protected function _script($tree) : string
    {
        $content = [];

        if (is_string($tree)) 
        {
            $tree = addslashes($tree);
            return "\$_text[] = \"{$tree}\";";
        }

        if (sizeof($tree["children"]) > 0) 
        {
            foreach ($tree["children"] as $child) 
            {
                $content[] = $this->_script($child);
            }
        }

        if (isset($tree["parent"])) 
        {
            return $this->_implementation->handle($tree, implode($content));
        }

        return implode($content);
    }
        
    /**
     * @readwrite
     * @access protected 
     * @var Implementation
     */
    protected $_implementation;

    /**
     * @readwrite
     * @access protected 
     * @var string
     */
    protected $_header = "\nif(is_array(\$_data) && sizeof(\$_data))\nextract(\$_data); \n\$_text = [];\n";

    /**
     * @readwrite
     * @access protected 
     * @var string
     */
    protected $_footer = "\nreturn implode(\$_text);\n";

    /**
     * @readwrite
     * @access protected 
     * @var string
     */
    protected $_code;

    /**
     * @readwrite
     * @access protected 
     * @var string
     */
    protected $_function;
    
    /**
     * @readwrite
     * @access protected 
     * @var string
     */
    protected $_data;
}


