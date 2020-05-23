<?php
namespace Smooler;

class Lang 
{
	protected $langs;

	function __construct() 
	{
		global $app;
        $lang = $app->config->get('app.lang');
        if (!$lang) {
        	throw new \Exception("Error config lang");
        }

        $path = __DIR__ . '/../../lang/' . $lang . '/';
        $filesnames = scandir($path);
        foreach ($filesnames as $file) {
        	if (is_file($path . $file)) {
				$this->langs[substr($file, 0, -4)] = require_once $path . $file;
        	}
        }
	}

	public function get($key) 
	{
		$keyArr = explode('.', $key);
		$value = $this->langs;
		foreach ($keyArr as $val) {
			if (!isset($value[$val])) {
				return;
			}
			$value = $value[$val];
		}
		return $value;
	}
}
