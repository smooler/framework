<?php
namespace Smooler\Traits;

trait Singleton
{
    protected function getSingleton($className) 
    {
        global $app;
        $singleton = $app->singleton->get($className);
        if (!$singleton) {
            $singleton = new $className;
            $app->singleton->put($className, $singleton);
        }
        return $singleton;
    }
}
			