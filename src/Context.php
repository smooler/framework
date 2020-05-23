<?php
namespace Smooler;

use Swoole\Coroutine;

class Context
{
    function get($key)
    {
        return Co::getContext()[$key];
    }

    function put($key, $item)
    {
        Co::getContext()[$key] = $item;
    }

    function push($key, $item)
    {
        Co::getContext()[$key][] = $item;
    }

    function delete($key)
    {
        Co::getContext()[$key] = null;
    }

    function getData()
    {
        $data = &$this->get('data');
        if (!$data) {
            $request = $this->get('request');
            switch ($request->server["request_method"]) {
                case 'GET':
                    $data = $request->get;
                    break;
                default:
                    $data = $request->post;
                    if (!$data) {
                        $data = json_decode($request->rawContent(), true);
                    }
                    break;
            }
            $this->put('data', $data);
        }
        return $data;
    }
}