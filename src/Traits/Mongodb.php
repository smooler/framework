<?php
namespace Smooler\Traits;

trait Mongodb
{
    protected $config_name;

    protected function __construct(){}

    function __get(string $key) 
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
                    $mongodb = new StdClass();
                    $mongodb->manager = new \MongoDB\Driver\Manager("mongodb://" . $config['username'] . ':' . $config['password'] . '@' . $configs['host'] . ":" . $configs['port'] . '/' . $configs['database']);
                    $mongodb->bulk = new \MongoDB\Driver\BulkWrite;
                    $mongodb->write_concern = new \MongoDB\Driver\WriteConcern(MongoDB\Driver\WriteConcern::MAJORITY, 1000);
                    $app->context->put('mongodb_' . $this->config_name, $mongodb);
                }
                return $mongodb;
                break;
        }
    }

    final public function db(string $database) 
    {
        global $app;
        $app->context->put('mongodb_' . get_called_class() . '_database', $database);
        return $this;
    }

    final public function collection(string $collection) 
    {
        global $app;
        $app->context->put('mongodb_' . get_called_class() . '_collection', $collection);
        return $this;
    }

    function insert(array &$data) 
    {
        $this->instance->bulk->insert($data);
        return $this;
    }

    function update(array &$where, array &$data, boolean $upsert = false) 
    {
        $this->instance->bulk->update(
            $where,
            $data,
            [
                'multi' => true, 
                'upsert' => $upsert
            ]
        );
        return $this;
    }

    function delete(array &$where, boolean $limit = false) 
    {
        $this->instance->bulk->delete(
            $where,
            [
                'limit' => $limit
            ]
        );
        return $this;
    }

    function find(array &$filter, array &$options) 
    {
        $options = array_merge($options, ['limit' => 1]);
        $result = $this->get($filter, $options);
        $result = json_decode(json_encode($result), true);
        return $result[0] ?? null;
    }

    function get(array &$filter, array &$options) 
    {
        global $app;
        $db = $app->context->get('mongodb_' . get_called_class() . '_database');
        $collection = $app->context->get('mongodb_' . get_called_class() . '_collection');
        $query = new MongoDB\Driver\Query($filter, $options);
        $result = $this->instance->manager->executeQuery($db . '.' . $collection, $query);
        $result = json_decode(json_encode($result), true);
        return $result ?? [];
    }

    function count(array &$where) 
    {
        global $app;
        $db = $app->context->get('mongodb_' . get_called_class() . '_database');
        $collection = $app->context->get('mongodb_' . get_called_class() . '_collection');
        $command = new \MongoDB\Driver\Command(
            [
                'count' => $collection, 
                'query' => $where
            ]
        );
        $result = $this->instance->manager->executeCommand($db, $command);
        $result = $result->toArray();
        $count = 0;
        $result && $count = $result[0]->n;
        return $count;
    }

    function write() 
    {
        global $app;
        $db = $app->context->get('mongodb_' . get_called_class() . '_database');
        $collection = $app->context->get('mongodb_' . get_called_class() . '_collection');
        return $this->instance->manager->executeBulkWrite($db . '.' . $collection, $this->instance->bulk, $this->instance->write_concern);
    }

    function createIndexes(array &$indexes) 
    {
        global $app;
        $db = $app->context->get('mongodb_' . get_called_class() . '_database');
        $collection = $app->context->get('mongodb_' . get_called_class() . '_collection');
        $command = new \MongoDB\Driver\Command(
            [
                'createIndexes' => $collection, 
                'indexes' => $indexes
            ]
        );
        $this->instance->manager->executeCommand($db, $command);
    }

    function command(string $action, array &$command) 
    {
        global $app;
        $db = $app->context->get('mongodb_' . get_called_class() . '_database');
        $collection = $app->context->get('mongodb_' . get_called_class() . '_collection');
        $command[$action] = $collection;
        $command = new \MongoDB\Driver\Command($command);
        return $this->instance->manager->executeCommand($db, $command);
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