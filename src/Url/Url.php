<?php

namespace Charlama\Url;

use Charlama\Http\Request;

class Url
{
    private function __construct() {}

    //put your code here
    public static function path($path)
    {
        return Request::baseUrl() . '/' . trim($path, '/');        
    }
    
    public static function previous()
    {
        return Request::previous();
    }
    
    public static function redirect($path)
    {
        header('location: ' . $path);
        exit();
    }
}
