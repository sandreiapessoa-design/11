<?php declare(strict_types=1); namespace IR\Utils\Types; if (!defined('IR_START')) exit('<pre>No direct script access allowed</pre>');
/**
 * @framework       iResponse Framework 
 * @version         1.0
 * @author          Amine Idrissi <contact@iresponse.tech>
 * @date            2024
 * @name            Strings.php	
 */

/**
 * @name Strings
 * @description strings utils class
 */
class Strings
{
    /**
     * @name getInstance
     * @description singleton access to constructor
     * @access public | static
     * @return Strings
     */
    public static function getInstance() : Strings
    {
        if(self::$_instance == null)
        {
            self::$_instance = new Strings();
        }
        
        return self::$_instance;
    }
    
    /**
     * @name singular
     * @description converts a string to its singular form
     * @access public 
     * @param string $string
     * @return string
     */
    public function singular(string $string) : string
    {
        $result = $string;
        
        foreach (self::$_SINGULAR_PATTERNS as $rule => $replacement) 
        {
            $rule = $this->_normalize($rule);
            
            if (preg_match($rule, $string)) 
            {
                $result = preg_replace($rule, $replacement, $string);
                break;
            }
        }
        
        return $result;
    }

    /**
     * @name plural
     * @description converts a string to its plural form
     * @access public 
     * @param string $string
     * @return string
     */
    public function plural(string $string) : string
    {
        $result = $string;
        
        foreach (self::$_PLURAL_PATTERNS as $rule => $replacement)
        {
            $rule = self::_normalize($rule);
            
            if (preg_match($rule, $string))
            {
                $result = preg_replace($rule, $replacement, $string);
                break;
            }
        }
        
        return $result;
    }

    /**
     * @name removeAccents
     * @description removes accents from charachters in a text  
     * @access public 
     * @param string $string
     * @return string
     */
    public function removeAccents(string $string) : string
    {
        $accents = ['À', 'Á', 'Â', 'Ã', 'Ä', 'Å', 'Æ', 'Ç', 'È', 'É', 'Ê', 'Ë', 'Ì', 'Í', 'Î', 'Ï', 'Ð', 'Ñ', 'Ò', 'Ó', 'Ô', 'Õ', 'Ö', 'Ø', 'Ù', 'Ú', 'Û', 'Ü', 'Ý', 'ß', 'à', 'á', 'â', 'ã', 'ä', 'å', 'æ', 'ç', 'è', 'é', 'ê', 'ë', 'ì', 'í', 'î', 'ï', 'ñ', 'ò', 'ó', 'ô', 'õ', 'ö', 'ø', 'ù', 'ú', 'û', 'ü', 'ý', 'ÿ', 'Ā', 'ā', 'Ă', 'ă', 'Ą', 'ą', 'Ć', 'ć', 'Ĉ', 'ĉ', 'Ċ', 'ċ', 'Č', 'č', 'Ď', 'ď', 'Đ', 'đ', 'Ē', 'ē', 'Ĕ', 'ĕ', 'Ė', 'ė', 'Ę', 'ę', 'Ě', 'ě', 'Ĝ', 'ĝ', 'Ğ', 'ğ', 'Ġ', 'ġ', 'Ģ', 'ģ', 'Ĥ', 'ĥ', 'Ħ', 'ħ', 'Ĩ', 'ĩ', 'Ī', 'ī', 'Ĭ', 'ĭ', 'Į', 'į', 'İ', 'ı', 'Ĳ', 'ĳ', 'Ĵ', 'ĵ', 'Ķ', 'ķ', 'Ĺ', 'ĺ', 'Ļ', 'ļ', 'Ľ', 'ľ', 'Ŀ', 'ŀ', 'Ł', 'ł', 'Ń', 'ń', 'Ņ', 'ņ', 'Ň', 'ň', 'ŉ', 'Ō', 'ō', 'Ŏ', 'ŏ', 'Ő', 'ő', 'Œ', 'œ', 'Ŕ', 'ŕ', 'Ŗ', 'ŗ', 'Ř', 'ř', 'Ś', 'ś', 'Ŝ', 'ŝ', 'Ş', 'ş', 'Š', 'š', 'Ţ', 'ţ', 'Ť', 'ť', 'Ŧ', 'ŧ', 'Ũ', 'ũ', 'Ū', 'ū', 'Ŭ', 'ŭ', 'Ů', 'ů', 'Ű', 'ű', 'Ų', 'ų', 'Ŵ', 'ŵ', 'Ŷ', 'ŷ', 'Ÿ', 'Ź', 'ź', 'Ż', 'ż', 'Ž', 'ž', 'ſ', 'ƒ', 'Ơ', 'ơ', 'Ư', 'ư', 'Ǎ', 'ǎ', 'Ǐ', 'ǐ', 'Ǒ', 'ǒ', 'Ǔ', 'ǔ', 'Ǖ', 'ǖ', 'Ǘ', 'ǘ', 'Ǚ', 'ǚ', 'Ǜ', 'ǜ', 'Ǻ', 'ǻ', 'Ǽ', 'ǽ', 'Ǿ', 'ǿ'];
        $replacements = ['A', 'A', 'A', 'A', 'A', 'A', 'AE', 'C', 'E', 'E', 'E', 'E', 'I', 'I', 'I', 'I', 'D', 'N', 'O', 'O', 'O', 'O', 'O', 'O', 'U', 'U', 'U', 'U', 'Y', 's', 'a', 'a', 'a', 'a', 'a', 'a', 'ae', 'c', 'e', 'e', 'e', 'e', 'i', 'i', 'i', 'i', 'n', 'o', 'o', 'o', 'o', 'o', 'o', 'u', 'u', 'u', 'u', 'y', 'y', 'A', 'a', 'A', 'a', 'A', 'a', 'C', 'c', 'C', 'c', 'C', 'c', 'C', 'c', 'D', 'd', 'D', 'd', 'E', 'e', 'E', 'e', 'E', 'e', 'E', 'e', 'E', 'e', 'G', 'g', 'G', 'g', 'G', 'g', 'G', 'g', 'H', 'h', 'H', 'h', 'I', 'i', 'I', 'i', 'I', 'i', 'I', 'i', 'I', 'i', 'IJ', 'ij', 'J', 'j', 'K', 'k', 'L', 'l', 'L', 'l', 'L', 'l', 'L', 'l', 'l', 'l', 'N', 'n', 'N', 'n', 'N', 'n', 'n', 'O', 'o', 'O', 'o', 'O', 'o', 'OE', 'oe', 'R', 'r', 'R', 'r', 'R', 'r', 'S', 's', 'S', 's', 'S', 's', 'S', 's', 'T', 't', 'T', 't', 'T', 't', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'W', 'w', 'Y', 'y', 'Y', 'Z', 'z', 'Z', 'z', 'Z', 'z', 's', 'f', 'O', 'o', 'U', 'u', 'A', 'a', 'I', 'i', 'O', 'o', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'A', 'a', 'AE', 'ae', 'O', 'o'];
        return str_replace($accents,$replacements, $string);
    }

