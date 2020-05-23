<?php
namespace Smooler;

class Middleware 
{
	protected $middlewares = [
		'auth' => \App\Middlewares\Auth::class,
		'sign' => \App\Middlewares\ValidateSignature::class,
	];

	protected $initMiddlewares = [
		'access_control_allow' => \App\Middlewares\AccessControlAllow::class,
	];

	public function handle() 
	{
		global $app;
		foreach ($this->initMiddlewares as $value) {
			$res = $app->singleton->get($value)->handle();
			return $res;
		}
	}

	public function getMiddlewareIoc($key) 
	{
		global $app;
		return $app->singleton->get($key)->handle();
	}
}
