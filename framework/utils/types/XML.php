<?php declare(strict_types=1); namespace IR\Utils\Types; if (!defined('IR_START')) exit('<pre>No direct script access allowed</pre>');
/**
 * @framework       iResponse Framework 
 * @version         1.0
 * @author          Amine Idrissi <contact@iresponse.tech>
 * @date            2024
 * @name            XML.php	
 */

# utilities 
use IR\Utils\Types\Strings as Strings;
use IR\Utils\Types\Arrays as Arrays;
use IR\Utils\Types\Objects as Objects;

# exceptions
use IR\Exceptions\Types\ArgumentException as ArgumentException;

/**
 * @name XML
 * @description xml utils class
 */
class XML
{
    /**
     * @name getInstance
     * @description singleton access to constructor
     * @access public | static
     * @return XML
     */
    public static function getInstance() : XML
    {
        if(self::$_instance == null)
        {
            self::$_instance = new XML();
        }
        
        return self::$_instance;
    }
    
    /**
     * @name objectToXML
     * @description converts a given object into an xml format
     * @access public
     * @param array $array
     * @param string $encoding
     * @return string
     */
    public function objectToXML($object,string $root = 'root',string $encoding = XML::ENCODING_UTF_8) : string
    {
        # check if the retreived object is not empty
        if($object == null)
        {
            throw new ArgumentException('Please check that you have sent an object of data to be converted !');
        }
        
        return $this->arrayToXML(Objects::getInstance()->toArray($object), $root, $encoding);
    }
    
    /**
     * @name xmlToObject
     * @description converts a given xml string into an object
     * @access public
     * @param string $xmldata
     * @param string $encoding
     * @return mixed
     */
    public function xmlToObject(string $xmldata,string $encoding = XML::ENCODING_UTF_8,bool $foldCase = true,string $valueArrayName = 'value',string $attributesArrayName = 'attributes',bool $trimText = false)
    {
        # check if the retreived array is not empty
        if($xmldata == null || strlen($xmldata) == 0)
        {
            throw new ArgumentException('Please check that you have sent an xml text to be converted !');
        }
        
        return Arrays::getInstance()->toObject($this->xmlToArray($xmldata, $encoding, $foldCase, $valueArrayName, $attributesArrayName, $trimText));
    }
    
    
    /**
     * @name arrayToXML
     * @description converts a given array into an xml format
     * @access public
     * @param array $array
     * @param string $encoding
     * @return string
     */
    public function arrayToXML(array $array,string $root = 'root',string $encoding = XML::ENCODING_UTF_8) : string
    {
        # check if the retreived array is not empty
        if($array == null || count($array) == 0)
        {
            throw new ArgumentException('Please check that you have sent an array of data to be converted !');
        }
        
        $dom = new DOMDocument();
        $dom->encoding = $encoding;

        $root = is_numeric(Arrays::getInstance()->first(array_keys($array))) ? $root : Strings::getInstance()->plural(strval(Arrays::getInstance()->first(array_keys($array))));
        $rootNode = $dom->appendChild($dom->createElement($root));
        $this->putChildren($dom,$array,$rootNode);
        return $dom->saveXML();
    }
    
    /**
     * @name xmlToArray
     * @description converts a given xml string into an array
     * @access public
     * @param string $xmldata
     * @param string $encoding
     * @return mixed
     */
    public function xmlToArray(string $xmldata,string $encoding = XML::ENCODING_UTF_8,bool $foldCase = false,string $valueArrayName = 'value',string $attributesArrayName = 'attributes',bool $trimText = false) : array
    {
        # check if the retreived array is not empty
        if($xmldata == null || strlen($xmldata) == 0)
        {
            throw new ArgumentException('Please check that you have sent an xml text to be converted !');
        }
        
        $parser = xml_parser_create($encoding);
        xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
        xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING,$foldCase);

        $values = [];
        
        if (!xml_parse_into_struct ($parser, $xmldata, $values)) 
        {
            throw new ArgumentException(sprintf("XML error: %s at line %d",xml_error_string(xml_get_error_code($parser)),xml_get_current_line_number($parser))); 
        }
        
        $folding = xml_parser_get_option($parser, XML_OPTION_CASE_FOLDING);
        xml_parser_free ($parser);

        if ($folding) 
        {
            $valueArrayName = strtoupper($valueArrayName);
            $attributesArrayName = strtoupper($attributesArrayName);
        }

