<?php
/**
 * Cntysoft Cloud Software Team
 * 
 * @author SOFTBOY <cntysoft@163.com>
 * @copyright  Copyright (c) 2010-2011 Cntysoft Technologies China Inc. <http://www.cntysoft.com>
 * @license    http://www.cntysoft.com/license/new-bsd     New BSD License
 */
namespace Cntysoft\Kernel;
use Phalcon\Config;
use Cntysoft\Stdlib\ArrayUtils;
use Cntysoft\Stdlib\Filesystem;
/**
 * 这个类负责系统范围里面的配置存取,貌似有点占内存,暂时就实现集中读取的功能
 */
class ConfigProxy
{
   const C_TYPE_GLOBAL = 1;
   const C_TYPE_MODULE = 2;
   const C_TYPE_APP = 3;
   const C_TYPE_FRAMEWORK_SYS = 4;
   const C_TYPE_FRAMEWORK_VENDER = 5;

   /**
    * 缓存处理对象
    * @var  
    */
   protected static $cacher = null;
   /**
    * 全局配置文件里面的配置信息
    * 
    * @var array $global
    */
   protected static $global = null;
   /**
    * 模块相关的配置信息
    * 
    * @var array $modules
    */
   protected static $modules = array();
   /**
    * APP相关的配置信息
    * 
    * @var array $apps
    */
   protected static $apps = array();
   /**
    * 框架一些配置
    * 
    * @var array $framework
    */
   protected static $frameworks = array();

   /**
    * 获取全局配置信息
    * 
    * @return \Phalcon\Config
    */
   public static function getGlobalConfig()
   {
      $cache = self::getCacher(array('Config'));
      $cachekey = md5('Application.json');
      if (!$cache->exists($cachekey)) {
         if (file_exists(CNTY_CFG_DIR . DS . 'Application.config.php')) {
            return new Config(include CNTY_CFG_DIR . DS . 'Application.config.php');
         }
         $data = Filesystem::fileGetContents(CNTY_CFG_DIR . DS . 'Application.json');
         $cache->save($cachekey, $data);
      } else {
         $data = $cache->get($cachekey);
      }
      return new Config(json_decode($data, true));
   }

   /**
    * 获取指定模型的相关配置信息
    * 
    * @param string $module
    * @return  \Phalcon\Config
    */
   public static function getModuleConfig($module)
   {
      if (!array_key_exists($module, self::$modules)) {
         $g = self::getGlobalConfig();
         $modules = $g->modules;
         if (!$modules->offsetExists($module)) {
            throw_exception(new Exception(
                    StdErrorType::msg('E_MODULE_NOT_SUPPORT', $module), StdErrorType::code('E_MODULE_NOT_SUPPORT')), \Cntysoft\STD_EXCEPTION_CONTEXT);
         }
         $filename = CNTY_CFG_DIR . DS . 'Module' . DS . $module . '.json';
         $cache = self::getCacher(array('Config', 'Module'));
         $cachekey = md5('Module' . DS . $module . '.json');
         if (!$cache->exists($cachekey)) {
            if (!file_exists(CNTY_CFG_DIR . DS . 'Module' . DS . $module . '.config.php')) {
               if (file_exists($filename)) {
                  $data = Filesystem::fileGetContents($filename);
                  $cache->save($cachekey, $data);
               }
            }
         } else {
            $data = $cache->get($cachekey);
         }
         if (!file_exists($filename)) {
            self::$modules[$module] = new Config;
         } else {
            if (file_exists(CNTY_CFG_DIR . DS . 'Module' . DS . $module . '.config.php')) {
               self::$modules[$module] = new Config(include CNTY_CFG_DIR . DS . 'Module' . DS . $module . '.config.php');
            } else {
               self::$modules[$module] = new Config(json_decode($data, true));
            }
         }
      }
      return self::$modules[$module];
   }

