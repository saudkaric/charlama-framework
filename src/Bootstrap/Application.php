<?php

namespace Charlama\Bootstrap;

use Charlama\Exceptions\Whoops;
use Charlama\File\File;
use Charlama\Http\Request;
use Charlama\Http\Response;
use Charlama\Router\Route;
use Charlama\Session\Session;

class Application 
{
    private function __construct() {}
    
    public static function run()
    {
        // Handle the errors
        Whoops::errorHandle();
        // Handle the session and cookies
        Session::start();
        // Handle the request
        Request::handle();
        // Require all routes directory
        File::require_directory('routes');
        // Handle the route and retun the response
        Response::output(Route::handle());
    }
}