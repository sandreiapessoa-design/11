<?php declare(strict_types=1); namespace IR\Utils\Types; if (!defined('IR_START')) exit('<pre>No direct script access allowed</pre>');
/**
 * @framework       iResponse Framework 
 * @version         1.0
 * @author          Amine Idrissi <contact@iresponse.tech>
 * @date            2024
 * @name            Arrays.php	
 */

/**
 * @name Arrays
 * @description arrays utils class
 */
class Arrays
{
    /**
     * @name getInstance
     * @description singleton access to constructor
     * @access public | static
     * @return Arrays
     */
    public static function getInstance() : Arrays
    {
        if(self::$_instance == null)
        {
            self::$_instance = new Arrays();
        }
        
        return self::$_instance;
    }
    
    /**
     * @name get
     * @description gets an element from an array
     * @param array $source
     * @param mixed $key
     * @param mixed $default
     * @return array
     */
    public function get(array $source,$key,$default = null) 
    {
        if(key_exists($key, $source))
        {
            return $source[$key];
        }

        return $default;
    }

    /**
     * @name set
     * @description set an new element to an array
     * @param array $source
     * @param mixed $key
     * @param mixed $value
     * @return array
     */
    public function set(array $source,$key,$value) 
    {
        if($key != null)
        {
            $source[$key] = $value;
        }
    }

    /**
     * @name getKey
     * @description gets the key of an element inside an array
     * @param array $source The array to search in
     * @param mixed $element The element to search for its position
     * @return integer
     */
    public function getKey(array $source,$element) 
    {
        if (isset($element)) 
        {
            foreach ($source as $key => $value ) 
            {
                if(!is_string($element))
                {
                    if ($element === $value) 
                    {
                        return $key;
                    }
                }
                else if (strpos($element,$value) !== FALSE)  
                {
                    return $key;
                }
            }
        }

        return null;       
    }
    
    /**
     * @name append
     * @description appends value or array of values to the end of an array.
     * @access public
     * @return
     */
    public function append(array &$source ,$value) : bool
    {
        if(is_array($value))
        {
            return $this->appendArray($source, $value);
        }

        return $this->appendValue($source, $value);
    }

    /**
     * @name prepend
     * @description prepend value or array of values to the beguining of an array.
     * @access public
     * @return
     */
    public function prepend(array &$source ,$value) : bool
    {
        if(is_array($value))
        {
            return $this->prependArray($source, $value);
        }

        return $this->prependValue($source, $value);
    }
    
    /**
     * @name length
     * @description returns array length
     * @access public
     * @return
     */
    public function length(array $source) : int
    {
        return count($source);
    }
    
    /**
     * @name reverse
     * @description reverses an array
     * @access public
     * @return
     */
    public function reverse(array $source) : array
    {
        return array_reverse($source);
    }
    
    /**
     * @name reset
     * @description resets an array
     * @access public
     * @return
     */
    public function reset(array $source)
    {
        reset($source);
    }
    
    /**
     * @name first
     * @description returns the first element an array
     * @access public
     * @return
     */
    public function first(array $source)
    {
        return reset($source);
    }
    
    /**
     * @name last
     * @description returns the last element an array
     * @access public
     * @return
     */
    public function last(array $source)
    {
        $item = end ($source);
        $this->reset($source);
        return $item;
    }
    
    /**
     * @name last
     * @description returns the last element an array
     * @access public
     * @return
     */
    public function unique(array $source) : array
    {
        return array_unique($source);
    }
    
    /**
     * @name clean
     * @description converts a string to its singular form
     * @param array $source
     * @return array
     */
    public function clean(array $source) : array
    {
        return array_filter($source, function($item){
            return !empty($item);           
        });
    }

    /**
     * @name trim
     * @description removes spaces from all elements from an array
     * @param array $source
     * @return array
     */
    public function trim(array $source) : array
    {
        return array_map(function($item){
            return trim($item);
        }, $source);
    }
    
