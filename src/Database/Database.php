<?php declare(strict_types=1);

namespace Charlama\Database;

use Charlama\File\File;
use Charlama\Http\Request;
use Exception;
use PDO;
use PDOException;

class Database
{
    protected static string $select         = '';
    protected static string $table          = '';
    protected static string $join           = '';
    protected static string $where          = '';
    protected static string $group_by       = '';
    protected static string $having         = '';
    protected static string $order_by       = '';
    protected static string $limit          = '';
    protected static string $offset         = '';
    protected static string $setter         = '';
    protected static string $query          = '';
    protected static array $binding         = [];
    protected static array $where_binding   = [];
    protected static array $having_binding  = [];

    protected static $instance   = null;
    protected static $connection = null;

    public function __construct(string $table) {
        static::$table = $table;
    }

    protected static function connect(): void
    {
        if (! static::$connection) {
            extract(File::require_file('config/database.php'));

            $dsn = "mysql:host={$dbhost};dbname={$dbname};";

            $options = [
                PDO::ATTR_ERRMODE               => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE    => PDO::FETCH_OBJ,
                PDO::ATTR_PERSISTENT            => false,
                PDO::MYSQL_ATTR_INIT_COMMAND    => "set NAMES {$charset} COLLATE {$collation}",
            ];

            try {
                static::$connection = new PDO($dsn, $username, $passwrod, $options);
            } catch (PDOException $e) {
                throw new Exception($e->getMessage());
            }
        }
    }

    protected static function instance(): self
    {
        static::connect();
        
        $tabel = static::$table;

        if (! static::$instance)
            static::$instance = new Database($tabel);

        return static::$instance;
    }

    public static function query(?string $query = null): self
    {
        static::instance();

        if ($query == '') {
            $query = 'SELECT ';
            $query .= static::$select ?: '*';
            $query .= ' FROM `'. static::$table . '`';
            $query .= ' '. static::$join;
            $query .= ' '. static::$where;
            $query .= ' '. static::$group_by;
            $query .= ' '. static::$having;
            $query .= ' '. static::$order_by;
            $query .= ' '. static::$limit;
            $query .= ' '. static::$offset;

            if (!static::$table) {
                throw new Exception('Unknown table name!');
            }
        }

        static::$query = $query;
        static::$binding    = array_merge(static::$where_binding, static::$having_binding);

        return static::instance();
    }

    public static function select(): self
    {
        static::$select = implode(', ', func_get_args());

        return static::instance();
    }

    public static function table(string $tabel): self
    {
        static::$table = $tabel;

        return static::instance();
    }

    public static function join(string $tabel, string $first, string $second, string $operator = '=', string $type = 'INNER'): self
    {
        static::$join .= "{$type} JOIN {$tabel} ON {$first} {$operator} {$second} ";

        return static::instance();
    }

    public static function joinLeft(string $tabel, string $first, string $second, string $operator = '='): self
    {
        self::join($tabel, $first, $second, $operator, 'LEFT');
        return static::instance();
    }

    public static function joinRight(string $tabel, string $first, string $second, string $operator = '='): self
    {
        self::join($tabel, $first, $second, $operator, 'RIGHT');
        return static::instance();
    }

    public static function where(string $column, string|int $value, string $operator = '=', ?string $type = null): self
    {
        $where = "`{$column}` {$operator} ?";

        if (!static::$where) {
            $stmt = " WHERE {$where}";
        } else {
            if ($type == null) {
                $stmt = " AND {$where}";
            } else {
                $stmt = " {$type} {$where}";
            }
        }

        static::$where .= $stmt;
        static::$where_binding[] = htmlspecialchars((string)$value);

        return static::instance();
    }

    public static function orWhere(string $column, string|int $value, string $operator = '='): self
    {
        static::where($column, $value, $operator, 'OR');
        return static::instance();
    }

    public static function grouBy(): self
    {
        static::$group_by = 'GROUP BY ' . implode(', ', func_num_args());
        return static::instance();
    }

