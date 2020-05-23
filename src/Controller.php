<?php
namespace Smooler;

use Smooler\Traits\Singleton;

class Controller 
{
	use Singleton;

	function handle($controller, $action, $param = []) 
	{
		$obj = $this->getSingleton($controller);
		return call_user_func_array(array($obj, $action), $param);
	}
}
