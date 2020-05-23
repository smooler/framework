<?php
namespace Smooler\Cache;

class Redis
{
	protected $instance;

	function __construct(string $configName)
	{
        global $app;
        $redis = $app->context->get('redis_' . $configName);
        if (!$redis) {
            $configs = &$app->config->get('redis.' . $configName);
            if (!$configs) {
                throw new \Exception('error config redis');
            }
            $redis = $app->redis->handle($configs);
            $app->context->put('redis_' . $configName, $redis);
        }
        $this->instance = $redis;
	}

    function __call($method, $args)
    {
        if (isset($args[0]) && is_string($args[0])) {
            $args[0] = $app->config->get('app.key') . ':' . $args[0];
        }
        return call_user_func_array([$this->instance, $method], $args);
    }
}