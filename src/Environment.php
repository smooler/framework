<?php
namespace Smooler;

class EnvironmentServer 
{
	protected $envs;

	function __construct () 
	{
        $filesnames = __DIR__ . '/../../env.php';
        $this->envs = require_once $filesnames;
	}

	public function get($key, $default = null) {
		return $this->envs[$key] ?? $default;
	}
}
