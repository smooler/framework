<?php
namespace Smooler\Traits;

use Smooler\Exceptions\Mysql;

// escape only where and data
// 只允许where和data通过tcp传参，防止sql注入
trait Eloquent
{
	protected $config_name;

	protected function __construct(){}

	function __get(string $key) 
	{
		switch ($key) {
			case 'write':
				global $app;
				$mysql = $app->context->get('mysql_' . $this->config_name . '_write');
				if (!$mysql) {
	            	$configs = &$app->config->get('mysql.' . $this->config_name . '.write');
			        if (!$configs) {
                        throw new \Exception('error config mysql');
			        }
		            $mysql = $app->mysql->handle($configs);
		            $app->context->put('mysql_' . $this->config_name . '_write', $mysql);
				}
				return $mysql;
				break;
			case 'read':
				global $app;
				$mysql = $app->context->get('mysql_' . $this->config_name . '_read');
				if (!$mysql) {
	            	$configs = &$app->config->get('mysql.' . $this->config_name . '.read');
	            	if (!$configs) {
						return $this->write;
	            	}
		            $mysql = $app->mysql->handle($configs);
		            $app->context->put('mysql_' . $this->config_name . '_read', $mysql);
				}
				return $mysql;
				break;
		}
	}

	final public function where(array &$where) 
	{
		global $app;
		$app->context->put('mysql_' . get_called_class() . '_whereArray', $where);
		return $this;
	}

	final public function alias(string $alias) 
	{
		global $app;
		$app->context->put('mysql_' . get_called_class() . '_alias', $alias);
		return $this;
	}

	final public function join(array $join) 
	{
		global $app;
		$app->context->push('mysql_' . get_called_class() . '_joinArray', ' INNER JOIN ' . $join);
		return $this;
	}

	final public function leftjoin(array $join) 
	{
		global $app;
		$app->context->push('mysql_' . get_called_class() . '_joinArray', ' LEFT JOIN ' . $join);
		return $this;
	}

	final public function rightjoin(array $join) 
	{
		global $app;
		$app->context->push('mysql_' . get_called_class() . '_joinArray', ' RIGHT JOIN ' . $join);
		return $this;
	}

	final public function on(string $on) 
	{
		global $app;
		$app->context->put('mysql_' . get_called_class() . '_on', $on);
		return $this;
	}

	public function limit(int $limit) 
	{
		global $app;
		$app->context->put('mysql_' . get_called_class() . '_limit', $limit);
		return $this;
	}

	public function offset(int $offset) 
	{
		global $app;
		$app->context->put('mysql_' . get_called_class() . '_offset', $limit);
		return $this;
	}

	public function order(string $order) 
	{
		global $app;
		$app->context->put('mysql_' . get_called_class() . '_order', $order);
		return $this;
	}

	public function group(string $group) 
	{
		global $app;
		$app->context->put('mysql_' . get_called_class() . '_group', $group);
		return $this;
	}

	public function master() 
	{
		global $app;
		$app->context->put('mysql_' . get_called_class() . '_master', true);
		return $this;
	}

	protected function clear() 
	{
		global $app;
		$app->context->delete('mysql_' . get_called_class() . '_whereArray');
		$app->context->delete('mysql_' . get_called_class() . '_alias');
		$app->context->delete('mysql_' . get_called_class() . '_joinArray');
		$app->context->delete('mysql_' . get_called_class() . '_on');
		$app->context->delete('mysql_' . get_called_class() . '_limit');
		$app->context->delete('mysql_' . get_called_class() . '_offset');
		$app->context->delete('mysql_' . get_called_class() . '_order');
		$app->context->delete('mysql_' . get_called_class() . '_group');
	}

