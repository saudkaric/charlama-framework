<?php

namespace Charlama\Router;

use BadFunctionCallException;
use InvalidArgumentException;
use Charlama\Http\Request;
use ReflectionException;

class Route
{
    private static $routes      = [];
    private static $middleware  = '';
    private static $prefix      = '';

    private function __construct() {}
        
    private static function add($method, $uri, $callback)
    {
        $uri = rtrim(static::$prefix . '/' . trim($uri, '/'), '/');
        $uri = $uri?:'/';
        
        static::$routes[] = [
            'uri'        => $uri,
            'callback'   => $callback,
            'method'     => $method,
            'middleware' => static::$middleware
        ];
    }
    
    public static function get($uri, $callback)
    {
        static::add('get', $uri, $callback);
    }
    
    public static function post($uri, $callback)
    {
        static::add('post', $uri, $callback);
    }
    
    public static function delete($uri, $callback)
    {
        static::add('delete', $uri, $callback);
    }
    
    public static function put($uri, $callback)
    {
        static::add('put', $uri, $callback);
    }
    
    public static function patch($uri, $callback)
    {
        static::add('patch', $uri, $callback);
    }
    
    public static function prefix($prefix, $callback)
    {
        $parent_prefix = static::$prefix;
        
        static::$prefix .= '/' . trim($prefix, '/');
        
        if (is_callable($callback)) {
            call_user_func($callback);
        } else {
            throw new BadFunctionCallException('Please provide valide callback function!');
        }
        
        static::$prefix = $parent_prefix;
    }

    public static function middleware($middleware, $callback)
    {        
        $parent_middleware = static::$middleware;
        
        static::$middleware.= '|' . trim($middleware, '|');
        
        if (is_callable($callback)) {
            call_user_func($callback);
        } else {
            throw new BadFunctionCallException('Please provide valide callback function!');
        }
        
        static::$middleware = $parent_middleware;
    }

    public static function handle()
    {
        $uri = Request::url();
        $method = Request::post('__method') ?? Request::method();
        
        foreach (static::$routes as $route)
        {
            $matched = true;
            $route['uri'] = preg_replace('/\/{(.*?)}/', '/(.*?)', $route['uri']);
            $route['uri'] = '#^' . $route['uri'] . '$#';
            
            if (preg_match($route['uri'], $uri, $matches)) {
                array_shift($matches);
                $params = array_values($matches);
                
                foreach ($params as $param)
                {
                    if (strpos($param, '/')) {
                        $matched = false;
                    }
                }
                
                if ($route['method'] != $method) {
                    $matched = false;
                }
                
                if ($matched == true) {
                    return static::invoke($route, $params);
                }
            }
        }
        
        return redirect('not-found');
    }
    
    private static function invoke($route, $params = [])
    {
        static::executeMiddleware($route);
        
        $callback = $route['callback'];
        
        if (is_callable($callback)) {
            return call_user_func_array($callback, $params);
        } elseif(strpos($callback, '@') !== false) {
            list($controller, $action) = explode('@', $callback);
            
            $controller = 'App\\Controllers\\' . ucfirst($controller) . 'Controller';
            $action     = $action . 'Action';
            
            if (class_exists($controller)) {
                $object = new $controller();
                
                if (method_exists($object, $action)) {
                    return call_user_func_array([$object, $action], $params);
                } else {
                    throw new BadFunctionCallException(sprintf(
                            'The method %s is not exists at %s'
                            ,$action, $controller));
                }
            } else {
                throw new ReflectionException(sprintf(
                    'Class %s does not exitsts',
                    $controller));
            }
        } else {
            throw new InvalidArgumentException('Please provide valid callback funciton');
        }
    }
    
    private static function executeMiddleware($route)
    {
        foreach (explode('|', $route['middleware']) as $middleware)
        {
            if ($middleware != '') {
                $middleware = 'App\\Middlewares\\' . ucfirst($middleware) . 'Middleware';
                
                if (class_exists($middleware)) {
                    $objet = new $middleware();
                    call_user_func([$objet, 'handle'], []);
                } else {
                    throw new ReflectionException(sprintf(
                    'Class %s does not exitsts',
                    $middleware));
                }
            }
        }
    }
}
