<?php

namespace Utils;

class App
{
    private static $app;

    public static function setApp($app)
    {
        static::$app = $app;
    }

    /**
     * @throws \Exception
     */
    public static function getApp()
    {
        if (!static::$app) {
            throw new \Exception( 'app not found' );
        }

        return static::$app;
    }

    /**
     * @throws \Exception
     */
    public static function getContainer()
    {
        return (static::getApp())->getContainer();
    }

    /**
     * @param $alias
     * @param $class
     * @param array $params:构造函数的参数
     * @return mixed
     * @throws \Exception
     * @note 容器对象
     */
    public  static function make($alias , $class , array $params = [])
    {
        $container = static::getContainer();

        if(isset($container[$alias])){
            return $container[$alias] ;
        }

        if($class instanceof \Closure){
            $container[$alias] = $class();
            return $container[$alias];
        }

        if(class_exists($class)){
            $container[$alias] = new $class(... $params);
            return $container[$alias];
        }

        throw  new  \Exception('failed to make');
    }
}