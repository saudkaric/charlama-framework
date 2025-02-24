<?php declare(strict_types=1);

namespace Charlama\File;

class File 
{
    //put your code here
    public function __construct() {}
    
    public static function root(): string 
    {
        return ROOT_DIR;
    }
    
    public static function ds(): string 
    {
        return DS;        
    }
    
    public static function path(string $path): string
    {
        $path = self::root() . self::ds() . trim($path, '/');
        $path = str_replace(['/', '\\', '|', '@'], self::ds(), $path);
        
        return $path;
    }
    
    public static function exists(string $path): bool
    {
        return file_exists(self::path($path));        
    }
    
    public static function require_file(string $path): mixed 
    {
        if (self::exists($path)) {
            return require_once self::path($path);
        }
    }
    
    public static function include_file(string $path): mixed 
    {
        if (self::exists($path)) {
            return include_once self::path($path);
        }
    }
    
    public static function require_dir(string $path): void
    {
        $files = array_diff(scandir(self::path($path)), ['.', '..']);
        
        foreach ($files as $file)
        {
            $file_path = $path . self::ds() . $file;
            
            if (self::exists($file_path)) {
                static::require_file($file_path);
            }
        }
    }
    
}