	protected function whereStr(array &$where, boolean $isMaster = false) 
	{
		$whereStr = '';
		foreach ($where as $value) {
			if (!in_array($value[1], ['=', '>', '>=', '<', '<=', 'in', 'like', 'is', 'is not'])) {
				throw new Mysql(0, 'sql compare error :' . $value[0]);
			}
			switch ($value[1]) {
				case 'in':
					if (is_array($value[2])) {
						$valueTemp = '';
						foreach ($value[2] as $val) {
							if (is_string($val)) {
								$val = '"' . $this->escape($val, $isMaster) . '"';
							} elseif (!is_int($val) && !is_float($val)) {
								throw new Mysql(0, 'sql value error :' . $value[0]);
							}
							if (!$valueTemp) {
								$valueTemp .= '(' . $val;
							} else {
								$valueTemp .= ',' . $val;
							}
						}
						if ($valueTemp) {
							$valueTemp .= ')';
						}
						$value[2] = $valueTemp;
					} else {
						throw new Mysql(0, 'sql value error :' . $value[0]);
					}
					break;
				default:
					if (is_string($value[2])) {
						$value[2] = '"' . $this->escape($value[2], $isMaster) . '"';
					} elseif (is_null($value[2])) {
						$value[2] = 'null';
					} elseif (is_array($value[2]) && isset($value[2]['raw']) && is_string($value[2]['raw'])) {
						$value[2] = $this->escape($value[2]['raw'], $isMaster);
					} elseif (!is_int($value[2]) && !is_float($value[2])) {
						throw new Mysql(0, 'sql value error :' . $value[0]);
					}
					break;
			}
			if (!$whereStr) {
				$whereStr .= $value[0] . ' ' . $value[1] . ' ' . $value[2];
			} else {
				$whereStr .= ' AND ' . $value[0] . ' ' . $value[1] . ' ' . $value[2];
			}
		}
		return $whereStr;
	}

	protected function valueStr(array &$data) 
	{
		$valueStr = '';
		foreach ($data as $key => $value) {
			if (!$collumnStr) {
				$collumnStr .= $key;
			} else {
				$collumnStr .= ', ' . $key;
			}
			if (is_string($value)) {
				$value = '"' . $this->escape($value, true) . '"';
			} elseif (is_null($value)) {
				$value = 'null';
			} elseif (!is_int($value) && !is_float($value)) {
				throw new Mysql(0, 'sql value error :' . $key);
			}
			if (!$valueStr) {
				$valueStr .= $value;
			} else {
				$valueStr .= ', ' . $value;
			}
		}
		return $valueStr;
	}

	public function insert(array &$data, boolean $ignoreDuplicate = false) 
	{
		if ($data) {
			$query = 'INSERT INTO ' . $this->table  . ' ';
			$collumnStr = '';
			$valueStr = $this->valueStr($data);
			if (!$valueStr) {
				throw new Mysql(0, 'sql value string error');
			}
			$query = $query . '(' . $collumnStr . ')' . 'VALUES(' . $valueStr . ')';
			if ($ignoreDuplicate) {
				$query .= ' ON DUPLICATE KEY UPDATE created_time = created_time';
			}
			$this->query($query, true);
			return $this->write->affected_rows;
		}
	}

	public function update(array &$data, boolean $ignoreDuplicate = false) 
	{
		global $app;
		$wehereArray = &$app->context->get('mysql_' . get_called_class() . '_whereArray');
		if ($wehereArray && $data) {
			$alias = $app->context->get('mysql_' . get_called_class() . '_alias');
			if ($alias) {
				$alias = ' ' . $alias;
			}
			$joinStr = '';
			$joinArray = &$app->context->get('mysql_' . get_called_class() . '_joinArray');
			$joinArray && $joinStr = implode('', $joinArray);
			$onStr = $app->context->get('mysql_' . get_called_class() . '_on');
			if ($onStr) {
				$onStr = ' ' . $onStr;
			}
			$valueStr = $this->valueStr($data);
			if (!$valueStr) {
				throw new Mysql(0, 'sql value string error');
			}
			$whereStr = $this->whereStr($wehereArray, true);
			if (!$whereStr) {
				throw new Mysql(0, 'sql where string error');
			}
			$whereStr = ' WHERE ' . $whereStr;
			$query = 'UPDATE ' . $this->table . $alias . $joinStr . $onStr . ' SET ' . $valueStr . $whereStr;
			if ($ignoreDuplicate) {
				$query .= ' ON DUPLICATE KEY UPDATE created_time = created_time';
			}
			$this->query($query, true);
			return $this->write->affected_rows;
		}
	}

	public function delete() 
	{
		global $app;
		$wehereArray = &$app->context->get('mysql_' . get_called_class() . '_whereArray');
		if ($wehereArray && $data) {
			$whereStr = $this->whereStr($wehereArray, true);
			if (!$whereStr) {
				throw new Mysql(0, 'sql where string error');
			}
			$whereStr = ' WHERE ' . $whereStr;
			$query = 'DELETE FROM ' . $this->table . $whereStr;
			$this->query($query, true);
			return $this->write->affected_rows;
		}
	}

