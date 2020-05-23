<?php
namespace Smooler;

use Smooler\Exceptions\Http;

class Route 
{
    protected $resources = ['get', 'post', 'delete', 'put'];
    protected $routes = [
        'get' => [],
        'post' => [],
        'delete' => [],
        'put' => [],
    ];

    function __construct() 
    {
        $routepath = BASE_DIR . '/route/';
        $filesnames = scandir($routepath);
        foreach ($filesnames as $file) {
            if (is_file($routepath . $file)) {
                require_once $routepath . $file;
            }
        }
    }

    function handle() 
    {
        global $app;
        $request = $app->context->get('request');
        $method = $request->server["request_method"];
        $method = strtolower($method);
        $url = $request->server["request_uri"];
        $url = strtolower(rtrim($url, '/'));
        if (!isset($this->routes[$method])) {
            throw new Http(404, 'not_found');
        }
        $res = [];
        $params = [];
        if (isset($this->routes[$method][$url])) {
            $res = $this->routes[$method][$url];
        } else {
            foreach ($this->routes[$method] as $key => $value) {
                $status = strpos($key, '{');
                if (false === $status) {
                    continue;
                }
                $params = $this->matchUrl($key, $url);
                if (is_array($params)) {
                    $res = $value;
                    break;
                }
            }
        }
        if (!$res) {
            throw new Http(404, 'not_found');
        }
        if (!empty($res['middlewares'])) {
            global $app;
            foreach ($res['middlewares'] as $value) {
                $middlewareRes = $app->middleware->getMiddlewareIoc($value)->handle($request);
                if (isset($middlewareRes['error'])) {
                    return $middlewareRes;
                }
            }
        }
        return [
            $res['controller'],
            $res['action'],
            $params,
        ];
    }

    function matchUrl($route, $url) 
    {
        $routeRe = preg_replace('#(\{.+?\})#', '([^/]+?)', $route);
        preg_match('#^' . $routeRe . '$#', $url, $params);
        if (isset($params[0])) {
            unset($params[0]);
            return array_values($params);
        }
    }

    function __call($method, $args) 
    {
        if (in_array($method, $this->resources)) {
            if (isset($this->routes[$method][$args[0]])) {
                throw new \Exception($method.':'.$args[0].':unique');
            }
            $this->routes[$method][$args[0]] = [
                'controller' => $args[1],
                'action' => $args[2],
                'middlewares' => $args[3] ?? []
            ];
        }
    }
}
