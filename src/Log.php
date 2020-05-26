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
        $logpath = BASE_DIR . '/storage/logs/';
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
	        $env = $app->config->get('app.env', 'production');
	        $taskWorkerNum = $app->config->get('app.task_worker_num');
	        if ('production' == $env && 0 < $taskWorkerNum) {
		        $app->server->AsyncTask(
		            \Smooler\Tasks\Email::class, 
		            'sendLog', 
		            [
		            	'error log',
		                $res
		            ]
		        );
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
