<?php declare(strict_types=1); namespace IR\Utils\Compression; if (!defined('IR_START')) exit('<pre>No direct script access allowed</pre>');
/**
 * @framework       iResponse Framework 
 * @version         1.0
 * @author          Amine Idrissi <contact@iresponse.tech>
 * @date            2024
 * @name            Zip.php	
 */
# php defaults
use \ZipArchive;
use \RecursiveDirectoryIterator;
use \RecursiveIteratorIterator;

# utilities
use IR\Utils\System\FileSystem as FileSystem;

/**
 * @name Zip
 * @description Compression utils class
 */
class Zip
{
    /**
     * @name getInstance
     * @description singleton access to constructor
     * @access public | static
     * @return Zip
     */
    public static function getInstance() : Zip
    {
        if(self::$_instance == null)
        {
            self::$_instance = new Zip();
        }
        
        return self::$_instance;
    }
    
    /**
     * @name extractTo
     * @description extracts a zip file to a given directory
     * @access public
     * @return int
     */
    public function extractTo(string $filePath,string $extractPath,int $removeSource = Zip::KEEP_SOURCE_FILE) : bool
    {
        if(FileSystem::getInstance()->fileExists($extractPath))
        {
            $zip = new ZipArchive();
            $res = $zip->open($filePath);

            if ($res === true) 
            {
                $zip->extractTo($extractPath);
                $zip->close();
                
                # remove source file 
                if($removeSource == Zip::DELETE_SOURCE_FILE)
                {
                    FileSystem::getInstance()->deleteFile($filePath);
                }
                
                return true;
            } 
        }

        return false;
    }
    
    /**
     * @name zipFolderTo
     * @description zip a folder to a given directory
     * @access public
     * @return int
     */
    public function zipTo(string $fileName,string $folderTozip,string $zipPathTo,int $removeSource = Zip::KEEP_SOURCE_FILE) : bool
    {
        if(FileSystem::getInstance()->fileExists($zipPathTo))
        {
            $zip = new ZipArchive();
            $zip->open(rtrim($zipPathTo,DS) . DS . $fileName, ZipArchive::CREATE | ZipArchive::OVERWRITE);

            $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($folderTozip),RecursiveIteratorIterator::LEAVES_ONLY);
            
            foreach ($files as $file)
            {
                if (!$file->isDir())
                {
                    $filePath = $file->getRealPath();
                    $relativePath = substr($filePath, strlen($folderTozip) + 1);
                    $zip->addFile($filePath, $relativePath);
                }
            }

            $zip->close();
            
            # remove source file/folder 
            if($removeSource == Zip::DELETE_SOURCE_FILE)
            {
                FileSystem::getInstance()->deleteFile($folderTozip);
            }
                
            return true;
        }

        return false;
    }
    
    /**
     * @name getFileNames
     * @description gets the names of all the files in a specefic zip file
     * @access public
     * @param string $zipFile
     * @param array
     * @return
     */
    public function getFileNames(string $zipFile) : array
    {
        $names = [];
        $zip = new ZipArchive();
        $res = $zip->open($zipFile);
        
        if ($res === true) 
        {
            for ($i = 0; $i < $zip->numFiles; $i++) 
            {
                $names[] = $zip->getNameIndex($i);
            }
        } 
        
        return $names;
     } 
    
    /**
     * @name __construct
     * @description class constructor
     * @access private
     * @return Zip
     */
    private function __construct() {}

    /**
     * @name __clone
     * @description preventing cloning object
     * @access public
     * @return Zip
     */
    public function __clone()
    {
        return (self::$_instance != null) ? self::$_instance : new Zip();  
    }
    
    /** 
     * @readwrite
     * @access private 
     * @var Zip
     */ 
    private static $_instance;
    
    /** 
     * @read
     * @access protected 
     * @var integer
     */
    const KEEP_SOURCE_FILE = 0;
    
    /** 
     * @read
     * @access protected 
     * @var integer
     */
    const DELETE_SOURCE_FILE = 1;
}


