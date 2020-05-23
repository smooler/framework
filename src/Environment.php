<?php
namespace Smooler;

class EnvironmentServer 
{
	protected $envs;

	function __construct () 
	{
        $filesnames = BASE_DIR . '/env.php';
        $this->envs = require_once $filesnames;
	}

	public function get($key, $default = null) {
		return $this->envs[$key] ?? $default;
	}
}
