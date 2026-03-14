<?php declare(strict_types=1); namespace IR\Templating; if (!defined('IR_START')) exit('<pre>No direct script access allowed</pre>');
/**
 * @framework       iResponse Framework 
 * @version         1.0
 * @author          Amine Idrissi <contact@iresponse.tech>
 * @date            2024
 * @name            DefaultImplementation.php	
 */

# core 
use IR\Core\Registry as Registry;
use IR\Core\Application as Application;

# utilities
use IR\Utils\Types\Strings as Strings;

# routing
use IR\Routing\Router as Router;

# http
use IR\Http\Request as Request;

/**
 * @name DefaultImplementation
 * @description DefaultImplementation class
 */
class DefaultImplementation extends Implementation
{
    /**
     * @name __construct
     * @description the class constructor
     * @access public
     * @param string $options
     * @return Extended
     */
    public function __construct($options = []) 
    {
        parent::__construct($options);

        $this->_defaultPath = VIEWS_PATH;

        $this->_map = [
            "partial" => [
                "opener" => "{partial",
                "closer" => "}",
                "handler" => "_partial"
            ],
            "include" => [
                "opener" => "{include",
                "closer" => "}",
                "handler" => "_include"
            ],
            "yield" => [
                "opener" => "{yield",
                "closer" => "}",
                "handler" => "_yield"
            ]
        ] + $this->_map;

        $this->_map["statement"]["tags"] = [
            "set" => [
                "isolated" => false,
                "arguments" => "{key}",
                "handler" => "set"
            ],
            "append" => [
                "isolated" => false,
                "arguments" => "{key}",
                "handler" => "append"
            ],
            "prepend" => [
                "isolated" => false,
                "arguments" => "{key}",
                "handler" => "prepend"
            ]
        ] + $this->_map["statement"]["tags"];
    }

    /**
     * @name set
     * @description 
     * @access public
     * @param string $key
     * @param string $value
     * @return
     */
    public function set($key, $value) 
    {
        if (Strings::getInstance()->indexOf($value, "\$_text") > -1) 
        {
            $first = Strings::getInstance()->indexOf($value, "\"");
            $last = Strings::getInstance()->indexOf($value, "\"");
            $value = stripslashes(substr($value, $first + 1, ($last - $first) - 1));
        }

        if (is_array($key)) 
        {
            $key = $this->_getKey($key);
        }

        $this->_setValue($key, $value);
    }

    /**
     * @name append
     * @description 
     * @access public
     * @param string $key
     * @param string $value
     * @return
     */
    public function append($key, $value)
    {
        if (is_array($key))
        {
            $key = $this->_getKey($key);
        }

        $previous = $this->_getValue($key);
        $this->set($key, $previous.$value);
    }

    /**
     * @name prepend
     * @description 
     * @access public
     * @param string $key
     * @param string $value
     * @return
     */
    public function prepend($key, $value)
    {
        if (is_array($key))
        {
            $key = $this->_getKey($key);
        }

        $previous = $this->_getValue($key);
        $this->set($key, $value.$previous);
    }

    /**
     * @name _yield
     * @description 
     * @access protected
     * @param array $tree
     * @param string $content
     * @return
     */
    public function _yield($tree, $content)
    {
        $content = trim($content);
        $key = trim($tree["raw"]);
        $value = addslashes($this->_getValue($key));
        return "\n\$_text[] = \"{$value}\";\n"; 
    }

    /**
     * @name _getKey
     * @description 
     * @access protected
     * @param array $tree
     * @return
     */
    protected function _getKey($tree) 
    {
        if (empty($tree["arguments"]["key"])) 
        {
            return null;
        }

        return trim($tree["arguments"]["key"]);
    }

