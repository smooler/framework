<?php
namespace Smooler\Exceptions;

use Exception;

class Http extends Exception 
{
	protected $httpCode;
	protected $message;

	function __construct($httpCode, $message = null) 
	{
		$this->httpCode = $httpCode;
		$this->message = $message;
	}

	final public function getHttpCode() 
	{
		return $this->httpCode;
	}

	final public function getMessage() 
	{
		return $this->message;
	}
}
