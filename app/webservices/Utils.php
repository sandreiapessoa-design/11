<?php declare(strict_types=1); namespace IR\App\Webservices; if (!defined('IR_START')) exit('<pre>No direct script access allowed</pre>');
/**
 * @framework       iResponse Framework 
 * @version         1.0
 * @author          Amine Idrissi <contact@iresponse.tech>
 * @date            2024
 * @name            Utils.php	
 */

# core 
use IR\Core\Base as Base;
use IR\Core\Application as Application;

# helpers 
use IR\App\Helpers\Authentication as Authentication;
use IR\App\Helpers\Page as Page;

/**
 * @name Utils
 * @description Utils WebService
 */
class Utils extends Base
{
    /**
     * @app
     * @readwrite
     */
    protected $app;
    
    /**
     * @name init
     * @description initializing process before the action method executed
     * @once
     * @protected
     */
    public function init() 
    {
        # set the current application to a local variable
        $this->app = Application::getCurrent();
    }
    
    /**
     * @name checkForDuplicates
     * @description check for duplicates action
     * @before init
     */
    public function checkForDuplicates($parameters = []) 
    { 
        # check for authentication
        if(!Authentication::isUserAuthenticated())
        {
            Page::printApiResults(401,'Only logged-in access allowed !');
        }
        
        $stopAction = false;
        $id = intval($this->app->utils->arrays->get($parameters,'id'));
        $model = $this->app->utils->arrays->get($parameters,'model');
        $package = $this->app->utils->arrays->get($parameters,'package');
        $formdata = $this->app->utils->arrays->formToArray(urldecode($this->app->utils->arrays->get($parameters,'form-data')));
        $columns = array_unique(array_filter(explode('|',$this->app->utils->arrays->get($parameters,'columns'))));
        $inputs = array_unique(array_filter(explode('|',$this->app->utils->arrays->get($parameters,'inputs'))));
        $class = "IR\App\Models\\{$package}\\{$model}"; 
                
        # update case
        if($id > 0)
        {
            $checkColumns = [];
            $checkInputs = [];
            $check = false;
            $object = new $class();
            $object->setId($id);
            $object->load();
            
            for ($index = 0; $index < count($columns); $index++) 
            {
                $getter = 'get' . str_replace(' ','',preg_replace( "/\r|\n/","",ucwords(str_replace('_',' ',$columns[$index]))));
                
                if($object->$getter() != $formdata[$inputs[$index]])
                {
                    $checkColumns[] = $columns[$index];
                    $checkInputs[] = $inputs[$index];
                    $check = true;
                }
            }
            
            if($check == true)
            {
                $table = $object->getTable();
                $schema = $object->getSchema();
                $query = "SELECT COUNT(1) AS count FROM {$schema}.{$table} WHERE ";

                for ($index = 0; $index < count($checkColumns); $index++) 
                {
                    $query .= "{$checkColumns[$index]} = '" . $formdata[$checkInputs[$index]] . "' OR ";
                }

                $query = $this->app->utils->strings->endsWith($query,' OR ') ? substr($query,0, strlen($query) - 4) : $query;
                $results = $this->app->database('system')->execute($query);
                $stopAction = count($results) > 0 && $results[0]['count'] > 0;
            }
        }
        # insert case
        else
        {
            $object = new $class();
            $table = $object->getTable();
            $schema = $object->getSchema();
            $query = "SELECT COUNT(1) AS count FROM {$schema}.{$table} WHERE ";
            
            for ($index = 0; $index < count($columns); $index++) 
            {
                $query .= "{$columns[$index]} = '" . $formdata[$inputs[$index]] . "' OR ";
            }
            
            $query = $this->app->utils->strings->endsWith($query,' OR ') ? substr($query,0, strlen($query) - 4) : $query;
            $results = $this->app->database('system')->execute($query);
            $stopAction = count($results) > 0 && $results[0]['count'] > 0;
        }
        
        if($stopAction == false)
        {
            Page::printApiResults(200,'No duplicates found !');
        }
        else
        {
            Page::printApiResults(500,'There is already a record with these information you have provided !');
        }
    }
}