    /**
     * @name slug
     * @description replacing html special characters 
     * @access public 
     * @param string $string
     * @return mixed
     */
    public function slug(string $string)
    {
        return strtolower(preg_replace(['/[^a-zA-Z0-9 -]/', '/[ -]+/', '/^-|-$/'],['', '-', ''], $this->removeAccents($string)));
    }

    /**
     * @name match
     * @description matching for results in a text by a regex pattern
     * @access public 
     * @param string $string
     * @param string $pattern
     * @return mixed
     */
    public function match(string $string, string $pattern)
    {
        $matches = [];
        
        preg_match_all($this->_normalize($pattern), $string, $matches, PREG_PATTERN_ORDER);

        if (!empty($matches[1])) 
        {
            return $matches[1];
        }
        if (!empty($matches[0])) 
        {
            return $matches[0];
        }
        
        return null;
    }

    /**
     * @name split
     * @description splitting a text by a regex pattern
     * @access public 
     * @param string $string
     * @param string $pattern
     * @param integer $limit
     * @return mixed
     */
    public function split(string $string, string $pattern, $limit = -1) : array
    {
        return preg_split($this->_normalize($pattern), $string, $limit, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
    }
    
    /**
     * @name trim
     * @description trim the whole string
     * @access public 
     * @return mixed
     */
    public function trim($string)
    {
        return str_replace(['<br/>','<br>','</br>'],'',trim(preg_replace('/\s\s+/','',$string)));
    }
    
    /**
     * @name stristr
     * @description returns all of haystack starting from and including the first occurrence of needle to the end. 
     * @access public 
     * @return mixed
     */
    public function stristr($haystack,$needle) : string
    {
        return stristr($haystack,$needle) === FALSE ? '' : stristr($haystack,$needle);
    }
    
    /**
     * @name strstr
     * @description returns all of haystack starting from and including the first occurrence of needle to the end. 
     * @access public 
     * @return mixed
     */
    public function strstr($haystack,$needle) : string
    {
        return strstr($haystack,$needle) === FALSE ? '' : strstr($haystack,$needle);
    }

    /**
     * @name random
     * @description generates random text 
     * @access public 
     * @param integer $size the size of generated text 
     * @param boolean $letters boolean value to tell the function whether use letters or not 
     * @param boolean $numbers boolean value to tell the function whether use uppercase letters too or not 
     * @param boolean $uppercase boolean value to tell the function whether use numbers or not
     * @param boolean $special boolean value to tell the function whether use special characters or not
     * @return string
     */
    public function random(int $size = 5, bool $letters = true, bool $numbers = true, bool $uppercase = false, bool $special = false) : string
    {
        $result = '';
        $characters = '';

        if($letters)
        {
            $characters .= 'abcdefghijklmnopqrstuvwxyz';
            if($uppercase)
            {
                $characters .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
            }
        }

        if($numbers)
        {
            $characters .= '0123456789';
        }

        if($special)
        {
            $characters .= '@\\/_*$&-#[](){}';
        }

        for ($i = 0; $i <$size; $i++) 
        {
             $result .= $characters[rand(0, strlen($characters) - 1)];
        }
        
        return $result;
    }

    /**
     * @name randomHex
     * @description generates random hex text 
     * @access public 
     * @param integer $size the size of generated text 
     * @param boolean $uppercase boolean value to tell the function whether use numbers or not
     * @return string
     */
    public function randomHex(int $size = 5,bool $uppercase = false) : string
    {
        $result = '';

        $characters = 'abcdef';
        
        if($uppercase)
        {
            $characters .= 'ABCDEF';
        }

        $characters .= '0123456789';

        for ($i = 0; $i <$size; $i++) 
        {
             $result .= $characters[rand(0, strlen($characters) - 1)];
        }
        
        return $result;
    }
    
    /**
     * @name prepareDbTableName
     * @description make a string acceptable to be a database table name
     * @access public 
     * @return string
     */
    public function prepareDbTableName($string) : string
    {
        $result = strtolower($this->trim($this->removeAccents(str_replace([' ','-','.',';',',','@'],'_',$string))));
        return (preg_match('/^\d/',$result) === 1) ? 'dt_' . $result : $result;
    }
    
    /**
     * @name sanitize
     * @description loops through the characters of a string, replacing them with regular expression friendly character representations.
     * @access public 
     * @param string $string
     * @param string $mask
     * @return string
     */
    public function sanitize(string $string, $mask) : string
    {
        if (is_array($mask)) 
        {
            $parts = $mask;
        } 
        else if (is_string($mask)) 
        {
            $parts = str_split($mask);
        } 
        else 
        {
            return $string;
        }

        foreach ($parts as $part) 
        {
            $normalized = $this->_normalize("\\{$part}");
            $string = preg_replace("{$normalized}m", "\\{$part}", $string);
        }
        
        return $string;
    }

    /**
     * @name unique
     * @description eliminates all duplicated characters in a string 
     * @access public 
     * @param string $string
     * @return string
     */
    public function unique(string $string) : string
    {
        $unique = "";
        $parts = str_split($string);

        foreach ($parts as $part) 
        {
            if (!strstr($unique, $part)) 
            {
                $unique .= $part;
            }
        }

        return $unique;
    }

    /**
     * @name indexOf
     * @description gets the index of a text inside another text
     * @access public 
     * @param string $string
     * @param string $needle
     * @param integer $offset
     * @return mixed
     */
    public function indexOf(string $string, string $needle, int $offset = 0) : int
    {
        if (strlen($string) == 0 || strlen($needle) == 0 ) 
        {
            return -1;
        }
        
        $position = strpos($string, $needle, $offset);
        
        if (!is_int($position)) 
        {
            return -1;
        }
        
        return $position;
    }

    /**
     * @name startsWith
     * @description check if a string starts with a given needle
     * @access public 
     * @param string $string
     * @param string $needle
     * @param bool $case
     * @return mixed
     */
    public function startsWith(string $string, string $needle, bool $case = true) : bool
    {
        $string = $case === false ? strtolower($string) : $string;
        $needle = $case === false ? strtolower($needle) : $needle;
        return $needle === "" || strrpos($string, $needle, -strlen($string)) !== FALSE;
    }

    /**
     * @name endsWith
     * @description get class name without namespace in it  
     * @access public 
     * @param string $string
     * @param string $needle
     * @return mixed
     */
    public function endsWith(string $string, string $needle, bool $case = true) : bool
    {
        $string = $case === false ? strtolower($string) : $string;
        $needle = $case === false ? strtolower($needle) : $needle;
        return $needle === "" || (($temp = strlen($string) - strlen($needle)) >= 0 && strpos($string, $needle, $temp) !== FALSE);
    }

    /**
     * @name contains
     * @description checks if a string contains the substring
     * @access public 
     * @param string $string
     * @param string $needle
     * @return boolean
     */ 
    public function contains(string $string, string $needle, bool $case = true) : bool
    {
        $string = $case === false ? strtolower($string) : $string;
        $needle = $case === false ? strtolower($needle) : $needle;
        return $this->indexOf($string,$needle) > -1;
    }

    /**
     * @name similarWords
     * @description check for similar words
     * @access public 
     * @param string $first
     * @param string $second
     * @param bool $case
     * @return mixed
     */
    public function similarWords(string $first, string $second) : array
    {
        $words = [];

        if (strlen($first)==0 || strlen($second)==0) 
        {
            return $words;
        }

        $first = preg_replace("/[^A-Za-z0-9-]/", ' ', $first);
        $second = preg_replace("/[^A-Za-z0-9-]/", ' ', $second);

        # remove double spaces
        while (strpos($first, "  ")!==false) 
        {
            $first = str_replace("  ", " ", $first);
        }
        
        while (strpos($second, "  ")!==false) 
        {
            $second = str_replace("  ", " ", $second);
        }

        # create arrays
        $ar1 = explode(" ",$first);
        $ar2 = explode(" ",$second);
        $l1 = count($ar1);
        $l2 = count($ar2);

        # flip the arrays if needed so ar1 is always largest.
        if ($l2>$l1) 
        {
            $t = $ar2;
            $ar2 = $ar1;
            $ar1 = $t;
        }

        # flip array 2, to make the words the keys
        $ar2 = array_flip($ar2);

        # find matching words
        foreach($ar1 as $word) 
        {
            if (array_key_exists($word, $ar2))
            {
                if(!empty($word))
                {
                    $words[] = $word;
                }
            }

        }

        return $words;
    }
    
    /**
     * @name _normalize
     * @description triming the pattern (regex) by making sure that the delimeter is not declared twice in the pattern
     * @access private 
     * @param string $pattern
     * @return mixed
     */
    private function _normalize(string $pattern)
    {
        return self::$_DELIMITER.trim($pattern, self::$_DELIMITER).self::$_DELIMITER;
    }

    /**
     * @name __construct
     * @description class constructor
     * @access private
     * @return Strings
     */
    private function __construct() {}

    /**
     * @name __clone
     * @description preventing cloning object
     * @access public
     * @return Strings
     */
    public function __clone()
    {
        return self::getInstance();  
    }
    
    /** 
     * @readwrite
     * @access private 
     * @var Strings
     */ 
    private static $_instance;
    
    /** 
     * @readwrite
     * @access private | static 
     * @var string
     */ 
    private static $_DELIMITER = "#";

    /** 
     * @readwrite
     * @access private | static 
     * @var string
     */ 
    private static $_SINGULAR_PATTERNS = [
        "(matr)ices$" => "\\1ix",
        "(vert|ind)ices$" => "\\1ex",
        "^(ox)en" => "\\1",
        "(alias)es$" => "\\1",
        "([octop|vir])i$" => "\\1us",
        "(cris|ax|test)es$" => "\\1is",
        "(shoe)s$" => "\\1",
        "(o)es$" => "\\1",
        "(bus|campus)es$" => "\\1",
        "([m|l])ice$" => "\\1ouse",
        "(x|ch|ss|sh)es$" => "\\1",
        "(m)ovies$" => "\\1\\2ovie",
        "(s)eries$" => "\\1\\2eries",
        "([^aeiouy]|qu)ies$" => "\\1y",
        "([lr])ves$" => "\\1f",
        "(tive)s$" => "\\1",
        "(hive)s$" => "\\1",
        "([^f])ves$" => "\\1fe",
        "(^analy)ses$" => "\\1sis",
        "((a)naly|(b)a|(d)iagno|(p)arenthe|(p)rogno|(s)ynop|(t)he)ses$" => "\\1\\2sis",
        "([ti])a$" => "\\1um",
        "(p)eople$" => "\\1\\2erson",
        "(m)en$" => "\\1an",
        "(s)tatuses$" => "\\1\\2tatus",
        "(c)hildren$" => "\\1\\2hild",
        "(n)ews$" => "\\1\\2ews",
        "([^u])s$" => "\\1"
    ];

    /** 
     * @readwrite
     * @access private | static 
     * @var string
     */ 
    private static $_PLURAL_PATTERNS = [
        "^(ox)$" => "\\1\\2en",
        "([m|l])ouse$" => "\\1ice",
        "(matr|vert|ind)ix|ex$" => "\\1ices",
        "(x|ch|ss|sh)$" => "\\1es",
        "([^aeiouy]|qu)y$" => "\\1ies",
        "(hive)$" => "\\1s",
        "(?:([^f])fe|([lr])f)$" => "\\1\\2ves",
        "sis$" => "ses",
        "([ti])um$" => "\\1a",
        "(p)erson$" => "\\1eople",
        "(m)an$" => "\\1en",
        "(c)hild$" => "\\1hildren",
        "(buffal|tomat)o$" => "\\1\\2oes",
        "(bu|campu)s$" => "\\1\\2ses",
        "(alias|status|virus)" => "\\1es",
        "(octop)us$" => "\\1i",
        "(ax|cris|test)is$" => "\\1es",
        "s$" => "s",
        "$" => "s"
    ];
}

