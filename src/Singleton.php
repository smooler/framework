<?php
namespace Smooler;

class Singleton 
{
    private $singletons;

    function put($className, $obj) 
    {
        $this->singletons[$className] = $obj;
    }

	function get($className) 
    {
        return $this->singletons[$className];
	}

    function delete($className)
    {
        unset($this->singletons[$className]);
    }

    function exist($className) 
    {
        return isset($this->singletons[$className]);
    }

    function clearCache()
    {
        $this->singletons = null;
    }
}
