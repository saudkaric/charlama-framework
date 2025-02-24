<?php declare(strict_types=1);

namespace Charlama\Router;

use BadFunctionCallException;
use Charlama\Http\Request;
use Charlama\Url\Url;
use InvalidArgumentException;
use ReflectionException;

class Route 
{
    private static array $routes        = [];
    
    private static string $prefix       = '';
    
    private static string $middleware   = '';
    
    public function __construct() {}
    
    private static function add(string $methods, string $path, mixed $callback): void 
    {
        $uri = rtrim(static::$prefix . '/' . trim($path, '/'), '/');
        $uri = $uri?:'/';
        
        foreach (explode('|', $methods) as $method)
        {
            static::$routes[] = [
                'uri'           => $uri,
                'method'        => $method,
                'callback'      => $callback,
                'middleware'    => static::$middleware,
            ];
        }
    }
    
    public static function get(string $path, mixed $callback): void 
    {
        static::add('get', $path, $callback);
    }
    
    public static function post(string $path, mixed $callback): void 
    {
        static::add('post', $path, $callback);
    }
    
    public static function put(string $path, mixed $callback): void 
    {
        static::add('put', $path, $callback);
    }
    
    public static function patch(string $path, mixed $callback): void 
    {
        static::add('patch', $path, $callback);
    }
    
    public static function delete(string $path, mixed $callback): void 
    {
        static::add('delete', $path, $callback);
    }
    
    public static function any(string $path, mixed $callback): void 
    {
        static::add('get|post', $path, $callback);
    }
    
    public static function prefix(string $prefix, mixed $callback): void
    {
        $parent_prefix = static::$prefix;
        
        static::$prefix .= '/' . trim($prefix, '/');
        
        if (is_callable($callback)) {
            call_user_func($callback);
        } else {
            throw new BadFunctionCallException("Please provide valid callback function");
        }
        
        static::$prefix = $parent_prefix;
    }
    
    public static function middleware(string $middleware, mixed $callback): void
    {        
        $parent_middleware = static::$middleware;
        
        static::$middleware .= '|' . trim($middleware, '|');
        
        if (is_callable($callback)) {
            call_user_func($callback);
        } else {
            throw new BadFunctionCallException("Please provide valid callback function");
        }
        
        static::$middleware = $parent_middleware;
    }
    
    public static function handel(): mixed
    {
        $method = Request::method();
        $uri    = (Request::url() != '/') ? '/' . Request::url() : Request::url();
                      
        foreach (static::$routes as $route)
        {
            $matched = true;
            $route['uri'] = preg_replace('/\/{(.*?)}/', '/(.*?)', $route['uri']);
            
            $patern = '#^' . $route['uri'] . '$#';
            
            if (preg_match($patern, $uri, $matches)) {
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
                
                if ($matched === true) {
                    return static::invoke($route, $params);
                }
            }
        }
        
        return Url::redirect(Url::path('not-found'));
    }
    
    public static function invoke(array $route, array $params = []): mixed
    {
        static::executeMiddleware($route);
        
        $callback = $route['callback'];
        
        if (is_callable($callback)) {
            return call_user_func_array($callback, $params);
        } else {
            
            if (is_string($callback) && strpos($callback, '@') !== false) {
                list($controller, $method) = explode('@', $callback);
            }

            if (is_array($callback)) {
                $controller = $callback[0];
                $method     = $callback[1];
            }

            $controller = 'App\\Controllers\\' . ucfirst($controller) . 'Controller';
            $action     = $method . 'Action';

            if (! class_exists($controller)) {
                throw new ReflectionException(sprintf("The calss '%s' does not exists", $controller));
            }

            $object = new $controller;

            if (! method_exists($object, $action)) {
                throw new BadFunctionCallException(sprintf("The metodh '%s' does not exists at '%s'", $action, $controller));
            }

            return call_user_func_array([$object, $action], $params);
        }
        
        throw new InvalidArgumentException("Please provide valid callback function");
    }
    
    public static function executeMiddleware(array $route) 
    {
        foreach (explode('|', $route['middleware']) as $middleware)
        {
            if ($middleware != '') {
                
                $middleware = 'App\\Middlewares\\' . ucfirst($middleware) . 'Middleware';
                $action     = 'handle';
                
                if (! class_exists($middleware)) {
                    throw new ReflectionException(sprintf("The calss '%s' does not exists", $middleware));
                }
                
                $object = new $middleware;
                
                if (! method_exists($object, $action)) {
                    throw new BadFunctionCallException(sprintf("The metodh '%s' does not exists at '%s'", $action, $middleware));
                }
                
                call_user_func_array([$object, $action], []);
            }
        }
    }
    
}
