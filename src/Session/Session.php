<?php declare(strict_types=1);

namespace Charlama\Session;

class Session 
{
    public function __construct() {}
    
    public static function start() : void
    {
        if (!session_id()) {
            ini_set('session.use_only_cookies', 1);
            session_start();
        }
    }
    
    public static function set(string $key, mixed $value): mixed
    {
        $_SESSION[$key] = $value;
        
        return $value;
    }
    
    public static function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }
    
    public static function get(string $key): mixed
    {
        return self::has($key) ? $_SESSION[$key] : null;
    }
    
    public static function remove($key): void
    {
        if (self::has($key)) {
            unset($_SESSION[$key]);
        }
    }
    
    public static function all(): array
    {
        return $_SESSION;
    }
    
    public static function destroy(): void
    {
        foreach (self::all() as $key => $value)
        {
            self::remove($key);
        }
    }
    
    public static function flash(string $key): mixed
    {
        $message = null;
        
        if (self::has($key)) {
            $message = self::get($key);
            self::remove($key);
        }
        
        return $message;        
    }
}
