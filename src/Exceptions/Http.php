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

	public function getHttpCode() 
	{
		return $this->httpCode;
	}

	public function getMessage() 
	{
		return $this->message;
	}
}
