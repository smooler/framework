<?php
namespace Smooler;

use Swoole\Coroutine\Redis;

class Redis 
{
	function handle(&$configs)
	{
		$redis = new Redis();
		$redis->connect(
		    $configs['host'],
		    $configs['port']
		);
		if ($configs['password']) {
			$redis->auth($configs['password']);
		}
		$redis->select($configs['database']);
		return $redis;
    }  
}
