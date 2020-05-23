<?php
namespace Smooler;

class Controller 
{
	function handle($controller, $action, $param = []) 
	{
		global $app;
		$obj = $app->singleton->get($controller);
		return call_user_func_array(array($obj, $action), $param);
	}
}
