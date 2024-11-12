<?php

namespace Charlama\Session;

class Session 
{
    private function __construct() {}
    
    public static function start()
    {
        if (!session_id()) {
            ini_set('session.use_only_cookies', 1);            
            session_start();
        }
    }
    
    public static function set($key, $value)
    {
        $_SESSION[$key] = $value;        
    }
    
    public static function has($key)
    {
        return isset($_SESSION[$key]);        
    }
    
    public static function get($key)
    {
        return self::has($key) ? $_SESSION[$key] : null;        
    }
    
    public static function remove($key)
    {
        if (self::has($key)) {
            unset($_SESSION[$key]);
        }        
    }
    
    public static function all()
    {
        return $_SESSION;        
    }
    
    public static function destroy()
    {
        foreach (self::all() as $key => $value)
        {
            self::remove($key);
        }
    }
    
    public static function flash($key)
    {
        $value = '';
        if (self::has($key)) {
            $value = self::get($key);
            self::remove($key);
        }
        return $value;
    }
}