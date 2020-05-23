<?php
namespace Smooler;

class Singleton 
{
    private $singletons;

	function get($className) 
    {
        $singleton = $this->singletons[$className] ?? null;
        if (!$singleton) {
            $singleton = new $className;
            $this->singletons[$className] = $singleton;
        }
        return $singleton;
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
