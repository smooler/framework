<?php
namespace Smooler\Traits;

trait Singleton
{
    protected function getSingleton($className) 
    {
        global $app;
        return $app->singleton->get($className);
    }
}
			