    /**
     * @name _setValue
     * @description 
     * @access protected
     * @param array $key
     * @param string $value
     * @return
     */
    protected function _setValue($key, $value) 
    {
        if (!empty($key)) 
        {
            $default = $this->getDefaultKey();

            $data = Registry::getInstance()->get($default,[]);
            $data[$key] = $value;

            Registry::getInstance()->set($default, $data);
        }
    }

    /**
     * @name _getValue
     * @description 
     * @access protected
     * @param array $key
     * @return string
     */
    protected function _getValue($key) 
    {
        $data = Registry::getInstance()->get($this->getDefaultKey());

        if (isset($data[$key])) 
        {
            return $data[$key];
        }

        return "";
    }

    /**
     * @name _include
     * @description 
     * @access protected
     * @param array $tree
     * @param array $content
     * @return string
     */
    protected function _include($tree) 
    {
        $file = trim($tree["raw"]);
        
        if(Application::isValid())
        {
            if(Application::getCurrent()->router instanceof Router)
            {
                $file = str_replace('$__page',Application::getCurrent()->router->controller, $file);
            }
        }
        
        $finalFilePath = $this->getDefaultPath() . DS . $file;

        if(file_exists($finalFilePath))
        {
            $template = new Template([
                "implementation" => new self()
            ]);

            if(count(file($finalFilePath,FILE_IGNORE_NEW_LINES)))
            {
                $template->parse($finalFilePath);
                $functionName = 'anon_' . Strings::getInstance()->random(8,true,false,true,false);
                return "function $functionName(\$_data){" . $template->getCode() . "};\n\$_text[] = $functionName(\$_data);\n";
            }  
        }
        
        return '';            
    }

    /**
     * @name _partial
     * @description 
     * @access protected
     * @param array $tree
     * @return string
     */
    protected function _partial($tree)
    {
        $address = trim($tree["raw"], " /");
        if (Strings::getInstance()->indexOf($address, "http") != 0)
        {
            $host = Request::getInstance()->retrieve('HTTP_HOST',Request::SERVER);
            $address = "http://{$host}/{$address}";
        }
        $response = Request::getInstance()->curl(trim($address));
        return "\n\$_text[] = \"{$response}\";\n";
    }
        
    /**
     * @name _echo
     * @description converts the string “{echo $hello}” to “$_text[] = $hello”, so that it is already optimized for our final evaluated function.
     * @access protected
     * @param string $tree
     * @param string $content
     * @return string
     */
    protected function _echo($tree, $content)
    {
        $raw = $this->_script($tree, $content);
        return "\n\$_text[] = {$raw}\n";
    }

    /**
     * @name _script
     * @description converts the string “{:$foo + = 1}” to “$foo + = 1”, so that it is already optimized for our final evaluated function.
     * @access protected
     * @param string $tree
     * @param string $content
     * @return string
     */
    protected function _script($tree)
    {
        $raw = !empty($tree["raw"]) ? $tree["raw"] : "";
        return "{$raw};";
    }

