<?php
namespace Smooler;

class Config 
{
	protected $configs;

	function __construct() 
	{
		global $app;
        $routepath = BASE_DIR . '/config/';
        $filesnames = scandir($routepath);
        foreach ($filesnames as $file) {
        	if (is_file($routepath . $file)) {
				$this->configs[substr($file, 0, -4)] = require_once $routepath . $file;
        	}
        }
	}

	public function get($key, $default = null) {
		$keyArr = explode('.', $key);
		$value = $this->configs;
		foreach ($keyArr as $val) {
			if (!isset($value[$val])) {
				return $default;
			}
			$value = $value[$val];
		}
		return $value;
	}
}
