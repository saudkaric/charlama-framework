<?php declare(strict_types=1);

namespace Charlama\Url;

use Charlama\Http\Request;

class Url
{
    public function __construct() {}
    //put your code here

    public static function path(string $path): string
    {
        return Request::baseUrl() . '/' . trim($path, '/');
    }

    public static function previous(): string
    {
        return Request::previous();
    }

    public static function redirect(string $path)
    {
        header('location: ' . $path);
        exit();
    }

}
