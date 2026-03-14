<?php declare(strict_types=1);
/**
 * @framework       iResponse Framework 
 * @version         1.0
 * @author          Amine Idrissi <contact@iresponse.tech>
 * @date            2024
 * @name            init.conf.php	
 */

# defining start time
define('IR_START',microtime(true));

# framework information
define('FW_NAME','iResponse Framework');
define('FW_ABBR','IR');
define('FW_VERSION','1.0');
define('FW_AUTHOR','Amine Idrissi');
define('FW_RELEASE_DATE','2024');
define('FW_VENDOR','iResponse');

# defining separators
define('DS',DIRECTORY_SEPARATOR);
define('RDS','/');
define('ANS','\\');

# defining the base path
define('BASE_PATH',dirname(__FILE__,2));

# defining framework directories
define('CONFIGS_PATH',BASE_PATH . DS . 'config');
define('ASSETS_PATH',BASE_PATH . DS . 'assets');
define('DATASOURCES_PATH',BASE_PATH . DS . 'datasources');
define('ROUTES_PATH',BASE_PATH . DS . 'routes');
define('STORAGE_PATH',BASE_PATH . DS . 'storage');
define('CACHE_PATH',STORAGE_PATH . DS . 'affiliate');
define('SESSIONS_PATH',STORAGE_PATH . DS . 'sessions');
define('TRASH_PATH',STORAGE_PATH . DS . 'trash');
define('LOGS_PATH',STORAGE_PATH . DS . 'logs');
define('PUBLIC_PATH',BASE_PATH . DS . 'public');
define('MEDIA_PATH',PUBLIC_PATH . DS . 'media');
define('VENDOR_PATH',BASE_PATH . DS . 'vendor');

# defining application directories
define('APP_PATH',BASE_PATH . DS . 'app');
define('API_PATH',APP_PATH . DS . 'api');
define('CONTROLLERS_PATH',APP_PATH . DS . 'controllers');
define('MODELS_PATH',APP_PATH . DS . 'models');
define('VIEWS_PATH',APP_PATH . DS . 'views');
define('HELPERS_PATH',APP_PATH . DS . 'helpers');
define('LIBRARIES_PATH',APP_PATH . DS . 'libraries');
define('WEB_SERVICES_PATH',APP_PATH . DS . 'webservices');

# defining the default controller and action 
define('DEFAULT_CONTROLLER','dashboard');
define('DEFAULT_ACTION','main');
define('DEFAULT_EXTENSION','html');

# defining coockies information
define('COOKIE_EXPIRE',time() + 60*60*24*30);

# defining dev mode
define('IS_DEV_MODE',false);