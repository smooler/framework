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
		$invalidation = 0;
		$message = '';
		foreach ($rules as $key => $value) {
			$ruleArr = explode('|', $value);
			if (!isset($data[$key])) {
				if (in_array('required', $ruleArr)) {
					$invalidation = 1;
					$message = $key . ':不能为空！';
					break;
				}
			} else {
				foreach ($ruleArr as $k => $val) {
					switch ($val) {
						case 'string':
							if (!is_string($data[$key])) {
								$invalidation = 1;
								$message = $key . ':必须为字符串！';
								break 2;
							}
							break;
						case 'integer':
							if (!is_int($data[$key])) {
								$invalidation = 1;
								$message = $key . ':必须为整形！';
								break 2;
							}
							break;
                        case 'mobile':
                            if (!preg_match("/^1[345789]\d{9}$/", $data[$key])) {
								$invalidation = 1;
								$message = $key . ':手机号格式错误！';
								break 2;
                            }
                            break;
						case 'numeric':
							if (!is_int($data[$key]) || !is_float($data[$key])) {
								$invalidation = 1;
								$message = $key . ':必须为数字！';
								break 2;
							}
							break;
						case 'array':
							if (!is_array($data[$key])) {
								$invalidation = 1;
								$message = $key . ':必须为数组！';
								break 2;
							} else {
								$keys = array_keys($data[$key]);
								if ($keys != array_keys($keys)) {
									$invalidation = 1;
									$message = $key . ':必须为数组！';
									break 2;
								}
							}
							break;
						case 'map':
							if (!is_array($data[$key])) {
								$invalidation = 1;
								$message = $key . ':必须为对象！';
								break 2;
							} else {
								$keys = array_keys($data[$key]);
								if ($keys == array_keys($keys)) {
									$invalidation = 1;
									$message = $key . ':必须为对象！';
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
											$invalidation = 1;
											$message = $key . ':长度不能小于' . $array[1] . '位！';
											break 3;
										}
										break;
									case 'max':
										if ($array[1] < mb_strlen($data[$key])) {
											$invalidation = 1;
											$message = $key . ':长度不能大于' . $array[1] . '位！';
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