    public static function having(string $column, string|int $value, string $operator = '='): self
    {
        $having = "`{$column}` {$operator} ?";

        if (!static::$having) {
            $stmt = " HAVING {$having}";
        } else {
            $stmt = " AND {$having}";
        }

        static::$having .= $stmt;
        static::$having_binding[] = htmlspecialchars((string)$value);

        return static::instance();
    }

    public static function orderBy(string $column, ?string $type = null): self
    {
        $sep = static::$order_by ? ', ' : 'ORDER BY ';
        $type = ($type != null && in_array(strtoupper($type), ['ASC', 'DESC'])) ? strtoupper($type) : 'ASC';

        $stmt = "{$sep} {$column} {$type}";

        static::$order_by .= $stmt;
        return static::instance();
    }

    public static function limit(strin|int $limit): self
    {
        static::$limit = "LIMIT {$limit}";
        return static::instance();
    }

    public static function offset(strin|int $offset): self
    {
        static::$offset = "OFFSET {$offset}";
        return static::instance();
    }

    public static function fetchExecute(): mixed
    {
        static::query(static::$query);
        $query = trim(static::$query);

        $data = static::$connection->prepare($query);
        $data->execute(static::$binding);

        static::clear();

        return $data;
    }

    public static function getAll(): mixed
    {
        return self::fetchExecute()->fetchAll();
    }

    public static function get(): mixed
    {
        return self::fetchExecute()->fetch();
    }

    public static function execute(array $data, string $query, ?bool $where = null): void
    {
        static::instance();

        if (!static::$table) {
            throw new Exception('Unknown table!');
        }

        foreach ($data as $key => $value)
        {
            static::$setter .= " `{$key}` = ?, ";
            static::$binding[] = filter_var($value, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        }

        static::$setter = trim(static::$setter, ', ');

        $query .= static::$setter;
        $query .= $where != null ? static::$where . ' ' : '';

        static::$binding = $where != null ? array_merge(static::$binding, static::$where_binding) : static::$binding;

        $data = static::$connection->prepare($query);
        $data->execute(static::$binding);

        static::clear();
    }

    public static function insert(array $data): mixed
    {
        $table = static::$table;
        static::execute($data, "INSERT INTO `{$table}` SET ");

        $object_id = static::$connection->lastInsertId();
        $object = static::table($table)->where('id', $object_id)->get();

        if ($object)
            return $object;

        return false;
    }

    public static function update(array $data): bool
    {
        if (static::execute($data, "UPDATE `" . static::$table . "` SET ", true))
            return true;

        return false;
    }

    public static function delete(): bool
    {
        if (static::execute([], "DELETE FROM `" . static::$table . "` ", true))
            return true;

        return false;
    }

    public static function pagination(string|int $items_par_page = 15): mixed
    {
        static::query(static::$query);

        $query = trim(static::$query, ' ');

        $data = static::$connection->prepare($query);
        $data->execute();
        $pages = ceil($data->rowCount() / $items_par_page);

        $page = Request::get('page');
        $current_page = (! is_numeric($page) || $page < 1) ? '1' : $page;
        $offset = ($current_page - 1) * $items_par_page;
        static::limit($items_par_page);
        static::offset($offset);
        static::query();

        $data = static::fetchExecute();
        $result = $data->fetchAll();

        return ['data' => $result, 'items_per_page' => $items_par_page, 'pages' => $pages, 'current_page' => $current_page];
    }

    private static function clear(): void
    {
        static::$select     = '';
        static::$table      = '';
        static::$join       = '';
        static::$where      = '';
        static::$group_by   = '';
        static::$having     = '';
        static::$order_by   = '';
        static::$limit      = '';
        static::$offset     = '';
        static::$query      = '';
        static::$instance   = '';
        static::$binding        = [];
        static::$where_binding  = [];
        static::$having_binding = [];
    }


}
