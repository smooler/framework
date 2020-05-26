<?php
namespace Smooler;

use Smooler\Exceptions\Http;

class Validation 
{
	function handle($rules) 
	{
		global $app;
		$data = &$app->context->getData();
		$this->validate($data, $rules);
	}
	
	function validate($data, $rules) 
	{
		global $app;
		$invalidation = false;
		$message = '';
		foreach ($rules as $key => $value) {
			$ruleArr = explode('|', $value);
			if (!isset($data[$key])) {
				if (in_array('required', $ruleArr)) {
					$invalidation = true;
					$message = $app->lang->get('validation.required'); 
					if ($message) {
						$message = sprintf($message, $key);
					}
					break;
				}
			} else {
				foreach ($ruleArr as $k => $val) {
					switch ($val) {
						case 'string':
							if (!is_string($data[$key])) {
								$invalidation = true;
								$message = $app->lang->get('validation.string'); 
								if ($message) {
									$message = sprintf($message, $key);
								}
								break 2;
							}
							break;
						case 'integer':
							if (!is_int($data[$key])) {
								$invalidation = true;
								$message = $app->lang->get('validation.integer'); 
								if ($message) {
									$message = sprintf($message, $key);
								}
								break 2;
							}
							break;
                        case 'mobile':
                            if (!preg_match("/^1[345789]\d{9}$/", $data[$key])) {
								$invalidation = true;
								$message = $app->lang->get('validation.mobile'); 
								if ($message) {
									$message = sprintf($message, $key);
								}
								break 2;
                            }
                            break;
						case 'numeric':
							if (!is_int($data[$key]) || !is_float($data[$key])) {
								$invalidation = true;
								$message = $app->lang->get('validation.numeric'); 
								if ($message) {
									$message = sprintf($message, $key);
								}
								break 2;
							}
							break;
						case 'array':
							if (!is_array($data[$key])) {
								$invalidation = true;
								$message = $app->lang->get('validation.array'); 
								if ($message) {
									$message = sprintf($message, $key);
								}
								break 2;
							} else {
								$keys = array_keys($data[$key]);
								if ($keys != array_keys($keys)) {
									$invalidation = true;
									$message = $app->lang->get('validation.array'); 
									if ($message) {
										$message = sprintf($message, $key);
									}
									break 2;
								}
							}
							break;
						case 'map':
							if (!is_array($data[$key])) {
								$invalidation = true;
								$message = $app->lang->get('validation.map'); 
								if ($message) {
									$message = sprintf($message, $key);
								}
								break 2;
							} else {
								$keys = array_keys($data[$key]);
								if ($keys == array_keys($keys)) {
									$invalidation = true;
									$message = $app->lang->get('validation.map'); 
									if ($message) {
										$message = sprintf($message, $key);
									}
									break 2;
								}
							}
							break;
						default:
							$array = explode(':', $val);
							if ($array && 2 == count($array)) {
								switch ($array[0]) {
									case 'min':
										if ($array[1] > mb_strlen($data[$key])) {
											$invalidation = true;
											$message = $app->lang->get('validation.min'); 
											if ($message) {
												$message = sprintf($message, $key, $array[1]);
											}
											break 3;
										}
										break;
									case 'max':
										if ($array[1] < mb_strlen($data[$key])) {
											$invalidation = true;
											$message = $app->lang->get('validation.max'); 
											if ($message) {
												$message = sprintf($message, $key, $array[1]);
											}
											break 3;
										}
										break;
								}
							}
							break;
					}
				}
			}
		}
		if ($invalidation) {
            throw new Http(483, $message);
		}
	}
}
