<?php
namespace Smooler\Exceptions;

use Exception;

class Mysql extends Exception 
{
	protected $code;
	protected $message;

	function __construct ($code, $message = null) 
	{
		$this->code = $code;
		$this->message = $message;
	}

	public function getCode() 
	{
		return $this->code;
	}

	public function getMessage() 
	{
		return $this->message;
	}
}
