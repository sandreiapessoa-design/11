<?php declare(strict_types=1); namespace IR\Utils\System; if (!defined('IR_START')) exit('<pre>No direct script access allowed</pre>');
/**
 * @framework       iResponse Framework 
 * @version         1.0
 * @author          Amine Idrissi <contact@iresponse.tech>
 * @date            2024
 * @name            FileSystem.php	
 */

# utilities
use IR\Utils\System\Terminal as Terminal;
use IR\Utils\Types\Arrays as Arrays; 

/**
 * @name FileSystem
 * @description objects utils class
 */
class FileSystem
{
    /**
     * @name getInstance
     * @description singleton access to constructor
     * @access public | static
     * @return FileSystem
     */
    public static function getInstance() : FileSystem
    {
        if(self::$_instance == null)
        {
            self::$_instance = new FileSystem();
        }
        
        return self::$_instance;
    }
    
    /**
     * @name sizeReadable
     * @description converts a file size into a human readable format
     * @access public
     * @param  int $bytes
     * @return int
     */
    public function sizeReadable(int $size, string $unit) : string
    {
        if((!$unit && $size >= 1<<30) || $unit == "GB")
        {
            return number_format($size/(1<<30),2)."G";
        }

        if((!$unit && $size >= 1<<20) || $unit == "MB")
        {
            return number_format($size/(1<<20),2)."M";
        }

        if( (!$unit && $size >= 1<<10) || $unit == "KB")
        {
            return number_format($size/(1<<10),2)."K";
        }

        return number_format($size)." bytes";
    } 
    
    /**
     * @name copyFileOrDirectory
     * @description copies a file or a dir to a new destination
     * @access public
     * @param  string $source
     * @param  string $destination
     * @return bool
     */
    public function copyFileOrDirectory(string $source, string $destination) : bool
    {
        return copy($source,$destination);
    }

    /**
     * @name move
     * @description moves a file or a dir to a new destination
     * @access public
     * @param  string $source
     * @param  string $destination
     * @return 
     */
    public function moveFileOrDirectory(string $source, string $destination)
    {
        Terminal::getInstance()->cmd('mv ' . $source . ' ' . $destination);
    }
    
    /**
     * @name move
     * @description empty a directory
     * @access public
     * @param  string $directory
     * @return 
     */
    public function emptyDirectory(string $directory)
    {
        Terminal::getInstance()->cmd('rm -rf ' . $directory . '/*');
    }
    
    /**
     * @name fileExists
     * @description check a file if exists
     * @access public
     * @param  string $path
     * @return bool
     */
    public function fileExists(string $path) : bool
    {
        return file_exists($path);
    }
    
    /**
     * @name move
     * @description moves a file to a new destination
     * @access public
     * @param  string $source
     * @param  string $destination
     * @return 
     */
    public function createFile(string $file)
    {
        Terminal::getInstance()->cmd('touch ' . $file);
    }
    
    /**
     * @name deleteFile
     * @description deletes a file
     * @access public
     * @param  string $file
     * @return 
     */
    public function deleteFile(string $file)
    {
        Terminal::getInstance()->cmd('rm -rf ' . $file);
    }
    
    /**
     * @name directoryExists
     * @description checks if a directory exists
     * @access public
     * @param  string $path
     * @return 
     */
    public function directoryExists(string $path) : bool
    {
        return is_dir($path) && $this->fileExists($path);
    }
    
    /**
     * @name createDir
     * @description creates a directory
     * @access public
     * @param  string $path
     * @return 
     */
    public function createDir(string $path)
    {
        Terminal::getInstance()->cmd('mkdir -p ' . $path);
    }
    
    /**
     * @name removeDir
     * @description removes a directory
     * @access public
     * @param  string $path
     * @return 
     */
    public function removeDir(string $path)
    {
        Terminal::getInstance()->cmd('rm -rf ' . $path);
    }
    
    /**
     * @name fileInfo
     * @description returns file's info
     * @access public
     * @param  string $file
     * @return bool
     */
    public function fileInfo(string $file) : array
    {
        return [
            'path' => $file,
            'name' => basename($file),
            'size' => filesize($file),
            'info-path' => pathinfo($file),
            'type' => Arrays::getInstance()->get(pathinfo($file),'extension')
        ]; 
    }
    
