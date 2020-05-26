<?php
namespace Smooler\Traits;

trait Mongodb
{
    protected $config_name;

    protected function __construct(){}

    final function __get(string $key) 
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
                    $mongodb->bulk = new MongoDB\Driver\BulkWrite;
                    $mongodb->write_concern = new MongoDB\Driver\WriteConcern(MongoDB\Driver\WriteConcern::MAJORITY, 1000);
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
    }

    function delete(array &$where, boolean $limit = false) 
    {
        $this->instance->bulk->delete(
            $where,
            [
                'limit' => $limit
            ]
        );
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
        $query = new MongoDB\Driver\Query($filter, $options);
        $result = $this->instance->manager->executeQuery($this->database . '.' . $this->collection, $query);
        $result = json_decode(json_encode($result), true);
        return $result ?? [];
    }

    function count(array &$where) 
    {
        $command = new MongoDB\Driver\Command(['count' => $this->collection, 'query' => $where]);
        $result = $this->instance->manager->executeCommand($this->database, $command);
        $result = $result->toArray();
        $count = 0;
        $result && $count = $result[0]->n;
        return $count;
    }

    function write() 
    {
        return $this->instance->manager->executeBulkWrite($this->database . '.' . $this->collection, $this->bulk, $this->writeConcern);
    }

    function setIndex() 
    {
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