    /**
     * @name _each
     * @description returns the code to perform a foreach loop through an array
     * @access protected
     * @param string $tree
     * @param string $content
     * @return string
     */
    protected function _each($tree, $content)
    {
        $object = $tree["arguments"]["object"];
        $element = $tree["arguments"]["element"];

        return $this->_loop($tree,
            "foreach ({$object} as {$element}_i => {$element}) {
                {$content}
            }"
        );
    }

    /**
     * @name _for
     * @description produces the code to perform a for loop through an array
     * @access protected
     * @param string $tree
     * @param string $content
     * @return string
     */
    protected function _for($tree, $content)
    {
        $object = $tree["arguments"]["object"];
        $element = $tree["arguments"]["element"];

        return $this->_loop($tree, 
            "for ({$element}_i = 0; {$element}_i < sizeof({$object}); {$element}_i++) {
                    {$element} = {$object}[{$element}_i];
                    {$content}
            }"
        );
    }

    /**
     * @name _if
     * @description return code to perform an IF statement in the template
     * @access protected
     * @param string $tree
     * @param string $content
     * @return string
     */
    protected function _if($tree, $content)
    {
        $raw = $tree["raw"];
        return "if({$raw}) {{$content}}";
    }

    /**
     * @name _elseif
     * @description return code to perform an ELSEIF statement in the template
     * @access protected
     * @param string $tree
     * @param string $content
     * @return string
     */
    protected function _elseif($tree, $content)
    {
        $raw = $tree["raw"];
        return "elseif({$raw}) {{$content}}";
    }

    /**
     * @name _else
     * @description return code to perform an ELSE statement in the template
     * @access protected
     * @param string $tree
     * @param string $content
     * @return string
     */
    protected function _else($tree, $content)
    {   
        return count($tree) ? "else {{$content}}" : "";
    }

    /**
     * @name _macro
     * @description creates the string representation of a function,based on the contents of a {macro...}...{/macro} tag set. 
     * @notice it is possible, using the {macro} tag, to define functions, which we then use within our templates.
     * @access protected
     * @param string $tree
     * @param string $content
     * @return string
     */
    protected function _macro($tree, $content)
    {
        $arguments = $tree["arguments"];
        $name = $arguments["name"];
        $args = $arguments["args"];

        return "function {$name}({$args}) {
        \$_text = [);
        {$content}
        return implode(\$_text);
        }";
    }

    /**
     * @name _literal
     * @description quotes any content within it. 
     * @notice the template parser only stops directly quoting the content when it finds a {/literal} closing tag.
     * @access protected
     * @param string $tree
     * @param string $content
     * @return string
     */
    protected function _literal($tree, $content)
    {
        $content = trim($content);
        $source = addslashes($tree["source"]);
        return "\n\$_text[] = \"{$source}\";\n";
    }

    /**
     * @name _loop
     * @description it augments the output of _for , _foreach with checks for the contents of the arrays used by those statements, as long as an {else} tag follows them.
     * @access protected
     * @param string $tree
     * @param string $inner
     * @return string
     */
    protected function _loop($tree, $inner)
    {
        $number = $tree["number"];
        $object = $tree["arguments"]["object"];
        $children = $tree["parent"]["children"];

        if (!empty($children[$number + 1]["tag"]) && $children[$number + 1]["tag"] == "else")
        {
            return "if (is_array({$object}) && sizeof({$object}) > 0) {{$inner}}";
        }

        return $inner;
    }
    
    /**
     * @readwrite
     * @access protected 
     * @var array
     */
    protected $_map = [
        "echo" => [
            "opener" => "{echo",
            "closer" => "}",
            "handler" => "_echo"
        ],
        "script" => [
            "opener" => "{script",
            "closer" => "}",
            "handler" => "_script"
        ],
        "statement" => [
            "opener" => "{",
            "closer" => "}",
            "tags" => [
                "foreach" => [
                    "isolated" => false,
                    "arguments" => "{element} in {object}",
                    "handler" => "_each"
                ],
                "for" => [
                    "isolated" => false,
                    "arguments" => "{element} in {object}",
                    "handler" => "_for"
                ],
                "if" => [
                    "isolated" => false,
                    "arguments" => null,
                    "handler" => "_if"
                ],
                "elseif" => [
                    "isolated" => true,
                    "arguments" => null,
                    "handler" => "_elseif"
                ],
                "else" => [
                    "isolated" => true,
                    "arguments" => null,
                    "handler" => "_else"
                ],
                "macro" => [
                    "isolated" => false,
                    "arguments" => "{name}({args})",
                    "handler" => "_macro"
                ],
                "literal" => [
                    "isolated" => false,
                    "arguments" => null,
                    "handler" => "_literal"
                ]
            ]
        ]
    ];

    /**
     * @readwrite
     * @access protected 
     * @var string
     */
    protected $_defaultPath;

    /**
     * @readwrite
     * @access protected 
     * @var string
     */
    protected $_defaultKey = "_data";
}


