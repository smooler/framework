<?php
namespace Smooler;

use Swoole\Coroutine\MySQL;

class Mysql 
{
	function handle(&$configs)
	{
		$mysql = new Swoole\Coroutine\MySQL();
		$mysql->connect([
		    'host' => $configs['host'],
		    'port' => $configs['port'],
		    'user' => $configs['user'],
		    'password' => $configs['password'],
		    'database' => $configs['database'],
		    'strict_type' => true,
    		'charset' => 'utf8mb4',
		]);
		return $mysql;
    }  
}