    /**
     * @name getRemoteFileSize
     * @description returns remote file size
     * @access public
     * @param  string $url
     * @return bool
     */
    public function getRemoteFileSize(string $url) : string
    {
        $result = 0;

        $curl = \curl_init($url);

        \curl_setopt($curl,CURLOPT_NOBODY,true);
        \curl_setopt($curl,CURLOPT_HEADER,true);
        \curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
        \curl_setopt($curl,CURLOPT_FOLLOWLOCATION,true);
        \curl_setopt($curl,CURLOPT_USERAGENT,"Mozilla/5.0 (X11; Ubuntu; Linux i686; rv:26.0) Gecko/20100101 Firefox/26.0");

        $data = \curl_exec($curl);
        \curl_close($curl);

        if ($data) 
        {
            $content_length = "unknown";
            $status = "unknown";
            $matches = [];
            
            if (preg_match("/^HTTP\/1\.[01] (\d\d\d)/", $data, $matches)) 
            {
                $status = (int) $matches[1];
            }

            if (preg_match("/Content-Length: (\d+)/", $data, $matches)) 
            {
                $content_length = (int) $matches[1];
            }

            if ($status == 200 || ($status > 300 && $status <= 308)) 
            {
                $result = $content_length;
            }
        }

        return $this->formatSize(intval($result));
    }
    
    /**
     * @name readFile
     * @description reads a file
     * @access public
     * @param  string $file
     * @return string
     */
    public function readFile(string $file)
    {
        return file_get_contents($file);
    }
    
    /**
     * @name writeFile
     * @description write data to file
     * @access public
     * @param  string $file
     * @param  string $content
     * @param  int $flags
     * @return bool
     */
    public function writeFile(string $file, string $content,int $flags = 0)
    {
        return file_put_contents($file,$content, $flags);
    }
    
    /**
     * @name formatSize
     * @description formats the size of a file 
     * @access public
     * @param  int $size
     * @return string
     */
    public function formatSize(int $size) : string
    {
        $sizes = [" Bytes", " KB", " MB", " GB", " TB", " PB", " EB", " ZB", " YB"];
        
        if ($size == 0) 
        {
            return('n/a');
        } 
        else 
        {
            return (round($size / pow(1024, ($i = floor(log($size, 1024)))), 2) . $sizes[$i]);
        }
    }
    
    /**
     * @name scanDir
     * @description scans a directory and returns file names
     * @access public
     * @param  string $directory
     * @return string
     */
    public function scanDir(string $directory) : array
    {
        $files = [];
        
        if (is_dir($directory))
        {
            $dh = opendir($directory);
            
            if ($dh)
            {
                while (($file = readdir($dh)) !== false)
                {
                    if(!in_array(trim($file),['.','..']))
                    {
                        $files[] = $file;
                    }   
                }
                
                closedir($dh);
            }
        }

        return $files;
    }
    
    /**
     * @name getAllFiles
     * @description scans a directory and returns all file names
     * @access public
     * @param  string $directory
     * @return string
     */
    public function getAllFiles(string $directory) : array
    {
        $files = [];
        
        if (is_dir($directory))
        {
            $dir = new \RecursiveDirectoryIterator($directory,\RecursiveDirectoryIterator::SKIP_DOTS);
            
            foreach (new \RecursiveIteratorIterator($dir) as $file) 
            {
                $files[] = $file->getPathName();
            }
        }

        return $files;
    }
    
    /**
     * @name __construct
     * @description class constructor
     * @access private
     * @return FileSystem
     */
    private function __construct() {}

    /**
     * @name __clone
     * @description preventing cloning object
     * @access public
     * @return FileSystem
     */
    public function __clone()
    {
        return (self::$_instance != null) ? self::$_instance : new FileSystem();  
    }
    
    /** 
     * @readwrite
     * @access private 
     * @var FileSystem
     */ 
    private static $_instance;
}


