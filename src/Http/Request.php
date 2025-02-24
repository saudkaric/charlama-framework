<?php declare(strict_types=1);

namespace Charlama\Http;

class Request
{
    //put your code here
    protected static string $base_url;

    protected static string $url;

    protected static string $full_url;

    protected static string $query_string;

    protected static string $script_name;

    public function __construct() {}

    public static function handel(): void
    {
        self::$script_name = str_replace('\\', '', dirname(Server::get('SCRIPT_NAME')));

        self::setBaseUrl();
        self::setUrl();
    }

    private static function setBaseUrl(): void
    {
        $protocol = Server::get('REQUEST_SCHEME') . '://';
        $host = Server::get('HTTP_HOST');

        self::$base_url = $protocol . $host . self::$script_name;
    }

    private static function setUrl(): void
    {
        $request_uri = urldecode(Server::get('REQUEST_URI'));
        $request_uri = preg_replace('#^' . self::$script_name . '#', '', $request_uri);
        $request_uri = rtrim($request_uri, '/');

        self::$full_url = $request_uri;

        $query_string = '';

        if (str_contains($request_uri, '?')) {
            list($request_uri, $query_string) = explode('?', $request_uri);
        }

        self::$url = $request_uri?:'/';
        self::$query_string = $query_string;

    }

    public static function baseUrl(): string
    {
        return trim(self::$base_url, '/');
    }

    public static function url(): string
    {
        return self::$url;
    }

    public static function fullUrl(): string
    {
        return self::$full_url;
    }

    public static function queryString(): string
    {
        return self::$query_string;
    }

    public static function method(): string
    {
        return strtolower($_POST['__method'] ?? Server::get('REQUEST_METHOD'));
    }

    public static function has(array $type, string $key): bool
    {
        return array_key_exists($key, $type);
    }

    public static function value(string $key, ?array $type = null): mixed
    {
        $type = $type ?? $_REQUEST;

        return self::has($type, $key) ? $type[$key] : null;
    }

    public static function get(string $key): ?string
    {
        return self::value($key, $_GET);
    }

    public static function post(string $key): ?string
    {
        return self::value($key, $_POST);
    }

    public function set(string $key, string $value): ?string
    {
        $_REQUEST[$key] = $value;
        $_GET[$key] = $value;
        $_POST[$key] = $value;

        return $value;
    }

    public static function previous(): string
    {
        return Server::get('HTTP_REFERER');
    }

    public static function all(): array
    {
        return $_REQUEST;
    }
}
