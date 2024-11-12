<?php

namespace Charlama\View;

use Charlama\File\File;
use Charlama\Session\Session;
use Exception;
use Jenssegers\Blade\Blade;

class View 
{
    private function __construct() {}
    
    public static function render($path, $data, $type = null)
    {
        $render = $type ? $type : 'blade';
        $render .= 'Render';
        
        $errors = Session::flash('errors');
        $old    = Session::flash('old');
        
        $data = array_merge($data, ['errors' => $errors, 'old' => $old]);
                
        return static::$render($path, $data);
    }
    
    public static function bladeRender($path, $data)
    {
        $blade = new Blade(File::path('views'), File::path('storage/cache'));
        return $blade->make($path, $data)->render();
    }

    public static function viewRender($path, $data)
    {
        $dir_sep = ['/', '\\', '.', '|', '@', '#'];
        $path = 'views' . File::ds() . str_replace($dir_sep, File::ds(), $path) . '.php';
        
        if (! File::exists($path)) {
            throw new Exception(sprintf('The view file %s does not exists', $path));
        }
        
        ob_start();
        extract($data);
        
        include_once File::path($path);
        $contetn = ob_get_contents();
        ob_end_clean();
        
        return $contetn;        
    }
}
