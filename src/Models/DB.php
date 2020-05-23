<?php
namespace App\Models;

use Smooler\Traits\Eloquent;

class DB
{
    use Eloquent;

    protected $config_name = 'base';

	function __get(string $key) 
	{
		switch ($key) {
			case 'table':
				global $app;
				$table = $app->context->get('mysql_' . $this->config_name . '_table');
				if (!$table) {
                    throw new \Exception('error table mysql');
				}
				return $table;
				break;
			default:
				return parent::$key;
				break;

		}
	}

	protected function table(string $table)
	{
		global $app;
		$app->context->put('mysql_' . get_called_class() . '_table', $table);
        return $this;
	}
}
