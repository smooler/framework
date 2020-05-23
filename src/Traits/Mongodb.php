<?php
namespace Smooler\Traits;

trait Mongodb
{
    protected $config_name;

    protected function __construct($configName)
    {
        $this->config_name = $configName;
    }

    function __get($key) 
    {
        switch ($key) {
            case 'instance':
                global $app;
                $mongodb = $app->context->get('mongodb_' . $this->config_name);
                if (!$mongodb) {
                    $configs = &$app->config->get('mongodb.' . $this->config_name);
                    if (!$configs) {
                        throw new \Exception('error config mongodb');
                    }
                    $mongodb = $app->mongodb->handle($configs);
                    $app->context->put('mongodb_' . $this->config_name, $mongodb);
                }
                return $mongodb;
                break;
        }
    }

    function insertOne() 
    {
    }

    function insertMany() 
    {
    }

    function update() 
    {
    }

    function delete() 
    {
    }

    function find() 
    {
    }

    function get() 
    {
    }

    function setIndex() 
    {
    }
}