        if ($trimText) 
        {
            foreach($values as &$val) 
            {
                if (count($val['attributes']) > 0) 
                {
                    foreach($val['attributes'] as $name => $att) 
                    {
                        if ($trimText) $val['attributes'][$name] = trim($att);
                    }
                }
                
                if (isset($val['value'])) 
                {
                    if ($trimText) $val['value'] = trim($val['value']);
                }
            }
        }

        $i = 0;
        $children = $this->getChildren ($values, $i,$values[$i]['type'],$attributesArrayName,$valueArrayName);
        $valatt = $this->addAttributesAndValue($values, 0,$attributesArrayName,$valueArrayName);
        
        if (!empty($valatt))
        {
            $children = array_merge($valatt, $children);
        }

        $result[$values[$i]['tag']] = $children;
        return $result;
    }
    
    /**
     * @name putChildren
     * @description 
     * @param DOMDocument $dom
     * @param array $array
     * @param DOMElement $node
     * @access private
     * @return
     */ 
    private function putChildren(DOMDocument &$dom, array $array, DOMElement $node) 
    {
        foreach($array as $name => $content) 
        {
            if (strtoupper(strval($name)) == 'ATTRIBUTES') 
            {
                foreach($content as $n => $v) 
                {
                    $node->setAttribute($n, $v);
                }
           
            } 
            else if (strtoupper(strval($name)) == 'VALUE') 
            {
                $node->appendChild($dom->createTextNode($content));
            } 
            else 
            {
                if (is_numeric($name)) 
                {
                    $child = $dom->createElement(strtolower(Strings::getInstance()->singular($node->nodeName)) == 'root' ? 'element' : strtolower(Strings::getInstance()->singular($node->nodeName)), (is_array($content) ? '' : htmlspecialchars(strval($content))));
                    $node->appendChild($child);  
                } 
                else 
                {
                    $child = $dom->createElement(strtolower(strval($name)), (is_array($content) ? '' : htmlspecialchars(strval($content))));
                    $node->appendChild($child);
                }
               
                if (is_array($content)) 
                {
                    $this->putChildren($dom, $content, $child);
                }
            }
        }
    }

    /**
     * @name getChildren
     * @description 
     * @param array $values
     * @param intger $index
     * @param mixed $type
     * @access private
     * @return
     */ 
    private function getChildren ($values, &$index, $type,string $attributesArrayName = 'attributes', string $valueArrayName = 'values',bool $noAttributes = false) 
    {
        $children = array ();

        if ($type != 'complete') 
        {
            while ($values[++$index]['type'] != 'close') 
            {
                $type = $values[$index]['type'];
                $tag = $values[$index]['tag'];
 
                $valatt = $this->addAttributesAndValue($values, $index,$attributesArrayName,$valueArrayName,$noAttributes);

                if (isset ($children[$tag])) 
                {
                    $temp = array_keys ($children[$tag]);

                    if (is_string ($temp[0])) 
                    {
                        $a = $children[$tag];
                        unset ($children[$tag]);
                        $children[$tag][0] = $a;
                    }

                    $child = $this->getChildren($values, $index, $type,$attributesArrayName,$valueArrayName,$noAttributes);
                    
                    if (!empty($valatt))
                    {
                        $child = array_merge($valatt, $child);
                    }
                    
                    if(strtolower($tag) == 'element')
                    {
                        $children[] = $child;
                    }
                    else
                    {
                        $children[$tag][] = $child;
                    }
                } 
                else 
                {
                    $childs = $this->getChildren($values, $index, $type,$attributesArrayName,$valueArrayName,$noAttributes);
                    
                    if (!is_array($valatt)) 
                    {
                        $childs = $valatt;
                    } 
                    else 
                    {
                        if (!empty($valatt))
                        {
                            $childs = array_merge($valatt,$childs);
                        }
                    }

                    if(strtolower($tag) == 'element')
                    {
                        $children[] = $childs;
                    }
                    else
                    {
                        $children[$tag] = $childs;
                    }  
                }
            }
        }

        return $children;
    }

    /**
     * @name addAttributesAndValue
     * @description add any attributes or values from parser to the output array.
     * @param array $values
     * @param intger $index
     * @access private
     * @return
     */ 
    private function addAttributesAndValue(array $values, $index ,string $attributesArrayName = 'attributes', string $valueArrayName = 'values',bool $noAttributes = false) : array
    {

        $array = [];
        
        if ($noAttributes) 
        {
            if (isset ($values[$index]['value'])) $array = $values[$index]['value'];

        } 
        else 
        {
            if (isset ($values[$index]['value']))
            {
                $array = [$valueArrayName => $values[$index]['value']];
            }
            
            if (isset ($values[$index]['attributes']))
            {
                $array = array_merge($array,[$attributesArrayName => $values[$index]['attributes']]);
            }      
        }
        
        return $array;
    }
    
    /**
     * @name __construct
     * @description class constructor
     * @access private
     * @return XML
     */
    private function __construct() {}

    /**
     * @name __clone
     * @description preventing cloning object
     * @access public
     * @return XML
     */
    public function __clone()
    {
        return (self::$_instance != null) ? self::$_instance : new XML();  
    }
    
    /** 
     * @readwrite
     * @access private 
     * @var XML
     */ 
    private static $_instance;
    
    /**
     * @readwrite
     * @access static
     * @var string
     */
    const ENCODING_PASS = 'pass';

    /**
     * @readwrite
     * @access static
     * @var string
     */
    const ENCODING_AUTO = 'auto';

    /**
     * @readwrite
     * @access static
     * @var string
     */
    const ENCODING_WCHAR = 'wchar';

    /**
     * @readwrite
     * @access static
     * @var string
     */
    const ENCODING_BYTE2BE = 'byte2be';

    /**
     * @readwrite
     * @access static
     * @var string
     */
    const ENCODING_BYTE2LE = 'byte2le';

    /**
     * @readwrite
     * @access static
     * @var string
     */
    const ENCODING_BYTE4BE = 'byte4be';

    /**
     * @readwrite
     * @access static
     * @var string
     */
    const ENCODING_BYTE4LE = 'byte4le';

    /**
     * @readwrite
     * @access static
     * @var string
     */
    const ENCODING_BASE64 = 'BASE64';

    /**
     * @readwrite
     * @access static
     * @var string
     */
    const ENCODING_UUENCODE = 'UUENCODE';

    /**
     * @readwrite
     * @access static
     * @var string
     */
    const ENCODING_HTML_ENTITIES = 'HTML-ENTITIES';

    /**
     * @readwrite
     * @access static
     * @var string
     */
    const ENCODING_QUOTED_PRINTABLE = 'Quoted-Printable';

    /**
     * @readwrite
     * @access static
     * @var string
     */
    const ENCODING_7BIT = '7bit';

    /**
     * @readwrite
     * @access static
     * @var string
     */
    const ENCODING_8BIT = '8bit';

    /**
     * @readwrite
     * @access static
     * @var string
     */
    const ENCODING_UCS_4 = 'UCS-4';

    /**
     * @readwrite
     * @access static
     * @var string
     */
    const ENCODING_UCS_4BE = 'UCS-4BE';

    /**
     * @readwrite
     * @access static
     * @var string
     */
    const ENCODING_UCS_4LE = 'UCS-4LE';

    /**
     * @readwrite
     * @access static
     * @var string
     */
    const ENCODING_UCS_2 = 'UCS-2';

    /**
     * @readwrite
     * @access static
     * @var string
     */
    const ENCODING_UCS_2BE = 'UCS-2BE';

    /**
     * @readwrite
     * @access static
     * @var string
     */
    const ENCODING_UCS_2LE = 'UCS-2LE';

    /**
     * @readwrite
     * @access static
     * @var string
     */
    const ENCODING_UTF_32 = 'UTF-32';

    /**
     * @readwrite
     * @access static
     * @var string
     */
    const ENCODING_UTF_32BE = 'UTF-32BE';

    /**
     * @readwrite
     * @access static
     * @var string
     */
    const ENCODING_UTF_32LE = 'UTF-32LE';

    /**
     * @readwrite
     * @access static
     * @var string
     */
    const ENCODING_UTF_16 = 'UTF-16';

    /**
     * @readwrite
     * @access static
     * @var string
     */
    const ENCODING_UTF_16BE = 'UTF-16BE';

    /**
     * @readwrite
     * @access static
     * @var string
     */
    const ENCODING_UTF_16LE = 'UTF-16LE';

    /**
     * @readwrite
     * @access static
     * @var string
     */
    const ENCODING_UTF_8 = 'UTF-8';

    /**
     * @readwrite
     * @access static
     * @var string
     */
    const ENCODING_UTF_7 = 'UTF-7';

    /**
     * @readwrite
     * @access static
     * @var string
     */
    const ENCODING_UTF7_IMAP = 'UTF7-IMAP';

    /**
     * @readwrite
     * @access static
     * @var string
     */
    const ENCODING_ASCII = 'ASCII';

    /**
     * @readwrite
     * @access static
     * @var string
     */
    const ENCODING_EUC_JP = 'EUC-JP';

    /**
     * @readwrite
     * @access static
     * @var string
     */
    const ENCODING_SJIS = 'SJIS';

    /**
     * @readwrite
     * @access static
     * @var string
     */
    const ENCODING_EUCJP_WIN = 'eucJP-win';

    /**
     * @readwrite
     * @access static
     * @var string
     */
    const ENCODING_EUC_JP_2004 = 'EUC-JP-2004';

    /**
     * @readwrite
     * @access static
     * @var string
     */
    const ENCODING_SJIS_WIN = 'SJIS-win';

    /**
     * @readwrite
     * @access static
     * @var string
     */
    const ENCODING_SJIS_MOBILEDOCOMO = 'SJIS-Mobile#DOCOMO';

    /**
     * @readwrite
     * @access static
     * @var string
     */
    const ENCODING_SJIS_MOBILEKDDI = 'SJIS-Mobile#KDDI';

    /**
     * @readwrite
     * @access static
     * @var string
     */
    const ENCODING_SJIS_MOBILESOFTBANK = 'SJIS-Mobile#SOFTBANK';

    /**
     * @readwrite
     * @access static
     * @var string
     */
    const ENCODING_SJIS_MAC = 'SJIS-mac';

    /**
     * @readwrite
     * @access static
     * @var string
     */
    const ENCODING_SJIS_2004 = 'SJIS-2004';

    /**
     * @readwrite
     * @access static
     * @var string
     */
    const ENCODING_UTF_8_MOBILEDOCOMO = 'UTF-8-Mobile#DOCOMO';

    /**
     * @readwrite
     * @access static
     * @var string
     */
    const ENCODING_UTF_8_MOBILEKDDI_A = 'UTF-8-Mobile#KDDI-A';

    /**
     * @readwrite
     * @access static
     * @var string
     */
    const ENCODING_UTF_8_MOBILEKDDI_B = 'UTF-8-Mobile#KDDI-B';

    /**
     * @readwrite
     * @access static
     * @var string
     */
    const ENCODING_UTF_8_MOBILESOFTBANK = 'UTF-8-Mobile#SOFTBANK';

    /**
     * @readwrite
     * @access static
     * @var string
     */
    const ENCODING_CP932 = 'CP932';

    /**
     * @readwrite
     * @access static
     * @var string
     */
    const ENCODING_CP51932 = 'CP51932';

    /**
     * @readwrite
     * @access static
     * @var string
     */
    const ENCODING_JIS = 'JIS';

    /**
     * @readwrite
     * @access static
     * @var string
     */
    const ENCODING_ISO_2022_JP = 'ISO-2022-JP';

    /**
     * @readwrite
     * @access static
     * @var string
     */
    const ENCODING_ISO_2022_JP_MS = 'ISO-2022-JP-MS';

    /**
     * @readwrite
     * @access static
     * @var string
     */
    const ENCODING_GB18030 = 'GB18030';

    /**
     * @readwrite
     * @access static
     * @var string
     */
    const ENCODING_WINDOWS_1252 = 'Windows-1252';

    /**
     * @readwrite
     * @access static
     * @var string
     */
    const ENCODING_WINDOWS_1254 = 'Windows-1254';

    /**
     * @readwrite
     * @access static
     * @var string
     */
    const ENCODING_ISO_8859_1 = 'ISO-8859-1';

    /**
     * @readwrite
     * @access static
     * @var string
     */
    const ENCODING_ISO_8859_2 = 'ISO-8859-2';

    /**
     * @readwrite
     * @access static
     * @var string
     */
    const ENCODING_ISO_8859_3 = 'ISO-8859-3';

    /**
     * @readwrite
     * @access static
     * @var string
     */
    const ENCODING_ISO_8859_4 = 'ISO-8859-4';

    /**
     * @readwrite
     * @access static
     * @var string
     */
    const ENCODING_ISO_8859_5 = 'ISO-8859-5';

    /**
     * @readwrite
     * @access static
     * @var string
     */
    const ENCODING_ISO_8859_6 = 'ISO-8859-6';

    /**
     * @readwrite
     * @access static
     * @var string
     */
    const ENCODING_ISO_8859_7 = 'ISO-8859-7';

    /**
     * @readwrite
     * @access static
     * @var string
     */
    const ENCODING_ISO_8859_8 = 'ISO-8859-8';

    /**
     * @readwrite
     * @access static
     * @var string
     */
    const ENCODING_ISO_8859_9 = 'ISO-8859-9';

    /**
     * @readwrite
     * @access static
     * @var string
     */
    const ENCODING_ISO_8859_10 = 'ISO-8859-10';

    /**
     * @readwrite
     * @access static
     * @var string
     */
    const ENCODING_ISO_8859_13 = 'ISO-8859-13';

    /**
     * @readwrite
     * @access static
     * @var string
     */
    const ENCODING_ISO_8859_14 = 'ISO-8859-14';

    /**
     * @readwrite
     * @access static
     * @var string
     */
    const ENCODING_ISO_8859_15 = 'ISO-8859-15';

    /**
     * @readwrite
     * @access static
     * @var string
     */
    const ENCODING_ISO_8859_16 = 'ISO-8859-16';

    /**
     * @readwrite
     * @access static
     * @var string
     */
    const ENCODING_EUC_CN = 'EUC-CN';

    /**
     * @readwrite
     * @access static
     * @var string
     */
    const ENCODING_CP936 = 'CP936';

    /**
     * @readwrite
     * @access static
     * @var string
     */
    const ENCODING_HZ = 'HZ';

    /**
     * @readwrite
     * @access static
     * @var string
     */
    const ENCODING_EUC_TW = 'EUC-TW';

    /**
     * @readwrite
     * @access static
     * @var string
     */
    const ENCODING_BIG_5 = 'BIG-5';

    /**
     * @readwrite
     * @access static
     * @var string
     */
    const ENCODING_CP950 = 'CP950';

    /**
     * @readwrite
     * @access static
     * @var string
     */
    const ENCODING_EUC_KR = 'EUC-KR';

    /**
     * @readwrite
     * @access static
     * @var string
     */
    const ENCODING_UHC = 'UHC';

    /**
     * @readwrite
     * @access static
     * @var string
     */
    const ENCODING_ISO_2022_KR = 'ISO-2022-KR';

    /**
     * @readwrite
     * @access static
     * @var string
     */
    const ENCODING_WINDOWS_1251 = 'Windows-1251';

    /**
     * @readwrite
     * @access static
     * @var string
     */
    const ENCODING_CP866 = 'CP866';

    /**
     * @readwrite
     * @access static
     * @var string
     */
    const ENCODING_KOI8_R = 'KOI8-R';

    /**
     * @readwrite
     * @access static
     * @var string
     */
    const ENCODING_KOI8_U = 'KOI8-U';

    /**
     * @readwrite
     * @access static
     * @var string
     */
    const ENCODING_ARMSCII_8 = 'ArmSCII-8';

    /**
     * @readwrite
     * @access static
     * @var string
     */
    const ENCODING_CP850 = 'CP850';

    /**
     * @readwrite
     * @access static
     * @var string
     */
    const ENCODING_JIS_MS = 'JIS-ms';

    /**
     * @readwrite
     * @access static
     * @var string
     */
    const ENCODING_ISO_2022_JP_2004 = 'ISO-2022-JP-2004';

    /**
     * @readwrite
     * @access static
     * @var string
     */
    const ENCODING_ISO_2022_JP_MOBILEKDDI = 'ISO-2022-JP-MOBILE#KDDI';

    /**
     * @readwrite
     * @access static
     * @var string
     */
    const ENCODING_CP50220 = 'CP50220';

    /**
     * @readwrite
     * @access static
     * @var string
     */
    const ENCODING_CP50220RAW = 'CP50220raw';

    /**
     * @readwrite
     * @access static
     * @var string
     */
    const ENCODING_CP50221 = 'CP50221';

    /**
     * @readwrite
     * @access static
     * @var string
     */
    const ENCODING_CP50222 = 'CP50222';
}


