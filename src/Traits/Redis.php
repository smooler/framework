<?php
namespace Smooler\Traits;

trait Redis
{
	protected $config_name;

    protected function __construct(){}

	function __get(string $key) 
	{
        switch ($key) {
            case 'instance':
                global $app;
                $redis = $app->context->get('redis_' . $this->config_name);
                if (!$redis) {
                    $configs = &$app->config->get('redis.' . $this->config_name);
                    if (!$configs) {
                        throw new \Exception('error config redis');
                    }
                    $redis = $app->redis->handle($configs);
                    $app->context->put('redis_' . $this->config_name, $redis);
                }
                return $redis;
                break;
        }
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