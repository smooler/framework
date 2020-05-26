<?php
namespace Smooler\Models;

use Smooler\Traits\Eloquent;

class DB
{
    use Eloquent;

    protected $config_name;
    protected $table;

    function __construct(string $configName)
    {
    	$this->config_name = $configName;
    }

	public function table(string $table)
	{
		$this->table = $table;
        return $this;
	}

    protected function __callStatic(){}
}
