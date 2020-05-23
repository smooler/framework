<?php
namespace Smooler;

use Swoole\Coroutine;
use Tooler\Core\Traits\Singleton;

class Log
{
	use Singleton;

	protected $filePath;

	function __construct() 
	{
        $logpath = __DIR__ . '/../../storage/logs/';
        $this->filePath = $logpath;
	}

	function write($res, $type = 'info') 
	{
		global $app;
		$time = time();
		$request = $app->context->get('request');
		$filename = $this->filePath . date('Ymd', $time) . '.log';
		$res = '['.$type.'] ' . date('H:i:s', $time) . ' ' . ($request->server["request_method"] ?? 'unknown') . ' ' . ($request->server["request_uri"] ?? 'unknown'). PHP_EOL . $res . PHP_EOL;
		Coroutine::create(function () use ($filename, $res) {
		    Coroutine::writeFile($filename, $res, FILE_APPEND);
		});
		if ('error' == $type) {
	        $rErrorLog = $this->getSingleton(ErrorLogRepository::class);
	        $data = [
	        	'method' => $request->server["request_method"] ?? 'unknown',
	        	'uri' => $request->server["request_uri"] ?? 'unknown',
	        	'client_ip' => $request->header['x-forwarded-for'] ?? ($request->header['x-real-ip'] ?? '127.0.0.1');
	        	'content' => $res,
	        	'created_time' => $time,
	        ];
	        $rErrorLog->insert($data);
	        $env = $app->config->get('app.env', 'production');
	        if ('generation' == $env) {
	        	$sEmail = $this->getSingleton(Email::class);
	        	$sEmail->sendErrorReport($data);
	        }
		}
    }  

	function info($res) 
	{
		$this->write($res, 'info');
    }  

	function debug($res) 
	{
		global $app;
        $env = $app->config->get('app.env', 'production');
        if ('develop' == $env) {
			$this->write($res, 'debug');
        }
    }  

	function error($res) 
	{
		$this->write($res, 'error');
    }  
}
