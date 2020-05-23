<?php
namespace Smooler\Exceptions;

use Exception;

class Mysql extends Exception 
{
	protected $code;
	protected $message;

	function __construct($code, $message = null) 
	{
		$this->code = $code;
		$this->message = $message;
	}

	final public function getCode() 
	{
		return $this->code;
	}

	final public function getMessage() 
	{
		return $this->message;
	}
}
