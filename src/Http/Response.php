<?php declare(strict_types=1);

namespace Charlama\Http;

class Response 
{
    public function __construct() {}
    
    public static function output(mixed $data)
    {
        if (!$data) return null;
        
        if (!is_string($data)) {
            $data = static::json($data);
        }
        
        echo $data;
    }
    
    public static function json(mixed $data): mixed
    {
        return json_encode($data);
    }
}