	public function increase(string $collumn, float $number = 1) 
	{
		global $app;
		$wehereArray = &$app->context->get('mysql_' . get_called_class() . '_whereArray');
		if ($wehereArray) {
			$alias = $app->context->get('mysql_' . get_called_class() . '_alias');
			if ($alias) {
				$alias = ' ' . $alias;
			}
			$joinStr = '';
			$joinArray = &$app->context->get('mysql_' . get_called_class() . '_joinArray');
			$joinArray && $joinStr = implode('', $joinArray);
			$onStr = $app->context->get('mysql_' . get_called_class() . '_on');
			if ($onStr) {
				$onStr = ' ' . $onStr;
			}
			$whereStr = $this->whereStr($wehereArray, true);
			if (!$whereStr) {
				throw new Mysql(0, 'sql where string error');
			}
			$whereStr = ' WHERE ' . $whereStr;
			$query = 'UPDATE ' . $this->table . $alias . $joinStr . $onStr . ' SET ' . $collumn . ' = ' . $collumn . ' + ' . $number . $whereStr;
			$this->query($query, true);
			return $this->write->affected_rows;
		}
	}

	public function decrease(string $collumn, float $number = 1) 
	{
		global $app;
		$wehereArray = &$app->context->get('mysql_' . get_called_class() . '_whereArray');
		if ($wehereArray) {
			$alias = $app->context->get('mysql_' . get_called_class() . '_alias');
			if ($alias) {
				$alias = ' ' . $alias;
			}
			$joinStr = '';
			$joinArray = &$app->context->get('mysql_' . get_called_class() . '_joinArray');
			$joinArray && $joinStr = implode('', $joinArray);
			$onStr = $app->context->get('mysql_' . get_called_class() . '_on');
			if ($onStr) {
				$onStr = ' ' . $onStr;
			}
			$whereStr = $this->whereStr($wehereArray, true);
			if (!$whereStr) {
				throw new Mysql(0, 'sql where string error');
			}
			$whereStr = ' WHERE ' . $whereStr;
			$query = 'UPDATE ' . $this->table . $alias . $joinStr . $onStr . ' SET ' . $collumn . ' = ' . $collumn . ' - ' . $number . $whereStr;
			$this->query($query, true);
			return $this->write->affected_rows;
		}
	}

	public function find(string ...$collumns) 
	{
		$this->limit(1);
		$res = $this->get(...$collumns);
		return $res ? $res[0] : null;
	}

	public function get(string ...$collumns) 
	{
		if ($collumns && !in_array('*', $collumns)) {
			global $app;
			$isMaster = $app->context->get('mysql_' . get_called_class() . '_master');
			$collumnStr = '';
			foreach ($collumns as $value) {
				if (!$collumnStr) {
					$collumnStr .= $value;
				} else {
					$collumnStr .= ', ' . $value;
				}
			}
			$alias = $app->context->get('mysql_' . get_called_class() . '_alias');
			if ($alias) {
				$alias = ' ' . $alias;
			}
			$joinStr = '';
			$joinArray = &$app->context->get('mysql_' . get_called_class() . '_joinArray');
			$joinArray && $joinStr = implode('', $joinArray);
			$onStr = $app->context->get('mysql_' . get_called_class() . '_on');
			if ($onStr) {
				$onStr = ' ' . $onStr;
			}
			$whereStr = '';
			$wehereArray = &$app->context->get('mysql_' . get_called_class() . '_whereArray');
			if ($wehereArray) {
				$whereStr = $this->whereStr($wehereArray, $isMaster);
			}
			if ($whereStr) {
				$whereStr = ' WHERE ' . $whereStr;
			}
			$orderStr = $app->context->get('mysql_' . get_called_class() . '_order');
			if ($orderStr) {
				$orderStr = ' ORDER BY ' . $orderStr;
			}
			$groupStr = $app->context->get('mysql_' . get_called_class() . '_group');
			if ($groupStr) {
				$groupStr = ' GROUP BY ' . $groupStr;
			}
			$limitStr = $app->context->get('mysql_' . get_called_class() . '_limit');
			if ($limitStr) {
				$limitStr = ' LIMIT ' . $limitStr;
			}
			$offsetStr = $app->context->get('mysql_' . get_called_class() . '_offset');
			if ($offsetStr) {
				$offsetStr = ' OFFSET ' . $offsetStr;
			}
			$query = 'SELECT ' . $collumnStr . ' FROM ' . $this->table . $alias . $joinStr . $onStr . $whereStr . $groupStr . $orderStr . $limitStr . $offsetStr;
			$res = $this->query($query, $isMaster);
			return $res ? $res : [];
		}
	}

