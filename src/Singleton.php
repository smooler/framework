<?php
namespace Smooler;

class Singleton 
{
    private $singletons;

	function get($className) 
    {
        return $singleton = $this->singletons[$className];
	}

    function delete($className)
    {
        unset($this->singletons[$className]);
    }

    function exist($className) 
    {
        return isset($this->singletons[$className]);
    }

    function put($className, $obj) 
    {
        $this->singletons[$className] = $obj;
    }

    function clearCache()
    {
        $this->singletons = null;
    }
}
