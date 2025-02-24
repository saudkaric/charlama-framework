<?php

declare(strict_types=1);

namespace Charlama\Http;

class Server 
{
    //put your code here
    public function __construct() {}
    
    public static function has(string $key): bool
    {
        return isset($_SERVER[$key]);
    }
    
    public static function get(string $key): ?string
    {
        return self::has($key) ? $_SERVER[$key] : null;
    }
    
    public static function all(): array
    {
        return $_SERVER;
    }
    
    public static function path_info(string $path): array
    {
        return pathinfo($path);
    }
}