	public function value(string $collumn) 
	{
		$res = $this->find($collumn);
		if ($res) {
			return $res[$collumn];
		}
	}

	public function count() 
	{
		global $app;
		$isMaster = $app->context->get('mysql_' . get_called_class() . '_master');
		$alias = $app->context->get('mysql_' . get_called_class() . '_alias');
		if ($alias) {
			$alias = ' ' . $alias;
		}
		$joinStr = '';
		$joinArray = &$app->context->get('mysql_' . get_called_class() . '_joinArray');
		$joinArray && $joinStr = implode('', $joinArray);
		$onStr = $app->context->get('mysql_' . get_called_class() . '_on');
		if ($onStr) {
			$onStr = ' ' . $onStr;
		}
		$whereStr = '';
		$wehereArray = &$app->context->get('mysql_' . get_called_class() . '_whereArray');
		if ($wehereArray) {
			$whereStr = $this->whereStr($wehereArray, $isMaster);
		}
		if ($whereStr) {
			$whereStr = ' WHERE ' . $whereStr;
		}
		$query = 'SELECT count(1) as count FROM ' . $this->table . $alias . $joinStr . $onStr . $whereStr;
		$res = $this->query($query, $isMaster);
		if ($res) {
			return $res[0]['count'];
		} else {
			throw new Mysql(0, 'sql count error');
		}
	}

	public function avg(string $collumn) 
	{
		global $app;
		$isMaster = $app->context->get('mysql_' . get_called_class() . '_master');
		$alias = $app->context->get('mysql_' . get_called_class() . '_alias');
		if ($alias) {
			$alias = ' ' . $alias;
		}
		$joinStr = '';
		$joinArray = &$app->context->get('mysql_' . get_called_class() . '_joinArray');
		$joinArray && $joinStr = implode('', $joinArray);
		$onStr = $app->context->get('mysql_' . get_called_class() . '_on');
		if ($onStr) {
			$onStr = ' ' . $onStr;
		}
		$whereStr = '';
		$wehereArray = &$app->context->get('mysql_' . get_called_class() . '_whereArray');
		if ($wehereArray) {
			$whereStr = $this->whereStr($wehereArray, $isMaster);
		}
		if ($whereStr) {
			$whereStr = ' WHERE ' . $whereStr;
		}
		$query = 'SELECT avg(' . $collumn . ') as avg FROM ' . $this->table . $alias . $joinStr . $onStr . $whereStr;
		$res = $this->query($query, $isMaster);
		if ($res) {
			return $res[0]['avg'];
		} else {
			throw new Mysql(0, 'sql avg error');
		}
	}

	public function min(string $collumn) 
	{
		global $app;
		$isMaster = $app->context->get('mysql_' . get_called_class() . '_master');
		$alias = $app->context->get('mysql_' . get_called_class() . '_alias');
		if ($alias) {
			$alias = ' ' . $alias;
		}
		$joinStr = '';
		$joinArray = &$app->context->get('mysql_' . get_called_class() . '_joinArray');
		$joinArray && $joinStr = implode('', $joinArray);
		$onStr = $app->context->get('mysql_' . get_called_class() . '_on');
		if ($onStr) {
			$onStr = ' ' . $onStr;
		}
		$whereStr = '';
		$wehereArray = &$app->context->get('mysql_' . get_called_class() . '_whereArray');
		if ($wehereArray) {
			$whereStr = $this->whereStr($wehereArray, $isMaster);
		}
		if ($whereStr) {
			$whereStr = ' WHERE ' . $whereStr;
		}
		$query = 'SELECT min(' . $collumn . ') as min FROM ' . $this->table . $alias . $joinStr . $onStr . $whereStr;
		$res = $this->query($query, $isMaster);
		if ($res) {
			return $res[0]['min'];
		} else {
			throw new Mysql(0, 'sql min error');
		}
	}

