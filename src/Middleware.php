<?php
namespace Smooler;

use Smooler\Traits\Singleton;

class Middleware 
{
	use Singleton;

	public function handle() 
	{
		global $app;
		foreach ($app->initMiddlewares as $value) {
			$res = $this->getSingleton($value)->handle();
			return $res;
		}
	}
}
