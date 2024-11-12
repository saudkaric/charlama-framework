<?php

namespace Charlama\Exceptions;


class Whoops 
{
    private function __construct() {}
    
    public static function errorHandle()
    {
        // Report all PHP errors
        error_reporting(-1);
        ini_set('display_errors', 1);
        
        if (DEBUG && ENV === 'produciton') {

            ini_set('display_errors', 0);
            
            $errors = error_get_last();
            
            if ($errors != null || $errors != '')
                die('There is an erroro, pleas tye agien');            
            
            
        } else {
            $whoops = new \Whoops\Run;
            $whoops->pushHandler(new \Whoops\Handler\PrettyPageHandler);
            $whoops->register();
        }
        
        
 
        
        
        
        
                
        
    }
    
}
