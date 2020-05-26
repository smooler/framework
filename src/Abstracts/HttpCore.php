<?php
namespace Smooler\Abstracts;

use Smooler\Singleton;
use Smooler\Context;
use Smooler\Environment;
use Smooler\Constant;
use Smooler\Config;
use Smooler\Lang;
use Smooler\Log;
use Smooler\Exception;
use Smooler\Middleware;
use Smooler\Route;
use Smooler\Controller;
use Smooler\Validation;
use Smooler\Mysql;
use Smooler\Redis;

abstract class HttpCore
{
	protected $server;

	final function __construct() 
	{
		new Constant();
		$this->singleton = new Singleton();
		$this->context = new Context();
		$this->environment = new Environment();
		$this->config = new Config();
		$this->lang = new Lang();
		$this->log = new Log();
		$this->exception = new Exception();
		$this->middleware = new Middleware();
		$this->route = new Route();
		$this->controller = new Controller();
		$this->validate = new Validation();
		$this->mysql = new Mysql();
		$this->redis = new Redis();
	}

	abstract function handleFirstWorkStart();

	final function registerServer($server) 
	{
		$this->server = $server;
	}

	final function handleWorkStart($worker_id) 
	{
		swoole_time_tick(
			1000 * 3600,
			function(){
				$this->singleton->clearCache();
			}
		);
		if (0 == $worker_id) {
			// 第一个进程启动
			try {
				$this->handleFirstWorkStart();
			} catch (\Exception $e) {
				if ($e instanceof ExitException) {
					return;
				}
				$this->exception->handle($e);
			}
		}
    }

	final function handleShutdown() 
	{
        $error = error_get_last();
        var_dump($error);
        switch ($error['type'] ?? null) {
            case E_ERROR :
            case E_PARSE :
            case E_CORE_ERROR :
            case E_COMPILE_ERROR :
				$response = $this->context->get('response');
				$response->status(500);
				$response->header('Content-Type', 'application/json');
				$res = [
					'error' => [
						'code' => 0,
						'message' => $error['message'],
					],
					'data' => null,
				];
				$response->end(json_encode($res));
                $app->log->error($error['message'] . ' ' . $error['file'] . ' ' . $error['line']);
                break;
        }
    }

	final function handle() 
	{
		try {
			$res = $this->middleware->handle();
			if (isset($res['error'])) {
				$this->responseError($res);
				return;
			} 
			$res = $this->route->handle();
			if (isset($res['error'])) {
				$this->responseError($res);
				return;
			} 
			list($controller, $action, $param) = $res;
			$res = $this->controller->handle($controller, $action, $param);

			if (isset($res['error'])) {
				$this->responseError($res);
			} else {
				$this->responseSuccess($res);
			}
		} catch (\Throwable $e) {
			if ($e instanceof \Swoole\ExitException) {
				return;
			}
			$res = $this->exception->handle($e);
			$response = $this->context->get('response');
			$response->status($res['http_code']);
			$response->header('Content-Type', 'application/json');
			$res = [
				'error' => [
					'code' => 0,
					'message' => $res['message'],
				],
				'data' => null,
			];
			$response->end(json_encode($res));
		} 
	}

	final function responseSuccess($res = null) 
	{
		$response = $this->context->get('response');
		$res = [
			'error' => null,
			'data' => $res
		];
		$response->header('Content-Type', 'application/json');
		$response->end(json_encode($res));
	}

	final function responseError($res) 
	{
		$message = '';
		if (isset($res['message']) && $res['message']) {
			$message = $res['message'];
		} else {
			$message = $this->lang->get('error.' . $res['error']);
			if ($message) {
				if (isset($res['params']) && $res['params']) {
					$message = vsprintf($message, $res['params']);
				}
			} else {
				$message = 'unknown';
			}
		}
		$res = [
			'error' => [
				'code' => $res['error'],
				'message' => $message,
			],
			'data' => $res['data'] ?? null,
		];
		$response = $this->context->get('response');
		$response->status(400);
		$response->header('Content-Type', 'application/json');
		$response->end(json_encode($res));
	}

	final function AsyncTask($taskClass, $action, &$param = [], $isWait = false, $timeout = 10) 
	{
		$data['task_class'] = $taskClass;
		$data['action'] = $action;
		$data['param'] = $param;
		if ($isWaiting) {
			return $this->server->taskwait($data, $timeout);
		} else {
			return $this->server->task($data);
		}
	}

	final function handleTask(&$data = []) 
	{
		try {
		    if (isset($data['task_class']) && isset($data['action'])) {
            	$obj = $this->singleton->get($data['task_class'])
		        $res = call_user_func_array([$obj, $data['action']], $data['param'] ?? []);
		        $this->server->finish($res);
		    }
		} catch (\Throwable $e) {
			if ($e instanceof \Swoole\ExitException) {
				return;
			}
			$res = $this->exception->handle($e);
			$res = [
				'error' => 0,
				'message' => $res['message']
			];
	        $this->server->finish($res);
		} 
	}
    
	final function handleTaskShutdown() 
	{
        $error = error_get_last();
        var_dump($error);
        switch ($error['type'] ?? null) {
            case E_ERROR :
            case E_PARSE :
            case E_CORE_ERROR :
            case E_COMPILE_ERROR :
                $this->log->error($error['message'] . ' ' . $error['file'] . ' ' . $error['line']);
				$res = [
					'error' => 0,
					'message' => $error['message']
				];
		        $this->server->finish($res);
                break;
        }
    }
}
