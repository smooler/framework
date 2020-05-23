<?php
namespace Smooler;

use Smooler\Exceptions\Http;
use Smooler\Exceptions\Mysql;

class ExceptionServer 
{
	protected $ignores = [
		Http::class
	];

	function handle(\Throwable $e) 
	{
		$className = get_class($e);
		if (!in_array($className, $this->ignores)) {
			global $app;
			$message = $e->getMessage();
			$app->log->error($message . ' ' . $e->getFile() . ' ' . $e->getLine() . PHP_EOL . substr($e->getTraceAsString(), 0, 1024));
		}
		$httpCode = 500;
		$message = $className;
		if ($e instanceof \Exception) {
			if ($e instanceof Http) {
				$httpCode = $e->getHttpCode();
				$message = $e->getMessage();
			}
			if ($e instanceof Mysql) {
				$message = 'sql error code:' . $e->getCode();
			}
		}
		return [
			'http_code' => $httpCode,
			'message' => $message,
		];
	}
}