    /**
     * @name swap
     * @description uses the array given and swaps the value at index 1 with the value at index 2.
     * @param array $source the array to use for the swap
     * @param mixed $index1 the index of the first value to be swapped.
     * @param mixed $index2 the index of the second value to be swapped.
     * @return string
     */
    public function swap(array &$source, $index1, $index2) 
    {
        $temp = $source[$index1];
        $source[$index1] = $source[$index2];
        $source[$index2] = $temp;
    }

    /**
     * @name shift
     * @description shift an array
     * @param array $source
     * @return array
     */
    public function shift(array $source) : array 
    {
        return array_shift($source);
    }
    
    /**
     * @name reindex
     * @description reindexes the supplied array from 0 to number of values - 1.
     * @param array $source
     * @return
     */
    public function reindex(array &$source)
    {
        $temp = $source;
        $source = [];
        
        foreach ($temp as $value) 
        {
            $source[] = $value;
        }
    }
    
    /**
     * @name toObject
     * @description converts an array into an object
     * @param  array  $source 
     * @return mixed
     */
    public function toObject(array $source) : stdClass
    {
        $result = new stdClass();

        foreach ($source as $key => $value) 
        {
            if (is_array($value)) 
            {
                $result->{$key} = $this->toObject($value);
            } 
            else 
            {
                $result->{$key} = $value;
            }
        }

        return $result;
    }
    
    /**
     * @name dropNulls
     * @description unsets array rows where the value is NULL
     * @param array $source
     * @return
     */
    public function dropNulls(array &$source) 
    {
        if (is_array($source)) 
        {
            reset($source);

            foreach ($source as $key => $value) 
            {
                if (is_null($value)) 
                {
                    unset($source[$key]);
                }
            }
        }     
    }

    /**
     * @name sort
     * @description sort an array
     * @param array $source
     * @param int $sortType
     * @param int $direction
     * @return
     */
    public function sort(&$source,$sortType = Arrays::SORT_VALUE, $direction = Arrays::SORT_ASC) : bool    
    {
        if ($sortType == Arrays::SORT_KEYS)
        {
            return ($direction == Arrays::SORT_ASC)  ? ksort($source,SORT_ASC) : ksort($source,SORT_DESC);
        }
        
        return ($direction == Arrays::SORT_ASC)  ? asort($source,SORT_ASC) : asort($source,SORT_DESC);
    }
    

    /**
     * @name interval
     * @description creates an array of numbers from start to final in increments of the interval variable
     * @param integer $final final number of the count
     * @param integer $start number to begin counting on (not from)
     * @param integer $step number to increment the count
     * @return string
     */
    public function interval(int $final, int $start = 0, int $step = 1) : array
    {
        $count = 0;

        if ($step && $final > $start) 
        {
            for ($i = $start; $i <= $final; $i = $i + $step) 
            {
                $count++;
                $result[] = $i;  
            }

            return $result;
        }

        return [];   
    }

    /**
     * @name max
     * @description gets the max value of an array
     * @param  array $source The array to search in
     * @return array
     */
    public function max(array $source) : array
    {    
        $max = $source[0];
        $index = 0;
        
        foreach($source as $key => $val)
        {
            if($val > $max)
            {
                $max = $val;
                $index = $key;
            }
        }   
        
        return ["index" => $index, "value" => $max];
    } 

    /**
     * @name min
     * @description gets the min value of an array 
     * @param array $source
     * @return array
     */
    public function min(array $source) : array
    {    
        $min = $source[0];
        $index = 0;

        foreach($source as $key => $val)
        {
            if($val < $min)
            {
                $min = $val;
                $index = $key;
            }
        }  

        return ["index" => $index, "value" => $min];
    } 

    /**
     * @name sum
     * @description gets the the sum of an array values
     * @param array $source
     * @return array
     */
    public function sum(array $source) : array
    {    
        $sum = 0;

        foreach($source as $val)
        {
            $sum += $val;
        }  

        return $sum; 
    } 

    /**
     * @name flatten
     * @description converts multidimensional arrays into a unidimensional arrays
     * @param array $source
     * @return array
     */
    public function flatten(array $source) : array
    {
        $return = [];

        foreach ($source as $value)
        {
            if (is_array($value))
            {
                $return = $this->flatten($value, $return);
            }
            else
            {
                $return[] = $value;
            }
        }

        return $return;
    }
    
