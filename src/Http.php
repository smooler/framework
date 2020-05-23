<?php
namespace App;

class Http
{
	protected $server;

	function __construct() 
	{
		$this->singleton = new Singleton();
		$this->context = new Context();
		$this->environment = new Environment();
		$this->constant = new Constant();
		$this->config = new Config();
		$this->lang = new Lang();
		$this->log = new Log();
		$this->exception = new Exception();
		$this->middleware = new Middleware();
		$this->route = new Route();
		$this->controller = new Controller();
		$this->validate = new Validation();
		$this->mysql = new Mysql();
		$this->redis = new Redis();
	}

	function registerServer($server) 
	{
		$this->server = $server;
	}
}
