<?php
/**
 * Cntysoft Cloud Software Team
 *
 * @author SOFTBOY <cntysoft@163.com>
 * @copyright  Copyright (c) 2010-2011 Cntysoft Technologies China Inc. <http://www.cntysoft.com>
 * @license    http://www.cntysoft.com/license/new-bsd     New BSD License
 */
namespace Qs\Lib;
use Cntysoft\Kernel\StdHtmlPath;
use Cntysoft\Framework\Qs\Utils;
class Sys
{
   /**
    * 加载模板JS文件
    *
    * 调用方式
    * <code>Qs::Sys('loadJs', 'filename', 'path/to/file');</code> or
    * <code>Qs::Sys('loadJs', array('filename1', 'filename2'), 'path/to/file');</code>
    * @param string $file
    * @param string $basepath
    * @return string
    */
   public static function loadJs($file, $basepath = null)
   {
      $basepath = $basepath ? $basepath : StdHtmlPath::getSkinPath();
      return Utils::generateJsScriptTag($basepath, $file);
   }

   /**
    * 加载前台共用Js文件
    *
    * @param $file
    * @param null $basepath
    * @return string
    */
   public static  function loadPubJs($file, $basepath = null)
   {
      $basepath = $basepath ? $basepath : StdHtmlPath::getFrontJsLibPath();
      return Utils::generateJsScriptTag($basepath, $file);
   }

   /**
    * 加载指定版本的Jquery文件
    *
    * 调用方式 <code>Qs::Sys('loadJquery', array('version');</code>
    * @param array $file
    * @return string
    */
   public static function loadJquery($file)
   {
      $base = StdHtmlPath::getJsPath();
      return Utils::generateJsScriptTag($base, sprintf('Jquery/jquery-%s.min.js', $file[0]));
   }
   /**
    * 加载用户中心相关的js
    *
    * @param string $filename
    * @return string
    */
   public static function loadUcJs($filename)
   {
      return self::loadJs($filename, '/Modules/User/Ui/Js');
   }

   /**
    * 加载用户中心指定的Css文件
    *
    * @param string $filename
    * @return string
    */
   public static function loadUcCss($filename)
   {
      return self::loadCss($filename, '/Modules/User/Ui/Css');
   }

   /**
    * 加载指定路径的图片
    * 
    * @param array $file
    * @return string
    */
   public static function loadImage($file)
   {
      $basepath = StdHtmlPath::getSkinPath();
      return $basepath . '/' . $file[0];
   }
   /**
    * 加载Css文件
    *
    * 调用方式
    * <code>Qs::Sys('loadCss', 'filename', 'path/to/file');</code> Or
    * <code>Qs::Sys('loadCss', array('filename1', 'filename2'), 'path/to/file');</code>
    * @param string | array $file
    * @param string $basePath
    */
   public static function loadCss($file, $basePath = null)
   {
      $basePath = $basePath ? $basePath : StdHtmlPath::getSkinPath();
      return Utils::generateCssLinkTag($basePath, $file);
   }


   /**
    * 加载软件版本信息
    *
    * 调用方式
    * <code>
    *      Qs::Sys('softInfo');
    * </code>
    * @return string
    */
   public static function softInfo()
   {
      return  '网站基于'.OPEN_ENGINE_NAME.'_'.OPEN_ENGINE_PRODUCT_NAME.' 当前版本为 '.OPEN_ENGINE_VERSION.'_'.OPEN_ENGINE_BUILD.'_'.OPEN_ENGINE_RELEASE;
   }
}