    /**
     * @name containsValue
     * @description checks if a value exsist in an array
     * @param array $source
     * @param mixed $value
     * @return bool
     */
    public function containsValue(array $source,$value) : bool
    {
        return in_array($value, $source);
    }
    
    /**
     * @name containsKey
     * @description checks if a key exsist in an array
     * @param array $source
     * @param mixed $key
     * @return bool
     */
    public function containsKey(array $source,$key) : bool
    {
        return array_key_exists($key,$source);
    }
    
    /**
     * @name toCsv
     * @description converts an associative array to CSV
     * @access public
     * @return
     */
    public function toCsv(array &$source, string $delimiter = ';') :string
    {
        $csv = '';
        
        if (is_array($source) && count($source))
        {
            $csv = join($delimiter, array_keys($source[0])) . PHP_EOL;
            
            foreach ($source as $row)
            {
                $csv .= join($delimiter, array_values($row)) . PHP_EOL;
            }
        }

        return $csv;
    } 
    
    /**
     * @name appendArray
     * @description appends array of values to the end of an array.
     * @access private
     * @return
     */
    private function appendArray(array &$source ,array $array) :bool
    {
        foreach ($array as $value)
        {
            $this->append($source,$value);
        }
        
        return true;
    }
   
    /**
     * @name appendValue
     * @description appends a value to the end of an array.
     * @access private
     * @return
     */
    private function appendValue(array &$source ,$value) :bool
    {
        if (!is_array($source) || is_array($value))
        {
            return false;
        }
        
        $source[] = $value;
        return true;
    }

    /**
     * @name prependArray
     * @description prepends array of values to the beguining of an array.
     * @access private
     * @return
     */
    private function prependArray(array &$source ,array $array) :bool
    {
        $sourceLength = $this->length($source);
        $arrayLength = $this->length($array); 
        $source = array_merge($array,$source);
        
        return $this->length($source) == ($sourceLength + $arrayLength);
    }
    
    
    /**
     * @name prependValue
     * @description prepends a value to the beguining of an array.
     * @access private
     * @return
     */
    private function prependValue(array &$source ,$value) :bool
    {
        if (!is_array($source) || is_array($value))
        {
            return false;
        }
        
        return $this->prepend($source,[$value]);
    }
    
    /**
     * @name implode
     * @description returns imploded value
     * @access public
     * @return string
     */
    public function implode($source,$glue = ',') : string
    {
        return is_array($source) && count($source) ? implode($glue,$source) : '';
    }
    
    /**
     * @name formToArray
     * @description converts a form serialized data into an array
     * @access public
     * @return string
     */
    public function formToArray($form) : array
    {
        $result = [];
        $parts = explode("&", $form);
        
        foreach($parts as $item) 
        {
            $keyValue = explode("=",$item);
            
            if(count($keyValue) == 1)
            {
                $result[$keyValue[0]] = '';
            }
            else if(count($keyValue) == 2)
            {
                $result[$keyValue[0]] = $keyValue[1];
            }
        }
        
        return $result;
    }
    
    /**
     * @name __construct
     * @description class constructor
     * @access private
     * @return Arrays
     */
    private function __construct() {}

    /**
     * @name __clone
     * @description preventing cloning object
     * @access public
     * @return Arrays
     */
    public function __clone()
    {
        return self::getInstance();  
    }
    
    /** 
     * @readwrite
     * @access private 
     * @var Arrays
     */ 
    private static $_instance;
    
    /** 
     * @readwrite
     * @access public 
     * @var integer
     */ 
    const SORT_KEYS = 0;
    
    /** 
     * @readwrite
     * @access public 
     * @var integer
     */ 
    const SORT_VALUE = 1;
    
    /** 
     * @readwrite
     * @access public 
     * @var integer
     */ 
    const SORT_ASC = 3;
    
    /** 
     * @readwrite
     * @access public 
     * @var integer
     */ 
    const  SORT_DESC = 4;
}


