<?php

namespace Charlama\Http;

class Response 
{
    private function __construct() {}
        
    public static function output($data)
    {
        if (! $data)
            return;
        
        if (!is_string($data)) {
            $data = static::json($data);
        }
        
        echo $data;
    }
    
    public static function json($data)
    {
        return json_encode($data);
    }
}