   /**
    * 获取系统所有的模块的配置信息
    * 
    * @return array
    */
   public static function getModuleConfigs()
   {
      return self::$modules;
   }

   /**
    * 获取应用程序配置信息, 配置文件不存在暂时就抛出异常
    * 
    * @param string $module
    * @param string $app
    * @return array
    */
   public static function getAppConfig($module, $app)
   {
      /**
       * 不判断module中是否存在某个APP 直接构造配置文件名称
       * @todo 以后加上 配置文件的后缀信息怎么来 暂时硬编码
       */
      $key = $module . '\\' . $app;
      if (!array_key_exists($key, self::$apps)) {
         $filename = self::getAppConfigFilename($module, $app);
         $cache = self::getCacher(array('Config', 'App', $module));
         $cachekey = md5('App' . DS . $module . DS . $app . '.json');
         if (!$cache->exists($cachekey)) {
            if (!file_exists(CNTY_CFG_DIR . DS . 'Module' . DS . $module . '.config.php')) {
               if (file_exists($filename)) {
                  $data = Filesystem::fileGetContents($filename);
                  $cache->save($cachekey, $data);
               }
            }
         } else {
            $data = $cache->get($cachekey);
         }
         if (!file_exists($filename)) {
            throw_exception(new Exception(
                    StdErrorType::msg('E_CONFIG_FILE_NOT_EXIST', str_replace(CNTY_ROOT_DIR, '', $filename)), StdErrorType::code('E_CONFIG_FILE_NOT_EXIST')), \Cntysoft\STD_EXCEPTION_CONTEXT);
         }
         if (!file_exists(CNTY_CFG_DIR . DS . 'Module' . DS . $module . '.config.php')) {
            self::$apps[$key] = new Config(json_decode($data, true));
         } else {
            self::$apps[$key] = new Config(include $filename);
         }
      }
      return self::$apps[$key];
   }

   /**
    * 获取框架的配置信息
    * 
    * @param string $name 框架的名称
    * @param int $type 框架的类型
    * @return \Phalcon\Config
    */
   public static function getFrameworkConfig($name, $type = self::C_TYPE_FRAMEWORK_SYS)
   {
      //获取特定的路径
      $fileNameInfo = self::getFrameworkConfigFilename($name, $type);
      $key = $fileNameInfo[0];
      if (!array_key_exists($key, self::$frameworks)) {
         $filename = $fileNameInfo[1]; //后缀暂时硬编码
         $frame = $type == self::C_TYPE_FRAMEWORK_SYS ? 'Framework' : 'Vender';
         $cache = self::getCacher(array('Config', $frame));
         $cachekey = md5($frame . DS . $name . '.json');
         if (!$cache->exists($cachekey)) {
            if (strpos($filename, '.json') && file_exists($filename)) {
               $data = Filesystem::fileGetContents($filename);
               $cache->save($cachekey, $data);
            }
         } else {
            $data = $cache->get($cachekey);
         }
         if (!file_exists($filename)) {
            self::$frameworks[$key] = new Config;
         } else {
            if (strpos($filename, '.json')) {
               self::$frameworks[$key] = new Config(json_decode($data, true));
            } else {
               self::$frameworks[$key] = new Config(include $filename);
            }
         }
      }
      return self::$frameworks[$key];
   }

