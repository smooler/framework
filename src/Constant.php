<?php
namespace Smooler;

class Constant 
{
    function __construct() 
	{
		global $app;
        $routepath = __DIR__ . '/../../constant/';
        $filesnames = scandir($routepath);
        foreach ($filesnames as $file) {
        	if (is_file($routepath . $file)) {
				require_once $routepath . $file;
        	}
        }
	}
}
