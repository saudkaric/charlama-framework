<?php

declare(strict_types=1);

namespace Charlama\Cookie;

class Cookie 
{
    public function __construct() {}
    
    
    public static function set(string $key, mixed $value): mixed
    {        
        setcookie($key, $value, strtotime( '+10 days' ), '/', '', false, true);
        
        return $value;
    }
    
    public static function has(string $key): bool
    {
        return isset($_COOKIE[$key]);
    }
    
    public static function get(string $key): mixed
    {
        return self::has($key) ? $_COOKIE[$key] : null;
    }
    
    public static function remove($key): void
    {
        if (self::has($key)) {
            setcookie($key, '', -1, '/', '', false, true);
        }
    }
    
    public static function all(): array
    {
        return $_COOKIE;
    }
    
    public static function destroy(): void
    {
        foreach (self::all() as $key => $value)
        {
            self::remove($key);
        }
    }
}