   /**
    * 设置全局的配置信息
    * 
    * @param string $key 配置键值 支持点语法 A.B.C
    * @param array $data
    * @return string 
    */
   public static function setGlobalConfig($key, $data)
   {
      $config = self::getGlobalConfig();
      set_config_item_by_path($config, $key, $data);
      $filename = self::getGlobalConfigFilename();
      if (!strpos($filename, '.json')) {
         self::writeBackToFile($filename, $config->toArray());
      } else {
         self::writeBackToFile($filename, json_encode($config->toArray(), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
         $cache = self::getCacher(array('Config'));
         $cache->delete(md5('Application.json'));
      }
      self::$global = $config;
   }

   /**
    * 设置framework的配置信息
    * 
    * @param string $name
    * @param string $key 
    * @param mixed $data
    * @param int $type 框架是内置的还是第三方的

    */
   public static function setFrameworkConfig($name, $key, $data, $type = self::C_TYPE_FRAMEWORK_SYS)
   {
      $filename = self::getFrameworkConfigFilename($name, $type);
      if (file_exists($filename[1])) {
         $config = self::getFrameworkConfig($name, $type);
      } else {
         $config = new Config();
      }
      set_config_item_by_path($config, $key, $data);
      if (!strpos($filename, '.json')) {
         self::writeBackToFile($filename[1], $config->toArray());
      } else {
         self::writeBackToFile($filename[1], json_encode($config->toArray(), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
         $frame = $type == self::C_TYPE_FRAMEWORK_SYS ? 'Framework' : 'Vender';
         $cache = self::getCacher(array('Config', $frame));
         $cache->delete(md5($frame . DS . $name . '.json'));
      }
      self::$frameworks[$filename[0]] = $config;
   }

   /**
    * 写入模块的配置信息
    * 
    * @param string $name 模块的名称
    * @param string $key 写入的键值数据
    * @param mixed $data 更改的数据
    */
   public static function setModuleConfig($name, $key, $data)
   {

      $filename = self::getModuleConfigFilename($name);
      if (file_exists($filename)) {
         $config = self::getModuleConfig($name);
      } else {
         $config = new Config();
      }
      $map = get_config_item_by_path($config, $key);
      foreach ($data as $key => $value) {
         $map[$key] = $value;
      }
      if (!strpos($filename, '.json')) {
         self::writeBackToFile($filename, $config->toArray());
      } else {
         self::writeBackToFile($filename, json_encode($config->toArray(), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
         $cache = self::getCacher(array('Config', 'Module'));
         $cache->delete(md5('Module' . DS . $name . '.json'));
      }
   }

   /**
    * 一次写入一个模型所有的配置信息， 用的时候要注意，这个会无条件覆盖原有的配置信息
    * 
    * @param string $name 模块的名称
    * @param array $config 所有配置项的数据
    */
   public static function setModuleConfigs($name, array $config)
   {
      $filename = self::getModuleConfigFilename($name);
      if (!strpos($filename, '.json')) {
         self::writeBackToFile($filename, $config);
      } else {
         self::writeBackToFile($filename, json_encode($config, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
         $cache = self::getCacher(array('Config', 'Module'));
         $cache->delete(md5('Module' . DS . $name . '.json'));
      }
      self::$modules[$name] = $config;
   }

   /**
    * 写入APP的配置信息
    * 
    * @param string $module APP模块的名称
    * @param string $name APP的名称
    * @param string $key 写入配置键值
    * @param mixed $data 写入配置键下数据
    */
   public static function setAppConfig($module, $name, $key, $data)
   {
      $filename = self::getAppConfigFilename($module, $name);
      if (file_exists($filename)) {
         $config = self::getAppConfig($module, $name)->toArray();
      } else {
         $config = array();
      }
      ArrayUtils::set($config, $key, $data);
      if (!strpos($filename, '.json')) {
         self::writeBackToFile($filename, $config);
      } else {
         self::writeBackToFile($filename, json_encode($config, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
         $cache = self::getCacher(array('Config', 'App', $module));
         $cache->delete(md5('App' . DS . $module . DS . $name . '.json'));
      }
      self::$apps[$module . '\\' . $name] = $config;
   }

   /**
    * 一次性存储一个APP配置的配置数据, 用的时候要注意，这个会无条件覆盖原有的配置信息
    * 
    * @param string $module APP模块的名称
    * @param string $name APP的名称
    * @param array $config 配置信息
    */
   public static function setAppConfigs($module, $name, array $config)
   {
      $filename = self::getAppConfigFilename($module, $name);
      if (!strpos($filename, '.json')) {
         self::writeBackToFile($filename, $config);
      } else {
         self::writeBackToFile($filename, json_encode($config, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
         $cache = self::getCacher(array('Config', 'App', $module));
         $cache->delete(md5('App' . DS . $module . DS . $name . '.json'));
      }
      self::$apps[$module . '\\' . $name] = $config;
   }

   /**
    * 获取全局信息配置文件名称
    * 
    * @return string
    */
   protected static function getGlobalConfigFilename()
   {
      if (file_exists(CNTY_CFG_DIR . DS . 'Application.config.php')) {
         return CNTY_CFG_DIR . DS . 'Application.config.php';
      } else {
         return CNTY_CFG_DIR . DS . 'Application.json';
      }
   }

   /**
    * 获取框架的配置文件名称
    * 
    * @param string $name Framework的
    * @param string $type
    * @return array
    */
   protected static function getFrameworkConfigFilename($name, $type)
   {
      //获取特定的路径
      if ($type == self::C_TYPE_FRAMEWORK_SYS) {
         $key = 'Sys\\' . $name;
         $path = CNTY_CFG_DIR . DS . 'Framework';
      } elseif ($type == self::C_TYPE_FRAMEWORK_VENDER) {
         $key = 'Vender\\' . $name;
         $path = CNTY_CFG_DIR . DS . 'Vender';
      } else {
         throw_exception(new Exception(
                 StdErrorType::msg('E_FRAMEWORK_TYPE_NOT_SUPPORTED', $type), StdErrorType::code('E_FRAMEWORK_TYPE_NOT_SUPPORTED')), \Cntysoft\STD_EXCEPTION_CONTEXT);
      }
      if (file_exists($path . DS . $name . '.config.php')) {
         $filenamesuffix = '.config.php';
      } else {
         $filenamesuffix = '.json';
      }
      return array($key, $path . DS . $name . $filenamesuffix); //后缀暂时硬编码
   }

   /**
    * 获取模型的配置信息文件名称
    * 
    * @param string $name
    * @return string
    */
   protected static function getModuleConfigFilename($name)
   {
      if (file_exists(CNTY_CFG_DIR . DS . 'Module' . DS . $name . '.config.php')) {
         return CNTY_CFG_DIR . DS . 'Module' . DS . $name . '.config.php';
      } else {
         return CNTY_CFG_DIR . DS . 'Module' . DS . $name . '.json';
      }
   }

   /**
    * 获取APP的配置文件的名称
    * 
    * @param string $module APP模块的名称
    * @param string $name App的名称
    */
   protected static function getAppConfigFilename($module, $name)
   {
      $configPath = implode(DS, array(
         CNTY_CFG_DIR,
         'App',
         $module,
         $name
      ));
      $suffix = file_exists($configPath . '.config.php') ? '.config.php' : '.json';
      return $configPath . $suffix; //暂时硬编码
   }

   /**
    * 将保存之后的数据存入数据库里面
    * 
    * @param string $filename 写入配置信息的文件
    * @param array $data 写入的数组
    */
   protected static function writeBackToFile($filename, array $data)
   {
      //不存在就创建, 同时会创建文件夹
      if (!file_exists($filename)) {
         $dir = dirname($filename);
         if (!file_exists($dir)) {
            Filesystem::createDir($dir, 0750, true);
         }
      }
      //文可以保证存在
      if (!strpos($filename, '.json')) {
         $data = "<?php\nreturn " . var_export($data, true) . ';';
      }

      Filesystem::filePutContents($filename, $data);
   }

   /**
    * @param array $configdir 
    * 
    * @return \Phalcon\Cache\Backend\File
    */
   protected static function getCacher($configdir)
   {
      $key = md5(implode(DS, $configdir));
      if (null == self::$cacher || !key_exists($key, self::$cacher)) {
         self::$cacher[$key] = make_cache_object(implode(DS, $configdir), 7200);
      }
      return self::$cacher[$key];
   }

}