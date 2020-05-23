<?php
namespace Core;

class WebSocket
{
	protected $server;

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

	function registerServer($server) 
	{
		$this->server = $server;
	}
}
