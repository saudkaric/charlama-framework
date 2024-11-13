<?php

if (!function_exists('view')) {
    function view($path, $data = [], $type = 'blade') {
        return Charlama\View\View::render($path, $data, $type);
    }
}

if (!function_exists('request')) {
    function request($key) {
        return \Charlama\Http\Request::value($key);
    }
}

if (!function_exists('redirect')) {
    function redirect($path) {
        return Charlama\Url\Url::redirect($path);
    }
}

if (!function_exists('previous')) {
    function previous() {
        return Charlama\Url\Url::previous();
    }
}

if (!function_exists('url')) {
    function url($path) {
        return Charlama\Url\Url::path($path);
    }
}

if (!function_exists('asset')) {
    function asset($path) {
        return Charlama\Url\Url::path('assets/' . $path);
    }
}

if (!function_exists('dnd')) {
    function dnd($value) {
        echo '<pre>';
        if (is_string($value)) {
            echo $value;
        } else {
            print_r($value);
        }
        echo '</pre>';
        die;
    }
}

if (!function_exists('session')) {
    function session($key) {
        return Charlama\Session\Session::get($key);
    }
}

if (!function_exists('flash')) {
    function flash($key) {
        return Charlama\Session\Session::flash($key);
    }
}

if (!function_exists('links')) {
    function links($current_page, $pages) {
        return \Charlama\Database\Database::links($current_page, $pages);
    }
}

if (!function_exists('auth')) {
    function auth($table) {
        $auth = Charlama\Session\Session::get($table) ?: \Charlama\Cookie\Cookie::get($table);
        
        return \Charlama\Database\Database::table($table)->where('id', $auth)->first();
    }
}