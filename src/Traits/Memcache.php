<?php
namespace Smooler\Traits;

trait Memcache
{
    protected $config_name;

    protected function __construct($configName)
    {
        $this->config_name = $configName;
    }

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
        $obj = null;
        if (!$app->singleton->exist($className)) {
            $obj = new $className($this->config_name);
            $app->singleton->put($className, $obj);
        } else {
            $obj = $app->singleton->get($className);
        }
        if (isset($args[0]) && is_string($args[0])) {
            $args[0] = $app->config->get('app.key') . ':' . $args[0];
        }
        return call_user_func_array([$obj->instance, $method], $args);
    }
}