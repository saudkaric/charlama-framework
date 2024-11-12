<?php

namespace Charlama\Cookie;

class Cookie
{

    private function __construct() {}

    public static function set($key, $value)
    {
        setcookie($key, $value, strtotime( '+7 days' ), '/', '', false, true);
    }
    
    public static function has($key)
    {
        return isset($_COOKIE[$key]);        
    }
    
    public static function get($key)
    {
        return self::has($key) ? $_COOKIE[$key] : null;        
    }
    
    public static function remove($key)
    {
        if (self::has($key)) {
            setcookie($key, '', '-1', '/', '', false, true);
        }        
    }
    
    public static function all()
    {
        return $_COOKIE;
    }
    
    public static function destroy()
    {
        foreach (self::all() as $key => $value)
        {
            self::remove($key);
        }
    }
}