<?php
namespace Smooler;

class ScriptApp
{
	function __construct() 
	{
		$this->context = new Context();
		$this->environment = new Environment();
		$this->config = new Config();
		$this->lang = new Lang();
		$this->log = new Log();
		$this->exception = new Exception();
		$this->mysql = new Mysql();
		$this->redis = new Redis();
	}
}
