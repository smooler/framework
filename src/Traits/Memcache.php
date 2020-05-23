<?php
namespace Smooler\Traits;

trait Memcache
{
    protected $config_name;

    protected function __construct(){}

    function __get($key) 
    {
        switch ($key) {
            case 'instance':
                global $app;
                $memcache = $app->context->get('memcache_' . $this->config_name);
                if (!$memcache) {
                    $configs = &$app->config->get('memcache.' . $this->config_name);
                    if (!$configs) {
                        throw new \Exception('error config memcache');
                    }
                    $memcache = $app->memcache->handle($configs);
                    $app->context->put('memcache_' . $this->config_name, $memcache);
                }
                return $memcache;
                break;
        }
    }

    function set() 
    {
    }

    function get() 
    {
    }

    function delete() 
    {
    }

    function __callStatic($method, $args)
    {
        global $app;
        $className = get_called_class();
        $obj = $app->singleton->get($className);
        if (!$obj) {
            $obj = new $className();
            $app->singleton->put($className, $obj);
        }
        if (isset($args[0]) && is_string($args[0])) {
            $args[0] = $app->config->get('app.key') . ':' . $args[0];
        }
        return call_user_func_array([$obj->instance, $method], $args);
    }
}