	public function max(string $collumn) 
	{
		global $app;
		$isMaster = $app->context->get('mysql_' . get_called_class() . '_master');
		$alias = $app->context->get('mysql_' . get_called_class() . '_alias');
		if ($alias) {
			$alias = ' ' . $alias;
		}
		$joinStr = '';
		$joinArray = &$app->context->get('mysql_' . get_called_class() . '_joinArray');
		$joinArray && $joinStr = implode('', $joinArray);
		$onStr = $app->context->get('mysql_' . get_called_class() . '_on');
		if ($onStr) {
			$onStr = ' ' . $onStr;
		}
		$whereStr = '';
		$wehereArray = &$app->context->get('mysql_' . get_called_class() . '_whereArray');
		if ($wehereArray) {
			$whereStr = $this->whereStr($wehereArray, $isMaster);
		}
		if ($whereStr) {
			$whereStr = ' WHERE ' . $whereStr;
		}
		$query = 'SELECT max(' . $collumn . ') as max FROM ' . $this->table . $alias . $joinStr . $onStr . $whereStr;
		$res = $this->query($query, $isMaster);
		if ($res) {
			return $res[0]['max'];
		} else {
			throw new Mysql(0, 'sql max error');
		}
	}

	public function sum(string $collumn) 
	{
		global $app;
		$isMaster = $app->context->get('mysql_' . get_called_class() . '_master');
		$alias = $app->context->get('mysql_' . get_called_class() . '_alias');
		if ($alias) {
			$alias = ' ' . $alias;
		}
		$joinStr = '';
		$joinArray = &$app->context->get('mysql_' . get_called_class() . '_joinArray');
		$joinArray && $joinStr = implode('', $joinArray);
		$onStr = $app->context->get('mysql_' . get_called_class() . '_on');
		if ($onStr) {
			$onStr = ' ' . $onStr;
		}
		$whereStr = '';
		$wehereArray = &$app->context->get('mysql_' . get_called_class() . '_whereArray');
		if ($wehereArray) {
			$whereStr = $this->whereStr($wehereArray, $isMaster);
		}
		if ($whereStr) {
			$whereStr = ' WHERE ' . $whereStr;
		}
		$query = 'SELECT sum(' . $collumn . ') as sum FROM ' . $this->table . $alias . $joinStr . $onStr . $whereStr;
		$res = $this->query($query, $isMaster);
		if ($res) {
			return $res[0]['sum'];
		} else {
			throw new Mysql(0, 'sql sum error');
		}
	}

	public function query(string $query, boolean $isMaster = false, boolean $repeat = false) 
	{
		$this->clear();
		if ($isMaster) {
			$res = $this->write->query($query);
		} else {
			$res = $this->read->query($query);
		}
		if (false === $res) {
			var_dump($query);
	        if (2006 == ($isMaster ? $this->write->errno : $this->read->errno) || 2013 == ($isMaster ? $this->write->errno : $this->read->errno)) {
				var_dump('mysql close');
				if (!$repeat) {
					global $app;
		        	if ($isMaster) {
		            	$configs = &$app->config->get('mysql.' . $this->config_name . '.write');
			            $mysql = $app->mysql->handle($configs);
			            $app->context->put('mysql_' . $this->config_name . '_write', $mysql);
		        	} else {
		            	$configs = &$app->config->get('mysql.' . $this->config_name . '.read');
		            	if (!$configs) {
			            	$configs = &$app->config->get('mysql.' . $this->config_name . '.write');
				            $mysql = $app->mysql->handle($configs);
				            $app->context->put('mysql_' . $this->config_name . '_write', $mysql);
		            	} else {
				            $mysql = $app->mysql->handle($configs);
				            $app->context->put('mysql_' . $this->config_name . '_read', $mysql);
		            	}
		        	}
		        	return $this->query($query, $isMaster, true); 
				}
	        }
			throw new Mysql(($isMaster ? $this->write->errno : $this->read->errno) ?? 0, ($isMaster ? $this->write->error : $this->read->error) ?? '未知错误');
		}
		return $res;
	}

	public function begin() 
	{
		$this->write->begin();
	}

	public function commit() 
	{
		$this->write->commit();
	}

	public function rollback() 
	{
		$this->write->rollback();
	}

	public function lastInsertId() 
	{
		return $this->write->insert_id ?? 0;
	}
	
	public function escape(string $str, boolean $isMaster = false) 
	{
		if ($isMaster) {
			return $this->write->escape($str);
		} else {
			return $this->read->escape($str);
		}
	}

	function __callStatic($method, $args)
	{
		global $app;
		$className = get_called_class();
		$obj = $app->singleton->get($className);
		if (!$obj) {
			$obj = new $className();
			$app->singleton->put($className, $obj);
		}
		return call_user_func_array([$obj, $method], $args);
	}
}
